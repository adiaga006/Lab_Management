<?php
require_once 'core.php';

$valid = array('success' => false, 'messages' => '');
$caseStudyId = $_GET['id'];

try {
    if ($_POST) {
        $caseStudyId = $_GET['id'];
        $caseName = $_POST['editCaseStudyName'];
        $location = $_POST['editLocation'];
        $categoryId = $_POST['editCategoryName'];
        $startDate = date('Y-m-d', strtotime($_POST['editStartDate'])); // Định dạng YYYY-MM-DD
        $status = $_POST['editCaseStudyStatus'];
        $numReps = isset($_POST['editNumReps']) ? intval($_POST['editNumReps']) : 1; // Lấy num_reps từ form, mặc định là 1

        // Lưu thông tin treatments từ form
        $treatments = [];
        if (isset($_POST['treatment_name']) && isset($_POST['product_application'])) {
            for ($i = 0; $i < count($_POST['treatment_name']); $i++) {
                $treatmentName = $_POST['treatment_name'][$i];
                $productApplication = $_POST['product_application'][$i];

                if (isset($treatmentName) && isset($productApplication)) {
                    $treatments[] = [
                        'name' => $treatmentName,
                        'product_application' => $productApplication
                    ];
                }

            }
        }

        // Chuyển treatments thành JSON
        $treatmentsJson = json_encode($treatments);
        // Lưu thông tin phases từ form
        $phases = [];
        if (isset($_POST['phases']) && is_array($_POST['phases'])) {
            foreach ($_POST['phases'] as $phaseName => $phaseDuration) {
                $phases[] = [
                    'name' => $phaseName,
                    'duration' => (int) $phaseDuration
                ];
            }
        }

        // Chuyển phases thành JSON
        $phasesJson = json_encode($phases);

        // Cập nhật dữ liệu
        $sql = "UPDATE case_study SET 
                    case_name = ?, 
                    location = ?, 
                    start_date = ?, 
                    categories_id = ?, 
                    status = ?, 
                    phases = ?, 
                    treatment = ?, 
                    num_reps = ?
                WHERE case_study_id = ?";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }

        // Bind các tham số với kiểu dữ liệu chính xác
        $stmt->bind_param(
            "sssisssis",
            $caseName,
            $location,
            $startDate,
            $categoryId,
            $status,
            $phasesJson,
            $treatmentsJson,
            $numReps,
            $caseStudyId
        );
        if ($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Successfully Updated";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
} finally {
    $connect->close();
}

// Trả về phản hồi JSON
echo json_encode($valid);
exit;
?>