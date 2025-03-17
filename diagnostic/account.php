<?php
include('./constant/layout/head.php');
include('./constant/layout/header.php');
include('./constant/layout/sidebar.php');
include('./constant/connect.php');

// Kiểm tra session và lấy thông tin user
if (!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

$userId = $_SESSION['userId'];
$sql = "SELECT * FROM user_infor WHERE user_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userInfo = $result->fetch_assoc();

// Xác định role text
$roleText = '';
switch ($userInfo['role']) {
    case 1:
        $roleText = 'Administrator';
        break;
    case 2:
        $roleText = 'Employee';
        break;
    case 3:
        $roleText = 'Guest';
        break;
}
?>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="fa fa-user-circle"></i> My Account
                        </h4>
                        <div class="row">
                            <!-- Avatar Section -->
                            <div class="col-md-4 text-center">
                                <div class="avatar-wrapper">
                                    <?php
                                    $userId = $_SESSION['userId'];
                                    $avatarPath = null;
                                    $avatarDir = './assets/uploads/avatars/';
                                    $avatarPattern = $avatarDir . 'user_' . $userId . '.*';
                                    $avatarFiles = glob($avatarPattern);
                                    
                                    if (!empty($avatarFiles)) {
                                        $avatarPath = $avatarFiles[0];
                                    }
                                    ?>
                                    <img src="<?php echo $avatarPath ? $avatarPath : './assets/images/default-avatar.png'; ?>" 
                                         class="profile-avatar" 
                                         alt="Profile Avatar"
                                         style="cursor: pointer;"
                                         onclick="showFullImage(this.src)">
                                    <div class="avatar-edit">
                                        <input type="file" id="avatarUpload" accept="image/*" style="display: none"/>
                                        <label for="avatarUpload" class="btn btn-primary mt-3">
                                            <i class="fa fa-camera"></i> Change Avatar
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h5><?php echo htmlspecialchars($userInfo['username']); ?></h5>
                                    <span class="badge role-badge role-<?php echo $userInfo['role']; ?>">
                                        <?php 
                                        switch($userInfo['role']) {
                                            case 1:
                                                echo '<span class="badge badge-danger">
                                                        <i class="fas fa-crown"></i> Administrator
                                                      </span>';
                                                break;
                                            case 2:
                                                echo '<span class="badge badge-success">
                                                        <i class="fas fa-user-tie"></i> Employee
                                                      </span>';
                                                break;
                                            case 3:
                                                echo '<span class="badge badge-info">
                                                        <i class="fas fa-user"></i> Guest
                                                      </span>';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Account Details Section -->
                            <div class="col-md-8">
                                <form id="accountUpdateForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-user"></i> Username</label>
                                                <input type="text" class="form-control" name="username" 
                                                       value="<?php echo htmlspecialchars($userInfo['username']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-envelope"></i> Email</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($userInfo['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-id-card"></i> Full Name</label>
                                                <input type="text" class="form-control" name="fullname" 
                                                       value="<?php echo htmlspecialchars($userInfo['fullname'] ?? ''); ?>"
                                                       <?php echo ($userInfo['role'] != 3) ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-birthday-cake"></i> Age</label>
                                                <input type="number" class="form-control" name="age" 
                                                       value="<?php echo htmlspecialchars($userInfo['age'] ?? ''); ?>"
                                                       <?php echo ($userInfo['role'] != 3) ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fa fa-home"></i> Address</label>
                                        <input type="text" class="form-control" name="address" 
                                               value="<?php echo htmlspecialchars($userInfo['address'] ?? ''); ?>"
                                               <?php echo ($userInfo['role'] != 3) ? 'required' : ''; ?>>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-briefcase"></i> Position</label>
                                                <input type="text" class="form-control" name="position" 
                                                       value="<?php echo htmlspecialchars($userInfo['position'] ?? ''); ?>"
                                                       <?php echo ($userInfo['role'] != 3) ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-building"></i> Work Address</label>
                                                <input type="text" class="form-control" name="work_address" 
                                                       value="<?php echo htmlspecialchars($userInfo['work_address'] ?? ''); ?>"
                                                       <?php echo ($userInfo['role'] != 3) ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-key"></i> New Password</label>
                                                <input type="password" class="form-control" name="new_password" 
                                                       placeholder="Leave blank to keep current password">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fa fa-lock"></i> Confirm Password</label>
                                                <input type="password" class="form-control" name="confirm_password" 
                                                       placeholder="Confirm new password">
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-save"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for full image -->
<div id="imageModal" class="modal-overlay" onclick="closeModal()">
    <div class="modal-content">
        <img id="fullImage" src="" alt="Full size image">
        <span class="close-btn" onclick="closeModal()">&times;</span>
    </div>
</div>

<style>
.profile-avatar {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #f8f9fa;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.02);
    filter: brightness(0.9);
}

.avatar-wrapper {
    position: relative;
    margin: 20px auto;
}

.card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.08);
    border-radius: 15px;
}

.form-control {
    border-radius: 8px;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.role-badge {
    padding: 8px 16px;
    font-size: 0.9em;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.role-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.role-1 { /* Admin */
    background-color: #dc3545;
    color: white;
}

.role-1 i { /* Admin icon */
    margin-right: 5px;
    animation: pulse 2s infinite;
}

.role-2 { /* Employee */
    background-color: #28a745;
    color: white;
}

.role-2 i { /* Employee icon */
    margin-right: 5px;
}

.role-3 { /* Guest */
    background-color: #17a2b8;
    color: white;
}

.role-3 i { /* Guest icon */
    margin-right: 5px;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.form-group label {
    font-weight: 500;
    color: #495057;
}

.form-group label i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
    color: #6c757d;
    transition: all 0.3s ease;
}

.form-group:hover label i {
    color: #007bff;
    transform: scale(1.1);
}

.btn-success {
    border-radius: 8px;
    padding: 12px 30px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.badge {
    padding: 8px 12px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    border-radius: 15px;
    margin-top: 5px;
    transition: all 0.3s ease;
}

.badge i {
    margin-right: 8px;
}

/* Administrator Badge */
.badge.badge-danger {
    background: linear-gradient(45deg, #dc3545, #c82333);
    box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
}

/* Employee Badge */
.badge.badge-success {
    background: linear-gradient(45deg, #28a745, #218838);
    box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
}

/* Guest Badge */
.badge.badge-info {
    background: linear-gradient(45deg, #17a2b8, #138496);
    box-shadow: 0 2px 5px rgba(23, 162, 184, 0.3);
}

/* Hover effects */
.badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.badge:hover i {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

/* Modal styles */
.modal-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

.modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90vh;
    margin: auto;
    display: flex;
    justify-content: center;
    align-items: center;
}

#fullImage {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
}

.close-btn {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.close-btn:hover {
    color: #ddd;
    transform: scale(1.1);
}

.profile-avatar {
    transition: transform 0.3s ease, filter 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.02);
    filter: brightness(0.9);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Account update form handling
    document.getElementById('accountUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('php_action/update_account.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'An error occurred'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.'
            });
        });
    });

    // Avatar upload handling
    document.getElementById('avatarUpload').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('avatar', file);
            
            // Hiển thị loading
            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('php_action/update_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Cập nhật ảnh ngay lập tức
                    const avatarImg = document.querySelector('.profile-avatar');
                    const timestamp = new Date().getTime();
                    const newSrc = data.avatar_path + '?t=' + timestamp;
                    avatarImg.src = newSrc;
                    
                    // Dispatch event để header.php cập nhật avatar
                    const event = new CustomEvent('avatarUpdated', {
                        detail: { avatarPath: data.avatar_path }
                    });
                    document.dispatchEvent(event);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update avatar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred while uploading the avatar.'
                });
            });
        }
    });
});

// Image modal functions
function showFullImage(src) {
    const modal = document.getElementById('imageModal');
    const fullImage = document.getElementById('fullImage');
    
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    fullImage.src = src + '?t=' + timestamp;
    
    modal.style.display = 'flex';
    
    // Prevent scrolling of the background
    document.body.style.overflow = 'hidden';
    
    // Stop event propagation
    event.stopPropagation();
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    
    // Re-enable scrolling
    document.body.style.overflow = 'auto';
}

// Close modal when pressing ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Prevent modal from closing when clicking on the image
document.getElementById('fullImage').addEventListener('click', function(event) {
    event.stopPropagation();
});
</script>

<?php include('./constant/layout/footer.php'); ?> 