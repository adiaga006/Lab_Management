<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['sop_authenticated']) || $_SESSION['sop_authenticated'] !== true) {
    header("Location: ../manage_SOP.php");
    exit();
}

if (isset($_GET['file'])) {
    $filename = $_GET['file'];
    $file_path = "../sop_files/" . $filename;
    
    // Basic security check to prevent directory traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        header("Location: ../manage_SOP.php?error=invalid_filename");
        exit();
    }
    
    // Check if file exists
    if (file_exists($file_path)) {
        // Delete the file
        if (unlink($file_path)) {
            // Xóa description trong file JSON
            $json_file = "../sop_files/descriptions.json";
            if (file_exists($json_file)) {
                $descriptions = json_decode(file_get_contents($json_file), true);
                if (isset($descriptions[$filename])) {
                    unset($descriptions[$filename]);
                    file_put_contents($json_file, json_encode($descriptions, JSON_UNESCAPED_UNICODE));
                }
            }
            
            // Also delete description file if exists
            $desc_file = "../sop_files/" . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
            if (file_exists($desc_file)) {
                unlink($desc_file);
            }
            header("Location: ../manage_SOP.php?success=2");
        } else {
            header("Location: ../manage_SOP.php?error=delete_failed");
        }
    } else {
        header("Location: ../manage_SOP.php?error=file_not_found");
    }
} else {
    header("Location: ../manage_SOP.php");
}
exit();
?> 