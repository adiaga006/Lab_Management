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
    $phaseName = $phase['name'];
    $phaseStartDate = $phase['start_date'];
    $phaseEndDate = $phase['end_date'];

    foreach ($treatmentData as $treatmentName => $data) {
        // Lấy dữ liệu cho start_date và end_date
        $startSamples = $data[$phaseStartDate] ?? [];
        $endSamples = ($phaseStartDate === $phaseEndDate)
            ? $startSamples // Nếu start_date == end_date, sử dụng cùng một dữ liệu
            : ($data[$phaseEndDate] ?? []);

        // Tính tỷ lệ sống
        $survivalRates = [];
        $totalPairs = min(count($startSamples), count($endSamples)); // Pair start and end samples
        for ($i = 0; $i < $totalPairs; $i++) {
            $rate = calculateSurvivalRate($startSamples[$i], $endSamples[$i]);
            if ($rate !== null) {
                $survivalRates[] = $rate;
            }
        }

        // Tính trung bình và độ lệch chuẩn
        if (!empty($survivalRates)) {
            $averageSurvivalRate = round(array_sum($survivalRates) / count($survivalRates), 2);
            $standardDeviation = calculateStandardDeviation($survivalRates);

            $survivalResults[$phaseName][] = [
                'treatment_name' => $treatmentName,
                'average_survival_rate' => $averageSurvivalRate,
                'standard_deviation' => $standardDeviation,
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
                <h4 style="font-size: 1.5em; color: black; font-weight: bold;">Total Feed Consumption</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
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

        <!-- Separate Tables for Survival Rates for each phase -->
        <?php foreach ($phases as $phase): ?>
            <div class="card">
                <div class="card-body">
                    <h4 style="font-size: 1.5em; color: black; font-weight: bold;">
                        Survival Rate: <?php echo htmlspecialchars($phase['name']); ?>
                        (<?php echo (new DateTime($phase['start_date']))->format('d-m-Y'); ?> to
                        <?php echo (new DateTime($phase['end_date']))->format('d-m-Y'); ?>)
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Treatment Name</th>
                                    <th>Average Survival Rate (%)</th>
                                    <th>Standard Deviation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($survivalResults[$phase['name']])): ?>
                                    <?php foreach ($survivalResults[$phase['name']] as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['treatment_name']); ?></td>
                                            <td><?php echo $result['average_survival_rate']; ?></td>
                                            <td><?php echo $result['standard_deviation']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No data available</td>
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
<?php include('./constant/layout/footer.php'); ?>