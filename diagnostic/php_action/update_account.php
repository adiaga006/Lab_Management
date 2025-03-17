<?php
require_once 'core.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if (!isset($_SESSION['userId'])) {
    $response['message'] = 'Not authorized';
    echo json_encode($response);
    exit();
}

try {
    $userId = $_SESSION['userId'];
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $age = $_POST['age'] ?? null;
    $address = $_POST['address'] ?? '';
    $position = $_POST['position'] ?? '';
    $work_address = $_POST['work_address'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Get user role
    $stmt = $connect->prepare("SELECT role FROM user_infor WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userRole = $result->fetch_assoc()['role'];

    // Validate required fields for non-guest users
    if ($userRole != 3) {
        if (empty($fullname) || empty($age) || empty($address) || empty($position) || empty($work_address)) {
            throw new Exception('All fields are required for staff members');
        }
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $connect->begin_transaction();
    
    $stmt = $connect->prepare("UPDATE user_infor SET 
        username = ?, 
        email = ?,
        fullname = ?,
        age = ?,
        address = ?,
        position = ?,
        work_address = ?
        WHERE user_id = ?");
    
    $stmt->bind_param("sssisssi", 
        $username, 
        $email,
        $fullname,
        $age,
        $address,
        $position,
        $work_address,
        $userId
    );
    $stmt->execute();
    
    if (!empty($newPassword)) {
        if ($newPassword !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        $hashedPassword = sha1($newPassword);
        $stmt = $connect->prepare("UPDATE user_infor SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();
    }
    
    $connect->commit();
    $response['success'] = true;
    $response['message'] = 'Profile updated successfully!';
    
} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollback();
    }
    $response['message'] = $e->getMessage();
} finally {
    echo json_encode($response);
    exit();
}