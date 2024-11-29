<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

// Fetch the `case_study_id` from the URL
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;
if (!$caseStudyId) {
    die("Error: Missing case_study_id in URL");
}

// Fetch case study details
$sql = "SELECT start_date, num_reps, phases FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    die("Error: Case study not found.");
}

$startDate = new DateTime($caseStudy['start_date']);
$numReps = $caseStudy['num_reps'];
$phases = json_decode($caseStudy['phases'], true);

if (!$phases || $numReps <= 0) {
    die("Error: Invalid case study data.");
}

// Generate dates and associate them with phases
$dates = [];
$currentDate = clone $startDate;

foreach ($phases as $phase) {
    $duration = $phase['duration'];
    for ($i = 0; $i < $duration; $i++) {
        $dates[] = [
            'date' => $currentDate->format('Y-m-d'),
            'phase' => $phase['name'],
        ];
        $currentDate->modify('+1 day');
    }
}

// Fetch entry_data
$sql = "SELECT treatment_name, survival_sample, lab_day , created_at
        FROM entry_data 
        WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$entryData = [];

// Lưu dữ liệu vào một mảng tạm thời
$tempData = [];
while ($row = $result->fetch_assoc()) {
    $tempData[] = $row; // Lưu từng dòng kết quả vào mảng
}

// Sắp xếp lại $tempData theo treatment_name, lab_day, và created_at
usort($tempData, function ($a, $b) {
    if ($a['treatment_name'] !== $b['treatment_name']) {
        return strcmp($a['treatment_name'], $b['treatment_name']);
    }
    if ($a['lab_day'] !== $b['lab_day']) {
        return strcmp($a['lab_day'], $b['lab_day']);
    }
    $timeA = strtotime($a['created_at']);
    $timeB = strtotime($b['created_at']);
    return $timeA <=> $timeB;
});

// Tái tổ chức lại $entryData sau khi sắp xếp
foreach ($tempData as $row) {
    // Lưu cả survival_sample và created_at
    $entryData[$row['treatment_name']][$row['lab_day']][] = [
        'survival_sample' => $row['survival_sample'],
        'created_at' => $row['created_at']
    ];
}

$stmt->close();


function formatDate($date)
{
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj ? $dateObj->format('d-m-Y') : $date;
}
?>
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h3 class="text-primary">
                    View of Survival Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?>
                    <?php echo htmlspecialchars($groupName); ?>
                </h3>
                <!-- Bảng cố định "Treatment Name" và "Reps" -->
                <div class="table-container">

                    <div class="table-fixed">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="header-row">
                                    <th rowspan="2" class="sticky-header">Treatment Name</th>
                                    <th rowspan="2" class="sticky-header">Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entryData as $treatment => $daysData): ?>
                                    <?php for ($rep = 1; $rep <= $numReps; $rep++): ?>
                                        <tr>
                                            <?php if ($rep === 1): ?>
                                                <td rowspan="<?php echo $numReps; ?>" class="centered">
                                                    <?php echo htmlspecialchars($treatment); ?>
                                                </td>
                                            <?php endif; ?>
                                            <td class="centered"><?php echo $rep; ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bảng chứa dữ liệu và các tiêu đề còn lại -->
                    <div class="table-scrollable">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="header-row phase-header">
                                    <?php
                                    $currentPhase = '';
                                    $startPhaseDate = '';
                                    foreach ($dates as $index => $dateInfo):
                                        if ($dateInfo['phase'] !== $currentPhase):
                                            if ($currentPhase !== ''):
                                                // Hiển thị kết thúc phase trước đó
                                                $duration = (strtotime($prevDate) - strtotime($startPhaseDate)) / (60 * 60 * 24) + 1;
                                                echo "<th colspan=\"$phaseCount\" class=\"centered\">$currentPhase<br>($duration days)<br>(" . formatDate($startPhaseDate) . " to " . formatDate($prevDate) . ")</th>";
                                            endif;
                                            $currentPhase = $dateInfo['phase'];
                                            $startPhaseDate = $dateInfo['date'];
                                            $phaseCount = 1;
                                        else:
                                            $phaseCount++;
                                        endif;
                                        $prevDate = $dateInfo['date'];
                                    endforeach;
                                    $duration = (strtotime($prevDate) - strtotime(datetime: $startPhaseDate)) / (60 * 60 * 24) + 1;
                                    echo "<th colspan=\"$phaseCount\" class=\"centered\">$currentPhase<br>($duration days)<br>(" . formatDate($startPhaseDate) . " to " . formatDate($prevDate) . ")</th>";
                                    ?>
                                </tr>
                                <tr class="header-row date-header">
                                    <?php foreach ($dates as $dateInfo): ?>
                                        <th><?php echo formatDate($dateInfo['date']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tbody>
    <?php foreach ($entryData as $treatment => $daysData): ?>
        <?php for ($rep = 0; $rep < $numReps; $rep++): ?>
            <tr>
                <?php foreach ($dates as $dateInfo): ?>
                    <td>
    <?php
    $currentDate = $dateInfo['date'];

    if (isset($daysData[$currentDate][$rep])) {
        $sampleData = $daysData[$currentDate][$rep];
        echo htmlspecialchars($sampleData['survival_sample']);
    } else {
        echo '-';
    }
    ?>
</td>

                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    <?php endforeach; ?>
</tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS for sticky columns -->
<style>
    /* Container tổng */
    .table-container {
        display: flex;
        width: 100%;
        overflow: hidden;
        /* Ngăn tràn */
        max-height: calc(100vh - 200px);
        /* Trừ chiều cao header/footer */
    }

    /* Bảng cố định Treatment Name và Reps */
    .table-fixed {
        flex: 0 0 auto;
        /* Không co giãn */
        width: 250px;
        /* Chiều rộng cố định */
        overflow: hidden;
        /* Ngăn tràn */
        border-right: 1px solid #000;
        /* Gạch dọc đậm hơn */
        text-align: center;
    }

    /* Bảng cuộn */
    .table-scrollable {
        flex: 1 1 auto;
        /* Co giãn theo nội dung */
        overflow: auto;
    }

    /* Đồng bộ hóa bảng */
    .table-fixed table,
    .table-scrollable table {
        border-collapse: collapse;
        width: 100%;
    }

    .table-fixed th,
    .table-fixed td,
    .table-scrollable th,
    .table-scrollable td {
        white-space: nowrap;
        text-align: center;
        border: 0.1px solid #000;
        /* Gạch ngang và dọc đậm */
        color: #333;
        /* Màu chữ đậm */
        font-weight: bold;
        /* Chữ in đậm */
        background-clip: padding-box;
        /* Giữ gạch khi cố định */
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền bên trong */
    }

    /* Phase header (cố định khi cuộn xuống) */
    .phase-header th {
        position: sticky;
        top: 0;
        /* Cố định ở đỉnh */
        z-index: 3;
        /* Cao hơn nội dung */
        background: #e9ecef;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        border: 0.1px solid #000;
        /* Gạch dọc và ngang đậm */
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền khi cố định */
    }

    /* Date header (cố định khi cuộn xuống bên dưới phase) */
    .date-header th {
        position: sticky;
        top: 112px;
        /* Ngay bên dưới phase header */
        z-index: 2;
        /* Cao hơn nội dung, thấp hơn phase */
        background: #f8f9fa;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        border: 0.1px solid #000;
        /* Gạch ngang và dọc đậm */
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền khi cố định */
    }

    /* Tiêu đề Treatment Name và Reps */
    .sticky-header {
        position: sticky;
        top: 0;
        /* Đảm bảo cố định */
        left: 0;
        /* Cố định bên trái */
        z-index: 5;
        /* Cao hơn tiêu đề phase và date */
        background: #f8f9fa;
        border: 0.1px solid #000;
        /* Gạch ngang và dọc đậm */
        text-align: center;
        font-weight: bold;
        height: 160px;
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền bên trong */
    }

    /* Nội dung bảng */
    .table-fixed td {
        background: white;
        z-index: 1;
        /* Thấp hơn tiêu đề */
        border: 0.1px solid #000;
        /* Gạch ngang và dọc đậm */
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền bên trong */
    }

    /* Gộp các ô với căn giữa */
    .centered {
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
        font-weight: bold;
        /* Chữ in đậm */
        border: 0.1px solid #000;
        /* Gạch ngang và dọc đậm */
    }


    tbody tr td:last-child {
        text-align: center;
    }

    thead tr th:last-child {
        text-align: center;
    }
</style>

<!-- Đồng bộ cuộn -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fixedTable = document.querySelector('.table-fixed');
        const scrollableTable = document.querySelector('.table-scrollable');

        scrollableTable.addEventListener('scroll', function () {
            fixedTable.scrollTop = scrollableTable.scrollTop;
        });
    });
</script>

<?php include('./constant/layout/footer.php'); ?>