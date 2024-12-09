<?php
include('../constant/connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseStudyId = $_POST['case_study_id'] ?? null;

    if (!$caseStudyId) {
        echo json_encode(["success" => false, "message" => "case_study_id is missing."]);
        exit;
    }

    $sql = "SELECT treatment_name, test_time, rep, death_sample
            FROM shrimp_death_data
            WHERE case_study_id = ?
            ORDER BY created_at DESC
            LIMIT 5";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('s', $caseStudyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }

    echo json_encode(["success" => true, "data" => $entries]);
    exit;
}
?>
