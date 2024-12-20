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
$sql = "SELECT start_date, phases, treatment FROM case_study WHERE case_study_id = ?";
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
$phases = json_decode($caseStudy['phases'], true);
$treatmentList = json_decode($caseStudy['treatment'], true);

if (!$phases || !$treatmentList) {
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

// Calculate the end_date
$totalDuration = array_reduce($phases, fn($carry, $phase) => $carry + $phase['duration'], 0);
$endDate = clone $startDate;
$endDate->modify("+" . ($totalDuration - 1) . " days");

// Fetch entry_data
$sql = "SELECT treatment_name, feeding_weight, lab_day, rep FROM entry_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$entryData = [];
while ($row = $result->fetch_assoc()) {
    $entryData[$row['treatment_name']][$row['lab_day']][] = [
        'rep' => $row['rep'],
        'feeding_weight' => $row['feeding_weight'],
    ];
}
$stmt->close();

// Utility function to format dates
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
                    <h3 class="text-primary">View of Feeding Data for Case Study ID:
                        <?php echo htmlspecialchars($caseStudyId); ?></h3>
                    <div style="font-size: 1em; color: black; font-weight: bold;" class="date-range">
                        Start Date: <?php echo $startDate->format('d-m-Y'); ?> ||
                        End Date: <?php echo $endDate->format('d-m-Y'); ?>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <!-- Fixed Table -->
                    <div class="table-fixed">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="sticky-header">Treatment Name</th>
                                    <th rowspan="2" class="sticky-header">Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatmentList as $treatment): ?>
                                    <?php for ($rep = 1; $rep <= $treatment['num_reps']; $rep++): ?>
                                        <tr>
                                            <?php if ($rep === 1): ?>
                                                <td rowspan="<?php echo $treatment['num_reps']; ?>" class="centered">
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

                    <!-- Scrollable Table -->
                    <div class="table-scrollable">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="phase-header">
                                    <?php
                                    $colors = ['green', 'blue', 'red', 'orange'];
                                    $colorIndex = 0;
                                    $currentPhase = '';
                                    $startPhaseDate = '';

                                    foreach ($dates as $index => $dateInfo):
                                        if ($dateInfo['phase'] !== $currentPhase):
                                            if ($currentPhase !== ''):
                                                $duration = (strtotime($prevDate) - strtotime($startPhaseDate)) / (60 * 60 * 24) + 1;
                                                echo "<th colspan=\"$phaseCount\" style=\"background-color: {$colors[$colorIndex]}; color: white;\">$currentPhase<br>($duration days)</th>";
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
                                    echo "<th colspan=\"$phaseCount\" style=\"background-color: {$colors[$colorIndex]}; color: white;\">$currentPhase<br>($duration days)</th>";
                                    ?>
                                </tr>
                                <tr class="date-header">
                                    <?php foreach ($dates as $dateInfo): ?>
                                        <th><?php echo formatDate($dateInfo['date']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatmentList as $treatment): ?>
                                    <?php for ($rep = 1; $rep <= $treatment['num_reps']; $rep++): ?>
                                        <tr>
                                            <?php foreach ($dates as $dateInfo): ?>
                                                <?php
                                                $currentDate = $dateInfo['date'];
                                                $feedingWeight = '-';
                                                if (isset($entryData[$treatment['name']][$currentDate])) {
                                                    foreach ($entryData[$treatment['name']][$currentDate] as $data) {
                                                        if ($data['rep'] == $rep) {
                                                            $feedingWeight = htmlspecialchars($data['feeding_weight']);
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <td><?php echo $feedingWeight; ?></td>
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