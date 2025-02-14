<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

try {
    if ($_POST) {
        $id = $_POST['id'];
        $dateCheck = $_POST['date_check'];
        $diets = $_POST['diets'];
        $workDone = $_POST['work_done'];
        $checkStatus = $_POST['check_status'];
        $updateAllInDate = isset($_POST['update_all_in_date']) && $_POST['update_all_in_date'] === 'true';

        // Kiểm tra các trường bắt buộc
        if (empty($dateCheck) || empty($diets) || empty($workDone) || empty($checkStatus)) {
            throw new Exception("All fields are required");
        }

        // Bắt đầu transaction
        $connect->begin_transaction();

        try {
            if ($updateAllInDate) {
                // Lấy case_study_id từ schedule hiện tại
                $sqlGetCaseStudy = "SELECT case_study_id FROM schedule WHERE id = ?";
                $stmtGetCaseStudy = $connect->prepare($sqlGetCaseStudy);
                $stmtGetCaseStudy->bind_param("i", $id);
                $stmtGetCaseStudy->execute();
                $resultCaseStudy = $stmtGetCaseStudy->get_result();
                $caseStudyData = $resultCaseStudy->fetch_assoc();
                $caseStudyId = $caseStudyData['case_study_id'];
                $stmtGetCaseStudy->close();

                // Cập nhật tất cả schedule trong cùng ngày
                $sqlUpdateAll = "UPDATE schedule 
                               SET check_status = ? 
                               WHERE case_study_id = ? AND date_check = ?";
                $stmtUpdateAll = $connect->prepare($sqlUpdateAll);
                $stmtUpdateAll->bind_param("sss", $checkStatus, $caseStudyId, $dateCheck);
                $stmtUpdateAll->execute();
                $stmtUpdateAll->close();

                // Cập nhật schedule hiện tại với đầy đủ thông tin
                $sqlUpdateCurrent = "UPDATE schedule 
                                   SET date_check = ?, diets = ?, work_done = ? 
                                   WHERE id = ?";
                $stmtUpdateCurrent = $connect->prepare($sqlUpdateCurrent);
                $stmtUpdateCurrent->bind_param("sssi", $dateCheck, $diets, $workDone, $id);
                $stmtUpdateCurrent->execute();
                $stmtUpdateCurrent->close();
            } else {
                // Cập nhật chỉ schedule hiện tại
                $sql = "UPDATE schedule 
                       SET date_check = ?, diets = ?, work_done = ?, check_status = ? 
                       WHERE id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("ssssi", $dateCheck, $diets, $workDone, $checkStatus, $id);
                $stmt->execute();
                $stmt->close();
            }

            // Commit transaction
            $connect->commit();
            $valid['success'] = true;
            $valid['messages'] = "Successfully Updated";

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $connect->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
?>