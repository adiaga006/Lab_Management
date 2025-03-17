<?php
//session_start();
//include('../constant/check.php'); ?>
<?php
//session_start();
include('./constant/check.php');
include('./constant/connect.php');
?>
<!-- jQuery and Bootstrap JS -->
<!-- <script src="assets/js/lib/jquery/jquery.min.js"></script> -->
<!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
<!-- Bootstrap CSS-->
<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
 <!-- Font Awesome -->
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<div id="main-wrapper">

    <div class="header">
        <marquee class="d-lg-none d-block">
            <div class="ml-lg-5 pl-lg-5 ">

                <b id="ti" class="ml-lg-5 pl-lg-5"></b>


            </div>
        </marquee>
        <nav class="navbar top-navbar navbar-expand-md navbar-light">

            <div class="navbar-header">
                <a class="navbar-brand" href="index.php">


                    <b>
                        <img src="./assets/uploadImage/Logo/shrimvet_logo.png" style="width: 100%; height: 80px;"
                            alt="homepage" class="dark-logo" />
                    </b>

                </a>
            </div>

            <div class="navbar-collapse">

                <ul class="navbar-nav mr-auto mt-md-0">

                    <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted  "
                            href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                    <li class="nav-item m-l-10"> <a class="nav-link sidebartoggler hidden-sm-down text-muted  "
                            href="javascript:void(0)"><i class="ti-menu"></i></a> </li>



                </ul>
                <marquee behavior="scroll" direction="left" scrollamount="1">
                    <p style="color: red;">
                        Welcome back, this is a test version, not fully functional, we are still perfecting it.</p>
                </marquee>
                <ul class="navbar-nav my-lg-0 ml-auto">
                    <!-- Dịch Google Translate sang phải -->
                    <div id="google_translate_element" style="margin-right: 35px;"></div>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-muted" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php
                            $userId = $_SESSION['userId'];
                            $avatarPath = null;
                            $avatarDir = './assets/uploads/avatars/';
                            $avatarPattern = $avatarDir . 'user_' . $userId . '.*';
                            $avatarFiles = glob($avatarPattern);
                            
                            if (!empty($avatarFiles)) {
                                $avatarPath = $avatarFiles[0];
                            }
                            ?>
                            <img src="<?php echo $avatarPath ? $avatarPath : './assets/images/default-avatar.png'; ?>" 
                                 alt="user" 
                                 class="profile-pic header-avatar" 
                                 style="width: 55px; 
                                        height: 55px; 
                                        border-radius: 50%; 
                                        object-fit: cover;
                                        border: 2px solid #fff;
                                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                        transition: all 0.3s ease;" />
                        </a>
                        <div class="dropdown-menu dropdown-menu-right animated zoomIn">
                            <!-- Profile header in dropdown -->
                            <div class="dropdown-header d-flex align-items-center p-3">
                                <img src="<?php echo $avatarPath ? $avatarPath : './assets/images/default-avatar.png'; ?>" 
                                     alt="user" 
                                     class="dropdown-avatar"
                                     style="width: 70px; 
                                            height: 70px; 
                                            border-radius: 50%; 
                                            object-fit: cover;
                                            margin-right: 15px;
                                            border: 3px solid #fff;
                                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                                <div>
                                    <h6 class="mb-0"><?php echo $_SESSION['username']; ?></h6>
                                    <small class="text-muted">
                                        <?php 
                                        switch($_SESSION['role']) {
                                            case 1:
                                                echo '<span class="badge badge-danger">
                                                        <i class="fas fa-crown"></i> Administrator
                                                      </span>';
                                                break;
                                            case 2:
                                                echo '<span class="badge badge-success">
                                                        <i class="fas fa-user-tie"></i> Employee
                                                      </span>';
                                                break;
                                            case 3:
                                                echo '<span class="badge badge-info">
                                                        <i class="fas fa-user"></i> Guest
                                                      </span>';
                                                break;
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <ul class="dropdown-user">
                                <?php if (isset($_SESSION['userId'])) { ?>
                                    <li>
                                        <a href="account.php" class="dropdown-item">
                                            <i class="fa fa-user-circle"></i> My Account
                                        </a>
                                    </li>
                                    
                                    <?php if ($_SESSION['userId'] == 1) { ?>
                                        <li>
                                            <a href="setting.php" class="dropdown-item">
                                                <i class="fa fa-key"></i> Changed Password
                                            </a>
                                        </li>
                                        <li>
                                            <a href="users.php" class="dropdown-item">
                                                <i class="fa fa-user"></i> Add user
                                            </a>
                                        </li>
                                    <?php } ?>

                                    <li>
                                        <a href="./constant/logout.php" class="dropdown-item">
                                            <i class="fa fa-power-off"></i> Logout
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <style>
    .navbar-nav .nav-item.dropdown {
        display: flex;
        align-items: center;
    }

    .profile-pic:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .dropdown-menu {
        min-width: 280px;
        padding: 0;
    }

    .dropdown-header {
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
    }

    .dropdown-item {
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        padding-left: 25px;
    }

    .dropdown-item i {
        width: 20px;
        margin-right: 10px;
        text-align: center;
    }

    .badge {
        padding: 8px 12px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        border-radius: 15px;
        margin-top: 5px;
        transition: all 0.3s ease;
    }

    .badge i {
        margin-right: 8px;
    }

    /* Administrator Badge */
    .badge.badge-danger {
        background: linear-gradient(45deg, #dc3545, #c82333);
        box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
    }

    /* Employee Badge */
    .badge.badge-success {
        background: linear-gradient(45deg, #28a745, #218838);
        box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
    }

    /* Guest Badge */
    .badge.badge-info {
        background: linear-gradient(45deg, #17a2b8, #138496);
        box-shadow: 0 2px 5px rgba(23, 162, 184, 0.3);
    }

    /* Hover effects */
    .badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .badge:hover i {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }
    </style>

    <script>
    // Function to update all avatar images
    function updateAvatarImages(newSrc) {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const srcWithTimestamp = `${newSrc}?t=${timestamp}`;
        
        // Update header avatar
        document.querySelectorAll('.header-avatar').forEach(img => {
            img.src = srcWithTimestamp;
        });
        
        // Update dropdown avatar
        document.querySelectorAll('.dropdown-avatar').forEach(img => {
            img.src = srcWithTimestamp;
        });
    }

    // Listen for custom event from account.php
    document.addEventListener('avatarUpdated', function(e) {
        if (e.detail && e.detail.avatarPath) {
            updateAvatarImages(e.detail.avatarPath);
        }
    });
    </script>