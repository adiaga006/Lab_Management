<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['entry_data_id'];
    $treatmentName = $_POST['treatment_name'];
    $productApplication = $_POST['product_application'];
    $survivalSample = $_POST['survival_sample'];
    $labDay = $_POST['lab_day'];

    $sql = "UPDATE entry_data SET treatment_name = '$treatmentName', product_application = '$productApplication', survival_sample = '$survivalSample', lab_day = '$labDay' WHERE entry_data_id = '$entryId'";

    if ($connect->query($sql) === TRUE) {
        $response['success'] = true;
        $response['messages'] = 'Entry updated successfully';
    } else {
        $response['messages'] = 'Error updating entry: ' . $connect->error;
    }
}

$connect->close();
echo json_encode($response);
?>
