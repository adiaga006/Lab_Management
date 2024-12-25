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

// Query case study details
$sql = "SELECT start_date, phases, treatment, no_of_survival_shrimp_after_immunology_sampling FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    die("Error: Case study not found.");
}
$noOfSurvivalShrimp = $caseStudy['no_of_survival_shrimp_after_immunology_sampling'];
if ($noOfSurvivalShrimp === null || $noOfSurvivalShrimp == 0) {
    echo "<p style='color: red; text-align: center;'>Error: No valid survival shrimp data available.</p>";
    return; // Dừng script tại đây
}


$treatmentList = json_decode($caseStudy['treatment'], true);
if (!$treatmentList) {
    die("Error: Invalid treatment data.");
}
$phasesJson = $caseStudy['phases'];
$phases = json_decode($phasesJson, true);
if (!is_array($phases)) {
    die("Error: Invalid or missing phases data.");
}
foreach ($phases as $phase) {
    $phaseName = $phase['name'] ?? $phase['Name'] ?? null; // Hỗ trợ cả 'name' và 'Name'
    $phaseName = trim($phaseName); // Loại bỏ khoảng trắng nếu có
}
// Fetch the number of survival shrimp after immunology sampling
$sql = "SELECT no_of_survival_shrimp_after_immunology_sampling FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$stmt->bind_result($shrimpAfterImmunology);
$stmt->fetch();
$stmt->close();
// Kiểm tra nếu `no_of_survival_shrimp_after_immunology_sampling` bằng 0 hoặc không có giá trị
if ($shrimpAfterImmunology === null || $shrimpAfterImmunology == 0) {
    $shrimpAfterImmunology = null; // Đặt giá trị null để báo lỗi trong phase "Post-challenge"
}
// Calculate phase dates
$currentDate = new DateTime($caseStudy['start_date']);
foreach ($phases as &$phase) {
    $phase['start_date'] = $currentDate->format('Y-m-d');
    $currentDate->modify("+{$phase['duration']} days");
    $phase['end_date'] = $currentDate->modify("-1 day")->format('Y-m-d');
    $currentDate->modify("+1 day");
}
unset($phase); // Reset tham chiếu

// Query entry_data for all treatments
$sql = "SELECT treatment_name, survival_sample, feeding_weight, lab_day FROM entry_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}
$stmt->close();

// Group data by treatment
$treatmentData = [];
foreach ($entries as $entry) {
    $treatmentName = $entry['treatment_name'];
    $labDay = $entry['lab_day'];

    if (!isset($treatmentData[$treatmentName])) {
        $treatmentData[$treatmentName] = [];
    }

    $treatmentData[$treatmentName][$labDay][] = $entry['survival_sample'];
}
$sql = "SELECT DATE(MIN(test_time)) as base_date FROM shrimp_death_data WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$stmt->bind_result($baseDate); // Lấy ngày sớm nhất
$stmt->fetch();
$stmt->close();

// Thêm giờ 15:00 vào ngày sớm nhất
if (is_string($baseDate) && !empty($baseDate)) {
    $baseTime = new DateTime("{$baseDate} 15:00:00"); // Sử dụng string interpolation
}

// Helper function to calculate survival rate
function calculateSurvivalRate($startSamples, $endSamples)
{
    if ($startSamples > 0 && $endSamples > 0) {
        return round(($endSamples / $startSamples) * 100, 2);
    }
    return null;
}
foreach ($phases as $phase) {
    $phaseName = $phase['name'] ?? $phase['Name'] ?? null; // Hỗ trợ cả 'name' và 'Name'
    $phaseName = trim($phaseName); // Loại bỏ khoảng trắng nếu có
}

// Calculate survival rates for each phase
$survivalRatesByPhase = [];
foreach ($phases as $phase) {
    $phaseName = $phase['name'] ?? $phase['Name'] ?? null; // Hỗ trợ cả 'name' và 'Name'
    $phaseName = trim($phaseName); // Loại bỏ khoảng trắng nếu có
    $phaseStartDate = $phase['start_date'];
    $phaseEndDate = $phase['end_date'];

    foreach ($treatmentData as $treatmentName => $data) {
        $survivalRates = [];

        // Special case for "Post-challenge"
        if (strtolower($phaseName) === "post-challenge") {
            if (empty($shrimpAfterImmunology) || $shrimpAfterImmunology == 0) {
                // Nếu không có dữ liệu, báo lỗi
                $survivalRatesByPhase[$phaseName][$treatmentName] = 'No data available';
                continue;
            }

            // Lấy tất cả endSamples từ phase "Post-challenge"
            $endSamples = $data[$phaseEndDate] ?? [];

            // Tính survival rate cho từng mẫu kết thúc
            foreach ($endSamples as $endSample) {
                $rate = calculateSurvivalRate($shrimpAfterImmunology, $endSample);
                if ($rate !== null) {
                    $survivalRates[] = $rate;
                }
            }
        } else {
            // Logic cho các phase khác
            $startSamples = $data[$phaseStartDate] ?? [];
            $endSamples = ($phaseStartDate === $phaseEndDate)
                ? $startSamples // Nếu start_date == end_date, sử dụng cùng một dữ liệu
                : ($data[$phaseEndDate] ?? []);

            // Tính survival rate cho từng cặp startSamples và endSamples
            $totalPairs = min(count($startSamples), count($endSamples));
            for ($i = 0; $i < $totalPairs; $i++) {
                $rate = calculateSurvivalRate($startSamples[$i], $endSamples[$i]);
                if ($rate !== null) {
                    $survivalRates[] = $rate;
                }
            }
        }

        // Tính trung bình nếu có dữ liệu
        if (!empty($survivalRates)) {
            $averageSurvivalRate = round(array_sum($survivalRates) / count($survivalRates), 2);
            $survivalRatesByPhase[$phaseName][$treatmentName] = $averageSurvivalRate;
        } else {
            $survivalRatesByPhase[$phaseName][$treatmentName] = 'No data available';
        }
    }
}
// Chuẩn bị dữ liệu cho biểu đồ đường
// Fetch shrimp death data
$sql = "SELECT treatment_name, rep, test_time, death_sample
        FROM shrimp_death_data
        WHERE case_study_id = ?
        ORDER BY test_time, treatment_name, rep";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

$deathData = [];
$timeLabels = []; // Lưu tất cả các thời gian để hiển thị trên trục X

while ($row = $result->fetch_assoc()) {
    $testTime = new DateTime($row['test_time']);
    $deathData[$row['treatment_name']][] = [
        'time' => $testTime,
        'rep' => $row['rep'],
        'death_sample' => $row['death_sample'],
    ];
    $timeLabels[] = $testTime->getTimestamp(); // Lưu dấu thời gian (timestamp) thay vì DateTime
}
$stmt->close();

// Loại bỏ trùng lặp và sắp xếp các nhãn thời gian
$timeLabels = array_unique($timeLabels);
sort($timeLabels);

// Tính độ lệch giờ (có thể âm) so với baseTime
$hourOffsets = array_map(fn($timestamp) => round(($timestamp - $baseTime->getTimestamp()) / 3600, 2), $timeLabels);
// Chuẩn bị dữ liệu biểu đồ
$chartData = [
    'labels' => $hourOffsets ?: [], // Đảm bảo nhãn không rỗng
    'datasets' => !empty($chartData['datasets']) ? $chartData['datasets'] : [], // Đảm bảo có cấu trúc hợp lệ
];
// Đảm bảo xử lý dữ liệu rỗng trước khi tính toán
if (empty($deathData) || empty($treatmentList)) {
    $chartData['datasets'] = [];
    $chartDataSurvival['datasets'] = [];
} else {
    foreach ($treatmentList as $treatment) {
        $cumulativeDeaths = [];
        $dataset = [
            'label' => $treatment['name'],
            'data' => array_fill(0, count($hourOffsets), 0), // Khởi tạo dữ liệu với giá trị 0
            'fill' => false,
        ];

        if (!isset($deathData[$treatment['name']])) {
            continue;
        }

        foreach ($deathData[$treatment['name']] as $data) {
            $currentTime = $data['time']->getTimestamp();
            $hoursDiff = round(($currentTime - $baseTime->getTimestamp()) / 3600, 2);

            // Tìm vị trí của độ lệch giờ trong nhãn trục X
            $index = array_search($hoursDiff, $hourOffsets);
            if ($index === false) {
                continue;
            }

            // Cộng dồn số lượng death_sample cho từng rep
            $cumulativeDeaths[$data['rep']] = ($cumulativeDeaths[$data['rep']] ?? 0) + $data['death_sample'];

            // Tính death rate cho từng rep
            $deathRate = ($cumulativeDeaths[$data['rep']] / $noOfSurvivalShrimp) * 100;

            // Tích lũy death rate vào dataset
            $dataset['data'][$index] += $deathRate;
        }

        // Trung bình hóa death rate theo số rep
        foreach ($dataset['data'] as &$value) {
            $value = round($value / $treatment['num_reps'], 2);
        }

        $chartData['datasets'][] = $dataset;
    }
}
$hasDeathData = !empty($chartData['datasets']);

// Chuẩn bị dữ liệu biểu đồ cho tỷ lệ sống
$chartDataSurvival = [
    'labels' => $hourOffsets ?: [], // Đảm bảo nhãn không rỗng
    'datasets' => !empty($chartDataSurvival['datasets']) ? $chartDataSurvival['datasets'] : [], // Đảm bảo có cấu trúc hợp lệ
];
if (empty($deathData) || empty($treatmentList)) {
    $chartData['datasets'] = [];
    $chartDataSurvival['datasets'] = [];
} else {
    foreach ($chartData['datasets'] as $dataset) {
        $survivalDataset = [
            'label' => $dataset['label'],
            'data' => array_map(fn($value) => 100 - $value, $dataset['data']), // Tỷ lệ sống = 100 - tỷ lệ tử vong
            'borderColor' => $dataset['borderColor'], // Sử dụng cùng màu đường
            'fill' => false,
        ];
        $chartDataSurvival['datasets'][] = $survivalDataset;
    }
}
$hasSurvivalData = !empty($chartDataSurvival['datasets']);
?>
<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Pre-challenge Chart -->
        <div class="card" style="margin-bottom: 50px;">
            <div class="card-body">
                <?php if (!empty($survivalRatesByPhase['Pre-challenge'])): ?>
                    <canvas id="preChallengeChart" width="1000" height="500" style="margin: auto; display: block;"></canvas>
                <?php else: ?>
                    <p style="color: gray; text-align: center;">No data available for Pre-challenge phase.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Post-challenge Chart -->
        <div class="card">
            <div class="card-body">
                <?php if (!empty($survivalRatesByPhase['Post-challenge'])): ?>
                    <canvas id="postChallengeChart" width="1000" height="500"
                        style="margin: auto; display: block;"></canvas>
                <?php else: ?>
                    <p style="color: gray; text-align: center;">No data available for Post-challenge phase.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Biểu đồ đường cho tỷ lệ chết -->
        <div class="card">
            <div class="card-body">
                <?php if ($hasDeathData): ?>
                    <canvas id="mortalityChart" width="1000" height="600" style="margin: auto; display: block;"></canvas>
                <?php else: ?>
                    <p style="color: gray; text-align: center;">No data available for Death Rate.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Biểu đồ đường cho tỷ lệ sống -->
        <div class="card">
            <div class="card-body">
                <?php if ($hasSurvivalData): ?>
                    <canvas id="survivalChart" width="1000" height="600" style="margin: auto; display: block;"></canvas>
                <?php else: ?>
                    <p style="color: gray; text-align: center;">No data available for Survival Rate.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    // Prepare data for the charts
    const treatments = <?php echo json_encode(array_keys($treatmentData)); ?>;
    const preChallengeData = <?php echo json_encode($survivalRatesByPhase['Pre-challenge'] ?? []); ?>;
    const postChallengeData = <?php echo json_encode($survivalRatesByPhase['Post-challenge'] ?? []); ?>;
    // Tùy chọn chung cho cả hai biểu đồ
    // Hàm thêm nhãn lên cột
    function drawLabels(chart) {
        const ctx = chart.ctx;
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((bar, index) => {
                const value = dataset.data[index];
                ctx.save();
                ctx.fillStyle = '#FF0000'; // Màu của nhãn
                ctx.font = 'bold 14px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillText(`${value}%`, bar.x, bar.y - 5); // Hiển thị nhãn trên cột
                ctx.restore();
            });
        });
    }

    // Tùy chỉnh cấu hình biểu đồ
    const chartOptions = {
        responsive: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        return `${context.raw}%`;
                    },
                },
            },
            title: {
                display: true,
                text: '', // Sẽ được đặt riêng cho từng biểu đồ
                font: {
                    size: 24, // Cỡ chữ tiêu đề
                    weight: 'bold',
                },
                color: '#333', // Màu chữ tiêu đề
                padding: {
                    top: 10,
                    bottom: 30,
                },
            },
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Treatments',
                    font: {
                        size: 20,
                    },
                },
                ticks: {
                    font: {
                        size: 14,
                    },
                },
            },
            y: {
                beginAtZero: true,
                max: 100, // Đặt giá trị tối đa cho trục Y
                ticks: {
                    stepSize: 10, // Hiển thị mỗi bước 10%
                    font: {
                        size: 14,
                    },
                },
                title: {
                    display: true,
                    text: 'Survival rate (%)',
                    font: {
                        size: 20,
                    },
                },
            },
        },
        animation: false, // Loại bỏ animation để nhãn luôn ổn định
    };

    // Hàm vẽ nhãn trên cột
    function drawLabels(chart) {
        const ctx = chart.ctx;
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((bar, index) => {
                const value = dataset.data[index];
                ctx.save();
                ctx.fillStyle = '#FF0000'; // Màu của nhãn
                ctx.font = 'bold 14px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillText(`${value}%`, bar.x, bar.y - 5); // Hiển thị nhãn trên cột
                ctx.restore();
            });
        });
    }

    // Render biểu đồ Post-challenge
    if (Object.keys(postChallengeData).length > 0) {
        const postChallengeCtx = document.getElementById('postChallengeChart').getContext('2d');
        const postChallengeChart = new Chart(postChallengeCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(postChallengeData),
                datasets: [
                    {
                        label: 'Post-challenge',
                        data: Object.values(postChallengeData),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        ...chartOptions.plugins.title,
                        text: 'Survival rate of shrimp on day 10 of post-challenge',
                    },
                },
            },
            plugins: [
                {
                    id: 'customLabels',
                    afterRender: (chart) => drawLabels(chart), // Vẽ nhãn sau khi render
                },
            ],
        });
    }

    // Render biểu đồ Pre-challenge
    if (Object.keys(preChallengeData).length > 0) {
        const preChallengeCtx = document.getElementById('preChallengeChart').getContext('2d');
        const preChallengeChart = new Chart(preChallengeCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(preChallengeData),
                datasets: [
                    {
                        label: 'Pre-challenge',
                        data: Object.values(preChallengeData),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        ...chartOptions.plugins.title,
                        text: 'Survival rate of shrimp on day 21 of pre-challenge',
                    },
                },
            },
            plugins: [
                {
                    id: 'customLabels',
                    afterRender: (chart) => drawLabels(chart), // Vẽ nhãn sau khi render
                },
            ],
        });
    }
    //bieu do duong
    const chartData = <?php echo json_encode($chartData); ?>;
    // Màu mới
    const newColorPalette = [
        '#FF7F50', // Coral
        '#87CEEB', // Sky Blue
        '#32CD32', // Lime Green
        '#FFD700', // Gold
        '#FF69B4', // Hot Pink
        '#9370DB', // Medium Purple
        '#20B2AA', // Light Sea Green
        '#FF6347', // Tomato
        '#4682B4', // Steel Blue
        '#EE82EE', // Violet
    ];
    // Gán màu mới cho datasets
    chartData.datasets.forEach((dataset, index) => {
        dataset.borderColor = newColorPalette[index % newColorPalette.length];
        dataset.backgroundColor = newColorPalette[index % newColorPalette.length];
    });
    const ctx = document.getElementById('mortalityChart').getContext('2d');

    // Parse base time from PHP
    const baseTime = new Date(<?php echo json_encode($baseTime->format('Y-m-d H:i:s')); ?>); // Base time from PHP
    const xLabels = chartData.labels; // Sử dụng độ lệch giờ trực tiếp làm nhã
    function formatTooltipLabel(tooltipItem, dataset) {
        const hoursDiff = chartData.labels[tooltipItem.dataIndex]; // Lấy độ lệch giờ
        const actualTime = new Date(baseTime.getTime() + hoursDiff * 60 * 60 * 1000); // Tính thời gian thực tế

        // Format thời gian chi tiết cho tooltip
        const formattedDate = actualTime.toLocaleString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });

        // Lấy tên treatment
        const treatmentName = dataset.label;

        return `Treatment: ${treatmentName}\n || Time: ${formattedDate}\n || Value: ${tooltipItem.raw.toFixed(2)}%`;
    }
    if (!chartData.labels.length || !chartData.datasets.length) {
        console.warn("No data available for Mortality Chart.");
    } else {
        // Render the line chart with corrected labels and tooltips
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: xLabels, // Sử dụng thời gian thực tế làm nhãn trục X
                datasets: chartData.datasets.map(dataset => ({
                    ...dataset,
                    data: Object.values(dataset.data),
                    pointRadius: 4, // Tăng kích thước chấm dữ liệu
                    pointHoverRadius: 6, // Kích thước chấm khi hover
                })),
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            boxHeight: 10,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                const dataset = chartData.datasets[tooltipItem.datasetIndex];
                                return formatTooltipLabel(tooltipItem, dataset); // Sử dụng hàm định dạng tooltip
                            },
                        },
                    },
                    title: {
                        display: true,
                        text: `Cumulative Mortality Curves on day 10 of post-challenge`,
                        font: {
                            size: 24,
                        },
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time of post-challenge(hr)', // Chỉ hiển thị độ lệch giờ
                        },
                        ticks: {
                            align: 'center', // Đặt các nhãn thẳng
                            maxRotation: 0,  // Không cho phép xoay
                            minRotation: 0,  // Đảm bảo luôn thẳng
                            font: {
                                size: 12, // Điều chỉnh kích thước font nếu cần
                            },
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Mortality Rate (%)',
                        },
                        beginAtZero: true,
                        max: 100,
                    },
                },
            },
        })
    };
    // Biểu đồ đường cho tỷ lệ sống
    const survivalChartData = <?php echo json_encode($chartDataSurvival); ?>;
    survivalChartData.datasets.forEach((dataset, index) => {
        dataset.borderColor = newColorPalette[index % newColorPalette.length];
        dataset.backgroundColor = newColorPalette[index % newColorPalette.length];
    });
    const survivalCtx = document.createElement('canvas');
    document.querySelector('.container-fluid').appendChild(survivalCtx); // Thêm canvas mới vào trang
    if (!survivalChartData.labels.length || !survivalChartData.datasets.length) {
        console.warn("No data available for Survival Chart.");
    } else {
        // Biểu đồ đường cho tỷ lệ sống
        const survivalCtx = document.getElementById('survivalChart').getContext('2d');
        new Chart(survivalCtx, {
            type: 'line',
            data: {
                labels: survivalChartData.labels, // Sử dụng cùng nhãn trục X
                datasets: survivalChartData.datasets.map(dataset => ({
                    ...dataset,
                    data: dataset.data,
                    pointRadius: 4, // Tăng kích thước chấm dữ liệu
                    pointHoverRadius: 6, // Kích thước chấm khi hover
                })),
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            boxHeight: 10,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                const dataset = survivalChartData.datasets[tooltipItem.datasetIndex];
                                return `Treatment: ${dataset.label}, Value: ${tooltipItem.raw.toFixed(2)}%`;
                            },
                        },
                    },
                    title: {
                        display: true,
                        text: `Cumulative Survival Curves on day 10 of post-challenge`,
                        font: {
                            size: 24,
                        },
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time of post-challenge (hr)',
                        },
                        ticks: {
                            align: 'center',
                            stepSize: 10, // Khoảng cách giữa các bước
                            maxRotation: 0,
                            minRotation: 0,
                            font: {
                                size: 12,
                            },
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Survival Rate (%)',
                        },
                        beginAtZero: true,
                        max: 105,
                        ticks: {
                            callback: function (value) {
                                // Ẩn nhãn "105"
                                if (value === 105) {
                                    return '';
                                }
                                return value;
                            },
                            stepSize: 10, // Khoảng cách giữa các bước
                            font: {
                                size: 12,
                            },
                        },
                    },
                },
            },
        });
    }
</script>

<?php include('./constant/layout/footer.php'); ?>

<style>
    canvas {
        background-color: transparent !important;
        /* Ghi đè màu nền */
    }
</style>