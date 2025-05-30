<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$id = $_GET['id'];

// Hapus jadwal
$stmt = $pdo->prepare("DELETE FROM jadwal WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = "Jadwal berhasil dihapus";
header('Location: admin_dashboard.php');
exit();
?>