<?php 	
require_once 'core.php';

$categoriesId = $_GET['id'];
if($_POST) {	
    $categoriesName = $_POST['categoriesName'];
    $categoriesStatus = $_POST['categoriesStatus'];
    $groupIds = explode(',', $_POST['groupIds']);

    // Cập nhật bảng categories
    $sql = "UPDATE categories SET categories_name = '$categoriesName', categories_active = '$categoriesStatus' WHERE categories_id = '$categoriesId'";
    $connect->query($sql);

    // Xóa liên kết cũ trong category_groups
    $connect->query("DELETE FROM category_groups WHERE category_id = '$categoriesId'");

    // Thêm các liên kết mới
    foreach ($groupIds as $groupId) {
        $connect->query("INSERT INTO category_groups (category_id, group_id) VALUES ('$categoriesId', '$groupId')");
    }

    header('location:../categories.php');
    $connect->close();
}
