<?php
session_start(); // Memulai sesi PHP

include 'php/db_connection.php';
include 'php/header.php'; // Termasuk header aplikasi dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Pembatasan akses: HANYA ADMIN yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<p style='color: red; text-align: center; margin-top: 50px;'>Anda tidak memiliki izin untuk mengakses halaman ini. Hanya Admin yang diizinkan.</p>";
    exit();
}

// --- Fungsionalitas Tambah/Edit Pengguna ---
$user_id = '';
$username = '';
$role = 'staff'; // Default role saat tambah
$is_edit = false; // Flag untuk menandakan mode edit
$message = '';
$message_type = '';

// Ambil data pengguna jika ada parameter 'edit' di URL
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $user_id = htmlspecialchars($_GET['edit']);
    $is_edit = true;

    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $username = $user_data['username'];
        $role = $user_data['role'];
    } else {
        // Pengguna tidak ditemukan, mungkin ID salah
        $is_edit = false; // Kembali ke mode tambah
        $message = "Pengguna tidak ditemukan.";
        $message_type = "error";
    }
    $stmt->close();
}

// --- Fungsionalitas Hapus Pengguna ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = htmlspecialchars($_GET['delete']);

    // Mencegah admin menghapus dirinya sendiri
    if ($delete_id == $_SESSION['user_id']) { // Pastikan user_id disimpan di sesi saat login
        $_SESSION['message'] = "Anda tidak bisa menghapus akun Anda sendiri!";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Pengguna berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menghapus pengguna: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: user_management.php"); // Redirect kembali setelah hapus
    exit();
}

// --- Fungsionalitas Proses Form (Tambah/Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_user_id = htmlspecialchars(trim($_POST['user_id'] ?? ''));
    $username_post = htmlspecialchars(trim($_POST['username']));
    $password_post = $_POST['password'] ?? ''; // Password bisa kosong jika tidak diubah saat edit
    $role_post = htmlspecialchars(trim($_POST['role']));

    // Validasi input
    if (empty($username_post) || empty($role_post)) {
        $message = "Username dan Role wajib diisi!";
        $message_type = "error";
    } elseif ($form_user_id && $is_edit) { // Mode Update
        // Cek apakah username sudah ada untuk user lain
        $check_username_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_username_stmt->bind_param("si", $username_post, $form_user_id);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();

        if ($check_username_result->num_rows > 0) {
            $message = "Username sudah digunakan oleh pengguna lain!";
            $message_type = "error";
        } else {
            if (!empty($password_post)) { // Update dengan password baru
                $hashed_password = password_hash($password_post, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $username_post, $hashed_password, $role_post, $form_user_id);
            } else { // Update tanpa mengubah password
                $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
                $stmt->bind_param("ssi", $username_post, $role_post, $form_user_id);
            }

            if ($stmt->execute()) {
                $message = "Pengguna berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui pengguna: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_username_stmt->close();

    } else { // Mode Tambah Baru
        // Cek apakah username sudah ada
        $check_username_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_username_stmt->bind_param("s", $username_post);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();

        if ($check_username_result->num_rows > 0) {
            $message = "Username sudah ada. Pilih username lain.";
            $message_type = "error";
        } elseif (empty($password_post)) {
            $message = "Password wajib diisi untuk pengguna baru!";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password_post, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username_post, $hashed_password, $role_post);

            if ($stmt->execute()) {
                $message = "Pengguna baru berhasil ditambahkan!";
                $message_type = "success";
            } else {
                $message = "Gagal menambahkan pengguna: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_username_stmt->close();
    }

    // Set pesan ke sesi untuk ditampilkan setelah redirect
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header("Location: user_management.php"); // Redirect kembali setelah proses
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Gaya spesifik untuk halaman ini jika diperlukan */
        /* Misalnya untuk mengatur lebar kolom password */
        .password-note {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Manajemen Pengguna</h1>
        <p>Kelola akun pengguna (Admin dan Staff) sistem apotek.</p>

        <?php
        // Menampilkan pesan notifikasi dari sesi
        if (isset($_SESSION['message'])) {
            echo '<div class="alert ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="card form-card">
            <h2><?php echo $is_edit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h2>
            <form action="user_management.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required <?php echo $is_edit ? 'readonly' : ''; ?>>
                    <?php if ($is_edit): ?>
                        <p class="password-note">Username tidak bisa diubah setelah dibuat.</p>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="password"><?php echo $is_edit ? 'Password (Biarkan kosong jika tidak diubah):' : 'Password:'; ?></label>
                    <input type="password" id="password" name="password" <?php echo $is_edit ? '' : 'required'; ?>>
                    <?php if ($is_edit): ?>
                        <p class="password-note">Password akan diupdate jika diisi.</p>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="staff" <?php echo ($role == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary"><?php echo $is_edit ? 'Perbarui Pengguna' : 'Tambah Pengguna'; ?></button>
                <?php if ($is_edit): ?>
                    <a href="user_management.php" class="btn-cancel">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card table-card">
            <h2>Daftar Pengguna</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ambil data pengguna dari database
                        $sql = "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars(ucfirst($row['role'])) . "</td>";
                                echo "<td>" . date('d-m-Y H:i', strtotime($row['created_at'])) . "</td>";
                                echo "<td class='actions'>";
                                // Tombol Edit
                                echo "<a href='user_management.php?edit=" . htmlspecialchars($row['id']) . "' class='action-btn edit-btn' title='Edit'><i class='fas fa-edit'></i></a>";
                                // Tombol Hapus (admin tidak bisa menghapus dirinya sendiri)
                                if ($row['id'] != $_SESSION['user_id']) {
                                    echo "<a href='user_management.php?delete=" . htmlspecialchars($row['id']) . "' class='action-btn delete-btn' title='Hapus' onclick='return confirm(\"Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.\");'><i class='fas fa-trash-alt'></i></a>";
                                } else {
                                    echo "<span class='action-btn delete-btn' style='opacity: 0.5; cursor: not-allowed;' title='Tidak bisa menghapus akun sendiri'><i class='fas fa-trash-alt'></i></span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>Tidak ada data pengguna.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/restrict.js"></script>
</body>
</html>
