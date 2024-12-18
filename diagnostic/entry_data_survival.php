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
$sql = "SELECT start_date, num_reps, phases, treatment FROM case_study WHERE case_study_id = ?";
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
$treatmentList = json_decode($caseStudy['treatment'], true); // Decode JSON for treatment list

if (!$phases || !$treatmentList || $numReps <= 0) {
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
// Tính toán end_date từ start_date và duration của các phase
$totalDuration = array_reduce($phases, function ($carry, $phase) {
    return $carry + $phase['duration'];
}, 0);

// Tạo end_date
$endDate = clone $startDate;
$endDate->modify("+" . ($totalDuration - 1) . " days");


// Fetch entry_data
$sql = "SELECT treatment_name, survival_sample, lab_day, rep FROM entry_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$entryData = [];
$tempData = [];
while ($row = $result->fetch_assoc()) {
    $tempData[] = $row;
}

// Sort $tempData by treatment_name, lab_day, and rep
usort($tempData, function ($a, $b) {
    if ($a['treatment_name'] !== $b['treatment_name']) {
        return strcmp($a['treatment_name'], $b['treatment_name']);
    }
    if ($a['lab_day'] !== $b['lab_day']) {
        return strcmp($a['lab_day'], $b['lab_day']);
    }
    return $a['rep'] <=> $b['rep'];
});

// Reorganize $entryData after sorting
foreach ($tempData as $row) {
    $entryData[$row['treatment_name']][$row['lab_day']][] = [
        'rep' => $row['rep'],
        'survival_sample' => $row['survival_sample'],
    ];
}

$stmt->close();

function formatDate($date)
{
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj ? $dateObj->format('d-m') : $date;
}
?>
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="header-title">
                    <h3 class="text-primary">
                        View of Survival Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?>
                    </h3>
                    <div style="font-size: 1em; color: black; font-weight: bold;" class="date-range">
                        Start Date: <?php echo $startDate->format('d-m-Y'); ?> ||
                        End Date: <?php echo $endDate->format('d-m-Y'); ?>
                    </div>
                </div>


                <!-- Fixed table for Treatment Name and Reps -->
                <div class="table-container">
                    <div class="table-fixed">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="header-row">
                                    <th rowspan="2" style="width: 100px;" class="sticky-header">Treatment Name</th>
                                    <th rowspan="2" style="width: 20px;" class="sticky-header">Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatmentList as $treatment): ?>
                                    <?php for ($rep = 1; $rep <= $numReps; $rep++): ?>
                                        <tr>
                                            <?php if ($rep === 1): ?>
                                                <td rowspan="<?php echo $numReps; ?>" class="centered">
                                                    <?php echo htmlspecialchars($treatment['name']); ?>
                                                </td>
                                            <?php endif; ?>
                                            <td class="centered"><?php echo $rep; ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-scrollable">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="header-row phase-header">
                                    <?php
                                    $colors = ['green', 'blue', 'red', 'green'];
                                    $colorIndex = 0;
                                    $currentPhase = '';
                                    $startPhaseDate = '';

                                    foreach ($dates as $index => $dateInfo):
                                        if ($dateInfo['phase'] !== $currentPhase):
                                            if ($currentPhase !== ''):
                                                $duration = (strtotime($prevDate) - strtotime($startPhaseDate)) / (60 * 60 * 24) + 1;
                                                echo "<th colspan=\"$phaseCount\" class=\"centered\" style=\"background-color: {$colors[$colorIndex]}; color: white;\">$currentPhase<br>($duration days)</th>";
                                                $colorIndex = ($colorIndex + 1) % count($colors);
                                            endif;
                                            $currentPhase = $dateInfo['phase'];
                                            $startPhaseDate = $dateInfo['date'];
                                            $phaseCount = 1;
                                        else:
                                            $phaseCount++;
                                        endif;
                                        $prevDate = $dateInfo['date'];
                                    endforeach;

                                    $duration = (strtotime($prevDate) - strtotime($startPhaseDate)) / (60 * 60 * 24) + 1;
                                    echo "<th colspan=\"$phaseCount\" class=\"centered\" style=\"background-color: {$colors[$colorIndex]}; color: white;\">$currentPhase<br>($duration days)</th>";
                                    ?>
                                </tr>
                            </thead>
                            <tr class="header-row date-header">
                                <?php foreach ($dates as $dateInfo): ?>
                                    <th><?php echo formatDate($dateInfo['date']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                            <tbody>
                                <?php foreach ($treatmentList as $treatment): ?>
                                    <?php for ($rep = 1; $rep <= $numReps; $rep++): ?>
                                        <tr>
                                            <?php foreach ($dates as $dateInfo): ?>
                                                <?php
                                                $currentDate = $dateInfo['date'];
                                                $foundData = '-'; // Mặc định hiển thị trống nếu không có dữ liệu
                                    
                                                // Kiểm tra và hiển thị dữ liệu tương ứng với treatment, date và rep
                                                if (isset($entryData[$treatment['name']][$currentDate])) {
                                                    foreach ($entryData[$treatment['name']][$currentDate] as $data) {
                                                        if ($data['rep'] == $rep) {
                                                            $foundData = htmlspecialchars($data['survival_sample']);
                                                            break; // Dừng vòng lặp khi tìm thấy dữ liệu
                                                        }
                                                    }
                                                }
                                                ?>
                                                <td><?php echo $foundData; ?></td>
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
    /* Đảm bảo container có đủ khoảng trống bên dưới cho thanh cuộn */
    .table-container {
        display: flex;
        max-height: calc(100vh - 170px);
        overflow: hidden;
        position: relative;
        padding-bottom: 16px;
        /* Tạo khoảng trống cho thanh cuộn ngang */
    }

    /* Bảng cố định Treatment Name và Reps */
    .table-fixed {
        flex: 0 0 auto;
        /* Không co giãn */
        /* Chiều rộng cố định */
        overflow: hidden;
        /* Ngăn tràn */
        border-right: 1px solid #000;
        /* Gạch dọc đậm hơn */
        text-align: center;
        padding-bottom: 16px;
        /* Giữ cố định cùng chiều cao */
    }

    /* Bảng cuộn */
    /* Bảng cuộn */
    .table-scrollable {
        flex: 1 1 auto;
        overflow-x: auto;
        /* Hiển thị thanh cuộn ngang */
        overflow-y: auto;
        /* Duy trì cuộn dọc */
    }

    /* Đồng bộ hóa bảng */
    .table-fixed table,
    .table-scrollable table {
        border-collapse: collapse;
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
        top: 80px;
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
        height: 128.5px;
        box-shadow: inset 0 0 0 1px #000;
        /* Tạo viền bên trong */
    }

    /* Nội dung bảng */
    .table-fixed td .table-scrollable td {
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

    .header-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .header-title .text-primary {
        margin: 0;
    }

    .header-title .year-header {
        font-size: 16px;
        color: #555;
        font-weight: normal;
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