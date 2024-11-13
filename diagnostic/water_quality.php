<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'];
$groupId = $_GET['group_id'];

// Lấy dữ liệu water_quality theo case_study_id và group_id
$waterQualitySql = "SELECT * FROM water_quality WHERE case_study_id = ? ORDER BY day ASC";
$stmt = $connect->prepare($waterQualitySql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$waterQualityResult = $stmt->get_result();
$waterQualityData = [];
while ($row = $waterQualityResult->fetch_assoc()) {
    $waterQualityData[] = $row;
}
$stmt->close();

function calculateMean($data)
{
    return array_sum($data) / count($data);
}

function calculateSD($data)
{
    $mean = calculateMean($data);
    $sumOfSquares = array_reduce($data, function ($carry, $item) use ($mean) {
        $carry += pow($item - $mean, 2);
        return $carry;
    }, 0);
    return sqrt($sumOfSquares / count($data));
}

if (count($waterQualityData) > 0) {
    // Lấy các giá trị cho các cột để tính toán Mean và SD
    $salinityValues = array_column($waterQualityData, 'salinity');
    $tempValues = array_column($waterQualityData, 'temperature');
    $doValues = array_column($waterQualityData, 'dissolved_oxygen');
    $phValues = array_column($waterQualityData, 'pH');
    $alkalinityValues = array_column($waterQualityData, 'alkalinity');
    $tanValues = array_column($waterQualityData, 'tan');
    $nitriteValues = array_column($waterQualityData, 'nitrite');

    // Tính Mean và SD cho từng cột
    $meanSalinity = calculateMean($salinityValues);
    $sdSalinity = calculateSD($salinityValues);

    $meanTemp = calculateMean($tempValues);
    $sdTemp = calculateSD($tempValues);

    $meanDO = calculateMean($doValues);
    $sdDO = calculateSD($doValues);

    $meanPH = calculateMean($phValues);
    $sdPH = calculateSD($phValues);

    $meanAlkalinity = calculateMean($alkalinityValues);
    $sdAlkalinity = calculateSD($alkalinityValues);

    $meanTAN = calculateMean($tanValues);
    $sdTAN = calculateSD($tanValues);

    $meanNitrite = calculateMean($nitriteValues);
    $sdNitrite = calculateSD($nitriteValues);
} else {
    echo "<p>No data available for this case study.</p>";
}
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-10 align-self-center">
            <h3 class="text-primary">Water Quality Data for Case Study ID: <?php echo htmlspecialchars($caseStudyId); ?>
            </h3>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <?php if (count($waterQualityData) > 0): ?>
                    <div class="table-responsive m-t-20">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Date</th>
                                    <th>Salinity (ppt)</th>
                                    <th>Temp (°C)</th>
                                    <th>DO (ppm)</th>
                                    <th>pH</th>
                                    <th>Alkalinity (ppm)</th>
                                    <th>TAN (ppm)</th>
                                    <th>Nitrite (ppm)</th>
                                    <th>Actions</th> <!-- Thêm cột hành động -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waterQualityData as $index => $data): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo date('d-M', strtotime($data['day'])); ?></td>
                                        <td><?php echo $data['salinity']; ?></td>
                                        <td><?php echo $data['temperature']; ?></td>
                                        <td><?php echo $data['dissolved_oxygen']; ?></td>
                                        <td><?php echo $data['pH']; ?></td>
                                        <td><?php echo $data['alkalinity']; ?></td>
                                        <td><?php echo $data['tan']; ?></td>
                                        <td><?php echo $data['nitrite']; ?></td>
                                        <td>
                                            <!-- Nút chỉnh sửa -->
                                            <button class="btn btn-warning btn-sm"
                                                data-toggle="modal"
                                                data-target="#editWaterQualityModal"
                                                onclick="editWaterQualityData(<?php echo $data['id']; ?>)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <!-- Nút xóa -->
                                            <button class="btn btn-danger btn-sm"
                                                onclick="deleteWaterQualityData(<?php echo $data['id']; ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="2"><strong>Mean</strong></td>
                                    <td><?php echo number_format($meanSalinity, 2); ?></td>
                                    <td><?php echo number_format($meanTemp, 2); ?></td>
                                    <td><?php echo number_format($meanDO, 2); ?></td>
                                    <td><?php echo number_format($meanPH, 2); ?></td>
                                    <td><?php echo number_format($meanAlkalinity, 2); ?></td>
                                    <td><?php echo number_format($meanTAN, 2); ?></td>
                                    <td><?php echo number_format($meanNitrite, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><strong>SD</strong></td>
                                    <td><?php echo number_format($sdSalinity, 2); ?></td>
                                    <td><?php echo number_format($sdTemp, 2); ?></td>
                                    <td><?php echo number_format($sdDO, 2); ?></td>
                                    <td><?php echo number_format($sdPH, 2); ?></td>
                                    <td><?php echo number_format($sdAlkalinity, 2); ?></td>
                                    <td><?php echo number_format($sdTAN, 2); ?></td>
                                    <td><?php echo number_format($sdNitrite, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><strong>Mean ± SD</strong></td>
                                    <td><?php echo number_format($meanSalinity, 2) . " ± " . number_format($sdSalinity, 2); ?>
                                    </td>
                                    <td><?php echo number_format($meanTemp, 2) . " ± " . number_format($sdTemp, 2); ?></td>
                                    <td><?php echo number_format($meanDO, 2) . " ± " . number_format($sdDO, 2); ?></td>
                                    <td><?php echo number_format($meanPH, 2) . " ± " . number_format($sdPH, 2); ?></td>
                                    <td><?php echo number_format($meanAlkalinity, 2) . " ± " . number_format($sdAlkalinity, 2); ?>
                                    </td>
                                    <td><?php echo number_format($meanTAN, 2) . " ± " . number_format($sdTAN, 2); ?></td>
                                    <td><?php echo number_format($meanNitrite, 2) . " ± " . number_format($sdNitrite, 2); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No data available for this case study.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Edit Water Quality Modal -->
<div id="editWaterQualityModal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editWaterQualityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Water Quality Data</h4>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Hidden field for Water Quality ID -->
                    <input type="hidden" name="id" id="editWaterQualityId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Day</label>
                                <input type="date" name="day" class="form-control" id="editDay" readonly>
                            </div>
                            <div class="form-group">
                                <label>Salinity (ppt)</label>
                                <input type="number" name="salinity" class="form-control" id="editSalinity" step="0.1"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Temperature (°C)</label>
                                <input type="number" name="temperature" class="form-control" id="editTemperature"
                                    step="0.1" required>
                            </div>
                            <div class="form-group">
                                <label>Dissolved Oxygen (ppm)</label>
                                <input type="number" name="dissolved_oxygen" class="form-control"
                                    id="editDissolvedOxygen" step="0.01" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>pH</label>
                                <input type="number" name="pH" class="form-control" id="editPH" step="0.1" required>
                            </div>
                            <div class="form-group">
                                <label>Alkalinity (ppm)</label>
                                <input type="number" name="alkalinity" class="form-control" id="editAlkalinity"
                                    step="0.1" required>
                            </div>
                            <div class="form-group">
                                <label>TAN (ppm)</label>
                                <input type="number" name="tan" class="form-control" id="editTAN" step="0.1" required>
                            </div>
                            <div class="form-group">
                                <label>Nitrite (ppm)</label>
                                <input type="number" name="nitrite" class="form-control" id="editNitrite" step="0.1"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>System Type</label>
                                <select name="system_type" class="form-control" id="editSystemType" required>
                                    <option value="RAS System (NC, PC & Treatments)">RAS System (NC, PC & Treatments)
                                    </option>
                                    <option value="Static System (Negative Control)">Static System (Negative Control)
                                    </option>
                                    <option value="RAS System (Positive Control, T1, T2, T3 & T4)">RAS System (Positive
                                        Control, T1, T2, T3 & T4)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="updateWaterQualityData()">Save
                        Changes</button>
                        <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function editWaterQualityData(id) {
        $.ajax({
            url: 'php_action/get_water_quality_data.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#editWaterQualityId').val(response.data.id);
                    $('#editDay').val(response.data.day);
                    $('#editSalinity').val(response.data.salinity);
                    $('#editTemperature').val(response.data.temperature);
                    $('#editDissolvedOxygen').val(response.data.dissolved_oxygen);
                    $('#editPH').val(response.data.pH);
                    $('#editAlkalinity').val(response.data.alkalinity);
                    $('#editTAN').val(response.data.tan);
                    $('#editNitrite').val(response.data.nitrite);
                    $('#editSystemType').val(response.data.system_type);
                } else {
                    alert('Error fetching water quality data.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    function updateWaterQualityData() {
        const formData = $('#editWaterQualityForm').serialize();
        $.ajax({
            url: 'php_action/edit_water_quality.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Water quality data updated successfully!');
                    $('.btn-close-modal').click();
                    location.reload();
                } else {
                    alert('Failed to update water quality data.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }


    function deleteWaterQualityData(id) {
        if (confirm('Are you sure you want to delete this entry?')) {
            $.ajax({
                url: 'php_action/remove_water_quality.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Water quality data deleted successfully!');
                        location.reload();
                    } else {
                        alert('Failed to delete water quality data.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
    }

</script>

<?php include('./constant/layout/footer.php');
