<?php
include('../constant/connect.php');

$caseStudyId = isset($_POST['case_study_id']) ? $_POST['case_study_id'] : 0;

if ($caseStudyId) {
    $recentEntriesSql = "
        SELECT treatment_name, 
               DATE_FORMAT(lab_day, '%d-%m-%Y') AS lab_day, 
               survival_sample, 
               feeding_weight,
               rep
        FROM entry_data 
        WHERE case_study_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5"; // Fetch 5 most recent entries
    $stmt = $connect->prepare($recentEntriesSql);
    $stmt->bind_param("s", $caseStudyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentEntries = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $recentEntries]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid case_study_id']);
}
?>
