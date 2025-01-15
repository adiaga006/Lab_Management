<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

try {
    if ($_POST) {
        $id = $_POST['id'];
        $dateCheck = $_POST['date_check'];
        $diets = $_POST['diets'];
        $workDone = $_POST['work_done'];
        $checkStatus = $_POST['check_status'];

        // Kiểm tra xem có cột nào null không
        if (empty($dateCheck) || empty($diets) || empty($workDone) || empty($checkStatus)) {
            $valid['success'] = false;
            $valid['messages'] = "All fields are required. None of the fields can be null.";
            echo json_encode($valid);
            exit();
        }

        $sql = "UPDATE schedule SET date_check = ?, diets = ?, work_done = ?, check_status = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssss", $dateCheck, $diets, $workDone, $checkStatus, $id);

        if ($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Successfully Updated";
        } else {
            throw new Exception($stmt->error);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
?>