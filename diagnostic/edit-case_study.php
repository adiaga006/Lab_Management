<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>   
<?php include('./constant/connect.php');

// Lấy dữ liệu của case study từ database
$sql = "SELECT * FROM case_study WHERE case_study_id='" . $_GET['id'] . "'";
$result = $connect->query($sql)->fetch_assoc();  
?> 

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Edit Case Study Management</h3> 
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Edit Case Study</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-body">
                        <div class="input-states">
                            <!-- Form cập nhật thông tin case study -->
                            <form class="form-horizontal" method="POST" id="submitCaseStudyForm" action="php_action/editCaseStudy.php?id=<?php echo $_GET['id']; ?>" enctype="multipart/form-data">
                                <fieldset>
                                    <h1>Case Study Info</h1>

                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Case Study Name</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editCaseStudyName" value="<?php echo $result['case_name']; ?>" name="editCaseStudyName" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Location</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editLocation" value="<?php echo $result['location']; ?>" name="editLocation" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                     <!-- Start Date -->
                                     <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Start Date</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editStartDate" value="<?php echo date('d-m-Y', strtotime($result['start_date'])); ?>" name="editStartDate" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Category</label>
                                            <div class="col-sm-9">
                                                <select id="editCategoryName" name="editCategoryName" required class="form-control">
                                                    <?php
                                                        $sql = "SELECT * FROM categories WHERE categories_status=1";
                                                        $categoriesResult = mysqli_query($connect, $sql);
                                                        while ($row = mysqli_fetch_assoc($categoriesResult)) {
                                                    ?>
                                                    <option value="<?php echo $row['categories_id']; ?>"<?php if($result['categories_id'] == $row['categories_id']) echo "selected"; ?>>
                                                        <?php echo $row['categories_name']; ?>
                                                    </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Status</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="editCaseStudyStatus" name="editCaseStudyStatus">
                                                    <option value="Prepare" <?php if($result['status'] == "Prepare") echo "selected"; ?>>Prepare</option>
                                                    <option value="In-process" <?php if($result['status'] == "In-process") echo "selected"; ?>>In-process</option>
                                                    <option value="Complete" <?php if($result['status'] == "Complete") echo "selected"; ?>>Complete</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="update" id="updateCaseStudyBtn" class="btn btn-primary btn-flat m-b-30 m-t-30">Update</button>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Thêm Flatpickr CSS và JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // Cấu hình Flatpickr để hiển thị theo định dạng DD-MM-YYYY cho các trường ngày
    flatpickr("#editStartDate", {
        dateFormat: "d-m-Y",  // Định dạng hiển thị DD-MM-YYYY
        altInput: true,
        altFormat: "d-m-Y"
    });
</script>
<?php include('./constant/layout/footer.php'); ?>
