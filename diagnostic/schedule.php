<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');

$caseStudyId = $_GET['case_study_id'];

// Lấy dữ liệu treatment từ bảng case_study
$sql = "SELECT treatment FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$treatmentData = $result->fetch_assoc();
$treatments = json_decode($treatmentData['treatment'], true);
$stmt->close();
// Lấy tất cả work_name từ cơ sở dữ liệu
$sql = "SELECT id, work_name FROM work_criteria ORDER BY work_name";
$result = $connect->query($sql);
$workNames = [];
while ($row = $result->fetch_assoc()) {
    $workNames[] = $row;
}
// Lấy dữ liệu schedule
$sql = "SELECT date_check, work_done, check_status FROM schedule WHERE case_study_id = ? ORDER BY date_check";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$scheduleData = [];

while ($row = $result->fetch_assoc()) {
    // Chuyển đổi định dạng ngày từ YYYY-MM-DD sang DD-MM-YYYY cho khớp với format datepicker
    $date = date('d-m-Y', strtotime($row['date_check']));
    $scheduleData[$date] = [
        'work_done' => $row['work_done'],
        'check_status' => $row['check_status']
    ];
}
$stmt->close();
?>

<style>
    .btn {
        align-items: center;
        /* Căn giữa icon và chữ */
    }

    .btn i {
        margin-right: 5px;
        /* Khoảng cách giữa icon và chữ */
    }

    /* Thay đổi màu nền cho ô Work Done */
    #workDoneSelect {
        font-weight: bold;
        /* Đậm chữ */
        background-color: #f0f0f0;
        /* Màu nền nhạt cho ô Work Done */
        color: black;
        /* Màu chữ đen */
    }

    /* Màu nền cho các lựa chọn trong combo box */
    .form-control {
        background-color: white;
        /* Màu nền trắng cho combo box */
        color: black;
        /* Màu chữ đen */
    }

    /* Màu nền cho các lựa chọn khi hover */
    .form-control option {
        background-color: white;
        /* Màu nền trắng cho các lựa chọn */
        color: black;
        /* Màu chữ đen */
    }

    /* Màu nền cho các lựa chọn đã chọn */
    .form-control option[data-color="g"]:hover {
        background-color: #d4edda;
        /* Màu nền nhạt cho Acclimation */
    }

    .form-control option[data-color="b"]:hover {
        background-color: #cce5ff;
        /* Màu nền nhạt cho Feeding & Observation */
    }

    .form-control option[data-color="o"]:hover {
        background-color: #ffeeba;
        /* Màu nền nhạt cho Post -challenge observation */
    }

    .form-control option[data-color="r"]:hover {
        background-color: #f8d7da;
        /* Màu nền nhạt cho Immersion ? challenge */
    }

    .selected-criteria {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 45px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .criteria-tag {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        color: #000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .criteria-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }

    /* 6 màu cho các task */
    .criteria-tag:nth-of-type(6n + 1) { background-color: #FFB7B2; }
    .criteria-tag:nth-of-type(6n + 2) { background-color: #BAFFC9; }
    .criteria-tag:nth-of-type(6n + 3) { background-color: #BAE1FF; }
    .criteria-tag:nth-of-type(6n + 4) { background-color: #FFFFBA; }
    .criteria-tag:nth-of-type(6n + 5) { background-color: #E2BAE1; }
    .criteria-tag:nth-of-type(6n) { background-color: #B2FFFF; }

    /* Style cho nút remove */
    .remove-criteria {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(0,0,0,0.1);
        color: #666;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .remove-criteria:hover {
        background: rgba(220,53,69,0.2);
        color: #dc3545;
    }

    /* Animation khi thêm/xóa task */
    .criteria-tag {
        animation: fadeInScale 0.3s ease;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Toast styles */
    .custom-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        color: white;
        z-index: 9999;
        animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }

    .table th {
        font-weight: bold;
        font-size: 1.1em;
        color: black;
    }

    .table td {
        font-size: 1em;
        font-weight: bold;
        color: black;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }

    .table-bordered tbody tr td {
        border: 2px solid #333;
        /* Đường viền đậm hơn */
    }

    .table-bordered th {
        border: 2px solid #333;
        /* Đường viền đậm hơn */
    }

    .task-done {
        text-align: left !important;
    }

    .task-done th {
        text-align: center;
    }

    .selected-criteria .criteria-tag {
        margin-bottom: 5px;
        display: inline-block;
    }

    .selected-criteria {
        min-height: 40px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .action-column {
        text-align: center !important;
        /* Căn giữa cho cột Action */
    }

    /* Căn giữa cho tiêu đề cột Task Done */
    .table th.task-done {
        text-align: center !important;
        /* Căn giữa tiêu đề */
    }

    .criteria-tag {
        display: inline-block;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 15px;
        color: #000;
        font-size: 0.9em;
    }

    .criteria-tag i {
        margin-left: 5px;
        cursor: pointer;
    }

    #deleteConfirmModal .modal-content {
        border-radius: 10px;
    }

    #deleteConfirmModal .modal-header {
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    #deleteConfirmModal .modal-body {
        padding: 20px;
        text-align: center;
        font-size: 1.1em;
    }

    #changeViewBtn {
        background-color: #007bff;
        /* Màu nền */
        color: white;
        /* Màu chữ */
        border: none;
        /* Không có viền */
        padding: 10px 20px;
        /* Padding */
        border-radius: 5px;
        /* Bo góc */
        cursor: pointer;
        /* Con trỏ chuột */
        transition: background-color 0.3s;
        /* Hiệu ứng chuyển màu */
    }

    #changeViewBtn:hover {
        background-color: #0056b3;
        /* Màu nền khi hover */
    }
</style>


<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Schedule for Case Study: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between mb-3">
                    <!-- Button to trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">
                        <i class="fa fa-check-circle"></i> Confirm completed tasks
                    </button>
                    <button id="changeViewBtn" class="btn btn-primary">
                        <i class="fa fa-eye"></i> Change View
                    </button>
                </div>
                <!-- Schedule Modal -->
                <div class="modal" id="scheduleModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" style="color:black">Add Schedule Data</h5>
                            </div>
                            <div class="modal-body">
                                <form id="scheduleForm">
                                    <input type="hidden" name="case_study_id"
                                        value="<?php echo htmlspecialchars($caseStudyId); ?>">

                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="text" placeholder="Select Date" class="form-control" id="dateCheck"
                                            name="date_check" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Diets</label>
                                        <select class="form-control" name="diets" required>
                                            <option value="">Select Diet</option>
                                            <?php foreach ($treatments as $treatment): ?>
                                                <option value="<?php echo htmlspecialchars($treatment['name']); ?>">
                                                    <?php echo htmlspecialchars($treatment['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Work Done</label>
                                        <select class="form-control" name="work_done" id="workDoneSelect" required>
                                            <option value="">Select Work Done</option>
                                            <option value="Acclimation" data-color="g">Acclimation</option>
                                            <option value="Feeding & Observation" data-color="b">Feeding & Observation
                                            </option>
                                            <option value="Post -challenge observation" data-color="o">Post -challenge
                                                observation</option>
                                            <option value="Immersion ? challenge" data-color="r">Immersion ? challenge
                                            </option>
                                        </select>
                                        <div id="additionalOptions" style="display: none;">
                                            <label>Choose challenge:</label>
                                            <select class="form-control" name="immersion_option"
                                                id="immersionOptionSelect">
                                                <option value="">Select challenge</option>
                                                <option value="EMS">EMS</option>
                                                <option value="TSB+">TSB+</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Thay thế phần check_status trong form -->
                                    <div class="form-group">
                                        <label>Task Done</label>
                                        <select class="form-control" id="checkStatusSelect">
                                            <option value="">Select Task Done</option>
                                            <?php
                                            $sql = "SELECT id, work_name FROM work_criteria ORDER BY work_name";
                                            $result = $connect->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['work_name']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="selected-criteria mb-2"></div>
                                        <input type="hidden" name="check_status" id="checkStatusInput">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal" id="editScheduleModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" style="color:black">Edit Schedule Data</h5>
                            </div>
                            <div class="modal-body">
                                <form id="editScheduleForm">
                                    <input type="hidden" name="id" id="editScheduleId">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="text" class="form-control" id="editDateCheck" name="date_check"
                                            readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Diets</label>
                                        <input type="text" class="form-control" id="editDiets" name="diets" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Work Done</label>
                                        <select class="form-control" name="work_done" id="editWorkDoneSelect" required>
                                            <option value="">Select Work Done</option>
                                            <option value="Acclimation" data-color="g">Acclimation</option>
                                            <option value="Feeding & Observation" data-color="b">Feeding & Observation
                                            </option>
                                            <option value="Post -challenge observation" data-color="o">Post -challenge
                                                observation</option>
                                            <option value="Immersion ? challenge" data-color="r">Immersion ? challenge
                                            </option>
                                        </select>
                                        <div id="editAdditionalOptions" style="display: none;">
                                            <label>Choose challenge:</label>
                                            <select class="form-control" name="immersion_option"
                                                id="editImmersionOptionSelect">
                                                <option value="">Select challenge</option>
                                                <option value="EMS">EMS</option>
                                                <option value="TSB+">TSB+</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Task Done</label>
                                        <select class="form-control" id="editCheckStatusSelect">
                                            <option value="">Select Task Done</option>
                                            <?php foreach ($workNames as $work): ?>
                                                <option value="<?php echo $work['id']; ?>">
                                                    <?php echo htmlspecialchars($work['work_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="selected-criteria mb-2" id="editSelectedCriteria"></div>
                                        <input type="hidden" name="check_status" id="editCheckStatusInput">
                                    </div>

                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal" id="deleteConfirmModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Confirm Delete</h5>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this schedule data?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-close-modal"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr></tr>
                                    </tr>
                                    <th>Date</th>
                                    <th>Diets</th>
                                    <th>Work Done</th>
                                    <th class="task-done">Task Done</th>
                                    <th class="action-column">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleTableBody">
                                    <?php
                                    $sql = "SELECT * FROM schedule WHERE case_study_id = ? ORDER BY date_check ASC";
                                    $stmt = $connect->prepare($sql);
                                    $stmt->bind_param("s", $caseStudyId);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    $groupedData = [];
                                    while ($row = $result->fetch_assoc()) {
                                        $dateCheck = date('d-m-Y', strtotime($row['date_check']));
                                        $groupedData[$dateCheck][] = $row;
                                    }

                                    foreach ($groupedData as $date => $rows) {
                                        echo "<tr><td rowspan='" . count($rows) . "'>" . $date . "</td>";
                                        echo "<td>" . htmlspecialchars($rows[0]['diets']) . "</td>";
                                        echo "<td>" . htmlspecialchars($rows[0]['work_done']) . "</td>";

                                        // Xử lý hiển thị check_status với work_name
                                        $checkStatusIds = json_decode($rows[0]['check_status'], true);
                                        $criteriaNames = [];
                                        if ($checkStatusIds) {
                                            $idList = implode(',', array_map('intval', $checkStatusIds));
                                            $sqlCriteria = "SELECT work_name FROM work_criteria WHERE id IN ($idList)";
                                            $criteriasResult = $connect->query($sqlCriteria);
                                            while ($criteria = $criteriasResult->fetch_assoc()) {
                                                $criteriaNames[] = "- " . htmlspecialchars($criteria['work_name']);
                                            }
                                        }
                                        echo "<td class='task-done'>" . nl2br(implode("\n", $criteriaNames)) . "</td>";
                                        echo "<td class='action-column'>
                                                <a class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editScheduleModal' onclick='editSchedule(" . $rows[0]['id'] . ")'>
                                                    <i class='fa fa-edit'></i> Edit
                                                </a>
                                                <a  class='btn btn-danger btn-sm' onclick='confirmDelete(" . $rows[0]['id'] . ")'>
                                                    <i class='fa fa-trash'></i> Delete
                                                </a>
                                              </td>";
                                        echo "</tr>";

                                        // Hiển thị các dòng còn lại
                                        for ($i = 1; $i < count($rows); $i++) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($rows[$i]['diets']) . "</td>";
                                            echo "<td>" . htmlspecialchars($rows[$i]['work_done']) . "</td>";

                                            // Xử lý hiển thị check_status với work_name
                                            $checkStatusIds = json_decode($rows[$i]['check_status'], true);
                                            $criteriaNames = [];
                                            if ($checkStatusIds) {
                                                $idList = implode(',', array_map('intval', $checkStatusIds));
                                                $sqlCriteria = "SELECT work_name FROM work_criteria WHERE id IN ($idList)";
                                                $criteriasResult = $connect->query($sqlCriteria);
                                                while ($criteria = $criteriasResult->fetch_assoc()) {
                                                    $criteriaNames[] = "- " . htmlspecialchars($criteria['work_name']);
                                                }
                                            }
                                            echo "<td class='task-done'>" . nl2br(implode("\n", $criteriaNames)) . "</td>";
                                            echo "<td class='action-column'>
                                                    <a  class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editScheduleModal' onclick='editSchedule(" . $rows[$i]['id'] . ")'>
                                                        <i class='fa fa-edit'></i> Edit
                                                    </a>
                                                    <a class='btn btn-danger btn-sm' onclick='confirmDelete(" . $rows[$i]['id'] . ")'>
                                                        <i class='fa fa-trash'></i> Delete
                                                    </a>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function () {
        $('#changeViewBtn').on('click', function () {
            window.location.href = 'change_view_schedule.php?case_study_id=<?php echo $caseStudyId; ?>';
        });
    });
    // Initialize Flatpickr
    flatpickr("#dateCheck", {
        dateFormat: "d-m-Y",
        altInput: true,
        altFormat: "d-m-Y"
    });

    // Handle Work Done selection
    $('#workDoneSelect').on('change', function () {
        const selectedValue = $(this).val();
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.data('color');

        // Reset background color
        $(this).css('background-color', '');

        if (selectedValue) {
            // Change background color based on selection
            $(this).css('background-color', color === 'g' ? '#d4edda' :
                color === 'b' ? '#cce5ff' :
                    color === 'o' ? '#ffeeba' :
                        color === 'r' ? '#f8d7da' : '');

            $(this).css('color', 'black'); // Đậm màu chữ

            // Show additional options if "Immersion ? challenge" is selected
            if (selectedValue === "Immersion ? challenge") {
                $('#additionalOptions').show();
            } else {
                $('#additionalOptions').hide();
            }
        } else {
            $('#additionalOptions').hide();
        }
    });

    // Handle additional option selection
    $('#immersionOptionSelect').on('change', function () {
        const selectedImmersionOption = $(this).val();
        const workDoneSelect = $('#workDoneSelect');

        if (selectedImmersionOption) {
            // Cập nhật giá trị ô Work Done với lựa chọn đã chọn
            const currentWorkDone = workDoneSelect.val();
            const newWorkDone = currentWorkDone.includes("Immersion")
                ? `Immersion ${selectedImmersionOption} challenge`
                : `Immersion ${selectedImmersionOption} challenge`;

            // Tạo option mới và thêm vào select
            const newOption = new Option(newWorkDone, newWorkDone);
            $(newOption).attr('data-color', 'r');

            // Thêm option mới và chọn nó
            workDoneSelect.append(newOption);
            workDoneSelect.val(newWorkDone);
            workDoneSelect.trigger('change'); // Gọi lại sự kiện change để cập nhật màu
            $('#additionalOptions').hide(); // Ẩn combo box bổ sung
        }
    });

    // Thêm hàm hiển thị toast
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type === 'success' ? 'bg-success' : 'bg-danger'}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Cập nhật xử lý form submission
    $('#scheduleForm').on('submit', function (e) {
        e.preventDefault();

        const dateCheck = $('#dateCheck').val();
        const [day, month, year] = dateCheck.split('-');
        const formattedDate = `${year}-${month}-${day}`;

        const formData = new FormData(this);
        formData.set('date_check', formattedDate);

        $.ajax({
            url: 'php_action/add_schedule.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showToast('Schedule added successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.messages, 'error');
                }
            },
            error: function () {
                showToast('Error occurred while adding schedule', 'error');
            }
        });
    });

    // Khởi tạo mảng để lưu các criteria đã chọn
    let selectedCriteria = [];

    // Xử lý khi chọn criteria
    $('#checkStatusSelect').on('change', function () {
        const selectedId = $(this).val();
        if (!selectedId) return;

        const selectedText = $(this).find('option:selected').text();

        // Thêm vào mảng selectedCriteria
        if (!selectedCriteria.find(item => item.id === selectedId)) {
            selectedCriteria.push({
                id: selectedId,
                name: selectedText
            });
        }

        // Cập nhật hiển thị
        updateCriteriaDisplay();

        // Ẩn option đã chọn
        $(this).find(`option[value="${selectedId}"]`).hide();

        // Reset select về giá trị mặc định
        $(this).val('');

        // Cập nhật input hidden
        updateCheckStatusInput();
    });

    // Hàm cập nhật hiển thị các criteria đã chọn
    function updateCriteriaDisplay() {
        const container = $('.selected-criteria');
        container.empty();

        selectedCriteria.forEach((criteria, index) => {
            const criteriaElement = $('<div>', {
                class: 'criteria-tag',
                'data-id': criteria.id,
                html: criteria.name
            }).css({
                'animation': 'fadeInScale 0.3s ease'
            });

            // Chỉ thêm nút xóa nếu không bị khóa
            if (!isTaskDoneLocked) {
                const removeButton = $('<span>', {
                    class: 'remove-criteria',
                    html: '×',
                    click: function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const tag = $(this).closest('.criteria-tag');
                        const idToRemove = tag.data('id');
                        
                        // Animation khi xóa
                        tag.css({
                            'transform': 'scale(0.8)',
                            'opacity': '0'
                        });
                        
                        setTimeout(() => {
                            // Xóa tag
                            tag.remove();
                            
                            // Hiện lại option trong select
                            $(`#checkStatusSelect option[value="${idToRemove}"]`).show();
                            
                            // Cập nhật mảng selectedCriteria
                            selectedCriteria = selectedCriteria.filter(item => item.id !== idToRemove);
                            
                            // Cập nhật input hidden
                            updateCheckStatusInput();
                        }, 200);
                    }
                });
                criteriaElement.append(removeButton);
            }

            container.append(criteriaElement);
        });
    }

    // Sửa lại hàm xử lý xóa criteria
    $(document).on('click', '.remove-criteria', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        const tag = $(this).closest('.criteria-tag');
        const idToRemove = tag.data('id');
        
        // Animation khi xóa
        tag.css('transform', 'scale(0.8)');
        tag.css('opacity', '0');
        
        setTimeout(() => {
            // Xóa tag
            tag.remove();
            
            // Hiện lại option trong select
            $(`#checkStatusSelect option[value="${idToRemove}"]`).show();
            
            // Cập nhật mảng selectedCriteria
            selectedCriteria = selectedCriteria.filter(item => item.id !== idToRemove);
            
            // Cập nhật input hidden
            $('#checkStatusInput').val(JSON.stringify(selectedCriteria.map(item => item.id)));
        }, 200);
    });

    // Hàm cập nhật input hidden
    function updateCheckStatusInput() {
        const criteriaIds = selectedCriteria.map(item => item.id);
        $('#checkStatusInput').val(JSON.stringify(criteriaIds));
    }
    // Xác nhận xóa
    function confirmDelete(id) {
        $('#deleteConfirmModal').modal('show');

        $('#confirmDeleteBtn').off('click').on('click', function () {
            $.ajax({
                url: 'php_action/delete_schedule.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json', // jQuery sẽ tự động parse JSON
                success: function (data) { // data đã là đối tượng JavaScript
                    if (data.success) {
                        showToast('Schedule deleted successfully', 'success');
                        $('#deleteConfirmModal').modal('hide');
                        updateTableData(); // Cập nhật bảng mà không reload trang
                    } else {
                        showToast('Error: ' + data.messages, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Ajax Error:', error);
                    showToast('Error occurred while deleting schedule', 'error');
                }
            });
        });
    }

    function editSchedule(id) {
        // Reset form và dữ liệu
        editSelectedCriteria = [];
        $('#editCheckStatusSelect option').show();
        $('#editSelectedCriteria').empty();

        $.ajax({
            url: 'php_action/get_schedule.php',
            type: 'POST',
            data: { id: id },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    const schedule = data.schedule;

                    // Set các giá trị cơ bản
                    $('#editScheduleId').val(schedule.id);
                    $('#editDiets').val(schedule.diets);

                    // Định dạng và set ngày
                    const dateCheck = new Date(schedule.date_check);
                    const formattedDate = `${('0' + dateCheck.getDate()).slice(-2)}-${('0' + (dateCheck.getMonth() + 1)).slice(-2)}-${dateCheck.getFullYear()}`;
                    $('#editDateCheck').val(formattedDate);

                    // Set và update màu cho Work Done ngay lập tức
                    $('#editWorkDoneSelect')
                        .val(schedule.work_done)
                        .trigger('change');

                    // Xử lý Task Done
                    if (schedule.check_status) {
                        try {
                            const checkStatus = JSON.parse(schedule.check_status);
                            editSelectedCriteria = []; // Reset lại mảng

                            // Thêm từng task vào mảng selectedCriteria
                            checkStatus.forEach((id, index) => {
                                const option = $(`#editCheckStatusSelect option[value="${id}"]`);
                                if (option.length) {
                                    editSelectedCriteria.push({
                                        id: id,
                                        name: option.text(),
                                        color: taskColors[index % taskColors.length]
                                    });
                                    option.hide(); // Ẩn option đã chọn
                                }
                            });

                            // Cập nhật UI
                            updateEditCriteriaDisplay();
                            updateEditCheckStatusInput();
                        } catch (e) {
                            console.error('Error parsing check_status:', e);
                            showToast('Error loading task done data', 'error');
                        }
                    }
                }
            },
            error: function () {
                showToast('Error loading schedule data', 'error');
            }
        });
    }
    // Xử lý Work Done selection trong form edit
    $('#editWorkDoneSelect').on('change', function () {
        const selectedValue = $(this).val();
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.data('color');

        $(this).css('background-color', '');

        if (selectedValue) {
            $(this).css('background-color', color === 'g' ? '#d4edda' :
                color === 'b' ? '#cce5ff' :
                    color === 'o' ? '#ffeeba' :
                        color === 'r' ? '#f8d7da' : '');

            $(this).css('color', 'black');

            if (selectedValue === "Immersion ? challenge") {
                $('#editAdditionalOptions').show();
            } else {
                $('#editAdditionalOptions').hide();
            }
        } else {
            $('#editAdditionalOptions').hide();
        }
    });

    // Xử lý Immersion Option trong form edit
    $('#editImmersionOptionSelect').on('change', function () {
        const selectedImmersionOption = $(this).val();
        const workDoneSelect = $('#editWorkDoneSelect');

        if (selectedImmersionOption) {
            const newWorkDone = `Immersion ${selectedImmersionOption} challenge`;
            const newOption = new Option(newWorkDone, newWorkDone);
            $(newOption).attr('data-color', 'r');

            workDoneSelect.append(newOption);
            workDoneSelect.val(newWorkDone);
            workDoneSelect.trigger('change');
            $('#editAdditionalOptions').hide();
        }
    });

    // Hàm cập nhật bảng mà không reload trang
    function updateTableData() {
        $.ajax({
            url: 'php_action/get_schedule_data.php',
            type: 'POST',
            data: { case_study_id: '<?php echo $caseStudyId; ?>' },
            success: function (response) {
                $('#scheduleTableBody').html(response);
            }
        });
    }

    // Cập nhật tất cả schedule trong cùng ngày khi submit edit form
    $('#editScheduleForm').on('submit', function (e) {
        e.preventDefault();

        const dateCheck = $('#editDateCheck').val();
        const [day, month, year] = dateCheck.split('-');
        const formattedDate = `${year}-${month}-${day}`;
        const checkStatus = $('#editCheckStatusInput').val();

        // Gọi API để cập nhật tất cả schedule trong cùng ngày
        $.ajax({
            url: 'php_action/edit_schedule.php',
            type: 'POST',
            data: {
                id: $('#editScheduleId').val(),
                date_check: formattedDate,
                diets: $('#editDiets').val(),
                work_done: $('#editWorkDoneSelect').val(),
                check_status: checkStatus,
                update_all_in_date: true
            },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showToast('All schedules for this date updated successfully', 'success');
                    $('#editScheduleModal').modal('hide');
                    updateTableData();
                } else {
                    showToast('Error: ' + data.messages, 'error');
                }
            }
        });
    })
    const taskColors = [
        '#FFB6C1', '#98FB98', '#87CEFA', '#DDA0DD', '#F0E68C',
        '#E6E6FA', '#FFA07A', '#98FF98', '#87CEEB', '#FFB6C1'
    ];

    // Khởi tạo mảng để lưu các criteria đã chọn trong form edit
    let editSelectedCriteria = [];

    // Xử lý khi chọn criteria trong form edit
    $('#editCheckStatusSelect').on('change', function () {
        const selectedId = $(this).val();
        if (!selectedId) return;

        const selectedText = $(this).find('option:selected').text();
        const colorIndex = editSelectedCriteria.length % taskColors.length;

        // Thêm vào mảng editSelectedCriteria
        if (!editSelectedCriteria.find(item => item.id === selectedId)) {
            editSelectedCriteria.push({
                id: selectedId,
                name: selectedText,
                color: taskColors[colorIndex]
            });

            // Ẩn option đã chọn
            $(this).find(`option[value="${selectedId}"]`).hide();

            // Cập nhật UI
            updateEditCriteriaDisplay();
            updateEditCheckStatusInput();
        }

        // Reset select về giá trị mặc định
        $(this).val('');
    });

    // Hàm cập nhật hiển thị các criteria đã chọn trong form edit
    function updateEditCriteriaDisplay() {
        const container = $('#editSelectedCriteria');
        container.empty();

        editSelectedCriteria.forEach(criteria => {
            container.append(`
            <span class="criteria-tag" style="background-color: ${criteria.color}">
                ${criteria.name}
                <i class="fa fa-times remove-edit-criteria" data-id="${criteria.id}"></i>
            </span>
        `);
        });
    }

    // Xử lý xóa criteria trong form edit
    $(document).on('click', '.remove-edit-criteria', function () {
        const idToRemove = $(this).data('id');

        // Xóa khỏi mảng editSelectedCriteria
        editSelectedCriteria = editSelectedCriteria.filter(item => item.id !== idToRemove.toString());

        // Hiện lại option trong select
        $(`#editCheckStatusSelect option[value="${idToRemove}"]`).show();

        // Cập nhật UI
        updateEditCriteriaDisplay();
        updateEditCheckStatusInput();
    });

    // Cập nhật màu cho Work Done khi thay đổi
    $('#editWorkDoneSelect').on('change', function () {
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.data('color') || '#ffffff';
        $(this).css('background-color', color);
    });
    // Hàm cập nhật input hidden trong form edit
    function updateEditCheckStatusInput() {
        const criteriaIds = editSelectedCriteria.map(item => item.id);
        $('#editCheckStatusInput').val(JSON.stringify(criteriaIds));
    }

    // Lưu trữ dữ liệu schedule dưới dạng JavaScript object
    const scheduleData = <?php echo json_encode($scheduleData); ?>;

    // Thêm biến để theo dõi trạng thái khóa
    let isTaskDoneLocked = false;

    // Thêm event handler cho input ngày trong modal add schedule
    $('#dateCheck').on('change', function () {
        const dateCheck = $(this).val();

        // Reset Work Done (luôn cho phép chỉnh sửa)
        $('#workDoneSelect').val('');
        $('#workDoneSelect').prop('readonly', false);

        // Kiểm tra xem ngày đã chọn có dữ liệu không
        if (scheduleData[dateCheck]) {
            const data = scheduleData[dateCheck];

            // Chỉ điền và khóa Task Done
            if (data.check_status) {
                isTaskDoneLocked = true; // Đánh dấu là đã khóa
                const checkStatus = JSON.parse(data.check_status);
                selectedCriteria = []; // Reset mảng criteria hiện tại

                // Ẩn tất cả options trước
                $('#checkStatusSelect option').show();

                // Thêm từng criteria đã chọn
                checkStatus.forEach(id => {
                    const option = $(`#checkStatusSelect option[value="${id}"]`);
                    if (option.length) {
                        selectedCriteria.push({
                            id: id,
                            name: option.text()
                        });
                        option.hide(); // Ẩn option đã chọn
                    }
                });

                // Cập nhật hiển thị và input hidden
                updateCriteriaDisplay();
                updateCheckStatusInput();

                // Khóa không cho chọn thêm Task Done
                $('#checkStatusSelect').prop('disabled', true);
            }
        } else {
            // Reset Task Done và mở khóa
            isTaskDoneLocked = false; // Đánh dấu là đã mở khóa
            selectedCriteria = [];
            updateCriteriaDisplay();
            updateCheckStatusInput();
            $('#checkStatusSelect option').show();
            $('#checkStatusSelect').prop('disabled', false);
        }
    });

    // Reset form khi mở modal
    $('#addScheduleModal').on('show.bs.modal', function () {
        isTaskDoneLocked = false; // Reset trạng thái khóa
        $('#addScheduleForm')[0].reset();
        selectedCriteria = [];
        updateCriteriaDisplay();
        updateCheckStatusInput();
        $('#checkStatusSelect option').show();
        $('#checkStatusSelect').prop('disabled', false);
        
        // Reset selected-criteria container
        $('.selected-criteria').empty().css({
            'min-height': '45px',
            'padding': '10px',
            'border': '1px solid #ddd',
            'border-radius': '8px',
            'background': '#f8f9fa'
        });
    });

    // Thêm animation khi thêm task mới trong form add
    $('#checkStatusSelect').on('change', function() {
        const selectedId = $(this).val();
        if (!selectedId) return;

        const selectedText = $(this).find('option:selected').text();
        const colorIndex = selectedCriteria.length % 6; // Để luân phiên 6 màu

        // Thêm vào mảng selectedCriteria
        if (!selectedCriteria.find(item => item.id === selectedId)) {
            selectedCriteria.push({
                id: selectedId,
                name: selectedText
            });

            // Ẩn option đã chọn
            $(this).find(`option[value="${selectedId}"]`).hide();

            // Cập nhật UI với animation
            updateCriteriaDisplay();
            updateCheckStatusInput();
        }

        // Reset select về giá trị mặc định
        $(this).val('');
    });
</script>