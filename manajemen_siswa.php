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
    if (isset($_POST['add_student'])) {
        // Create user account first
        $username = trim($_POST['nis']);
        $password = password_hash($username, PASSWORD_DEFAULT); // Default password is NIS
        $nama_lengkap = trim($_POST['nama_lengkap']);
        
        try {
            $pdo->beginTransaction();
            
            // Create user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, 'siswa', ?)");
            $stmt->execute([$username, $password, $nama_lengkap]);
            $user_id = $pdo->lastInsertId();
            
            // Create student record
            $stmt = $pdo->prepare("INSERT INTO siswa (user_id, kelas_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $_POST['kelas_id']]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Siswa berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menambahkan siswa: " . $e->getMessage();
        }
        header("Location: manajemen_siswa.php");
        exit();
    } elseif (isset($_POST['edit_student'])) {
        $stmt = $pdo->prepare("UPDATE users u JOIN siswa s ON u.id = s.user_id 
                              SET u.nama_lengkap = ?, s.kelas_id = ? 
                              WHERE s.id = ?");
        $stmt->execute([$_POST['nama_lengkap'], $_POST['kelas_id'], $_POST['id']]);
        $_SESSION['success_message'] = "Data siswa berhasil diperbarui!";
        header("Location: manajemen_siswa.php");
        exit();
    } elseif (isset($_POST['delete_student'])) {
        try {
            $pdo->beginTransaction();
            
            // Get user_id first
            $stmt = $pdo->prepare("SELECT user_id FROM siswa WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $user_id = $stmt->fetchColumn();
            
            // Delete student record
            $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Siswa berhasil dihapus!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Gagal menghapus siswa: " . $e->getMessage();
        }
        header("Location: manajemen_siswa.php");
        exit();
    } elseif (isset($_POST['reset_password'])) {
        $newPassword = password_hash($_POST['nis'], PASSWORD_DEFAULT); // Reset to NIS
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newPassword, $_POST['user_id']]);
        $_SESSION['success_message'] = "Password berhasil direset ke NIS!";
        header("Location: manajemen_siswa.php");
        exit();
    }
}

// Get all students data with class information
$siswa = $pdo->query("SELECT s.id, u.id as user_id, u.username as nis, u.nama_lengkap, k.nama_kelas, k.id as kelas_id 
                      FROM siswa s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN kelas k ON s.kelas_id = k.id
                      ORDER BY k.nama_kelas, u.nama_lengkap")->fetchAll();

// Get all classes for dropdown
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Siswa - Sistem Jadwal Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Same style as other pages */
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
        .nis-badge {
            background-color: #e9ecef;
            color: #495057;
            font-family: monospace;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.85em;
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
                            <a class="nav-link" href="manajemen_kelas.php">
                                <i class="bi bi-building me-2"></i>Kelas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manajemen_siswa.php">
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
                    <h1 class="h2">Manajemen Siswa</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Siswa
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

                <!-- Students Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-people-fill me-2"></i>Daftar Siswa
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>NIS</th>
                                        <th>Nama Lengkap</th>
                                        <th>Kelas</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($siswa as $index => $s): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><span class="nis-badge"><?= htmlspecialchars($s['nis']) ?></span></td>
                                        <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($s['nama_kelas']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editStudentModal<?= $s['id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-info btn-action" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?= $s['id'] ?>">
                                                <i class="bi bi-key"></i> Reset Password
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteStudentModal<?= $s['id'] ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Student Modal -->
                                    <div class="modal fade" id="editStudentModal<?= $s['id'] ?>" tabindex="-1" aria-labelledby="editStudentModalLabel<?= $s['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-white">
                                                    <h5 class="modal-title" id="editStudentModalLabel<?= $s['id'] ?>">Edit Data Siswa</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_siswa.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">NIS</label>
                                                            <input type="text" class="form-control" value="<?= htmlspecialchars($s['nis']) ?>" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="nama_lengkap<?= $s['id'] ?>" class="form-label">Nama Lengkap</label>
                                                            <input type="text" class="form-control" id="nama_lengkap<?= $s['id'] ?>" name="nama_lengkap" value="<?= htmlspecialchars($s['nama_lengkap']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="kelas_id<?= $s['id'] ?>" class="form-label">Kelas</label>
                                                            <select class="form-select" id="kelas_id<?= $s['id'] ?>" name="kelas_id" required>
                                                                <?php foreach ($kelas as $k): ?>
                                                                    <option value="<?= $k['id'] ?>" <?= $k['id'] == $s['kelas_id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($k['nama_kelas']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_student" class="btn btn-warning">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetPasswordModal<?= $s['id'] ?>" tabindex="-1" aria-labelledby="resetPasswordModalLabel<?= $s['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title" id="resetPasswordModalLabel<?= $s['id'] ?>">Reset Password</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_siswa.php" method="post">
                                                    <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                                                    <input type="hidden" name="nis" value="<?= $s['nis'] ?>">
                                                    <div class="modal-body">
                                                        <p>Reset password untuk siswa <strong><?= htmlspecialchars($s['nama_lengkap']) ?></strong>?</p>
                                                        <p>Password akan direset ke NIS: <span class="nis-badge"><?= $s['nis'] ?></span></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="reset_password" class="btn btn-info">Ya, Reset Password</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Student Modal -->
                                    <div class="modal fade" id="deleteStudentModal<?= $s['id'] ?>" tabindex="-1" aria-labelledby="deleteStudentModalLabel<?= $s['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteStudentModalLabel<?= $s['id'] ?>">Hapus Siswa</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="manajemen_siswa.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus siswa <strong><?= htmlspecialchars($s['nama_lengkap']) ?></strong>?</p>
                                                        <p class="text-danger">Semua data siswa termasuk akun login akan dihapus permanen!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="delete_student" class="btn btn-danger">Ya, Hapus</button>
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

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addStudentModalLabel"><i class="bi bi-person-plus me-2"></i>Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manajemen_siswa.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nis" class="form-label">NIS (Nomor Induk Siswa)</label>
                            <input type="text" class="form-control" id="nis" name="nis" required pattern="[0-9]+" title="Hanya angka yang diperbolehkan">
                            <div class="form-text">NIS akan digunakan sebagai username untuk login</div>
                        </div>
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label for="kelas_id" class="form-label">Kelas</label>
                            <select class="form-select" id="kelas_id" name="kelas_id" required>
                                <option value="" selected disabled>Pilih Kelas</option>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Password default untuk login adalah NIS siswa
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Batal</button>
                        <button type="submit" name="add_student" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>