<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['entry_data_id'];

    $sql = "DELETE FROM entry_data WHERE entry_data_id = '$entryId'";

    if ($connect->query($sql) === TRUE) {
        $response['success'] = true;
        $response['messages'] = 'Entry deleted successfully';
    } else {
        $response['messages'] = 'Error deleting entry: ' . $connect->error;
    }
}

$connect->close();
echo json_encode($response);
?>
