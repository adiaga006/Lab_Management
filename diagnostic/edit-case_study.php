<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>
<?php include('./constant/connect.php');

// Lấy dữ liệu của case study từ database
$sql = "SELECT * FROM case_study WHERE case_study_id='" . $_GET['id'] . "'";
$result = $connect->query($sql)->fetch_assoc();
?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">
                Edit Case Study: "<?php echo htmlspecialchars($_GET['id']); ?>"
            </h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Edit Case Study</li>
            </ol>
        </div>
    </div>


    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-body">
                        <div class="input-states">
                            <!-- Form cập nhật thông tin case study -->
                            <form class="form-horizontal" method="POST" id="submitCaseStudyForm"
                                action="php_action/editCaseStudy.php?id=<?php echo $_GET['id']; ?>"
                                enctype="multipart/form-data">
                                <fieldset>
                                    <h1>Case Study Info</h1>

                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Case Study Name</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editCaseStudyName"
                                                    value="<?php echo $result['case_name']; ?>" name="editCaseStudyName"
                                                    autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Location</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editLocation"
                                                    value="<?php echo $result['location']; ?>" name="editLocation"
                                                    autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Start Date -->
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Start Date</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="editStartDate"
                                                    value="<?php echo date('d-m-Y', strtotime($result['start_date'])); ?>"
                                                    name="editStartDate" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Type of Case Study</label>
                                            <div class="col-sm-9">
                                                <select id="categoryId" name="editCategoryName" required
                                                    class="form-control">
                                                    <?php
                                                    $sql = "SELECT * FROM categories WHERE categories_status=1";
                                                    $categoriesResult = mysqli_query($connect, $sql);
                                                    while ($row = mysqli_fetch_assoc($categoriesResult)) {
                                                    ?>
                                                        <option value="<?php echo $row['categories_id']; ?>" <?php if ($result['categories_id'] == $row['categories_id'])
                                                                                                                    echo "selected"; ?>>
                                                            <?php echo $row['categories_name']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Farm or Lab</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="categoryName" name="category_name"
                                                    required>
                                                    <option value="lab" <?php echo (isset($result['category_name']) && strtolower($result['category_name']) == 'lab') ? 'selected' : ''; ?>>Lab</option>
                                                    <option value="farm" <?php echo (isset($result['category_name']) && strtolower($result['category_name']) == 'farm') ? 'selected' : ''; ?>>Farm</option>
                                                </select>
                                                <small class="form-text text-muted">
                                                    This is a case study that takes place in a <?php echo isset($result['category_name']) ? htmlspecialchars($result['category_name']) : 'Lab'; ?>.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <label class="col-sm-3 control-label">Status</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="editCaseStudyStatus"
                                                    name="editCaseStudyStatus">
                                                    <option value="Prepare" <?php if ($result['status'] == "Prepare")
                                                                                echo "selected"; ?>>Prepare</option>
                                                    <option value="In-process" <?php if ($result['status'] == "In-process")
                                                                                    echo "selected"; ?>>In-process</option>
                                                    <option value="Complete" <?php if ($result['status'] == "Complete")
                                                                                    echo "selected"; ?>>Complete</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="phasesContainer" style="display: none;">
                                        <h3>Define Phases</h3>
                                        <?php
                                        // Parse JSON phases từ database
                                        $phases = json_decode($result['phases'], true);
                                        $defaultPhases = [
                                            'Acclimation period' => 0,
                                            'Pre-challenge' => 0,
                                            'Challenge' => 0,
                                            'Post-challenge' => 0
                                        ];

                                        // Kết hợp phases từ database với default phases
                                        foreach ($defaultPhases as $phaseName => $defaultDuration) {
                                            $currentDuration = 0;
                                            if ($phases) {
                                                foreach ($phases as $phase) {
                                                    if ($phase['name'] === $phaseName) {
                                                        $currentDuration = $phase['duration'];
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                            <div class="form-group">
                                                <label>Phase: <?php echo htmlspecialchars($phaseName); ?></label>
                                                <input type="number"
                                                    name="phases[<?php echo htmlspecialchars($phaseName); ?>]"
                                                    class="form-control"
                                                    value="<?php echo htmlspecialchars($currentDuration); ?>"
                                                    placeholder="Duration in days" required>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="col-sm-9">
                                        <label class="section-title">Define Treatments</label>
                                        <div class="col-sm-9">
                                            <div id="treatmentsContainer">
                                                <?php
                                                $treatments = json_decode($result['treatment'], true);
                                                if (!empty($treatments)) {
                                                    foreach ($treatments as $treatment) {
                                                ?>
                                                        <div class="treatmentRow">
                                                            <label class="form-label">Treatment Name</label>
                                                            <input type="text" name="treatment_name[]" class="form-control"
                                                                value="<?php echo htmlspecialchars($treatment['name']); ?>"
                                                                placeholder="Treatment Name" required>
                                                            <label class="form-label">Product Application</label>
                                                            <input type="text" name="product_application[]" class="form-control"
                                                                value="<?php echo htmlspecialchars($treatment['product_application']); ?>"
                                                                placeholder="Product Application" required>
                                                            <label class="form-label">Num Reps</label>
                                                            <input type="number" name="num_reps[]" class="form-control"
                                                                value="<?php echo htmlspecialchars($treatment['num_reps']); ?>"
                                                                placeholder="Num Reps" min="1" required>
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm removeTreatmentRow">Remove</button>
                                                        </div>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <button type="button" id="addTreatmentRow"
                                                class="btn btn-secondary btn-sm">Add Treatment</button>
                                        </div>
                                        <button type="submit" name="update" id="updateCaseStudyBtn"
                                            class="btn btn-primary btn-flat m-b-30 m-t-30">Update</button>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Thông báo Toast -->
    <div class="custom-toast-container">
        <div id="toastMessage" class="custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="custom-toast-header">
                <strong id="toastTitle">Notification</strong>
                <button type="button" class="custom-toast-close" onclick="closeToast()"
                    aria-label="Close">&times;</button>
            </div>
            <div class="custom-toast-body" id="toastBody">
                This is a message.
            </div>
        </div>
    </div>
    <!-- Thêm Flatpickr CSS và JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.getElementById('addTreatmentRow').addEventListener('click', function() {
            const treatmentsContainer = document.getElementById('treatmentsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'treatmentRow';
            newRow.innerHTML = `
        <label class="form-label">Treatment Name</label>
        <input type="text" name="treatment_name[]" class="form-control" placeholder="Treatment Name" required>
        <label class="form-label">Product Application</label>
        <input type="text" name="product_application[]" class="form-control" placeholder="Product Application" required>
        <label class="form-label">Num Reps</label>
        <input type="number" name="num_reps[]" class="form-control" placeholder="Num Reps" min="1" required>
        <button type="button" class="btn btn-danger btn-sm removeTreatmentRow">Remove</button>
    `;
            treatmentsContainer.appendChild(newRow);

            // Thêm sự kiện xóa vào nút Remove
            newRow.querySelector('.removeTreatmentRow').addEventListener('click', function() {
                newRow.remove();
            });
        });
        // Thêm sự kiện xóa cho các dòng `treatment` đã tải từ server
        document.querySelectorAll('.removeTreatmentRow').forEach(button => {
            button.addEventListener('click', function() {
                button.parentElement.remove();
            });
        });

        document.addEventListener('wheel', function(event) {
            if (document.activeElement.type === 'number') {
                event.preventDefault();
            }
        }, {
            passive: false
        });
        document.getElementById('submitCaseStudyForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn chặn hành vi gửi form mặc định

            const form = document.getElementById('submitCaseStudyForm');
            const formData = new FormData(form);

            fetch('php_action/editCaseStudy.php?id=<?php echo $_GET['id']; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hiển thị thông báo thành công
                        showToast('Case Study has been successfully updated!', 'Success', true);

                        // Quay lại trang case_study.php sau 1 giây
                        setTimeout(() => {
                            window.location.href = 'case_study.php';
                        }, 1000);
                    } else {
                        // Hiển thị lỗi nếu có
                        alert('Error: ' + data.messages);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(response.messages, 'Error', false);
                });
        });

        //Hàm để kiểm tra và hiển thị/ẩn Define Phases
        function togglePhasesVisibility() {
            const categoryElement = document.getElementById('categoryId');
            const selectedCategory = categoryElement.value;
            const phasesContainer = document.getElementById('phasesContainer');
            phasesContainer.style.display = (selectedCategory === '1') ? 'block' : 'none';
        }

        // Gọi hàm khi giá trị Category thay đổi
        document.getElementById('categoryId').addEventListener('change', togglePhasesVisibility);

        // Gọi hàm khi trang được tải lần đầu
        document.addEventListener('DOMContentLoaded', togglePhasesVisibility);
        // Cấu hình Flatpickr để hiển thị theo định dạng DD-MM-YYYY cho các trường ngày
        flatpickr("#editStartDate", {
            dateFormat: "d-m-Y", // Định dạng hiển thị DD-MM-YYYY
            altInput: true,
            altFormat: "d-m-Y"
        });

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
        document.getElementById('categoryName').addEventListener('change', function() {
            const selectedValue = this.value;
            const capitalizedValue = selectedValue.charAt(0).toUpperCase() + selectedValue.slice(1);
            const helpText = this.parentElement.querySelector('.form-text');
            helpText.textContent = `This is a case study that takes place in a ${capitalizedValue}.`;
        });
    </script>
    <?php include('./constant/layout/footer.php'); ?>
    <style>
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Container cho toast */
        .custom-toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
        }

        /* Toast container */
        .custom-toast {
            display: none;
            /* Ẩn mặc định */
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

            0%,
            90% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        /* Màu sắc cho các loại thông báo */
        .custom-toast.bg-success {
            background-color: #28a745;
        }

        .custom-toast.bg-danger {
            background-color: #dc3545;
        }

        /* Tiêu đề của mỗi phần */
        .section-title {
            font-size: 1.5rem;
            /* Kích thước chữ lớn hơn */
            font-weight: bold;
            color: #333;
            /* Màu chữ đậm */
            margin-bottom: 20px;
            /* Tạo khoảng cách giữa tiêu đề và nội dung */
            text-transform: uppercase;
            /* Chữ in hoa */
        }

        /* Căn chỉnh tiêu đề và nhãn */
        .form-label {
            font-size: 1rem;
            /* Cỡ chữ vừa đủ */
            font-weight: 600;
            /* Chữ đậm hơn cho dễ nhìn */
            margin-bottom: 10px;
            /* Khoảng cách giữa tiêu đề và trường nhập liệu */
            display: block;
        }

        /* Định dạng ô nhập liệu */
        .treatmentRow {
            margin-bottom: 20px;
            /* Khoảng cách giữa các dòng trong Define Treatments */
            padding: 10px;
            /* Khoảng cách nội bộ */
            border-bottom: 1px solid #ddd;
            /* Đường ngăn cách */
        }

        .treatmentRow:last-child {
            border-bottom: none;
            /* Xóa đường ngăn cuối */
        }

        /* Khoảng cách hợp lý giữa các nút */
        button {
            margin-top: 10px;
        }

        /* Căn chỉnh nút Remove */
        .removeTreatmentRow {
            margin-top: 10px;
            /* Khoảng cách giữa trường nhập liệu và nút */
        }
    </style>