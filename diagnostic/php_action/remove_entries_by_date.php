<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => '', 'deleted_count' => 0];

// Kiểm tra xem có dữ liệu POST và ngày không
if ($_POST && isset($_POST['case_study_id']) && isset($_POST['date'])) {
    $caseStudyId = $_POST['case_study_id'];
    $date = $_POST['date'];
    
    // Kiểm tra định dạng ngày
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $response['messages'] = 'Invalid date format. Required format is YYYY-MM-DD';
        echo json_encode($response);
        exit;
    }
    
    // Bắt đầu transaction
    $connect->begin_transaction();
    
    try {
        // Xóa tất cả dữ liệu theo ngày và case study ID
        $sql = "DELETE FROM entry_data WHERE case_study_id = ? AND lab_day = ?";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Query preparation error: ' . $connect->error);
        }
        
        $stmt->bind_param("ss", $caseStudyId, $date);
        $stmt->execute();
        
        $deletedCount = $stmt->affected_rows;
        $stmt->close();
        
        // Commit transaction nếu thành công
        $connect->commit();
        
        if ($deletedCount > 0) {
            $response['success'] = true;
            $response['deleted_count'] = $deletedCount;
            $response['messages'] = "Successfully deleted $deletedCount items";
        } else {
            $response['messages'] = "No data deleted for date $date";
        }
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $connect->rollback();
        $response['messages'] = 'Error when deleting data: ' . $e->getMessage();
    }
} else {
    $response['messages'] = 'Missing case study ID or date information';
}

$connect->close();
echo json_encode($response);
?> 