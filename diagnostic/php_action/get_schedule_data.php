<?php
require_once 'core.php';

if (isset($_POST['case_study_id'])) {
    $caseStudyId = $_POST['case_study_id'];

    $sql = "SELECT * FROM schedule WHERE case_study_id = ? ORDER BY date_check ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $caseStudyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $groupedData = [];
    while ($row = $result->fetch_assoc()) {
        $dateCheck = date('d-m-Y', strtotime($row['date_check']));
        $groupedData[$dateCheck][] = $row;
    }

    foreach ($groupedData as $date => $rows) {
        echo "<tr><td rowspan='" . count($rows) . "'>" . $date . "</td>";
        echo "<td>" . htmlspecialchars($rows[0]['diets']) . "</td>";
        echo "<td>" . htmlspecialchars($rows[0]['work_done']) . "</td>";

        // Xử lý hiển thị check_status với work_name
        $checkStatusIds = json_decode($rows[0]['check_status'], true);
        $criteriaNames = [];
        if ($checkStatusIds) {
            $idList = implode(',', array_map('intval', $checkStatusIds));
            $sqlCriteria = "SELECT work_name FROM work_criteria WHERE id IN ($idList)";
            $criteriasResult = $connect->query($sqlCriteria);
            while ($criteria = $criteriasResult->fetch_assoc()) {
                $criteriaNames[] = "- " . htmlspecialchars($criteria['work_name']);
            }
        }
        echo "<td class='task-done'>" . nl2br(implode("\n", $criteriaNames)) . "</td>";
        echo "<td class='action-column'>
                <a  class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editScheduleModal' onclick='editSchedule(" . $rows[0]['id'] . ")'>
                    <i class='fa fa-edit'></i> Edit
                </a>
                <a  class='btn btn-danger btn-sm' onclick='confirmDelete(" . $rows[0]['id'] . ")'>
                    <i class='fa fa-trash'></i> Delete
                </a>
              </td>";
        echo "</tr>";

        // Hiển thị các dòng còn lại
        for ($i = 1; $i < count($rows); $i++) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rows[$i]['diets']) . "</td>";
            echo "<td>" . htmlspecialchars($rows[$i]['work_done']) . "</td>";

            // Xử lý hiển thị check_status với work_name
            $checkStatusIds = json_decode($rows[$i]['check_status'], true);
            $criteriaNames = [];
            if ($checkStatusIds) {
                $idList = implode(',', array_map('intval', $checkStatusIds));
                $sqlCriteria = "SELECT work_name FROM work_criteria WHERE id IN ($idList)";
                $criteriasResult = $connect->query($sqlCriteria);
                while ($criteria = $criteriasResult->fetch_assoc()) {
                    $criteriaNames[] = "- " . htmlspecialchars($criteria['work_name']);
                }
            }
            echo "<td class='task-done'>" . nl2br(implode("\n", $criteriaNames)) . "</td>";
            echo "<td class='action-column'>
                    <a  class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editScheduleModal' onclick='editSchedule(" . $rows[$i]['id'] . ")'>
                        <i class='fa fa-edit'></i> Edit
                    </a>
                    <a  class='btn btn-danger btn-sm' onclick='confirmDelete(" . $rows[$i]['id'] . ")'>
                        <i class='fa fa-trash'></i> Delete
                    </a>
                  </td>";
            echo "</tr>";
        }
    }
}
?>