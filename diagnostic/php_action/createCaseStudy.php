<?php 
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

try {
    if ($_POST) {	
        $caseStudyId = $_POST['case_study_id'];
        $caseName = $_POST['case_name'];
        $location = $_POST['location'];
        $categoryId = $_POST['categories_id'];
        $startDate = $_POST['start_date'];
        $pondId = isset($_POST['pond_id']) ? $_POST['pond_id'] : NULL; // Xử lý nếu pond_id không có giá trị
        $status = $_POST['status'];
        $numReps = isset($_POST['num_reps']) ? intval($_POST['num_reps']) : 1;

        // Xử lý dữ liệu phases
        $phases = [];
        if (isset($_POST['phase_name']) && isset($_POST['phase_duration'])) {
            for ($i = 0; $i < count($_POST['phase_name']); $i++) {
                $phaseName = $_POST['phase_name'][$i];
                $phaseDuration = $_POST['phase_duration'][$i];

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

        // Kiểm tra xem case_study_id đã tồn tại chưa
        $checkSql = "SELECT * FROM case_study WHERE case_study_id = ?";
        $stmt = $connect->prepare($checkSql);
        if (!$stmt) throw new Exception("Prepare failed: " . $connect->error);

        $stmt->bind_param("s", $caseStudyId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $valid['success'] = false;
            $valid['messages'] = "Case Study ID is available. Please choose another ID.";
        } else {
            // Chèn case study mới
            $sql = "INSERT INTO case_study (case_study_id, case_name, location, categories_id, start_date, pond_id, status, phases,num_reps) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)";
            $stmt = $connect->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $connect->error);

            $stmt->bind_param("sssissisi", $caseStudyId, $caseName, $location, $categoryId, $startDate, $pondId, $status, $phasesJson, $numReps);

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
