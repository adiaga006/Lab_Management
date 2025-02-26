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

                <div class="card mb-3">
                    <div class="card-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-3">
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php
                                    $catSql = "SELECT DISTINCT category_name FROM case_study WHERE user_id = ?";
                                    $stmt = $connect->prepare($catSql);
                                    $stmt->bind_param("i", $_SESSION['userId']);
                                    $stmt->execute();
                                    $catResult = $stmt->get_result();
                                    while ($row = $catResult->fetch_assoc()) {
                                        echo '<option value="'.$row['category_name'].'">'.ucwords(strtolower($row['category_name'])).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="monthFilter" class="form-select">
                                    <option value="">All Months</option>
                                    <?php
                                    for ($m = 1; $m <= 12; $m++) {
                                        echo '<option value="'.$m.'">'.date('F', mktime(0, 0, 0, $m, 1)).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="yearFilter" class="form-select">
                                    <option value="">All Years</option>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                        echo '<option value="'.$y.'">'.$y.'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Prepare">Prepare</option>
                                    <option value="In-process">In-process</option>
                                    <option value="Complete">Complete</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="resetFilter" class="btn btn-secondary w-100">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="myTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case Study ID</th>
                                <th>Case Study Name</th>
                                <th>Location</th>
                                <th>Start Date</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT cs.*, c.categories_name 
                                    FROM case_study cs 
                                    LEFT JOIN categories c ON cs.categories_id = c.categories_id 
                                    WHERE cs.user_id = ?";
                            $stmt = $connect->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['userId']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $i = 1;
                            while ($row = $result->fetch_assoc()) {
                                $statusBadge = match($row['status']) {
                                    'Prepare' => '<span class="badge bg-info">Prepare</span>',
                                    'In-process' => '<span class="badge bg-warning">In-process</span>',
                                    'Complete' => '<span class="badge bg-success">Complete</span>',
                                    default => '<span class="badge bg-secondary">Unknown</span>'
                                };
                                ?>
                                <tr data-category="<?php echo $row['category_name']; ?>">
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <a href="group.php?case_study_id=<?php echo $row['case_study_id']; ?>">
                                            <?php echo $row['case_study_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $row['case_name']; ?></td>
                                    <td><?php echo $row['location']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row['categories_name']; ?></td>
                                    <td><?php echo $statusBadge; ?></td>
                                    <td>
                                        <a href="edit-case_study.php?id=<?php echo $row['case_study_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $row['case_study_id']; ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
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

        .filter-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .filter-section select {
            min-width: 120px;
        }
        
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>

    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo DataTable
        var table = $('#myTable').DataTable({
            responsive: true,
            order: [[4, 'desc']],
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                search: "Search:",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Custom filtering function
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            var category = $('#categoryFilter').val();
            var month = $('#monthFilter').val();
            var year = $('#yearFilter').val();
            var status = $('#statusFilter').val();
            
            // Lấy row hiện tại và category_name từ data attribute
            var row = table.row(dataIndex).node();
            var rowCategory = $(row).data('category');
            var rowDate = data[4];      // Date column
            var rowStatus = $(data[6]).text().trim();  // Status column
            
            // Parse date
            var date = rowDate.split('-');
            var rowMonth = parseInt(date[1]);
            var rowYear = parseInt(date[2]);
            
            // Debug logs
            console.log('Filter Values:', {
                selectedCategory: category,
                rowCategory: rowCategory,
                month: month,
                rowMonth: rowMonth,
                year: year,
                rowYear: rowYear,
                status: status,
                rowStatus: rowStatus
            });
            
            // Check each filter
            var categoryMatch = !category || category === rowCategory;
            var monthMatch = !month || parseInt(month) === rowMonth;
            var yearMatch = !year || parseInt(year) === rowYear;
            var statusMatch = !status || status === rowStatus;
            
            return categoryMatch && monthMatch && yearMatch && statusMatch;
        });

        // Apply filters
        $('.form-select').change(function() {
            table.draw();
        });

        // Reset filters
        $('#resetFilter').click(function() {
            $('#filterForm')[0].reset();
            table.draw();
        });

        // Delete handling
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this case study?')) {
                $.ajax({
                    url: 'php_action/removeCaseStudy.php',
                    type: 'POST',
                    data: {id: id},
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error);
                        alert('Error occurred while deleting the data');
                    }
                });
            }
        });
    });
    </script>