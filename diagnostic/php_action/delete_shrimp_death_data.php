<?php
include('../constant/connect.php');

header('Content-Type: application/json'); // Ensure JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID from the POST request
    $entryId = $_POST['id'] ?? null;

    if (!$entryId) {
        echo json_encode(["success" => false, "message" => "Missing entry ID."]);
        return;
    }

    // Delete the record
    $sql = "DELETE FROM shrimp_death_data WHERE id = ?";
    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement."]);
        return;
    }

    $stmt->bind_param("i", $entryId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete data."]);
    }

    $stmt->close();
}
?>
