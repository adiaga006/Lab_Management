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

// Query to get all treatments and their data
$sql = "SELECT treatment_name, survival_sample, feeding_weight 
        FROM entry_data 
        WHERE case_study_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store data by treatment
$treatmentData = [];
while ($row = $result->fetch_assoc()) {
    $treatmentName = $row['treatment_name'];
    if (!isset($treatmentData[$treatmentName])) {
        $treatmentData[$treatmentName] = ['survival_sample' => [], 'feeding_weight' => []];
    }
    if ($row['survival_sample'] !== null) {
        $treatmentData[$treatmentName]['survival_sample'][] = $row['survival_sample'];
    }
    if ($row['feeding_weight'] !== null) {
        $treatmentData[$treatmentName]['feeding_weight'][] = $row['feeding_weight'];
    }
}
$stmt->close();

// Function to calculate mean
function calculateMean($data) {
    if (empty($data)) {
        return null; // Skip empty data
    }
    return array_sum($data) / count($data);
}

// Function to calculate standard deviation
function calculateSD($data, $mean) {
    if (empty($data)) {
        return null; // Skip empty data
    }
    $variance = array_reduce($data, function ($carry, $item) use ($mean) {
        $carry += pow($item - $mean, 2);
        return $carry;
    }, 0) / count($data);
    return sqrt($variance);
}

// Calculate survival rate (%), total feeding weight, and their SD for each treatment
$results = [];
foreach ($treatmentData as $treatmentName => $data) {
    // Calculate survival rate (in percentage)
    $survivalTotal = array_sum($data['survival_sample']);
    $survivalRates = [];
    foreach ($data['survival_sample'] as $sample) {
        $survivalRates[] = ($sample / $survivalTotal) * 100;
    }
    $meanSurvivalRate = calculateMean($survivalRates);
    $sdSurvivalRate = calculateSD($survivalRates, $meanSurvivalRate);

    // Calculate total feeding weight and SD
    $totalFeedingWeight = array_sum($data['feeding_weight']);
    $feedingSD = calculateSD($data['feeding_weight'], $totalFeedingWeight);

    $results[] = [
        'treatment_name' => $treatmentName,
        'mean_survival_rate' => $meanSurvivalRate,
        'sd_survival_rate' => $sdSurvivalRate,
        'total_feeding_weight' => $totalFeedingWeight,
        'feeding_sd' => $feedingSD,
    ];
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-10 align-self-center">
            <h3 class="text-primary">Results for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4>Calculated Results</h4>
                <?php if (!empty($results)): ?>
                    <div class="table-responsive m-t-20">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Treatment Name</th>
                                    <th>Mean Survival Rate (%)</th>
                                    <th>SD (Survival Rate)</th>
                                    <th>Total Feed Consumption</th>
                                    <th>SD (Feed Consumption)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['treatment_name']); ?></td>
                                        <td><?php echo $result['mean_survival_rate'] !== null ? round($result['mean_survival_rate'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $result['sd_survival_rate'] !== null ? round($result['sd_survival_rate'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $result['total_feeding_weight'] !== null ? round($result['total_feeding_weight'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $result['feeding_sd'] !== null ? round($result['feeding_sd'], 2) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No data available for calculations.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include('./constant/layout/footer.php'); ?>
