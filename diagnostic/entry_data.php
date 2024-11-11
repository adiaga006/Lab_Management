<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'];
$groupId = $_GET['group_id'];

// Fetch group name
$sql = "SELECT group_name FROM groups WHERE group_id = '$groupId'";
$groupResult = $connect->query($sql);
$group = $groupResult->fetch_assoc();

// Fetch case study start date
$caseStudySql = "SELECT start_date FROM case_study WHERE case_study_id = '$caseStudyId'";
$caseStudyResult = $connect->query($caseStudySql);
$caseStudy = $caseStudyResult->fetch_assoc();
$startDate = $caseStudy ? $caseStudy['start_date'] : null;

// Define phases with correct date ranges
function definePhases($startDate) {
    $phases = [
        ["name" => "Acclimation period (2 days)", "days" => 2],
        ["name" => "Pre-challenge (21 days)", "days" => 21],
        ["name"=> " No. of survival shrimp after immunology sampling (3 shrimp)", ""=> 1],
        ["name" => "EMS/AHPND challenge (1 day)", "days" => 1],
        ["name" => "Post-challenge (10 days)", "days" => 10]
    ];

    $currentDate = new DateTime($startDate);
    foreach ($phases as &$phase) {
        $phase['start_date'] = $currentDate->format('Y-m-d');
        $currentDate->modify("+{$phase['days']} days");
        $phase['end_date'] = $currentDate->modify("-1 day")->format('Y-m-d'); // End date is the day before
        $currentDate->modify("+1 day"); // Move to the next phase's start date
    }

    // Adjust the "No. of survival shrimp after immunology sampling" phase to overlap with the end of Pre-challenge
    $phases[2]['start_date'] = $phases[1]['end_date'];
    $phases[2]['end_date'] = $phases[2]['start_date']; // Single-day phase

    return $phases;
}

$phases = $startDate ? definePhases($startDate) : [];

// Fetch and organize entry data by date
$entrySql = "SELECT * FROM entry_data WHERE case_study_id = '$caseStudyId' AND group_id = '$groupId'
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
$entryResult = $connect->query($entrySql);
$entries = [];
while ($entry = $entryResult->fetch_assoc()) {
    $entries[] = $entry;
}

// Group entries by phases
function groupEntriesByPhase($entries, $phases) {
    $grouped = [];
    foreach ($phases as $phase) {
        $phaseEntries = array_filter($entries, function($entry) use ($phase) {
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
                Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?> - Group: <?php echo htmlspecialchars($group['group_name']); ?>
            </h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4>Entries for Group: <?php echo htmlspecialchars($group['group_name']); ?></h4>

                <?php foreach ($groupedEntries as $grouped): ?>
                    <h5 style="font-size: 1.5em; color: black; font-weight: bold;">
                        <?php echo htmlspecialchars($grouped['phase']['name']); ?>
                        (<?php echo date('d-m-Y', strtotime($grouped['phase']['start_date'])); ?> to <?php echo date('d-m-Y', strtotime($grouped['phase']['end_date'])); ?>)
                    </h5>

                    <?php if (count($grouped['entries']) > 0): ?>
                        <div class="table-responsive m-t-20">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Treatment Name</th>
                                        <th>Day</th>
                                        <th>Reps</th>
                                        <th>Product Application</th>
                                        <th>Survival Sample</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Biến theo dõi treatment và ngày hiện tại
                                    $currentTreatment = '';
                                    $currentDate = '';

                                    foreach ($grouped['entries'] as $entry) {
                                        $isNewTreatment = $currentTreatment !== $entry['treatment_name'];
                                        $isNewDay = $currentDate !== $entry['lab_day'];

                                        // Nếu là treatment mới, thiết lập lại biến $currentTreatment và tính rowspan
                                        if ($isNewTreatment) {
                                            $currentTreatment = $entry['treatment_name'];
                                            // Đếm số hàng cho treatment hiện tại để tính rowspan
                                            $treatmentRowCount = count(array_filter($grouped['entries'], function($e) use ($currentTreatment) {
                                                return $e['treatment_name'] === $currentTreatment;
                                            }));
                                        }

                                        // Nếu là ngày mới trong treatment hiện tại, thiết lập lại biến $currentDate và tính rowspan cho ngày
                                        if ($isNewDay) {
                                            $currentDate = $entry['lab_day'];
                                            // Đếm số hàng cho ngày hiện tại trong treatment hiện tại
                                            $dayRowCount = count(array_filter($grouped['entries'], function($e) use ($currentDate, $currentTreatment) {
                                                return $e['lab_day'] === $currentDate && $e['treatment_name'] === $currentTreatment;
                                            }));
                                            $repCount = 1; // Khởi tạo lại số lần thử nghiệm cho ngày mới
                                        } else {
                                            $repCount++; // Tăng số lần thử nghiệm nếu cùng ngày và cùng treatment
                                        }

                                        echo "<tr>";

                                        // Chỉ hiển thị treatment name khi là treatment mới, với rowspan đã tính toán trước đó
                                        if ($isNewTreatment) {
                                            echo "<td rowspan='{$treatmentRowCount}' style='vertical-align: middle; font-weight: bold;'>{$entry['treatment_name']}</td>";
                                        }

                                        // Chỉ hiển thị ngày khi là ngày mới, với rowspan đã tính toán cho ngày đó
                                        if ($isNewDay) {
                                            echo "<td rowspan='{$dayRowCount}' style='vertical-align: middle;'>" . date('d-m-Y', strtotime($entry['lab_day'])) . "</td>";
                                        }

                                        // Cột reps, sẽ hiển thị cho mỗi hàng trong cùng ngày
                                        echo "<td>{$repCount}</td>";

                                        // Chỉ hiển thị product application khi là treatment mới
                                        if ($isNewTreatment) {
                                            echo "<td rowspan='{$treatmentRowCount}' style='vertical-align: middle;'>{$entry['product_application']}</td>";
                                        }

                                        // Cột survival sample
                                        echo "<td>{$entry['survival_sample']}</td>";

                                        // Cột action với các nút
                                        echo "<td>
                                            <button class='btn btn-warning btn-sm' onclick='editEntryData({$entry['entry_data_id']})'>
                                                <i class='fa fa-pencil'></i>
                                            </button>
                                            <button class='btn btn-danger btn-sm' onclick='deleteEntryData({$entry['entry_data_id']})'>
                                                <i class='fa fa-trash'></i>
                                            </button>
                                        </td>";

                                        echo "</tr>";
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
                        <input type="text" name="product_application" class="form-control" id="editProductApplication" readonly>
                    </div>

                    <div class="form-group">
                        <label>Survival Sample</label>
                        <input type="number" name="survival_sample" class="form-control" id="editSurvivalSample" required>
                    </div>

                    <div class="form-group">
                        <label>Day</label>
                        <input type="text" name="lab_day" class="form-control datepicker" id="editLabDay" required maxlength="10">
                    </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />

<script>
// Initialize Datepicker for dd-mm-yyyy format
$('.datepicker').datepicker({
    format: 'dd-mm-yyyy',
    autoclose: true,
    todayHighlight: true
});

// Open the Edit Modal and populate data
function editEntryData(entryId) {
    console.log("Opening edit modal for entry ID:", entryId);  // Debugging line

    $.ajax({
        url: 'php_action/get_entry_data.php',
        type: 'POST',
        data: { entry_data_id: entryId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editEntryId').val(response.data.entry_data_id);
                $('#editTreatmentName').val(response.data.treatment_name);
                $('#editProductApplication').val(response.data.product_application);
                $('#editSurvivalSample').val(response.data.survival_sample);

                // Convert date format to dd-mm-yyyy for display
                const formattedDate = new Date(response.data.lab_day).toLocaleDateString('en-GB');
                $('#editLabDay').val(formattedDate);

                $('#editDataModal').modal('show'); // Show the modal
            } else {
                alert('Error fetching entry data.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Function to submit updated entry data
function updateEntryData() {
    const labDay = $('#editLabDay').val().split('-').reverse().join('-'); // Convert to yyyy-mm-dd
    $('#editLabDay').val(labDay);

    const formData = $('#editDataForm').serialize();

    $.ajax({
        url: 'php_action/edit_entry_data.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Entry updated successfully!');
                $('#editDataModal').modal('hide');
                location.reload();
            } else {
                alert('Failed to update entry.');
            }
        },
        error: function(xhr, status, error) {
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
            success: function(response) {
                if (response.success) {
                    alert('Entry deleted successfully!');
                    location.reload();
                } else {
                    alert('Failed to delete entry.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
}
</script>