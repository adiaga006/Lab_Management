<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');

$caseStudyId = $_GET['case_study_id'];

// Lấy tất cả work criteria
$sql = "SELECT id, work_name FROM work_criteria ORDER BY work_name";
$result = $connect->query($sql);
$workCriteria = [];
while ($row = $result->fetch_assoc()) {
    $workCriteria[] = $row;
}

// Lấy dữ liệu schedule
$sql = "SELECT date_check, work_done, check_status FROM schedule WHERE case_study_id = ? ORDER BY date_check";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$scheduleData = [];
while ($row = $result->fetch_assoc()) {
    $date = date('d-m-Y', strtotime($row['date_check']));
    $scheduleData[$date] = [
        'work_done' => $row['work_done'],
        'check_status' => json_decode($row['check_status'], true)
    ];
}
$stmt->close();

function getColorCode($workDone)
{
    switch ($workDone) {
        case 'Acclimation':
            return 'g';
        case 'Feeding & Observation':
            return 'b';
        case 'Post -challenge observation';
            return 'o';
        default:
            return 'r';
    }
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Schedule Overview: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-body">
                <div class="work-done-wrapper">
                    <div class="work-done-scroll">
                        <table class="table table-bordered work-done-table">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Treatment</th>
                                    <?php foreach ($scheduleData as $date => $data): ?>
                                        <th class="text-center align-middle"><?php echo $date; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Lấy danh sách unique diets
                                $sql = "SELECT DISTINCT diets FROM schedule WHERE case_study_id = ? ORDER BY diets";
                                $stmt = $connect->prepare($sql);
                                $stmt->bind_param("s", $caseStudyId);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $uniqueDiets = [];
                                while ($row = $result->fetch_assoc()) {
                                    $uniqueDiets[] = $row['diets'];
                                }
                                $stmt->close();

                                // Hiển thị work done cho từng diet
                                foreach ($uniqueDiets as $diet): ?>
                                    <tr>
                                        <td class="text-center align-middle"><?php echo htmlspecialchars($diet); ?></td>
                                        <?php
                                        // Lấy work done theo từng ngày cho diet hiện tại
                                        foreach ($scheduleData as $date => $data):
                                            $sql = "SELECT work_done FROM schedule 
                                                   WHERE case_study_id = ? 
                                                   AND date_check = ? 
                                                   AND diets = ?";
                                            $stmt = $connect->prepare($sql);
                                            $dateFormatted = date('Y-m-d', strtotime($date));
                                            $stmt->bind_param("sss", $caseStudyId, $dateFormatted, $diet);
                                            $stmt->execute();
                                            $workDoneResult = $stmt->get_result();
                                            $workDone = $workDoneResult->fetch_assoc();
                                            $stmt->close();
                                            ?>
                                            <td class="text-center align-middle">
                                                <?php if ($workDone): ?>
                                                    <div class="work-done-cell"
                                                        data-color="<?php echo getColorCode($workDone['work_done']); ?>">
                                                        <?php echo htmlspecialchars($workDone['work_done']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <!-- Fixed Column -->
                    <div class="fixed-column">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Tasks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workCriteria as $criteria): ?>
                                    <tr>
                                        <td class="text-center align-middle">
                                            <?php echo htmlspecialchars($criteria['work_name']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="table-scroll">
                        <table class="table table-bordered scroll-content">
                            <thead>
                                <tr>
                                    <?php foreach ($scheduleData as $date => $data): ?>
                                        <th class="text-center align-middle"><?php echo $date; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workCriteria as $criteria): ?>
                                    <tr>
                                        <?php foreach ($scheduleData as $data): ?>
                                            <td class="text-center align-middle">
                                                <?php
                                                if (
                                                    isset($data['check_status']) &&
                                                    in_array($criteria['id'], $data['check_status'])
                                                ) {
                                                    echo 'X';
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .table th,
    .table td {
        border: 2px solid #000 !important;
        padding: 12px !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-weight: bold;
        min-width: 100px;
    }

    .table th {
        background-color: #ffeb3b !important;
        color: #000;
    }

    .table-responsive {
        overflow-x: auto;
        padding: 0;
        border-radius: 8px;
    }

    /* Định dạng cột đầu tiên */

    /* Màu cho các work_done khác nhau */


    /* Màu cho các trạng thái work_done */
    tr td[data-color="g"] {
        background-color: #d4edda !important;
    }

    /* Success - Xanh lá */
    tr td[data-color="b"] {
        background-color: #cce5ff !important;
    }

    /* Info - Xanh dương */
    tr td[data-color="o"] {
        background-color: #fff3cd !important;
    }

    /* Warning - Vàng */
    tr td[data-color="r"] {
        background-color: #f8d7da !important;
    }

    /* Danger - Đỏ */

    /* Hiệu ứng hover */
    .table tbody tr:hover td {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }

    /* Fix cho cột cuối */
    .table td:last-child,
    .table th:last-child {
        border-right: 2px solid #000 !important;
    }

    /* CSS cho table wrapper */
    .table-wrapper {
        position: relative;
        overflow: hidden;
    }

    /* CSS cho fixed column */
    .fixed-column {
        position: absolute;
        left: 0;
        top: auto;
        width: 322px;
        background-color: #fff;
        border-right: 2px solid #000;
        z-index: 2;
    }

    .fixed-column table {
        width: 100%;
        margin-bottom: 0;
    }

    /* CSS cho scrollable area */
    .table-scroll {
        overflow-x: auto;
        margin-left: 322px;
    }

    /* Đảm bảo các cell cùng chiều cao */
    .fixed-column td,
    .fixed-column th,
    .scroll-content td,
    .scroll-content th {
        height: 50px;
        white-space: nowrap;
        padding: 8px;
    }

    /* Thêm shadow cho phân biệt fixed column */
    .fixed-column::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(to right, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
    }

    .work-done-table {
        margin-bottom: 0;
    }

    .work-done-table th,
    .work-done-table td {
        border: 2px solid #000 !important;
        padding: 12px !important;
        text-align: center !important;
        vertical-align: middle !important;
        min-width: 120px;
    }

    .work-done-table th {
        background-color: #ffeb3b !important;
        color: #000;
        font-weight: 600;
    }

    .work-done-cell {
        padding: 8px;
        border-radius: 4px;
        font-weight: bold;
    }

    .work-done-cell[data-color="g"] {
        background-color: #d4edda;
    }

    .work-done-cell[data-color="b"] {
        background-color: #cce5ff;
    }

    .work-done-cell[data-color="o"] {
        background-color: #fff3cd;
    }

    .work-done-cell[data-color="r"] {
        background-color: #f8d7da;
    }

    /* CSS cho bảng Work Done */
    .work-done-table {
        margin-bottom: 0;
    }

    /* Wrapper cho bảng Work Done */
    .work-done-wrapper {
        position: relative;
        overflow: hidden;
    }

    /* Phần cuộn cho bảng Work Done */
    .work-done-scroll {
        overflow-x: auto;
        margin-left: px; /* Độ rộng của cột Treatment */
    }

    /* Cột cố định cho bảng Work Done */
    .work-done-table td:first-child,
    .work-done-table th:first-child {
        position: absolute;
        left: 0;
        width: 322px;
        background-color: #fff;
        border-right: 2px solid #000 !important;
        z-index: 1;
    }

    .work-done-table th:first-child {
        z-index: 2;
        background-color: #ffeb3b !important;
    }

    /* CSS cho bảng Task Done (điều chỉnh lại) */
    .table-wrapper {
        position: relative;
        overflow: hidden;
    }

    .fixed-column {
        position: absolute;
        left: 0;
        top: auto;
        width: 322px;
        background-color: #fff;
        border-right: 2px solid #000;
        z-index: 2;
    }

    .table-scroll {
        overflow-x: auto;
        margin-left: 322px;
    }

    /* Đảm bảo các cell cùng chiều cao */
    .fixed-column td,
    .fixed-column th,
    .scroll-content td,
    .scroll-content th {
        height: 50px;
        white-space: nowrap;
        padding: 8px;
    }

    /* Shadow effect cho cột cố định */
    .fixed-column::after,
    .work-done-table td:first-child::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(to right, rgba(0,0,0,0.1), rgba(0,0,0,0));
    }

    /* Styles chung cho cả hai bảng */
    .work-done-table th,
    .work-done-table td,
    .table th,
    .table td {
        border: 2px solid #000 !important;
        padding: 12px !important;
        text-align: center !important;
        vertical-align: middle !important;
        min-width: 120px;
    }

    .work-done-table th,
    .table th {
        background-color: #ffeb3b !important;
        color: #000;
        font-weight: 600;
    }
</style>

<script>
    // Script để set màu cho các ô dựa vào work_done
    document.addEventListener('DOMContentLoaded', function () {
        const cells = document.querySelectorAll('td:not(:first-child)');
        cells.forEach(cell => {
            const workDone = cell.textContent.trim();
            if (workDone === 'X') {
                cell.style.backgroundColor = '#d4edda';
            }
        });
    });

    // Đồng bộ chiều cao của các hàng giữa fixed column và scrollable content
    function syncRowHeights() {
        const fixedRows = document.querySelectorAll('.fixed-column tr');
        const scrollRows = document.querySelectorAll('.scroll-content tr');

        fixedRows.forEach((row, index) => {
            if (scrollRows[index]) {
                const height = Math.max(row.offsetHeight, scrollRows[index].offsetHeight);
                row.style.height = `${height}px`;
                scrollRows[index].style.height = `${height}px`;
            }
        });
    }

    // Đồng bộ chiều cao cho bảng Work Done
    function syncWorkDoneHeights() {
        const rows = document.querySelectorAll('.work-done-table tr');
        rows.forEach(row => {
            const firstCell = row.querySelector('td:first-child, th:first-child');
            const height = row.offsetHeight;
            if (firstCell) {
                firstCell.style.height = `${height}px`;
            }
        });
    }

    // Đồng bộ chiều cao cho cả hai bảng
    window.addEventListener('load', function() {
        syncWorkDoneHeights();
        syncRowHeights(); // Hàm hiện tại cho bảng Task Done
    });
    window.addEventListener('resize', function() {
        syncWorkDoneHeights();
        syncRowHeights();
    });
</script>

<?php include('./constant/layout/footer.php'); ?>