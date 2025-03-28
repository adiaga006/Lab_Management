<?php
include('../constant/connect.php');

// Đặt header để trả về JSON
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['case_study_id']) && isset($_POST['date'])) {
    try {
        $caseStudyId = $_POST['case_study_id'];
        $date = $_POST['date'];
        
        // Cải tiến: Kiểm tra định dạng ngày đơn giản hóa
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception('Invalid date format');
        }
        
        // Sử dụng transaction để đảm bảo toàn vẹn dữ liệu
        $connect->begin_transaction();
        
        // Cải tiến: Sử dụng prepared statement để tránh SQL injection và tối ưu hiệu suất
        $sql = "DELETE FROM shrimp_death_data WHERE case_study_id = ? AND DATE(test_time) = ?";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Query preparation error');
        }
        
        $stmt->bind_param("ss", $caseStudyId, $date);
        $result = $stmt->execute();
        $deletedCount = $stmt->affected_rows;
        
        // Cải tiến: Kiểm tra kết quả thực thi thay vì số lượng bản ghi bị xóa
        // Điều này tránh trường hợp lỗi không phải do không có dữ liệu
        if ($result) {
            $connect->commit();
            $response['success'] = true;
            $response['deleted_count'] = $deletedCount;
            $response['message'] = "Successfully deleted $deletedCount entries";
        } else {
            $connect->rollback();
            throw new Exception('Error executing delete query');
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Cải tiến: Kiểm tra connect trước khi rollback để tránh lỗi
        if (isset($connect) && $connect->ping()) {
            $connect->rollback();
        }
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Missing required parameters';
}

// Đảm bảo đóng kết nối
if (isset($connect)) {
    $connect->close();
}

// Trả về JSON response
echo json_encode($response);
exit;