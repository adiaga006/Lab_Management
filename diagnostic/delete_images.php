<?php
session_start();
include('./constant/connect.php');
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['images']) || empty($data['images'])) {
        throw new Exception('No images selected');
    }

    $success = true;
    $errors = [];
    $deletedFiles = [];

    foreach ($data['images'] as $imagePath) {
        // Chuyển đường dẫn tương đối thành đường dẫn tuyệt đối
        $relativePath = str_replace('../', '', $imagePath);
        $fullPath = __DIR__ . '/../' . $relativePath;
        
        if (file_exists($fullPath)) {
            if (unlink($fullPath)) {
                $deletedFiles[] = $imagePath;
            } else {
                $success = false;
                $errors[] = "Failed to delete: " . basename($imagePath);
            }
        }
    }

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Images deleted successfully' : 'Some images could not be deleted',
        'errors' => $errors,
        'deletedFiles' => $deletedFiles
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 