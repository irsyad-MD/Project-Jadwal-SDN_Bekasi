<?php
// Koneksi database
$host = 'sql211.infinityfree.com';
$dbname = 'if0_39046857_mapel';
$username = 'if0_39046857';
$password = 'PJIXwkjm5KwTbO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk memeriksa login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

// Fungsi untuk mendapatkan data user
function getUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>