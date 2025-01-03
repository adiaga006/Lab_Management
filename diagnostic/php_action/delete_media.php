<?php
// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
include('../constant/connect.php');

header('Content-Type: application/json');

try {
    // Kiểm tra request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Đọc input JSON
    $input = file_get_contents('php://input');
    if (!$input) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (!isset($data['media']) || !is_array($data['media'])) {
        throw new Exception('Invalid request data structure');
    }

    $deletedCount = 0;
    $errors = [];
    $debugPaths = [];

    foreach ($data['media'] as $media) {
        if (!isset($media['path'])) {
            $errors[] = "Invalid media data: path not specified";
            continue;
        }

        $relativePath = $media['path'];
        $filePath = dirname(dirname(__FILE__)) . '/' . ltrim($relativePath, '/');
        
        $debugPaths[] = [
            'relative_path' => $relativePath,
            'full_path' => $filePath,
            'exists' => file_exists($filePath)
        ];

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $deletedCount++;
            } else {
                $errors[] = "Failed to delete: " . basename($filePath);
            }
        } else {
            $errors[] = "File not found: " . basename($filePath);
        }
    }

    echo json_encode([
        'success' => $deletedCount > 0,
        'message' => $deletedCount > 0 ? 
            "Successfully deleted $deletedCount media file(s)" : 
            "Failed to delete any files",
        'errors' => $errors,
        'debug' => [
            'paths' => $debugPaths,
            'script_path' => __FILE__,
            'base_path' => dirname(dirname(__FILE__))
        ]
    ]);

} catch (Exception $e) {
    error_log("Delete media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?> 