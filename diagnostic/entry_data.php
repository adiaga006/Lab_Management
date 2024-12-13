<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'];

// Lấy categories_id từ bảng case_study
$sql = "SELECT categories_id, start_date, phases FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$caseStudyResult = $stmt->get_result();
$caseStudy = $caseStudyResult->fetch_assoc();
$categoriesId = $caseStudy['categories_id'];
$startDate = $caseStudy['start_date'];
$stmt->close();

// Lấy group_id từ bảng category_groups dựa trên categories_id
$groupSql = "SELECT group_id FROM category_groups WHERE category_id = ?";
$stmt = $connect->prepare($groupSql);
$stmt->bind_param("i", $categoriesId);
$stmt->execute();
$groupResult = $stmt->get_result();
$groupData = $groupResult->fetch_assoc();
$groupId = $groupData['group_id'];
$stmt->close();

// Lấy group_name từ bảng groups dựa trên group_id
$groupNameSql = "SELECT group_name FROM groups WHERE group_id = ?";
$stmt = $connect->prepare($groupNameSql);
$stmt->bind_param("i", $groupId);
$stmt->execute();
$groupNameResult = $stmt->get_result();
$groupNameData = $groupNameResult->fetch_assoc();
$groupName = $groupNameData['group_name'];
$phasesJson = $caseStudy['phases'] ?? '[]'; // Default to empty JSON array if phases is null
$stmt->close();
function definePhasesWithDates($phasesJson, $startDate)
{
    // Decode JSON to array
    $phases = json_decode($phasesJson, true);

    // Validate if JSON is a valid array
    if (!is_array($phases)) {
        $phases = [];
    }

    // Initialize variables
    $computedPhases = [];
    $currentDate = new DateTime($startDate);

    foreach ($phases as $phase) {
        // Ensure each phase has a name and duration
        if (empty($phase['name']) || empty($phase['duration']) || !is_numeric($phase['duration'])) {
            continue; // Skip invalid phase entries
        }

        // Calculate start and end dates
        $startDate = $currentDate->format('Y-m-d');
        $currentDate->modify("+{$phase['duration']} days");
        $endDate = $currentDate->modify("-1 day")->format('Y-m-d');
        $currentDate->modify("+1 day"); // Prepare for the next phase

        // Append computed phase
        $computedPhases[] = [
            'name' => $phase['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration' => $phase['duration'],
        ];
    }

    return $computedPhases;
}
$phases = definePhasesWithDates($phasesJson, $startDate);


// Truy vấn dữ liệu entry_data theo case_study_id
$entrySql = "SELECT * FROM entry_data WHERE case_study_id = ?
                ORDER BY 
                CASE
                    WHEN treatment_name = 'Negative control' THEN 1
                    WHEN treatment_name = 'Positive control' THEN 2
                    WHEN treatment_name = 'Treatment 1' OR treatment_name = 'Treatment T1' THEN 3
                    WHEN treatment_name = 'Treatment 2' OR treatment_name = 'Treatment T2' THEN 4
                    WHEN treatment_name = 'Treatment 3' OR treatment_name = 'Treatment T3' THEN 5
                    WHEN treatment_name = 'Treatment 4' OR treatment_name = 'Treatment T4' THEN 6
                    ELSE 7
                END,
                lab_day ASC,
                rep ASC";

$stmt = $connect->prepare($entrySql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$entryResult = $stmt->get_result();
$entries = [];
while ($entry = $entryResult->fetch_assoc()) {
    $entries[] = $entry;
}
$stmt->close();

// Nhóm các entries theo phase
function groupEntriesByPhase($entries, $phases)
{
    $grouped = [];
    foreach ($phases as $phase) {
        if (empty($phase['start_date']) || empty($phase['end_date'])) {
            continue; // Skip phases with incomplete date ranges
        }

        $phaseStartDate = (new DateTime($phase['start_date']))->format('Y-m-d');
        $phaseEndDate = (new DateTime($phase['end_date']))->format('Y-m-d');

        $phaseEntries = array_filter($entries, function ($entry) use ($phaseStartDate, $phaseEndDate) {
            $entryDate = (new DateTime($entry['lab_day']))->format('Y-m-d');
            return $entryDate >= $phaseStartDate && $entryDate <= $phaseEndDate;
        });

        $grouped[] = ["phase" => $phase, "entries" => $phaseEntries];
    }
    return $grouped;
}
$groupedEntries = groupEntriesByPhase($entries, $phases);
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-10 align-self-center">
            <h3 class="text-primary">
                Raw Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?> - Group:
                <?php echo htmlspecialchars($groupName); ?>
            </h3>
        </div>
    </div>

    <div class="filter-container mb-4">
        <form id="filterForm" class="d-flex align-items-center">
            <label for="filterDate" class="mr-2 font-weight-bold">Filter by Date:</label>
            <input type="text" id="filterDate" name="filterDate" class="form-control mr-2" style="width: 200px;"
                placeholder="Select a day to filter" />
            <button type="button" class="btn btn-primary" onclick="applyDateFilter()">Filter</button>
            <button type="button" class="btn btn-secondary ml-2" onclick="resetTableFilter()">Reset</button>
        </form>
    </div>
    <div class="card-body d-flex justify-content-between" style="padding: 10px 50px;">
        <a href="entry_data_survival.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
            class="btn btn-primary">
            Change Survival Sample View
        </a>
        <a href="entry_data_feeding.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>"
            class="btn btn-primary">
            Change Feeding Table View
        </a>
    </div>



    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <?php foreach ($groupedEntries as $group): ?>
                    <div class="phase-container">
                        <h5 class="phase-title"
                            style="text-align: center;background-color:#A8CD89;font-size: 1.5em; color: black; font-weight: bold;">
                            <?php echo htmlspecialchars($group['phase']['name']); ?>
                            (<?php echo date('d-m-Y', strtotime($group['phase']['start_date'])); ?> to
                            <?php echo date('d-m-Y', strtotime($group['phase']['end_date'])); ?>)
                        </h5>

                        <?php $phaseEntries = $group['entries']; ?>

                        <?php if (!empty($phaseEntries)): ?>
                            <div class="table-responsive m-t-20">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 160px;">Treatment Name</th>
                                            <th style="width: 200px;">Product Application</th>
                                            <th style="width: 105px;" width: 50px>Day</th>
                                            <th style="width: 50px;">Reps</th>
                                            <th style="text-align: center;width: 150px;">Survival Sample</th>
                                            <th style="text-align: center;width: 150px;">Feeding Weight</th>
                                            <th style=" text-align: center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $currentTreatment = '';
                                        $currentDate = '';
                                        foreach ($phaseEntries as $entry):
                                            $isNewTreatment = $currentTreatment !== $entry['treatment_name'];
                                            $isNewDay = $currentDate !== $entry['lab_day'];

                                            if ($isNewTreatment) {
                                                $currentTreatment = $entry['treatment_name'];
                                                $currentDate = $entry['lab_day'];
                                            } elseif ($isNewDay) {
                                                $currentDate = $entry['lab_day'];
                                            }
                                            ?>
                                            <tr>
                                                <?php if ($isNewTreatment): ?>
                                                    <?php
                                                    $treatmentRowCount = count(array_filter($phaseEntries, function ($e) use ($currentTreatment) {
                                                        return $e['treatment_name'] === $currentTreatment;
                                                    }));
                                                    ?>
                                                    <td rowspan="<?php echo $treatmentRowCount; ?>"
                                                        style="vertical-align: middle; font-weight: bold;">
                                                        <?php echo htmlspecialchars($entry['treatment_name']); ?>
                                                    </td>
                                                    <td rowspan="<?php echo $treatmentRowCount; ?>" style="vertical-align: middle;">
                                                        <?php echo htmlspecialchars($entry['product_application']); ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($isNewDay || $isNewTreatment): ?>
                                                    <?php
                                                    $dayRowCount = count(array_filter($phaseEntries, function ($e) use ($currentDate, $currentTreatment) {
                                                        return $e['lab_day'] === $currentDate && $e['treatment_name'] === $currentTreatment;
                                                    }));
                                                    ?>
                                                    <td rowspan="<?php echo $dayRowCount; ?>" style="vertical-align: middle;">
                                                        <?php echo date('d-m-Y', strtotime($entry['lab_day'])); ?>
                                                    </td>
                                                <?php endif; ?>

                                                <td><?php echo htmlspecialchars($entry['rep']); ?></td>
                                                <td><?php echo htmlspecialchars($entry['survival_sample']); ?></td>
                                                <td><?php echo htmlspecialchars($entry['feeding_weight']); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-warning btn-sm"
                                                            onclick="editEntryData(<?php echo $entry['entry_data_id']; ?>)">
                                                            <i class="fa fa-pencil">Edit</i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="deleteEntryData(<?php echo $entry['entry_data_id']; ?>)">
                                                            <i class="fa fa-trash">Delete</i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No entries for this phase.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


</div>
<!-- Edit Data Modal -->
<div id="editDataModal" class="modal fade" role="dialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editDataForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Entry Data</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="entry_data_id" id="editEntryId">

                    <div class="form-group">
                        <label>Treatment Name</label>
                        <input type="text" name="treatment_name" class="form-control" id="editTreatmentName" readonly>
                    </div>

                    <div class="form-group">
                        <label>Product Application</label>
                        <input type="text" name="product_application" class="form-control" id="editProductApplication"
                            readonly>
                    </div>
                    <div class="form-group">
                        <label>Lab Day</label>
                        <input type="text" name="lab_day" class="form-control" id="editLabDay" readonly>
                    </div>

                    <div class="form-group">
                        <label>Rep</label>
                        <input type="number" name="rep" class="form-control" id="editRep">
                    </div>

                    <div class="form-group">
                        <label>Survival Sample</label>
                        <input type="number" name="survival_sample" class="form-control" id="editSurvivalSample"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Feeding Weight</label>
                        <input type="number" name="feeding_weight" class="form-control" id="editFeedingWeight"
                            step="0.01" required>
                    </div>
                    <!-- Day is hidden here, so users cannot modify it -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="updateEntryData()">Save Changes</button>
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

<?php include('./constant/layout/footer.php'); ?>
<!-- flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        flatpickr("#filterDate", {
            dateFormat: "d-m-Y", // Định dạng hiển thị dd-MM-YYYY
            allowInput: true,    // Cho phép người dùng nhập thủ công
            defaultDate: null,   // Ngày mặc định (nếu có)
        });
    });
    document.addEventListener('wheel', function (event) {
        if (document.activeElement.type === 'number') {
            event.preventDefault();
        }
    }, { passive: false });
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
    function editEntryData(entryId) {
        $.ajax({
            url: 'php_action/get_entry_data.php',
            type: 'POST',
            data: { entry_data_id: entryId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#editEntryId').val(response.data.entry_data_id);
                    $('#editTreatmentName').val(response.data.treatment_name);
                    $('#editProductApplication').val(response.data.product_application);
                    $('#editSurvivalSample').val(response.data.survival_sample);
                    $('#editFeedingWeight').val(response.data.feeding_weight);
                    $('#editRep').val(response.data.rep); // Điền giá trị rep
                    $('#editLabDay').val(formatToDDMMYYYY(response.data.lab_day)); // Định dạng dd-mm-yyyy
                    $('#editDataModal').modal('show');
                } else {
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
    // Load lại dữ liệu khi chỉnh sửa hoặc xóa
    function reloadFilteredData() {
        const filterDate = document.getElementById('filterDate').value;

        if (filterDate) {
            // Chuyển đổi định dạng từ dd-MM-YYYY sang YYYY-MM-DD
            const formattedDate = filterDate.split('-').reverse().join('-');

            // Nếu có filter date, cập nhật dữ liệu đã lọc
            $.ajax({
                url: './php_action/filter_entry_data.php',
                type: 'POST',
                data: {
                    case_study_id: '<?php echo $caseStudyId; ?>',
                    filterDate: formattedDate // Gửi ngày với định dạng chuẩn YYYY-MM-DD
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        renderPhases(response.data);
                    } else {
                        showToast(response.message || 'No data found!', 'Error', false);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        } else {
            // Nếu không có filter date, reload lại toàn bộ trang
            location.reload();
        }
    }



    // Cập nhật dữ liệu
    // Cập nhật dữ liệu
    function updateEntryData() {
        const formData = $('#editDataForm').serializeArray();

        $.ajax({
            url: 'php_action/edit_entry_data.php',
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Entry updated successfully!', 'Success', true);
                    $('#editDataModal').modal('hide');
                    reloadFilteredData(); // Kiểm tra và hành động theo trạng thái filterDate
                } else {
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Xóa dữ liệu
    function deleteEntryData(entryId) {
        if (confirm('Are you sure you want to delete this entry?')) {
            $.ajax({
                url: 'php_action/remove_entry_data.php',
                type: 'POST',
                data: { entry_data_id: entryId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Entry deleted successfully!', 'Success', true);
                        reloadFilteredData(); // Kiểm tra và hành động theo trạng thái filterDate
                    } else {
                        showToast(response.messages, 'Error', false);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
    }



    function resetTableFilter() {
        document.getElementById('filterDate').value = ''; // Xóa giá trị ngày
        showToast('Reset successfully!', 'Success', true);
        setTimeout(() => {
            location.reload();;
        }, 1000);
    }
    function formatToDDMMYYYY(dateString) {
        const parts = dateString.split('-'); // Giả định dateString là YYYY-MM-DD
        if (parts.length === 3) {
            const [year, month, day] = parts;
            return `${day}-${month}-${year}`; // Chuyển đổi sang DD-MM-YYYY
        }
        return dateString; // Nếu không phải định dạng YYYY-MM-DD, trả về nguyên bản
    }

    function formatToYYYYMMDD(dateString) {
        const parts = dateString.split('-'); // Giả định dateString là DD-MM-YYYY
        if (parts.length === 3) {
            const [day, month, year] = parts;
            return `${year}-${month}-${day}`; // Chuyển đổi sang YYYY-MM-DD
        }
        return dateString; // Nếu không phải định dạng DD-MM-YYYY, trả về nguyên bản
    }

    function applyDateFilter() {
        const filterDateInput = document.getElementById('filterDate');
        const filterDateValue = filterDateInput.value;

        // Chuyển đổi sang YYYY-MM-DD
        const formattedDate = filterDateValue.split('-').reverse().join('-');

        $.ajax({
            url: 'php_action/filter_entry_data.php',
            type: 'POST',
            data: {
                case_study_id: '<?php echo $caseStudyId; ?>',
                filterDate: formattedDate
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderPhases(response.data); // Hiển thị dữ liệu sau khi nhận
                    showToast('Filter applied successfully!', 'Success', true);
                } else {
                    showToast(response.message || 'No data found!', 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }




    function renderPhases(groupedEntries) {
        const container = document.querySelector('.container-fluid .card-body');
        container.innerHTML = ''; // Xóa nội dung cũ trước khi hiển thị dữ liệu mới

        groupedEntries.forEach(group => {
            const startDateFormatted = formatToDDMMYYYY(group.start_date);
            const endDateFormatted = formatToDDMMYYYY(group.end_date);

            const phaseTitle = `
        <h5 class="phase-title" style="text-align: center;background-color:#A8CD89;font-size: 1.5em; color: black; font-weight: bold;">
            ${group.phase} (${startDateFormatted} to ${endDateFormatted})
        </h5>
        `;

            const sortedEntries = [...group.entries].sort((a, b) => {
                if (a.lab_day !== b.lab_day) {
                    return new Date(a.lab_day) - new Date(b.lab_day);
                }
                if (a.treatment_name !== b.treatment_name) {
                    return a.treatment_name.localeCompare(b.treatment_name);
                }
                return a.rep - b.rep;
            });

            let tableContent = '';
            let currentDay = null;
            let currentTreatment = null;
            let displayedProducts = new Map(); // Lưu các product_application đã hiển thị cho mỗi treatment

            sortedEntries.forEach((entry, index) => {
                const isNewDay = currentDay !== entry.lab_day;
                const isNewTreatment = currentTreatment !== entry.treatment_name;
                const productKey = `${entry.treatment_name}_${entry.product_application}`;

                if (isNewDay) {
                    if (currentDay !== null) {
                        tableContent += `</tbody></table></div>`;
                    }

                    currentDay = entry.lab_day;
                    currentTreatment = null;
                    displayedProducts.clear(); // Xóa bộ nhớ khi sang ngày mới

                    tableContent += `
                <h6 class="day-title" style="text-align: center; background-color: #D8E6F3; font-size: 1.2em; color: #333; padding: 5px; margin-top: 20px;">
                    Day: ${formatToDDMMYYYY(currentDay)}
                </h6>
                <div class="table-responsive m-t-20">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Treatment Name</th>
                                <th style="width: 200px;">Product Application</th>
                                <th style="width: 50px;">Reps</th>
                                <th style="text-align: center">Survival Sample</th>
                                <th style="text-align: center">Feeding Weight</th>
                                <th style="text-align: center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                }

                if (isNewTreatment) {
                    currentTreatment = entry.treatment_name;
                }

                // Tính toán rowspan
                const treatmentRowCount = sortedEntries.filter(
                    e => e.lab_day === currentDay && e.treatment_name === currentTreatment
                ).length;

                const productRowCount = sortedEntries.filter(
                    e =>
                        e.lab_day === currentDay &&
                        e.treatment_name === currentTreatment &&
                        e.product_application === entry.product_application
                ).length;

                // Kiểm tra và hiển thị `product_application` chỉ khi chưa được hiển thị cho treatment hiện tại
                const isNewProductApplication = !displayedProducts.has(productKey);

                if (isNewProductApplication) {
                    displayedProducts.set(productKey, true); // Đánh dấu product_application đã hiển thị
                }

                // Hiển thị hàng
                tableContent += `
            <tr>
                ${isNewTreatment ? `
                    <td rowspan="${treatmentRowCount}" style="vertical-align: middle; font-weight: bold;">
                        ${currentTreatment}
                    </td>
                ` : ''}
                ${isNewProductApplication ? `
                    <td rowspan="${productRowCount}" style="vertical-align: middle;">
                        ${entry.product_application}
                    </td>
                ` : ''}
                <td>${entry.rep}</td>
                <td>${entry.survival_sample}</td>
                <td>${entry.feeding_weight}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-warning btn-sm" onclick="editEntryData(${entry.entry_data_id || 0})">
                            <i class="fa fa-pencil">Edit</i> 
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteEntryData(${entry.entry_data_id || 0})">
                            <i class="fa fa-trash">Delete</i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
            });

            if (currentDay !== null) {
                tableContent += `</tbody></table></div>`;
            }

            container.innerHTML += phaseTitle + tableContent;
        });
    }




    function formatToDDMMYYYY(dateString) {
        const parts = dateString.split('-'); // Giả định dateString là YYYY-MM-DD
        if (parts.length === 3) {
            const [year, month, day] = parts;
            return `${day}-${month}-${year}`; // Chuyển đổi sang DD-MM-YYYY
        }
        return dateString; // Nếu không phải định dạng YYYY-MM-DD, trả về nguyên bản
    }


</script>
<style>
    .action-buttons {
        display: flex;
        gap: 20px;
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