<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['sop_authenticated']) || $_SESSION['sop_authenticated'] !== true) {
    header("Location: ../manage_SOP.php");
    exit();
}

// Check if file is uploaded
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
                // Create directory if not exists
                $upload_dir = "../sop_files/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
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
                    
                    header("Location: ../manage_SOP.php?success=1");
                } else {
                    header("Location: ../manage_SOP.php?error=upload_failed");
                }
            } else {
                header("Location: ../manage_SOP.php?error=file_too_large");
            }
        } else {
            header("Location: ../manage_SOP.php?error=file_error");
        }
    } else {
        header("Location: ../manage_SOP.php?error=invalid_file_type");
    }
} else {
    header("Location: ../manage_SOP.php");
}
exit();
?>