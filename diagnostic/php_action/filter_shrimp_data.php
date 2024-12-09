<?php
// Hiển thị lỗi (chỉ bật trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Đảm bảo đường dẫn chính xác tới connect.php
include('../constant/connect.php');

// Đặt tiêu đề phản hồi là JSON
header('Content-Type: application/json');

// Lấy tham số từ AJAX
$caseStudyId = $_POST['case_study_id'] ?? null;
$filterDate = $_POST['filterDate'] ?? null;

// Kiểm tra tham số đầu vào
if (!$caseStudyId) {
    echo json_encode(['success' => false, 'message' => 'Missing case study ID.']);
    exit;
}

// Xây dựng câu truy vấn
$sql = "SELECT treatment_name, product_application, DATE(test_time) AS test_date, 
               HOUR(test_time) AS test_hour, rep, death_sample, id
        FROM shrimp_death_data
        WHERE case_study_id = ?";
$params = [$caseStudyId];
$types = "s";

if ($filterDate) {
    $sql .= " AND DATE(test_time) = ?";
    $params[] = $filterDate;
    $types .= "s";
}

$sql .= " ORDER BY 
            test_date ASC,
            CASE
                WHEN treatment_name = 'Negative control' THEN 1
                WHEN treatment_name = 'Positive control' THEN 2
                WHEN treatment_name LIKE 'Treatment%' THEN 3
                ELSE 4
            END,
            rep ASC,
            test_hour ASC";

// Chuẩn bị truy vấn
$stmt = $connect->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $connect->error]);
    exit;
}

// Gán tham số và thực thi truy vấn
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . $stmt->error]);
    exit;
}

// Lấy kết quả truy vấn và phân nhóm
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $treatment = $row['treatment_name'];
    $rep = $row['rep'];
    $testDate = $row['test_date'];

    if (!isset($data[$testDate])) {
        $data[$testDate] = [];
    }
    if (!isset($data[$testDate][$treatment])) {
        $data[$testDate][$treatment] = [];
    }
    if (!isset($data[$testDate][$treatment][$rep])) {
        $data[$testDate][$treatment][$rep] = [];
    }

    $data[$testDate][$treatment][$rep][] = [
        'hour' => $row['test_hour'],
        'death_sample' => $row['death_sample'],
        'id' => $row['id'],
        'product_application' => $row['product_application'],
    ];
}

$stmt->close();

// Trả về dữ liệu JSON
if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No data found.']);
} else {
    echo json_encode(['success' => true, 'data' => $data]);
}
