<?php
include('../constant/connect.php');
header('Content-Type: application/json');

// Bật chế độ báo lỗi trong PHP để kiểm tra
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $death_sample = $_POST['death_sample'] ?? null;

    // Ghi log giá trị nhận được từ AJAX
    file_put_contents('php://stderr', "Received ID: $id, Death Sample: $death_sample\n");

    if ($id && $death_sample !== null) {
        $sql = "UPDATE shrimp_death_data SET death_sample = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);

        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Prepare statement failed: " . $connect->error]);
            exit;
        }

        $stmt->bind_param("ii", $death_sample, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Update successful."]);
        } else {
            echo json_encode(["success" => false, "message" => "Execution failed: " . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid input data: ID or Death Sample is missing."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}

?>
