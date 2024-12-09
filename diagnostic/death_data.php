<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

// Lấy dữ liệu từ bảng shrimp_death_data
$caseStudyId = $_GET['case_study_id'];
$filterDate = $_POST['filterDate'] ?? null; // Lấy giá trị filter date từ AJAX request (nếu có)

$sql = "SELECT id,treatment_name, product_application, DATE(test_time) AS test_date, HOUR(test_time) AS test_hour, rep, death_sample
        FROM shrimp_death_data
        WHERE case_study_id = ?
        ORDER BY 
        test_date ASC,
        CASE
            WHEN treatment_name = 'Negative control' THEN 1
            WHEN treatment_name = 'Positive control' THEN 2
            WHEN treatment_name = 'Treatment 1' OR treatment_name = 'Treatment T1' THEN 3
            WHEN treatment_name = 'Treatment 2' OR treatment_name = 'Treatment T2' THEN 4
            WHEN treatment_name = 'Treatment 3' OR treatment_name = 'Treatment T3' THEN 5
            WHEN treatment_name = 'Treatment 4' OR treatment_name = 'Treatment T4' THEN 6
            ELSE 7
        END,
        rep ASC,
        test_hour ASC";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}
$stmt->close();

// Nhóm dữ liệu theo ngày
$groupedByDate = [];
foreach ($entries as $entry) {
    $date = $entry['test_date'];
    $groupedByDate[$date][] = $entry;
}
?>
<!-- Edit Entry Modal -->
<div id="editEntryModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editEntryForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 style="color: black;" class="modal-title">Edit Death Sample</h4>
                </div>
                <div class="modal-body">
                    <!-- Hidden field for entry ID -->
                    <input type="hidden" name="id" id="editEntryId">

                    <!-- Treatment Name (readonly) -->
                    <div class="form-group">
                        <label for="editTreatmentName">Treatment Name</label>
                        <input type="text" class="form-control" id="editTreatmentName" name="treatment_name" readonly>
                    </div>

                    <!-- Product Application (readonly) -->
                    <div class="form-group">
                        <label for="editProductApplication">Product Application</label>
                        <input type="text" class="form-control" id="editProductApplication" name="product_application"
                            readonly>
                    </div>

                    <!-- Test Time (readonly, formatted as d-m-Y || H:i) -->
                    <div class="form-group">
                        <label for="editTestTime">Test Time</label>
                        <input type="text" class="form-control" id="editTestTime" name="test_time" readonly>
                    </div>

                    <!-- Death Sample -->
                    <div class="form-group">
                        <label for="editDeathSample">Death Sample</label>
                        <input type="number" class="form-control" id="editDeathSample" name="death_sample" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="updateEntry()">Save Changes</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-bs-dismiss="modal">Close</button>
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
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-10 align-self-center">
            <h3 class="text-primary">Death Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        </div>
    </div>
    <div class="filter-container mb-4">
        <form id="filterForm" class="d-flex align-items-center">
            <label for="filterDate" class="mr-2 font-weight-bold">Filter by Date:</label>
            <input type="text" id="filterDate" name="filterDate" class="form-control mr-2" style="width: 200px;"
                placeholder="Select a day to filter">
            <button type="button" class="btn btn-primary" onclick="applyDateFilter()">Filter</button>
            <button type="button" class="btn btn-secondary ml-2" onclick="resetFilter()">Reset</button>
        </form>
    </div>

    <div class="container-fluid">
        <?php foreach ($groupedByDate as $date => $dailyEntries): ?>
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center"
                        style="font-size: 1.5em;background-color:#A8CD89; color:black; font-weight: bold;">
                        Day: <?php echo date('d-m-Y', strtotime($date)); ?>
                    </h4>
                    <div class="table-responsive m-t-20">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 220px;">Treatment Name</th>
                                    <th style="width: 250px;">Product Application</th>
                                    <th style="width: 50px;">Reps</th>
                                    <th style="width: 50px;">Hour</th>
                                    <th>Death Sample</th>
                                    <th style=" text-align: center;width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $currentTreatment = '';
                                $currentRep = '';
                                foreach ($dailyEntries as $index => $entry):
                                    $isNewTreatment = $currentTreatment !== $entry['treatment_name'];
                                    $isNewRep = $currentRep !== $entry['rep'];

                                    if ($isNewTreatment) {
                                        $currentTreatment = $entry['treatment_name'];
                                        $currentRep = ''; // Reset current rep on new treatment
                                    }
                                    if ($isNewRep) {
                                        $currentRep = $entry['rep'];
                                    }

                                    // Tính rowspan cho Rep
                                    $repRowspan = count(array_filter($dailyEntries, function ($e) use ($currentTreatment, $currentRep) {
                                        return $e['treatment_name'] === $currentTreatment && $e['rep'] === $currentRep;
                                    }));
                                    ?>
                                    <tr>
                                        <?php if ($isNewRep): ?>
                                            <td rowspan="<?php echo $repRowspan; ?>" style="vertical-align: middle;">
                                                <?php echo htmlspecialchars($entry['treatment_name']); ?>
                                            </td>
                                            <td rowspan="<?php echo $repRowspan; ?>" style="vertical-align: middle;">
                                                <?php echo htmlspecialchars($entry['product_application']); ?>
                                            </td>
                                            <td rowspan="<?php echo $repRowspan; ?>" style="vertical-align: middle;">
                                                <?php echo htmlspecialchars($entry['rep']); ?>
                                            </td>
                                        <?php endif; ?>

                                        <td><?php echo htmlspecialchars($entry['test_hour']); ?>:00</td>
                                        <td><?php echo htmlspecialchars($entry['death_sample']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-warning btn-sm"
                                                    onclick="editEntry(<?php echo htmlspecialchars($entry['id']); ?>)">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="deleteEntry(<?php echo htmlspecialchars($entry['id']); ?>)">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize Flatpickr for filterDate
        flatpickr("#filterDate", {
            dateFormat: "d-m-Y", // Set the display format to dd-MM-YYYY
        });
    });

    // Function to apply date filter
    function applyDateFilter() {
        const filterDateInput = document.getElementById('filterDate').value;

        if (!filterDateInput) {
            showToast('Please select a valid date to filter!', 'Error', false);
            return;
        }

        const formattedDate = filterDateInput.split('-').reverse().join('-'); // Ensure proper format
        fetchFilteredData('<?php echo $caseStudyId; ?>', formattedDate);
    }


    function fetchFilteredData(caseStudyId, filterDate = null) {
        $.ajax({
            url: 'php_action/filter_shrimp_data.php',
            type: 'POST',
            data: {
                case_study_id: caseStudyId,
                filterDate: filterDate
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderFilteredData(response.data);
                    showToast('Filter applied successfully!', 'Success', true);
                } else {
                    showToast(response.message || 'Failed to fetch data!', 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error during data fetch!', 'Error', false);
            }
        });
    }

    // Function to reset the filter
    function resetFilter() {
        document.getElementById("filterDate").value = ""; // Clear the filter date
        location.reload(); // Reload the page
    }

    // Function to render filtered data
    function renderFilteredData(groupedByDate) {
        console.log(groupedByDate); // Kiểm tra cấu trúc dữ liệu
        const container = document.querySelector(".container-fluid");
        container.innerHTML = ""; // Xóa nội dung cũ

        for (const [date, treatments] of Object.entries(groupedByDate)) {
            // Tạo card cho mỗi ngày
            const card = document.createElement("div");
            card.className = "card";

            const cardBody = document.createElement("div");
            cardBody.className = "card-body";

            const header = document.createElement("h4");
            header.className = "text-center";
            header.style =
                "font-size: 1.3em;background-color:#A8CD89; color:black; font-weight: bold;";
            header.textContent = `Day: ${date.split("-").reverse().join("-")}`;

            const table = document.createElement("table");
            table.className = "table table-bordered table-striped";

            // Tạo header cho bảng
            const thead = document.createElement("thead");
            thead.innerHTML = `
        <tr>
            <th style="width: 220px;">Treatment Name</th>
            <th style="width: 250px;">Product Application</th>
            <th style="width: 50px;">Reps</th>
            <th style="width: 50px;">Hour</th>
            <th>Death Sample</th>
            <th style="text-align: center;width: 150px;">Action</th>
        </tr>
    `;

            const tbody = document.createElement("tbody");

            // Lặp qua treatments và reps
            for (const [treatmentName, reps] of Object.entries(treatments)) {
                for (const [rep, entries] of Object.entries(reps)) {
                    const repRowspan = entries.length;

                    entries.forEach((entry, index) => {
                        const row = document.createElement("tr");

                        // Hiển thị Treatment Name cho mỗi Rep một lần duy nhất
                        if (index === 0) {
                            row.innerHTML += `
                        <td rowspan="${repRowspan}" style="vertical-align: middle;">${treatmentName}</td>
                        <td rowspan="${repRowspan}" style="vertical-align: middle;">${entry.product_application}</td>
                        <td rowspan="${repRowspan}" style="vertical-align: middle;">${rep}</td>
                    `;
                        }

                        // Thêm các cột còn lại
                        row.innerHTML += `
                    <td>${entry.hour}:00</td>
                    <td>${entry.death_sample}</td>
            <td>
    <div class="action-buttons">
        <button class="btn btn-warning btn-sm" onclick="editEntry(${entry.id})">
            <i class="fa fa-pencil"></i> Edit
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteEntry(${entry.id})">
            <i class="fa fa-trash"></i> Delete
        </button>
    </div>
</td>

                `;

                        tbody.appendChild(row);
                    });
                }
            }

            table.appendChild(thead);
            table.appendChild(tbody);
            cardBody.appendChild(header);
            cardBody.appendChild(table);
            card.appendChild(cardBody);
            container.appendChild(card);
        }
    }

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
    // Open the Edit Modal and populate data
    function editEntry(entryID) {
        console.log("Editing entry with ID:", entryID); // Debugging

        $.ajax({
            url: 'php_action/get_entry_death.php',
            type: 'POST',
            data: { id: entryID},
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const data = response.data;

                    // Kiểm tra và xử lý định dạng ngày trực tiếp
                    let formattedDate = 'Invalid Date';
                    if (data.test_date && typeof data.test_date === 'string') {
                        const dateParts = data.test_date.split('-');
                        if (dateParts.length === 3) {
                            formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                        } else {
                            console.error('Invalid date format:', data.test_date);
                        }
                    } else {
                        console.error('Invalid or missing test_date:', data.test_date);
                    }

                    // Điền dữ liệu vào các trường
                    document.getElementById('editEntryId').value = data.id;
                    document.getElementById('editTreatmentName').value = data.treatment_name;
                    document.getElementById('editProductApplication').value = data.product_application;
                    document.getElementById('editTestTime').value = `${formattedDate} || ${data.test_hour}:00`;
                    document.getElementById('editDeathSample').value = data.death_sample;

                    // Hiển thị modal
                    $('#editEntryModal').modal('show');
                } else {
                    showToast(response.message || 'Failed to fetch entry details!', 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error fetching entry details!', 'Error', false);
            }
        });
    }




    function updateEntry() {
        const formData = $('#editEntryForm').serializeArray();
        const data = {};

        // Tạo đối tượng dữ liệu từ form
        formData.forEach(field => {
            data[field.name] = field.value;
        });

        // Lấy giá trị filterDate nếu có
        const filterDate = $('#filterDate').val();

        $.ajax({
            url: 'php_action/edit_shrimp_death_data.php', // API cập nhật bản ghi
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Entry updated successfully!', 'Success', true);
                    $('#editEntryModal').modal('hide'); // Đóng modal

                    if (filterDate) {
                        // Nếu có filterDate, chỉ làm mới dữ liệu được lọc
                        applyDateFilter();
                    } else {
                        // Nếu không có filterDate, reload toàn bộ trang
                        location.reload();
                    }
                } else {
                    showToast(response.message || 'Failed to update entry!', 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error updating entry!', 'Error', false);
            }
        });
    }




    function deleteEntry(entryId) {
        if (confirm('Are you sure you want to delete this entry?')) {
            const filterDate = $('#filterDate').val(); // Lấy giá trị filterDate nếu có

            $.ajax({
                url: './php_action/delete_shrimp_death_data.php', // API xử lý xóa bản ghi
                type: 'POST',
                data: { id: entryId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast(response.message || 'Death shrimp data deleted successfully!', 'Success', true);

                        if (filterDate) {
                            // Nếu có filterDate, làm mới dữ liệu đã lọc
                            applyDateFilter();
                        } else {
                            // Nếu không có filterDate, reload lại toàn bộ trang
                            location.reload();
                        }
                    } else {
                        showToast(response.message || 'Failed to delete entry!', 'Error', false);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    showToast('Error during entry deletion!', 'Error', false);
                }
            });
        }
    }


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

    .action-buttons {
        display: flex;
        gap: 10px;
        /* Khoảng cách giữa các nút */
    }

    .filter-container {
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
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

    /* Tăng độ đậm cho text trong bảng */
    .table th {
        font-weight: bold;
        font-size: 1.3em;
        color: black;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
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

    /* Tăng độ dày của đường ngăn ngang giữa các treatment */
    .table-bordered tbody tr td {
        border: 2px solid #333;
        /* Đường viền đậm hơn */
    }

    .table-bordered th {
        border: 2px solid #333;
        /* Đường viền đậm hơn */
    }

    .table-bordered tbody tr td[rowspan] {
        border-top: 3px solid #333;
        /* Đường viền trên các treatment đậm hơn */
    }
</style>
<?php include('./constant/layout/footer.php'); ?>