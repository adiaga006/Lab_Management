<?php
include('../constant/connect.php');

// Đặt header để trả về JSON
header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'deleted_count' => 0];

// Ghi log chi tiết dữ liệu nhận được
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw POST data: " . file_get_contents("php://input"));
error_log("POST array: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Xử lý IDs với nhiều cách khác nhau để đảm bảo tương thích
        $ids = [];
        
        // Cách 1: Kiểm tra mảng $_POST['ids'] trực tiếp - cách hiệu quả nhất
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
        } 
        // Cách 2: Kiểm tra chuỗi JSON 
        elseif (isset($_POST['ids']) && is_string($_POST['ids'])) {
            $decoded = json_decode($_POST['ids'], true);
            if (is_array($decoded)) {
                $ids = array_map('intval', $decoded);
            }
        }
        // Cách 3: Tìm các key dạng ids[index]
        else {
            foreach ($_POST as $key => $value) {
                if (preg_match('/^ids\[\d*\]$/', $key)) {
                    $ids[] = intval($value);
                }
            }
        }
        
        if (empty($ids)) {
            throw new Exception('No valid IDs received to delete');
        }
        
        // Bắt đầu transaction
        $connect->begin_transaction();
        
        // Cải tiến: Sử dụng câu lệnh DELETE IN thay vì xóa từng ID một
        $idPlaceholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM shrimp_death_data WHERE id IN ($idPlaceholders)";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Query preparation error');
        }
        
        // Cải tiến: Bind tất cả tham số một lần thay vì trong vòng lặp
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        
        // Thực thi và kiểm tra
        $result = $stmt->execute();
        $deletedCount = $stmt->affected_rows;
        
        if (!$result) {
            throw new Exception('Query execution error');
        }
        
        $stmt->close();
        $connect->commit();
        
        // Thiết lập response
        $response['success'] = true;
        $response['deleted_count'] = $deletedCount;
        $response['message'] = "Successfully deleted $deletedCount items";
        
    } catch (Exception $e) {
        // Rollback transaction nếu cần
        if (isset($connect) && $connect->ping()) {
            $connect->rollback();
        }
        $response['message'] = 'Error when deleting data: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Đóng kết nối
if (isset($connect)) {
    $connect->close();
}

// Trả về response
echo json_encode($response);
exit;