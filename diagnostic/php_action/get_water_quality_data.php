<?php
include('../constant/connect.php');

$response = ['success' => false, 'data' => [], 'messages' => ''];

if ($_POST) {
    $entryId = $_POST['id'];  // ID của bản ghi cần lấy dữ liệu

    // Truy vấn lấy thông tin chi tiết của bản ghi water_quality dựa vào id
    $stmt = $connect->prepare("SELECT * FROM water_quality WHERE id = ?");
    $stmt->bind_param("i", $entryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();  // Lưu thông tin bản ghi vào 'data'
    } else {
        $response['messages'] = 'Không tìm thấy dữ liệu.';
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
?>
