<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

try {
    if ($_POST) {
        $caseStudyId = $_POST['case_study_id'];
        $dateCheck = $_POST['date_check'];
        $diets = $_POST['diets'];
        $workDone = $_POST['work_done'];
        $checkStatus = $_POST['check_status'];

        // Kiểm tra xem có cột nào null không
        if (empty($caseStudyId) || empty($dateCheck) || empty($diets) || empty($workDone) || empty($checkStatus)) {
            $valid['success'] = false;
            $valid['messages'] = "All fields are required. None of the fields can be null.";
            echo json_encode($valid);
            exit();
        }

        // Kiểm tra xem đã tồn tại bản ghi nào có cùng case_study_id, date_check và diets chưa
        $checkSql = "SELECT COUNT(*) as count FROM schedule 
                    WHERE case_study_id = ? 
                    AND date_check = ? 
                    AND diets = ?";
        
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("sss", $caseStudyId, $dateCheck, $diets);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Nếu đã tồn tại, trả về thông báo lỗi
            $valid['success'] = false;
            $valid['messages'] = "A schedule with the same date and diets already exists for this case study!";
        } else {
            // Nếu chưa tồn tại, thực hiện thêm mới
            $sql = "INSERT INTO schedule (case_study_id, date_check, diets, work_done, check_status) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sssss", 
                $caseStudyId, 
                $dateCheck, 
                $diets, 
                $workDone, 
                $checkStatus
            );

            if($stmt->execute()) {
                $valid['success'] = true;
                $valid['messages'] = "Successfully Added";
            } else {
                throw new Exception($stmt->error);
            }
            
            $stmt->close();
        }
        $checkStmt->close();
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
?>