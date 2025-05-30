<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $adminId = $_POST['id'];
    
    // Prevent deleting own account
    if ($adminId == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Anda tidak dapat menghapus akun sendiri!";
        header("Location: pengaturan.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Admin berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Admin tidak ditemukan atau tidak dapat dihapus!";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Gagal menghapus admin: " . $e->getMessage();
    }
}

header("Location: pengaturan.php");
exit();
?>