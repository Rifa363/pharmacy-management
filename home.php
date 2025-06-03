<?php
session_start(); // Memulai sesi PHP

// Mengimpor file koneksi database dan header
include 'php/db_connection.php';
include 'php/header.php'; // Ini akan menampilkan header umum dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Mengambil informasi pengguna dari sesi
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'Staff'; // Default role jika tidak diset
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
    // Memanggil fungsi untuk menampilkan header aplikasi
    displayHeader();
    // Mengimpor sidebar navigasi
    include 'php/sidenav.php';
    ?>

    <div class="main-content" id="mainContent">
        <h1>Selamat Datang, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Ini adalah dashboard sistem manajemen apotek Anda. Anda dapat melihat ringkasan penting di sini.</p>

        <div class="dashboard-widgets">
            <div class="widget">
                <i class="fas fa-pills widget-icon"></i>
                <h3>Total Obat Tersedia</h3>
                <p>1500 Jenis</p> </div>
            <div class="widget">
                <i class="fas fa-money-bill-wave widget-icon"></i>
                <h3>Transaksi Hari Ini</h3>
                <p>Rp 5.000.000</p> </div>
            <div class="widget">
                <i class="fas fa-exclamation-triangle widget-icon"></i>
                <h3>Obat Hampir Kadaluarsa</h3>
                <p>25 Item</p> </div>
            <div class="widget">
                <i class="fas fa-box-open widget-icon"></i>
                <h3>Stok Obat Rendah</h3>
                <p>10 Item</p> </div>
        </div>

        <div class="recent-activity">
            <h2>Aktivitas Terbaru</h2>
            <ul>
                <li><i class="fas fa-check-circle activity-icon"></i> <span>2025-05-31</span> - <strong><?php echo htmlspecialchars($username); ?></strong> melakukan penjualan obat Paracetamol.</li>
                <li><i class="fas fa-plus-circle activity-icon"></i> <span>2025-05-30</span> - <strong>Admin</strong> menambah stok obat Amoxicillin.</li>
                <li><i class="fas fa-edit activity-icon"></i> <span>2025-05-29</span> - <strong>Staff</strong> memperbarui harga obat Vitamin C.</li>
                <li><i class="fas fa-truck-loading activity-icon"></i> <span>2025-05-28</span> - <strong>Admin</strong> menerima pasokan dari Supplier A.</li>
            </ul>
        </div>
    </div>

    <script src="js/restrict.js"></script>
</body>
</html>
