<?php
session_start(); // Memulai sesi PHP

include 'php/db_connection.php';
include 'php/header.php'; // Termasuk header aplikasi dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id']; // Ambil ID pengguna dari sesi
$message = '';
$message_type = '';

// --- Ambil Data Pengguna Saat Ini ---
$current_username = '';
$current_role = '';
$current_profile_picture = 'default_profile.jpg'; // Default jika tidak ada di DB

$stmt = $conn->prepare("SELECT username, role, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user_data = $result->fetch_assoc();
    $current_username = $user_data['username'];
    $current_role = $user_data['role'];
    $current_profile_picture = $user_data['profile_picture'];
}
$stmt->close();

// --- Fungsionalitas Proses Form Update Profil ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $update_fields = [];
    $bind_types = '';
    $bind_params = [];

    // 1. Perbarui Username (jika berubah)
    if ($new_username !== $current_username) {
        // Cek apakah username baru sudah ada
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $new_username, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $message = "Username sudah digunakan oleh pengguna lain.";
            $message_type = "error";
        }
        $check_stmt->close();
        if ($message_type === 'error') goto end_process; // Lompat jika ada error username
        
        $update_fields[] = "username = ?";
        $bind_types .= "s";
        $bind_params[] = $new_username;
        $_SESSION['username'] = $new_username; // Perbarui sesi username
    }

    // 2. Perbarui Password (jika diisi)
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $message = "Konfirmasi password tidak cocok.";
            $message_type = "error";
            goto end_process; // Lompat jika password tidak cocok
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields[] = "password = ?";
        $bind_types .= "s";
        $bind_params[] = $hashed_password;
    }

    // 3. Tangani Upload Gambar Profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "Format gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF yang diizinkan.";
            $message_type = "error";
            goto end_process;
        }
        if ($file_size > $max_file_size) {
            $message = "Ukuran gambar terlalu besar. Maksimal 2MB.";
            $message_type = "error";
            goto end_process;
        }

        // Buat nama file unik
        $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
        $upload_dir = 'images/';
        $upload_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp_name, $upload_path)) {
            // Hapus gambar lama jika bukan default
            if ($current_profile_picture !== 'default_profile.jpg' && file_exists($upload_dir . $current_profile_picture)) {
                unlink($upload_dir . $current_profile_picture);
            }
            $update_fields[] = "profile_picture = ?";
            $bind_types .= "s";
            $bind_params[] = $new_file_name;
            $_SESSION['profile_picture'] = $new_file_name; // Perbarui sesi gambar profil
        } else {
            $message = "Gagal mengunggah gambar profil.";
            $message_type = "error";
            goto end_process;
        }
    }

    // Jika ada field yang perlu diupdate
    if (!empty($update_fields)) {
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $bind_types .= "i"; // Tambahkan tipe untuk user_id
        $bind_params[] = $user_id; // Tambahkan user_id ke parameter

        $stmt_update = $conn->prepare($sql);
        // Menggunakan call_user_func_array untuk bind_param karena jumlah parameter dinamis
        call_user_func_array([$stmt_update, 'bind_param'], array_merge([$bind_types], $bind_params));

        if ($stmt_update->execute()) {
            $message = "Profil berhasil diperbarui!";
            $message_type = "success";
            // Perbarui current_username dan current_profile_picture untuk tampilan langsung
            if (isset($new_username)) $current_username = $new_username;
            if (isset($new_file_name)) $current_profile_picture = $new_file_name;
        } else {
            $message = "Gagal memperbarui profil: " . $conn->error;
            $message_type = "error";
        }
        $stmt_update->close();
    } else {
        $message = "Tidak ada perubahan yang disimpan.";
        $message_type = "info";
    }

    end_process: // Label untuk goto
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header("Location: profile_management.php"); // Redirect untuk menampilkan pesan
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Profil - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Gaya spesifik untuk halaman profil */
        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
            margin-top: 30px;
        }

        .profile-card {
            text-align: center;
            width: 100%;
            max-width: 450px;
        }

        .profile-picture-display {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #3f51b5;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .profile-info h3 {
            font-size: 28px;
            color: #3f51b5;
            margin-bottom: 5px;
        }

        .profile-info p {
            font-size: 16px;
            color: #666;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .form-card .input-group-file {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-card .input-group-file label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 15px;
        }

        .form-card .input-group-file input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: calc(100% - 22px); /* Sesuaikan padding */
            box-sizing: border-box;
            background-color: #f9f9f9;
        }

        .password-note {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin-top: 20px;
            }
            .profile-picture-display {
                width: 120px;
                height: 120px;
            }
            .profile-info h3 {
                font-size: 24px;
            }
            .profile-info p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Manajemen Profil</h1>
        <p>Perbarui informasi akun dan gambar profil Anda.</p>

        <?php
        // Menampilkan pesan notifikasi dari sesi
        if (isset($_SESSION['message'])) {
            echo '<div class="alert ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="profile-container">
            <div class="card profile-card">
                <img src="images/<?php echo htmlspecialchars($current_profile_picture); ?>" alt="Profile Picture" class="profile-picture-display" onerror="this.onerror=null; this.src='images/default_profile.jpg';">
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($current_username); ?></h3>
                    <p>Role: <?php echo htmlspecialchars(ucfirst($current_role)); ?></p>
                </div>
            </div>

            <div class="card form-card" style="width: 100%; max-width: 500px;">
                <h2>Perbarui Profil</h2>
                <form action="profile_management.php" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password Baru (biarkan kosong jika tidak diubah):</label>
                        <input type="password" id="password" name="password">
                        <p class="password-note">Isi untuk mengubah password Anda.</p>
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Konfirmasi Password Baru:</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    <div class="input-group-file">
                        <label for="profile_picture">Upload Gambar Profil:</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <p class="password-note">Format: JPG, PNG, GIF. Maks: 2MB.</p>
                    </div>

                    <button type="submit" class="btn-primary">Perbarui Profil</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/restrict.js"></script>
</body>
</html>
