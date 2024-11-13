<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'];

// Lấy `categories_id` từ bảng `case_study`
$sql = "SELECT categories_id, start_date FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $caseStudyId);
$stmt->execute();
$caseStudyResult = $stmt->get_result();
$caseStudy = $caseStudyResult->fetch_assoc();
$categoriesId = $caseStudy['categories_id'];
$startDate = $caseStudy['start_date'];
$stmt->close();

// Lấy `group_id` từ bảng `category_groups` dựa trên `categories_id`
$groupSql = "SELECT group_id FROM category_groups WHERE category_id = ?";
$stmt = $connect->prepare($groupSql);
$stmt->bind_param("i", $categoriesId);
$stmt->execute();
$groupResult = $stmt->get_result();
$groupData = $groupResult->fetch_assoc();
$groupId = $groupData['group_id'];
$stmt->close();

// Lấy `group_name` từ bảng `groups` dựa trên `group_id`
$groupNameSql = "SELECT group_name FROM groups WHERE group_id = ?";
$stmt = $connect->prepare($groupNameSql);
$stmt->bind_param("i", $groupId);
$stmt->execute();
$groupNameResult = $stmt->get_result();
$groupNameData = $groupNameResult->fetch_assoc();
$groupName = $groupNameData['group_name'];
$stmt->close();

// Định nghĩa các giai đoạn với ngày bắt đầu từ `start_date`
// Định nghĩa các giai đoạn với ngày bắt đầu từ `start_date`
function definePhases($startDate)
{
    $phases = [
        ["name" => "Acclimation period (2 days)", "days" => 2],
        ["name" => "Pre-challenge (21 days)", "days" => 21],
        ["name" => "No. of survival shrimp after immunology sampling (3 shrimp)", "days" => 1],
        ["name" => "EMS/AHPND challenge (1 day)", "days" => 1],
        ["name" => "Post-challenge (10 days)", "days" => 10]
    ];

    $currentDate = new DateTime($startDate);
    foreach ($phases as $index => &$phase) {
        // Thiết lập ngày bắt đầu của phase hiện tại
        $phase['start_date'] = $currentDate->format('Y-m-d');

        // Nếu phase tiếp theo là "No. of survival shrimp after immunology sampling" 
        // thì đặt ngày bắt đầu của nó trùng với ngày kết thúc của phase trước đó
        if ($phase['name'] === "No. of survival shrimp after immunology sampling (3 shrimp)") {
            $phase['start_date'] = $phases[$index - 1]['end_date'];
            $phase['end_date'] = $phase['start_date'];
        } else {
            // Tính ngày kết thúc của phase hiện tại
            $currentDate->modify("+{$phase['days']} days");
            $phase['end_date'] = $currentDate->modify("-1 day")->format('Y-m-d');
            $currentDate->modify("+1 day"); // Ngày bắt đầu cho phase tiếp theo
        }
    }

    return $phases;
}

$phases = $startDate ? definePhases($startDate) : [];

// Truy vấn dữ liệu `entry_data` theo `case_study_id`
$entrySql = "SELECT * FROM entry_data WHERE case_study_id = ?
                ORDER BY 
                CASE
                    WHEN treatment_name = 'Negative control' THEN 1
                    WHEN treatment_name = 'Positive control' THEN 2
                    WHEN treatment_name = 'Treatment 1' THEN 3
                    WHEN treatment_name = 'Treatment 2' THEN 4
                    WHEN treatment_name = 'Treatment 3' THEN 5
                    WHEN treatment_name = 'Treatment 4' THEN 6
                    ELSE 7
                END,
                lab_day ASC, 
                created_at ASC";
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
        $phaseEntries = array_filter($entries, function ($entry) use ($phase) {
            return $entry['lab_day'] >= $phase['start_date'] && $entry['lab_day'] <= $phase['end_date'];
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
                Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?> - Group:
                <?php echo htmlspecialchars($groupName); ?>
            </h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4>Entries for Group: <?php echo htmlspecialchars($groupName); ?></h4>

                <?php foreach ($groupedEntries as $grouped): ?>
                    <h5 style="font-size: 1.5em; color: black; font-weight: bold;">
                        <?php echo htmlspecialchars($grouped['phase']['name']); ?>
                        (<?php echo date('d-m-Y', strtotime($grouped['phase']['start_date'])); ?> to
                        <?php echo date('d-m-Y', strtotime($grouped['phase']['end_date'])); ?>)
                    </h5>

                    <?php if (count($grouped['entries']) > 0): ?>
                        <div class="table-responsive m-t-20">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Treatment Name</th>
                                        <th style="width: 200px;">Product Application</th>
                                        <th>Day</th>
                                        <th>Reps</th>
                                        <th>Survival Sample</th>
                                        <th>Feeding Weight</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $currentTreatment = '';
                                    $currentDate = '';
                                    $repCount = 1;

                                    foreach ($grouped['entries'] as $entry) {
                                        $isNewTreatment = $currentTreatment !== $entry['treatment_name'];
                                        $isNewDay = $currentDate !== $entry['lab_day'];

                                        if ($isNewTreatment) {
                                            $currentTreatment = $entry['treatment_name'];
                                            $currentDate = $entry['lab_day'];
                                            $repCount = 1;
                                        } elseif ($isNewDay) {
                                            $currentDate = $entry['lab_day'];
                                            $repCount = 1;
                                        }

                                        echo "<tr>";

                                        if ($isNewTreatment) {
                                            $treatmentRowCount = count(array_filter($grouped['entries'], function ($e) use ($currentTreatment) {
                                                return $e['treatment_name'] === $currentTreatment;
                                            }));
                                            echo "<td rowspan='{$treatmentRowCount}' style='vertical-align: middle; font-weight: bold;'>{$entry['treatment_name']}</td>";
                                            echo "<td rowspan='{$treatmentRowCount}' style='vertical-align: middle; width: 200px;'>{$entry['product_application']}</td>";
                                        }

                                        if ($isNewDay || $isNewTreatment) {
                                            $dayRowCount = count(array_filter($grouped['entries'], function ($e) use ($currentDate, $currentTreatment) {
                                                return $e['lab_day'] === $currentDate && $e['treatment_name'] === $currentTreatment;
                                            }));
                                            echo "<td rowspan='{$dayRowCount}' style='vertical-align: middle;'>" . date('d-m-Y', strtotime($entry['lab_day'])) . "</td>";
                                        } else {
                                            echo "<td style='display: none;'></td>";
                                        }

                                        echo "<td>{$repCount}</td>";
                                        echo "<td>{$entry['survival_sample']}</td>";
                                        echo "<td>{$entry['feeding_weight']}</td>";

                                        echo "<td>
                                                <button class='btn btn-warning btn-sm' onclick='editEntryData({$entry['entry_data_id']})'>
                                                    <i class='fa fa-pencil'></i>
                                                </button>
                                                <button class='btn btn-danger btn-sm' onclick='deleteEntryData({$entry['entry_data_id']})'>
                                                    <i class='fa fa-trash'></i>
                                                </button>
                                            </td>";

                                        echo "</tr>";

                                        $repCount++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No entries for this phase.</p>
                    <?php endif; ?>
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
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php include('./constant/layout/footer.php'); ?>

<!-- Include jQuery, Bootstrap, and Bootstrap Datepicker -->
<script src="assets/js/lib/jquery/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
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
                    $('#editDataModal').modal('show');
                } else {
                    alert('Error fetching entry data.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Function to submit updated entry data
    function updateEntryData() {
        const formData = $('#editDataForm').serializeArray();

        // Lab_day is not sent in the request
        $.ajax({
            url: 'php_action/edit_entry_data.php',
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Entry updated successfully!');
                    $('#editDataModal').modal('hide');
                    location.reload();
                } else {
                    alert('Failed to update entry.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Delete entry data
    function deleteEntryData(entryId) {
        if (confirm('Are you sure you want to delete this entry?')) {
            $.ajax({
                url: 'php_action/remove_entry_data.php',
                type: 'POST',
                data: { entry_data_id: entryId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Entry deleted successfully!');
                        location.reload();
                    } else {
                        alert('Failed to delete entry.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
    }
</script>