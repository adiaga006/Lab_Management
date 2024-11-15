<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/layout/footer.php');
include('./constant/connect.php');
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;
// Lấy `start_date` từ bảng `case_study` dựa trên `case_study_id`
$sql = "SELECT start_date FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $caseStudyId);
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
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Manage Groups for Case Study: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4>Groups under Category: <?php echo htmlspecialchars($categoryId); ?></h4>

                <?php if ($groupResult->num_rows > 0): ?>
                    <div class="table-responsive m-t-40">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Group Name</th>
                                    <th>Actions</th>
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
                                                <button class="btn btn-primary" data-toggle="modal"
                                                    data-target="#addDataModalGroup1"
                                                    onclick="openAddDataModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                    Add Data
                                                </button>
                                            <?php elseif ($group['group_id'] == 3): ?>
                                                <button class="btn btn-primary" data-toggle="modal"
                                                    data-target="#addDataModalGroup3"
                                                    onclick="openWaterQualityModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                    Add Data
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary"
                                                    onclick="alert('Add Data feature is not available for this group.');">
                                                    Add Data
                                                </button>
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
    <div class="modal-dialog">
        <form id="addDataForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Entry Data for Group: <span id="modalGroupName"></span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId">

                    <div class="form-group">
                        <label>Treatment Name</label>
                        <select name="treatment_name" class="form-control" required
                            onchange="updateProductApplication()">
                            <option value="">Select Treatment</option>
                            <option value="Negative control">Negative control</option>
                            <option value="Positive control">Positive control</option>
                            <option value="Treatment 1">Treatment 1 (1,000 ppm_Prototype 13A)</option>
                            <option value="Treatment 2">Treatment 2 (2,000 ppm_Prototype 13A)</option>
                            <option value="Treatment 3">Treatment 3 (1,000 ppm_AviPlus Aqua)</option>
                            <option value="Treatment 4">Treatment 4 (2,000 ppm_AviPlus Aqua)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Application</label>
                        <input type="text" name="product_application" class="form-control" id="productApplication"
                            readonly required>
                    </div>
                    <div class="form-group">
                        <label>Day (DD/MM/YYYY)</label>
                        <input type="text" name="lab_day" class="form-control datepicker" required>
                    </div>
                    <div class="form-group">
                        <label>Survival Sample</label>
                        <input type="number" name="survival_sample" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Feeding Weight</label>
                        <input type="number" step="0.01" name="feeding_weight" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="submitEntryData()">Add</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Data Modal for Group 3 (Water Quality) -->
<div id="addDataModalGroup3" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <form id="addWaterQualityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Water Quality Data for Group: <span id="modalGroupName3"></span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId3">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Day</label>
                            <input type="text" name="day" class="form-control datepicker" required>
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
                                <option value="">Select System Type</option>
                                <option value="RAS System (NC, PC & Treatments)">RAS System (NC, PC & Treatments)
                                </option>
                                <option value="Static System (Negative Control)">Static System (Negative Control)
                                </option>
                                <option value="RAS System (Positive Control, T1, T2, T3 & T4)">RAS System (Positive
                                    Control, T1, T2, T3 & T4)</option>
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
<script>
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
    const twentyThirdDayAfterStart = new Date(startDate);
    twentyThirdDayAfterStart.setDate(twentyThirdDayAfterStart.getDate() + 22);

    // Khởi tạo Datepicker cho trường ngày
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    }).on('changeDate', function (e) {
        checkAndSetSystemType(e.date);
    });

    // Kiểm tra ngày đã chọn và đặt giá trị cho system_type
    function checkAndSetSystemType(selectedDate) {
        const systemTypeSelect = $('select[name="system_type"]');

        if (selectedDate >= startDate && selectedDate <= twentyThirdDayAfterStart) {
            // Ngày trong khoảng 23 ngày sau startDate
            systemTypeSelect.html('<option value="RAS System (NC, PC & Treatments)">RAS System (NC, PC & Treatments)</option>');
            systemTypeSelect.val("RAS System (NC, PC & Treatments)").prop('readonly', true); // Làm readonly
        } else {
            // Ngày sau 23 ngày, cho phép người dùng chọn từ 2 lựa chọn và mở khóa readonly
            systemTypeSelect.html(`
                <option value="Static System (Negative Control)">Static System (Negative Control)</option>
                <option value="RAS System (Positive Control, T1, T2, T3 & T4)">RAS System (Positive Control, T1, T2, T3 & T4)</option>
            `);
            systemTypeSelect.prop('disabled', false).val(""); // Mở khóa và đặt giá trị trống để người dùng chọn
        }
    }
    // Mở form thêm Entry Data và điền case_study_id vào form
    function openAddDataModal(caseStudyId, groupName) {
        $('#modalCaseStudyId').val(caseStudyId);
        $('#modalGroupName').text(groupName);

        // Kiểm tra và nạp lại dữ liệu từ session nếu tồn tại
        if (sessionStorage.getItem('treatment_name')) {
            $('select[name="treatment_name"]').val(sessionStorage.getItem('treatment_name')).change();
        }
        if (sessionStorage.getItem('lab_day')) {
            $('input[name="lab_day"]').val(sessionStorage.getItem('lab_day'));
        }
        // Đăng ký sự kiện "keydown" khi form đang hiển thị
        $('#addDataForm').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn không cho form submit mặc định
                submitEntryData(); // Gọi hàm submitEntryData để gửi dữ liệu
            }
        });
    }
    // Mở form thêm Water Quality và điền case_study_id vào form
    function openWaterQualityModal(caseStudyId, groupName) {
        $('#modalCaseStudyId3').val(caseStudyId);
        $('#modalGroupName3').text(groupName);
        // Đăng ký sự kiện "keydown" khi form đang hiển thị
        $('#addWaterQualityForm').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn không cho form submit mặc định
                submitWaterQualityData(); // Gọi hàm submitWaterQualityData để gửi dữ liệu
            }
        });
    }
    // Đóng form và hủy sự kiện "keydown" để tránh xung đột
    $('.btn-close-modal').on('click', function () {
        $('#addDataForm').off('keydown');
    });
    // Submit dữ liệu Entry Data và reset chỉ hai trường cụ thể
    function submitEntryData() {
        const labDay = $('input[name="lab_day"]').val().split('/').reverse().join('-');
        $('input[name="lab_day"]').val(labDay);

        const formData = $('#addDataForm').serialize();

        $.ajax({
            url: 'php_action/add_entry_data.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Entry data added successfully!', 'Success', true);

                    // Reset chỉ `Survival Sample` và `Feeding Weight`
                    $('input[name="survival_sample"]').val('');
                    $('input[name="feeding_weight"]').val('');
                } else {
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
    // Submit dữ liệu Water Quality bằng Ajax
    function submitWaterQualityData() {
        const dayFormatted = $('input[name="day"]').val().split('/').reverse().join('-');
        $('input[name="day"]').val(dayFormatted);

        const formData = $('#addWaterQualityForm').serialize();

        $.ajax({
            url: 'php_action/add_water_quality.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Entry data added successfully!', 'Success', true);
                    $('#addWaterQualityForm')[0].reset();
                    $('.btn-close-modal').click();
                } else {
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
    // Cập nhật Product Application tự động và lưu vào sessionStorage
    function updateProductApplication() {
        const treatment = $('select[name="treatment_name"]').val();
        let productApplication = "";

        // Kiểm tra các trường hợp để xác định product application
        switch (treatment) {
            case "Negative control":
            case "Positive control":
                productApplication = "0";
                break;
            case "Treatment 1":
                productApplication = "1,000 ppm (Prototype 13A)";
                break;
            case "Treatment 2":
                productApplication = "2,000 ppm (Prototype 13A)";
                break;
            case "Treatment 3":
                productApplication = "1,000 ppm (AviPlus Aqua)";
                break;
            case "Treatment 4":
                productApplication = "2,000 ppm (AviPlus Aqua)";
                break;
            default:
                productApplication = "";
        }

        // Gán giá trị vào trường product_application và lưu vào sessionStorage
        $('#productApplication').val(productApplication);
        sessionStorage.setItem('treatment_name', treatment);
        sessionStorage.setItem('product_application', productApplication);
    }

    // Lưu ngày vào sessionStorage khi người dùng nhập
    $('input[name="lab_day"]').on('change', function () {
        sessionStorage.setItem('lab_day', $(this).val());
    });
    // Tạo timeout để tự động xóa session sau 3 tiếng (10800000 ms)
    setTimeout(function () {
        sessionStorage.removeItem('treatment_name');
        sessionStorage.removeItem('product_application');
        sessionStorage.removeItem('lab_day');
    }, 10800000);
</script>
<style>
/* Container cho toast */
.custom-toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1100;
}
/* Toast container */
.custom-toast {
    display: none; /* Ẩn mặc định */
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
    0%, 90% { opacity: 1; }
    100% { opacity: 0; }
}

/* Màu sắc cho các loại thông báo */
.custom-toast.bg-success {
    background-color: #28a745;
}
.custom-toast.bg-danger {
    background-color: #dc3545;
}
</style>