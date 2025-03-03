<?php
session_start();

if (!isset($_SESSION['userId'])) {
    // Lưu URL hiện tại vào session
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Chuyển hướng đến trang đăng nhập
    header('Location: login.php');
    exit();
}
?>