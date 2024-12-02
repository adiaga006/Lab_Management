<?php
include('../constant/connect.php');

$caseStudyId = $_POST['case_study_id'] ?? null;
$filterDate = $_POST['filterDate'] ?? null;

if (!$caseStudyId) {
    echo json_encode(['success' => false, 'message' => 'Missing case study ID.']);
    exit;
}

// Lấy thông tin `case_study` và `phases`
$sql = "SELECT start_date, phases FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    echo json_encode(['success' => false, 'message' => 'Case study not found.']);
    exit;
}

$startDate = $caseStudy['start_date'];
$phasesJson = $caseStudy['phases'] ?? '[]';
$phases = json_decode($phasesJson, true);

if (!is_array($phases)) {
    $phases = [];
}

// Xử lý logic phases
$currentDate = new DateTime($startDate);
$computedPhases = [];
foreach ($phases as $phase) {
    if (empty($phase['name']) || empty($phase['duration']) || !is_numeric($phase['duration'])) {
        continue; // Skip invalid phase entries
    }

    $phaseStartDate = $currentDate->format('Y-m-d');
    $currentDate->modify("+{$phase['duration']} days");
    $phaseEndDate = $currentDate->modify("-1 day")->format('Y-m-d');
    $currentDate->modify("+1 day");

    $computedPhases[] = [
        'name' => $phase['name'],
        'start_date' => $phaseStartDate,
        'end_date' => $phaseEndDate,
        'duration' => $phase['duration'],
    ];
}

// Lọc dữ liệu `entry_data`
$entriesSql = "SELECT * FROM entry_data WHERE case_study_id = ?";
$params = [$caseStudyId];
$types = "s";

// Nếu `filterDate` không được gửi (reset), không áp dụng bộ lọc ngày
if ($filterDate) {
    $entriesSql .= " AND lab_day = ?";
    $params[] = $filterDate;
    $types .= "s";
}

$entriesSql .= " ORDER BY 
    CASE
        WHEN treatment_name = 'Negative control' THEN 1
        WHEN treatment_name = 'Positive control' THEN 2
        WHEN treatment_name = 'Treatment 1' OR treatment_name = 'Treatment T1' THEN 3
        WHEN treatment_name = 'Treatment 2' OR treatment_name = 'Treatment T2' THEN 4
        WHEN treatment_name = 'Treatment 3' OR treatment_name = 'Treatment T3' THEN 5
        WHEN treatment_name = 'Treatment 4' OR treatment_name = 'Treatment T4' THEN 6
        ELSE 7
    END,
    lab_day ASC, created_at ASC";


$stmt = $connect->prepare($entriesSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entryResult = $stmt->get_result();
$entries = $entryResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Nhóm entries theo phases
$groupedEntries = [];
foreach ($computedPhases as $phase) {
    $phaseEntries = array_filter($entries, function ($entry) use ($phase) {
        return $entry['lab_day'] >= $phase['start_date'] && $entry['lab_day'] <= $phase['end_date'];
    });

    $groupedEntries[] = [
        'phase' => $phase['name'],
        'start_date' => $phase['start_date'],
        'end_date' => $phase['end_date'],
        'entries' => array_values($phaseEntries), // Đảm bảo entries luôn là mảng
    ];
}

// Đảm bảo trả về dữ liệu đúng định dạng
if (empty($groupedEntries)) {
    echo json_encode(['success' => true, 'data' => []]);
} else {
    echo json_encode(['success' => true, 'data' => $groupedEntries]);
}
exit;

