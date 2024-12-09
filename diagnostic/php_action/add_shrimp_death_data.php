<?php
include('../constant/connect.php');

header('Content-Type: application/json'); // Đảm bảo trả về JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ yêu cầu POST
    $caseStudyId = $_POST['case_study_id'] ?? null;
    $treatmentName = $_POST['treatment_name'] ?? null;
    $productApplication = $_POST['product_application'] ?? null;
    $deathSample = isset($_POST['death_sample']) ? intval($_POST['death_sample']) : null;
    $testTime = $_POST['test_time'] ?? null; // Thời gian dạng đầu vào
    $rep = isset($_POST['rep']) ? intval($_POST['rep']) : null;



    // Xử lý testTime
    if ($testTime) {
        try {
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $testTime);
            if (!$dateTime) {
                echo json_encode(["success" => false, "message" => "Invalid date format for test_time."]);
                return;
            }
            $testTime = $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error parsing date for test_time."]);
            return;
        }
    }

    // Kiểm tra dữ liệu đầu vào hợp lệ
        $sql = "INSERT INTO shrimp_death_data (case_study_id, treatment_name, product_application, death_sample, test_time, rep) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);


           $stmt->bind_param("sssisi", $caseStudyId, $treatmentName, $productApplication, $deathSample, $testTime, $rep);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Data added successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add data."]);
            }
            $stmt->close();
        }
?>
