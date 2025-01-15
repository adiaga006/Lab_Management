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
?>

<style>
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
        gap: 5px;
    }

    .criteria-tag {
        background: #e9ecef;
        padding: 5px 10px;
        border-radius: 3px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .remove-criteria {
        cursor: pointer;
        color: #dc3545;
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

    /* Criteria tag styles với 10 màu khác nhau */
    .criteria-tag:nth-of-type(10n + 1) {
        background-color: #FF9AA2;
    }

    .criteria-tag:nth-of-type(10n + 2) {
        background-color: #FFB7B2;
    }

    .criteria-tag:nth-of-type(10n + 3) {
        background-color: #FFDAC1;
    }

    .criteria-tag:nth-of-type(10n + 4) {
        background-color: #E2F0CB;
    }

    .criteria-tag:nth-of-type(10n + 5) {
        background-color: #B5EAD7;
    }

    .criteria-tag:nth-of-type(10n + 6) {
        background-color: #C7CEEA;
    }

    .criteria-tag:nth-of-type(10n + 7) {
        background-color: #E8E8E8;
    }

    .criteria-tag:nth-of-type(10n + 8) {
        background-color: #F8C8DC;
    }

    .criteria-tag:nth-of-type(10n + 9) {
        background-color: #B4F8C8;
    }

    .criteria-tag:nth-of-type(10n) {
        background-color: #A0E7E5;
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
                <!-- Button to trigger modal -->
                <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#scheduleModal">
                    <i class="fa fa-check-circle"></i> Confirm completed tasks
                </button>

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

        selectedCriteria.forEach(criteria => {
            container.append(`
                <span class="criteria-tag">
                    ${criteria.name}
                    <i class="fa fa-times remove-criteria" data-id="${criteria.id}"></i>
                </span>
            `);
        });
    }

    // Sửa lại hàm xử lý xóa criteria
    $(document).on('click', '.remove-criteria', function () {
        const idToRemove = $(this).data('id');

        // Xóa khỏi mảng selectedCriteria
        selectedCriteria = selectedCriteria.filter(item => item.id !== idToRemove.toString());

        // Hiện lại option trong select
        $(`#checkStatusSelect option[value="${idToRemove}"]`).show();

        // Cập nhật hiển thị
        updateCriteriaDisplay();

        // Cập nhật input hidden
        updateCheckStatusInput();
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
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showToast('Schedule deleted successfully', 'success');
                        $('#deleteConfirmModal').modal('hide');
                        updateTableData(); // Cập nhật bảng mà không reload trang
                    } else {
                        showToast('Error: ' + data.messages, 'error');
                    }
                }
            });
        });
    }

    function editSchedule(id) {
        editSelectedCriteria = []; // Reset mảng criteria

        $.ajax({
            url: 'php_action/get_schedule.php',
            type: 'POST',
            data: { id: id },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    const schedule = data.schedule;

                    $('#editScheduleId').val(schedule.id);
                    // Định dạng lại ngày ở dạng d-m-Y
                    const dateCheck = new Date(schedule.date_check);
                    const formattedDate = `${('0' + dateCheck.getDate()).slice(-2)}-${('0' + (dateCheck.getMonth() + 1)).slice(-2)}-${dateCheck.getFullYear()}`;
                    $('#editDateCheck').val(formattedDate);

                    $('#editDiets').val(schedule.diets);

                    // Set Work Done
                    const workDoneSelect = $('#editWorkDoneSelect');
                    workDoneSelect.val(schedule.work_done);

                    // Kiểm tra và thêm giá trị "Immersion EMS challenge" và "Immersion TSB+ challenge" nếu chưa có
                    const immersionOptions = ["Immersion EMS challenge", "Immersion TSB+ challenge"];
                    immersionOptions.forEach(option => {
                        if (schedule.work_done.includes(option) && !workDoneSelect.find(`option[value="${option}"]`).length) {
                            const newOption = new Option(option, option);
                            $(newOption).attr('data-color', 'r'); // Gán màu cho option
                            workDoneSelect.append(newOption); // Thêm option vào select
                        }
                    });

                    // Chọn option vừa thêm nếu có
                    workDoneSelect.val(schedule.work_done);

                    // Set màu cho Work Done
                    const selectedOption = workDoneSelect.find('option:selected');
                    const color = selectedOption.data('color');
                    workDoneSelect.css('background-color', color === 'g' ? '#d4edda' :
                        color === 'b' ? '#cce5ff' :
                            color === 'o' ? '#ffeeba' :
                                color === 'r' ? '#f8d7da' : '');

                    // Load Task Done
                    const checkStatusIds = JSON.parse(schedule.check_status);
                    checkStatusIds.forEach((id, index) => {
                        const option = $(`#editCheckStatusSelect option[value="${id}"]`);
                        if (option.length) {
                            editSelectedCriteria.push({
                                id: id,
                                name: option.text(),
                                color: taskColors[index % taskColors.length]
                            });
                            option.hide();
                        }
                    });
                    updateEditCriteriaDisplay();
                    updateEditCheckStatusInput();
                }
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

    // Xử lý form edit submission
    $('#editScheduleForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: 'php_action/edit_schedule.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showToast('Schedule updated successfully', 'success');
                    $('#editScheduleModal').modal('hide');
                    updateTableData();
                } else {
                    showToast('Error: ' + data.messages, 'error');
                }
            }
        });
    });
    // Mảng màu random cho các task
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
        }

        // Cập nhật hiển thị
        updateEditCriteriaDisplay();

        // Ẩn option đã chọn
        $(this).find(`option[value="${selectedId}"]`).hide();

        // Reset select về giá trị mặc định
        $(this).val('');

        // Cập nhật input hidden
        updateEditCheckStatusInput();
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

        // Cập nhật hiển thị
        updateEditCriteriaDisplay();

        // Cập nhật input hidden
        updateEditCheckStatusInput();
    });

    // Hàm cập nhật input hidden trong form edit
    function updateEditCheckStatusInput() {
        const criteriaIds = editSelectedCriteria.map(item => item.id);
        $('#editCheckStatusInput').val(JSON.stringify(criteriaIds));
    }
</script>