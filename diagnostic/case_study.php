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
                    <form id="filterForm" class="row g-3 align-items-center">
                        <div class="col-md">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-map-marker-alt filter-icon"></i>
                                <select id="categoryFilter" class="form-select">
                                    <option value="">Case study location</option>
                                    <?php
                                    $catSql = "SELECT DISTINCT category_name FROM case_study WHERE user_id = ?";
                                    $stmt = $connect->prepare($catSql);
                                    $stmt->bind_param("i", $_SESSION['userId']);
                                    $stmt->execute();
                                    $catResult = $stmt->get_result();
                                    while ($row = $catResult->fetch_assoc()) {
                                        // Chuẩn hóa text: viết hoa chữ cái đầu
                                        $formattedName = ucwords(strtolower($row['category_name']));
                                        echo '<option value="' . $row['category_name'] . '">' .
                                            htmlspecialchars($formattedName) .
                                            '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-tag filter-icon"></i>
                                <select id="categoriesFilter" class="form-select">
                                    <option value="">Categories</option>
                                    <?php
                                    $categoriesSql = "SELECT DISTINCT c.categories_id, c.categories_name 
                                                    FROM categories c 
                                                    INNER JOIN case_study cs ON c.categories_id = cs.categories_id 
                                                    WHERE cs.user_id = ? 
                                                    ORDER BY c.categories_name ASC";
                                    $stmt = $connect->prepare($categoriesSql);
                                    $stmt->bind_param("i", $_SESSION['userId']);
                                    $stmt->execute();
                                    $categoriesResult = $stmt->get_result();
                                    while ($row = $categoriesResult->fetch_assoc()) {
                                        echo '<option value="' . $row['categories_name'] . '">' .
                                            htmlspecialchars($row['categories_name']) .
                                            '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="filter-icon-wrapper">
                                <i class="far fa-calendar filter-icon"></i>
                                <select id="monthFilter" class="form-select">
                                    <option value="">Month</option>
                                    <?php
                                    for ($m = 1; $m <= 12; $m++) {
                                        echo '<option value="' . $m . '">' . date('F', mktime(0, 0, 0, $m, 1)) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="filter-icon-wrapper">
                                <i class="far fa-calendar-alt filter-icon"></i>
                                <select id="yearFilter" class="form-select">
                                    <option value="">Year</option>
                                    <?php
                                    $currentYear = date('Y');
                                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                        echo '<option value="' . $y . '">' . $y . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="filter-icon-wrapper">
                                <i class="fas fa-tasks filter-icon"></i>
                                <select id="statusFilter" class="form-select">
                                    <option value="">Status</option>
                                    <option value="Prepare">Prepare</option>
                                    <option value="In-process">In Process</option>
                                    <option value="Complete">Complete</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <button type="button" id="resetFilter" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
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
                                $statusBadge = match ($row['status']) {
                                    'Prepare' => '<span class="badge bg-info">Prepare</span>',
                                    'In-process' => '<span class="badge bg-warning">In-process</span>',
                                    'Complete' => '<span class="badge bg-success">Complete</span>',
                                    default => '<span class="badge bg-secondary">Unknown</span>'
                                };
                            ?>
                                <tr data-category-name="<?php echo htmlspecialchars($row['category_name']); ?>"
                                    onclick="window.location='group.php?case_study_id=<?php echo $row['case_study_id']; ?>'"
                                    style="cursor: pointer;">
                                    <td>
                                        <?php
                                        // Xác định màu dựa trên status
                                        $statusColor = match ($row['status']) {
                                            'Prepare' => 'text-info',         // Màu xanh dương nhạt
                                            'In-process' => 'text-warning',   // Màu vàng
                                            'Complete' => 'text-success',     // Màu xanh lá
                                            default => 'text-secondary'       // Màu xám cho trường hợp khác
                                        };
                                        ?>
                                        <span class="case-study-link <?php echo $statusColor; ?> fw-bold">
                                            <?php echo $row['case_study_id']; ?>
                                        </span>
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
            color: #2f2f2f;
            /* Đậm hơn */
        }

        /* Case Study ID color */
        .case-study-link {
            color: #28a745 !important;
            /* Màu xanh lá */
            text-decoration: none;
            font-weight: 500;
        }

        /* Filter section styling */
        .filter-section {
            background: #fff;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e3e6f0;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Form elements */
        .filter-icon-wrapper {
            position: relative;
            margin-bottom: 0;
        }

        .filter-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6e707e;
            z-index: 1;
            font-size: 0.875rem;
        }

        .form-select {
            padding-left: 2.25rem;
            padding-right: 2rem;
            height: 38px;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            border: 1px solid #e3e6f0;
            background-color: #fff;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .form-select:hover {
            border-color: #bac8f3;
        }

        /* Reset button */
        .btn-secondary {
            color: #fff;
            background-color: #858796;
            border-color: #858796;
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            height: 38px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6c6e7c;
            transform: translateY(-1px);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .filter-section {
                padding: 1rem;
            }

            .row.g-3 {
                row-gap: 0.75rem !important;
            }

            .col-auto {
                width: 100%;
                margin-top: 1rem;
            }

            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation */
        .filter-section {
            animation: slideDown 0.3s ease-out;
        }

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
            background-color: rgba(0, 0, 0, .02);
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
            color: #28a745 !important;
            /* Màu xanh lá */
            text-decoration: none;
            font-weight: 500;
        }

        tr:hover .case-study-link {
            text-decoration: underline;
            cursor: pointer;
        }

        /* Màu cho status */
        .text-info {
            color: #0dcaf0 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .text-success {
            color: #198754 !important;
        }

        .text-secondary {
            color: #6c757d !important;
        }

        /* Thêm hiệu ứng hover nếu cần */
        .case-study-link:hover {
            opacity: 0.8;
            cursor: pointer;
        }

        /* Đảm bảo font weight */
        .fw-bold {
            font-weight: 600 !important;
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
                var categoryType = $('#categoryFilter').val();
                var categories = $('#categoriesFilter').val();
                var month = $('#monthFilter').val();
                var year = $('#yearFilter').val();
                var status = $('#statusFilter').val();

                var row = table.row(dataIndex).node();
                var rowCategoryType = $(row).data('category-name'); // Lấy từ data attribute
                var rowCategories = data[4]; // Categories Name column từ DataTable
                var rowDate = data[3]; // Start Date column
                var rowStatus = $(row).find('td:eq(5)').find('span.badge').text().trim();

                // Parse date
                var date = rowDate.split('-');
                var rowMonth = parseInt(date[1]);
                var rowYear = parseInt(date[2]);

                // Check each filter
                var categoryTypeMatch = !categoryType ||
                    categoryType.toLowerCase() === rowCategoryType.toLowerCase();
                var categoriesMatch = !categories || categories === rowCategories;
                var monthMatch = !month || parseInt(month) === rowMonth;
                var yearMatch = !year || parseInt(year) === rowYear;
                var statusMatch = !status || status === rowStatus;

                return categoryTypeMatch && categoriesMatch && monthMatch && yearMatch && statusMatch;
            });

            // Apply filters
            $('.form-select').change(function() {
                table.draw();
            });

            // Reset filters
            $('#resetFilter').click(function() {
                ['categoryFilter', 'categoriesFilter', 'monthFilter', 'yearFilter', 'statusFilter'].forEach(id => {
                    document.getElementById(id).value = '';
                });
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
                            data: {
                                id: id
                            },
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