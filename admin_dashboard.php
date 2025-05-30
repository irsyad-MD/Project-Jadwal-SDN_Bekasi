<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

// Get schedule data
$stmt = $pdo->query("SELECT j.*, k.nama_kelas, mp.nama_pelajaran, u.nama_lengkap AS nama_guru 
                     FROM jadwal j
                     JOIN kelas k ON j.kelas_id = k.id
                     JOIN mata_pelajaran mp ON j.mata_pelajaran_id = mp.id
                     JOIN guru g ON j.guru_id = g.id
                     JOIN users u ON g.user_id = u.id
                     ORDER BY j.hari, j.waktu_mulai");
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get data for form
$kelas = $pdo->query("SELECT * FROM kelas")->fetchAll();
$mapel = $pdo->query("SELECT * FROM mata_pelajaran")->fetchAll();
$guru = $pdo->query("SELECT g.id, u.nama_lengkap FROM guru g JOIN users u ON g.user_id = u.id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Jadwal Sekolah</title>
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
            background-color: #007bff;
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
            background-color: #007bff;
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
        .btn-action {
            padding: 5px 10px;
            margin: 0 3px;
            font-size: 0.85rem;
        }
        .welcome-header {
            background: linear-gradient(135deg, #007bff, #00b4ff);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .time-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 767.98px) {
            .sidebar {
                min-height: auto;
                position: fixed;
                bottom: 0;
                width: 100%;
                z-index: 1000;
                height: 60px;
                overflow-y: hidden;
            }
            .sidebar .position-sticky {
                position: relative !important;
            }
            .sidebar .nav {
                flex-direction: row !important;
                overflow-x: auto;
                white-space: nowrap;
                padding: 0 10px;
            }
            .sidebar .nav-item {
                display: inline-block;
                margin-right: 5px;
            }
            .sidebar .nav-link {
                padding: 10px 15px;
                margin-bottom: 0;
                border-radius: 5px;
            }
            .sidebar .nav-link i {
                margin-right: 0 !important;
                display: block;
                text-align: center;
                font-size: 1.2rem;
            }
            .sidebar .nav-link span {
                display: none;
            }
            .sidebar .text-center {
                display: none;
            }
            
            .main-content {
                padding-bottom: 80px; /* Space for mobile navbar */
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table td, .table th {
                white-space: nowrap;
            }
            
            .btn-action {
                margin: 2px;
                padding: 3px 6px;
                font-size: 0.75rem;
            }
            
            .welcome-header {
                padding: 15px;
            }
            
            .welcome-header h3 {
                font-size: 1.2rem;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
            }
            
            .btn-toolbar {
                margin-top: 10px;
            }
        }
        
        /* Tablet styles */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            .sidebar .nav-link span {
                display: none;
            }
            .sidebar .nav-link i {
                margin-right: 0 !important;
                display: block;
                text-align: center;
                font-size: 1.2rem;
            }
            .sidebar .text-center h4 {
                display: none;
            }
            .sidebar .text-center hr {
                display: none;
            }
        }
    </style>
</head>
<body>
        <!-- Tombol Toggle Sidebar untuk Mobile -->
        <button class="btn btn-dark d-md-none mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list"></i>
        </button>

        <!-- Sidebar (Offcanvas untuk HP & tetap di kiri untuk desktop) -->
<div class="offcanvas offcanvas-start d-md-none bg-dark text-white" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileSidebarLabel">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Isi menu seperti biasa -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="bi bi-calendar-week me-2"></i><span>Jadwal Pelajaran</span>
                </a>
            </li>
             <li class="nav-item">
                            <a class="nav-link" href="manajemen_guru.php">
                                <i class="bi bi-people me-2"></i><span>Manajemen Guru</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_mapel.php">
                                <i class="bi bi-journal-bookmark me-2"></i><span>Mata Pelajaran</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_kelas.php">
                                <i class="bi bi-building me-2"></i><span>Kelas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_siswa.php">
                                <i class="bi bi-people-fill me-2"></i><span>Siswa</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaturan.php">
                                <i class="bi bi-gear me-2"></i><span>Pengaturan</span>
                            </a>
                        </li>
                        <li class="nav-item mt-md-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i><span>Logout</span>
                            </a>
                        </li>
        </ul>
    </div>
</div>


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
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="bi bi-calendar-week me-2"></i><span>Jadwal Pelajaran</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_guru.php">
                                <i class="bi bi-people me-2"></i><span>Manajemen Guru</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_mapel.php">
                                <i class="bi bi-journal-bookmark me-2"></i><span>Mata Pelajaran</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_kelas.php">
                                <i class="bi bi-building me-2"></i><span>Kelas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_siswa.php">
                                <i class="bi bi-people-fill me-2"></i><span>Siswa</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaturan.php">
                                <i class="bi bi-gear me-2"></i><span>Pengaturan</span>
                            </a>
                        </li>
                        <li class="nav-item mt-md-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i><span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Admin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-printer"></i> <span class="d-none d-md-inline">Cetak</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download"></i> <span class="d-none d-md-inline">Export</span>
                            </button>
                        </div>
                        <span class="text-muted d-none d-md-inline"><?= date('l, d F Y') ?></span>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h3><i class="bi bi-person-circle me-2"></i>Selamat datang, <?= $userData['nama_lengkap'] ?></h3>
                            <p class="mb-0">Anda login sebagai Administrator Sistem. Terakhir login: <?= date('d/m/Y H:i') ?></p>
                        </div>
                        <div class="col-md-4 text-end d-none d-md-block">
                            <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-table me-2"></i>Daftar Jadwal Pelajaran</span>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="bi bi-plus-circle me-1"></i><span class="d-none d-md-inline">Tambah Jadwal</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="10%">Hari</th>
                                        <th width="15%">Waktu</th>
                                        <th width="15%">Kelas</th>
                                        <th width="20%">Mata Pelajaran</th>
                                        <th width="20%">Guru</th>
                                        <th width="20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jadwal as $j): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary badge-day"><?= $j['hari'] ?></span>
                                        </td>
                                        <td class="time-display">
                                            <?= substr($j['waktu_mulai'], 0, 5) ?> - <?= substr($j['waktu_selesai'], 0, 5) ?>
                                        </td>
                                        <td><?= $j['nama_kelas'] ?></td>
                                        <td><?= $j['nama_pelajaran'] ?></td>
                                        <td><?= $j['nama_guru'] ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap">
                                                <a href="edit_jadwal.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-warning btn-action mb-1">
                                                    <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                                </a>
                                                <a href="hapus_jadwal.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-danger btn-action mb-1" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
                                                    <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Hapus</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addScheduleModalLabel"><i class="bi bi-calendar-plus me-2"></i>Tambah Jadwal Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="tambah_jadwal.php" method="post">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="hari" class="form-label">Hari</label>
                                <select class="form-select" id="hari" name="hari" required>
                                    <option value="" selected disabled>Pilih Hari</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kelas_id" class="form-label">Kelas</label>
                                <select class="form-select" id="kelas_id" name="kelas_id" required>
                                    <option value="" selected disabled>Pilih Kelas</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= $k['nama_kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                                <input type="time" class="form-control" id="waktu_mulai" name="waktu_mulai" required>
                            </div>
                            <div class="col-md-6">
                                <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                                <input type="time" class="form-control" id="waktu_selesai" name="waktu_selesai" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mata_pelajaran_id" class="form-label">Mata Pelajaran</label>
                                <select class="form-select" id="mata_pelajaran_id" name="mata_pelajaran_id" required>
                                    <option value="" selected disabled>Pilih Mata Pelajaran</option>
                                    <?php foreach ($mapel as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= $m['nama_pelajaran'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="guru_id" class="form-label">Guru Pengajar</label>
                                <select class="form-select" id="guru_id" name="guru_id" required>
                                    <option value="" selected disabled>Pilih Guru</option>
                                    <?php foreach ($guru as $g): ?>
                                        <option value="<?= $g['id'] ?>"><?= $g['nama_lengkap'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple time validation
        document.getElementById('waktu_selesai').addEventListener('change', function() {
            const start = document.getElementById('waktu_mulai').value;
            const end = this.value;
            
            if (start && end && start >= end) {
                alert('Waktu selesai harus setelah waktu mulai!');
                this.value = '';
            }
        });
        
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth < 768) {
                const sidebar = document.querySelector('.sidebar');
                const navLinks = document.querySelectorAll('.sidebar .nav-link');
                
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (this.classList.contains('active')) {
                            sidebar.style.height = '60px';
                        } else {
                            sidebar.style.height = 'auto';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>