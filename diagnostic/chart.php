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
$sql = "SELECT start_date, phases, num_reps FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    die("Error: Case study not found.");
}

$numReps = $caseStudy['num_reps']; // Lưu giá trị num_reps
if (empty($numReps) || $numReps <= 0) {
    die("Error: Invalid num_reps value.");
}

if (!$caseStudy) {
    die("Error: Case study not found.");
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

            // Tính survival rate cho từng cặp
            foreach ($endSamples as $endSample) {
                $rate = calculateSurvivalRate($shrimpAfterImmunology, $endSample);
                if ($rate !== null) {
                    $survivalRates[] = $rate;
                }
            }
        } else {
            // Logic cho các phase khác
            $startSamples = $data[$phaseStartDate] ?? [];
            $endSamples = $data[$phaseEndDate] ?? [];

        // Tính toán survival rate
        for ($i = 0; $i < $numReps; $i++) {
            $rate = calculateSurvivalRate($startSamples[$i], $endSamples[$i]);
            if ($rate !== null) {
                $survivalRates[] = $rate;
            }
        }
    }
        if (!empty($survivalRates)) {
            $averageSurvivalRate = round(array_sum($survivalRates) / count($survivalRates), 2);
            $survivalRatesByPhase[$phaseName][$treatmentName] = $averageSurvivalRate;
        } else {
            $survivalRatesByPhase[$phaseName][$treatmentName] = 'No data available';
        }
    }
}

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
    // Cấu hình chung cho biểu đồ
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
</script>

<?php include('./constant/layout/footer.php'); ?>