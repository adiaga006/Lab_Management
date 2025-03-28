<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => '', 'deleted_count' => 0];

// Kiểm tra xem có dữ liệu POST và mảng entry_ids không
if ($_POST && isset($_POST['entry_ids'])) {
    $entryIds = $_POST['entry_ids'];
    $deletedCount = 0;
    
    // Bắt đầu transaction
    $connect->begin_transaction();
    
    try {
        foreach ($entryIds as $entryId) {
            // Bảo vệ dữ liệu đầu vào bằng prepared statement
            $sql = "DELETE FROM entry_data WHERE entry_data_id = ?";
            $stmt = $connect->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Query preparation error: ' . $connect->error);
            }
            
            $stmt->bind_param("i", $entryId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $deletedCount++;
            }
            
            $stmt->close();
        }
        
        // Commit transaction nếu thành công
        $connect->commit();
        
        $response['success'] = true;
        $response['deleted_count'] = $deletedCount;
        $response['messages'] = "Successfully deleted $deletedCount items";
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $connect->rollback();
        $response['messages'] = 'Error when deleting data: ' . $e->getMessage();
    }
} else {
    $response['messages'] = 'No data received to delete';
}

$connect->close();
echo json_encode($response);
?> 