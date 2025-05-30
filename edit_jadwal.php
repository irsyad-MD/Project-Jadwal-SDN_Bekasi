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

// Ambil data jadwal yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM jadwal WHERE id = ?");
$stmt->execute([$id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    header('Location: admin_dashboard.php');
    exit();
}

// Ambil data untuk dropdown
$kelas = $pdo->query("SELECT * FROM kelas")->fetchAll();
$mapel = $pdo->query("SELECT * FROM mata_pelajaran")->fetchAll();
$guru = $pdo->query("SELECT g.id, u.nama_lengkap FROM guru g JOIN users u ON g.user_id = u.id")->fetchAll();

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
        header("Location: edit_jadwal.php?id=$id");
        exit();
    }
    
    // Cek konflik jadwal (kecuali dengan dirinya sendiri)
    $stmt = $pdo->prepare("SELECT * FROM jadwal 
                          WHERE hari = ? 
                          AND kelas_id = ?
                          AND id != ?
                          AND ((waktu_mulai <= ? AND waktu_selesai > ?) 
                          OR (waktu_mulai < ? AND waktu_selesai >= ?)
                          OR (waktu_mulai >= ? AND waktu_selesai <= ?))");
    $stmt->execute([$hari, $kelas_id, $id, $waktu_mulai, $waktu_mulai, $waktu_selesai, $waktu_selesai, $waktu_mulai, $waktu_selesai]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Konflik jadwal dengan pelajaran lain";
        header("Location: edit_jadwal.php?id=$id");
        exit();
    }
    
    // Update jadwal
    $stmt = $pdo->prepare("UPDATE jadwal 
                          SET hari = ?, waktu_mulai = ?, waktu_selesai = ?, 
                          kelas_id = ?, mata_pelajaran_id = ?, guru_id = ?
                          WHERE id = ?");
    $stmt->execute([$hari, $waktu_mulai, $waktu_selesai, $kelas_id, $mata_pelajaran_id, $guru_id, $id]);
    
    $_SESSION['success'] = "Jadwal berhasil diperbarui";
    header('Location: admin_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Jadwal</title>
</head>
<body>
    <h1>Edit Jadwal</h1>
    <a href="admin_dashboard.php">Kembali</a>
    
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?= $_SESSION['error'] ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="post">
        <label>Hari:</label>
        <select name="hari" required>
            <option value="Senin" <?= $jadwal['hari'] === 'Senin' ? 'selected' : '' ?>>Senin</option>
            <option value="Selasa" <?= $jadwal['hari'] === 'Selasa' ? 'selected' : '' ?>>Selasa</option>
            <option value="Rabu" <?= $jadwal['hari'] === 'Rabu' ? 'selected' : '' ?>>Rabu</option>
            <option value="Kamis" <?= $jadwal['hari'] === 'Kamis' ? 'selected' : '' ?>>Kamis</option>
            <option value="Jumat" <?= $jadwal['hari'] === 'Jumat' ? 'selected' : '' ?>>Jumat</option>
            <option value="Sabtu" <?= $jadwal['hari'] === 'Sabtu' ? 'selected' : '' ?>>Sabtu</option>
        </select><br>
        
        <label>Waktu Mulai:</label>
        <input type="time" name="waktu_mulai" value="<?= substr($jadwal['waktu_mulai'], 0, 5) ?>" required><br>
        
        <label>Waktu Selesai:</label>
        <input type="time" name="waktu_selesai" value="<?= substr($jadwal['waktu_selesai'], 0, 5) ?>" required><br>
        
        <label>Kelas:</label>
        <select name="kelas_id" required>
            <?php foreach ($kelas as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $k['id'] == $jadwal['kelas_id'] ? 'selected' : '' ?>>
                    <?= $k['nama_kelas'] ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <label>Mata Pelajaran:</label>
        <select name="mata_pelajaran_id" required>
            <?php foreach ($mapel as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $m['id'] == $jadwal['mata_pelajaran_id'] ? 'selected' : '' ?>>
                    <?= $m['nama_pelajaran'] ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <label>Guru:</label>
        <select name="guru_id" required>
            <?php foreach ($guru as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $g['id'] == $jadwal['guru_id'] ? 'selected' : '' ?>>
                    <?= $g['nama_lengkap'] ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <button type="submit">Simpan Perubahan</button>
    </form>
</body>
</html>