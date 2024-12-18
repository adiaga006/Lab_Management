<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

// Fetch the case_study_id from the URL
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;
if (!$caseStudyId) {
    die("Error: Missing case_study_id in URL");
}

// Fetch case study details
$sql = "SELECT start_date, num_reps FROM case_study WHERE case_study_id = ?";
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
$numReps = $caseStudy['num_reps']; // Số lần đo trong mỗi ngày

// Fetch shrimp_death_data
$sql = "SELECT treatment_name, rep, product_application, test_time, death_sample
FROM shrimp_death_data
WHERE case_study_id = ?
ORDER BY treatment_name, test_time, rep";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$deathData = [];
$productApplication = [];
while ($row = $result->fetch_assoc()) {
    $testDate = date('Y-m-d', strtotime($row['test_time']));
    $testHour = date('H:i', strtotime($row['test_time']));
    $deathData[$row['treatment_name']][$testDate][$row['rep']][$testHour] = $row['death_sample'];
    $productApplication[$row['treatment_name']] = $row['product_application'];
}
$stmt->close();

// Define the 6 specific time slots
$timeSlots = ['03:00', '07:00', '11:00', '15:00', '19:00', '23:00'];
// Lấy ngày nhỏ nhất trong database
$sql = "SELECT MIN(DATE(test_time)) AS earliest_date FROM shrimp_death_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || !$row['earliest_date']) {
    die("Error: Unable to fetch the earliest date from the database.");
}

// Xác định ngày gốc
$baseDate = $row['earliest_date']; // Ngày nhỏ nhất
$baseTime = new DateTime($baseDate . ' 15:00'); // Thêm giờ gốc 15:00

// Helper function to format date
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
                <div class="header-title">
                    <h3 class="text-primary">Death Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?>
                    </h3>
                </div>

                <div class="table-container">
                    <!-- Bảng cố định (Treatment Name và Reps) -->
                    <div class="table-fixed">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="sticky-header">Treatment Name</th>
                                    <th class="sticky-header">Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deathData as $treatmentName => $dates): ?>
                                    <tr>
                                        <td rowspan="<?php echo $numReps; ?>" class="centered">
                                            <?php echo htmlspecialchars($treatmentName); ?>
                                        </td>
                                        <?php for ($rep = 1; $rep <= $numReps; $rep++): ?>
                                            <?php if ($rep > 1): ?>
                                            <tr>
                                            <?php endif; ?>
                                            <td class="centered"><?php echo $rep; ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bảng cuộn (Dữ liệu theo ngày và khung giờ) -->
                    <div class="table-scrollable">
                        <table class="table table-bordered">
                            <thead>
                                <!-- Hàng tiêu đề ngày -->
                                <tr class="date-header">
                                    <?php $dayIndex = 0; ?>
                                    <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                        <th colspan="<?php echo count($timeSlots); ?>"
                                            class="<?php echo $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>">
                                            <?php echo formatDate($date); ?>
                                        </th>
                                        <?php $dayIndex++; ?>
                                    <?php endforeach; ?>
                                </tr>
                                <!-- Hàng tiêu đề khung giờ -->
                                <tr class="time-header">
                                    <?php $dayIndex = 0; ?>
                                    <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                        <?php foreach ($timeSlots as $slot): ?>
                                            <th class="<?php echo $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>">
                                                <?php echo $slot; ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <?php $dayIndex++; ?>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <!-- Hàng tiêu đề độ lệch giờ -->
                            <tr class="offset-header">
                                <?php $dayIndex = 0; ?>
                                <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                    <?php foreach ($timeSlots as $slot): ?>
                                        <?php
                                        $currentSlot = new DateTime("$date $slot"); // Kết hợp ngày và giờ hiện tại
                                        $interval = $baseTime->diff($currentSlot); // Tính khoảng cách đến thời gian gốc
                                        $hourDifference = ($interval->days * 24) + $interval->h; // Chênh lệch giờ
                                        if ($interval->invert) {
                                            $hourDifference = -$hourDifference; // Nếu trước thời gian gốc thì giá trị âm
                                        }
                                        ?>
                                        <th style="background-color: white;">
                                            <?php echo $hourDifference; ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <?php $dayIndex++; ?>
                                <?php endforeach; ?>
                            </tr>
                            <tbody>
                                <?php foreach ($deathData as $treatmentName => $dates): ?>
                                    <?php for ($rep = 1; $rep <= $numReps; $rep++): ?>
                                        <tr>
                                            <?php $dayIndex = 0; ?>
                                            <?php foreach ($dates as $date => $reps): ?>
                                                <?php $dayColor = $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>
                                                <?php foreach ($timeSlots as $slot): ?>
                                                    <td class="<?php echo $dayColor; ?>">
                                                        <?php
                                                        // Kiểm tra dữ liệu có tồn tại cho rep và time slot
                                                        if (isset($reps[$rep][$slot])) {
                                                            echo htmlspecialchars($reps[$rep][$slot]);
                                                        } else {
                                                            echo '-'; // Hiển thị trống nếu không có dữ liệu
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <?php $dayIndex++; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

                        /* Header cố định */
                        .date-header th {
                            position: sticky;
                            top: 0;
                            /* Vị trí cố định khi cuộn */
                            z-index: 4;
                            /* Cao hơn nội dung khác */
                            background-color: #e9ecef;
                            /* Màu nền cho tiêu đề */
                            font-weight: bold;
                            text-align: center;
                            vertical-align: middle;
                            border: 0.1px solid #000;
                            box-shadow: inset 0 0 0 1px #000;
                            /* Tạo viền bên trong */
                        }

                        /* Hàng khung giờ cố định ngay dưới header ngày */
                        .offset-header th {
                            position: sticky;
                            top: 96.4px;
                            /* Ngay bên dưới hàng date-header */
                            z-index: 3;
                            /* Thấp hơn date-header nhưng cao hơn nội dung */
                            background-color: #f8f9fa;
                            font-weight: bold;
                            text-align: center;
                            vertical-align: middle;
                            border: 0.1px solid #000;
                            box-shadow: inset 0 0 0 1px #000;
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
                            height: 145px;
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

                        /* Cố định hàng tiêu đề */

                        .time-header th {
                            position: sticky;
                            top: 48.2px;
                            /* Ngay bên dưới hàng date-header */
                            z-index: 3;
                            /* Thấp hơn date-header nhưng cao hơn nội dung */
                            background-color: #f8f9fa;
                            font-weight: bold;
                            text-align: center;
                            vertical-align: middle;
                            border: 0.1px solid #000;
                            box-shadow: inset 0 0 0 1px #000;
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

                        /* Màu hồng nhạt cho các ngày */
                        .bg-pink {
                            background-color: #f8d7da !important;
                        }

                        /* Màu xanh lá nhạt cho các ngày */
                        .bg-green {
                            background-color: #d4edda !important;
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