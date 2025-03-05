<?php
require_once 'core.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    try {
        $stmt = $connect->prepare("DELETE FROM shrimp_weight WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Record deleted successfully';
        } else {
            $response['message'] = 'Error deleting record';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);