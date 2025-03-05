<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = isset($_GET['case_study_id']) ? $_GET['case_study_id'] : 0;

if (!$caseStudyId) {
    die("Error: Missing case_study_id in URL");
}

?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Shrimp Weight Management</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Shrimp Weight</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="shrimpWeightTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Weight (g)</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM shrimp_weight WHERE case_study_id = ? ORDER BY no_shrimp ASC";
                            $stmt = $connect->prepare($sql);
                            $stmt->bind_param("s", $caseStudyId);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['no_shrimp']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['weight']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                echo "<td>
                                        <button class='btn btn-warning btn-sm' onclick='editWeight(" . json_encode($row) . ")'><i class='fa fa-edit'></i></button>
                                        <button class='btn btn-danger btn-sm' onclick='deleteWeight(" . $row['id'] . ")'><i class='fa fa-trash'></i></button>
                                    </td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editWeightModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Weight</h5>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editWeightForm">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label>Weight (g)</label>
                        <input type="number" id="editWeight" class="form-control" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="updateWeight()">Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#shrimpWeightTable').DataTable();
});

function closeModal() {
    $('#editWeightModal').modal('hide');
    $('#editWeightModal').removeClass('show');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
}

function editWeight(data) {
    $('#editId').val(data.id);
    $('#editWeight').val(data.weight);
    $('#editWeightModal').modal('show');
}

function updateWeight() {
    const id = $('#editId').val();
    const weight = $('#editWeight').val();

    $.ajax({
        url: 'php_action/edit_shrimp_weight.php',
        method: 'POST',
        data: { id, weight },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Weight updated successfully'
                }).then(() => {
                    closeModal();
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        }
    });
}

// Xử lý khi modal đóng
$('#editWeightModal').on('hidden.bs.modal', function () {
    $('#editWeightForm')[0].reset();
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
});

// Xử lý khi nhấn ESC
$(document).keydown(function(e) {
    if (e.keyCode === 27) { // ESC key
        closeModal();
    }
});

function deleteWeight(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php_action/delete_shrimp_weight.php',
                method: 'POST',
                data: { id },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        Swal.fire(
                            'Deleted!',
                            'Record has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            data.message,
                            'error'
                        );
                    }
                }
            });
        }
    });
}
</script>

<?php include('./constant/layout/footer.php'); ?> 