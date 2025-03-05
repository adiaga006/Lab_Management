<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/layout/footer.php');
include('./constant/connect.php');
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;
// Lấy `start_date` từ bảng `case_study` dựa trên `case_study_id`
$sql = "SELECT start_date FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$stmt->bind_result($startDate);
$stmt->fetch();
$stmt->close();

if (!$startDate) {
    die("Error: start_date not found for the given case_study_id");
}

// Encode start_date thành định dạng JSON để sử dụng trong JavaScript
$startDateJs = json_encode($startDate);
if (!$caseStudyId) {
    die("Error: Missing case_study_id in URL");
}
$recentEntriesSql = "
    SELECT treatment_name, lab_day, survival_sample, feeding_weight, entry_data_id ,rep
    FROM entry_data 
    WHERE case_study_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5";
$stmt = $connect->prepare($recentEntriesSql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$recentEntriesResult = $stmt->get_result();
$recentEntries = $recentEntriesResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Fetch duration của tất cả phases từ database
$sql = "SELECT phases FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$stmt->bind_result($phasesJson);
$stmt->fetch();
$stmt->close();

// Giải mã phases JSON và tính tổng duration
$phases = json_decode($phasesJson, true);
$totalDuration = 0;
$preChallengeDuration = (int) $phases[0]['duration'] + (int) $phases[1]['duration'];

if (is_array($phases)) {
    foreach ($phases as $phase) {
        $totalDuration += (int) $phase['duration'];
    }
}

// Tính `end_date` dựa trên `start_date` và tổng duration (trừ 1 ngày)
$endDate = (new DateTime($startDate))
    ->modify("+$totalDuration days") // Cộng tổng duration
    ->modify("-1 day")              // Trừ đi 1 ngày
    ->format('Y-m-d');              // Định dạng kết quả thành Y-m-d


// Encode end_date thành JSON để sử dụng trong JavaScript
$endDateJs = json_encode($endDate);

// Lấy thông tin case study và category ID
$sql = "SELECT case_name, categories_id FROM case_study WHERE case_study_id = '$caseStudyId'";
$caseStudyResult = $connect->query($sql);
$caseStudy = $caseStudyResult->fetch_assoc();
$categoryId = $caseStudy['categories_id'];

// Lấy các nhóm liên quan đến category của case study
$groupSql = "SELECT g.group_id, g.group_name 
             FROM category_groups cg
             JOIN groups g ON cg.group_id = g.group_id
             WHERE cg.category_id = '$categoryId'
             ORDER BY g.group_id ASC"; // Added ORDER BY clause
$groupResult = $connect->query($groupSql);
?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-12 d-flex justify-content-between align-items-start">
            <h3 class="page-title">
                <i class="fa fa-flask"></i> Case Study: <?php echo htmlspecialchars($caseStudyId); ?>
            </h3>
            <div class="button-container d-flex flex-column align-items-end">
                <div class="top-buttons">
                    <a href="schedule.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
                        class="btn btn-schedule btn-lg">
                        <i class="fa fa-calendar"></i> Schedule
                    </a>
                    <a href="chart.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
                        class="btn btn-chart btn-lg">
                        <i class="fa fa-pie-chart"></i> Show Chart
                    </a>
                    <a href="results.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
                        class="btn btn-results btn-lg">
                        <i class="fa fa-bar-chart"></i> Show Results
                    </a>
                    <button class="btn btn-info" onclick="showQRCode()">
                        <i class="fas fa-qrcode"></i> Show QR
                    </button>
                </div>
                <a href="manage_image.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
                    class="btn btn-media btn-lg mt-2">
                    <i class="fa fa-image"></i> Show Image / Video
                </a>
            </div>
        </div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <?php if ($groupResult->num_rows > 0): ?>
                        <table class="table table-bordered table-striped table-custom">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="fa fa-database"></i> Raw Data
                                    </th>
                                    <th style="text-align: center; vertical-align: middle;">
                                        <i class="fa fa-cogs"></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($group = $groupResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            // Determine URL based on group_id and include group_id in the URL
                                            if ($group['group_id'] == 1) {
                                                $url = "entry_data.php?case_study_id=" . htmlspecialchars($caseStudyId) . "&group_id=" . htmlspecialchars($group['group_id']);
                                            } elseif ($group['group_id'] == 3) {
                                                $url = "water_quality.php?case_study_id=" . htmlspecialchars($caseStudyId) . "&group_id=" . htmlspecialchars($group['group_id']);
                                            } elseif ($group['group_id'] == 2) {
                                                $url = "death_data.php?case_study_id=" . htmlspecialchars($caseStudyId) . "&group_id=" . htmlspecialchars($group['group_id']);
                                            } elseif ($group['group_id'] == 4) {
                                                $url = "shrimp_weight.php?case_study_id=" . htmlspecialchars($caseStudyId) . "&group_id=" . htmlspecialchars($group['group_id']);
                                            } else {
                                                $url = "#";
                                            }

                                            ?>
                                            <a href="<?php echo $url; ?>">
                                                <?php echo htmlspecialchars($group['group_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <!-- Add Data button triggers modal for each group -->
                                            <?php if ($group['group_id'] == 1): ?>
                                                <div class="button-container">
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#addDataModalGroup1"
                                                        onclick="openAddDataModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                        Add Data
                                                    </button>
                                                </div>

                                            <?php elseif ($group['group_id'] == 2): ?>
                                                <div class="button-container">
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#addDataModalGroup2"
                                                        onclick="openShrimpDeathModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                        Add Data
                                                    </button>
                                                </div>
                                            <?php elseif ($group['group_id'] == 3): ?>
                                                <div class="button-container">
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#addDataModalGroup3"
                                                        onclick="openWaterQualityModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                        Add Data
                                                    </button>
                                                </div>
                                            <?php elseif ($group['group_id'] == 4): ?>
                                                <div class="button-container">
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#addDataModalGroup4"
                                                        onclick="openShrimpWeightModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                        Add Data
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="button-container">
                                                    <button class="btn btn-primary"
                                                        onclick="alert('Add Data feature is not available for this group.');">
                                                        Add Data
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                </div>
            <?php else: ?>
                <p>No groups available for this category.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Data Modal for Group 1 -->
<div id="addDataModalGroup1" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addDataForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 style="color: black" class="modal-title">Add Data</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId">
                    <div class="form-group">
                        <label>Treatment Name</label>
                        <select name="treatment_name" id="treatmentName" class="form-control" required
                            onchange="updateProductApplication()">
                            <option value="" disabled selected hidden>Select Treatment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Application</label>
                        <input type="text" name="product_application" class="form-control" id="productApplication"
                            readonly required>
                    </div>

                    <div class="form-group">
                        <label>Day (DD/MM/YYYY)</label>
                        <input type="text" name="lab_day" id="testTimePicker" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Rep</label>
                        <input type="number" name="rep" class="form-control" required min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label>Survival Sample</label>
                        <input type="number" name="survival_sample" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Feed Intake(g)</label>
                        <input type="number" step="0.01" name="feeding_weight" class="form-control" required>
                    </div>
                </div>

                <div id="recentEntriesContainer" style="font-size: 12px; line-height: 1.6;">
                    <?php if (!empty($recentEntries)): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Treatment Name</th>
                                    <th>Day</th>
                                    <th>Rep</th> <!-- Thêm cột Rep -->
                                    <th>Survival Sample</th>
                                    <th>Feed Intake(g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEntries as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['treatment_name']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($entry['lab_day'])); ?></td>
                                        <td><?php echo htmlspecialchars($entry['rep']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['survival_sample']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['feeding_weight']); ?> g</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No recent entries found.</p>
                    <?php endif; ?>
                </div>


                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="submitEntryData()">Add</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Add Data Modal for Group 2 (Shrimp Death Data) -->
<div id="addDataModalGroup2" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addShrimpDeathDataForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 style="color: black" class="modal-title">Add Shrimp Death Data</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId2">

                    <div class="form-group">
                        <label>Treatment Name</label>
                        <select name="treatmentNameGroup2" id="treatmentNameGroup2" class="form-control" required
                            onchange="updateProductApplicationGroup2()">
                            <option value="" disabled selected hidden>Select Treatment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Application</label>
                        <input type="text" name="productApplicationGroup2" class="form-control"
                            id="productApplicationGroup2" readonly required>
                    </div>
                    <div class="form-group">
                        <label>Rep</label>
                        <input type="number" name="rep_2" class="form-control" required min="1" value="1">
                    </div>
                    <div class="form-group">
                        <label>Death Sample</label>
                        <input type="number" name="death_sample" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Test Time (Date & Time)</label>
                        <!-- Nhập ngày -->
                        <input type="text" name="test_date" id="testTimePicker" class="form-control" required>
                        <!-- Nhập giờ -->
                        <select name="test_hour" id="testHour" class="form-control mt-2" required>
                            <option value="" disabled selected hidden>Select Hour</option>
                            <option value="03">03:00</option>
                            <option value="07">07:00</option>
                            <option value="11">11:00</option>
                            <option value="15">15:00</option>
                            <option value="19">19:00</option>
                            <option value="23">23:00</option>
                        </select>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="submitShrimpDeathData()">Add</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
                <div id="recentEntriesContainerGroup2" class="mt-4"></div>
            </div>
        </form>
    </div>
</div>

<!-- Add Data Modal for Group 3 (Water Quality) -->
<div id="addDataModalGroup3" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addWaterQualityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 style="color: black" class="modal-title">Add Water Quality Data</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId3">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Day</label>
                            <input type="text" name="day" id="testTimePicker" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Salinity (ppt)</label>
                            <input type="number" name="salinity" class="form-control" step="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Temperature (°C)</label>
                            <input type="number" name="temperature" class="form-control" step="0.1" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Dissolved Oxygen (ppm)</label>
                            <input type="number" name="dissolved_oxygen" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>pH</label>
                            <input type="number" name="pH" class="form-control" step="0.1" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Alkalinity (ppm)</label>
                            <input type="number" name="alkalinity" class="form-control" step="0.1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>TAN (ppm)</label>
                            <input type="number" name="tan" class="form-control" step="0.1" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Nitrite (ppm)</label>
                            <input type="number" name="nitrite" class="form-control" step="0.1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label>System Type</label>
                            <select name="system_type" class="form-control" required>
                                <option value="" disabled selected hidden>Select System</option>
                                <option value="RAS System (NC, PC & Treatments)">RAS System (NC, PC & Treatments)
                                </option>
                                <option value="Static System (Negative Control)">Static System (Negative Control)
                                </option>
                                <option value="RAS System (Positive Control & Treatments)">RAS System (Positive
                                    Control & Treatments)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="submitWaterQualityData()">Add</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Add Data Modal for Group 4 (Shrimp Weight) -->
<div id="addDataModalGroup4" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Shrimp Weight Data</h5>
                </button>
            </div>
            <div class="modal-body">
                <form id="addShrimpWeightForm">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId4">

                    <div class="form-group">
                        <label>Number of Shrimps</label>
                        <input type="number" id="shrimpCount" class="form-control" min="1" max="100" required>
                        <small class="form-text text-muted">Enter the number of shrimps to measure (max 100)</small>
                    </div>

                    <div id="weightInputsContainer" class="weight-inputs-container">
                        <!-- Weight inputs will be generated here -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="submitShrimpWeightData()">Submit</button>
                <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Thông báo Toast -->
<div class="custom-toast-container">
    <div id="toastMessage" class="custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="custom-toast-header">
            <strong id="toastTitle">Notification</strong>
            <button type="button" class="custom-toast-close" onclick="closeToast()" aria-label="Close">&times;</button>
        </div>
        <div class="custom-toast-body" id="toastBody">
            This is a message.
        </div>
    </div>
</div>
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- QR JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- Thêm vào phần head -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            sessionStorage.clear(); // Xóa sessionStorage
            $('input, select').val(''); // Reset các trường dữ liệu
            location.reload(); // Tải lại trang
        }
    });

    window.addEventListener("beforeunload", function() {
        sessionStorage.clear(); // Xóa sessionStorage
        $('input, select').val(''); // Reset các trường dữ liệu
    });
    if (window.performance && window.performance.getEntriesByType("navigation")[0].type === "back_forward") {
        location.reload(); // Tải lại trang để xóa dữ liệu cũ
    }
    window.addEventListener("pagehide", function() {
        sessionStorage.clear(); // Xóa sessionStorage khi trang bị ẩn
        $('input, select').val('');
    });
    window.onpageshow = function(event) {
        if (event.persisted) {
            sessionStorage.clear(); // Xóa sessionStorage khi trang bị ẩn
            $('input, select').val('');
        }
    };
    document.addEventListener('wheel', function(event) {
        if (document.activeElement.type === 'number') {
            event.preventDefault();
        }
    }, {
        passive: false
    });

    function showToast(message, title = 'Notification', isSuccess = true) {
        const toastTitle = document.getElementById('toastTitle');
        const toastBody = document.getElementById('toastBody');
        const toastElement = document.getElementById('toastMessage');

        // Đặt tiêu đề và nội dung thông báo
        toastTitle.textContent = title;
        toastBody.textContent = message;

        // Thêm lớp cho kiểu thông báo
        toastElement.classList.remove('bg-success', 'bg-danger');
        toastElement.classList.add(isSuccess ? 'bg-success' : 'bg-danger');

        // Hiển thị toast
        toastElement.classList.add('show');

        // Tự động ẩn toast sau 3 giây
        setTimeout(() => {
            toastElement.classList.remove('show');
        }, 3000);
    }

    // Hàm đóng toast thủ công
    function closeToast() {
        document.getElementById('toastMessage').classList.remove('show');
    }


    // Chuyển đổi start_date từ PHP sang biến JavaScript
    const startDate = new Date(<?php echo $startDateJs; ?>);
    const lastDayOfPreChallenge = new Date(startDate);
    lastDayOfPreChallenge.setDate(lastDayOfPreChallenge.getDate() + (<?php echo $preChallengeDuration; ?> - 1));

    // Kiểm tra ngày đã chọn và đặt giá trị cho system_type
    function checkAndSetSystemType(selectedDate) {
        const systemTypeSelect = $('select[name="system_type"]');

        // Đảm bảo selectedDate là đối tượng Date hợp lệ
        const selectedDateTime = selectedDate instanceof Date ? selectedDate : new Date(selectedDate);

        // Reset time portions to compare dates only
        const selectedDateOnly = new Date(selectedDateTime.setHours(0, 0, 0, 0));
        const startDateOnly = new Date(startDate.setHours(0, 0, 0, 0));
        const lastDayOnly = new Date(lastDayOfPreChallenge.setHours(0, 0, 0, 0));

        // Kiểm tra tính hợp lệ của ngày
        if (isNaN(selectedDateTime.getTime())) {
            console.error('Invalid date provided to checkAndSetSystemType');
            return;
        }
        if (selectedDateOnly >= startDateOnly && selectedDateOnly <= lastDayOnly) {
            systemTypeSelect.html('<option value="RAS System (NC, PC & Treatments)">RAS System (NC, PC & Treatments)</option>');
            systemTypeSelect.val("RAS System (NC, PC & Treatments)");
            systemTypeSelect.prop('disabled', true);
        } else {
            systemTypeSelect.html(`
                <option value="" disabled selected>Select System Type</option>
                <option value="Static System (Negative Control)">Static System (Negative Control)</option>
                <option value="RAS System (Positive Control & Treatments)">RAS System (Positive Control & Treatments)</option>
            `);
            systemTypeSelect.prop('disabled', false);
        }
    }

    // Thêm event listener cho input ngày
    $('input[name="day"]').on('change', function() {
        const selectedDate = flatpickr.parseDate(this.value, "d-m-Y");
        if (selectedDate) {
            checkAndSetSystemType(selectedDate);
        }
    });
    // Mở form thêm Entry Data và điền case_study_id vào form
    function openAddDataModal(caseStudyId, groupName) {
        $('#modalCaseStudyId').val(caseStudyId);
        $('#modalGroupName').text(groupName);
        // Gỡ bỏ sự kiện "keydown" trước khi đăng ký mới
        $('#addDataForm').off('keydown');
        // Gỡ bỏ sự kiện "keydown" trước khi đăng ký mới
        $('#addShrimpDeathDataForm').off('keydown');
        // Kiểm tra và nạp lại dữ liệu từ session nếu tồn tại
        if (sessionStorage.getItem('treatment_name')) {
            $('select[name="treatment_name"]').val(sessionStorage.getItem('treatment_name')).change();
        }
        if (sessionStorage.getItem('lab_day')) {
            // Lấy giá trị từ sessionStorage (định dạng Y-M-d)
            const labDay = sessionStorage.getItem('lab_day');
            const parts = labDay.split('-'); // Tách thành mảng [Y, M, d]

            if (parts.length === 3) {
                // Chuyển đổi sang định dạng d-M-Y
                const formattedDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
                $('input[name="lab_day"]').val(formattedDate); // Hiển thị vào input
            } else {
                console.error('Invalid date format in sessionStorage:', labDay);
            }
        }

        // Đăng ký sự kiện "keydown" khi form đang hiển thị
        $('#addDataForm').on('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn không cho form submit mặc định
                submitEntryData(); // Gọi hàm submitEntryData để gửi dữ liệu
            }
        });
    }
    // Mở form thêm Water Quality và điền case_study_id vào form
    function openWaterQualityModal(caseStudyId, groupName) {
        $('#modalCaseStudyId3').val(caseStudyId); // Đăng ký sự kiện "keydown" khi form đang hiển thị
        $('#addWaterQualityForm').on('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn không cho form submit mặc định
                submitWaterQualityData(); // Gọi hàm submitWaterQualityData để gửi dữ liệu
            }
        });
    }
    // Đóng form và hủy sự kiện "keydown" để tránh xung đột
    $('.btn-close-modal').on('click', function() {
        $('#addDataForm').off('keydown'); // Xóa sự kiện cho Form 1
        $('#addShrimpDeathDataForm').off('keydown'); // Xóa sự kiện cho Form 2
        $('#addWaterQualityForm').off('keydown'); // Xóa sự kiện cho Form 3
        $('#addShrimpWeightForm').off('keydown'); // Xóa sự kiện cho Form 4
    });

    // Submit dữ liệu Entry Data và reset chỉ hai trường cụ thể
    function submitEntryData() {
        const caseStudyId = $('#modalCaseStudyId').val();
        const treatmentName = $('select[name="treatment_name"]').val();
        const productApplication = $('#productApplication').val();
        const survivalSample = $('input[name="survival_sample"]').val();
        const feedingWeight = $('input[name="feeding_weight"]').val();
        const rep = $('input[name="rep"]').val();
        const labDay = $('input[name="lab_day"]').val();

        // Kiểm tra các trường bắt buộc
        if (!treatmentName || !productApplication || !survivalSample || !feedingWeight || !rep || !labDay) {
            showToast("All fields are required.", "Error", false);
            return;
        }

        // Validate ngày và chuyển đổi định dạng nếu cần
        if (!validateDateInput(labDay)) {
            return; // Ngừng nếu ngày không hợp lệ
        }
        const formattedDate = labDay.split("-").reverse().join("-"); // Định dạng ngày dd-MM-yyyy thành yyyy-MM-dd

        // Chuẩn bị dữ liệu để gửi
        const formData = {
            case_study_id: caseStudyId,
            treatment_name: treatmentName,
            product_application: productApplication,
            survival_sample: survivalSample,
            feeding_weight: feedingWeight,
            rep: rep,
            lab_day: formattedDate,
        };

        // Gửi AJAX
        $.ajax({
            url: 'php_action/add_entry_data.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Data added successfully!', 'Success', true);

                    // Reset các trường cụ thể
                    $('input[name="survival_sample"]').val('');
                    $('input[name="feeding_weight"]').val('');
                    const currentRep = parseInt($('input[name="rep"]').val(), 10);
                    $('input[name="rep"]').val(currentRep + 1); // Tăng giá trị Rep

                    // Cập nhật recent entries
                    updateRecentEntries();
                } else {
                    showToast(response.messages, 'Error', false); // Hiển thị lỗi từ API
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                showToast("An unexpected error occurred.", "Error", false);
            }
        });
    }

    function updateRecentEntries() {
        const caseStudyId = $('#modalCaseStudyId').val(); // Lấy `case_study_id` từ modal

        $.ajax({
            url: 'php_action/get_recent_entries.php', // Endpoint để lấy recent entries
            type: 'POST',
            data: {
                case_study_id: caseStudyId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let tableHtml = `
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Treatment Name</th>
                                <th>Day</th>
                                <th>Rep</th>
                                <th>Survival Sample</th>
                                <th>Feed Intake(g)</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                    response.data.forEach(entry => {
                        const formattedDate = formatDate(entry.lab_day); // Định dạng ngày
                        tableHtml += `
                        <tr>
                            <td>${entry.treatment_name}</td>
                            <td>${formattedDate}</td>
                            <td>${entry.rep}</td>
                            <td>${entry.survival_sample}</td>
                            <td>${entry.feeding_weight}</td>
                        </tr>
                    `;
                    });

                    tableHtml += '</tbody></table>';
                    $('#recentEntriesContainer').html(tableHtml); // Cập nhật container recent entries
                } else {
                    $('#recentEntriesContainer').html('<p>No recent entries found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch recent entries:', error);
            }
        });
    }


    // Hàm định dạng ngày sang dd-mm-yyyy
    function formatDate(dateString) {
        const date = new Date(dateString);
        if (isNaN(date)) return dateString; // Nếu không phải ngày hợp lệ, trả về giá trị gốc
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }


    // Submit dữ liệu Water Quality bằng Ajax
    function submitWaterQualityData() {
        const caseStudyId = $('#modalCaseStudyId3').val();
        const day = $('input[name="day"]').val();
        const salinity = $('input[name="salinity"]').val();
        const temperature = $('input[name="temperature"]').val();
        const dissolvedOxygen = $('input[name="dissolved_oxygen"]').val();
        const pH = $('input[name="pH"]').val();
        const alkalinity = $('input[name="alkalinity"]').val();
        const tan = $('input[name="tan"]').val();
        const nitrite = $('input[name="nitrite"]').val();
        const systemType = $('select[name="system_type"]').val();

        // Kiểm tra chỉ các trường bắt buộc
        if (!day || !temperature || !dissolvedOxygen || !pH || !systemType) {
            showToast('Please fill in all required fields (Temperature, Dissolved Oxygen, pH, System Type and Day).', 'Error', false);
            return;
        }

        // Validate ngày và chuyển đổi định dạng nếu cần
        if (!validateDateInput(day)) {
            return; // Dừng nếu ngày không hợp lệ
        }
        const formattedDate = day.split("-").reverse().join("-"); // Định dạng ngày dd-MM-yyyy thành yyyy-MM-dd

        // Chuẩn bị dữ liệu để gửi
        const formData = {
            case_study_id: caseStudyId,
            day: formattedDate,
            salinity: salinity || null, // Gửi null nếu không có giá trị
            temperature: temperature,
            dissolved_oxygen: dissolvedOxygen,
            pH: pH,
            alkalinity: alkalinity || null, // Gửi null nếu không có giá trị
            tan: tan || null, // Gửi null nếu không có giá trị
            nitrite: nitrite || null, // Gửi null nếu không có giá trị
            system_type: systemType,
        };

        // Gửi AJAX
        $.ajax({
            url: 'php_action/add_water_quality.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Water Quality data added successfully!', 'Success', true);

                    // Reset form sau khi thành công
                    $('#addWaterQualityForm')[0].reset();
                    $('.btn-close-modal').click();
                } else {
                    showToast(response.messages, 'Error', false); // Hiển thị lỗi từ API
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                showToast("An unexpected error occurred.", "Error", false);
            }
        });
    }


    // Lưu ngày vào sessionStorage khi người dùng nhập
    $('input[name="lab_day"]').on('change', function() {
        const labDay = $('input[name="lab_day"]').val(); // Giá trị đầu vào (d-M-Y)
        const parts = labDay.split('-'); // Tách thành [d, M, Y]
        if (parts.length === 3) {
            const formattedDate = `${parts[0]}-${parts[1]}-${parts[2]}`; // Chuyển sang Y-M-d
            sessionStorage.setItem('lab_day', formattedDate); // Lưu Y-M-d
        }
    });
    // Tạo timeout để tự động xóa session sau 3 tiếng (10800000 ms)
    setTimeout(function() {
        sessionStorage.removeItem('treatment_name');
        sessionStorage.removeItem('product_application');
        sessionStorage.removeItem('lab_day');
    }, 10800000);
    document.addEventListener("DOMContentLoaded", function() {
        // Xóa sessionStorage của form 2 khi tải lại trang
        $('input, select').val('');
        sessionStorage.clear(); // Xóa mọi dữ liệu trong sessionStorage
        const treatmentDropdown = document.getElementById("treatmentName");
        const treatmentDropdown2 = document.getElementById("treatmentNameGroup2");
        // Lấy case_study_id từ URL
        const urlParams = new URLSearchParams(window.location.search);
        const caseStudyId = urlParams.get("case_study_id");

        if (!caseStudyId) {
            console.error("Missing case_study_id in URL.");
            return;
        }

        // Gọi API để lấy danh sách treatments
        fetch(`php_action/getTreatments.php?case_study_id=${encodeURIComponent(caseStudyId)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const treatments = data.data;

                    // Điền options vào dropdown của group 1
                    treatments.forEach(treatment => {
                        const option1 = document.createElement("option");
                        option1.value = treatment.name;
                        option1.textContent = treatment.name;
                        option1.setAttribute("data-application", treatment.product_application);
                        treatmentDropdown.appendChild(option1);

                        // Điền options vào dropdown của group 2
                        const option2 = document.createElement("option");
                        option2.value = treatment.name;
                        option2.textContent = treatment.name;
                        option2.setAttribute("data-application", treatment.product_application);
                        treatmentDropdown2.appendChild(option2);
                    });
                } else {
                    console.error("Error fetching treatments:", data.message);
                }
            })
            .catch(error => console.error("Error fetching treatments:", error));
    });


    function updateProductApplication() {
        const treatmentDropdown = document.getElementById("treatmentName");
        const selectedOption = treatmentDropdown.options[treatmentDropdown.selectedIndex];
        const productApplicationField = document.getElementById("productApplication");

        // Lấy giá trị từ thuộc tính data-application
        if (selectedOption && selectedOption.getAttribute("data-application")) {
            const productApplication = selectedOption.getAttribute("data-application");
            productApplicationField.value = productApplication;
            sessionStorage.setItem('product_application', productApplication); // Lưu vào session
        } else {
            productApplicationField.value = "";
            sessionStorage.removeItem('product_application');
        }
        // Gọi hàm resetRep để reset giá trị rep về 1
        resetRep(treatmentDropdown);
    }

    function openShrimpDeathModal(caseStudyId, groupName) {
        $('#modalCaseStudyId2').val(caseStudyId);
        updateRecentEntriesGroup2();

        const savedTreatmentName = sessionStorage.getItem('treatmentNameGroup2');
        const savedTestDate = sessionStorage.getItem('test_date_group2');
        const savedTestHour = sessionStorage.getItem('test_hour_group2');
        const savedProductApplication = sessionStorage.getItem('product_application_group2');

        if (savedTreatmentName) {
            $('select[name="treatmentNameGroup2"]').val(savedTreatmentName);
        }
        if (savedTestDate) {
            $('input[name="test_date"]').val(savedTestDate);
        }
        if (savedTestHour) {
            $('select[name="test_hour"]').val(savedTestHour);
        }
        if (savedProductApplication) {
            $('#productApplicationGroup2').val(savedProductApplication);
        }
        $('input[name="test_date"]').on('change', function() {
            sessionStorage.setItem('test_date_group2', $(this).val());
        });

        $('select[name="test_hour"]').on('change', function() {
            sessionStorage.setItem('test_hour_group2', $(this).val());
        });

        $('select[name="treatmentNameGroup2"]').on('change', function() {
            const selectedValue = $(this).val();
            sessionStorage.setItem('treatmentNameGroup2', selectedValue);
        });

        // Đăng ký sự kiện nhấn Enter
        $('#addShrimpDeathDataForm').on('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitShrimpDeathData();
            }
        });
    }
    // Xóa session sau 3 tiếng
    setTimeout(function() {
        sessionStorage.removeItem('treatmentNameGroup2');
        sessionStorage.removeItem('test_date_group2');
        sessionStorage.removeItem('test_hour_group2');
        sessionStorage.removeItem('product_application_group2');
    }, 10800000); // 3 tiếng
    function updateProductApplicationGroup2() {
        const treatmentDropdown2 = document.getElementById("treatmentNameGroup2");
        const selectedOption = treatmentDropdown2.options[treatmentDropdown2.selectedIndex]; // Sửa từ `treatmentDropdown` thành `treatmentDropdown2`
        const productApplicationField = document.getElementById("productApplicationGroup2");

        // Lấy giá trị từ thuộc tính data-application
        if (selectedOption && selectedOption.getAttribute("data-application")) {
            const productApplication = selectedOption.getAttribute("data-application");
            productApplicationField.value = productApplication;
            sessionStorage.setItem('product_application_group2', productApplication); // Lưu vào session
        } else {
            productApplicationField.value = "";
            sessionStorage.removeItem('product_application_group2');
        }
        // Gọi hàm resetRep để reset giá trị rep về 1
        resetRep(treatmentDropdown2);
    }

    function submitShrimpDeathData() {
        const caseStudyId = $('#modalCaseStudyId2').val();
        const treatmentName = $('select[name="treatmentNameGroup2"]').val();
        const productApplication = $('#productApplicationGroup2').val();
        const deathSample = $('input[name="death_sample"]').val();
        const rep = $('input[name="rep_2"]').val();

        if (!treatmentName || !productApplication || !deathSample || !rep) {
            showToast("All fields are required.", "Error", false);
            return;
        }

        // Xử lý ngày và giờ
        const testDate = $('input[name="test_date"]').val();
        if (!validateDateInput(testDate)) {
            return; // Dừng submit nếu ngày không hợp lệ
        }
        const testHour = $('select[name="test_hour"]').val();
        if (!testDate || !testHour) {
            showToast("Please select both date and time.", "Error", false);
            return;
        }

        const formattedDate = testDate.split("-").reverse().join("-"); // Định dạng ngày
        const testTime = `${formattedDate} ${testHour}:00:00`;

        const formData = {
            case_study_id: caseStudyId,
            treatment_name: treatmentName,
            product_application: productApplication,
            death_sample: deathSample,
            rep: rep,
            test_time: testTime,
        };

        // Gửi AJAX
        $.ajax({
            url: 'php_action/add_shrimp_death_data.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Shrimp Death Data added successfully!', 'Success', true);

                    // Reset các trường sau khi thành công
                    $('input[name="death_sample"]').val('');
                    const currentRep = parseInt($('input[name="rep_2"]').val(), 10);
                    $('input[name="rep_2"]').val(currentRep + 1);

                    updateRecentEntriesGroup2();
                } else {
                    showToast(response.messages, 'Error', false); // Hiển thị lỗi từ API
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                showToast("An unexpected error occurred.", "Error", false); // Lỗi không mong muốn
            }
        });
    }



    document.addEventListener("DOMContentLoaded", function() {
        const endDate = new Date(<?php echo $endDateJs; ?>); // end_date từ PHP
        const formattedEndDate = endDate.toLocaleDateString('en-GB').replace(/\//g, '-'); // Chuyển định dạng
        const formattedStartDate = startDate.toLocaleDateString('en-GB').replace(/\//g, '-'); // Chuyển định dạng

        // Khởi tạo Flatpickr
        flatpickr("#testTimePicker", {
            dateFormat: "d-m-Y", // Định dạng ngày
            defaultDate: new Date(), // Ngày mặc định
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    const selectedDate = new Date(selectedDates[0]);
                    selectedDate.setHours(0, 0, 0, 0); // Chuẩn hóa về date-only

                    const normalizedStartDate = new Date(startDate);
                    normalizedStartDate.setHours(0, 0, 0, 0);

                    const normalizedEndDate = new Date(endDate);
                    normalizedEndDate.setHours(0, 0, 0, 0);
                    // So sánh với `end_date`
                    if (selectedDate > normalizedEndDate) {
                        showToast(`The selected date exceeds the project's end date (${formattedEndDate}).`, 'Error', false);
                        instance.clear(); // Xóa giá trị nếu không hợp lệ
                    }
                    // Kiểm tra nếu selectedDate nhỏ hơn startDate
                    if (selectedDate < normalizedStartDate) {
                        showToast(`The selected date cannot be earlier than the project's start date (${formattedStartDate}).`, "Error", false);
                        instance.clear(); // Xóa giá trị nếu không hợp lệ
                    }
                }
            }
        });
    });

    // Cập nhật danh sách Recent Entries cho Group 2
    function updateRecentEntriesGroup2() {
        const caseStudyId = $('#modalCaseStudyId2').val();

        $.ajax({
            url: 'php_action/get_recent_entries_death.php',
            type: 'POST',
            data: {
                case_study_id: caseStudyId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let tableHtml = `
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Treatment Name</th>
                                <th>Test Time</th>
                                <th>Rep</th>
                                <th>Death Sample</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                    response.data.forEach(entry => {
                        // Định dạng lại test_time
                        const testTime = new Date(entry.test_time);
                        const options = {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        };
                        const formattedTestTime = testTime.toLocaleString('en-GB', options).replace(',', ''); // Định dạng theo d-M-Y H:i

                        tableHtml += `
                        <tr>
                            <td>${entry.treatment_name}</td>
                            <td>${formattedTestTime}</td>
                            <td>${entry.rep}</td>
                            <td>${entry.death_sample}</td>
                        </tr>
                    `;
                    });
                    tableHtml += '</tbody></table>';

                    $('#recentEntriesContainerGroup2').html(tableHtml);
                } else {
                    $('#recentEntriesContainerGroup2').html('<p>No recent entries found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch recent entries:', error);
            }
        });
    }

    function resetRep(selectElement) {
        const formGroup = selectElement.closest('.form-group').parentElement;
        const repInput = formGroup.querySelector('input[name="rep"], input[name="rep_2"]');

        if (repInput) {
            repInput.value = 1; // Reset rep về 1
        }
    }
    const endDate = new Date(<?php echo $endDateJs; ?>);
    if (isNaN(endDate)) {
        console.error("Invalid endDate: ", endDate);
    }

    // Chuyển endDate sang định dạng d-m-Y
    const formattedEndDate = new Date(endDate).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).replace(/\//g, '-'); // Chuyển dấu / thành dấu -
    const formattedStartDate = new Date(startDate).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).replace(/\//g, '-'); // Chuyển dấu / thành dấu -
    function validateDateInput(inputDate) {
        // Kiểm tra nếu inputDate là chuỗi rỗng hoặc không hợp lệ
        if (!inputDate || typeof inputDate !== "string") {
            showToast("Invalid date format.", "Error", false);
            return false;
        }

        // Tách ngày, tháng, năm từ định dạng dd-MM-YYYY
        const parts = inputDate.split("-");
        if (parts.length !== 3) {
            showToast("Invalid date format. Use dd-MM-YYYY.", "Error", false);
            return false;
        }

        const [day, month, year] = parts.map(Number);

        // Kiểm tra nếu ngày, tháng, năm không hợp lệ
        if (
            isNaN(day) || isNaN(month) || isNaN(year) ||
            day < 1 || day > 31 || month < 1 || month > 12 || year < 1900
        ) {
            showToast("Invalid date entered.", "Error", false);
            return false;
        }

        // Tạo đối tượng Date từ ngày nhập và chuẩn hóa về date-only
        const selectedDate = new Date(year, month - 1, day); // Tháng bắt đầu từ 0
        selectedDate.setHours(0, 0, 0, 0); // Loại bỏ giờ, phút, giây

        // Chuẩn hóa startDate và endDate về date-only
        const normalizedStartDate = new Date(startDate);
        normalizedStartDate.setHours(0, 0, 0, 0); // Loại bỏ giờ, phút, giây

        const normalizedEndDate = new Date(endDate);
        normalizedEndDate.setHours(0, 0, 0, 0); // Loại bỏ giờ, phút, giây

        // Kiểm tra nếu selectedDate không hợp lệ
        if (isNaN(selectedDate)) {
            showToast("Invalid date entered.", "Error", false);
            return false;
        }

        // So sánh với startDate
        if (selectedDate < normalizedStartDate) {
            showToast(`The selected date cannot be earlier than the project's start date (${formattedStartDate}).`, "Error", false);
            return false;
        }

        // So sánh với endDate
        if (selectedDate > normalizedEndDate) {
            showToast(`The selected date cannot be later than the project's end date (${formattedEndDate}).`, "Error", false);
            return false;
        }

        return true;
    }
    // Đặt code trong document.ready để đảm bảo DOM đã load xong
    document.addEventListener('DOMContentLoaded', function() {
        // Định nghĩa các hàm toàn cục
        window.showQRCode = function() {
            const currentUrl = window.location.href;
            const qrImage = document.getElementById('qrImage');

            qrImage.crossOrigin = "anonymous";
            const qrServerApi = 'https://api.qrserver.com/v1/create-qr-code/?data=' +
                encodeURIComponent(currentUrl) +
                '&size=256x256&format=png';

            qrImage.src = qrServerApi;

            // Hiển thị URL trong input
            document.getElementById('qrUrlDisplay').value = currentUrl;

            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
        };

        window.copyQRUrl = function() {
            // Lấy input element
            const urlInput = document.getElementById('qrUrlDisplay');

            // Select text trong input
            urlInput.select();
            urlInput.setSelectionRange(0, 99999); // Cho mobile

            // Copy
            document.execCommand('copy');

            // Bỏ select
            urlInput.blur();

            showToast('Page URL copied to clipboard!', 'Success', true);
        };

        window.saveQRImage = function() {
            const img = document.getElementById('qrImage');
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                const caseStudyId = '<?php echo $caseStudyId; ?>';

                link.href = url;
                link.download = `QR_CaseStudy_${caseStudyId}.png`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                showToast('QR Code saved successfully!', 'Success', true);
            }, 'image/png');
        };
    });

    // Thêm CSS để làm cho ảnh QR code có thể select và copy được
    document.head.insertAdjacentHTML('beforeend', `
        <style>
        #qrImage {
            user-select: all;
            -webkit-user-select: all;
        }
        </style>
    `);

    // Generate weight inputs based on shrimp count
    document.getElementById('shrimpCount').addEventListener('change', function() {
        const count = parseInt(this.value);
        const container = document.getElementById('weightInputsContainer');
        container.innerHTML = '';

        if (count > 100) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Maximum number of shrimps is 100'
            });
            this.value = 100;
            return;
        }

        for (let i = 1; i <= count; i++) {
            const inputGroup = document.createElement('div');
            inputGroup.className = 'input-group mb-2';
            inputGroup.innerHTML = `
                <span class="input-group-text">Shrimp #${i}</span>
                <input type="number" 
                       class="form-control weight-input" 
                       name="weights[]" 
                       step="0.01" 
                       min="0"
                       placeholder="Weight in grams"
                       data-index="${i}"
                       required>
            `;
            container.appendChild(inputGroup);
        }

        // Focus vào input đầu tiên
        const firstInput = container.querySelector('.weight-input');
        if (firstInput) {
            firstInput.focus();
        }

        // Thêm xử lý keydown cho form
        setupWeightInputsKeydown(count);
    });

    function setupWeightInputsKeydown(totalInputs) {
        const form = document.getElementById('addShrimpWeightForm');
        const inputs = form.querySelectorAll('.weight-input');
        
        // Remove existing keydown events first
        inputs.forEach(input => {
            input.removeEventListener('keydown', handleWeightInputKeydown);
        });

        // Add new keydown event listeners
        inputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                handleWeightInputKeydown(e, totalInputs);
            });
        });
    }

    function handleWeightInputKeydown(e, totalInputs) {
        if (e.key === 'Enter') {
            e.preventDefault();
            
            const currentIndex = parseInt(e.target.dataset.index);
            const allInputs = document.querySelectorAll('.weight-input');
            const currentInput = e.target;

            // Kiểm tra giá trị hợp lệ
            if (!currentInput.value || currentInput.value <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Please enter a valid weight'
                });
                return;
            }

            if (currentIndex < totalInputs) {
                // Focus vào input tiếp theo
                const nextInput = document.querySelector(`.weight-input[data-index="${currentIndex + 1}"]`);
                if (nextInput) {
                    nextInput.focus();
                }
            } else {
                // Kiểm tra xem tất cả input đã được điền chưa
                const allFilled = Array.from(allInputs).every(input => input.value && input.value > 0);
                
                if (allFilled) {
                    // Tự động submit form
                    submitShrimpWeightData();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Data',
                        text: 'Please fill in all weights before submitting'
                    });
                }
            }
        }
    }

    // Xử lý khi modal được mở
    $('#addDataModalGroup4').on('shown.bs.modal', function() {
        // Xóa các event listener cũ của các form khác
        $('#addDataForm').off('keydown');
        $('#addShrimpDeathDataForm').off('keydown');
        $('#addWaterQualityForm').off('keydown');
        
        // Focus vào input số lượng tôm
        $('#shrimpCount').focus();
    });

    // Xử lý khi modal đóng
    $('#addDataModalGroup4').on('hidden.bs.modal', function() {
        // Reset form
        $('#addShrimpWeightForm')[0].reset();
        // Xóa các input đã tạo
        $('#weightInputsContainer').empty();
        // Xóa session storage
        sessionStorage.removeItem('isSubmitting');
        // Xóa các event listener
        $('.weight-input').off('keydown');
    });

    // Submit form data
    function submitShrimpWeightData() {
        if (sessionStorage.getItem('isSubmitting')) {
            Swal.fire({
                icon: 'warning',
                title: 'Please wait',
                text: 'Your request is being processed'
            });
            return;
        }

        const form = document.getElementById('addShrimpWeightForm');
        if (!form.checkValidity()) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please fill in all required fields'
            });
            return;
        }

        sessionStorage.setItem('isSubmitting', 'true');

        // Lấy case_study_id và danh sách weights
        const caseStudyId = document.getElementById('modalCaseStudyId4').value;
        const weights = Array.from(document.querySelectorAll('.weight-input')).map(input => input.value);

        // Gửi request
        $.ajax({
            url: 'php_action/add_shrimp_weight.php',
            method: 'POST',
            data: {
                case_study_id: caseStudyId,
                weights: weights
            },
            success: function(response) {
                sessionStorage.removeItem('isSubmitting');
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.messages
                        }).then(() => {
                            $('#addDataModalGroup4').modal('hide');
                            form.reset();
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.messages || 'An error occurred'
                        });
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e, response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error processing server response'
                    });
                }
            },
            error: function(xhr, status, error) {
                sessionStorage.removeItem('isSubmitting');
                console.error('AJAX Error:', status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to submit data'
                });
            }
        });
    }

    function openShrimpWeightModal(caseStudyId, groupName) {
        // Set case study ID
        document.getElementById('modalCaseStudyId4').value = caseStudyId;
        
        // Reset form and container
        const form = document.getElementById('addShrimpWeightForm');
        form.reset();
        document.getElementById('weightInputsContainer').innerHTML = '';
    }

    // Thêm event listener cho modal khi đóng
    $('.btn-close-modal').on('click', function() {
        $('#addDataForm').off('keydown'); // Xóa sự kiện cho Form 1
        $('#addShrimpDeathDataForm').off('keydown'); // Xóa sự kiện cho Form 2
        $('#addWaterQualityForm').off('keydown'); // Xóa sự kiện cho Form 3
        $('#addShrimpWeightForm').off('keydown'); // Xóa sự kiện cho Form 4
    });
</script>
<style>
    /* Thêm styles cho QR code */
    .qr-container {
        padding: 15px;
    }

    .qr-image-container {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }

    .copy-instruction {
        font-size: 0.9rem;
        padding: 8px;
        margin: 0;
        background-color: #e8f4f8;
        border-color: #b8e7f3;
        color: #0c5460;
    }

    .copy-instruction i {
        margin-right: 5px;
    }

    .gap-2 {
        gap: 0.5rem !important;
    }

    .btn {
        white-space: nowrap;
    }

    /* Container cho toast */
    .custom-toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1100;
    }

    /* Toast container */
    .custom-toast {
        display: none;
        /* Ẩn mặc định */
        padding: 16px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        min-width: 250px;
        max-width: 300px;
        animation: fadeInOut 5s forwards;
        color: #fff;
    }

    .custom-toast.show {
        display: block;
    }

    /* Header của toast */
    .custom-toast-header {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        margin-bottom: 8px;
    }

    /* Nút đóng toast */
    .custom-toast-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
    }

    /* Nội dung của toast */
    .custom-toast-body {
        margin-top: 5px;
    }

    /* Hiệu ứng fadeIn và fadeOut */
    @keyframes fadeInOut {

        0%,
        90% {
            opacity: 1;
        }

        100% {
            opacity: 0;
        }
    }

    /* Màu sắc cho các loại thông báo */
    .custom-toast.bg-success {
        background-color: #28a745;
    }

    .custom-toast.bg-danger {
        background-color: #dc3545;
    }

    .btn-success {
        font-size: 16px;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 5px;
    }

    .btn-success i {
        margin-right: 5px;
    }

    .text-right {
        text-align: right;
    }

    .recent-entries table {
        font-size: 12px;
    }

    .recent-entries th,
    .recent-entries td {
        text-align: center;
        vertical-align: middle;
    }

    .recent-entries .btn-sm {
        font-size: 10px;
        padding: 2px 6px;
    }

    .recent-entries {
        font-size: 12px;
        line-height: 1.6;
        margin-top: 0px;
    }

    .recent-entries div {
        margin-bottom: 0px;
    }

    /* Responsive modal adjustments */
    /* Ensure proper alignment for the modal on all devices */
    /* Ensure the modal is vertically and horizontally centered */
    .modal-dialog-centered {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        /* Ensure proper alignment */
    }

    /* Prevent modal clipping on all screens */
    .modal-content {
        margin: auto;
        max-height: 90vh;
        /* Limit modal height to 90% of the viewport */
        overflow-y: auto;
        /* Add scroll if content exceeds height */
    }

    /* Adjust modal header spacing */
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    /* Ensure modal title is properly aligned and doesn't clip */
    .modal-header h4 {
        font-size: 1.25rem;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Responsive adjustments for smaller screens */
    @media (max-width: 576px) {
        .modal-dialog {
            max-width: 90%;
            /* Reduce width for smaller screens */
            margin: 10px auto;
            /* Add space around the modal */
        }

        .modal-header h4 {
            font-size: 1rem;
            /* Smaller title size */
        }

        .modal-body {
            padding: 1rem;
            /* Adjust padding for smaller screens */
        }

        .btn {
            font-size: 0.9rem;
            /* Adjust button font size */
        }
    }

    /* Ensure consistent positioning on large screens */
    @media (min-width: 577px) {
        .modal-dialog {
            max-width: 600px;
            /* Adjust modal width for desktop */
            margin: 1.75rem auto;
            /* Center the modal vertically */
        }
    }

    .custom-toast.show {
        display: block !important;
    }

    a:hover {
        text-decoration: underline !important;
    }

    a:hover button {
        text-decoration: none !important;
    }

    .table-custom th,
    .table-custom td {
        text-align: center;
        /* Căn giữa các cột */
        vertical-align: middle;
        /* Căn giữa nội dung theo chiều dọc */
    }

    .button-container {
        display: flex;
        justify-content: center;
        /* Căn giữa theo chiều ngang */
        align-items: center;
        /* Căn giữa theo chiều dọc */
        height: 100%;
        /* Chiều cao bao quanh nút */
    }

    a.btn {
        text-decoration: none !important;
        /* Loại bỏ gạch chân */
    }

    a.btn:hover {
        text-decoration: none !important;
        /* Đảm bảo không c gạch chân khi hover */
    }

    .btn {
        margin: 5px;
        transition: transform 0.2s;
    }

    .btn:hover {
        transform: scale(1.05);
    }

    .d-flex {
        display: flex;
    }

    .justify-content-end {
        justify-content: flex-end;
    }

    .align-items-center {
        align-items: center;
    }

    .btn-secondary {
        background-color: #81BFDA !important;
        /* Màu nền cho nút */
        color: white;
        /* Màu chữ */
        border: none;
        /* Bỏ viền */
    }

    .btn-secondary.text {
        align-items: center !important;

    }

    .btn-secondary:hover {
        background-color: #5a6268;
        /* Màu nền khi hover */
        transform: translateY(-2px);
        /* Hiệu ứng nâng lên khi hover */
    }

    /* Điều chỉnh khoảng cách cho container chính */
    .page-wrapper {
        padding-top: 10px;
        /* Giảm padding phía trên */
    }

    /* Điều chỉnh khoảng cách cho phần tiêu đề */
    .page-titles {
        margin-bottom: 5px;
        /* Giảm margin dưới của phần chứa nút */
        padding: 10px 20px;
    }

    /* Điều chỉnh khoảng cách cho bảng */
    .table-responsive {
        margin-top: 15px;
        /* Giảm margin trên của bảng */
    }

    /* Điều chỉnh khoảng cách cho container chứa nút */
    .button-container {
        margin-bottom: 0;
        /* Loại bỏ margin dưới của container chứa nút */
    }

    /* Điều chỉnh khoảng cách cho nút Show Image/Video */
    .button-container .btn-secondary {
        margin-top: 5px;
        /* Giảm khoảng cách giữa nút trên và nút dưới */
    }

    /* Điều chỉnh khoảng cách cho card */
    .card {
        margin-top: 10px;
        /* Giảm margin trên của card */
    }

    /* Đảm bảo card-body không có margin trên thừa */
    .card-body {
        padding-top: 10px;
    }

    /* Thiết kế hiện đại cho bảng */
    .table-custom {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .table-custom th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        padding: 15px;
        font-size: 1.1em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom td {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    /* Style cho nút Add Data */
    .btn-primary {
        background: linear-gradient(45deg, #4e73df, #6f42c1);
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    /* Icon trong nút Add Data */
    .btn-primary i {
        margin-right: 8px;
    }

    /* Style cho links trong Raw Data */
    .table-custom a {
        color: #3498db;
        text-decoration: none;
        transition: color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-custom a i {
        margin-right: 8px;
        font-size: 1.1em;
    }

    .table-custom a:hover {
        color: #2980b9;
    }

    /* Cập nhật nút Add Data */
    .button-container .btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .button-container .btn-primary::before {
        content: '\f067';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        margin-right: 8px;
    }

    .button-container {
        margin-bottom: 0;
    }

    .top-buttons {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    .top-buttons .btn {
        flex: 1 1 45%;
        min-width: 110px;
        padding: 8px 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        font-size: 15px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        text-transform: none;
        letter-spacing: normal;
    }

    .top-buttons .btn:nth-child(n+3) {
        flex: 1 1 30%;
    }

    /* Button Icons */
    .top-buttons .btn i {
        font-size: 15px;
        color: #fff;
    }

    /* Button Colors and Hover Effects */
    .btn-primary {
        background: #6f42c1;
        color: #fff !important;
    }

    .btn-chart {
        background: linear-gradient(45deg, #6f42c1, #8250df);
        color: #fff !important;
    }

    .btn-chart:hover {
        background: linear-gradient(45deg, #8250df, #6f42c1);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
    }

    .btn-results {
        background: linear-gradient(45deg, #2ecc71, #27ae60);
        color: #fff !important;
    }

    .btn-results:hover {
        background: linear-gradient(45deg, #27ae60, #2ecc71);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    }

    .btn-media {
        background: linear-gradient(45deg, #34495e, #2c3e50);
        color: #fff !important;
    }

    .btn-media:hover {
        background: linear-gradient(45deg, #2c3e50, #34495e);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(52, 73, 94, 0.3);
    }

    .btn-schedule {
        background: #20c997;
        color: #fff !important;
    }

    .btn-schedule:hover {
        background: #1ba97e;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(32, 201, 151, 0.3);
    }

    /* General Button Hover */
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    /* Button Icons */
    .btn i {
        font-size: 1.1em;
        color: #fff;
    }

    /* Page Title */
    .page-title {
        font-size: 24px;
        font-weight: bold;
        color: #5CB338;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .page-title i {
        margin-right: 10px;
        font-size: 1.5em;
        color: #6f42c1;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .top-buttons {
            padding: 0 10px;
            margin-top: 10px;
        }

        .top-buttons .btn {
            flex: 1 1 100%;
            margin-bottom: 5px;
            font-size: 14px;
            padding: 10px 15px;
            min-height: 45px;
        }

        .top-buttons .btn:nth-child(n+3) {
            flex: 1 1 100%;
        }

        .page-title {
            font-size: 20px;
            padding: 8px 12px;
            margin: 10px;
        }
    }

    /* Đảm bảo text màu trắng cho tất cả buttons */
    .btn-media,
    .btn-media *,
    .top-buttons .btn,
    .top-buttons .btn * {
        color: #fff !important;
    }

    #qrUrlDisplay {
        word-break: break-all;
        background-color: #f8f9fa;
    }

    /* Thêm hiệu ứng hover cho container ảnh */
    .qr-image-container:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Style cho alert trên mobile */
    @media (max-width: 576px) {
        .copy-instruction {
            font-size: 0.8rem;
            padding: 6px;
        }
    }

    .weight-inputs-container {
        max-height: 400px;
        overflow-y: auto;
        padding: 15px;
        margin-top: 20px;
    }

    .input-group {
        margin-bottom: 10px;
    }

    .input-group-text {
        min-width: 100px;
        background-color: #f8f9fa;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    /* Custom scrollbar */
    .weight-inputs-container::-webkit-scrollbar {
        width: 8px;
    }

    .weight-inputs-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .weight-inputs-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .weight-inputs-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
<!-- Thêm Font Awesome cho icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Thêm Modal QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code for Current Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qrContainer" class="qr-container text-center">
                    <!-- Thêm hướng dẫn copy ảnh -->
                    <div class="copy-instruction alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i>
                        To copy image: Right-click on QR code → Select "Copy image"
                    </div>

                    <div class="qr-image-container mb-3">
                        <img id="qrImage" alt="QR Code" style="max-width: 256px;">
                    </div>

                    <div class="input-group mb-3">
                        <input type="text" id="qrUrlDisplay" class="form-control" readonly>
                        <button class="btn btn-info" onclick="copyQRUrl()">
                            <i class="fas fa-copy"></i> Copy URL
                        </button>
                    </div>

                    <div class="qr-actions d-flex justify-content-center gap-2">
                        <button class="btn btn-primary" onclick="saveQRImage()">
                            <i class="fas fa-save"></i> Save Image
                        </button>
                        <button class="btn btn-secondary" onclick="window.open(document.getElementById('qrImage').src)">
                            <i class="fas fa-external-link-alt"></i> Open Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>