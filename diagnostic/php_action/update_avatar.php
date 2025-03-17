<?php
require_once 'core.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if (!isset($_SESSION['userId'])) {
    $response['message'] = 'Not authorized';
    echo json_encode($response);
    exit();
}

try {
    if (!isset($_FILES['avatar'])) {
        throw new Exception('No file uploaded');
    }

    $userId = $_SESSION['userId'];
    
    // Create uploads directory if it doesn't exist
    $uploadDir = dirname(dirname(__FILE__)) . '/assets/uploads/avatars/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png');
    
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, JPEG & PNG files are allowed.');
    }
    
    // Xóa avatar cũ nếu tồn tại
    $oldAvatarPath = $uploadDir . 'user_' . $userId . '.*';
    array_map('unlink', glob($oldAvatarPath));
    
    $fileName = 'user_' . $userId . '.' . $fileExtension;
    $targetFile = $uploadDir . $fileName;
    
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
        throw new Exception('Error uploading file');
    }
    
    $avatarPath = './assets/uploads/avatars/' . $fileName;
    
    $response['success'] = true;
    $response['message'] = 'Avatar updated successfully';
    $response['avatar_path'] = $avatarPath;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    echo json_encode($response);
    exit();
}