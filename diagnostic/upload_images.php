<?php
session_start();
include('./constant/connect.php');

// Ensure we're sending JSON response
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['images']) || !isset($_POST['case_study_id']) || !isset($_POST['upload_date'])) {
        throw new Exception('Missing required fields');
    }

    $uploadDate = $_POST['upload_date'];
    $caseStudyId = $_POST['case_study_id'];
    
    if(empty($uploadDate) || empty($caseStudyId)) {
        throw new Exception('Missing required data');
    }

    if(empty($_FILES['images']['name'][0])) {
        throw new Exception('No images selected');
    }

    $uploadDir = __DIR__ . "/../assets/uploadImage/Shrimp_image/$caseStudyId/$uploadDate";

    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    $uploadSuccess = true;
    $uploadedFiles = [];
    
    // Lấy số thứ tự lớn nhất hiện tại
    $existingFiles = glob("$uploadDir/*");
    $maxIndex = 0;
    foreach ($existingFiles as $file) {
        if (preg_match('/(\d+)\.jpg$/', $file, $matches)) {
            $maxIndex = max($maxIndex, (int)$matches[1]);
        }
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        $maxIndex++;
        $fileName = $maxIndex . '.jpg';
        $targetFilePath = "$uploadDir/$fileName";
        
        if(move_uploaded_file($tmpName, $targetFilePath)) {
            $uploadedFiles[] = $fileName;
        } else {
            throw new Exception('Failed to upload file: ' . $fileName);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Images uploaded successfully',
        'files' => $uploadedFiles
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 