<?php
session_start(); // Memulai sesi PHP

include 'php/db_connection.php';
include 'php/header.php'; // Termasuk header aplikasi dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Pembatasan akses: hanya admin dan staff yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo "<p style='color: red; text-align: center; margin-top: 50px;'>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
    exit();
}

// --- Fungsionalitas Tambah/Edit Supplier ---
$supplier_id = '';
$name = '';
$contact_person = '';
$phone = '';
$email = '';
$address = '';
$is_edit = false; // Flag untuk menandakan mode edit

// Ambil data supplier jika ada parameter 'edit' di URL
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $supplier_id = htmlspecialchars($_GET['edit']);
    $is_edit = true;

    $stmt = $conn->prepare("SELECT id, name, contact_person, phone, email, address FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $supplier_data = $result->fetch_assoc();
        $name = $supplier_data['name'];
        $contact_person = $supplier_data['contact_person'];
        $phone = $supplier_data['phone'];
        $email = $supplier_data['email'];
        $address = $supplier_data['address'];
    } else {
        // Supplier tidak ditemukan, mungkin ID salah
        $is_edit = false; // Kembali ke mode tambah
    }
    $stmt->close();
}

// --- Fungsionalitas Hapus Supplier ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = htmlspecialchars($_GET['delete']);

    // Periksa apakah supplier terkait dengan obat mana pun sebelum dihapus
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM medicines WHERE supplier_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $count_result = $check_stmt->get_result()->fetch_row()[0];
    $check_stmt->close();

    if ($count_result > 0) {
        $_SESSION['message'] = "Gagal menghapus supplier. Supplier ini terhubung dengan " . $count_result . " obat. Harap perbarui data obat terlebih dahulu.";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Supplier berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menghapus supplier: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: supplier_management.php"); // Redirect kembali setelah hapus
    exit();
}

// --- Fungsionalitas Proses Form (Tambah/Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $contact_person = htmlspecialchars(trim($_POST['contact_person']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $email = htmlspecialchars(trim($_POST['email']));
    $address = htmlspecialchars(trim($_POST['address']));
    $form_supplier_id = htmlspecialchars(trim($_POST['supplier_id'] ?? ''));

    if (empty($name) || empty($contact_person) || empty($phone) || empty($email) || empty($address)) {
        $_SESSION['message'] = "Semua field wajib diisi!";
        $_SESSION['message_type'] = "error";
    } else {
        if ($form_supplier_id && $is_edit) { // Mode Update
            $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $contact_person, $phone, $email, $address, $form_supplier_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Supplier berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal memperbarui supplier: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        } else { // Mode Tambah Baru
            $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $contact_person, $phone, $email, $address);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Supplier baru berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menambahkan supplier: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    }
    header("Location: supplier_management.php"); // Redirect kembali setelah proses
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Supplier - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Manajemen Supplier</h1>
        <p>Kelola data supplier obat-obatan.</p>

        <?php
        // Menampilkan pesan notifikasi dari sesi
        if (isset($_SESSION['message'])) {
            echo '<div class="alert ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="card form-card">
            <h2><?php echo $is_edit ? 'Edit Supplier' : 'Tambah Supplier Baru'; ?></h2>
            <form action="supplier_management.php" method="POST">
                <input type="hidden" name="supplier_id" value="<?php echo htmlspecialchars($supplier_id); ?>">

                <div class="input-group">
                    <label for="name">Nama Supplier:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="input-group">
                    <label for="contact_person">Contact Person:</label>
                    <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($contact_person); ?>" required>
                </div>
                <div class="input-group">
                    <label for="phone">Telepon:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="input-group">
                    <label for="address">Alamat:</label>
                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>

                <button type="submit" class="btn-primary"><?php echo $is_edit ? 'Perbarui Supplier' : 'Tambah Supplier'; ?></button>
                <?php if ($is_edit): ?>
                    <a href="supplier_management.php" class="btn-cancel">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card table-card">
            <h2>Daftar Supplier</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Supplier</th>
                            <th>Contact Person</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ambil data supplier dari database
                        $sql = "SELECT id, name, contact_person, phone, email, address FROM suppliers ORDER BY name ASC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['contact_person']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                echo "<td class='actions'>";
                                echo "<a href='supplier_management.php?edit=" . htmlspecialchars($row['id']) . "' class='action-btn edit-btn' title='Edit'><i class='fas fa-edit'></i></a>";
                                echo "<a href='supplier_management.php?delete=" . htmlspecialchars($row['id']) . "' class='action-btn delete-btn' title='Hapus' onclick='return confirm(\"Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.\");'><i class='fas fa-trash-alt'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>Tidak ada data supplier.</td></tr>";
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
