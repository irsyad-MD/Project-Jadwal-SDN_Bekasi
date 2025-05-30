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
    if (isset($_POST['add_teacher'])) {
        try {
            $pdo->beginTransaction();
            
            // First create user account
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, 'guru', ?)");
            $stmt->execute([$_POST['username'], $hashedPassword, $_POST['nama_lengkap']]);
            $user_id = $pdo->lastInsertId();
            
            // Then create teacher record
            $stmt = $pdo->prepare("INSERT INTO guru (user_id, mata_pelajaran_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $_POST['mata_pelajaran_id']]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Guru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menambahkan guru: " . $e->getMessage();
        }
        header("Location: manajemen_guru.php");
        exit();
    } elseif (isset($_POST['edit_teacher'])) {
        $stmt = $pdo->prepare("UPDATE users u JOIN guru g ON u.id = g.user_id 
                              SET u.nama_lengkap = ?, g.mata_pelajaran_id = ? 
                              WHERE g.id = ?");
        $stmt->execute([$_POST['nama_lengkap'], $_POST['mata_pelajaran_id'], $_POST['id']]);
        $_SESSION['success_message'] = "Data guru berhasil diperbarui!";
        header("Location: manajemen_guru.php");
        exit();
    } elseif (isset($_POST['delete_teacher'])) {
        try {
            $pdo->beginTransaction();
            
            // First get user_id
            $stmt = $pdo->prepare("SELECT user_id FROM guru WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete teacher record
            $stmt = $pdo->prepare("DELETE FROM guru WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$teacher['user_id']]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Guru berhasil dihapus!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menghapus guru: " . $e->getMessage();
        }
        header("Location: manajemen_guru.php");
        exit();
    }
}

// Get all teachers data
$stmt = $pdo->query("SELECT g.id, g.mata_pelajaran_id, u.id as user_id, u.username, u.nama_lengkap, mp.nama_pelajaran 
                     FROM guru g 
                     JOIN users u ON g.user_id = u.id
                     JOIN mata_pelajaran mp ON g.mata_pelajaran_id = mp.id");
$guru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects for dropdown
$mapel = $pdo->query("SELECT * FROM mata_pelajaran")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Guru - Sistem Jadwal Sekolah</title>
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
                            <a class="nav-link active" href="manajemen_guru.php">
                                <i class="bi bi-people me-2"></i>Manajemen Guru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manajemen_mapel.php">
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
                    <h1 class="h2">Manajemen Guru</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Guru
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

                <!-- Teachers Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-2"></i>Daftar Guru
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guru as $index => $g): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($g['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($g['username']) ?></td>
                                        <td><?= htmlspecialchars($g['nama_pelajaran']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editTeacherModal<?= $g['id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteTeacherModal<?= $g['id'] ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Teacher Modal -->
                                    <div class="modal fade" id="editTeacherModal<?= $g['id'] ?>" tabindex="-1" aria-labelledby="editTeacherModalLabel<?= $g['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editTeacherModalLabel<?= $g['id'] ?>">Edit Data Guru</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_guru.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="nama_lengkap<?= $g['id'] ?>" class="form-label">Nama Lengkap</label>
                                                            <input type="text" class="form-control" id="nama_lengkap<?= $g['id'] ?>" name="nama_lengkap" value="<?= htmlspecialchars($g['nama_lengkap']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="mata_pelajaran_id<?= $g['id'] ?>" class="form-label">Mata Pelajaran</label>
                                                            <select class="form-select" id="mata_pelajaran_id<?= $g['id'] ?>" name="mata_pelajaran_id" required>
                                                                <?php foreach ($mapel as $m): ?>
                                                                    <option value="<?= $m['id'] ?>" <?= $m['id'] == $g['mata_pelajaran_id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($m['nama_pelajaran']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_teacher" class="btn btn-warning">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Teacher Modal -->
                                    <div class="modal fade" id="deleteTeacherModal<?= $g['id'] ?>" tabindex="-1" aria-labelledby="deleteTeacherModalLabel<?= $g['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteTeacherModalLabel<?= $g['id'] ?>">Hapus Guru</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_guru.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus guru <strong><?= htmlspecialchars($g['nama_lengkap']) ?></strong>?</p>
                                                        <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="delete_teacher" class="btn btn-danger">Ya, Hapus</button>
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

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addTeacherModalLabel">Tambah Guru Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manajemen_guru.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="mata_pelajaran_id" class="form-label">Mata Pelajaran</label>
                            <select class="form-select" id="mata_pelajaran_id" name="mata_pelajaran_id" required>
                                <option value="" selected disabled>Pilih Mata Pelajaran</option>
                                <?php foreach ($mapel as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_pelajaran']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>