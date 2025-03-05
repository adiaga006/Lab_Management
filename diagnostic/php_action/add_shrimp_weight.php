<?php
require_once 'core.php';

$response = array('success' => false, 'messages' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseStudyId = $_POST['case_study_id'];
    $weights = $_POST['weights'];
    
    try {
        $stmt = $connect->prepare("INSERT INTO shrimp_weight (case_study_id, weight, no_shrimp) VALUES (?, ?, ?)");
        
        foreach ($weights as $index => $weight) {
            $noShrimp = $index + 1;
            $stmt->bind_param("sdi", $caseStudyId, $weight, $noShrimp);
            $stmt->execute();
        }
        
        $response['success'] = true;
        $response['messages'] = 'Data added successfully';
    } catch (Exception $e) {
        $response['messages'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);