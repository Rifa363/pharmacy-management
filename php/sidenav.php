<?php
// Pastikan sesi sudah dimulai untuk mengakses $_SESSION['username'] dan $_SESSION['role']
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mendapatkan username, role, dan profile_picture dari sesi. Gunakan nilai default jika tidak ada.
$current_username = $_SESSION['username'] ?? 'Pengguna';
$current_role = $_SESSION['role'] ?? 'Staff';
// Ambil profile_picture dari sesi, default ke 'default_profile.jpg' jika tidak ada
$current_profile_picture_path = 'images/' . ($_SESSION['profile_picture'] ?? 'default_profile.jpg');
?>

<div id="mySidenav" class="sidenav">
    <a href="javascript:void(0)" class="closebtn" onclick="toggleNav()">&times;</a>

    <div class="profile-section">
        <img src="<?php echo htmlspecialchars($current_profile_picture_path); ?>" alt="Profile Picture" class="profile-pic" onerror="this.onerror=null; this.src='images/default_profile.jpg';">
        <h3><?php echo htmlspecialchars($current_username); ?></h3>
        <p><?php echo htmlspecialchars($current_role); ?></p>
    </div>

    <a href="home.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="medicine_management.php"><i class="fas fa-pills"></i> Manajemen Obat</a>
    <a href="sales_management.php"><i class="fas fa-shopping-cart"></i> Manajemen Penjualan</a>
    <a href="supplier_management.php"><i class="fas fa-truck"></i> Manajemen Supplier</a>
    <a href="reports_management.php"><i class="fas fa-chart-line"></i> Laporan</a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <a href="user_management.php"><i class="fas fa-users-cog"></i> Manajemen Pengguna</a>
    <?php endif; ?>

    <a href="profile_management.php"><i class="fas fa-user-circle"></i> Manajemen Profil</a> <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
