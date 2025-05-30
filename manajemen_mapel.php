<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (nama_pelajaran, kode_pelajaran) VALUES (?, ?)");
        $stmt->execute([$_POST['nama_pelajaran'], $_POST['kode_pelajaran']]);
        $_SESSION['success_message'] = "Mata pelajaran berhasil ditambahkan!";
        header("Location: manajemen_mapel.php");
        exit();
    } elseif (isset($_POST['edit_subject'])) {
        $stmt = $pdo->prepare("UPDATE mata_pelajaran SET nama_pelajaran = ?, kode_pelajaran = ? WHERE id = ?");
        $stmt->execute([$_POST['nama_pelajaran'], $_POST['kode_pelajaran'], $_POST['id']]);
        $_SESSION['success_message'] = "Data mata pelajaran berhasil diperbarui!";
        header("Location: manajemen_mapel.php");
        exit();
    } elseif (isset($_POST['delete_subject'])) {
        try {
            $pdo->beginTransaction();
            
            // Check if subject is used in schedule or by teachers
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE mata_pelajaran_id = ?");
            $stmt->execute([$_POST['id']]);
            $scheduleCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM guru WHERE mata_pelajaran_id = ?");
            $stmt->execute([$_POST['id']]);
            $teacherCount = $stmt->fetchColumn();
            
            if ($scheduleCount > 0 || $teacherCount > 0) {
                $_SESSION['error_message'] = "Mata pelajaran tidak dapat dihapus karena masih digunakan!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM mata_pelajaran WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success_message'] = "Mata pelajaran berhasil dihapus!";
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menghapus mata pelajaran: " . $e->getMessage();
        }
        header("Location: manajemen_mapel.php");
        exit();
    }
}

// Get all subjects data
$mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_pelajaran")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mata Pelajaran - Sistem Jadwal Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Same style as the dashboard */
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
        .table th {
            background-color: #f1f1f1;
            font-weight: 600;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 0 3px;
            font-size: 0.85rem;
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
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-calendar-week me-2"></i>Jadwal Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_guru.php">
                                <i class="bi bi-people me-2"></i>Manajemen Guru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manajemen_mapel.php">
                                <i class="bi bi-journal-bookmark me-2"></i>Mata Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_kelas.php">
                                <i class="bi bi-building me-2"></i>Kelas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_siswa.php">
                                <i class="bi bi-people-fill me-2"></i>Siswa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaturan.php">
                                <i class="bi bi-gear me-2"></i>Pengaturan
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
                    <h1 class="h2">Manajemen Mata Pelajaran</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Mata Pelajaran
                        </button>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Subjects Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-2"></i>Daftar Mata Pelajaran
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kode</th>
                                        <th>Nama Mata Pelajaran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mapel as $index => $m): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($m['kode_pelajaran']) ?></td>
                                        <td><?= htmlspecialchars($m['nama_pelajaran']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editSubjectModal<?= $m['id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteSubjectModal<?= $m['id'] ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Subject Modal -->
                                    <div class="modal fade" id="editSubjectModal<?= $m['id'] ?>" tabindex="-1" aria-labelledby="editSubjectModalLabel<?= $m['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editSubjectModalLabel<?= $m['id'] ?>">Edit Mata Pelajaran</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_mapel.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="nama_pelajaran<?= $m['id'] ?>" class="form-label">Nama Mata Pelajaran</label>
                                                            <input type="text" class="form-control" id="nama_pelajaran<?= $m['id'] ?>" name="nama_pelajaran" value="<?= htmlspecialchars($m['nama_pelajaran']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="kode_pelajaran<?= $m['id'] ?>" class="form-label">Kode Pelajaran</label>
                                                            <input type="text" class="form-control" id="kode_pelajaran<?= $m['id'] ?>" name="kode_pelajaran" value="<?= htmlspecialchars($m['kode_pelajaran']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_subject" class="btn btn-warning">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Subject Modal -->
                                    <div class="modal fade" id="deleteSubjectModal<?= $m['id'] ?>" tabindex="-1" aria-labelledby="deleteSubjectModalLabel<?= $m['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteSubjectModalLabel<?= $m['id'] ?>">Hapus Mata Pelajaran</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_mapel.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus mata pelajaran <strong><?= htmlspecialchars($m['nama_pelajaran']) ?></strong>?</p>
                                                        <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="delete_subject" class="btn btn-danger">Ya, Hapus</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addSubjectModalLabel">Tambah Mata Pelajaran Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manajemen_mapel.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_pelajaran" class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" class="form-control" id="nama_pelajaran" name="nama_pelajaran" required>
                        </div>
                        <div class="mb-3">
                            <label for="kode_pelajaran" class="form-label">Kode Pelajaran</label>
                            <input type="text" class="form-control" id="kode_pelajaran" name="kode_pelajaran" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_subject" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>