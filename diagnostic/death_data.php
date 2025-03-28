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
                    <div class="form-group">
                        <label for="editRep">Rep</label>
                        <input type="text" class="form-control" id="editRep" name="rep" readonly>
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
    <div class="filter-container mb-4 d-flex justify-content-between align-items-center">
        <!-- Bộ lọc ngày -->
        <form id="filterForm" class="d-flex align-items-center">
            <label for="filterDate" class="mr-2 font-weight-bold">Filter by Date:</label>
            <input type="text" id="filterDate" name="filterDate" class="form-control mr-2" style="width: 200px;"
                placeholder="Select a day to filter">
            <button type="button" class="btn btn-primary" onclick="applyDateFilter()">Filter</button>
            <button type="button" class="btn btn-secondary ml-2" onclick="resetFilter()">Reset</button>
        </form>

        <!-- Nút xóa nhiều dòng và Change View -->
        <div>
            <button type="button" class="btn btn-danger me-2" id="deleteSelectedBtn" style="display: none;" onclick="deleteSelectedEntries()">
                <i class="fa fa-trash"></i> Delete Selected
            </button>
            <button type="button" class="btn btn-danger me-2" id="deleteByDateBtn" style="display: none;" onclick="deleteByDate()">
                <i class="fa fa-trash"></i> Delete By Date
            </button>
            <button type="button" id="changeViewButton" class="btn btn-info" onclick="changeView()">
                Change View
            </button>
        </div>
    </div>


    <div class="container-fluid">
        <?php foreach ($groupedByDate as $date => $dailyEntries): ?>
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center mb-4"
                        style="font-size: 1.5em;background-color:#A8CD89; color:black; font-weight: bold; padding: 10px;">
                        Day: <?php echo date('d-m-Y', strtotime($date)); ?>
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Treatment Name</th>
                                    <th style="width: 25%;">Product Application</th>
                                    <th style="width: 10%;">Reps</th>
                                    <th style="width: 10%;">Hour</th>
                                    <th style="width: 15%;">Death Sample</th>
                                    <th style="width: 15%;">Action</th>
                                    <th style="width: 5%;">Delete Many</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Sắp xếp entries theo treatment_name
                                usort($dailyEntries, function ($a, $b) {
                                    return strcmp($a['treatment_name'], $b['treatment_name']);
                                });

                                // Nhóm entries theo treatment_name
                                $groupedTreatments = [];
                                foreach ($dailyEntries as $entry) {
                                    $treatmentName = $entry['treatment_name'];
                                    if (!isset($groupedTreatments[$treatmentName])) {
                                        $groupedTreatments[$treatmentName] = [];
                                    }
                                    $groupedTreatments[$treatmentName][] = $entry;
                                }

                                foreach ($groupedTreatments as $treatmentName => $treatmentEntries) {
                                    // Tính tổng số hàng cho treatment này
                                    $totalRows = count($treatmentEntries);
                                    $firstTreatmentRow = true;

                                    // Nhóm theo rep
                                    $groupedReps = [];
                                    foreach ($treatmentEntries as $entry) {
                                        $rep = $entry['rep'];
                                        if (!isset($groupedReps[$rep])) {
                                            $groupedReps[$rep] = [];
                                        }
                                        $groupedReps[$rep][] = $entry;
                                    }

                                    foreach ($groupedReps as $rep => $repEntries) {
                                        $repRowCount = count($repEntries);
                                        $firstRepRow = true;

                                        foreach ($repEntries as $entry) {
                                ?>
                                            <tr>
                                                <?php if ($firstTreatmentRow): ?>
                                                    <td rowspan="<?php echo $totalRows; ?>" class="align-middle">
                                                        <?php echo htmlspecialchars($treatmentName); ?>
                                                    </td>
                                                    <td rowspan="<?php echo $totalRows; ?>" class="align-middle">
                                                        <?php echo htmlspecialchars($entry['product_application']); ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstRepRow): ?>
                                                    <td rowspan="<?php echo $repRowCount; ?>" class="align-middle text-center">
                                                        <?php echo htmlspecialchars($rep); ?>
                                                    </td>
                                                <?php endif; ?>

                                                <td class="text-center">
                                                    <?php echo sprintf('%02d:00', $entry['test_hour']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo htmlspecialchars($entry['death_sample']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-warning btn-sm me-2"
                                                            onclick="editEntry(<?php echo htmlspecialchars($entry['id']); ?>)">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="deleteEntry(<?php echo htmlspecialchars($entry['id']); ?>)">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="entry-checkbox" value="<?php echo htmlspecialchars($entry['id']); ?>">
                                                </td>
                                            </tr>
                                <?php
                                            $firstRepRow = false;
                                            $firstTreatmentRow = false;
                                        }
                                    }
                                }
                                ?>
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
<!-- SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
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
            success: function(response) {
                if (response.success) {
                    renderFilteredData(response.data);
                    showToast('Filter applied successfully!', 'Success', true);
                } else {
                    showToast(response.message || 'Failed to fetch data!', 'Error', false);
                }
            },
            error: function(xhr, status, error) {
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

    function compareTreatmentOrder(a, b) {
        const order = {
            "Negative control": 1,
            "Positive control": 2,
            "Treatment 1": 3,
            "Treatment T1": 3,
            "Treatment 2": 4,
            "Treatment T2": 4,
            "Treatment 3": 5,
            "Treatment T3": 5,
            "Treatment 4": 6,
            "Treatment T4": 6,
        };

        const orderA = order[a] || 7; // Mặc định là 7 nếu không có trong thứ tự
        const orderB = order[b] || 7;
        return orderA - orderB;
    }

    function renderFilteredData(groupedByDate) {
        const container = document.querySelector(".container-fluid");
        container.innerHTML = ""; // Xóa nội dung cũ

        for (const [date, treatments] of Object.entries(groupedByDate)) {
            // Sắp xếp `treatments` theo thứ tự đã định nghĩa
            const sortedTreatments = Object.keys(treatments).sort(compareTreatmentOrder);

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
            <th style="width: 50px;">Delete Many</th>
        </tr>
    `;

            const tbody = document.createElement("tbody");

            // Lặp qua các treatment đã được sắp xếp
            for (const treatmentName of sortedTreatments) {
                const reps = treatments[treatmentName];
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
                    <td class="text-center">
                        <input type="checkbox" class="entry-checkbox" value="${entry.id}">
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
        $.ajax({
            url: 'php_action/get_entry_death.php',
            type: 'POST',
            data: {
                id: entryID
            },
            dataType: 'json',
            success: function(response) {
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
                    document.getElementById('editRep').value = data.rep; // Điền giá trị Rep

                    // Hiển thị modal
                    $('#editEntryModal').modal('show');
                } else {
                    showToast(response.message || 'Failed to fetch entry details!', 'Error', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error fetching entry details!', 'Error', false);
            }
        });
    }

    function updateEntry() {
        const formData = $('#editEntryForm').serializeArray();
        const data = {};

        formData.forEach(field => {
            data[field.name] = field.value;
        });

        const filterDate = $('#filterDate').val();

        $.ajax({
            url: 'php_action/edit_shrimp_death_data.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editEntryModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: 'Entry updated successfully!',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        setTimeout(() => {
                            if (filterDate) {
                                applyDateFilter();
                            } else {
                                location.reload();
                            }
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to update entry!',
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Error updating entry!',
                    icon: 'error'
                });
            }
        });
    }

    function deleteEntry(entryId) {
        Swal.fire({
            title: 'Confirm Deletion',
            html: 'Are you sure you want to delete this entry?<br>This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const filterDate = $('#filterDate').val();

                $.ajax({
                    url: './php_action/delete_shrimp_death_data.php',
                    type: 'POST',
                    data: {
                        id: entryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message || 'Death shrimp data deleted successfully!',
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                setTimeout(() => {
                                    if (filterDate) {
                                        applyDateFilter();
                                    } else {
                                        location.reload();
                                    }
                                }, 500);
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to delete entry!',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Response:', xhr.responseText);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error during entry deletion!',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }

    function changeView() {
        const caseStudyId = "<?php echo htmlspecialchars($caseStudyId); ?>"; // Lấy giá trị caseStudyId từ PHP
        const filterDate = document.getElementById('filterDate').value || null;

        // Xây dựng URL với các tham số (nếu có)
        let url = `view_death_data.php?case_study_id=${caseStudyId}`;
        if (filterDate) {
            const formattedDate = filterDate.split('-').reverse().join('-'); // Định dạng ngày
            url += `&filterDate=${formattedDate}`;
        }

        // Chuyển hướng đến file view_death_data.php
        window.location.href = url;
    }

    // Hàm kiểm tra và hiển thị nút Delete Selected
    function updateDeleteButtonVisibility() {
        const selectedCheckboxes = document.getElementsByClassName('entry-checkbox');
        const selectedCount = Array.from(selectedCheckboxes).filter(checkbox => checkbox.checked).length;
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        deleteSelectedBtn.style.display = selectedCount > 0 ? 'inline-block' : 'none';
    }

    // Hàm xóa nhiều dòng
    function deleteSelectedEntries() {
        const selectedCheckboxes = document.getElementsByClassName('entry-checkbox');
        const selectedIds = Array.from(selectedCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => parseInt(checkbox.value));

        if (selectedIds.length === 0) {
            Swal.fire({
                title: 'Warning!',
                text: 'Please select at least one row to delete!',
                icon: 'warning'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete ${selectedIds.length} selected rows?<br>This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const filterDate = $('#filterDate').val();
                
                // Hiện thông báo loading
                Swal.fire({
                    title: 'Đang xóa dữ liệu...',
                    text: 'Vui lòng đợi trong giây lát',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Sử dụng cách tối ưu để gửi dữ liệu
                $.ajax({
                    url: './php_action/delete_multiple_death_data.php',
                    type: 'POST',
                    data: { ids: selectedIds },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: `Successfully deleted ${response.deleted_count} items`,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                if (filterDate) {
                                    applyDateFilter();
                                } else {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to delete selected rows!',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.close();
                        
                        let errorMessage = 'An error occurred while deleting data!';
                        try {
                            if (xhr.responseText) {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            }
                        } catch (e) {
                            errorMessage = `Error: ${error}`;
                        }
                        
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }

    function deleteByDate() {
        const filterDateInput = document.getElementById('filterDate').value;

        if (!filterDateInput) {
            Swal.fire({
                title: 'Warning!',
                text: 'Please select a date to delete!',
                icon: 'warning'
            });
            return;
        }

        const formattedDate = filterDateInput.split('-').reverse().join('-');

        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete all entries for date ${filterDateInput}?<br>This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete all!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: './php_action/delete_entries_by_date.php',
                    type: 'POST',
                    data: {
                        case_study_id: '<?php echo $caseStudyId; ?>',
                        date: formattedDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message || 'All entries for the selected date were deleted successfully!',
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                setTimeout(() => {
                                    location.reload();
                                }, 500);
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to delete entries!',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error during entries deletion!',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo event listener cho checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('entry-checkbox')) {
                updateDeleteButtonVisibility();
            }
        });

        // Khởi tạo event listener cho filter date
        document.getElementById('filterDate').addEventListener('change', function() {
            const deleteByDateBtn = document.getElementById('deleteByDateBtn');
            deleteByDateBtn.style.display = this.value ? 'inline-block' : 'none';
        });

        // Kiểm tra trạng thái ban đầu
        updateDeleteButtonVisibility();
        const filterDate = document.getElementById('filterDate').value;
        const deleteByDateBtn = document.getElementById('deleteByDateBtn');
        deleteByDateBtn.style.display = filterDate ? 'inline-block' : 'none';
    });
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

    .card {
        margin-bottom: 2rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-body {
        padding: 1.5rem;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
    }

    .btn-group {
        display: flex;
        gap: 0.5rem;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .align-middle {
        vertical-align: middle !important;
    }

    .text-center {
        text-align: center !important;
    }

    /* Đảm bảo các cột có chiều rộng cố định */
    .table-responsive {
        overflow-x: auto;
    }

    /* Thêm hover effect cho các hàng */
    .table-striped tbody tr:hover {
        background-color: rgba(0, 0, 0, .05);
    }

    /* Style cho các nút action */
    .btn-warning {
        color: #fff;
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-danger {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }

    /* Đảm bảo các icon có khoảng cách với text */
    .fa {
        margin-right: 4px;
    }
</style>
<?php include('./constant/layout/footer.php'); ?>