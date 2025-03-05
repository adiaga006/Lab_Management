<?php
require_once 'core.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $weight = $_POST['weight'];
    
    try {
        $stmt = $connect->prepare("UPDATE shrimp_weight SET weight = ? WHERE id = ?");
        $stmt->bind_param("di", $weight, $id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Weight updated successfully';
        } else {
            $response['message'] = 'Error updating weight';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);