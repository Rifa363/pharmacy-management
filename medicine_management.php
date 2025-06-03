<?php
session_start(); // Memulai sesi PHP

// Mengimpor file koneksi database dan header
include 'php/db_connection.php';
include 'php/header.php'; // Ini akan menampilkan header umum dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

// Memeriksa peran pengguna untuk pembatasan akses (misal: hanya admin dan staff yang bisa kelola obat)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo "<p style='color: red; text-align: center; margin-top: 50px;'>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
    exit();
}

// --- Fungsionalitas Tambah/Edit Obat ---
$medicine_id = '';
$name = '';
$description = '';
$price = '';
$stock = '';
$category = '';
$expiry_date = '';
$supplier_id = '';
$is_edit = false; // Flag untuk menandakan mode edit

// Ambil data obat jika ada parameter 'edit' di URL
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $medicine_id = htmlspecialchars($_GET['edit']);
    $is_edit = true;

    $stmt = $conn->prepare("SELECT id, name, description, price, stock, category, expiry_date, supplier_id FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $medicine_data = $result->fetch_assoc();
        $name = $medicine_data['name'];
        $description = $medicine_data['description'];
        $price = $medicine_data['price'];
        $stock = $medicine_data['stock'];
        $category = $medicine_data['category'];
        $expiry_date = $medicine_data['expiry_date'];
        $supplier_id = $medicine_data['supplier_id'];
    } else {
        // Obat tidak ditemukan, mungkin ID salah
        $is_edit = false; // Kembali ke mode tambah
    }
    $stmt->close();
}

// --- Fungsionalitas Hapus Obat ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = htmlspecialchars($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Obat berhasil dihapus!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menghapus obat: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    header("Location: medicine_management.php"); // Redirect kembali setelah hapus
    exit();
}

// --- Fungsionalitas Proses Form (Tambah/Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = htmlspecialchars(trim($_POST['price']));
    $stock = htmlspecialchars(trim($_POST['stock']));
    $category = htmlspecialchars(trim($_POST['category']));
    $expiry_date = htmlspecialchars(trim($_POST['expiry_date']));
    $supplier_id = htmlspecialchars(trim($_POST['supplier_id']));
    $form_medicine_id = htmlspecialchars(trim($_POST['medicine_id'] ?? ''));

    if (empty($name) || empty($price) || empty($stock) || empty($category) || empty($expiry_date) || empty($supplier_id)) {
        $_SESSION['message'] = "Semua field wajib diisi!";
        $_SESSION['message_type'] = "error";
    } else {
        if ($form_medicine_id && $is_edit) { // Mode Update
            $stmt = $conn->prepare("UPDATE medicines SET name=?, description=?, price=?, stock=?, category=?, expiry_date=?, supplier_id=? WHERE id=?");
            $stmt->bind_param("ssdissii", $name, $description, $price, $stock, $category, $expiry_date, $supplier_id, $form_medicine_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Obat berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal memperbarui obat: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        } else { // Mode Tambah Baru
            $stmt = $conn->prepare("INSERT INTO medicines (name, description, price, stock, category, expiry_date, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $expiry_date, $supplier_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Obat baru berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menambahkan obat: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    }
    header("Location: medicine_management.php"); // Redirect kembali setelah proses
    exit();
}

// Mengambil daftar supplier untuk dropdown form
$suppliers_result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers = [];
if ($suppliers_result->num_rows > 0) {
    while($row = $suppliers_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Obat - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Manajemen Obat</h1>
        <p>Kelola data obat-obatan yang tersedia di apotek Anda.</p>

        <?php
        // Menampilkan pesan notifikasi dari sesi
        if (isset($_SESSION['message'])) {
            echo '<div class="alert ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="card form-card">
            <h2><?php echo $is_edit ? 'Edit Obat' : 'Tambah Obat Baru'; ?></h2>
            <form action="medicine_management.php" method="POST">
                <input type="hidden" name="medicine_id" value="<?php echo htmlspecialchars($medicine_id); ?>">

                <div class="input-group">
                    <label for="name">Nama Obat:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="input-group">
                    <label for="description">Deskripsi:</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="input-group">
                    <label for="price">Harga (Rp):</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                </div>
                <div class="input-group">
                    <label for="stock">Stok:</label>
                    <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($stock); ?>" required>
                </div>
                <div class="input-group">
                    <label for="category">Kategori:</label>
                    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>" required>
                </div>
                <div class="input-group">
                    <label for="expiry_date">Tanggal Kadaluarsa:</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($expiry_date); ?>" required>
                </div>
                <div class="input-group">
                    <label for="supplier_id">Supplier:</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Pilih Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier['id']); ?>"
                                <?php echo ($supplier['id'] == $supplier_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary"><?php echo $is_edit ? 'Perbarui Obat' : 'Tambah Obat'; ?></button>
                <?php if ($is_edit): ?>
                    <a href="medicine_management.php" class="btn-cancel">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card table-card">
            <h2>Daftar Obat</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Obat</th>
                            <th>Deskripsi</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Kategori</th>
                            <th>Kadaluarsa</th>
                            <th>Supplier</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ambil data obat dari database
                        $sql = "SELECT m.id, m.name, m.description, m.price, m.stock, m.category, m.expiry_date, s.name AS supplier_name
                                FROM medicines m
                                LEFT JOIN suppliers s ON m.supplier_id = s.id
                                ORDER BY m.name ASC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                echo "<td>Rp " . number_format($row['price'], 2, ',', '.') . "</td>";
                                echo "<td>" . htmlspecialchars($row['stock']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['expiry_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['supplier_name'] ?? 'N/A') . "</td>";
                                echo "<td class='actions'>";
                                echo "<a href='medicine_management.php?edit=" . htmlspecialchars($row['id']) . "' class='action-btn edit-btn' title='Edit'><i class='fas fa-edit'></i></a>";
                                echo "<a href='medicine_management.php?delete=" . htmlspecialchars($row['id']) . "' class='action-btn delete-btn' title='Hapus' onclick='return confirm(\"Apakah Anda yakin ingin menghapus obat ini?\");'><i class='fas fa-trash-alt'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>Tidak ada data obat.</td></tr>";
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
