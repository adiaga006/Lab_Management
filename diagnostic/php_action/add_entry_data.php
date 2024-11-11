<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $caseStudyId = $_POST['case_study_id'];
    $groupId = $_POST['group_id'];
    $treatmentName = $_POST['treatment_name'];
    $productApplication = $_POST['product_application'];
    $survivalSample = $_POST['survival_sample'];
    $labDay = $_POST['lab_day'];

    // Convert date format from dd/mm/yyyy to yyyy-mm-dd
    $labDayFormatted = date('Y-m-d', strtotime(str_replace('/', '-', $labDay)));

    // Prepare SQL statement to insert data without phase_id
    $stmt = $connect->prepare("INSERT INTO entry_data (case_study_id, group_id, treatment_name, product_application, survival_sample, lab_day) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissis", $caseStudyId, $groupId, $treatmentName, $productApplication, $survivalSample, $labDayFormatted);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Entry data added successfully';
    } else {
        $response['messages'] = 'Error adding entry data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>
