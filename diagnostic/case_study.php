<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php');

$userId = $_SESSION['userId'];

$sql = "SELECT cs.*, c.categories_name 
        FROM case_study cs 
        LEFT JOIN categories c ON cs.categories_id = c.categories_id 
        WHERE cs.user_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
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
                    <table id="myTable" class="table table-bordered table-striped table-custom">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 15%;">Case Study ID</th>
                                <th style="width: 20%;">Case Study Name</th>
                                <th style="width: 22%;">Location</th>
                                <th style="width: 10%;">Start Date</th>
                                <th style="width: 10%;">Category</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 8%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = $result->fetch_assoc()) {
                                // Fetch category name from categories table
                                $sql = "SELECT categories_name FROM categories WHERE categories_id='" . $row['categories_id'] . "'";
                                $result2 = $connect->query($sql);
                                $row2 = $result2->fetch_assoc();

                                // Set color for case_study_id and status label
                                $caseStudyColor = "";
                                $statusLabel = "";

                                if ($row['status'] == "Prepare") {
                                    $caseStudyColor = "color: #17a2b8;"; // Info color
                                    $statusLabel = "<label class='label label-info'><h4>Prepare</h4></label>";
                                } elseif ($row['status'] == "In-process") {
                                    $caseStudyColor = "color: #ffc107;"; // Warning color
                                    $statusLabel = "<label class='label label-warning'><h4>In-process</h4></label>";
                                } else {
                                    $caseStudyColor = "color: #28a745;"; // Success color
                                    $statusLabel = "<label class='label label-success'><h4>Complete</h4></label>";
                                }
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <a href="group.php?case_study_id=<?php echo $row['case_study_id']; ?>"
                                            style="<?php echo $caseStudyColor; ?> text-decoration: none;">
                                            <?php echo $row['case_study_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $row['case_name']; ?></td>
                                    <td><?php echo $row['location']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row2['categories_name']; ?></td>
                                    <td><?php echo $statusLabel; ?></td>
                                    <td>
                                        <a href="edit-case_study.php?id=<?php echo $row['case_study_id']; ?>">
                                            <button type="button" class="btn btn-xs btn-primary"><i
                                                    class="fa fa-pencil"></i></button>
                                        </a>
                                        <a href="php_action/removeCaseStudy.php?id=<?php echo $row['case_study_id']; ?>">
                                            <button type="button" class="btn btn-xs btn-danger"
                                                onclick="return confirm('Are you sure to delete this record?')">
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

    <style>
        /* Add underline on hover for Case Study ID */
        a:hover {
            text-decoration: underline !important;
        }

        .table-custom th,
        .table-custom td {
            text-align: center;
            /* Căn giữa các cột */
            vertical-align: middle;
            /* Căn giữa nội dung theo chiều dọc */
        }
    </style>