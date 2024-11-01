<?php

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if ($_POST) {	

    $caseStudyId   = $_POST['case_study_id'];
    $caseName      = $_POST['case_name'];
    $location      = $_POST['location'];
    $categoryId    = $_POST['categories_id'];
    $startDate     = $_POST['start_date'];
    $endDate       = $_POST['end_date'];
    $userId        = $_POST['user_id'];
    $pondId        = $_POST['pond_id'];
    $status        = $_POST['status'];
    $repNumber     = $_POST['rep_number'];
    
    // Kiểm tra xem case_study_id đã tồn tại chưa
    $checkSql = "SELECT * FROM case_study WHERE case_study_id = '$caseStudyId'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult->num_rows > 0) {
        // Nếu case_study_id đã tồn tại
        $valid['success'] = false;
        $valid['messages'] = "ID này đã tồn tại. Vui lòng chọn ID khác.";
    } else {
        // Nếu không trùng, thực hiện chèn dữ liệu
        $sql = "INSERT INTO case_study (case_study_id, case_name, location, categories_id, start_date, end_date, user_id, pond_id, status, rep_number) 
                VALUES ('$caseStudyId', '$caseName', '$location', '$categoryId', '$startDate', '$endDate', '$userId', '$pondId', '$status', '$repNumber')";

        if ($connect->query($sql) === TRUE) {
            $valid['success'] = true;
            $valid['messages'] = "Thêm thành công";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Lỗi khi thêm thí nghiệm";
        }
    }

    $connect->close();
    echo json_encode($valid);
}
?>
