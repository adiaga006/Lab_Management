<?php
include('../constant/connect.php');

header('Content-Type: application/json'); // Ensure JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caseStudyId = $_POST['case_study_id'] ?? null;
    $shrimpCount = isset($_POST['shrimpCount']) ? intval($_POST['shrimpCount']) : null;

    if ($caseStudyId && $shrimpCount !== null) {
        $sql = "UPDATE case_study SET no_of_survival_shrimp_after_immunology_sampling = ? WHERE case_study_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("is", $shrimpCount, $caseStudyId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Data updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update data."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid input data."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
