
<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

try {
    if ($_POST) {
        $id = $_POST['id'];

        $sql = "DELETE FROM schedule WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $id);

        if ($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Successfully Deleted";
        } else {
            throw new Exception($stmt->error);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    $valid['success'] = false;
    $valid['messages'] = $e->getMessage();
}

echo json_encode($valid);
?>
```
