<?php
include('../constant/connect.php');

$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$searchTerm = $connect->real_escape_string($searchTerm);

$query = "SELECT group_id, group_name FROM groups WHERE group_name LIKE '%$searchTerm%' LIMIT 10";
$result = $connect->query($query);

$groups = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
}

echo json_encode($groups);
?>
