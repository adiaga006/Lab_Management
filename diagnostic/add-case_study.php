<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Add Case Study</h3> 
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Add Case Study</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-body">
                        <!-- Thêm phần này để hiển thị thông báo -->
                        <div id="message" style="display: none;" class="alert"></div>

                        <div class="input-states">
                            <form class="form-horizontal" method="POST" id="submitCaseStudyForm" action="php_action/createCaseStudy.php" enctype="multipart/form-data">

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Case Study ID</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="caseStudyId" name="case_study_id" placeholder="Enter Case Study ID" required="" />
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Các phần khác của form (Case Name, Location, Category, v.v.) -->
                                                       
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Case Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="caseName" name="case_name" placeholder="Enter Case Name" required="" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Location</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="location" name="location" placeholder="Enter Location" required="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Category</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="categoryId" name="categories_id" required="">
                                                <option value="">~~SELECT~~</option>
                                                <?php 
                                                $sql = "SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 AND categories_active = 1";
                                                $result = $connect->query($sql);

                                                while($row = $result->fetch_array()) {
                                                    echo "<option value='".$row['categories_id']."'>".$row['categories_name']."</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Start Date</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="startDate" name="start_date" placeholder="YYYY-MM-DD" required="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">End Date</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="endDate" name="end_date" placeholder="YYYY-MM-DD" required="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Pond ID</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="pondId" name="pond_id" placeholder="Enter Pond ID" required="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Status</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="status" name="status" required="">
                                                <option value="">~~SELECT~~</option>
                                                <option value="Prepare">Prepare</option>
                                                <option value="In-process">In-process</option>
                                                <option value="Complete">Complete</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Repetition Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="repNumber" name="rep_number" placeholder="Enter Repetition Number" required="" pattern="^[0-9]+$" />
                                        </div>
                                    </div>
                                </div>

                                <button type="button" id="createCaseStudyBtn" class="btn btn-primary btn-flat m-b-30 m-t-30">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include('./constant/layout/footer.php'); ?>

<!-- JavaScript xử lý thông báo -->
<script>
document.getElementById('createCaseStudyBtn').addEventListener('click', function() {
    const form = document.getElementById('submitCaseStudyForm');
    const formData = new FormData(form);

    fetch('php_action/createCaseStudy.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('message');
        messageDiv.style.display = 'block';
        
        if (data.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.innerText = data.messages;
            form.reset(); // Xóa dữ liệu form sau khi thêm thành công
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerText = data.messages;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>
