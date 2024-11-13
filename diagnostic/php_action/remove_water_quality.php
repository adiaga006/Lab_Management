<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['id'];  // Ensure the ID name here matches the JS delete function

    $stmt = $connect->prepare("DELETE FROM water_quality WHERE id = ?");
    $stmt->bind_param("i", $entryId);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Water quality data deleted successfully';
    } else {
        $response['messages'] = 'Error deleting water quality data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>
