<?php 
include('./constant/layout/head.php'); 
include('./constant/layout/header.php'); 
include('./constant/layout/sidebar.php');
include('./constant/layout/footer.php'); 
include('./constant/connect.php'); 
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;

// Kiểm tra biến caseStudyId đã được lấy đúng không
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
             WHERE cg.category_id = '$categoryId'";
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
                                            <a href="entry_data.php?case_study_id=<?php echo htmlspecialchars($caseStudyId); ?>&group_id=<?php echo htmlspecialchars($group['group_id']); ?>">
                                                <?php echo htmlspecialchars($group['group_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary" data-toggle="modal" data-target="#addDataModal" onclick="openAddDataModal('<?php echo htmlspecialchars($caseStudyId); ?>', '<?php echo htmlspecialchars($group['group_id']); ?>', '<?php echo htmlspecialchars($group['group_name']); ?>')">
                                                Add Data
                                            </button>
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

<!-- Add Data Modal -->
<div id="addDataModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <form id="addDataForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Entry Data for Group: <span id="modalGroupName"></span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="case_study_id" id="modalCaseStudyId">
                    <input type="hidden" name="group_id" id="modalGroupId">

                    <div class="form-group">
                        <label>Treatment Name</label>
                        <select name="treatment_name" class="form-control" required onchange="updateProductApplication()">
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
                        <input type="text" name="product_application" class="form-control" id="productApplication" readonly required>
                    </div>
                    <div class="form-group">
                        <label>Survival Sample</label>
                        <input type="number" name="survival_sample" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Feeding Weight</label>
                        <input type="number" step="0.01" name="feeding_weight" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Day (DD/MM/YYYY)</label>
                        <input type="text" name="lab_day" class="form-control datepicker" required>
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

<!-- Include Bootstrap Datepicker 
 <!-- <script src="assets/js/lib/jquery/jquery.min.js"></script> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>  
 <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />  -->
    <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>   -->
<script>
// Initialize Bootstrap Datepicker for date fields

    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });

    function openAddDataModal(caseStudyId, groupId, groupName) {
        $('#modalCaseStudyId').val(caseStudyId);
        $('#modalGroupId').val(groupId);
        $('#modalGroupName').text(groupName);
    }
// Update Product Application based on Treatment Name selection
function updateProductApplication() {
    const treatment = $('select[name="treatment_name"]').val();
    let productApplication = "";

    switch (treatment) {
        case "Negative control":
        case "Positive control":
            productApplication = "0";
            break;
        case "Treatment 1":
            productApplication = "1,000 ppm(Prototype 13A)";
            break;
        case "Treatment 2":
            productApplication = "2,000 ppm(Prototype 13A)";
            break;
        case "Treatment 3":
            productApplication = "1,000 ppm(AviPlus Aqua)";
            break;
        case "Treatment 4":
            productApplication = "2,000 ppm(AviPlus Aqua)";
            break;
    }

    $('#productApplication').val(productApplication);
}

// Submit entry data using AJAX
function submitEntryData() {
    // Convert lab_day từ dd/mm/yyyy sang yyyy-mm-dd
    const labDay = $('input[name="lab_day"]').val().split('/').reverse().join('-');
    $('input[name="lab_day"]').val(labDay);

    const formData = $('#addDataForm').serialize();

    // Kiểm tra giá trị formData trước khi gửi
    console.log("Form Data:", formData);

    $.ajax({
        url: 'php_action/add_entry_data.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Entry data added successfully!');
                $('#addDataForm')[0].reset();
                $('.btn-close-modal').click();
            } else {
                alert(response.messages);  // Hiển thị thông báo lỗi chính xác
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

</script>
