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
// Fetch case study details, including treatment JSON
$sql = "SELECT start_date, treatment,no_of_survival_shrimp_after_immunology_sampling FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    die("Error: Case study not found.");
}
// Fetch survival shrimp count
$noOfSurvivalShrimp = $caseStudy['no_of_survival_shrimp_after_immunology_sampling'];
if (!$noOfSurvivalShrimp) {
    die("Error: Missing no_of_survival_shrimp_after_immunology_sampling in case study.");
}
$startDate = new DateTime($caseStudy['start_date']);
$treatmentList = json_decode($caseStudy['treatment'], true); // Decode treatment JSON

if (!$treatmentList) {
    die("Error: Invalid or missing treatment data.");
}

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
while ($row = $result->fetch_assoc()) {
    $testDate = date('Y-m-d', strtotime($row['test_time']));
    $testHour = date('H:i', strtotime($row['test_time']));
    $deathData[$row['treatment_name']][$testDate][$row['rep']][$testHour] = $row['death_sample'];
}
$stmt->close();

// Get distinct time slots from the database
$sql = "SELECT DISTINCT TIME_FORMAT(TIME(test_time), '%H:%i') as time_slot 
        FROM shrimp_death_data 
        WHERE case_study_id = ? 
        ORDER BY TIME(test_time)";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$timeSlots = [];
while ($row = $result->fetch_assoc()) {
    $timeSlots[] = $row['time_slot'];
}
$stmt->close();

// Get the earliest test_time as base time
$sql = "SELECT MIN(test_time) as base_time FROM shrimp_death_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$baseTimeRow = $result->fetch_assoc();
$stmt->close();

// Initialize variables
$baseTime = null;
$averageStdResults = [];

// Only proceed with calculations if we have data
if (!empty($deathData) && !empty($timeSlots) && $baseTimeRow && !empty($baseTimeRow['base_time'])) {
    $baseTime = new DateTime($baseTimeRow['base_time']);

    foreach ($treatmentList as $treatment) {
        if (isset($deathData[$treatment['name']])) {
            foreach ($deathData[$treatment['name']] as $date => $reps) {
                foreach ($timeSlots as $slot) {
                    $currentKey = "$date $slot";
                    $currentTime = new DateTime($currentKey);

                    // Nếu trước baseTime, bỏ qua
                    if ($currentTime < $baseTime) {
                        $averageStdResults[$treatment['name']][$currentKey] = [
                            'average' => 0.00,
                            'std_dev' => 0.00,
                        ];
                        continue;
                    }

                    $deathRates = [];

                    // Tính death_rate cho từng rep
                    for ($rep = 1; $rep <= $treatment['num_reps']; $rep++) {
                        $cumulativeDeath = 0;

                        // Cộng dồn death_data từ baseTime đến currentTime
                        foreach ($deathData[$treatment['name']] as $pastDate => $pastReps) {
                            foreach ($timeSlots as $pastSlot) {
                                $pastKey = "$pastDate $pastSlot";
                                $pastTime = new DateTime($pastKey);

                                if ($pastTime >= $baseTime && $pastTime <= $currentTime) {
                                    $cumulativeDeath += $pastReps[$rep][$pastSlot] ?? 0;
                                }
                            }
                        }

                        // Tính death_rate (nhân vi 100 để biểu diễn phần trăm)
                        $deathRate = $noOfSurvivalShrimp > 0 ? ($cumulativeDeath / $noOfSurvivalShrimp) * 100 : 0.00;
                        $deathRates[] = $deathRate;
                    }

                    // Tính mean và SD chỉ khi có dữ liệu
                    if (!empty($deathRates)) {
                        $mean = round(array_sum($deathRates) / count($deathRates), 2);
                        
                        if (count($deathRates) > 1) {
                            $variance = array_sum(array_map(function ($x) use ($mean) {
                                return pow($x - $mean, 2);
                            }, $deathRates)) / (count($deathRates) - 1);
                            $sd = round(sqrt($variance), 2);
                        } else {
                            $sd = 0.00;
                        }

                        $averageStdResults[$treatment['name']][$currentKey] = [
                            'average' => $mean,
                            'std_dev' => $sd,
                        ];
                    }
                }
            }
        }
    }
}

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
                    <h3 class="text-primary">Death Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?></h3>
                </div>

                <?php if (empty($deathData) || empty($timeSlots)): ?>
                    <div class="alert alert-info" role="alert">
                        No death data available for this case study.
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <!-- Fixed Table (Treatment Name and Reps) -->
                    <div class="table-fixed">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="sticky-header">Treatment Name</th>
                                    <th class="sticky-header">Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($treatmentList)): ?>
                                    <?php foreach ($treatmentList as $treatment): ?>
                                        <!-- Hiển thị các reps -->
                                        <?php for ($rep = 1; $rep <= $treatment['num_reps']; $rep++): ?>
                                            <tr>
                                                <?php if ($rep === 1): ?>
                                                    <td rowspan="<?php echo $treatment['num_reps'] + 2; ?>" class="centered">
                                                        <?php echo htmlspecialchars($treatment['name']); ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="centered"><?php echo $rep; ?></td>
                                            </tr>
                                        <?php endfor; ?>

                                        <!-- Thêm hàng Mean -->
                                        <tr>
                                            <td class="centered" style="background-color: white !important; color: red"><strong>Mean</strong></td>
                                        </tr>

                                        <!-- Thêm hàng SD -->
                                        <tr>
                                            <td class="centered" style="background-color:white !important;color: red"><strong>SD</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No treatments available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>


                    <!-- Scrollable Table (Dates and Time Slots) -->
                    <div class="table-scrollable">
                        <table class="table table-bordered">
                            <thead>
                                <!-- Date Header -->
                                <tr class="date-header">
                                    <?php if (!empty($deathData) && !empty(array_key_first($deathData))): ?>
                                        <?php $dayIndex = 0; ?>
                                        <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                            <th colspan="<?php echo count($timeSlots); ?>" class="<?php echo $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>">
                                                <?php echo formatDate($date); ?>
                                            </th>
                                            <?php $dayIndex++; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th>No dates available</th>
                                    <?php endif; ?>
                                </tr>

                                <!-- Time Slot Header -->
                                <tr class="time-header">
                                    <?php if (!empty($deathData) && !empty(array_key_first($deathData))): ?>
                                        <?php $dayIndex = 0; ?>
                                        <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                            <?php foreach ($timeSlots as $slot): ?>
                                                <th class="<?php echo $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>">
                                                    <?php echo $slot; ?>
                                                </th>
                                            <?php endforeach; ?>
                                            <?php $dayIndex++; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th>No time slots available</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <!-- Hàng tiêu đề ộ lệch giờ -->
                            <tr class="offset-header">
                                <?php if (!empty($deathData) && !empty(array_key_first($deathData))): ?>
                                    <?php $dayIndex = 0; ?>
                                    <?php foreach (array_keys($deathData[array_key_first($deathData)]) as $date): ?>
                                        <?php foreach ($timeSlots as $slot): ?>
                                            <?php
                                            $currentSlot = new DateTime("$date $slot");
                                            $interval = $baseTime->diff($currentSlot);
                                            $hourDifference = ($interval->days * 24) + $interval->h;
                                            if ($interval->invert) {
                                                $hourDifference = -$hourDifference;
                                            }
                                            ?>
                                            <th style="background-color: white;">
                                                <?php echo $hourDifference; ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <?php $dayIndex++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <th>No time differences available</th>
                                <?php endif; ?>
                            </tr>
                            <tbody>
                                <?php if (!empty($treatmentList) && !empty($deathData)): ?>
                                    <?php foreach ($treatmentList as $treatment): ?>
                                        <!-- Hiển thị các reps -->
                                        <?php for ($rep = 1; $rep <= $treatment['num_reps']; $rep++): ?>
                                            <tr>
                                                <?php $dayIndex = 0; ?>
                                                <?php foreach ($deathData[$treatment['name']] as $date => $reps): ?>
                                                    <?php $dayColor = $dayIndex % 2 === 0 ? 'bg-pink' : 'bg-green'; ?>
                                                    <?php foreach ($timeSlots as $slot): ?>
                                                        <td class="<?php echo $dayColor; ?>">
                                                            <?php echo $reps[$rep][$slot] ?? '-'; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <?php $dayIndex++; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endfor; ?>

                                        <!-- Hàng Mean -->
                                        <tr class="row-mean">
                                            <?php foreach ($deathData[$treatment['name']] as $date => $reps): ?>
                                                <?php foreach ($timeSlots as $slot): ?>
                                                    <td>
                                                        <?php
                                                        $currentKey = "$date $slot";
                                                        echo $averageStdResults[$treatment['name']][$currentKey]['average'] ?? '0.00';
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>

                                        <!-- Hàng SD -->
                                        <tr class="row-sd">
                                            <?php foreach ($deathData[$treatment['name']] as $date => $reps): ?>
                                                <?php foreach ($timeSlots as $slot): ?>
                                                    <td>
                                                        <?php
                                                        $currentKey = "$date $slot";
                                                        echo $averageStdResults[$treatment['name']][$currentKey]['std_dev'] ?? '0.00';
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
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
                            /* Gạch ngang và dọc đm */
                            color: #333;
                            /* Màu chữ đậm */
                            font-weight: bold;
                            /* Chữ in đậm */
                            background-clip: padding-box;
                            /* Giữ gạch khi cố định */
                            box-shadow: inset 0 0 0 1px #000;
                            /* Tạo viền bên trong */
                        }

                        /* Phase header (cố định khi cuộn xung) */
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
                            /* Vị trí c định khi cuộn */
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
                            /* ảm bảo cố định */
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
                            /* Tạo vin bên trong */
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
                            /* Ch in đậm */
                            border: 0.1px solid #000;
                            /* Gạch ngang và dọc đm */
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

                        /* Định dạng hàng Mean */
                        .row-mean td {
                            background-color: #ffffff;
                            /* Nền trắng */
                            color: red;
                            /* Chữ đỏ */
                            font-weight: bold;
                        }

                        /* Định dạng hàng SD */
                        .row-sd td {
                            background-color: #ffffff;
                            /* Nền trắng */
                            color: red;
                            /* Chữ đỏ */
                            font-weight: bold;
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