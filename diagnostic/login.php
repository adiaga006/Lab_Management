<link rel="stylesheet" href="assets/css/popup_style.css"> 
<style>
.footer1 {
  position: fixed;
  bottom: 0;
  width: 100%;
  color: #5c4ac7;
  text-align: center;
}
</style>

<?php
include('./constant/layout/head.php');
session_start();

// Nếu đã đăng nhập và có URL chuyển hướng
if (isset($_SESSION['userId']) && isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    header('location: ' . $redirect_url);
    exit();
} 
// Nếu đã đăng nhập nhưng không có URL chuyển hướng
else if (isset($_SESSION['userId'])) {
    header('location: dashboard.php');
    exit();
}

$errors = array();

if ($_POST) {    
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        if ($username == "") {
            $errors[] = "Username is required";
        } 
        if ($password == "") {
            $errors[] = "Password is required";
        }
    } else {
        // Kiểm tra username trong database
        $sql = "SELECT * FROM user_infor WHERE username = '$username'";
        $result = $connect->query($sql);

        if ($result->num_rows == 1) {
            $password = md5($password);
            $mainSql = "SELECT * FROM user_infor WHERE username = '$username' AND password = '$password'";
            $mainResult = $connect->query($mainSql);

            if ($mainResult->num_rows == 1) {
                $value = $mainResult->fetch_assoc();
                
                // Thiết lập session
                $_SESSION['userId'] = $value['user_id'];
                $_SESSION['username'] = $value['username'];
                $_SESSION['role'] = $value['role'];
                $_SESSION['session_id'] = session_create_id('user_');
                $_SESSION['isAdmin'] = ($value['role'] == 1);
                $_SESSION['isEmployee'] = ($value['role'] == 2);
                $_SESSION['isGuest'] = ($value['role'] == 3);

                // Kiểm tra URL chuyển hướng
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    ?>
                    <div class="popup popup--icon -success js_success-popup popup--visible">
                        <div class="popup__background"></div>
                        <div class="popup__content">
                            <h3 class="popup__content__title">Success</h3>
                            <p>Login Successfully</p>
                            <p><?php echo "<script>setTimeout(\"location.href = '" . $redirect_url . "';\",1500);</script>"; ?></p>
                        </div>
                    </div>
                    <?php
                } else {
                    // Hiển thị popup thành công và chuyển về dashboard như cũ
                    ?>
                    <div class="popup popup--icon -success js_success-popup popup--visible">
                        <div class="popup__background"></div>
                        <div class="popup__content">
                            <h3 class="popup__content__title">Success</h3>
                            <p><?php echo ($value['role'] == 1) ? 'Admin' : ($value['role'] == 2 ? 'Employee' : 'Guest'); ?> Login Successfully</p>
                            <p><?php echo "<script>setTimeout(\"location.href = 'dashboard.php';\",1500);</script>"; ?></p>
                        </div>
                    </div>
                    <?php
                }
            } else { ?>
                <div class="popup popup--icon -error js_error-popup popup--visible">
                    <div class="popup__background"></div>
                    <div class="popup__content">
                        <h3 class="popup__content__title">Error</h3>
                        <p>Incorrect password</p>
                        <p><a href="login.php"><button class="button button--error">Close</button></a></p>
                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="popup popup--icon -error js_error-popup popup--visible">
                <div class="popup__background"></div>
                <div class="popup__content">
                    <h3 class="popup__content__title">Error</h3>
                    <p>Username does not exist</p>
                    <p><a href="login.php"><button class="button button--error">Close</button></a></p>
                </div>
            </div>
        <?php }
    }
}
?>

<!-- HTML và CSS còn lại như trước -->

<div id="main-wrapper">
    <div class="unix-login">
        <div class="container-fluid" style="background-image: url('assets/myimages/background.jpg'); background-color: #ffffff; background-size: cover;">
            <div class="row">
                <div class="col-lg-4 ml-auto">
                    <div class="login-content">
                        <div class="login-form">
                            <center><img src="./assets/uploadImage/Logo/shrimvet_logo.png" style="width: 100%;"></center><br>
                            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" id="loginForm">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" id="username" class="form-control" placeholder="Username" required="">
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required="">
                                </div>
                                <button type="submit" name="login" class="btn btn-primary btn-flat m-b-30 m-t-30">Sign in</button>
                                <div class="forgot-phone text-left f-left">
                                    <a href="search.php" class="text-right f-w-600">Search Reports</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/lib/jquery/jquery.min.js"></script>
<script src="./assets/js/lib/bootstrap/js/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="./assets/js/jquery.slimscroll.js"></script>
<script src="./assets/js/sidebarmenu.js"></script>
<script src="./assets/js/lib/sticky-kit-master/dist/sticky-kit.min.js"></script>
<script src="./assets/js/custom.min.js"></script>
<script>
function onReady(callback) {
    var intervalID = window.setInterval(checkReady, 1000);
    function checkReady() {
        if (document.getElementsByTagName('body')[0] !== undefined) {
            window.clearInterval(intervalID);
            callback.call(this);
        }
    }
}

function show(id, value) {
    document.getElementById(id).style.display = value ? 'block' : 'none';
}

onReady(function () {
    show('page', true);
    show('loading', false);
});
</script>
</body>
</html>
