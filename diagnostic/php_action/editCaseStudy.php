<?php 	

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());
$caseStudyId = $_GET['id'];

if ($_POST) {
    $caseStudyId = $_GET['id'];
    $caseName = $_POST['editCaseStudyName'];
    $location = $_POST['editLocation'];
    $categoryId = $_POST['editCategoryName'];
    $startDate = date('Y-m-d', strtotime($_POST['editStartDate'])); // Chuyển đổi sang YYYY-MM-DD
    $status = $_POST['editCaseStudyStatus'];

	$sql = "UPDATE case_study SET 
                case_name = '$caseName', 
                location = '$location', 
                start_date = '$startDate', 
                categories_id = '$categoryId', 
                status = '$status' 
            WHERE case_study_id = '$caseStudyId'";

	if ($connect->query($sql) === TRUE) {
		$valid['success'] = true;
		$valid['messages'] = "Successfully Updated";	
		header('location:../case_study.php');
	} else {
		$valid['success'] = false;
		$valid['messages'] = "Error while updating case study info";
	}

	$connect->close();
	echo json_encode($valid);
}
?>
