<?php
// Kiểm tra kết nối database đã được thiết lập chưa
if (!isset($connect)) {
    include('./constant/connect.php');
}

// Kiểm tra xem có đang ở trang liên quan đến case study không
$currentPage = basename($_SERVER['PHP_SELF']);
$relatedPages = ['group.php', 'manage_image.php', 'chart.php', 'results.php', 'entry_data.php', 'death_data.php', 'water_quality.php', 'view_death_data.php', 'entry_data_survival.php', 'entry_data_feeding.php', 'edit-case_study.php','schedule.php'];
$isRelatedPage = (isset($_GET['case_study_id']) || isset($_GET['id'])) && in_array($currentPage, $relatedPages);

// Lấy case study ID từ URL (xử lý cả hai trường hợp)
$currentCaseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : (isset($_GET['id']) ? $_GET['id'] : null);
?>

<div class="left-sidebar">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="nav-devider"></li>
                <li class="nav-label">Home</li>
                <li> <a href="dashboard.php" aria-expanded="false"><i class="fa fa-tachometer"></i>Dashboard</a></li>

                <!-- Hiển thị phần dành riêng cho admin -->
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) { ?>
                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-user"></i><span
                                class="hide-menu">Client</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="add_client.php">Add Client</a></li>
                            <li><a href="client.php">Manage Client</a></li>
                        </ul>
                    </li>
                    <!-- Test Categories Menu -->
                    <li>
                        <a class="has-arrow" href="#" aria-expanded="false">
                            <i class="fa fa-list"></i>
                            <span class="hide-menu">Test Categories</span>
                        </a>
                        <ul class="collapse submenu">
                            <li><a href="add-category.php">Add Test Category</a></li>
                            <li><a href="categories.php">Manage Categories</a></li>
                        </ul>
                    </li>

                    <!-- Case Study Menu -->
                    <li>
                        <a class="<?php echo !$isRelatedPage ? 'has-arrow' : 'active'; ?>" href="#"
                            aria-expanded="<?php echo $isRelatedPage ? 'true' : 'false'; ?>">
                            <i class="fa fa-flask"></i>
                            <span class="hide-menu">Case Study</span>
                        </a>
                        <ul aria-expanded="<?php echo $isRelatedPage ? 'true' : 'false'; ?>"
                            class="collapse submenu <?php echo $isRelatedPage ? 'show' : ''; ?>">
                            <li><a href="add-case_study.php">Add Case Study</a></li>
                            <li <?php echo ($currentPage == 'case_study.php') ? 'class="active"' : ''; ?>>
                                <a href="case_study.php">Manage Case Study</a>
                            </li>
                            <?php
                            if ($currentCaseStudyId) {
                                echo '<li class="active" style="background-color: #A7D477;">';
                                echo '<a href="group.php?case_study_id=' . htmlspecialchars($currentCaseStudyId) . '" style="color: #ffffff; padding-left: 30px;">';
                                echo '<i class="fa fa-angle-right"></i> ';
                                echo htmlspecialchars($currentCaseStudyId);
                                echo '</a>';
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </li>
                <?php } ?>

                <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-files-o"></i><span
                            class="hide-menu">Invoices</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="add-invoice.php">Add Invoice</a></li>
                        <li><a href="invoice.php">Manage Invoices</a></li>
                    </ul>
                </li>

                <!-- Các phần chỉ dành cho admin -->
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) { ?>
                    <li><a href="report.php" aria-expanded="false"><i class="fa fa-print"></i><span
                                class="hide-menu">Reports</span></a></li>

                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-cog"></i><span
                                class="hide-menu">Setting</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="manage_website.php">Appearance</a></li>
                            <li><a href="email_config.php">Email</a></li>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
        </nav>
    </div>
</div>
<style>
    /* Style cho submenu */
    .sidebar-nav .submenu {
        position: static !important; /* Đảm bảo submenu không bị đè */
        height: auto !important;
        visibility: visible;
    }

    /* Khi submenu được mở */
    .sidebar-nav .submenu.show {
        display: block !important;
    }

    /* Style cho mục active */
    .sidebar-nav ul li.active > a {
        color: #ffffff !important;
        background-color: #A7D477;
    }

    /* Border cho mục active */
    .sidebar-nav ul li.active {
        border-left: 3px solid #0056b3;
    }

    /* Hiệu ứng hover */
    .sidebar-nav ul li a:hover {
        color: #000000;
        background-color: #6BBE45;
        text-decoration: none !important; /* Loại bỏ gạch dưới khi hover */
    }

    /* Đảm bảo các submenu không chồng lên nhau */
    .sidebar-nav ul.submenu {
        margin-bottom: 0;
        padding-bottom: 0;
    }

    /* Đảm bảo khoảng cách giữa các menu */
    .sidebar-nav > ul > li {
        margin-bottom: 5px;
    }
</style>