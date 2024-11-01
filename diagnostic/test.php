<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>   
<?php include('./constant/connect.php'); 

$sql = "SELECT case_study_id, case_name, location, start_date, end_date, categories_id, status, rep_number FROM case_study";
$result = $connect->query($sql);

?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary"> View Case Studies</h3> 
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">View Case Studies</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <a href="add-case_study.php"><button class="btn btn-primary">Add Case Study</button></a>
                
                <div class="table-responsive m-t-40">
                    <table id="myTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case Study ID</th>
                                <th>Case Study Name</th>
                                <th>Location</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($result as $row) {
                                // Lấy tên của category từ bảng categories
                                $sql = "SELECT categories_name FROM categories WHERE categories_id='" . $row['categories_id'] . "'";
                                $result2 = $connect->query($sql);
                                $row2 = $result2->fetch_assoc();

                                // Thiết lập màu sắc cho case_study_id và nhãn trạng thái
                                $caseStudyIdClass = "";
                                $statusLabel = "";

                                if ($row['status'] == "Prepare") {
                                    $caseStudyIdClass = "text-info"; // Màu nhãn xám (label-info)
                                    $statusLabel = "<label class='label label-info'><h4>Prepare</h4></label>";
                                } elseif ($row['status'] == "In-process") {
                                    $caseStudyIdClass = "text-warning"; // Màu nhãn xanh lá cây (label-warning)
                                    $statusLabel = "<label class='label label-warning'><h4>In-process</h4></label>";
                                } else {
                                    $caseStudyIdClass = "text-success"; // Màu nhãn xanh dương (label-success)
                                    $statusLabel = "<label class='label label-success'><h4>Complete</h4></label>";
                                }
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td class="<?php echo $caseStudyIdClass; ?>"><?php echo $row['case_study_id']; ?></td>
                                <td><?php echo $row['case_name']; ?></td>
                                <td><?php echo $row['location']; ?></td>
                                <td><?php echo $row['start_date']; ?></td>
                                <td><?php echo $row['end_date']; ?></td>
                                <td><?php echo $row2['categories_name']; ?></td>
                                <td><?php echo $statusLabel; ?></td>
                                <td>
                                    <a href="edit-case_study.php?id=<?php echo $row['case_study_id']; ?>">
                                        <button type="button" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></button>
                                    </a>
                                    <a href="php_action/removeCaseStudy.php?id=<?php echo $row['case_study_id']; ?>" >
                                        <button type="button" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                $i++;   
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include('./constant/layout/footer.php'); ?>
