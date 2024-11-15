<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');
// Lấy `case_study_id` từ URL và `start_date` từ bảng `case_study`
$caseStudyId = $_GET['case_study_id'];
$caseStudySql = "SELECT start_date FROM case_study WHERE case_study_id = ?";
$stmt = $connect->prepare($caseStudySql);
$stmt->bind_param("s", $caseStudyId);
$stmt->execute();
$stmt->bind_result($startDate);
$stmt->fetch();
$stmt->close();

if (!$startDate) {
    die("Error: Start date not found for the given case_study_id.");
}
$groupId = $_GET['group_id'];

// Calculate phases based on `start_date`
$startDateObj = new DateTime($startDate);
$preChallengeEnd = clone $startDateObj;
$preChallengeEnd->modify('+22 days'); // Phase 1: Pre-challenge period (23 days)
// Lấy dữ liệu `water_quality` theo `case_study_id` và sắp xếp theo ngày
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

// Hàm tính trung bình
// Function to calculate mean
function calculateMean($data) {
    return count($data) > 0 ? array_sum($data) / count($data) : 0;
}

// Function to calculate standard deviation
function calculateSD($data) {
    $mean = calculateMean($data);
    $sumOfSquares = array_reduce($data, function ($carry, $item) use ($mean) {
        $carry += pow($item - $mean, 2);
        return $carry;
    }, 0);
    return count($data) > 1 ? sqrt($sumOfSquares / count($data)) : 0;
}

// Separate data by system type and phase
$phase1Data = array_filter($waterQualityData, function($data) use ($preChallengeEnd) {
    $dataDate = new DateTime($data['day']);
    return $dataDate <= $preChallengeEnd && $data['system_type'] == 'RAS System (NC, PC & Treatments)';
});

$phase2DataRAS = array_filter($waterQualityData, function($data) use ($preChallengeEnd) {
    $dataDate = new DateTime($data['day']);
    return $dataDate > $preChallengeEnd && $data['system_type'] == 'RAS System (Positive Control, T1, T2, T3 & T4)';
});

$phase2DataStatic = array_filter($waterQualityData, function($data) use ($preChallengeEnd) {
    $dataDate = new DateTime($data['day']);
    return $dataDate > $preChallengeEnd && $data['system_type'] == 'Static System (Negative Control)';
});

// Calculate stats for each system type
function calculateStats($data) {
    $salinityValues = array_column($data, 'salinity');
    $tempValues = array_column($data, 'temperature');
    $doValues = array_column($data, 'dissolved_oxygen');
    $phValues = array_column($data, 'pH');
    $alkalinityValues = array_column($data, 'alkalinity');
    $tanValues = array_column($data, 'tan');
    $nitriteValues = array_column($data, 'nitrite');

    return [
        'meanSalinity' => calculateMean($salinityValues),
        'sdSalinity' => calculateSD($salinityValues),
        'meanTemp' => calculateMean($tempValues),
        'sdTemp' => calculateSD($tempValues),
        'meanDO' => calculateMean($doValues),
        'sdDO' => calculateSD($doValues),
        'meanPH' => calculateMean($phValues),
        'sdPH' => calculateSD($phValues),
        'meanAlkalinity' => calculateMean($alkalinityValues),
        'sdAlkalinity' => calculateSD($alkalinityValues),
        'meanTAN' => calculateMean($tanValues),
        'sdTAN' => calculateSD($tanValues),
        'meanNitrite' => calculateMean($nitriteValues),
        'sdNitrite' => calculateSD($nitriteValues),
    ];
}

// Get statistics for each phase and system type
$phase1Stats = calculateStats($phase1Data);
$phase2RASStats = calculateStats($phase2DataRAS);
$phase2StaticStats = calculateStats($phase2DataStatic);
?>

<!-- HTML to display data by phase and system type -->
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <!-- Phase 1: Pre-challenge period -->
                <h3 class="text-primary">Water Quality during Pre-challenge Period</h3>
                <h4 class="text-secondary">RAS System (NC, PC & Treatments)</h4>

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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $dayCounter = 1; // Reset day counter for Phase 1
                            foreach ($phase1Data as $data): ?>
                                <tr>
                                    <td><?=  $dayCounter ++; ?></td>
                                    <td><?= date('d-M', strtotime($data['day'])); ?></td>
                                    <td><?= $data['salinity']; ?></td>
                                    <td><?= $data['temperature']; ?></td>
                                    <td><?= $data['dissolved_oxygen']; ?></td>
                                    <td><?= $data['pH']; ?></td>
                                    <td><?= $data['alkalinity']; ?></td>
                                    <td><?= $data['tan']; ?></td>
                                    <td><?= $data['nitrite']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editWaterQualityModal" onclick="editWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2"><strong>Mean ± SD</strong></td>
                                <td><?= number_format($phase1Stats['meanSalinity'], 2) . " ± " . number_format($phase1Stats['sdSalinity'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanTemp'], 2) . " ± " . number_format($phase1Stats['sdTemp'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanDO'], 2) . " ± " . number_format($phase1Stats['sdDO'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanPH'], 2) . " ± " . number_format($phase1Stats['sdPH'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanAlkalinity'], 2) . " ± " . number_format($phase1Stats['sdAlkalinity'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanTAN'], 2) . " ± " . number_format($phase1Stats['sdTAN'], 2); ?></td>
                                <td><?= number_format($phase1Stats['meanNitrite'], 2) . " ± " . number_format($phase1Stats['sdNitrite'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phase 2: Challenge & Post-challenge period -->
                <h3 class="text-primary">Water Quality during Challenge & Post-challenge Period</h3>

                <!-- RAS System Section -->
                <h4 class="text-secondary">RAS System (Positive Control, T1, T2, T3 & T4)</h4>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $dayCounter = 1; // Reset day counter for Phase 2, RAS System
                            foreach ($phase2DataRAS as $data): ?>
                                <tr>
                                    <td><?=  $dayCounter ++; ?></td>
                                    <td><?= date('d-M', strtotime($data['day'])); ?></td>
                                    <td><?= $data['salinity']; ?></td>
                                    <td><?= $data['temperature']; ?></td>
                                    <td><?= $data['dissolved_oxygen']; ?></td>
                                    <td><?= $data['pH']; ?></td>
                                    <td><?= $data['alkalinity']; ?></td>
                                    <td><?= $data['tan']; ?></td>
                                    <td><?= $data['nitrite']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editWaterQualityModal" onclick="editWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2"><strong>Mean ± SD</strong></td>
                                <td><?= number_format($phase2RASStats['meanSalinity'], 2) . " ± " . number_format($phase2RASStats['sdSalinity'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanTemp'], 2) . " ± " . number_format($phase2RASStats['sdTemp'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanDO'], 2) . " ± " . number_format($phase2RASStats['sdDO'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanPH'], 2) . " ± " . number_format($phase2RASStats['sdPH'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanAlkalinity'], 2) . " ± " . number_format($phase2RASStats['sdAlkalinity'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanTAN'], 2) . " ± " . number_format($phase2RASStats['sdTAN'], 2); ?></td>
                                <td><?= number_format($phase2RASStats['meanNitrite'], 2) . " ± " . number_format($phase2RASStats['sdNitrite'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Static System Section -->
                <h4 class="text-secondary">Static System (Negative Control)</h4>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $dayCounter = 1; // Reset day counter for Phase 2, Static System
                            foreach ($phase2DataStatic as $data): ?>
                                <tr>
                                    <td><?= $dayCounter ++; ?></td>
                                    <td><?= date('d-M', strtotime($data['day'])); ?></td>
                                    <td><?= $data['salinity']; ?></td>
                                    <td><?= $data['temperature']; ?></td>
                                    <td><?= $data['dissolved_oxygen']; ?></td>
                                    <td><?= $data['pH']; ?></td>
                                    <td><?= $data['alkalinity']; ?></td>
                                    <td><?= $data['tan']; ?></td>
                                    <td><?= $data['nitrite']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editWaterQualityModal" onclick="editWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteWaterQualityData(<?= $data['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2"><strong>Mean ± SD</strong></td>
                                <td><?= number_format($phase2StaticStats['meanSalinity'], 2) . " ± " . number_format($phase2StaticStats['sdSalinity'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanTemp'], 2) . " ± " . number_format($phase2StaticStats['sdTemp'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanDO'], 2) . " ± " . number_format($phase2StaticStats['sdDO'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanPH'], 2) . " ± " . number_format($phase2StaticStats['sdPH'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanAlkalinity'], 2) . " ± " . number_format($phase2StaticStats['sdAlkalinity'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanTAN'], 2) . " ± " . number_format($phase2StaticStats['sdTAN'], 2); ?></td>
                                <td><?= number_format($phase2StaticStats['meanNitrite'], 2) . " ± " . number_format($phase2StaticStats['sdNitrite'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal chỉnh sửa -->
<div id="editWaterQualityModal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editWaterQualityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Water Quality Data</h4>
                </div>
                <div class="modal-body">
                    <!-- Các trường dữ liệu cần chỉnh sửa -->
                    <input type="hidden" name="id" id="editWaterQualityId">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Day</label>
                            <input type="date" name="day" class="form-control" id="editDay" readonly>
                            <label>Salinity (ppt)</label>
                            <input type="number" name="salinity" class="form-control" id="editSalinity" step="0.1" required>
                            <label>Temperature (°C)</label>
                            <input type="number" name="temperature" class="form-control" id="editTemperature" step="0.1" required>
                            <label>Dissolved Oxygen (ppm)</label>
                            <input type="number" name="dissolved_oxygen" class="form-control" id="editDissolvedOxygen" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label>pH</label>
                            <input type="number" name="pH" class="form-control" id="editPH" step="0.1" required>
                            <label>Alkalinity (ppm)</label>
                            <input type="number" name="alkalinity" class="form-control" id="editAlkalinity" step="0.1" required>
                            <label>TAN (ppm)</label>
                            <input type="number" name="tan" class="form-control" id="editTAN" step="0.1" required>
                            <label>Nitrite (ppm)</label>
                            <input type="number" name="nitrite" class="form-control" id="editNitrite" step="0.1" required>
                            <label>System Type</label>
                            <input name="system_type" class="form-control" id="editSystemType" readonly>
                            </input>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="updateWaterQualityData()">Save Changes</button>
                    <button type="button" class="btn btn-default btn-close-modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Thông báo Toast -->
<div class="custom-toast-container">
    <div id="toastMessage" class="custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="custom-toast-header">
            <strong id="toastTitle">Notification</strong>
            <button type="button" class="custom-toast-close" onclick="closeToast()" aria-label="Close">&times;</button>
        </div>
        <div class="custom-toast-body" id="toastBody">
            This is a message.
        </div>
    </div>
</div>
<script>
    function showToast(message, title = 'Notification', isSuccess = true) {
    const toastTitle = document.getElementById('toastTitle');
    const toastBody = document.getElementById('toastBody');
    const toastElement = document.getElementById('toastMessage');

    // Đặt tiêu đề và nội dung thông báo
    toastTitle.textContent = title;
    toastBody.textContent = message;

    // Thêm lớp cho kiểu thông báo
    toastElement.classList.remove('bg-success', 'bg-danger');
    toastElement.classList.add(isSuccess ? 'bg-success' : 'bg-danger');

    // Hiển thị toast
    toastElement.classList.add('show');

    // Tự động ẩn toast sau 3 giây
    setTimeout(() => {
        toastElement.classList.remove('show');
    }, 3000);
}

// Hàm đóng toast thủ công
function closeToast() {
    document.getElementById('toastMessage').classList.remove('show');
}
    // Hàm mở modal chỉnh sửa và lấy dữ liệu của entry cần chỉnh sửa
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
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Hàm cập nhật dữ liệu sau khi chỉnh sửa
    function updateWaterQualityData() {
        const formData = $('#editWaterQualityForm').serialize();
        $.ajax({
            url: 'php_action/edit_water_quality.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Water quality data updated successfully!', 'Success', true);

                    $('.btn-close-modal').click();
                    location.reload();
                } else {
                    showToast(response.messages, 'Error', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }

    // Hàm xóa dữ liệu
    function deleteWaterQualityData(id) {
        if (confirm('Are you sure you want to delete this entry?')) {
            $.ajax({
                url: 'php_action/remove_water_quality.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Water quality data deleted successfully!', 'Success', true);
                        location.reload();
                    } else {
                        showToast(response.messages, 'Error', false);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }
    }
</script>

<?php include('./constant/layout/footer.php'); ?>
<style>
/* Container cho toast */
.custom-toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1100;
}
/* Toast container */
.custom-toast {
    display: none; /* Ẩn mặc định */
    padding: 16px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    min-width: 250px;
    max-width: 300px;
    animation: fadeInOut 5s forwards;
    color: #fff;
}

.custom-toast.show {
    display: block;
}

/* Header của toast */
.custom-toast-header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-bottom: 8px;
}

/* Nút đóng toast */
.custom-toast-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
}

/* Nội dung của toast */
.custom-toast-body {
    margin-top: 5px;
}

/* Hiệu ứng fadeIn và fadeOut */
@keyframes fadeInOut {
    0%, 90% { opacity: 1; }
    100% { opacity: 0; }
}

/* Màu sắc cho các loại thông báo */
.custom-toast.bg-success {
    background-color: #28a745;
}
.custom-toast.bg-danger {
    background-color: #dc3545;
}
</style>