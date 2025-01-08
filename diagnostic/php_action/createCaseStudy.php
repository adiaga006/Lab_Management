<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

try {
    if ($_POST) {
        // Lấy user_id từ session
        $userId = $_SESSION['userId'];
        
        $caseStudyId = $_POST['case_study_id'];
        $caseName = $_POST['case_name'];
        $location = $_POST['location'];
        $categoryId = $_POST['categories_id'];
        $startDate = $_POST['start_date'];
        $pondId = isset($_POST['pond_id']) ? $_POST['pond_id'] : NULL;
        $status = $_POST['status'];
        
        // Xử lý dữ liệu phases
        $phases = [];
        if (isset($_POST['phase_name']) && isset($_POST['phase_duration'])) {
            for ($i = 0; $i < count($_POST['phase_name']); $i++) {
                $phaseName = $_POST['phase_name'][$i];
                $phaseDuration = $_POST['phase_duration'][$i];
        
                // Chỉ thêm vào nếu các giá trị không trống
                if (!empty($phaseName) && !empty($phaseDuration)) {
                    $phases[] = [
                        'name' => $phaseName,
                        'duration' => $phaseDuration
                    ];
                }
            }
        }
        

        // Chuyển phases thành JSON
        $phasesJson = json_encode($phases);

        // Xử lý danh sách treatments với num_reps riêng
        $treatments = [];
        if (isset($_POST['treatment_name']) && isset($_POST['num_reps']) && isset($_POST['product_application'])) {
            for ($i = 0; $i < count($_POST['treatment_name']); $i++) {
                $treatmentName = $_POST['treatment_name'][$i];
                $productApplication = $_POST['product_application'][$i];
                $numReps = isset($_POST['num_reps'][$i]) ? intval($_POST['num_reps'][$i]) : 1;

                if (!empty($treatmentName)) {
                    $treatments[] = [
                        'name' => $treatmentName,
                        'product_application' => $productApplication,
                        'num_reps' => $numReps
                    ];
                }
            }
        }

        // Chuyển treatments thành JSON
        $treatmentsJson = json_encode($treatments);

        // Kiểm tra case_study_id đã tồn tại
        $checkSql = "SELECT * FROM case_study WHERE case_study_id = ?";
        $stmt = $connect->prepare($checkSql);
        if (!$stmt)
            throw new Exception("Prepare failed: " . $connect->error);

        $stmt->bind_param("s", $caseStudyId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $valid['success'] = false;
            $valid['messages'] = "Case Study ID is available. Please choose another ID.";
        } else {
            // Thêm user_id vào câu query INSERT
            $sql = "INSERT INTO case_study (case_study_id, case_name, location, categories_id, start_date, pond_id, status, treatment, phases, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $connect->prepare($sql);
            if (!$stmt)
                throw new Exception("Prepare failed: " . $connect->error);

            $stmt->bind_param(
                "sssisisssi", // Thêm j cho user_id (integer)
                $caseStudyId,
                $caseName,
                $location,
                $categoryId,
                $startDate,
                $pondId,
                $status,
                $treatmentsJson,
                $phasesJson,
                $userId // Thêm user_id vào bind_param
            );

            if ($stmt->execute()) {
                $valid['success'] = true;
                $valid['messages'] = "Successfully Added";
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
exit;
?>