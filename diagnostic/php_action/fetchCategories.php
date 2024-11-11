<?php 	
require_once 'core.php';

$sql = "SELECT categories.categories_id, categories.categories_name, categories.categories_active, 
               GROUP_CONCAT(groups.group_name SEPARATOR ', ') AS groups 
        FROM categories 
        LEFT JOIN category_groups ON categories.categories_id = category_groups.category_id
        LEFT JOIN groups ON category_groups.group_id = groups.group_id
        WHERE categories.categories_status = 1 
        GROUP BY categories.categories_id";
        
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) { 

    $activeCategories = ""; 

    while($row = $result->fetch_array()) {
        $categoriesId = $row[0];
        $activeCategories = ($row['categories_active'] == 1) ? "<label class='label label-success'>Available</label>" : "<label class='label label-danger'>Not Available</label>";

        $button = '
        <div class="btn-group">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Action <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a type="button" data-toggle="modal" id="editCategoriesModalBtn" data-target="#editCategoriesModal" onclick="editCategories('.$categoriesId.')"> <i class="glyphicon glyphicon-edit"></i> Edit</a></li>
            <li><a type="button" data-toggle="modal" data-target="#removeCategoriesModal" id="removeCategoriesModalBtn" onclick="removeCategories('.$categoriesId.')"> <i class="glyphicon glyphicon-trash"></i> Remove</a></li>       
          </ul>
        </div>';

        $output['data'][] = array( 		
            $row['categories_name'], 		
            $row['groups'],  // Hiển thị nhóm liên kết dưới dạng chuỗi
            $activeCategories,
            $button 		
        ); 	
    }
}

$connect->close();
echo json_encode($output);
?>
