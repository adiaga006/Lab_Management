<?php 	
require_once 'core.php';

header('Content-Type: application/json');

$response = array('success' => false, 'messages' => '');

// Lấy case_study_id từ POST request
$caseStudyId = isset($_POST['id']) ? $_POST['id'] : null;

if ($caseStudyId) { 
    try {
        // Kiểm tra case study có tồn tại không
        $checkSql = "SELECT case_study_id FROM case_study WHERE case_study_id = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("s", $caseStudyId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // Xóa case study cụ thể
            $sql = "DELETE FROM case_study WHERE case_study_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("s", $caseStudyId);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['messages'] = "Case study has been deleted successfully";
            } else {
                throw new Exception("Error while deleting the case study");
            }
        } else {
            throw new Exception("Case study not found");
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
} else {
    $response['success'] = false;
    $response['messages'] = "Invalid case study ID";
}

$connect->close();

echo json_encode($response);  
