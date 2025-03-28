<?php
include('./constant/connect.php');
session_start();
$password = "SOP@2025"; // Fixed password

// Handle file upload within this file
if (isset($_GET['action']) && $_GET['action'] == 'upload' && isset($_SESSION['sop_authenticated']) && $_SESSION['sop_authenticated'] === true) {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // List of allowed file formats
        $allowed = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'avi', 'xls', 'xlsx');
        
        if (in_array($file_ext, $allowed)) {
            if ($file_error === 0) {
                if ($file_size <= 10485760) { // 10MB limit
                    // Ensure the correct upload directory
                    $upload_dir = "sop_files/";
                    
                    // Create directory if not exists
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            header("Location: manage_SOP.php?error=directory_creation_failed");
                            exit();
                        }
                    }
                    
                    // Keep original filename and ensure UTF-8 encoding
                    $timestamp = date('YmdHis');
                    $file_name_utf8 = mb_convert_encoding($file_name, 'UTF-8', mb_detect_encoding($file_name));
                    $file_new_name = $timestamp . '_' . $file_name_utf8;
                    $file_destination = $upload_dir . $file_new_name;
                    
                    if (move_uploaded_file($file_tmp, $file_destination)) {
                        // Save description to JSON file
                        if (isset($_POST['description']) && !empty(trim($_POST['description']))) {
                            $json_file = $upload_dir . "descriptions.json";
                            $descriptions = array();
                            
                            if (file_exists($json_file)) {
                                $descriptions = json_decode(file_get_contents($json_file), true);
                            }
                            
                            $descriptions[$file_new_name] = trim($_POST['description']);
                            file_put_contents($json_file, json_encode($descriptions, JSON_UNESCAPED_UNICODE));
                        }
                        
                        header("Location: manage_SOP.php?success=1");
                        exit();
                    } else {
                        header("Location: manage_SOP.php?error=upload_failed");
                        exit();
                    }
                } else {
                    header("Location: manage_SOP.php?error=file_too_large");
                    exit();
                }
            } else {
                header("Location: manage_SOP.php?error=file_error");
                exit();
            }
        } else {
            header("Location: manage_SOP.php?error=invalid_file_type");
            exit();
        }
    } else {
        header("Location: manage_SOP.php");
        exit();
    }
}

// Handle file deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['file']) && isset($_SESSION['sop_authenticated']) && $_SESSION['sop_authenticated'] === true) {
    $file_to_delete = $_GET['file'];
    $file_path = "sop_files/" . $file_to_delete;
    
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            // Remove description from JSON if it exists
            $json_file = "sop_files/descriptions.json";
            if (file_exists($json_file)) {
                $descriptions = json_decode(file_get_contents($json_file), true);
                if (isset($descriptions[$file_to_delete])) {
                    unset($descriptions[$file_to_delete]);
                    file_put_contents($json_file, json_encode($descriptions, JSON_UNESCAPED_UNICODE));
                }
            }
            header("Location: manage_SOP.php?success=2");
            exit();
        } else {
            header("Location: manage_SOP.php?error=delete_failed");
            exit();
        }
    } else {
        header("Location: manage_SOP.php?error=file_not_found");
        exit();
    }
}

// Check if password is submitted
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['sop_authenticated'] = true;
        $_SESSION['last_activity'] = time();
        header("Location: manage_SOP.php");
        exit();
    } else {
        $error = "Incorrect password! Please try again.";
    }
}

// Kiểm tra thời gian không hoạt động
$inactive = 1800; // 30 phút
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_destroy();
    header("Location: manage_SOP.php?error=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

// Thêm CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: manage_SOP.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['sop_authenticated']) || $_SESSION['sop_authenticated'] !== true) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SOP Management - Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <style>
            body {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.95);
                padding: 2.5rem;
                border-radius: 20px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
                width: 100%;
                max-width: 420px;
                backdrop-filter: blur(10px);
                transition: all 0.3s ease;
            }
            .login-container:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }
            .login-header {
                margin-bottom: 2rem;
            }
            .login-header i {
                font-size: 3.5rem;
                color: #28a745;
                margin-bottom: 1.5rem;
                filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
            }
            .login-header h2 {
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            .login-header p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            .form-label {
                font-weight: 500;
                color: #495057;
            }
            .form-control {
                padding: 0.8rem 1rem;
                border-radius: 10px;
                border: 1px solid #ced4da;
                transition: all 0.3s;
                background: rgba(255, 255, 255, 0.8);
            }
            .form-control:focus {
                border-color: #28a745;
                box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            }
            .password-container {
                position: relative;
                margin-bottom: 1.5rem;
            }
            .password-toggle {
                position: absolute;
                top: 50%;
                right: 10px;
                transform: translateY(-50%);
                cursor: pointer;
                color: #6c757d;
                font-size: 0.9rem;
            }
            .btn-login {
                background: #28a745;
                border: none;
                color: white;
                padding: 1rem;
                border-radius: 10px;
                font-weight: 500;
                letter-spacing: 0.5px;
                transition: all 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .btn-login:hover {
                background: #218838;
                transform: translateY(-2px);
                box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            }
            .btn-login:active {
                transform: translateY(0);
            }
            .app-title {
                color: #28a745;
                font-size: 1rem;
                margin-top: 1.5rem;
                text-align: center;
                font-weight: 500;
            }
            /* Icon Spacing Styles */
            .me-2 {
                margin-right: 1rem !important;
            }
            .me-1 {
                margin-right: 0.75rem !important;
            }
            
            /* Form Label Icon Spacing */
            .form-label i {
                margin-right: 1rem !important;
                width: 20px !important;
                display: inline-block !important;
                text-align: center !important;
            }

            .form-label {
                display: flex !important;
                align-items: center !important;
                gap: 0.5rem !important;
            }

            /* Small text icon spacing */
            .text-muted i {
                margin-right: 0.75rem !important;
            }

            /* Button Icon Spacing */
            .btn i {
                margin-right: 0.75rem;
                width: 16px;
                text-align: center;
            }

            /* Card Header Icon Spacing */
            .card-header i {
                margin-right: 1rem;
                width: 18px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header text-center">
                <i class="fa fa-file-text-o"></i>
                <h2>SOP Management</h2>
                <p class="text-muted">Please enter password to access SOP documents</p>
            </div>
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" required 
                               pattern=".{8,}" title="Password must be at least 8 characters long"
                               placeholder="Enter your password"
                               autocomplete="new-password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100">
                    <i class="fa fa-sign-in me-2"></i>Login
                </button>
            </form>
            <div class="app-title">ShrimpVet Data Management System</div>
        </div>

        <script>
            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const toggleIcon = document.getElementById('toggleIcon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            }

            // Thêm validation cho form
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                if (password.length < 8) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Lỗi',
                        text: 'Mật khẩu phải có ít nhất 8 ký tự!',
                        icon: 'error',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'Thử lại'
                    });
                }
            });

            // Thêm timeout cho session
            let inactivityTime = function() {
                let time;
                window.onload = resetTimer;
                document.onmousemove = resetTimer;
                document.onkeypress = resetTimer;

                function logout() {
                    window.location.href = 'manage_SOP.php?logout=1';
                }

                function resetTimer() {
                    clearTimeout(time);
                    time = setTimeout(logout, 1800000); // 30 phút
                }
            };
            inactivityTime();

            // Hiển thị thông báo lỗi đăng nhập
            <?php if (isset($error)): ?>
            Swal.fire({
                title: 'Login Failed',
                text: "<?php echo $error; ?>",
                icon: 'error',
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Try Again'
            });
            <?php endif; ?>

            // Hiển thị thông báo session hết hạn
            <?php if (isset($_GET['error']) && $_GET['error'] == 'session_expired'): ?>
            Swal.fire({
                title: 'Session Expired',
                text: 'Please login again to continue.',
                icon: 'warning',
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Login'
            });
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
} else {
    // Show file management interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SOP Management</title>
        <?php include('./constant/layout/head.php'); ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
        <style>
            /* Main Content Styles */
            .content-wrapper {
                margin-left: 90px; /* Adjust based on your sidebar width */
                padding: 20px 30px;
                transition: all 0.3s ease;
            }

            @media (min-width: 992px) {
                .content-wrapper {
                    margin-left: 250px;
                }
            }

            /* Page Header Styles */
            .page-header {
                background: #fff;
                padding: 1.5rem;
                border-radius: 15px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                margin-bottom: 2rem;
            }

            .page-header h1 {
                color: #2c3e50;
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
                display: flex;
                align-items: center;
            }

            .page-header h1 i {
                margin-right: 0.75rem;
                color: #28a745;
            }

            .breadcrumb {
                background: transparent;
                padding: 0;
                margin: 0;
            }

            .breadcrumb-item a {
                color: #28a745;
                text-decoration: none;
            }

            .breadcrumb-item.active {
                color: #6c757d;
            }

            /* Card Styles */
            .card {
                border: none;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                border-radius: 15px;
                overflow: hidden;
                margin-bottom: 2rem;
                background: #fff;
            }

            .card-header {
                background: #f8f9fa;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1.25rem 1.5rem;
                font-weight: 600;
                color: #2c3e50;
                display: flex;
                align-items: center;
            }

            .card-header i {
                margin-right: 1rem;
                font-size: 1.25rem;
            }

            .card-body {
                padding: 1.8rem;
                background: #fff;
            }

            /* File Card Styles */
            .file-card {
                background: #fff;
                border-radius: 15px;
                padding: 1.8rem;
                margin-bottom: 1.5rem;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid rgba(0, 0, 0, 0.05);
                height: 100%;
                display: flex;
                flex-direction: column;
                position: relative;
                overflow: hidden;
            }

            .file-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(to right, #28a745, #20c997);
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .file-card:hover::before {
                opacity: 1;
            }

            .file-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            }

            .file-icon {
                font-size: 2.5rem;
                color: #28a745;
                margin-bottom: 1rem;
                text-align: center;
                transition: all 0.3s ease;
            }

            .file-card:hover .file-icon {
                transform: scale(1.1);
            }

            .card-title {
                font-weight: 600;
                margin-bottom: 0.75rem;
                color: #2c3e50;
                font-size: 1.1rem;
                display: flex;
                align-items: center;
            }

            .card-title i {
                margin-right: 0.75rem;
                font-size: 1rem;
            }

            /* Form Styles */
            .form-control {
                border-radius: 10px;
                border: 1px solid #ced4da;
                padding: 0.75rem 1rem;
                transition: all 0.3s;
            }

            .form-control:focus {
                border-color: #28a745;
                box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            }

            .form-label {
                font-weight: 500;
                color: #2c3e50;
                margin-bottom: 0.5rem;
            }

            /* Button Styles */
            .btn {
                padding: 0.6rem 1.2rem;
                border-radius: 10px;
                font-weight: 500;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .btn i {
                margin-right: 0.5rem;
            }

            .btn-upload {
                background: #28a745;
                color: white;
                border: none;
                box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
            }

            .btn-upload:hover {
                background: #218838;
                transform: translateY(-2px);
                box-shadow: 0 6px 10px rgba(40, 167, 69, 0.3);
            }

            .btn-sm {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            /* Empty State */
            .empty-state {
                text-align: center;
                padding: 3rem 2rem;
                background: #f8f9fa;
                border-radius: 15px;
                margin: 1rem 0;
            }

            .empty-state i {
                font-size: 4rem;
                color: #28a745;
                margin-bottom: 1.5rem;
                opacity: 0.5;
            }

            .empty-state h5 {
                color: #2c3e50;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }

            .empty-state p {
                color: #6c757d;
                margin-bottom: 0;
            }

            /* File Description */
            .file-desc {
                color: #6c757d;
                margin: 1rem 0;
                line-height: 1.5;
            }

            /* File Meta */
            .file-meta {
                display: flex;
                align-items: center;
                gap: 1rem;
                color: #6c757d;
                font-size: 0.875rem;
                margin-top: auto;
                padding-top: 1rem;
                border-top: 1px solid #eee;
            }

            .file-meta i {
                margin-right: 0.5rem;
            }

            /* Action Buttons */
            .btn-group {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .btn-primary {
                background: #007bff;
                border: none;
                box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
            }

            .btn-primary:hover {
                background: #0056b3;
                transform: translateY(-2px);
                box-shadow: 0 6px 10px rgba(0, 123, 255, 0.3);
            }

            .btn-danger {
                background: #dc3545;
                border: none;
                box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
            }

            .btn-danger:hover {
                background: #c82333;
                transform: translateY(-2px);
                box-shadow: 0 6px 10px rgba(220, 53, 69, 0.3);
            }

            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .content-wrapper {
                    padding: 15px;
                }

                .card-body {
                    padding: 1.2rem;
                }

                .file-card {
                    padding: 1.2rem;
                }
            }
        </style>
    </head>
    <body class="fixed-nav sticky-footer bg-dark" id="page-top">
        <?php include('./constant/layout/header.php'); ?>
        <?php include('./constant/layout/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fa fa-file-text-o"></i> SOP Management</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
                        <li class="breadcrumb-item active">SOP Management</li>
                    </ol>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fa fa-cloud-upload me-2"></i>
                                Upload New SOP File
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=upload" method="POST" enctype="multipart/form-data" id="uploadForm">
                                    <div class="mb-4">
                                        <label for="file" class="form-label">
                                            <i class="fa fa-upload"></i> Choose File
                                        </label>
                                        <input type="file" class="form-control" id="file" name="file" required>
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i>
                                            Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG, MP4, AVI, XLS, XLSX (Max: 10MB)
                                        </small>
                                    </div>
                                    <div class="mb-4">
                                        <label for="description" class="form-label">
                                            <i class="fa fa-align-left"></i> Description
                                        </label>
                                        <textarea class="form-control" id="description" name="description" rows="3" 
                                            placeholder="Enter a brief description about this SOP file..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-upload">
                                        <i class="fa fa-cloud-upload"></i>
                                        Upload File
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-folder-open-o"></i>
                                SOP Files List
                            </div>
                            <div class="card-body">
                                <div class="row" id="filesContainer">
                                    <?php
                                    $sop_dir = "sop_files/";
                                    if (!file_exists($sop_dir)) {
                                        mkdir($sop_dir, 0777, true);
                                    }
                                    
                                    $files = scandir($sop_dir);
                                    $has_files = false;
                                    
                                    foreach ($files as $file) {
                                        if ($file != "." && $file != ".." && $file != "descriptions.json") {
                                            $has_files = true;
                                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $icon = "fa-file-o";
                                            $color = "#6c757d";
                                            
                                            if (in_array($ext, ['pdf'])) {
                                                $icon = "fa-file-pdf-o";
                                                $color = "#dc3545";
                                            } elseif (in_array($ext, ['doc', 'docx'])) {
                                                $icon = "fa-file-word-o";
                                                $color = "#007bff";
                                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                                $icon = "fa-file-image-o";
                                                $color = "#28a745";
                                            } elseif (in_array($ext, ['mp4', 'avi'])) {
                                                $icon = "fa-file-video-o";
                                                $color = "#6f42c1";
                                            } elseif (in_array($ext, ['xls', 'xlsx'])) {
                                                $icon = "fa-file-excel-o";
                                                $color = "#217346";
                                            }
                                            
                                            // Lấy tên gốc của file (bỏ timestamp)
                                            $original_name = substr($file, 15); // Bỏ qua 14 ký tự đầu (YYYYMMDDHHmmss_)
                                            
                                            // Đọc description từ file json
                                            $desc = "";
                                            $json_file = $sop_dir . "descriptions.json";
                                            if (file_exists($json_file)) {
                                                $descriptions = json_decode(file_get_contents($json_file), true);
                                                if (isset($descriptions[$file])) {
                                                    $desc = $descriptions[$file];
                                                }
                                            }
                                            ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="file-card">
                                                    <div class="file-icon">
                                                        <i class="fa <?php echo $icon; ?>" style="color: <?php echo $color; ?>"></i>
                                                    </div>
                                                    <h5 class="card-title" title="<?php echo htmlspecialchars($original_name); ?>">
                                                        <i class="fa <?php echo $icon; ?>" style="color: <?php echo $color; ?>"></i>
                                                        <?php echo htmlspecialchars($original_name); ?>
                                                    </h5>
                                                    <div class="file-desc">
                                                        <?php if ($desc): ?>
                                                            <p class="mb-0"><?php echo htmlspecialchars($desc); ?></p>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0 fst-italic">
                                                                <i class="fa fa-info-circle me-1"></i>
                                                                No description provided
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="file-meta">
                                                        <span>
                                                            <i class="fa fa-calendar me-1"></i>
                                                            <?php 
                                                            $file_time = filemtime($sop_dir . $file);
                                                            echo date("d/m/Y", $file_time); 
                                                            ?>
                                                        </span>
                                                        <span>
                                                            <i class="fa fa-file-o me-1"></i>
                                                            <?php echo strtoupper($ext); ?>
                                                        </span>
                                                    </div>
                                                    <div class="btn-group">
                                                        <a href="<?php echo $sop_dir . $file; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fa fa-download me-2"></i>
                                                            Download
                                                        </a>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="confirmDelete('<?php echo htmlspecialchars($file); ?>')">
                                                            <i class="fa fa-trash me-2"></i>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                    
                                    if (!$has_files) {
                                        echo '<div class="col-12">
                                                <div class="empty-state">
                                                    <i class="fa fa-folder-open-o"></i>
                                                    <h5>No SOP Files Yet</h5>
                                                    <p>Upload your first SOP file using the form above</p>
                                                </div>
                                              </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('./constant/layout/footer.php'); ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
        <script>
            // Xác nhận xóa file
            function confirmDelete(filename) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to recover this file!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    heightAuto: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'manage_SOP.php?action=delete&file=' + encodeURIComponent(filename);
                    }
                });
            }
            
            // Upload form submit with validation
            document.getElementById('uploadForm').addEventListener('submit', function(event) {
                const fileInput = document.getElementById('file');
                if (fileInput.files.length > 0) {
                    const fileSize = fileInput.files[0].size;
                    const fileName = fileInput.files[0].name;
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    
                    const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'avi', 'xls', 'xlsx'];
                    if (!allowedTypes.includes(fileExt)) {
                        event.preventDefault();
                        Swal.fire({
                            title: 'Invalid File Type',
                            text: 'Please select a valid document or media file.',
                            icon: 'error',
                            confirmButtonColor: '#28a745'
                        });
                        return;
                    }
                    
                    if (fileSize > 10485760) { // 10MB
                        event.preventDefault();
                        Swal.fire({
                            title: 'File Too Large',
                            text: 'Please select a file smaller than 10MB.',
                            icon: 'error',
                            confirmButtonColor: '#28a745'
                        });
                        return;
                    }
                    
                    // Submit form with loading state
                    Swal.fire({
                        title: 'Uploading...',
                        text: 'Please wait while your file is being uploaded',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            });

            <?php
            // Display success/error messages
            if (isset($_GET['success'])) {
                $success_msg = ($_GET['success'] == 1) ? 'File uploaded successfully!' : 'File deleted successfully!';
                echo "
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Success!',
                            text: '$success_msg',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    });
                ";
            }
            if (isset($_GET['error'])) {
                $error_msg = '';
                switch($_GET['error']) {
                    case 'file_too_large':
                        $error_msg = 'File size exceeds limit (10MB)';
                        break;
                    case 'invalid_file_type':
                        $error_msg = 'Invalid file type';
                        break;
                    case 'upload_failed':
                        $error_msg = 'Failed to upload file';
                        break;
                    case 'delete_failed':
                        $error_msg = 'Failed to delete file';
                        break;
                    case 'file_not_found':
                        $error_msg = 'File not found';
                        break;
                    default:
                        $error_msg = 'An error occurred';
                }
                echo "
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Error!',
                            text: '$error_msg',
                            icon: 'error',
                            confirmButtonColor: '#28a745'
                        });
                    });
                ";
            }
            ?>
        </script>
    </body>
    </html>
    <?php
}
?>