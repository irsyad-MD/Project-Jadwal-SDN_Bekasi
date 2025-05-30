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
    if (isset($_POST['add_class'])) {
        $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, tingkat) VALUES (?, ?)");
        $stmt->execute([$_POST['nama_kelas'], $_POST['tingkat']]);
        $_SESSION['success_message'] = "Kelas berhasil ditambahkan!";
        header("Location: manajemen_kelas.php");
        exit();
    } elseif (isset($_POST['edit_class'])) {
        $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ? WHERE id = ?");
        $stmt->execute([$_POST['nama_kelas'], $_POST['tingkat'], $_POST['id']]);
        $_SESSION['success_message'] = "Data kelas berhasil diperbarui!";
        header("Location: manajemen_kelas.php");
        exit();
    } elseif (isset($_POST['delete_class'])) {
        try {
            $pdo->beginTransaction();
            
            // Check if class is used in schedule or by students
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE kelas_id = ?");
            $stmt->execute([$_POST['id']]);
            $scheduleCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ?");
            $stmt->execute([$_POST['id']]);
            $studentCount = $stmt->fetchColumn();
            
            if ($scheduleCount > 0 || $studentCount > 0) {
                $_SESSION['error_message'] = "Kelas tidak dapat dihapus karena masih digunakan!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success_message'] = "Kelas berhasil dihapus!";
            }
            
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menghapus kelas: " . $e->getMessage();
        }
        header("Location: manajemen_kelas.php");
        exit();
    }
}

// Get all classes data
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat, nama_kelas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas - Sistem Jadwal Sekolah</title>
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
                            <a class="nav-link" href="manajemen_mapel.php">
                                <i class="bi bi-journal-bookmark me-2"></i>Mata Pelajaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manajemen_kelas.php">
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
                    <h1 class="h2">Manajemen Kelas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Kelas
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

                <!-- Classes Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-2"></i>Daftar Kelas
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Kelas</th>
                                        <th>Tingkat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kelas as $index => $k): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                        <td><?= htmlspecialchars($k['tingkat']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editClassModal<?= $k['id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteClassModal<?= $k['id'] ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Class Modal -->
                                    <div class="modal fade" id="editClassModal<?= $k['id'] ?>" tabindex="-1" aria-labelledby="editClassModalLabel<?= $k['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editClassModalLabel<?= $k['id'] ?>">Edit Kelas</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_kelas.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="nama_kelas<?= $k['id'] ?>" class="form-label">Nama Kelas</label>
                                                            <input type="text" class="form-control" id="nama_kelas<?= $k['id'] ?>" name="nama_kelas" value="<?= htmlspecialchars($k['nama_kelas']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="tingkat<?= $k['id'] ?>" class="form-label">Tingkat</label>
                                                            <select class="form-select" id="tingkat<?= $k['id'] ?>" name="tingkat" required>
                                                                <option value="1" <?= $k['tingkat'] == '1' ? 'selected' : '' ?>>Kelas 1</option>
                                                                <option value="2" <?= $k['tingkat'] == '2' ? 'selected' : '' ?>>Kelas 2</option>
                                                                <option value="3" <?= $k['tingkat'] == '3' ? 'selected' : '' ?>>Kelas 3</option>
                                                                <option value="4" <?= $k['tingkat'] == '4' ? 'selected' : '' ?>>Kelas 4</option>
                                                                <option value="5" <?= $k['tingkat'] == '5' ? 'selected' : '' ?>>Kelas 5</option>
                                                                <option value="6" <?= $k['tingkat'] == '6' ? 'selected' : '' ?>>Kelas 6</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_class" class="btn btn-warning">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Class Modal -->
                                    <div class="modal fade" id="deleteClassModal<?= $k['id'] ?>" tabindex="-1" aria-labelledby="deleteClassModalLabel<?= $k['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteClassModalLabel<?= $k['id'] ?>">Hapus Kelas</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_kelas.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus kelas <strong><?= htmlspecialchars($k['nama_kelas']) ?></strong>?</p>
                                                        <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="delete_class" class="btn btn-danger">Ya, Hapus</button>
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

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addClassModalLabel">Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manajemen_kelas.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_kelas" class="form-label">Nama Kelas</label>
                            <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" required>
                        </div>
                        <div class="mb-3">
                            <label for="tingkat" class="form-label">Tingkat</label>
                            <select class="form-select" id="tingkat" name="tingkat" required>
                                <option value="" selected disabled>Pilih Tingkat</option>
                                <option value="1">Kelas 1</option>
                                <option value="2">Kelas 2</option>
                                <option value="3">Kelas 3</option>
                                <option value="4">Kelas 4</option>
                                <option value="5">Kelas 5</option>
                                <option value="6">Kelas 6</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_class" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>