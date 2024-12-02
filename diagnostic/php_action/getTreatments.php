<?php
require_once '../constant/connect.php';

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    if (!isset($_GET['case_study_id']) || empty($_GET['case_study_id'])) {
        throw new Exception("Missing or empty case_study_id.");
    }

    $caseStudyId = $_GET['case_study_id'];

    // Truy vấn treatment từ bảng case_study
    $sql = "SELECT treatment FROM case_study WHERE case_study_id = ?";
    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $connect->error);
    }

    $stmt->bind_param("s", $caseStudyId);
    $stmt->execute();
    $stmt->bind_result($treatmentJson);
    $stmt->fetch();
    $stmt->close();

    if (empty($treatmentJson)) {
        throw new Exception("No treatment data found for the given case_study_id.");
    }

    // Kiểm tra JSON
    $treatments = json_decode($treatmentJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to decode JSON: " . json_last_error_msg());
    }

    $response['success'] = true;
    $response['data'] = $treatments;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    $connect->close();
}

header('Content-Type: application/json');
echo json_encode($response);
