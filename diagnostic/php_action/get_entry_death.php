<?php
include('../constant/connect.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}


$entryId = trim($_POST['id'] ?? null);

if (!$entryId) {
    echo json_encode(['success' => false, 'message' => 'Missing entry ID.']);
    exit;
}

try {
    // Log entryId để debug
    error_log("Received entryId: " . $entryId);

    // Chuẩn bị truy vấn
    $stmt = $connect->prepare("SELECT id, treatment_name, product_application, 
    DATE(test_time) AS test_date, 
    HOUR(test_time) AS test_hour, 
    rep, death_sample 
    FROM shrimp_death_data WHERE id = ?");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $connect->error]);
        exit;
    }

    $stmt->bind_param("i", $entryId);
    $stmt->execute();

    // Log thông tin truy vấn
    error_log("Executing query for ID: " . $entryId);

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Entry not found.']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
