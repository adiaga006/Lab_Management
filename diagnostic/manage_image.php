<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images']) && isset($_POST['submit'])) {
    $uploadDate = $_POST['upload_date'];

    if (empty($uploadDate)) {
        echo "<script>showToast('Please select upload date!', 'error');</script>";
    } else if (empty($_FILES['images']['name'][0])) {
        echo "<script>showToast('Please select at least one image!', 'error');</script>";
    } else {
        $uploadDir = __DIR__ . "/../assets/uploadImage/Shrimp_image/$caseStudyId/$uploadDate";

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadSuccess = true;

        // Lấy số thứ tự lớn nhất hiện tại trong thư mục
        $existingFiles = glob("$uploadDir/*");
        $maxIndex = 0;
        foreach ($existingFiles as $file) {
            if (preg_match('/(\d+)\.jpg$/', $file, $matches)) {
                $maxIndex = max($maxIndex, (int) $matches[1]);
            }
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $maxIndex++;
            $fileName = $maxIndex . '.jpg';
            $targetFilePath = "$uploadDir/$fileName";

            if (!move_uploaded_file($tmpName, $targetFilePath)) {
                $uploadSuccess = false;
            }
        }

        if ($uploadSuccess) {
            // Redirect sau khi upload thành công
            $_SESSION['success_message'] = 'Images uploaded successfully!';
            header("Location: " . $_SERVER['PHP_SELF'] . "?case_study_id=" . $caseStudyId);
            exit();
        } else {
            $_SESSION['error_message'] = 'Error uploading some images!';
            header("Location: " . $_SERVER['PHP_SELF'] . "?case_study_id=" . $caseStudyId);
            exit();
        }
    }
}

// Thêm phần hiển thị thông báo từ session
if (isset($_SESSION['success_message'])) {
    echo "<script>showToast('" . $_SESSION['success_message'] . "', 'success');</script>";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo "<script>showToast('" . $_SESSION['error_message'] . "', 'error');</script>";
    unset($_SESSION['error_message']);
}

// Organize images by date
$imagesByDate = [];
if ($caseStudyId) {
    $baseDir = __DIR__ . "/../assets/uploadImage/Shrimp_image/$caseStudyId";
    if (file_exists($baseDir)) {
        $dates = array_diff(scandir($baseDir), ['.', '..']);
        foreach ($dates as $date) {
            $dateDir = "$baseDir/$date";
            if (is_dir($dateDir)) {
                $files = array_diff(scandir($dateDir), ['.', '..']);
                $imagesByDate[$date] = [];
                foreach ($files as $file) {
                    $imagesByDate[$date][] = "../assets/uploadImage/Shrimp_image/$caseStudyId/$date/$file";
                }
            }
        }
        krsort($imagesByDate);
    }
}
?>

<!-- CSS -->
<style>
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        padding: 15px;
    }

    .image-item {
        aspect-ratio: 1;
        overflow: hidden;
    }

    .image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s;
    }

    .image-item:hover img {
        transform: scale(1.05);
    }

    .date-header {
        background: #f8f9fa;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
        font-weight: bold;
    }

    /* Toast CSS */
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px;
        border-radius: 4px;
        z-index: 9999;
        font-size: 14px;
        display: none;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .toast-success {
        background-color: #51a351;
        color: white;
    }

    .toast-error {
        background-color: #bd362f;
        color: white;
    }

    /* Fancybox custom CSS */
    .fancybox__container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        z-index: 9999;
    }

    .fancybox__content {
        position: relative;
        width: auto;
        height: auto;
        max-width: 80%;
        max-height: 80vh;
        margin: 40px auto;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
    }

    .fancybox__image {
        display: block;
        width: auto;
        height: auto;
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    }

    .image-item {
        position: relative;
    }

    .image-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 1;
    }

    .custom-checkbox .custom-control-input:checked~.custom-control-label::before {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    #toggleDelete.active {
        background-color: #28a745;
    }

    /* Custom Confirm Dialog */
    .custom-confirm {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        min-width: 300px;
    }

    .custom-confirm-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .custom-confirm h3 {
        margin-top: 0;
        color: #dc3545;
    }

    .custom-confirm-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .btn i {
        margin-right: 5px;
    }

    /* Thêm animation cho việc xóa ảnh */
    .image-item.removing {
        animation: fadeOut 0.3s ease;
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: scale(0.9);
        }
    }

    /* Custom Alert Box Styles */
    .custom-alert {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .alert-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        text-align: center;
    }

    .alert-buttons {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    /* Image Grid Styles */
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        padding: 15px;
    }

    .image-item {
        position: relative;
    }

    .checkbox-wrapper {
        position: absolute;
        top: 5px;
        left: 5px;
        z-index: 2;
    }

    .image-item img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
    }

    /* Button Styles */
    .btn i {
        margin-right: 5px;
    }

    /* Dialog Styles */
    .custom-dialog {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .dialog-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .dialog-buttons {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
</style>

<div class="page-wrapper">
    <div class="container-fluid">
        <h3 class="text-primary">Manage Images for Case Study: <?php echo htmlspecialchars($caseStudyId); ?></h3>
        <button class="btn btn-primary mb-4" data-toggle="modal" data-target="#addImageModal">Add New Image</button>

        <!-- Modal -->
        <div class="modal" id="addImageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Image</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="upload_date">Upload Date:</label>
                                <input type="text" name="upload_date" id="uploadDatePicker" class="form-control"
                                    required>
                                <input type="hidden" name="case_study_id"
                                    value="<?php echo htmlspecialchars($caseStudyId); ?>">
                            </div>
                            <div class="form-group">
                                <label for="images">Select Images:</label>
                                <input type="file" name="images[]" multiple class="form-control" required
                                    accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-success">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="form-inline mb-4">
            <input type="text" id="filterDate" class="form-control mr-2" placeholder="Filter by date (d-m-Y)">
            <button class="btn btn-secondary mr-2" onclick="filterImages()">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button class="btn btn-warning mr-2" onclick="resetFilter()">
                <i class="fas fa-undo"></i> Reset
            </button>
            <button class="btn btn-danger mr-2" id="toggleDelete" onclick="toggleDeleteMode()">
                <i class="fas fa-trash"></i> Delete Images
            </button>
        </div>

        <!-- Confirm Dialog -->
        <div id="confirmDialog" class="custom-dialog" style="display: none;">
            <div class="dialog-content">
                <h4><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h4>
                <p>Are you sure you want to delete the selected images?</p>
                <div class="dialog-buttons">
                    <button onclick="proceedDelete()" class="btn btn-danger">
                        <i class="fas fa-check"></i> Yes, Delete
                    </button>
                    <button onclick="closeDialog()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Images Display -->
        <div id="imageGallery">
            <?php foreach ($imagesByDate as $date => $images): ?>
                <div class="img-container" data-date="<?php echo $date; ?>">
                    <div class="date-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Upload Date: <?php echo $date; ?></span>
                            <div class="select-all-container" style="display: none;">
                                <input type="checkbox" id="selectAll_<?php echo $date; ?>" class="select-all"
                                    data-date="<?php echo $date; ?>">
                                <label for="selectAll_<?php echo $date; ?>">Select All</label>
                            </div>
                        </div>
                    </div>
                    <div class="image-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="image-item">
                                <div class="checkbox-wrapper" style="display: none;">
                                    <input type="checkbox" class="image-select" id="<?php echo basename($image); ?>"
                                        data-date="<?php echo $date; ?>" data-path="<?php echo $image; ?>">
                                </div>
                                <a data-fancybox="gallery" href="<?php echo $image; ?>"
                                    data-caption="Upload Date: <?php echo $date; ?>">
                                    <img src="<?php echo $image; ?>" alt="Image" class="img-fluid">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css" />
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast toast-' + type;
        toast.style.display = 'block';

        setTimeout(function () {
            toast.style.display = 'none';
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr
        flatpickr("#uploadDatePicker", {
            dateFormat: "d-m-Y"
        });

        flatpickr("#filterDate", {
            dateFormat: "d-m-Y"
        });

        // Form submission with AJAX
        document.getElementById('uploadForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Disable submit button
            submitButton.disabled = true;
            submitButton.innerHTML = 'Uploading...';

            // Show loading message
            showToast('Uploading images...', 'info');

            fetch('upload_images.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON:', text);
                            throw new Error('Invalid server response');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        showToast('Upload successful!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message || 'Upload failed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Upload failed: ' + error.message, 'error');
                })
                .finally(() => {
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Upload';
                });
        });

        // Ensure elements exist before adding event listeners
        const confirmBackdrop = document.querySelector('.custom-confirm-backdrop');
        const confirmDialog = document.querySelector('.custom-confirm');

        if (!confirmBackdrop || !confirmDialog) {
            console.error('Confirm dialog elements not found!');
            return;
        }

        // Update showConfirmDialog function
        window.showConfirmDialog = function () {
            confirmBackdrop.style.display = 'block';
            confirmDialog.style.display = 'block';
        };

        // Update closeConfirmDialog function
        window.closeConfirmDialog = function () {
            confirmBackdrop.style.display = 'none';
            confirmDialog.style.display = 'none';
        };

        // Add click event to backdrop to close dialog
        confirmBackdrop.addEventListener('click', closeConfirmDialog);

        // Kiểm tra xem có phần tử nào có thuộc tính data-fancybox không
        const elements = document.querySelectorAll('[data-fancybox]');
        if (elements.length > 0) {
            try {
                // Khởi tạo với cấu hình đơn giản hơn
                Fancybox.bind("[data-fancybox]", {
                    // Tùy chọn cơ bản
                    loop: true,
                    buttons: ["zoom", "close"]
                });
            } catch (error) {
                console.error('Fancybox initialization error:', error);
            }
        } else {
            console.warn('No elements with data-fancybox attribute found');
        }
    });

    function filterImages() {
        const filterDate = document.getElementById('filterDate').value;
        const containers = document.querySelectorAll('.img-container');

        containers.forEach(container => {
            const containerDate = container.dataset.date;
            container.style.display = filterDate === '' || containerDate === filterDate ? 'block' : 'none';
        });
    }

    function resetFilter() {
        document.getElementById('filterDate').value = '';
        const containers = document.querySelectorAll('.img-container');
        containers.forEach(container => {
            container.style.display = 'block';
        });
    }

    function updateDeleteButton() {
        const selectedImages = document.querySelectorAll('.image-select:checked');
        let deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        if (selectedImages.length > 0 && deleteMode) {
            if (!deleteSelectedBtn) {
                deleteSelectedBtn = document.createElement('button');
                deleteSelectedBtn.id = 'deleteSelectedBtn';
                deleteSelectedBtn.className = 'btn btn-danger';
                deleteSelectedBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Selected Images';
                deleteSelectedBtn.onclick = showConfirmDialog;
                document.querySelector('.form-inline').appendChild(deleteSelectedBtn);
            }
        } else if (deleteSelectedBtn) {
            deleteSelectedBtn.remove();
        }
    }

    let deleteMode = false;

    function toggleDeleteMode() {
        deleteMode = !deleteMode;
        const toggleBtn = document.getElementById('toggleDelete');
        const checkboxWrappers = document.querySelectorAll('.checkbox-wrapper');
        const selectAllContainers = document.querySelectorAll('.select-all-container');

        toggleBtn.classList.toggle('active');
        toggleBtn.innerHTML = deleteMode ?
            '<i class="fas fa-times"></i> Cancel Delete' :
            '<i class="fas fa-trash"></i> Delete Images';

        checkboxWrappers.forEach(wrapper => {
            wrapper.style.display = deleteMode ? 'block' : 'none';
        });

        selectAllContainers.forEach(container => {
            container.style.display = deleteMode ? 'block' : 'none';
        });

        if (!deleteMode) {
            document.querySelectorAll('.image-select, .select-all').forEach(checkbox => {
                checkbox.checked = false;
            });
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            if (deleteSelectedBtn) {
                deleteSelectedBtn.remove();
            }
        }

        updateDeleteButton();
    }

    // Handle Select All functionality
    document.querySelectorAll('.select-all').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const date = this.dataset.date;
            const imageCheckboxes = document.querySelectorAll(`.image-select[data-date="${date}"]`);
            imageCheckboxes.forEach(box => {
                box.checked = this.checked;
            });
            updateDeleteButton();
        });
    });

    // Handle individual checkbox changes
    document.querySelectorAll('.image-select').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeleteButton();

            const date = this.dataset.date;
            const selectAll = document.querySelector(`#selectAll_${date}`);
            const imageCheckboxes = document.querySelectorAll(`.image-select[data-date="${date}"]`);
            const allChecked = Array.from(imageCheckboxes).every(box => box.checked);
            if (selectAll) {
                selectAll.checked = allChecked;
            }
        });
    });

    function showCustomAlert() {
        document.getElementById('customAlert').style.display = 'flex';
    }

    function closeAlert() {
        document.getElementById('customAlert').style.display = 'none';
    }

    async function confirmDelete() {
        const selectedImages = Array.from(document.querySelectorAll('.image-select:checked'))
            .map(checkbox => checkbox.dataset.path);

        if (selectedImages.length === 0) {
            alert('No images selected');
            return;
        }

        try {
            const response = await fetch('delete_images.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ images: selectedImages })
            });

            const result = await response.json();

            if (result.success) {
                // Remove deleted images from DOM
                selectedImages.forEach(imagePath => {
                    const imageElement = document.querySelector(`[data-path="${imagePath}"]`).closest('.image-item');
                    imageElement.remove();
                });

                closeAlert();
                toggleDeleteMode();
            } else {
                alert('Error deleting images: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting images');
        }
    }

    function showConfirmDialog() {
        const selectedImages = document.querySelectorAll('.image-select:checked');
        if (selectedImages.length === 0) {
            alert('Please select images to delete');
            return;
        }
        const confirmDialog = document.getElementById('confirmDialog');
        if (confirmDialog) {
            confirmDialog.style.display = 'flex';
        }
    }

    function closeDialog() {
        const confirmDialog = document.getElementById('confirmDialog');
        if (confirmDialog) {
            confirmDialog.style.display = 'none';
        }
    }

    async function proceedDelete() {
        const selectedImages = Array.from(document.querySelectorAll('.image-select:checked'))
            .map(checkbox => checkbox.dataset.path);

        try {
            const response = await fetch('delete_images.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ images: selectedImages })
            });

            const result = await response.json();

            if (result.success) {
                selectedImages.forEach(imagePath => {
                    const imageElement = document.querySelector(`[data-path="${imagePath}"]`)
                        .closest('.image-item');
                    if (imageElement) {
                        imageElement.remove();
                    }
                });

                closeDialog();
                toggleDeleteMode();
            } else {
                alert('Error deleting images: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting images');
        }
    }

    // Update the delete button click handler
    document.getElementById('deleteSelected').addEventListener('click', showConfirmDialog);
</script>

<?php include('./constant/layout/footer.php'); ?>

<!-- Thêm Font Awesome cho icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">