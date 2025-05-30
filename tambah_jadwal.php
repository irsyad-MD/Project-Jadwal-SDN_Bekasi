<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hari = $_POST['hari'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $kelas_id = $_POST['kelas_id'];
    $mata_pelajaran_id = $_POST['mata_pelajaran_id'];
    $guru_id = $_POST['guru_id'];
    
    // Validasi waktu
    if ($waktu_mulai >= $waktu_selesai) {
        $_SESSION['error'] = "Waktu selesai harus setelah waktu mulai";
        header('Location: admin_dashboard.php');
        exit();
    }
    
    // Cek konflik jadwal
    $stmt = $pdo->prepare("SELECT * FROM jadwal 
                          WHERE hari = ? 
                          AND kelas_id = ?
                          AND ((waktu_mulai <= ? AND waktu_selesai > ?) 
                          OR (waktu_mulai < ? AND waktu_selesai >= ?)
                          OR (waktu_mulai >= ? AND waktu_selesai <= ?))");
    $stmt->execute([$hari, $kelas_id, $waktu_mulai, $waktu_mulai, $waktu_selesai, $waktu_selesai, $waktu_mulai, $waktu_selesai]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Konflik jadwal dengan pelajaran lain";
        header('Location: admin_dashboard.php');
        exit();
    }
    
    // Tambah jadwal
    $stmt = $pdo->prepare("INSERT INTO jadwal (hari, waktu_mulai, waktu_selesai, kelas_id, mata_pelajaran_id, guru_id) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$hari, $waktu_mulai, $waktu_selesai, $kelas_id, $mata_pelajaran_id, $guru_id]);
    
    $_SESSION['success'] = "Jadwal berhasil ditambahkan";
    header('Location: admin_dashboard.php');
    exit();
}
?>