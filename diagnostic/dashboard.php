<?php 
error_reporting(1);
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');   
include('./constant/connect.php');

// Kiểm tra nếu người dùng là admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 1;

// Đếm số hàng trong các bảng
$countPond = $connect->query("SELECT COUNT(*) AS total FROM pond")->fetch_assoc()['total'];
$countCaseStudy = $connect->query("SELECT COUNT(*) AS total FROM case_study")->fetch_assoc()['total'];
$countEntryData = $connect->query("SELECT COUNT(*) AS total FROM entry_data")->fetch_assoc()['total'];
$countUsers = $connect->query("SELECT COUNT(*) AS total FROM user_infor")->fetch_assoc()['total'];
$countCategories = $connect->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'];

$connect->close();
?>

<style type="text/css">
    .ui-datepicker-calendar {
        display: none;
    }
</style>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <!-- Optional header content -->
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Tổng số hồ (Pond) -->
            <div class="col-md-3 dashboard">
                <div class="card" style="background: #2BC155">
                    <div class="media widget-ten">
                        <div class="media-left meida media-middle">
                            <span><i class="ti-water f-s-40"></i></span>
                        </div>
                        <div class="media-body media-text-right">
                            <h2 class="color-white"><?php echo $countPond; ?></h2>
                            <p class="m-b-0">Total Ponds</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng số nghiên cứu (Case Study) -->
            <div class="col-md-3 dashboard">
                <div class="card" style="background-color: #F94687">
                    <div class="media widget-ten">
                        <div class="media-left meida media-middle">
                            <span><i class="ti-book f-s-40"></i></span>
                        </div>
                        <div class="media-body media-text-right">
                            <h2 class="color-white"><?php echo $countCaseStudy; ?></h2>
                            <p class="m-b-0">Total Case Studies</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tổng số dữ liệu nhập vào (Entry Data) -->
            <div class="col-md-3 dashboard">
                <div class="card" style="background-color:#009688;">
                    <div class="media widget-ten">
                        <div class="media-left meida media-middle">
                            <span><i class="ti-pencil-alt f-s-40"></i></span>
                        </div>
                        <div class="media-body media-text-right">
                            <h2 class="color-white"><?php echo $countEntryData; ?></h2>
                            <p class="m-b-0">Total Entry Data</p>
                        </div>
                    </div> 
                </div>
            </div>

            <!-- Tổng số người dùng (Users) -->
            <div class="col-md-3 dashboard">
                <div class="card" style="background: #5c4ac7">
                    <div class="media widget-ten">
                        <div class="media-left meida media-middle">
                            <span><i class="ti-user f-s-40"></i></span>
                        </div>
                        <div class="media-body media-text-right">
                            <h2 class="color-white"><?php echo $countUsers; ?></h2>
                            <p class="m-b-0">Total Users</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng số danh mục (Categories) -->
            <div class="col-md-3 dashboard">
                <div class="card" style="background-color: #e83e8c;">
                    <div class="media widget-ten">
                        <div class="media-left meida media-middle">
                            <span><i class="ti-tag f-s-40"></i></span>
                        </div>
                        <div class="media-body media-text-right">
                            <h2 class="color-white"><?php echo $countCategories; ?></h2>
                            <p class="m-b-0">Total Categories</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ('./constant/layout/footer.php'); ?>

<script>
$(function() {
    $(".preloader").fadeOut();
});
</script>
