<?php 
 include('./constant/connect.php');
?>

<div class="left-sidebar">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <li class="nav-devider"></li>
                <li class="nav-label">Home</li>
                <li> <a href="dashboard.php" aria-expanded="false"><i class="fa fa-tachometer"></i>Dashboard</a></li> 

                <!-- Hiển thị phần dành riêng cho admin -->
                <?php if(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) { ?>
                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-user"></i><span class="hide-menu">Client</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="add_client.php">Add Client</a></li>
                            <li><a href="client.php">Manage Client</a></li>
                        </ul>
                    </li>

                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-list"></i><span class="hide-menu">Test Categories</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="add-category.php">Add Test Category</a></li>
                            <li><a href="categories.php"> Manage Categories</a></li>
                        </ul>
                    </li>

                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-flask"></i><span class="hide-menu">Case Study</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="add-case_study.php">Add Case Study</a></li>
                            <li><a href="case_study.php">Manage Case Study</a></li>
                        </ul>
                    </li>
                <?php } ?>

                <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-files-o"></i><span class="hide-menu">Invoices</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="add-invoice.php">Add Invoice</a></li>
                        <li><a href="invoice.php">Manage Invoices</a></li>
                    </ul>
                </li>

                <!-- Các phần chỉ dành cho admin -->
                <?php if(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) { ?>
                    <li><a href="report.php" aria-expanded="false"><i class="fa fa-print"></i><span class="hide-menu">Reports</span></a></li>
                    
                    <li> <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-cog"></i><span class="hide-menu">Setting</span></a>
                        <ul aria-expanded="false" class="collapse">
                            <li><a href="manage_website.php">Appearance</a></li>
                            <li><a href="email_config.php">Email</a></li>
                        </ul>
                    </li>

                    <li><a href="about.php" aria-expanded="false"><i class="fa fa-info-circle"></i><span class="hide-menu">Know More</span></a></li>
                    <li><a href="https://mayurik.com/source-code/P5207/advance-laboratory-management-system-project" aria-expanded="false"><i class="fa fa-upload"></i><span class="hide-menu">Advance Version</span></a></li>
                <?php } ?>
            </ul>   
        </nav>
    </div>
</div>
