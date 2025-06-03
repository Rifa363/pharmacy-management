<?php
// Hapus atau komentari baris debugging ini di lingkungan produksi
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start(); // Memulai sesi PHP

// Mengimpor file koneksi database
// Pastikan db_connection.php berada di direktori yang sama atau jalur yang benar
include 'db_connection.php';

// Memeriksa apakah request adalah POST (dari submit form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil username dan password dari input form
    // Menggunakan htmlspecialchars dan trim untuk mencegah XSS dan menghapus spasi ekstra
    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars(trim($_POST['password']));

    // Memastikan username dan password tidak kosong
    if (empty($username) || empty($password)) {
        // Arahkan kembali ke halaman login dengan pesan error
        header("Location: ../index.php?error=" . urlencode("Username dan password tidak boleh kosong."));
        exit();
    }

    // Menyiapkan query SQL untuk mencari pengguna berdasarkan username
    // Menggunakan prepared statement untuk mencegah SQL Injection
    // Menambahkan kolom profile_picture ke dalam SELECT untuk disimpan di sesi
    $stmt = $conn->prepare("SELECT id, username, password, role, profile_picture FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // 's' berarti string
    $stmt->execute();
    $result = $stmt->get_result();

    // Memeriksa apakah pengguna ditemukan
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Ambil data pengguna

        // Memverifikasi password yang diinput dengan password_hash di database
        // password_verify() digunakan untuk membandingkan password plain text dengan hash
        if (password_verify($password, $user['password'])) {
            // Login berhasil! Set variabel sesi
            $_SESSION['user_id'] = $user['id']; // Simpan ID pengguna
            $_SESSION['username'] = $user['username']; // Simpan username
            $_SESSION['role'] = $user['role']; // Simpan role
            $_SESSION['profile_picture'] = $user['profile_picture']; // Simpan nama file gambar profil

            // Arahkan ke halaman dashboard
            header("Location: ../home.php");
            exit(); // Penting: Hentikan eksekusi skrip setelah redirect
        } else {
            // Password salah
            header("Location: ../index.php?error=" . urlencode("Username atau password salah."));
            exit();
        }
    } else {
        // Pengguna tidak ditemukan
        header("Location: ../index.php?error=" . urlencode("Username atau password salah."));
        exit();
    }

    $stmt->close(); // Menutup statement prepared
    $conn->close(); // Menutup koneksi database
} else {
    // Jika diakses langsung tanpa submit form POST, arahkan kembali ke halaman login
    header("Location: ../index.php");
    exit();
}
?>
