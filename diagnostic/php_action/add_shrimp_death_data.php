<?php
include('../constant/connect.php');

header('Content-Type: application/json'); // Đảm bảo trả về JSON response

$response = ['success' => false, 'messages' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ yêu cầu POST
    $caseStudyId = $_POST['case_study_id'] ?? null;
    $treatmentName = $_POST['treatment_name'] ?? null;
    $productApplication = $_POST['product_application'] ?? null;
    $deathSample = isset($_POST['death_sample']) ? intval($_POST['death_sample']) : null;
    $testTime = $_POST['test_time'] ?? null;
    $rep = isset($_POST['rep']) ? intval($_POST['rep']) : null;

    // Kiểm tra dữ liệu cần thiết
    if (!$caseStudyId || !$treatmentName || !$testTime || !$rep) {
        $response['messages'] = 'Missing required fields.';
        echo json_encode($response);
        exit;
    }

    // Xử lý testTime
    try {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $testTime);
        if (!$dateTime) {
            $response['messages'] = "Invalid date format for test_time.";
            echo json_encode($response);
            exit;
        }
        $testDate = $dateTime->format('Y-m-d'); // Ngày từ test_time
    } catch (Exception $e) {
        $response['messages'] = "Error parsing date for test_time.";
        echo json_encode($response);
        exit;
    }

    // Lấy `num_reps` từ bảng `case_study`
    $stmt = $connect->prepare("SELECT num_reps FROM case_study WHERE case_study_id = ?");
    $stmt->bind_param("s", $caseStudyId);
    $stmt->execute();
    $stmt->bind_result($numReps);
    $stmt->fetch();
    $stmt->close();

    if (!$numReps) {
        $response['messages'] = 'Error: Case study not found.';
        echo json_encode($response);
        exit;
    }

    // Kiểm tra nếu rep vượt quá num_reps
    if ($rep > $numReps) {
        $response['messages'] = "Error: Rep $rep exceeds the maximum allowed value ($numReps) for this case study.";
        echo json_encode($response);
        exit;
    }

    // Kiểm tra trùng lặp (trùng ngày, khung giờ, treatment_name, và rep)
    $stmt = $connect->prepare("
        SELECT COUNT(*) 
        FROM shrimp_death_data 
        WHERE case_study_id = ? AND treatment_name = ? AND DATE(test_time) = ? AND HOUR(test_time) = HOUR(?) AND rep = ?
    ");
    $stmt->bind_param("ssssi", $caseStudyId, $treatmentName, $testDate, $testTime, $rep);
    $stmt->execute();
    $stmt->bind_result($isDuplicateRep);
    $stmt->fetch();
    $stmt->close();

    if ($isDuplicateRep > 0) {
        $response['messages'] = "Error: Rep already exists for $treatmentName on $testDate at the same hour.";
        echo json_encode($response);
        exit;
    }

    // Chèn dữ liệu mới nếu không có lỗi
    $stmt = $connect->prepare("
        INSERT INTO shrimp_death_data (case_study_id, treatment_name, product_application, death_sample, test_time, rep) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssisi", $caseStudyId, $treatmentName, $productApplication, $deathSample, $testTime, $rep);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Data added successfully.';
    } else {
        $response['messages'] = 'Error adding data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
