<?php 	
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

$caseStudyId = $_GET['id'];

if ($caseStudyId) { 
    // Xóa các entry_data liên quan trước
    $deleteEntriesSql = "DELETE FROM entry_data WHERE case_study_id = '$caseStudyId'";
    $connect->query($deleteEntriesSql);

    // Xóa bản ghi từ bảng `case_study`
    $sql = "DELETE FROM case_study WHERE case_study_id = '$caseStudyId'";

    if ($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Successfully Removed";
        header('Location: ../case_study.php');
        exit();
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing the case study";
    }

    $connect->close();
    echo json_encode($valid);
}  
