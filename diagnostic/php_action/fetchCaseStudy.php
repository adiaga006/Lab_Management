<?php 

require_once 'core.php';

$sql = "SELECT case_study.case_study_id, case_study.case_name, case_study.location, case_study.start_date, 
        case_study.end_date, case_study.status, categories.categories_name 
        FROM case_study 
        INNER JOIN categories ON case_study.categories_id = categories.categories_id";

$result = $connect->query($sql);

$output = array('data' => array());

if ($result->num_rows > 0) { 

    while ($row = $result->fetch_array()) {
        $caseStudyId = $row[0];
        $status = "";

        // Status label
        if ($row[5] == "In-process") {
            $status = "<label class='label label-warning'>In-process</label>";
        } else if ($row[5] == "Prepare") {
            $status = "<label class='label label-info'>Prepare</label>";
        } else {
            $status = "<label class='label label-success'>Complete</label>";
        }

        $button = '
        <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Action <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a type="button" data-toggle="modal" id="editCaseStudyModalBtn" data-target="#editCaseStudyModal" onclick="editCaseStudy(\''.$caseStudyId.'\')"> 
                    <i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                <li><a type="button" data-toggle="modal" data-target="#removeCaseStudyModal" id="removeCaseStudyModalBtn" onclick="removeCaseStudy(\''.$caseStudyId.'\')"> 
                    <i class="glyphicon glyphicon-trash"></i> Remove</a></li>       
            </ul>
        </div>';

        // Category name
        $category = $row[7];

        // Adding each row data to output array
        $output['data'][] = array(
            $row[0],    // Case Study ID
            $row[1],    // Case Study Name
            $row[2],    // Location
            $row[3],    // Start Date
            $row[4],    // End Date
            $category,  // Category
            $status,    // Status
            $button     // Action buttons
        );
    }
}
header('location:../case_study.php');
$connect->close();

echo json_encode($output);