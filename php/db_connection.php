<?php
// Konfigurasi koneksi database
$servername = "localhost"; // Alamat server database Anda (biasanya localhost)
$username = "root";      // Username database Anda (ganti jika berbeda)
$password = "";          // Password database Anda (ganti jika berbeda)
$dbname = "pharmacy_management"; // Nama database yang akan digunakan

// Membuat koneksi ke database menggunakan MySQLi
     
    $conn = mysqli_connect($servername, $username, $password, $dbname);

// Memeriksa apakah koneksi berhasil atau gagal
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi skrip dan tampilkan pesan error
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Opsional: Atur charset koneksi untuk mendukung karakter khusus
$conn->set_charset("utf8mb4");

// echo "Koneksi database berhasil!"; // Baris ini bisa di-uncomment untuk pengujian koneksi awal
?>
