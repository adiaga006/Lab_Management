<?php include('./constant/layout/head.php'); ?>
<?php include('./constant/layout/header.php'); ?>
<?php include('./constant/layout/sidebar.php'); ?>

<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">Add Case Study</h3>
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Add Case Study</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8" style="margin-left: 10%;">
                <div class="card">
                    <div class="card-body">
                        <div id="message" style="display: none;" class="alert"></div>

                        <div class="input-states">
                            <form class="form-horizontal" method="POST" id="submitCaseStudyForm"
                                action="php_action/createCaseStudy.php" enctype="multipart/form-data">

                                <!-- Case Study ID -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Case Study ID</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="caseStudyId"
                                                name="case_study_id" placeholder="Enter Case Study ID" required="" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Case Name -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Case Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="caseName" name="case_name"
                                                placeholder="Enter Case Name" required="" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Location -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Location</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="location" name="location"
                                                placeholder="Enter Location" required="" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Category -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Category</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="categoryId" name="categories_id"
                                                required="">
                                                <option value="">~~SELECT~~</option>
                                                <?php
                                                $sql = "SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 AND categories_active = 1";
                                                $result = $connect->query($sql);

                                                while ($row = $result->fetch_array()) {
                                                    echo "<option value='" . $row['categories_id'] . "'>" . $row['categories_name'] . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Start Date</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="startDate" name="start_date"
                                                placeholder="DD-MM-YYYY" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Status -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Status</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" id="status" name="status" required="">
                                                <option value="">~~SELECT~~</option>
                                                <option value="Prepare">Prepare</option>
                                                <option value="In-process">In-process</option>
                                                <option value="Complete">Complete</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="phasesContainer" style="display: none;">
                                    <h3>Define Phases</h3>
                                    <div class="form-group">
                                        <label>Define duration of Phase</label>
                                        <input type="text" name="phase_name[]" value="Acclimation period" readonly
                                            class="form-control">
                                        <input type="number" name="phase_duration[]" class="form-control"
                                            placeholder="Duration in days" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="phase_name[]" value="Pre-challenge" readonly
                                            class="form-control">
                                        <input type="number" name="phase_duration[]" class="form-control"
                                            placeholder="Duration in days" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="phase_name[]" value="Challenge" readonly
                                            class="form-control">
                                        <input type="number" name="phase_duration[]" class="form-control"
                                            placeholder="Duration in days" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="phase_name[]" value="Post-challenge" readonly
                                            class="form-control">
                                        <input type="number" name="phase_duration[]" class="form-control"
                                            placeholder="Duration in days" required>
                                    </div>
                                </div>
                                <!-- Treatments Section -->
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-3 control-label">Treatments</label>
                                        <div class="col-sm-9">
                                            <div id="treatmentsContainer">
                                                <div class="treatmentRow">
                                                    <input type="text" name="treatment_name[]" class="form-control"
                                                        placeholder="Treatment Name" required>
                                                    <input type="text" name="product_application[]" class="form-control"
                                                        placeholder="Product Application" required>
                                                    <input type="number" name="num_reps[]" class="form-control"
                                                        placeholder="Num Reps" min="1"required>
                                                </div>
                                            </div>
                                            <button type="button" id="addTreatmentRow"
                                                class="btn btn-secondary btn-sm">Add Treatment</button>
                                        </div>
                                    </div>
                                </div>


                                <button type="submit" class="btn btn-primary">Submit</button>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="customNotification" class="notification hidden">
            <span id="notificationMessage"></span>
            <button id="closeNotification" class="close-btn">&times;</button>
        </div>
        <?php include('./constant/layout/footer.php'); ?>

        <!-- Thêm Flatpickr CSS và JS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            document.addEventListener('wheel', function (event) {
                if (document.activeElement.type === 'number') {
                    event.preventDefault();
                }
            }, { passive: false });
            // Lắng nghe sự kiện thay đổi trên dropdown của Category
            document.getElementById('categoryId').addEventListener('change', function () {
                const selectedCategory = this.value; // Lấy giá trị được chọn
                const definePhasesSection = document.getElementById('phasesContainer'); // Phần Define Phases

                if (selectedCategory === '1') { // Chỉ hiển thị nếu categories_id = 1
                    definePhasesSection.style.display = 'block';
                } else {
                    definePhasesSection.style.display = 'none';
                }
            });
            // Cấu hình Flatpickr để hiển thị theo định dạng DD-MM-YYYY
            flatpickr("#startDate", {
                dateFormat: "d-m-Y",  // Định dạng hiển thị DD-MM-YYYY
                altInput: true,
                altFormat: "d-m-Y"
            });
            // Hàm chuyển đổi từ DD-MM-YYYY sang YYYY-MM-DD
            function formatDateToYYYYMMDD(date) {
                const [day, month, year] = date.split("-");
                return `${year}-${month}-${day}`;
            }
            function showNotification(message, type = 'success') {
                const notification = document.getElementById('customNotification');
                const notificationMessage = document.getElementById('notificationMessage');
                const closeNotification = document.getElementById('closeNotification');

                // Thay đổi thông báo và kiểu
                notificationMessage.innerText = message;
                notification.className = `notification ${type} show`;

                // Đóng thông báo khi nhấn nút đóng
                closeNotification.addEventListener('click', () => {
                    hideNotification();
                });

                // Tự động ẩn thông báo sau 3 giây
                setTimeout(() => {
                    hideNotification();
                }, 3000);
            }

            function hideNotification() {
                const notification = document.getElementById('customNotification');
                notification.className = 'notification hidden';
            }

            // Sử dụng thông báo trong Fetch API
            document.getElementById('submitCaseStudyForm').addEventListener('submit', function (e) {
                e.preventDefault(); // Ngăn chặn gửi form mặc định
                const startDateInput = document.getElementById('startDate');

                // Chuyển đổi ngày về định dạng YYYY-MM-DD
                if (startDateInput.value) {
                    startDateInput.value = formatDateToYYYYMMDD(startDateInput.value);
                }

                // Gửi form qua Fetch API
                const form = document.getElementById('submitCaseStudyForm');
                const formData = new FormData(form);

                fetch('php_action/createCaseStudy.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.messages, 'success');
                            setTimeout(() => {
                                window.location.href = 'case_study.php';
                            }, 2000);
                        } else {
                            showNotification(data.messages, 'error');
                        }
                    })
                    .catch(() => {
                        showNotification('An error occurred while processing your request.', 'error');
                    });
            });
            document.getElementById('addTreatmentRow').addEventListener('click', function () {
                const treatmentsContainer = document.getElementById('treatmentsContainer');

                // Tạo một hàng mới
                const newRow = document.createElement('div');
                newRow.className = 'treatmentRow';
                newRow.style.marginBottom = '10px';

                // Nội dung của hàng mới
                newRow.innerHTML = `
        <input type="text" name="treatment_name[]" class="form-control" placeholder="Treatment Name" required>
        <input type="text" name="product_application[]" class="form-control" placeholder="Product Application" required>
        <input type="number" name="num_reps[]" class="form-control" placeholder="Num Reps" min="1" required>
        <button type="button" class="btn btn-danger btn-sm removeTreatmentRow">Remove</button>
    `;

                treatmentsContainer.appendChild(newRow);

                // Thêm sự kiện xóa hàng
                newRow.querySelector('.removeTreatmentRow').addEventListener('click', function () {
                    newRow.remove();
                });
            });


        </script>
        <style>
            .treatmentRow {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
                align-items: center;
            }

            .treatmentRow input {
                flex: 1;
            }

            .removeTreatmentRow {
                background-color: #f44336;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 5px;
                cursor: pointer;
            }

            .removeTreatmentRow:hover {
                background-color: #d32f2f;
            }

            .phaseRow {
                margin-bottom: 10px;
            }

            .phaseRow label {
                font-weight: bold;
                display: block;
            }

            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                background-color: #444;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
                transform: translateY(-20px);
            }

            .notification.show {
                opacity: 1;
                transform: translateY(0);
            }

            .notification.success {
                background-color: #4caf50;
            }

            .notification.error {
                background-color: #f44336;
            }

            .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
            }
        </style>