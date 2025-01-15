<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'schedule' => array());

try {
    if ($_POST) {
        $id = $_POST['id'];

        $sql = "SELECT * FROM schedule WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $valid['success'] = true;
            $valid['schedule'] = $result->fetch_assoc();
        } else {
            $valid['messages'] = "No schedule found.";
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
?>