<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $caseStudyId = $_POST['case_study_id'];
    $day = $_POST['day'];
    $salinity = $_POST['salinity'];
    $temperature = $_POST['temperature'];
    $dissolvedOxygen = $_POST['dissolved_oxygen'];
    $pH = $_POST['pH'];
    $alkalinity = $_POST['alkalinity'];
    $tan = $_POST['tan'];
    $nitrite = $_POST['nitrite'];
    $systemType = $_POST['system_type'];

    // Convert date format from dd/mm/yyyy to yyyy-mm-dd
    $dayFormatted = date('Y-m-d', strtotime(str_replace('/', '-', $day)));

    // Prepare SQL statement to insert data into water_quality table
    $stmt = $connect->prepare("INSERT INTO water_quality 
        (case_study_id, day, salinity, temperature, dissolved_oxygen, pH, alkalinity, tan, nitrite, system_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddddddds", $caseStudyId, $dayFormatted, $salinity, $temperature, $dissolvedOxygen, $pH, $alkalinity, $tan, $nitrite, $systemType);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Water quality data added successfully';
    } else {
        $response['messages'] = 'Error adding water quality data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>