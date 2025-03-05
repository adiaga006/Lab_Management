<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

$caseStudyId = $_GET['case_study_id'] ?? null;

// Di chuyển phần đọc notes ra ngoài vòng lặp foreach, đặt ở đầu file sau phần khai báo biến
$notesFile = dirname(__FILE__) . "/uploads/case_studies/{$caseStudyId}/day_notes.json";
$notes = [];
if (file_exists($notesFile)) {
    $notes = json_decode(file_get_contents($notesFile), true) ?: [];
}

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
        margin-top: 10px;
        /* Giảm khoảng cách giữa nút trên và nút dưới */

    }

    .page-title h3 {
        color: #222;
        font-weight: 700;
        margin-top: 5px;
        /* Giảm khoảng cách giữa nút trên và nút dưới */

    }

    .page-title span {
        color: #2ecc71;
        font-weight: 700;
        margin-top: 5px;
        /* Giảm khoảng cách giữa nút trên và nút dưới */

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
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #2ecc71;
        margin-bottom: 20px;
    }

    .upload-date {
        font-size: 1.1rem;
        color: #2c3e50;
        font-weight: 500;
    }

    /* Style cho note card */
    .note-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid rgba(46, 204, 113, 0.2);
        box-shadow: 0 2px 8px rgba(46, 204, 113, 0.1);
        padding: 15px;
        margin-top: 10px;
    }

    .note-content {
        display: flex;
        align-items: flex-start;
    }

    .note-content i {
        color: #2ecc71;
        font-size: 1.1rem;
        margin-top: 3px;
    }

    .note-content p {
        color: #555;
        font-size: 0.95rem;
        line-height: 1.5;
        margin-left: 10px;
        flex: 1;
    }

    /* Style cho buttons */
    .btn-link {
        color: #2ecc71;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 20px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        background-color: rgba(46, 204, 113, 0.1);
    }

    .btn-link:hover {
        background-color: rgba(46, 204, 113, 0.2);
        color: #27ae60;
        transform: translateY(-1px);
    }

    .btn-link i {
        font-size: 0.85rem;
    }

    /* Style cho modal */
    .modal-content {
        border-radius: 12px;
        border: none;
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        border-radius: 12px 12px 0 0;
    }

    .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #eee;
        border-radius: 0 0 12px 12px;
    }

    .form-control {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 10px;
    }

    .form-control:focus {
        border-color: #2ecc71;
        box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
    }

    /* Cải thiện khoảng cách icon trong date header */
    .date-header .d-flex.align-items-center i {
        margin-right: 10px;
        color: #2ecc71;
    }

    /* Modern Centered Toast Notifications */
    #toast {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) translateY(50px);
        z-index: 9999;
        min-width: 400px;
        padding: 25px;
        border-radius: 20px;
        background: white;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    #toast.show {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) translateY(0);
    }

    /* Toast Icon */
    #toast #toastIcon {
        font-size: 48px;
        margin-bottom: 10px;
    }

    /* Success Toast */
    #toast.toast-success {
        border: 2px solid rgba(46, 204, 113, 0.2);
        background: linear-gradient(145deg, #ffffff, #f8fff8);
    }

    #toast.toast-success #toastIcon {
        color: #2ecc71;
        animation: toastIconPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Error Toast */
    #toast.toast-error {
        border: 2px solid rgba(231, 76, 60, 0.2);
        background: linear-gradient(145deg, #ffffff, #fff8f8);
    }

    #toast.toast-error #toastIcon {
        color: #e74c3c;
    }

    /* Warning Toast */
    #toast.toast-warning {
        border: 2px solid rgba(241, 196, 15, 0.2);
        background: linear-gradient(145deg, #ffffff, #fffdf8);
    }

    #toast.toast-warning #toastIcon {
        color: #f1c40f;
    }

    /* Toast Content */
    #toast .toast-content {
        text-align: center;
    }

    #toast .toast-content span {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 500;
        display: block;
    }

    /* Toast Close Button */
    #toast .toast-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        color: #95a5a6;
        font-size: 18px;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    #toast .toast-close:hover {
        background: rgba(0, 0, 0, 0.05);
        color: #2c3e50;
        transform: rotate(90deg);
    }

    /* Toast Animations */
    @keyframes toastIconPop {
        0% {
            transform: scale(0.5);
            opacity: 0;
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Toast Progress Bar */
    #toast::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 0 0 20px 20px;
    }

    #toast.show::after {
        animation: toastProgress 3s linear forwards;
    }

    @keyframes toastProgress {
        0% {
            width: 100%;
        }
        100% {
            width: 0%;
        }
    }

    /* Overlay background */
    .toast-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .toast-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    /* SweetAlert2 custom styles */
    .swal2-popup {
        padding: 2rem;
        border-radius: 15px !important;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;
    }

    .swal2-icon {
        border: none !important;
        margin: 1.5rem auto !important;
        width: 5em !important;
        height: 5em !important;
    }

    .swal2-title {
        font-size: 1.2rem !important;
        font-weight: 500 !important;
        color: #2c3e50 !important;
        margin: 1rem 0 !important;
    }

    /* Success popup */
    .swal2-success-popup {
        border: 2px solid rgba(46, 204, 113, 0.2) !important;
    }

    .swal2-success-icon {
        color: #2ecc71 !important;
    }

    /* Error popup */
    .swal2-error-popup {
        border: 2px solid rgba(220, 53, 69, 0.2) !important;
    }

    /* Warning popup */
    .swal2-warning-popup {
        border: 2px solid rgba(255, 193, 7, 0.2) !important;
    }

    /* Animation */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translate3d(0, -20%, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    .animated {
        animation-duration: 0.3s;
        animation-fill-mode: both;
    }

    .fadeInDown {
        animation-name: fadeInDown;
    }

    /* Progress bar */
    .swal2-timer-progress-bar {
        background: rgba(46, 204, 113, 0.2) !important;
        height: 0.25rem !important;
        border-radius: 0 0 15px 15px !important;
    }

    .swal2-error-popup .swal2-timer-progress-bar {
        background: rgba(220, 53, 69, 0.2) !important;
    }

    .swal2-warning-popup .swal2-timer-progress-bar {
        background: rgba(255, 193, 7, 0.2) !important;
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
                            <div class="form-group mb-3">
                                <label for="upload_date">Upload Date:</label>
                                <input type="text" name="upload_date" id="uploadDatePicker" class="form-control"
                                    required>
                                <input type="hidden" name="case_study_id"
                                    value="<?php echo htmlspecialchars($caseStudyId); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label for="day_note">Day Note (optional):</label>
                                <textarea name="day_note" id="dayNote" class="form-control"
                                    placeholder="Add a note for this day's uploads"
                                    rows="3"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label for="files">Select Files:</label>
                                <input type="file" name="files[]" multiple class="form-control" required
                                    accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Upload</button>
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

        <!-- Thêm Modal cho Note -->
        <div class="modal fade" id="noteModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Day Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="modalDayNote" class="form-label">Note for <span id="noteDate"></span></label>
                            <textarea id="modalDayNote" class="form-control" rows="4"
                                placeholder="Edit a note for this day's uploads"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveNote()">Save Note</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Images Display -->
        <div id="imageGallery">
            <?php foreach ($mediaByDate as $date => $mediaItems): ?>
                <div class="img-container" data-date="<?php echo htmlspecialchars($date); ?>">
                    <div class="date-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="far fa-calendar-check me-3"></i>
                                <span class="upload-date">Upload Date: <?php echo htmlspecialchars($date); ?></span>
                                <button class="btn btn-link btn-sm ms-3" 
                                        onclick='showNoteModal("<?php echo htmlspecialchars($date); ?>", <?php echo isset($notes[$date]) ? json_encode($notes[$date]) : "\"\""; ?>)'>
                                    <i class="fas fa-edit me-1"></i> Edit Note
                                </button>
                            </div>
                            <div class="select-all-container" style="display: none;">
                                <input type="checkbox" id="selectAll_<?php echo htmlspecialchars($date); ?>"
                                    class="select-all" data-date="<?php echo htmlspecialchars($date); ?>">
                                <label for="selectAll_<?php echo htmlspecialchars($date); ?>">Select All</label>
                            </div>
                        </div>
                        <?php if (isset($notes[$date]) && !empty($notes[$date])): ?>
                        <div class="day-note mt-3">
                            <div class="note-card">
                                <div class="note-content">
                                    <i class="fas fa-sticky-note me-2"></i>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($notes[$date])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
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

<!-- Thêm overlay cho toast -->
<div class="toast-overlay"></div>

<!-- Cập nhật HTML cho toast message -->
<div id="toast" class="toast">
    <i id="toastIcon" class="fas"></i>
    <div class="toast-content">
        <span id="toastMessage"></span>
    </div>
    <button class="toast-close" onclick="hideToast()">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Scripts -->

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
    // Khai báo biến global ở đầu script
    var currentEditDate = '';

    // Sau đó mới định nghĩa các hàm
    function showNoteModal(date, currentNote) {
        currentEditDate = date;
        document.getElementById('noteDate').textContent = date;
        document.getElementById('modalDayNote').value = currentNote || '';
        
        // Sử dụng Bootstrap 5
        const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
        noteModal.show();
    }

    function saveNote() {
        const newNote = document.getElementById('modalDayNote').value;
        
        fetch('php_action/update_day_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                case_study_id: '<?php echo htmlspecialchars($caseStudyId); ?>',
                date: currentEditDate,
                note: newNote
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Đóng modal
                const noteModal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
                noteModal.hide();
                
                // Cập nhật UI trực tiếp
                updateNoteDisplay(currentEditDate, newNote);
                
                // Hiển thị thông báo thành công
                showToast('Note updated successfully', 'success');
            } else {
                showToast(data.message || 'Error updating note', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error updating note', 'error');
        });
    }

    function updateNoteDisplay(date, noteContent) {
        // Tìm container của ngày tương ứng
        const dateContainer = document.querySelector(`.img-container[data-date="${date}"]`);
        if (!dateContainer) return;
        
        const dateHeader = dateContainer.querySelector('.date-header');
        let dayNoteDiv = dateHeader.querySelector('.day-note');
        
        if (noteContent && noteContent.trim() !== '') {
            // Nếu chưa có div day-note, tạo mới
            if (!dayNoteDiv) {
                dayNoteDiv = document.createElement('div');
                dayNoteDiv.className = 'day-note mt-3';
                dateHeader.appendChild(dayNoteDiv);
            }
            
            // Cập nhật nội dung
            dayNoteDiv.innerHTML = `
                <div class="note-card">
                    <div class="note-content">
                        <i class="fas fa-sticky-note me-2"></i>
                        <p class="mb-0">${noteContent.replace(/\n/g, '<br>').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
                    </div>
                </div>
            `;
            
            // Cập nhật nút edit note với note mới
            const editButton = dateHeader.querySelector('.btn-link');
            if (editButton) {
                editButton.onclick = function() {
                    showNoteModal(date, noteContent);
                };
            }
        } else {
            // Nếu note trống, xóa div day-note nếu tồn tại
            if (dayNoteDiv) {
                dayNoteDiv.remove();
            }
            
            // Cập nhật nút edit note với note trống
            const editButton = dateHeader.querySelector('.btn-link');
            if (editButton) {
                editButton.onclick = function() {
                    showNoteModal(date, '');
                };
            }
        }
    }

    // Event listener cho form upload
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                // Upload files và note
                const response = await fetch('./php_action/upload_files.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Nếu upload thành công, cập nhật note
                    const uploadDate = formData.get('upload_date');
                    const dayNote = formData.get('day_note');

                    if (dayNote) {
                        await fetch('php_action/update_day_note.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                case_study_id: '<?php echo htmlspecialchars($caseStudyId); ?>',
                                date: uploadDate,
                                note: dayNote
                            })
                        });
                    }

                    showToast('Files uploaded successfully', 'success');
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
        });
    });

    function showToast(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: false,
            position: 'center',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            showCloseButton: false,
            customClass: {
                popup: 'animated fadeInDown',
                closeButton: 'btn btn-light-danger',
            }
        });

        switch(type) {
            case 'success':
                Toast.fire({
                    icon: 'success',
                    title: message,
                    background: '#fff',
                    iconColor: '#2ecc71',
                    customClass: {
                        popup: 'swal2-success-popup',
                        icon: 'swal2-success-icon'
                    }
                });
                break;
            case 'error':
                Toast.fire({
                    icon: 'error',
                    title: message,
                    background: '#fff',
                    iconColor: '#dc3545',
                    customClass: {
                        popup: 'swal2-error-popup'
                    }
                });
                break;
            case 'warning':
                Toast.fire({
                    icon: 'warning',
                    title: message,
                    background: '#fff',
                    iconColor: '#ffc107',
                    customClass: {
                        popup: 'swal2-warning-popup'
                    }
                });
                break;
        }
    }

    function hideToast() {
        const toast = document.getElementById('toast');
        const overlay = document.querySelector('.toast-overlay');
        
        toast.classList.remove('show');
        overlay.classList.remove('show');
        
        // Clean up after animation
        toast.addEventListener('transitionend', function handler() {
            toast.removeEventListener('transitionend', handler);
            toast.className = 'toast';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
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
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
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
        window.showConfirmDialog = function() {
            confirmBackdrop.style.display = 'block';
            confirmDialog.style.display = 'block';
        };

        // Update closeConfirmDialog function
        window.closeConfirmDialog = function() {
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
                click: function() {
                    isClosedByUser = true;
                    return true;
                }
            },
            // Handler cho click outside
            click: function() {
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
        window.addEventListener('popstate', function(event) {
            const instance = Fancybox.getInstance();
            if (instance) {
                isClosedByUser = false;
                instance.close();
            }
        });

        // Xử lý click outside
        document.addEventListener('click', function(event) {
            const instance = Fancybox.getInstance();
            if (instance && !event.target.closest('.fancybox__container')) {
                isClosedByUser = true;
            }
        }, true);

        // Xử lý phím ESC
        document.addEventListener('keydown', function(event) {
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
        checkbox.addEventListener('change', function() {
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
        checkbox.addEventListener('change', function() {
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
                body: JSON.stringify({
                    images: selectedImages
                })
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
                body: JSON.stringify({
                    media: selectedMedia
                })
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
    import {
        Fancybox
    } from "https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.esm.js";

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
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>