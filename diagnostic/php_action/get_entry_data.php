<?php
include('../constant/connect.php');

$response = ['success' => false, 'data' => []];

if (isset($_POST['entry_data_id'])) {
    $entryDataId = $_POST['entry_data_id'];
    $sql = "SELECT * FROM entry_data WHERE entry_data_id = '$entryDataId'";
    $result = $connect->query($sql);

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['message'] = 'No data found';
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>
