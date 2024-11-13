<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['id'];
    $salinity = $_POST['salinity'];
    $temperature = $_POST['temperature'];
    $dissolvedOxygen = $_POST['dissolved_oxygen'];
    $pH = $_POST['pH'];
    $alkalinity = $_POST['alkalinity'];
    $tan = $_POST['tan'];
    $nitrite = $_POST['nitrite'];
    $systemType = $_POST['system_type'];

    // Fetch the current `day` to avoid unintended changes
    $stmt = $connect->prepare("SELECT day FROM water_quality WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $stmt->bind_result($day);
    $stmt->fetch();
    $stmt->close();

    // Update fields except `day`
    $stmt = $connect->prepare("UPDATE water_quality 
        SET salinity = ?, 
            temperature = ?, 
            dissolved_oxygen = ?, 
            pH = ?, 
            alkalinity = ?, 
            tan = ?, 
            nitrite = ?, 
            system_type = ? 
        WHERE id = ?");
    $stmt->bind_param("dddddddsi", $salinity, $temperature, $dissolvedOxygen, $pH, $alkalinity, $tan, $nitrite, $systemType, $entryId);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Water quality data updated successfully';
    } else {
        $response['messages'] = 'Error updating water quality data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>
