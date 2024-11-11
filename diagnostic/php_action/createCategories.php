<?php 
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {	
    $categoriesName = $_POST['categoriesName'];
    $categoriesStatus = $_POST['categoriesStatus'];
    $groupIds = $_POST['groupIds']; // Nhận các group ID từ input ẩn

    // Thêm category vào bảng categories
    $sql = "INSERT INTO categories (categories_name, categories_active, categories_status) 
            VALUES ('$categoriesName', '$categoriesStatus', 1)";

    if($connect->query($sql) === TRUE) {
        $categoryId = $connect->insert_id; // Lấy ID của category vừa tạo

        // Chèn các group đã chọn vào bảng category_groups
        if (!empty($groupIds)) {
            $groupIdsArray = explode(',', $groupIds);
            foreach ($groupIdsArray as $groupId) {
                $connect->query("INSERT INTO category_groups (category_id, group_id) VALUES ('$categoryId', '$groupId')");
            }
        }

        $valid['success'] = true;
        $valid['messages'] = "Successfully Added";
        header('location:../categories.php');	
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding the category";
    }

    $connect->close();
    echo json_encode($valid);
} 
?>