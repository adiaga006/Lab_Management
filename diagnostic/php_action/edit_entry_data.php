<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['entry_data_id'];
    $treatmentName = $_POST['treatment_name'];
    $productApplication = $_POST['product_application'];
    $survivalSample = $_POST['survival_sample'];
    $feedingWeight = $_POST['feeding_weight'];

    // Luôn luôn lấy lab_day hiện tại từ cơ sở dữ liệu
    $stmt = $connect->prepare("SELECT lab_day FROM entry_data WHERE entry_data_id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $stmt->bind_result($labDay);
    $stmt->fetch();
    $stmt->close();

    // Cập nhật các trường khác, giữ nguyên lab_day
    $stmt = $connect->prepare("UPDATE entry_data 
        SET treatment_name = ?, 
            product_application = ?, 
            survival_sample = ?, 
            lab_day = ?, 
            feeding_weight = ? 
        WHERE entry_data_id = ?");
    $stmt->bind_param("ssdsdi", $treatmentName, $productApplication, $survivalSample, $labDay, $feedingWeight, $entryId);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Entry updated successfully';
    } else {
        $response['messages'] = 'Error updating entry: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>
