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

// Organize media by date
$mediaByDate = [];

// Get images
if ($caseStudyId) {
    $imageBaseDir = __DIR__ . "/../assets/uploadImage/Shrimp_image/$caseStudyId";
    if (file_exists($imageBaseDir)) {
        $dates = array_diff(scandir($imageBaseDir), ['.', '..']);
        foreach ($dates as $date) {
            $dateDir = "$imageBaseDir/$date";
            if (is_dir($dateDir)) {
                $files = array_diff(scandir($dateDir), ['.', '..']);
                if (!isset($mediaByDate[$date])) {
                    $mediaByDate[$date] = [];
                }
                foreach ($files as $file) {
                    $mediaByDate[$date][] = [
                        'path' => "../assets/uploadImage/Shrimp_image/$caseStudyId/$date/$file",
                        'type' => 'image'
                    ];
                }
            }
        }
    }

    // Get videos - cùng cấu trúc với images
    $videoBaseDir = __DIR__ . "/../assets/uploadVideo/$caseStudyId";
    if (file_exists($videoBaseDir)) {
        $dates = array_diff(scandir($videoBaseDir), ['.', '..']);
        foreach ($dates as $date) {
            $dateDir = "$videoBaseDir/$date";
            if (is_dir($dateDir)) {
                $files = array_diff(scandir($dateDir), ['.', '..']);
                if (!isset($mediaByDate[$date])) {
                    $mediaByDate[$date] = [];
                }
                foreach ($files as $file) {
                    $mediaByDate[$date][] = [
                        'path' => "../assets/uploadVideo/$caseStudyId/$date/$file",
                        'type' => 'video'
                    ];
                }
            }
        }
    }
}

// Trước phần hiển thị, thêm code sắp xếp
function compareDates($date1, $date2)
{
    // Chuyển đổi từ dd-mm-yyyy sang timestamp
    $d1 = DateTime::createFromFormat('d-m-Y', $date1);
    $d2 = DateTime::createFromFormat('d-m-Y', $date2);

    if ($d1 && $d2) {
        // Đổi dấu để sắp xếp giảm dần (mới nhất lên đầu)
        return $d2->getTimestamp() - $d1->getTimestamp();
    }
    return 0;
}
// Sắp xếp mảng theo key (ngày) giảm dần
uksort($mediaByDate, 'compareDates');
?>

<!-- CSS -->
<style>
    .image-item {
        aspect-ratio: 1;
        overflow: hidden;
        position: relative;
    }

    .image-item:hover img {
        transform: scale(1.05);
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

    .checkbox-wrapper {
        position: absolute;
        top: 5px;
        left: 5px;
        z-index: 2;
    }

    .image-item img {
        object-fit: cover;
        border-radius: 4px;
        transition: transform 0.2s;
        width: 100%;
        height: 100%;
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

    /* CSS for video display */
    .video-item {
        position: relative;
        width: 150px;
        height: 150px;
        overflow: hidden;
    }

    .video-thumbnail {
        width: 100%;
        height: 100%;
        display: block;
        position: relative;
        background: #000;
    }

    .video-thumbnail video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-play-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 40px;
        text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        z-index: 2;
        pointer-events: none;
    }

    .video-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        z-index: 1;
        pointer-events: none;
    }

    .fancybox__container video {
        max-width: 100%;
        max-height: 80vh;
    }

    /* Tùy chỉnh nút download */
    .fancybox__toolbar__items--right .carousel__button.is-download {
        display: block;
    }

    .fancybox__download {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        z-index: 20;
    }

    /* Tùy chỉnh video thumbnail */
    .video-item {
        position: relative;
        aspect-ratio: 16/9;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .video-thumbnail {
        width: 100%;
        height: 100%;
        position: relative;
        background: #000;
    }

    .video-thumbnail video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-item:hover .video-play-icon {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1);
    }

    .fancybox__toolbar {
        --fancybox-accent-color: #007bff;
    }

    /* Nút download trong Fancybox */
    .fancybox__toolbar__items .carousel__button.is-download {
        color: white;
        background: #007bff;
        border-radius: 4px;
        padding: 8px 12px;
        margin-right: 8px;
    }

    .fancybox__toolbar__items .carousel__button.is-download:hover {
        background: #0056b3;
    }

    /* Icon cho nút download */
    .fancybox__toolbar__items .carousel__button.is-download::before {
        content: '\f019';
        /* Font Awesome download icon */
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        margin-right: 5px;
    }

    /* Tùy chỉnh controls video */
    .fancybox__container video {
        border-radius: 8px;
    }

    .fancybox__container .fancybox__content {
        padding: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    /* Download button trong video player */
    .video-download-btn {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 3;
        transition: all 0.3s ease;
    }

    .video-download-btn:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }

    .video-download-btn i {
        font-size: 1.2em;
    }

    /* Style cho tiêu đề chính */
    .page-title {
        font-size: 2.5rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #2ecc71;
        animation: fadeIn 0.5s ease;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .page-title i {
        color: #2ecc71;
        font-size: 2.2rem;
        margin-top: 10px; /* Giảm khoảng cách giữa nút trên và nút dưới */

    }

    .page-title h3 {
        color: #222;
        font-weight: 700;
        margin-top: 5px; /* Giảm khoảng cách giữa nút trên và nút dưới */

    }

    .page-title span {
        color: #2ecc71;
        font-weight: 700;
        margin-top: 5px; /* Giảm khoảng cách giữa nút trên và nút dưới */

    }

    /* Style cho filter date */
    .date-filter {
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .date-filter input[type="text"] {
        padding-left: 30px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .date-filter::before {
        content: '\f073';
        font-family: 'Font Awesome 6 Free';
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        pointer-events: none;
    }

    .date-filter i {
        position: absolute;
        right: 10px;
        color: #3498db;
        font-size: 1.2em;
    }

    /* Style cho nút Add New */
    .btn-add-new {
        background: linear-gradient(45deg, #2ecc71, #27ae60);
        border: none;
        padding: 10px 20px;
        color: white;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .btn-add-new:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .btn-add-new i {
        font-size: 1.2em;
    }

    /* Style cho Upload Date header */
    .date-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 15px 20px;
        margin: 20px 0;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #2ecc71;
        animation: slideInLeft 0.3s ease;
    }

    .date-header span {
        font-size: 1.2rem;
        color: #2c3e50;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .date-header i {
        color: #2ecc71;
        font-size: 1.3rem;
    }

    /* Style cho confirm dialog */
    .custom-confirm {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        animation: fadeInUp 0.3s ease;
        max-width: 400px;
        width: 90%;
    }

    .custom-confirm h5 {
        color: #e74c3c;
        font-size: 1.4rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .custom-confirm h5 i {
        font-size: 1.6rem;
    }

    /* Animations */
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeOutZoom {
        to {
            transform: scale(0.8);
            opacity: 0;
        }
    }

    .removing {
        animation: fadeOutZoom 0.2s ease forwards;
    }
</style>

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="page-title">
            <i class="fas fa-photo-video"></i>
            <h3>Manage Images / Videos for Case Study: <span><?php echo htmlspecialchars($caseStudyId); ?></span></h3>
        </div>
        <button class="btn-add-new" data-toggle="modal" data-target="#addImageModal">
            <i class="fas fa-plus-circle"></i>
            Add New File
        </button>
        <!-- Modal -->
        <div class="modal" id="addImageModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: black;">Add New Image / Video</h5>
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
                                <label for="files">Select Files:</label>
                                <input type="file" name="files[]" multiple class="form-control" required
                                    accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime">
                            </div>
                            <button type="submit" class="btn btn-success">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="form-inline mb-4">
            <div class="date-filter">
                <input type="text" id="filterDate" class="form-control mr-2" placeholder="Filter by date">
            </div>
            <button class="btn btn-secondary mr-2" onclick="filterImages()">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button class="btn btn-warning mr-2" onclick="resetFilter()">
                <i class="fas fa-undo"></i> Reset
            </button>
            <button class="btn btn-danger mr-2" id="toggleDelete" onclick="toggleDeleteMode()">
                <i class="fas fa-trash"></i> Delete File(s)
            </button>
        </div>

        <!-- Confirm Dialog -->
        <div id="confirmDialog" class="custom-dialog">
            <div class="dialog-content">
                <h4><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h4>
                <p>Are you sure you want to delete the selected images?</p>
                <div class="dialog-buttons">
                    <button onclick="proceedDelete()" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Delete
                    </button>
                    <button onclick="closeDialog()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Images Display -->
        <div id="imageGallery">
            <?php foreach ($mediaByDate as $date => $mediaItems): ?>
                <div class="img-container" data-date="<?php echo htmlspecialchars($date); ?>">
                    <div class="date-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="far fa-calendar-check"></i>
                                Upload Date: <?php echo htmlspecialchars($date); ?>
                            </span>
                            <div class="select-all-container" style="display: none;">
                                <input type="checkbox" id="selectAll_<?php echo htmlspecialchars($date); ?>"
                                    class="select-all" data-date="<?php echo htmlspecialchars($date); ?>">
                                <label for="selectAll_<?php echo htmlspecialchars($date); ?>">Select All</label>
                            </div>
                        </div>
                    </div>
                    <div class="image-grid">
                        <?php foreach ($mediaItems as $item): ?>
                            <div class="<?php echo $item['type'] === 'video' ? 'video-item' : 'image-item'; ?>">
                                <div class="checkbox-wrapper" style="display: none;">
                                    <input type="checkbox" class="media-select"
                                        id="<?php echo htmlspecialchars(basename($item['path'])); ?>"
                                        data-date="<?php echo htmlspecialchars($date); ?>"
                                        data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                </div>
                                <?php if ($item['type'] === 'video'): ?>
                                    <a href="<?php echo htmlspecialchars($item['path']); ?>" data-fancybox="gallery"
                                        data-type="html5video" data-caption="Upload Date: <?php echo htmlspecialchars($date); ?>">
                                        <div class="video-thumbnail">
                                            <video>
                                                <source src="<?php echo htmlspecialchars($item['path']); ?>" type="video/mp4">
                                            </video>
                                            <i class="fas fa-play-circle video-play-icon"></i>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($item['path']); ?>" data-fancybox="gallery"
                                        data-caption="Upload Date: <?php echo htmlspecialchars($date); ?>">
                                        <img src="<?php echo htmlspecialchars($item['path']); ?>" alt="Media" class="img-fluid">
                                    </a>
                                <?php endif; ?>
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

        // Hàm upload files
        async function uploadFiles(formData) {
            try {
                const response = await fetch('./php_action/upload_files.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(result.message || 'Upload failed', 'error');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showToast('Upload failed: ' + error.message, 'error');
            }
        }

        // Event listener cho form submit
        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData();
            const fileInput = document.querySelector('input[type="file"]');
            const dateInput = document.querySelector('input[name="upload_date"]');
            const caseStudyId = document.querySelector('input[name="case_study_id"]').value;

            // Kiểm tra files
            if (fileInput.files.length === 0) {
                showToast('Please select files to upload', 'warning');
                return;
            }

            // Thêm từng file vào FormData
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('files[]', fileInput.files[i]);
            }

            // Thêm các trường khác
            formData.append('upload_date', dateInput.value);
            formData.append('case_study_id', caseStudyId);
            // Show loading indicator
            showToast('Uploading files...', 'info');

            await uploadFiles(formData);
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

        let isClosedByUser = false;

        // Khởi tạo Fancybox
        Fancybox.bind("[data-fancybox]", {
            on: {
                init: (fancybox) => {
                    isClosedByUser = false;
                },
                closing: (fancybox) => {
                    if (!isClosedByUser) {
                        // Nếu đóng bằng nút back của trình duyệt
                        return;
                    }
                    // Nếu đóng bằng nút close hoặc click outside
                    window.history.back();
                },
                destroy: (fancybox) => {
                    isClosedByUser = false;
                }
            },
            // Tắt tất cả tính năng liên quan đến history của Fancybox
            Hash: false,
            history: false,
            // Thêm handler cho nút close
            closeButton: {
                click: function () {
                    isClosedByUser = true;
                    return true;
                }
            },
            // Handler cho click outside
            click: function () {
                isClosedByUser = true;
                return "close";
            },
            // Các tùy chọn khác
            buttons: ['zoom', 'close'],
            keyboard: true,
            animated: false,
            trapFocus: false,
            placeFocusBack: false
        });

        // Xử lý khi người dùng nhấn nút back của trình duyệt
        window.addEventListener('popstate', function (event) {
            const instance = Fancybox.getInstance();
            if (instance) {
                isClosedByUser = false;
                instance.close();
            }
        });

        // Xử lý click outside
        document.addEventListener('click', function (event) {
            const instance = Fancybox.getInstance();
            if (instance && !event.target.closest('.fancybox__container')) {
                isClosedByUser = true;
            }
        }, true);

        // Xử lý phím ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                isClosedByUser = true;
            }
        });

        // Các phần tử khác
        const deleteButton = document.getElementById('deleteSelected');
        if (deleteButton) {
            deleteButton.addEventListener('click', showConfirmDialog);
        }

        const toggleDeleteButton = document.getElementById('toggleDelete');
        if (toggleDeleteButton) {
            toggleDeleteButton.addEventListener('click', toggleDeleteMode);
        }

        // Các phần tử khác...
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
        const selectedImages = document.querySelectorAll('.media-select:checked');
        let deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        if (selectedImages.length > 0 && deleteMode) {
            if (!deleteSelectedBtn) {
                deleteSelectedBtn = document.createElement('button');
                deleteSelectedBtn.id = 'deleteSelectedBtn';
                deleteSelectedBtn.className = 'btn btn-danger';
                deleteSelectedBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete Selected Files (${selectedImages.length})`;
                deleteSelectedBtn.onclick = showConfirmDialog;
                document.querySelector('.form-inline').appendChild(deleteSelectedBtn);
            } else {
                // Cập nhật số lượng đã chọn trên nút
                deleteSelectedBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Delete Selected Files (${selectedImages.length})`;
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
            document.querySelectorAll('.media-select, .select-all').forEach(checkbox => {
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
            const imageCheckboxes = document.querySelectorAll(`.media-select[data-date="${date}"]`);
            imageCheckboxes.forEach(box => {
                box.checked = this.checked;
            });
            updateDeleteButton();
        });
    });

    // Handle individual checkbox changes
    document.querySelectorAll('.media-select').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeleteButton();

            const date = this.dataset.date;
            const selectAll = document.querySelector(`#selectAll_${date}`);
            const imageCheckboxes = document.querySelectorAll(`.media-select[data-date="${date}"]`);
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
        const selectedImages = Array.from(document.querySelectorAll('.media-select:checked'))
            .map(checkbox => checkbox.dataset.path);

        if (selectedImages.length === 0) {
            alert('No images selected');
            return;
        }

        try {
            const response = await fetch('./php_action/delete_media.php', {
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
        const selectedImages = document.querySelectorAll('.media-select:checked');
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
        try {
            const selectedMedia = Array.from(document.querySelectorAll('.media-select:checked'))
                .map(checkbox => ({
                    path: checkbox.dataset.path,
                    type: checkbox.closest('.video-item') ? 'video' : 'image'
                }));

            const response = await fetch('./php_action/delete_media.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ media: selectedMedia })
            });

            const result = await response.json();

            if (result.success) {
                let imageCount = 0;
                let videoCount = 0;

                selectedMedia.forEach(media => {
                    const element = document.querySelector(`[data-path="${media.path}"]`)
                        .closest(media.type === 'video' ? '.video-item' : '.image-item');
                    if (element) {
                        element.classList.add('removing');
                        setTimeout(() => element.remove(), 200);

                        // Đếm số lượng ảnh và video đã xóa
                        if (media.type === 'video') {
                            videoCount++;
                        } else {
                            imageCount++;
                        }
                    }
                });

                closeDialog();
                toggleDeleteMode();
                showToast(`Successfully deleted ${imageCount} image(s) and ${videoCount} video(s)`, 'success');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast(`Error deleting media: ${error.message}`, 'error');
        }
    }
    // Update the delete button click handler
    document.getElementById('deleteSelected').addEventListener('click', showConfirmDialog);
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

<script type="module">
    import { Fancybox } from "https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.esm.js";

    document.addEventListener('DOMContentLoaded', () => {
        const options = {
            // Cấu hình chung
            infinite: true,
            compact: false,
            idle: false,
            dragToClose: false,
            preload: 3, // Preload 3 slides trước và sau slide hiện tại

            // Cấu hình Images
            Images: {
                zoom: true,
                protected: false, // Cho phép tải xuống ảnh
                preload: true // Preload ảnh
            },

            // Cấu hình Video mới cho Fancybox 5.0
            Video: {
                autoplay: false,
                ratio: 16 / 9,
                controls: true,
                fit: "contain",
                preload: true, // Preload video metadata
                protected: false, // Cho phép tải xuống video
                controlsList: "" // Bỏ nodownload để cho phép tải xuống
            },

            // Cấu hình HTML5 video
            Html: {
                video: {
                    autoplay: false,
                    controls: true,
                    playsinline: true,
                    loop: false,
                    muted: false,
                    preload: "auto", // Preload toàn bộ video
                    controlsList: "", // Bỏ nodownload để cho phép tải xuống
                }
            },

            // Cấu hình Toolbar mới cho 5.0
            Toolbar: {
                absolute: true,
                display: {
                    left: ["infobar"],
                    middle: [],
                    right: ["download", "iterateZoom", "slideshow", "fullscreen", "thumbs", "close"], // Thêm nút download
                },
            },

            // Thêm actions cho download
            on: {
                "done": (fancybox) => {
                    fancybox.carousel.slides.forEach((slide) => {
                        if (slide.type === "video" || slide.type === "html5video") {
                            const downloadBtn = document.createElement("a");
                            downloadBtn.className = "video-download-btn";
                            downloadBtn.href = slide.src;
                            downloadBtn.download = "";
                            downloadBtn.target = "_blank";
                            downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download';

                            // Thêm sự kiện click
                            downloadBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                const link = document.createElement('a');
                                link.href = slide.src;
                                link.download = slide.src.split('/').pop();
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            });

                            slide.el.appendChild(downloadBtn);
                        }
                    });
                }
            },

            // Cấu hình l10n (localization)
            l10n: {
                CLOSE: "Close",
                NEXT: "Next",
                PREV: "Previous",
                MODAL: "You can close this modal content with the ESC key",
                ERROR: "Something Went Wrong, Please Try Again Later",
                IMAGE_ERROR: "Image Not Found",
                ELEMENT_NOT_FOUND: "HTML Element Not Found",
                AJAX_NOT_FOUND: "Error Loading AJAX : Not Found",
                AJAX_FORBIDDEN: "Error Loading AJAX : Forbidden",
                IFRAME_ERROR: "Error Loading Page",
                TOGGLE_SLIDESHOW: "Toggle slideshow",
                TOGGLE_FULLSCREEN: "Toggle fullscreen",
                TOGGLE_THUMBS: "Toggle thumbnails",
                TOGGLE_ZOOM: "Toggle zoom",
                DOWNLOAD: "Download"
            }
        };

        // Khởi tạo Fancybox 5.0
        Fancybox.bind("[data-fancybox]", options);
    });
</script>
<?php include('./constant/layout/footer.php'); ?>
<!-- Thêm Font Awesome cho icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">