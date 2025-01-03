<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

// Xử lý form submission
if ($_POST) {
    $caseStudyId = $_POST['case_study_id'];
    $day = $_POST['day'];
    
    // Xử lý tất cả các trường đều có thể null
    $salinity = $_POST['salinity'] === '' ? null : $_POST['salinity'];
    $temperature = $_POST['temperature'] === '' ? null : $_POST['temperature'];
    $dissolvedOxygen = $_POST['dissolved_oxygen'] === '' ? null : $_POST['dissolved_oxygen'];
    $pH = $_POST['pH'] === '' ? null : $_POST['pH'];
    $alkalinity = $_POST['alkalinity'] === '' ? null : $_POST['alkalinity'];
    $tan = $_POST['tan'] === '' ? null : $_POST['tan'];
    $nitrite = $_POST['nitrite'] === '' ? null : $_POST['nitrite'];
    $systemType = $_POST['system_type'];

    // Convert date format
    $dayFormatted = date('Y-m-d', strtotime(str_replace('/', '-', $day)));

    // Prepare SQL statement
    $stmt = $connect->prepare("INSERT INTO water_quality 
        (case_study_id, day, salinity, temperature, dissolved_oxygen, pH, alkalinity, tan, nitrite, system_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind parameters với kiểu dữ liệu phù hợp
    $stmt->bind_param("ssddddddds", 
        $caseStudyId, 
        $dayFormatted, 
        $salinity,
        $temperature, 
        $dissolvedOxygen, 
        $pH, 
        $alkalinity, 
        $tan, 
        $nitrite, 
        $systemType
    );

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