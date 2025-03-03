<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

// Khởi tạo các biến dữ liệu mặc định
$chartData = ['labels' => [], 'datasets' => []];
$chartDataSurvival = ['labels' => [], 'datasets' => []];
$survivalRatesByPhase = [
    'Pre-challenge' => [],
    'Post-challenge' => []
];
$deathData = [];
$timeLabels = [];
$hasPreChallengeData = false;
$hasPostChallengeData = false;
$hasDeathData = false;
$hasSurvivalData = false;

// Fetch the `case_study_id` from the URL
$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;
if (!$caseStudyId) {
    die("Error: Missing case_study_id in URL");
}

try {
    // Query case study details
    $sql = "SELECT start_date, phases, treatment, no_of_survival_shrimp_after_immunology_sampling FROM case_study WHERE case_study_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $caseStudyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $caseStudy = $result->fetch_assoc();
    $stmt->close();

    if (!$caseStudy) {
        throw new Exception("Case study not found.");
    }

    // Kiểm tra và xử lý noOfSurvivalShrimp
    $noOfSurvivalShrimp = intval($caseStudy['no_of_survival_shrimp_after_immunology_sampling']);
    
    $treatmentList = json_decode($caseStudy['treatment'], true);
    if (!$treatmentList) {
        throw new Exception("Invalid treatment data.");
    }

    $phasesJson = $caseStudy['phases'];
    $phases = json_decode($phasesJson, true);
    if (!is_array($phases)) {
        throw new Exception("Invalid or missing phases data.");
    }

    // Calculate phase dates
    $currentDate = new DateTime($caseStudy['start_date']);
    foreach ($phases as &$phase) {
        $phase['start_date'] = $currentDate->format('Y-m-d');
        $currentDate->modify("+{$phase['duration']} days");
        $phase['end_date'] = $currentDate->modify("-1 day")->format('Y-m-d');
        $currentDate->modify("+1 day");
    }
    unset($phase);

    // Query entry_data for survival rates
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

    // Helper function to calculate survival rate with better validation
    function calculateSurvivalRate($startSamples, $endSamples) {
        $startSamples = floatval($startSamples);
        $endSamples = floatval($endSamples);
        
        if ($startSamples <= 0) {
            return null;
        }
        return round(($endSamples / $startSamples) * 100, 2);
    }

    // Calculate survival rates for each phase with better validation
    foreach ($phases as $phase) {
        $phaseName = trim($phase['name'] ?? $phase['Name'] ?? '');
        if (empty($phaseName)) continue;

        $phaseStartDate = $phase['start_date'];
        $phaseEndDate = $phase['end_date'];

        foreach ($treatmentData as $treatmentName => $data) {
            $survivalRates = [];
            
            if (strtolower($phaseName) === "post-challenge") {
                // Xử lý đặc biệt cho post-challenge khi noOfSurvivalShrimp = 0
                if ($noOfSurvivalShrimp <= 0) {
                    $survivalRatesByPhase[$phaseName][$treatmentName] = null;
                    continue;
                }
                
                $endSamples = $data[$phaseEndDate] ?? [];
                foreach ($endSamples as $endSample) {
                    $rate = calculateSurvivalRate($noOfSurvivalShrimp, $endSample);
                    if ($rate !== null) {
                        $survivalRates[] = $rate;
                    }
                }
            } else {
                $startSamples = $data[$phaseStartDate] ?? [];
                $endSamples = ($phaseStartDate === $phaseEndDate) ? $startSamples : ($data[$phaseEndDate] ?? []);
                
                $totalPairs = min(count($startSamples), count($endSamples));
                for ($i = 0; $i < $totalPairs; $i++) {
                    if ($startSamples[$i] <= 0) continue; // Skip if start sample is 0 or negative
                    $rate = calculateSurvivalRate($startSamples[$i], $endSamples[$i]);
                    if ($rate !== null) {
                        $survivalRates[] = $rate;
                    }
                }
            }

            // Calculate average only if we have valid rates
            if (!empty($survivalRates)) {
                $survivalRatesByPhase[$phaseName][$treatmentName] = round(array_sum($survivalRates) / count($survivalRates), 2);
            } else {
                $survivalRatesByPhase[$phaseName][$treatmentName] = null;
            }
        }
    }

    // Tách riêng việc kiểm tra dữ liệu cho từng loại biểu đồ
    // 1. Kiểm tra dữ liệu cho biểu đồ cột (Pre-challenge và Post-challenge)
    $hasPreChallengeData = isset($survivalRatesByPhase['Pre-challenge']) && 
        !empty($survivalRatesByPhase['Pre-challenge']) && 
        count(array_filter($survivalRatesByPhase['Pre-challenge'], function($value) {
            return is_numeric($value) && $value !== null;
        })) > 0;

    $hasPostChallengeData = isset($survivalRatesByPhase['Post-challenge']) && 
        !empty($survivalRatesByPhase['Post-challenge']) && 
        count(array_filter($survivalRatesByPhase['Post-challenge'], function($value) {
            return is_numeric($value) && $value !== null;
        })) > 0;

    // 2. Kiểm tra dữ liệu cho biểu đồ death rate và survival rate
    if ($noOfSurvivalShrimp > 0) {
        $sql = "SELECT DATE(MIN(test_time)) as base_date FROM shrimp_death_data WHERE case_study_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $caseStudyId);
        $stmt->execute();
        $stmt->bind_result($baseDate);
        $stmt->fetch();
        $stmt->close();

        // Thêm giờ 15:00 vào ngày sớm nhất
        if (is_string($baseDate) && !empty($baseDate)) {
            $baseTime = new DateTime("{$baseDate} 15:00:00");
            
            // Fetch death sample data
            $sql = "SELECT treatment_name, rep, test_time, death_sample
                    FROM shrimp_death_data
                    WHERE case_study_id = ?
                    ORDER BY test_time, treatment_name, rep";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("s", $caseStudyId);
            $stmt->execute();
            $result = $stmt->get_result();

            $deathData = [];
            $timeLabels = [];

            while ($row = $result->fetch_assoc()) {
                $testTime = new DateTime($row['test_time']);
                $deathData[$row['treatment_name']][] = [
                    'time' => $testTime,
                    'rep' => $row['rep'],
                    'death_sample' => $row['death_sample'],
                ];
                $timeLabels[] = $testTime->getTimestamp();
            }
            $stmt->close();

            // Loại bỏ trùng lặp và sắp xếp các nhãn thời gian
            $timeLabels = array_unique($timeLabels);
            sort($timeLabels);

            // Tính độ lệch giờ so với baseTime
            $hourOffsets = array_map(fn($timestamp) => round(($timestamp - $baseTime->getTimestamp()) / 3600, 2), $timeLabels);

            // Chuẩn bị dữ liệu biểu đồ
            $chartData = [
                'labels' => $hourOffsets ?: [],
                'datasets' => []
            ];

            if (!empty($deathData) && !empty($treatmentList)) {
                foreach ($treatmentList as $treatment) {
                    $cumulativeDeaths = [];
                    $dataset = [
                        'label' => $treatment['name'],
                        'data' => array_fill(0, count($hourOffsets), 0),
                        'fill' => false,
                    ];

                    if (!isset($deathData[$treatment['name']])) {
                        continue;
                    }

                    foreach ($deathData[$treatment['name']] as $data) {
                        $currentTime = $data['time']->getTimestamp();
                        $hoursDiff = round(($currentTime - $baseTime->getTimestamp()) / 3600, 2);

                        $index = array_search($hoursDiff, $hourOffsets);
                        if ($index === false) {
                            continue;
                        }

                        $cumulativeDeaths[$data['rep']] = ($cumulativeDeaths[$data['rep']] ?? 0) + $data['death_sample'];
                        $deathRate = ($cumulativeDeaths[$data['rep']] / $noOfSurvivalShrimp) * 100;
                        $dataset['data'][$index] += $deathRate;
                    }

                    foreach ($dataset['data'] as &$value) {
                        $value = round($value / $treatment['num_reps'], 2);
                    }

                    $chartData['datasets'][] = $dataset;
                }

                // Prepare survival rate data
                $chartDataSurvival = [
                    'labels' => $hourOffsets ?: [],
                    'datasets' => []
                ];

                foreach ($chartData['datasets'] as $dataset) {
                    $survivalDataset = [
                        'label' => $dataset['label'],
                        'data' => array_map(fn($value) => 100 - $value, $dataset['data']),
                        'fill' => false,
                    ];
                    $chartDataSurvival['datasets'][] = $survivalDataset;
                }

                $hasDeathData = !empty($chartData['datasets']);
                $hasSurvivalData = !empty($chartDataSurvival['datasets']);
            }
        }
    } else {
        $hasDeathData = false;
        $hasSurvivalData = false;
        // Không cần throw exception, chỉ cần log
        error_log("No survival shrimp data available for death/survival charts");
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Set default values when error occurs
    $hasPreChallengeData = false;
    $hasPostChallengeData = false;
    $hasDeathData = false;
    $hasSurvivalData = false;
}

// Debug logs
error_log("Pre-challenge data exists: " . ($hasPreChallengeData ? 'yes' : 'no'));
error_log("Post-challenge data exists: " . ($hasPostChallengeData ? 'yes' : 'no'));
error_log("Death data exists: " . ($hasDeathData ? 'yes' : 'no'));
error_log("Survival data exists: " . ($hasSurvivalData ? 'yes' : 'no'));
?>
<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Pre-challenge Chart -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Pre-challenge Survival Rate</h5>
            </div>
            <div class="card-body chart-container">
                <?php if ($hasPreChallengeData && !empty($survivalRatesByPhase['Pre-challenge'])): ?>
                    <canvas id="preChallengeChart"></canvas>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-bar text-muted mb-3"></i>
                        <p>No data available for Pre-challenge chart</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Post-challenge Chart -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Post-challenge Survival Rate</h5>
            </div>
            <div class="card-body chart-container">
                <?php if ($hasPostChallengeData && !empty($survivalRatesByPhase['Post-challenge'])): ?>
                    <canvas id="postChallengeChart"></canvas>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-bar text-muted mb-3"></i>
                        <p>No data available for Post-challenge chart</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Death Rate Chart -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Death Rate Over Time</h5>
            </div>
            <div class="card-body chart-container">
                <?php if ($hasDeathData && !empty($chartData['datasets'])): ?>
                    <canvas id="mortalityChart"></canvas>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-line text-muted mb-3"></i>
                        <p>No data available for Death Rate chart</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Survival Rate Chart -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Survival Rate Over Time</h5>
            </div>
            <div class="card-body chart-container">
                <?php if ($hasSurvivalData && !empty($chartDataSurvival['datasets'])): ?>
                    <canvas id="survivalChart"></canvas>
                <?php else: ?>
                    <div class="no-data-message">
                        <i class="fas fa-chart-line text-muted mb-3"></i>
                        <p>No data available for Survival Rate chart</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
    padding: 1rem;
}

.card-title {
    color: #4e73df;
    font-weight: 500;
}

.chart-container {
    min-height: 400px;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-data-message {
    text-align: center;
    color: #858796;
}

.no-data-message i {
    font-size: 48px;
    display: block;
    margin-bottom: 1rem;
}

.no-data-message p {
    font-size: 1rem;
    margin: 0;
}

canvas {
    width: 100% !important;
    height: 100% !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Debug data
    console.log('Pre-challenge Data:', <?php echo json_encode($survivalRatesByPhase['Pre-challenge'] ?? []); ?>);
    console.log('Post-challenge Data:', <?php echo json_encode($survivalRatesByPhase['Post-challenge'] ?? []); ?>);

    // Prepare data for the charts
    const preChallengeData = <?php echo json_encode($survivalRatesByPhase['Pre-challenge'] ?? []); ?>;
    const postChallengeData = <?php echo json_encode($survivalRatesByPhase['Post-challenge'] ?? []); ?>;

    // Ensure all chart containers are visible even without data
    document.addEventListener('DOMContentLoaded', function() {
        // Pre-challenge Chart
        if (<?php echo $hasPreChallengeData ? 'true' : 'false' ?>) {
            const preChallengeCtx = document.getElementById('preChallengeChart').getContext('2d');
            new Chart(preChallengeCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(preChallengeData),
                    datasets: [{
                        label: 'Pre-challenge',
                        data: Object.values(preChallengeData),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Survival rate (%)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Survival rate of shrimp on day 21 of pre-challenge'
                        }
                    }
                }
            });
        }

        // Post-challenge Chart
        if (<?php echo $hasPostChallengeData ? 'true' : 'false' ?>) {
            const postChallengeCtx = document.getElementById('postChallengeChart').getContext('2d');
            new Chart(postChallengeCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(postChallengeData),
                    datasets: [{
                        label: 'Post-challenge',
                        data: Object.values(postChallengeData),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Survival rate (%)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Survival rate of shrimp on day 10 of post-challenge'
                        }
                    }
                }
            });
        }

        // Death Rate Chart
        if (<?php echo $hasDeathData ? 'true' : 'false' ?>) {
            const mortalityCtx = document.getElementById('mortalityChart').getContext('2d');
            const chartData = <?php echo json_encode($chartData); ?>;
            const newColorPalette = [
                '#FF7F50', '#87CEEB', '#32CD32', '#FFD700', '#FF69B4',
                '#9370DB', '#20B2AA', '#FF6347', '#4682B4', '#EE82EE'
            ];

            chartData.datasets.forEach((dataset, index) => {
                dataset.borderColor = newColorPalette[index % newColorPalette.length];
                dataset.backgroundColor = newColorPalette[index % newColorPalette.length];
            });

            const baseTime = new Date(<?php echo json_encode($baseTime->format('Y-m-d H:i:s')); ?>);

            function formatTooltipLabel(tooltipItem, dataset) {
                const hoursDiff = chartData.labels[tooltipItem.dataIndex];
                const actualTime = new Date(baseTime.getTime() + hoursDiff * 60 * 60 * 1000);
                const formattedDate = actualTime.toLocaleString('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                });
                return `Treatment: ${dataset.label}\n || Time: ${formattedDate}\n || Value: ${tooltipItem.raw.toFixed(2)}%`;
            }

            new Chart(mortalityCtx, {
                type: 'line',
                data: chartData,
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
                                label: function(tooltipItem) {
                                    const dataset = chartData.datasets[tooltipItem.datasetIndex];
                                    return formatTooltipLabel(tooltipItem, dataset);
                                },
                            },
                        },
                        title: {
                            display: true,
                            text: 'Cumulative Mortality Curves on day 10 of post-challenge',
                            font: { size: 24 },
                        },
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Time of post-challenge(hr)',
                            },
                            ticks: {
                                align: 'center',
                                maxRotation: 0,
                                minRotation: 0,
                                font: { size: 12 },
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
            });
        }

        // Survival Rate Chart
        if (<?php echo $hasSurvivalData ? 'true' : 'false' ?>) {
            const survivalCtx = document.getElementById('survivalChart').getContext('2d');
            const survivalChartData = <?php echo json_encode($chartDataSurvival); ?>;
            const newColorPalette = [
                '#FF7F50', '#87CEEB', '#32CD32', '#FFD700', '#FF69B4',
                '#9370DB', '#20B2AA', '#FF6347', '#4682B4', '#EE82EE'
            ];

            survivalChartData.datasets.forEach((dataset, index) => {
                dataset.borderColor = newColorPalette[index % newColorPalette.length];
                dataset.backgroundColor = newColorPalette[index % newColorPalette.length];
            });

            new Chart(survivalCtx, {
                type: 'line',
                data: survivalChartData,
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
                                label: function(tooltipItem) {
                                    const dataset = survivalChartData.datasets[tooltipItem.datasetIndex];
                                    return formatTooltipLabel(tooltipItem, dataset);
                                },
                            },
                        },
                        title: {
                            display: true,
                            text: 'Survival Rate Over Time',
                            font: { size: 24 },
                        },
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Time of post-challenge(hr)',
                            },
                            ticks: {
                                align: 'center',
                                maxRotation: 0,
                                minRotation: 0,
                                font: { size: 12 },
                            },
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Survival Rate (%)',
                            },
                            beginAtZero: true,
                            max: 100,
                        },
                    },
                },
            });
        }
    });
</script>

<?php include('./constant/layout/footer.php'); ?>