<?php
include('../constant/connect.php');

$response = ['success' => false, 'messages' => ''];

if ($_POST) {
    $caseStudyId = $_POST['case_study_id'];
    $treatmentName = $_POST['treatment_name'];
    $productApplication = $_POST['product_application'];
    $survivalSample = $_POST['survival_sample'];
    $labDay = $_POST['lab_day'];
    $feedingWeight = $_POST['feeding_weight'];
    $rep = $_POST['rep']; // Lấy giá trị rep từ form

    // Xử lý labDay (dd/mm/yyyy -> yyyy-mm-dd)
    try {
        $dateTime = DateTime::createFromFormat('Y-m-d', $labDay);
        if (!$dateTime) {
            $response['messages'] = "Invalid date format for lab_day";
            echo json_encode($response);
            exit;
        }
        $labDayFormatted = $dateTime->format('Y-m-d'); // Chuyển sang định dạng yyyy-mm-dd
        $labDayDisplay = $dateTime->format('d-m-Y'); // Hiển thị dạng dd-mm-yyyy
    } catch (Exception $e) {
        $response['messages'] = "Error parsing date for lab_day.";
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

    // Kiểm tra nếu giá trị `rep` nhập vào vượt quá `num_reps`
    if ($rep > $numReps) {
        $response['messages'] = "Error: Rep $rep exceeds the maximum allowed value ($numReps) for this case study.";
        echo json_encode($response);
        exit;
    }

    // Kiểm tra số lần `rep` đã tồn tại cho `treatment_name` và `lab_day`
    $stmt = $connect->prepare("SELECT COUNT(*) AS currentReps FROM entry_data WHERE case_study_id = ? AND treatment_name = ? AND lab_day = ?");
    $stmt->bind_param("sss", $caseStudyId, $treatmentName, $labDayFormatted);
    $stmt->execute();
    $stmt->bind_result($currentReps);
    $stmt->fetch();
    $stmt->close();

    if ($currentReps >= $numReps) {
        $response['messages'] = "Error: Maximum reps ($numReps) exceeded for $treatmentName on $labDayDisplay.";
        echo json_encode($response);
        exit;
    }

    // Kiểm tra nếu `rep` bị trùng
    $stmt = $connect->prepare("SELECT COUNT(*) FROM entry_data WHERE case_study_id = ? AND treatment_name = ? AND lab_day = ? AND rep = ?");
    $stmt->bind_param("sssi", $caseStudyId, $treatmentName, $labDayFormatted, $rep);
    $stmt->execute();
    $stmt->bind_result($isDuplicateRep);
    $stmt->fetch();
    $stmt->close();

    if ($isDuplicateRep > 0) {
        $response['messages'] = "Error: Data of Rep $rep already exists for $treatmentName on $labDayDisplay.";
        echo json_encode($response);
        exit;
    }

    // Kiểm tra logic `survival_sample` cho ngày trước đó
    $stmt = $connect->prepare("
        SELECT survival_sample 
        FROM entry_data 
        WHERE case_study_id = ? AND treatment_name = ? AND rep = ? AND lab_day < ?
        ORDER BY lab_day DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ssis", $caseStudyId, $treatmentName, $rep, $labDayFormatted);
    $stmt->execute();
    $stmt->bind_result($previousSurvivalSample);
    $stmt->fetch();
    $stmt->close();

    // Nếu có dữ liệu trước đó, kiểm tra logic `survival_sample`
    if ($previousSurvivalSample !== null && $survivalSample > $previousSurvivalSample) {
        $response['messages'] = "Error: Survival sample ($survivalSample) must be less than or equal to the previous day's value ($previousSurvivalSample) for the same treatment and rep.";
        echo json_encode($response);
        exit;
    }

    // Thêm dữ liệu vào bảng `entry_data`
    $stmt = $connect->prepare("INSERT INTO entry_data (case_study_id, treatment_name, product_application, survival_sample, lab_day, feeding_weight, rep) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsdi", $caseStudyId, $treatmentName, $productApplication, $survivalSample, $labDayFormatted, $feedingWeight, $rep);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Entry data added successfully.';
    } else {
        $response['messages'] = 'Error adding entry data: ' . $stmt->error;
    }

    $stmt->close();
}

$connect->close();
echo json_encode($response);
