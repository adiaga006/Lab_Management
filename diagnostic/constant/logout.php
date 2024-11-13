<?php  
session_start(); // Bắt đầu session

// Xóa session PHP
session_destroy(); // Hủy session hoàn toàn

?>
<script>
// Xóa tất cả dữ liệu trong sessionStorage khi đăng xuất
sessionStorage.clear();
window.location = "../login.php"; // Điều hướng về trang login
</script>
<?php
exit;
?>
