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
$sql = "SELECT start_date, phases FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();
$caseStudy = $result->fetch_assoc();
$stmt->close();

if (!$caseStudy) {
    die("Error: Case study not found.");
}

$startDate = $caseStudy['start_date'];
$phasesJson = $caseStudy['phases'] ?? '[]'; // Default to empty JSON array if phases is null
function definePhasesWithDates($phasesJson, $startDate)
{
    // Decode JSON to array
    $phases = json_decode($phasesJson, true);

    // Validate if JSON is a valid array
    if (!is_array($phases)) {
        $phases = [];
    }

    // Initialize variables
    $computedPhases = [];
    $currentDate = new DateTime($startDate);

    foreach ($phases as $phase) {
        // Ensure each phase has a name and duration
        if (empty($phase['name']) || empty($phase['duration']) || !is_numeric($phase['duration'])) {
            continue; // Skip invalid phase entries
        }

        // Calculate start and end dates
        $startDate = $currentDate->format('Y-m-d');
        $currentDate->modify("+{$phase['duration']} days");
        $endDate = $currentDate->modify("-1 day")->format('Y-m-d');
        $currentDate->modify("+1 day"); // Prepare for the next phase

        // Append computed phase
        $computedPhases[] = [
            'name' => $phase['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration' => $phase['duration'],
        ];
    }

    return $computedPhases;
}
$phases = definePhasesWithDates($phasesJson, $startDate);
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

// Helper function to calculate standard deviation
// Function to calculate standard deviation
function calculateStandardDeviation($values)
{
    $count = count($values);
    if ($count <= 1) {
        return 0; // Standard deviation is undefined for one or no values
    }
    $mean = array_sum($values) / $count; // Calculate mean
    $sumOfSquares = 0;

    foreach ($values as $value) {
        $sumOfSquares += pow($value - $mean, 2);
    }
    // Divide by (n-1) for sample standard deviation
    return round(sqrt($sumOfSquares / ($count - 1)), 2);
}

// Initialize survival results and calculate survival rates
$survivalResults = [];
// Calculate survival rates for each phase
foreach ($phases as $phase) {
    $phaseName = strtolower($phase['name']); // Chuyển phase name về chữ thường
    $phaseStartDate = $phase['start_date'];
    $phaseEndDate = $phase['end_date'];

    foreach ($treatmentData as $treatmentName => $data) {
        $survivalRates = []; // Lưu tất cả survival rates cho phase

        // Special handling for "Post-challenge" phase
        if ($phaseName === strtolower("Post-challenge")) {
            if ($shrimpAfterImmunology === null) {
                // Nếu không có dữ liệu hoặc giá trị bằng 0
                $survivalResults[$phase['name']][] = [
                    'treatment_name' => $treatmentName,
                    'average_survival_rate' => 'No data available',
                    'standard_deviation' => 'No data available',
                ];
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
            $endSamples = ($phaseStartDate === $phaseEndDate)
                ? $startSamples // Nếu start_date == end_date, sử dụng cùng một dữ liệu
                : ($data[$phaseEndDate] ?? []);

            // Tính survival rate cho từng cặp
            $totalPairs = min(count($startSamples), count($endSamples));
            for ($i = 0; $i < $totalPairs; $i++) {
                $rate = calculateSurvivalRate($startSamples[$i], $endSamples[$i]);
                if ($rate !== null) {
                    $survivalRates[] = $rate;
                }
            }
        }

        // Tính trung bình và độ lệch chuẩn nếu có dữ liệu
        if (!empty($survivalRates)) {
            $averageSurvivalRate = round(array_sum($survivalRates) / count($survivalRates), 2);
            $standardDeviation = calculateStandardDeviation($survivalRates);

            $survivalResults[$phase['name']][] = [
                'treatment_name' => $treatmentName,
                'average_survival_rate' => $averageSurvivalRate,
                'standard_deviation' => $standardDeviation,
            ];
        } else {
            $survivalResults[$phase['name']][] = [
                'treatment_name' => $treatmentName,
                'average_survival_rate' => 'No data available',
                'standard_deviation' => 'No data available',
            ];
        }
    }
}


// Prepare feeding weight results
$feedingResults = [];

// Initialize feeding weights for each treatment
foreach ($entries as $entry) {
    $treatmentName = $entry['treatment_name'];
    $feedingWeight = $entry['feeding_weight'];

    if (!isset($feedingResults[$treatmentName])) {
        $feedingResults[$treatmentName] = 0;
    }

    if ($feedingWeight !== null) {
        $feedingResults[$treatmentName] += $feedingWeight; // Add feeding weight for the treatment
    }
}

// Format the feeding results
$feedingResults = array_map(function ($treatmentName, $totalFeedingWeight) {
    return [
        'treatment_name' => $treatmentName,
        'total_feeding_weight' => round($totalFeedingWeight, 2),
    ];
}, array_keys($feedingResults), $feedingResults);

?>

<!-- Feeding and Survival Rate Tables -->
<div class="page-wrapper">
    <div class="container-fluid">
        <!-- Table for feeding weight -->
        <div class="card">
            <div class="card-body">
                <h4 style="font-size: 1.5em; color: black; font-weight: bold; text-align: center;">Total Feed
                    Consumption</h4>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr style="font-weight: bold;">
                                <th>Treatment Name</th>
                                <th>Total Feed Consumption (g)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedingResults as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['treatment_name']); ?></td>
                                    <td><?php echo $result['total_feeding_weight']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center">
                <button style="color:white" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#addShrimpModal">
                    Add/Update the number of shrimp that survived after immunosampling
                </button>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <h5 style="font-size: 1.25em; font-weight: bold;color:black;">
                    Number of survival shrimp after immunology sampling: <?php
                    $sql = "SELECT no_of_survival_shrimp_after_immunology_sampling FROM case_study WHERE case_study_id = ?";
                    $stmt = $connect->prepare($sql);
                    $stmt->bind_param("s", $caseStudyId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $data = $result->fetch_assoc();
                    $shrimpCount = $data['no_of_survival_shrimp_after_immunology_sampling'];
                    $stmt->close();
                    echo htmlspecialchars($shrimpCount ?? "Not set");
                    ?>
                </h5>
            </div>
        </div>

        <!-- Separate Tables for Survival Rates for each phase -->
        <?php foreach ($phases as $phase): ?>
            <div class="card">
                <div class="card-body">
                    <h4 style="font-size: 1.5em; color: black; font-weight: bold; text-align: center;">
                        Survival Rate: <?php echo htmlspecialchars($phase['name']); ?>
                        (<?php echo (new DateTime($phase['start_date']))->format('d-m-Y'); ?> to
                        <?php echo (new DateTime($phase['end_date']))->format('d-m-Y'); ?>)
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr style="font-weight: bold;">
                                    <th>Treatment Name</th>
                                    <th>Average Survival Rate (%)</th>
                                    <th>Standard Deviation</th>
                                    <th>Average Survival Rate ± SD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($survivalResults[$phase['name']])): ?>
                                    <?php foreach ($survivalResults[$phase['name']] as $result): ?>
                                        <?php
                                        $mean = $result['average_survival_rate'];
                                        $sd = $result['standard_deviation'];
                                        $meanPlusMinusSD = number_format($mean, 2) . ' ± ' . number_format($sd, 2);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['treatment_name']); ?></td>
                                            <td><?php echo $mean; ?></td>
                                            <td><?php echo $sd; ?></td>
                                            <td><?php echo $meanPlusMinusSD; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="addShrimpModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addShrimpForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 style="color:black" class="modal-title">Add/Update Immunosampling Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label style="color:black" form="shrimpCount" class="form-label">Number of survival shrimp after
                            immunology sampling</label>
                        <input type="number" class="form-control" id="shrimpCount" name="shrimpCount" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Styling -->
<style>
    .table th,
    .table td {
        text-align: center;
        /* Center align all cells */
        vertical-align: middle;
        /* Vertically center align */
        font-weight: bold;
        /* Make font bold */
        font-size: 1.1em;
        /* Slightly increase font size */
    }

    .table thead th {
        background-color: #f8f9fa;
        /* Light gray header background */
    }

    .table tbody tr:nth-child(even) {
        background-color: white;
        /* Alternating row colors */
        color: black;
    }

    .table tbody tr td {
        border: 1px solid #ddd;
        color: black;
        /* Add border for clarity */
    }

    .table {
        border-collapse: collapse;
        width: 100%;
    }

    h4 {
        text-align: center;
        /* Center the phase header text */
    }

    tbody tr td:last-child {
        text-align: center;
    }

    thead tr th:last-child {
        text-align: center;
    }
</style>
<script>
    document.getElementById('addShrimpForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const shrimpCount = document.getElementById('shrimpCount').value;
        const caseStudyId = <?php echo json_encode($caseStudyId); ?>;

        // Send the data via AJAX
        fetch('php_action/add_shrimp_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ case_study_id: caseStudyId, shrimpCount: shrimpCount })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    });
</script>

<?php include('./constant/layout/footer.php'); ?>