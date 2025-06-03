<?php
        // Script sederhana untuk menghasilkan hash password
        $password_to_hash = 'bunga123'; // Ganti dengan password yang ingin Anda gunakan (misal: 'admin123')
        $hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
        echo "Password asli: " . $password_to_hash . "<br>";
        echo "Hash yang dihasilkan: " . $hashed_password . "<br>";
        ?>
        