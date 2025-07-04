<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'siswa') {
    header('Location: unauthorized.php');
    exit();
}

// Get student data and class
$stmt = $pdo->prepare("SELECT s.kelas_id, k.nama_kelas 
                       FROM siswa s 
                       JOIN kelas k ON s.kelas_id = k.id
                       WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$siswa = $stmt->fetch();

// Get class schedule for this student
$stmt = $pdo->prepare("SELECT j.*, mp.nama_pelajaran, u.nama_lengkap AS nama_guru 
                       FROM jadwal j
                       JOIN mata_pelajaran mp ON j.mata_pelajaran_id = mp.id
                       JOIN guru g ON j.guru_id = g.id
                       JOIN users u ON g.user_id = u.id
                       WHERE j.kelas_id = ?
                       ORDER BY j.hari, j.waktu_mulai");
$stmt->execute([$siswa['kelas_id']]);
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Sistem Jadwal Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: white;
            background-color: #6f42c1;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: #6f42c1;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f1f1f1;
            font-weight: 600;
        }
        .badge-day {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .time-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .welcome-header {
            background: linear-gradient(135deg, #6f42c1, #9b59b6);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .day-column {
            width: 15%;
        }
        .time-column {
            width: 15%;
        }
        .subject-column {
            width: 35%;
        }
        .teacher-column {
            width: 35%;
        }
        .student-info {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-dark">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>Sistem Jadwal</h4>
                        <hr class="bg-light">
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="bi bi-calendar-week me-2"></i>Jadwal Pelajaran
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Siswa</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted"><?= date('l, d F Y') ?></span>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h3><i class="bi bi-person-circle me-2"></i>Selamat datang, <?= $userData['nama_lengkap'] ?></h3>
                            <p class="mb-0">Anda login sebagai Siswa. Terakhir login: <?= date('d/m/Y H:i') ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="bi bi-people" style="font-size: 3rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="student-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="bi bi-info-circle me-2"></i>Informasi Siswa</h5>
                            <p class="mb-1"><strong>Nama Lengkap:</strong> <?= $userData['nama_lengkap'] ?></p>
                            <p class="mb-1"><strong>Kelas:</strong> <?= $siswa['nama_kelas'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="bi bi-calendar-event me-2"></i>Hari Ini</h5>
                            <p class="mb-1"><strong>Tanggal:</strong> <?= date('d F Y') ?></p>
                            <p class="mb-1"><strong>Hari:</strong> <?= date('l') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Class Schedule -->
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-table me-2"></i>Jadwal Pelajaran Kelas <?= $siswa['nama_kelas'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="day-column">Hari</th>
                                        <th class="time-column">Waktu</th>
                                        <th class="subject-column">Mata Pelajaran</th>
                                        <th class="teacher-column">Guru Pengajar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jadwal as $j): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-purple badge-day"><?= $j['hari'] ?></span>
                                        </td>
                                        <td class="time-display">
                                            <?= substr($j['waktu_mulai'], 0, 5) ?> - <?= substr($j['waktu_selesai'], 0, 5) ?>
                                        </td>
                                        <td><?= $j['nama_pelajaran'] ?></td>
                                        <td><?= $j['nama_guru'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule (Bonus Feature) -->
                <?php
                $today = date('l');
                $todayClasses = array_filter($jadwal, function($item) use ($today) {
                    return $item['hari'] === $today;
                });
                
                if (!empty($todayClasses)): ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <span><i class="bi bi-calendar-day me-2"></i>Jadwal Hari Ini (<?= $today ?>)</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($todayClasses as $class): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= $class['nama_pelajaran'] ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <i class="bi bi-person-badge me-1"></i><?= $class['nama_guru'] ?>
                                        </h6>
                                        <p class="card-text">
                                            <span class="time-display"><?= substr($class['waktu_mulai'], 0, 5) ?> - <?= substr($class['waktu_selesai'], 0, 5) ?></span>
                                        </p>
                                        <a href="#" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-journal-text me-1"></i>Materi
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-journal-check me-1"></i>Tugas
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>