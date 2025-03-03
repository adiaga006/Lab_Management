<?php
header('Content-Type: application/json');

try {
    // Nhận dữ liệu JSON từ request
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['case_study_id']) || !isset($data['date']) || !isset($data['note'])) {
        throw new Exception('Missing required data');
    }

    $caseStudyId = $data['case_study_id'];
    $date = $data['date'];
    $note = $data['note'];

    // Tạo đường dẫn file
    $baseDir = dirname(dirname(__FILE__));
    $notesFile = $baseDir . "/uploads/case_studies/{$caseStudyId}/day_notes.json";
    $notesDir = dirname($notesFile);

    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($notesDir)) {
        mkdir($notesDir, 0777, true);
    }

    // Đọc notes hiện tại
    $notes = [];
    if (file_exists($notesFile)) {
        $notesContent = file_get_contents($notesFile);
        $notes = json_decode($notesContent, true) ?: [];
    }

    // Cập nhật note
    if (empty($note)) {
        unset($notes[$date]); // Xóa note nếu trống
    } else {
        $notes[$date] = $note;
    }

    // Lưu file
    $success = file_put_contents($notesFile, json_encode($notes, JSON_PRETTY_PRINT));

    if ($success === false) {
        throw new Exception('Failed to save note');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Note updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}