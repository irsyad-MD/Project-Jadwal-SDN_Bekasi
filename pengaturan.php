<?php
session_start();
require 'config.php';
checkLogin();

$userData = getUserData($pdo, $_SESSION['user_id']);
if ($userData['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

// Handle form submission for adding new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error_message'] = "Username sudah digunakan!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, 'admin', ?)");
            $stmt->execute([$username, $password, $nama_lengkap]);
            $_SESSION['success_message'] = "Admin baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Gagal menambahkan admin: " . $e->getMessage();
        }
    }
    header("Location: pengaturan.php");
    exit();
}

// Get all admins
$admins = $pdo->query("SELECT id, username, nama_lengkap, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Admin - Sistem Jadwal Sekolah</title>
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
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
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
                            <a class="nav-link" href="manajemen_siswa.php">
                                <i class="bi bi-people-fill me-2"></i>Siswa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="pengaturan.php">
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
                    <h1 class="h2">Pengaturan Admin</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="bi bi-plus-circle me-1"></i>Tambah Admin
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

                <!-- Admins Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-people-fill me-2"></i>Daftar Administrator
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $index => $admin): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($admin['username']) ?></td>
                                        <td><?= htmlspecialchars($admin['nama_lengkap']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($admin['created_at'])) ?></td>
                                        <td>
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?= $admin['id'] ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                            <?php else: ?>
                                            <span class="text-muted">Akun aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Delete Admin Modal -->
                                    <div class="modal fade" id="deleteAdminModal<?= $admin['id'] ?>" tabindex="-1" aria-labelledby="deleteAdminModalLabel<?= $admin['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteAdminModalLabel<?= $admin['id'] ?>">Hapus Admin</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="hapus_admin.php" method="post">
                                                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus admin <strong><?= htmlspecialchars($admin['nama_lengkap']) ?></strong>?</p>
                                                        <p class="text-danger">Admin yang dihapus tidak dapat dikembalikan!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
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

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addAdminModalLabel"><i class="bi bi-person-plus me-2"></i>Tambah Admin Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="pengaturan.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                            <div class="form-text">Username harus unik dan tidak mengandung spasi</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="form-text">Minimal 8 karakter, mengandung huruf dan angka</div>
                        </div>
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Batal</button>
                        <button type="submit" name="add_admin" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            // Check length
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Check for numbers
            if (/\d/.test(password)) strength += 1;
            
            // Check for special chars
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
            
            // Update strength bar
            let width = strength * 25;
            let color = 'red';
            
            if (strength >= 3) color = 'green';
            else if (strength >= 2) color = 'orange';
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        });
    </script>
</body>
</html>