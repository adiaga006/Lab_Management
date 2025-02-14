<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if ($_POST) {
    $id = $_POST['id'];

    // Kiểm tra xem ID có hợp lệ không
    if (empty($id)) {
        $valid['success'] = false;
        $valid['messages'] = "Invalid ID.";
    } else {
        $sql = "DELETE FROM schedule WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Successfully deleted the schedule.";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while deleting the schedule.";
        }

        $stmt->close();
    }
}

// Đảm bảo không có ký tự thừa trước khi trả về JSON
header('Content-Type: application/json');
echo json_encode($valid); // Đảm bảo rằng bạn đang trả về chuỗi JSON
exit();
?>