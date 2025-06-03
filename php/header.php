<?php
// Pastikan sesi sudah dimulai. Jika belum, mulai sesi.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk menampilkan header aplikasi.
 * Header ini akan mencakup judul aplikasi, tombol untuk membuka sidebar,
 * dan informasi pengguna yang sedang login beserta tombol logout.
 */
function displayHeader() {
    echo '<div class="app-header">';
    // Tombol hamburger untuk membuka/menutup sidebar
    echo '    <span class="openbtn" onclick="toggleNav()">&#9776;</span>';
    // Judul utama aplikasi
    echo '    <h1>Sistem Informasi Apotek</h1>';

    // Menampilkan informasi pengguna jika sudah login
    if (isset($_SESSION['username'])) {
        echo '    <div class="user-info">';
        echo '        <span>Halo, ' . htmlspecialchars($_SESSION['username']) . '!</span>';
        // Tombol logout yang mengarah ke logout.php
        echo '        <a href="logout.php" class="logout-btn">Logout</a>';
        echo '    </div>';
    }
    echo '</div>';
}

// Anda mungkin juga ingin membuat file `logout.php` terpisah:
/*
// File: logout.php
<?php
session_start(); // Memulai sesi
session_unset(); // Menghapus semua variabel sesi
session_destroy(); // Menghancurkan sesi
header("Location: index.html"); // Mengarahkan kembali ke halaman login
exit();
?>
*/
?>
