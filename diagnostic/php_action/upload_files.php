<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

header('Content-Type: application/json');

include('../constant/connect.php');

try {
    $response = array('success' => false, 'message' => '', 'debug' => array());

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['files'])) {
            throw new Exception("No files in request");
        }

        $caseStudyId = $_POST['case_study_id'] ?? null;
        $uploadDate = $_POST['upload_date'] ?? null;
        $dayNote = isset($_POST['day_note']) ? $_POST['day_note'] : '';
        $notesFile = $baseDir . "/uploads/case_studies/{$caseStudyId}/day_notes.json";
        // Tạo thư mục nếu chưa tồn tại
        $notesDir = dirname($notesFile);
        if (!file_exists($notesDir)) {
            mkdir($notesDir, 0777, true);
        }

        // Tạo hoặc cập nhật file notes
        $notes = [];
        if (file_exists($notesFile)) {
            $notesContent = file_get_contents($notesFile);
            $notes = json_decode($notesContent, true) ?: [];
        }

        // Thêm note mới
        $notes[$uploadDate] = $dayNote;

        // Lưu file
        file_put_contents($notesFile, json_encode($notes, JSON_PRETTY_PRINT));

        if (!$caseStudyId || !$uploadDate) {
            throw new Exception("Missing required fields");
        }

        // Sửa lại đường dẫn để trỏ tới thư mục assets đồng cấp với diagnostic
        $baseDir = dirname(dirname(__FILE__)); // Lấy thư mục diagnostic
        $assetsDir = dirname($baseDir) . "/assets"; // Lên một cấp và vào thư mục assets

        $imageDir = $assetsDir . "/uploadImage/Shrimp_image/{$caseStudyId}/{$uploadDate}/";
        $videoDir = $assetsDir . "/uploadVideo/{$caseStudyId}/{$uploadDate}/";

        // Log paths for debugging
        $response['debug']['base_dir'] = $baseDir;
        $response['debug']['assets_dir'] = $assetsDir;
        $response['debug']['image_dir'] = $imageDir;
        $response['debug']['video_dir'] = $videoDir;

        // Create directories
        if (!file_exists($imageDir)) {
            if (!mkdir($imageDir, 0777, true)) {
                throw new Exception("Failed to create image directory: " . $imageDir);
            }
        }
        if (!file_exists($videoDir)) {
            if (!mkdir($videoDir, 0777, true)) {
                throw new Exception("Failed to create video directory: " . $videoDir);
            }
        }

        $successImageCount = 0;
        $successVideoCount = 0;
        $errors = array();

        // File type definitions
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $fileName = $_FILES['files']['name'][$key];
            $fileType = $_FILES['files']['type'][$key];
            $fileError = $_FILES['files']['error'][$key];
            $fileSize = $_FILES['files']['size'][$key];

            if ($fileError === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Determine file type
                $isVideo = in_array($fileType, $allowedVideoTypes) || in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);
                $isImage = in_array($fileType, $allowedImageTypes) || in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);

                if (!$isVideo && !$isImage) {
                    $errors[] = "File {$fileName} has invalid format";
                    continue;
                }

                // Set target path
                if ($isVideo) {
                    $index = count(glob($videoDir . "*")) + 1;
                    $newFileName = $index . '.' . $extension;
                    $targetPath = $videoDir . $newFileName;
                    $maxSize = 100 * 1024 * 1024;
                } else {
                    $index = count(glob($imageDir . "*")) + 1;
                    $newFileName = $index . '.' . $extension;
                    $targetPath = $imageDir . $newFileName;
                    $maxSize = 5 * 1024 * 1024;
                }

                if ($fileSize > $maxSize) {
                    $errors[] = "File {$fileName} exceeds size limit";
                    continue;
                }

                if (move_uploaded_file($tmp_name, $targetPath)) {
                    if ($isVideo) {
                        $successVideoCount++;
                    } else {
                        $successImageCount++;
                    }
                } else {
                    $error = error_get_last();
                    $errors[] = "Error uploading {$fileName}: " . ($error['message'] ?? 'Unknown error');
                }
            } else {
                $errors[] = "Upload error for {$fileName}: " . $fileError;
            }
        }

        if ($successImageCount > 0 || $successVideoCount > 0) {
            $response['success'] = true;
            $successMessages = array();

            if ($successImageCount > 0) {
                $successMessages[] = "$successImageCount image" . ($successImageCount > 1 ? "s" : "");
            }
            if ($successVideoCount > 0) {
                $successMessages[] = "$successVideoCount video" . ($successVideoCount > 1 ? "s" : "");
            }

            $response['message'] = "Successfully uploaded " . implode(" and ", $successMessages);
            if (!empty($errors)) {
                $response['message'] .= ". Errors: " . implode(", ", $errors);
            }
        } else {
            $response['message'] = "No files were uploaded successfully. " . implode(", ", $errors);
        }
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
    $response['debug']['exception'] = $e->getMessage();
    error_log($e->getMessage());
}

echo json_encode($response);
exit;
