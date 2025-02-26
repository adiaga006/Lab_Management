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
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">View Case Studies</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="action-buttons">
                    <a href="add-case_study.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Case Study
                    </a>
                    <button id="toggleFilter" class="btn btn-secondary">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
                </div>

                <div id="filterSection" class="filter-section" style="display: none;">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-folder filter-icon"></i>
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All types of Case Studies</option>
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
                        </div>
                        <div class="col-md-2">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-calendar filter-icon"></i>
                                <select id="monthFilter" class="form-select">
                                    <option value="">All Months</option>
                                    <?php
                                    for ($m = 1; $m <= 12; $m++) {
                                        echo '<option value="'.$m.'">'.date('F', mktime(0, 0, 0, $m, 1)).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-calendar-alt filter-icon"></i>
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
                        </div>
                        <div class="col-md-3">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-tasks filter-icon"></i>
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Prepare">Prepare</option>
                                    <option value="In-process">In-process</option>
                                    <option value="Complete">Complete</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="resetFilter" class="btn btn-secondary w-100">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="myTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Case Study ID</th>
                                <th>Case Study Name</th>
                                <th class="location-column">Location</th>
                                <th>Start Date</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="action-column">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = $result->fetch_assoc()) {
                                $statusBadge = match($row['status']) {
                                    'Prepare' => '<span class="badge bg-info">Prepare</span>',
                                    'In-process' => '<span class="badge bg-warning">In-process</span>',
                                    'Complete' => '<span class="badge bg-success">Complete</span>',
                                    default => '<span class="badge bg-secondary">Unknown</span>'
                                };
                                ?>
                                <tr onclick="window.location='group.php?case_study_id=<?php echo $row['case_study_id']; ?>'" 
                                    style="cursor: pointer;"
                                    data-category="<?php echo $row['category_name']; ?>">
                                    <td>
                                        <span class="case-study-link"><?php echo $row['case_study_id']; ?></span>
                                    </td>
                                    <td><?php echo $row['case_name']; ?></td>
                                    <td class="location-column" title="<?php echo $row['location']; ?>"><?php echo $row['location']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row['categories_name']; ?></td>
                                    <td><?php echo $statusBadge; ?></td>
                                    <td class="action-column">
                                        <a href="edit-case_study.php?id=<?php echo $row['case_study_id']; ?>" 
                                           class="btn btn-primary btn-sm btn-action" 
                                           onclick="event.stopPropagation();">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm btn-action btn-delete" 
                                                data-id="<?php echo $row['case_study_id']; ?>" 
                                                onclick="event.stopPropagation();">
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
        /* Loại bỏ margin của page-titles */
        .row.page-titles {
            margin: 0;
            padding: 1rem;
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        /* Điều chỉnh container-fluid */
        .container-fluid {
            padding-top: 0;
            margin-top: 0;
        }

        /* Điều chỉnh breadcrumb */
        .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
        }

        /* Text styling */
        .text-primary {
            margin: 0;
        }

        /* Breadcrumb links */
        .breadcrumb-item a:hover {
            color: #4e73df;
        }

        .breadcrumb-item.active {
            color: #5a5c69;
        }

        /* Main container styling */
        .container-fluid {
            padding: 20px;
        }

        /* Card styling */
        .card {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .card-body {
            padding: 25px;
        }

        /* Button group styling */
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #4e73df;
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.2);
        }

        .btn-secondary {
            background: #f8f9fa;
            border: 1px solid #e3e6f0;
            color: #5a5c69;
        }

        .btn-secondary:hover {
            background: #e2e6ea;
            transform: translateY(-2px);
        }

        /* Table text color */
        .table tbody td {
            color: #2f2f2f;  /* Đậm hơn */
        }

        /* Case Study ID color */
        .case-study-link {
            color: #28a745 !important;  /* Màu xanh lá */
            text-decoration: none;
            font-weight: 500;
        }

        /* Filter section styling */
        .filter-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Form select với icons */
        .form-select, .btn-secondary {
            border-radius: 20px !important;
            padding: 8px 20px;
            border: 1px solid #e3e6f0;
        }

        .form-select:focus {
            box-shadow: none;
            border-color: #4e73df;
        }

        /* Filter icons position */
        .filter-icon-wrapper {
            position: relative;
        }

        .filter-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            color: #6c757d;
        }

        /* Reset button */
        .btn-reset {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            color: #6c757d;
        }

        .btn-reset:hover {
            background-color: #eaecf4;
            border-color: #d1d3e2;
            color: #4e73df;
        }

        /* Table column widths */
        .table th.action-column,
        .table td.action-column {
            width: 100px;
            min-width: 100px;
            text-align: center;
        }

        .table th.location-column,
        .table td.location-column {
            width: auto;
            min-width: 200px;
        }

        /* Action buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
        }

        .btn-action i {
            font-size: 14px;
        }

        /* Table styling */
        .table {
            margin: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e3e6f0;
            color: #5a5c69;
            font-weight: 600;
            padding: 15px;
        }

        /* Loại bỏ màu xen kẽ và set màu nền đồng nhất */
        .table-striped tbody tr:nth-of-type(odd),
        .table-striped tbody tr:nth-of-type(even) {
            background-color: #fff;
        }

        /* Hover effect cho toàn bộ row */
        .table tbody tr {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .table tbody tr:hover {
            background-color: rgba(0,0,0,.02);
        }

        /* Column widths */
        .table th.case-id-column,
        .table td.case-id-column {
            min-width: 150px;
            width: 15%;
        }

        .table th.date-column,
        .table td.date-column {
            min-width: 150px;
            width: 15%;
        }

        /* Badge styling */
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        /* Animation */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* SweetAlert2 custom styles */
        .swal2-popup {
            border-radius: 15px;
        }

        .swal2-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .swal2-html-container {
            font-size: 1rem;
        }

        .swal2-confirm.btn-danger,
        .swal2-cancel.btn-secondary {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }

        .swal2-confirm.btn-danger {
            background-color: #dc3545;
        }

        .swal2-confirm.btn-danger:hover {
            background-color: #bb2d3b;
        }

        .swal2-cancel.btn-secondary {
            background-color: #6c757d;
        }

        .swal2-cancel.btn-secondary:hover {
            background-color: #5c636a;
        }

        /* Header row styling */
        .table thead th {
            background-color: #f8f9fa;
            color: #4e73df;
            font-weight: 600;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Case Study ID styling */
        .case-study-link {
            color: #28a745 !important;  /* Màu xanh lá */
            text-decoration: none;
            font-weight: 500;
        }

        tr:hover .case-study-link {
            text-decoration: underline;
            cursor: pointer;
        }

        /* Filter select styling */
        .form-select {
            border-radius: 20px !important;
            padding: 8px 35px !important; /* Tăng padding bên trái để chừa chỗ cho icon */
        }

        /* Filter icon positioning */
        .filter-icon-wrapper {
            position: relative;
        }

        .filter-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            color: #6c757d;
            margin-right: 10px;
        }

        /* Filter text spacing */
        .filter-text {
            margin-left: 10px; /* Khoảng cách giữa icon và text */
        }

        /* Action buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
        }

        /* Table hover effect */
        .table tbody tr:hover {
            background-color: rgba(0,0,0,.02);
        }
    </style>

    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Khởi tạo DataTable
            var table = $('#myTable').DataTable({
                responsive: true,
                order: [
                    [4, 'desc']
                ],
                language: {
                    lengthMenu: "Show _MENU_ case studies",
                    info: "Showing _START_ to _END_ of _TOTAL_ case studies",
                    infoEmpty: "Showing 0 to 0 of 0 case studies",
                    infoFiltered: "(filtered from _MAX_ total case studies)",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });

            // Toggle filter section
            const toggleFilter = document.getElementById('toggleFilter');
            const filterSection = document.getElementById('filterSection');
            
            toggleFilter.addEventListener('click', function() {
                if (filterSection.style.display === 'none') {
                    filterSection.style.display = 'block';
                    this.querySelector('i').classList.remove('fa-filter');
                    this.querySelector('i').classList.add('fa-times');
                } else {
                    filterSection.style.display = 'none';
                    this.querySelector('i').classList.remove('fa-times');
                    this.querySelector('i').classList.add('fa-filter');
                }
            });

            // Custom filtering function
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var category = $('#categoryFilter').val();
                var month = $('#monthFilter').val();
                var year = $('#yearFilter').val();
                var status = $('#statusFilter').val();
                
                var row = table.row(dataIndex).node();
                var rowCategory = $(row).data('category');
                var rowDate = data[3];  // Start Date column
                
                // Get status text directly from the badge span
                var statusCell = $(row).find('td:eq(5)'); // Status column
                var rowStatus = statusCell.find('span.badge').text().trim();
                
                // Parse date
                var date = rowDate.split('-');
                var rowMonth = parseInt(date[1]);
                var rowYear = parseInt(date[2]);
                
                // Debug logs
                console.log('Status Filter:', {
                    selectedStatus: status,
                    rowStatus: rowStatus,
                    match: !status || status === rowStatus
                });
                
                // Check each filter
                var categoryMatch = !category || category.toLowerCase() === (rowCategory || '').toLowerCase();
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
            $('.btn-delete').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const id = $(this).data('id');
                console.log('Deleting case study ID:', id); // Debug log
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        popup: 'swal2-show-animation',
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'php_action/removeCaseStudy.php',
                            type: 'POST',
                            data: {id: id},
                            dataType: 'json',
                            success: function(response) {
                                console.log('Server response:', response); // Debug log
                                
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'The case study has been deleted.',
                                        icon: 'success',
                                        customClass: {
                                            confirmButton: 'btn btn-success'
                                        },
                                        buttonsStyling: false
                                    }).then(() => {
                                        window.location.reload(); // Force page reload
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to delete case study.',
                                        icon: 'error',
                                        customClass: {
                                            confirmButton: 'btn btn-danger'
                                        },
                                        buttonsStyling: false
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Ajax error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });
                                
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Something went wrong while deleting.',
                                    icon: 'error',
                                    customClass: {
                                        confirmButton: 'btn btn-danger'
                                    },
                                    buttonsStyling: false
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</div>