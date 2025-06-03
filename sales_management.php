<?php
session_start(); // Memulai sesi PHP

include 'php/db_connection.php';
include 'php/header.php'; // Termasuk header aplikasi dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Pembatasan akses: hanya admin dan staff yang boleh mengakses halaman penjualan
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo "<p style='color: red; text-align: center; margin-top: 50px;'>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
    exit();
}

// --- Fungsionalitas Pencatatan Penjualan (saat form disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'record_sale') {
    $customer_name = htmlspecialchars(trim($_POST['customer_name']));
    $payment_method = htmlspecialchars(trim($_POST['payment_method']));
    $total_amount = floatval($_POST['total_amount']);
    $items_json = $_POST['sales_items_json']; // Ambil data item dalam format JSON

    $sales_items = json_decode($items_json, true); // Dekode JSON menjadi array PHP

    if (empty($customer_name) || empty($payment_method) || !is_array($sales_items) || count($sales_items) === 0) {
        $_SESSION['message'] = "Data penjualan tidak lengkap atau tidak ada item yang dipilih.";
        $_SESSION['message_type'] = "error";
        header("Location: sales_management.php");
        exit();
    }

    $conn->begin_transaction(); // Mulai transaksi database

    try {
        // 1. Masukkan data penjualan ke tabel 'sales'
        $stmt_sale = $conn->prepare("INSERT INTO sales (customer_name, sale_date, total_amount, payment_method, user_id) VALUES (?, NOW(), ?, ?, ?)");
        $stmt_sale->bind_param("sdsi", $customer_name, $total_amount, $payment_method, $_SESSION['user_id']); // Asumsi user_id disimpan di sesi
        $stmt_sale->execute();
        $sale_id = $conn->insert_id; // Ambil ID penjualan yang baru saja dibuat
        $stmt_sale->close();

        // 2. Masukkan detail item penjualan ke tabel 'sale_items' dan kurangi stok obat
        $stmt_item = $conn->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
        $stmt_update_stock = $conn->prepare("UPDATE medicines SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($sales_items as $item) {
            $medicine_id = $item['id'];
            $quantity = $item['quantity'];
            $price_at_sale = $item['price']; // Harga per unit saat penjualan

            // Periksa ketersediaan stok sebelum mengurangi
            $check_stock_stmt = $conn->prepare("SELECT stock FROM medicines WHERE id = ?");
            $check_stock_stmt->bind_param("i", $medicine_id);
            $check_stock_stmt->execute();
            $stock_result = $check_stock_stmt->get_result()->fetch_assoc();
            $current_stock = $stock_result['stock'] ?? 0;
            $check_stock_stmt->close();

            if ($current_stock < $quantity) {
                throw new Exception("Stok obat '" . htmlspecialchars($item['name']) . "' tidak mencukupi. Hanya tersedia " . $current_stock . " unit.");
            }

            // Masukkan item penjualan
            $stmt_item->bind_param("iidd", $sale_id, $medicine_id, $quantity, $price_at_sale);
            $stmt_item->execute();

            // Kurangi stok
            $stmt_update_stock->bind_param("iii", $quantity, $medicine_id, $quantity); // quantity ketiga untuk kondisi WHERE stock >= quantity
            $stmt_update_stock->execute();

            if ($stmt_update_stock->affected_rows === 0) {
                 // Ini bisa terjadi jika stok sudah kurang atau ID obat tidak ditemukan
                 throw new Exception("Gagal memperbarui stok untuk obat ID " . $medicine_id . ". Stok mungkin kurang.");
            }
        }
        $stmt_item->close();
        $stmt_update_stock->close();

        $conn->commit(); // Komit transaksi jika semua berhasil
        $_SESSION['message'] = "Penjualan berhasil dicatat!";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaksi jika ada error
        $_SESSION['message'] = "Gagal mencatat penjualan: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: sales_management.php");
    exit();
}

// --- Fungsionalitas Pencarian Obat (untuk JavaScript) ---
// Ini akan dipanggil oleh JavaScript melalui AJAX
if (isset($_GET['action']) && $_GET['action'] == 'search_medicine' && isset($_GET['query'])) {
    header('Content-Type: application/json');
    $query = '%' . htmlspecialchars(trim($_GET['query'])) . '%';
    $stmt = $conn->prepare("SELECT id, name, description, price, stock FROM medicines WHERE name LIKE ? OR description LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    $stmt->close();
    echo json_encode($medicines);
    exit(); // Penting: Hentikan eksekusi PHP setelah mengirim JSON
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Penjualan - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Manajemen Penjualan</h1>
        <p>Catat transaksi penjualan obat-obatan.</p>

        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="sales-form-section">
            <div class="card">
                <h2>Input Penjualan</h2>
                <div class="input-group">
                    <label for="customer_name">Nama Pelanggan:</label>
                    <input type="text" id="customer_name" name="customer_name" placeholder="Opsional">
                </div>
                <div class="input-group">
                    <label for="payment_method">Metode Pembayaran:</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Tunai">Tunai</option>
                        <option value="Debit">Debit</option>
                        <option value="Kredit">Kredit</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>

                <h3>Tambah Obat ke Keranjang</h3>
                <div class="search-medicine-container">
                    <label for="medicine_search">Cari Obat:</label>
                    <input type="text" id="medicine_search" placeholder="Ketik nama atau deskripsi obat...">
                    <div id="search_results" class="search-results" style="display: none;">
                        </div>
                </div>
                <div class="input-group">
                    <label for="quantity">Jumlah (Qty):</label>
                    <input type="number" id="quantity" value="1" min="1">
                </div>
                <button type="button" class="btn-primary" id="add_to_cart_btn">Tambah ke Keranjang</button>
                <div class="alert error" id="add_to_cart_error" style="display: none; margin-top: 15px;"></div>
            </div>

            <div class="card sales-cart-section">
                <h2>Keranjang Penjualan</h2>
                <div class="table-responsive">
                    <table id="sales_cart_items">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                <div class="cart-total">
                    Total: Rp <span id="grand_total">0.00</span>
                </div>
                <form action="sales_management.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mencatat penjualan ini?');">
                    <input type="hidden" name="action" value="record_sale">
                    <input type="hidden" name="customer_name" id="hidden_customer_name">
                    <input type="hidden" name="payment_method" id="hidden_payment_method">
                    <input type="hidden" name="total_amount" id="hidden_total_amount">
                    <input type="hidden" name="sales_items_json" id="hidden_sales_items_json">
                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 20px;">Catat Penjualan</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/restrict.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const medicineSearchInput = document.getElementById('medicine_search');
            const searchResultsDiv = document.getElementById('search_results');
            const addTocartBtn = document.getElementById('add_to_cart_btn');
            const quantityInput = document.getElementById('quantity');
            const salesCartItemsBody = document.querySelector('#sales_cart_items tbody');
            const grandTotalSpan = document.getElementById('grand_total');
            const addToCartErrorDiv = document.getElementById('add_to_cart_error');

            let selectedMedicine = null; // Menyimpan data obat yang dipilih dari hasil pencarian
            let salesCart = []; // Array untuk menyimpan item-item di keranjang penjualan

            // Fungsi untuk mencari obat
            let searchTimeout;
            medicineSearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 2) {
                    searchResultsDiv.style.display = 'none';
                    searchResultsDiv.innerHTML = '';
                    selectedMedicine = null;
                    return;
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    fetch(`sales_management.php?action=search_medicine&query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            searchResultsDiv.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(medicine => {
                                    const div = document.createElement('div');
                                    div.innerHTML = `${htmlspecialchars(medicine.name)} (Stok: ${medicine.stock})<span>Rp ${parseFloat(medicine.price).toLocaleString('id-ID')}</span>`;
                                    div.dataset.id = medicine.id;
                                    div.dataset.name = medicine.name;
                                    div.dataset.price = medicine.price;
                                    div.dataset.stock = medicine.stock;
                                    div.addEventListener('click', function() {
                                        medicineSearchInput.value = this.dataset.name;
                                        selectedMedicine = {
                                            id: this.dataset.id,
                                            name: this.dataset.name,
                                            price: parseFloat(this.dataset.price),
                                            stock: parseInt(this.dataset.stock)
                                        };
                                        searchResultsDiv.style.display = 'none';
                                        addToCartErrorDiv.style.display = 'none'; // Sembunyikan pesan error sebelumnya
                                        quantityInput.value = 1; // Reset quantity
                                        quantityInput.focus();
                                    });
                                    searchResultsDiv.appendChild(div);
                                });
                                searchResultsDiv.style.display = 'block';
                            } else {
                                searchResultsDiv.innerHTML = '<div>Tidak ada obat ditemukan.</div>';
                                searchResultsDiv.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching search results:', error);
                            searchResultsDiv.style.display = 'none';
                        });
                }, 300); // Debounce search input
            });

            // Sembunyikan hasil pencarian jika klik di luar
            document.addEventListener('click', function(event) {
                if (!medicineSearchInput.contains(event.target) && !searchResultsDiv.contains(event.target)) {
                    searchResultsDiv.style.display = 'none';
                }
            });

            // Fungsi untuk menambahkan obat ke keranjang
            addTocartBtn.addEventListener('click', function() {
                if (!selectedMedicine) {
                    addToCartErrorDiv.textContent = "Pilih obat dari daftar pencarian terlebih dahulu.";
                    addToCartErrorDiv.style.display = 'block';
                    return;
                }

                const quantity = parseInt(quantityInput.value);
                if (isNaN(quantity) || quantity <= 0) {
                    addToCartErrorDiv.textContent = "Jumlah harus angka positif.";
                    addToCartErrorDiv.style.display = 'block';
                    return;
                }

                if (quantity > selectedMedicine.stock) {
                    addToCartErrorDiv.textContent = `Stok "${selectedMedicine.name}" tidak mencukupi. Tersedia: ${selectedMedicine.stock}.`;
                    addToCartErrorDiv.style.display = 'block';
                    return;
                }

                addToCartErrorDiv.style.display = 'none'; // Sembunyikan pesan error jika berhasil

                const existingItemIndex = salesCart.findIndex(item => item.id === selectedMedicine.id);

                if (existingItemIndex > -1) {
                    // Jika obat sudah ada di keranjang, update jumlahnya
                    const currentTotalQty = salesCart[existingItemIndex].quantity + quantity;
                    if (currentTotalQty > selectedMedicine.stock) {
                        addToCartErrorDiv.textContent = `Jumlah total "${selectedMedicine.name}" melebihi stok. Tersedia: ${selectedMedicine.stock}.`;
                        addToCartErrorDiv.style.display = 'block';
                        return;
                    }
                    salesCart[existingItemIndex].quantity = currentTotalQty;
                } else {
                    // Tambahkan obat baru ke keranjang
                    salesCart.push({
                        id: selectedMedicine.id,
                        name: selectedMedicine.name,
                        price: selectedMedicine.price,
                        quantity: quantity,
                        stock: selectedMedicine.stock // Simpan stok awal untuk referensi
                    });
                }

                renderCart();
                resetMedicineInput();
            });

            // Fungsi untuk merender ulang isi keranjang
            function renderCart() {
                salesCartItemsBody.innerHTML = '';
                let grandTotal = 0;

                if (salesCart.length === 0) {
                    salesCartItemsBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Keranjang kosong.</td></tr>';
                } else {
                    salesCart.forEach((item, index) => {
                        const subtotal = item.price * item.quantity;
                        grandTotal += subtotal;

                        const row = salesCartItemsBody.insertRow();
                        row.dataset.index = index; // Menyimpan indeks di DOM untuk penghapusan mudah
                        row.innerHTML = `
                            <td>${htmlspecialchars(item.name)}</td>
                            <td>Rp ${parseFloat(item.price).toLocaleString('id-ID')}</td>
                            <td>${item.quantity}</td>
                            <td>Rp ${subtotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td><button type="button" class="remove-item-btn" data-index="${index}">Hapus</button></td>
                        `;
                    });
                }
                grandTotalSpan.textContent = grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Delegasi event untuk tombol hapus item di keranjang
            salesCartItemsBody.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-item-btn')) {
                    const indexToRemove = parseInt(event.target.dataset.index);
                    salesCart.splice(indexToRemove, 1); // Hapus item dari array
                    renderCart(); // Render ulang keranjang
                }
            });

            // Fungsi untuk mereset input pencarian obat setelah ditambahkan
            function resetMedicineInput() {
                medicineSearchInput.value = '';
                quantityInput.value = 1;
                selectedMedicine = null;
                searchResultsDiv.style.display = 'none';
            }

            // Submit form penjualan
            document.querySelector('form').addEventListener('submit', function(event) {
                if (salesCart.length === 0) {
                    addToCartErrorDiv.textContent = "Keranjang penjualan kosong. Tambahkan obat terlebih dahulu.";
                    addToCartErrorDiv.style.display = 'block';
                    event.preventDefault(); // Mencegah form disubmit
                    return false;
                }

                // Mengisi hidden input sebelum submit
                document.getElementById('hidden_customer_name').value = document.getElementById('customer_name').value;
                document.getElementById('hidden_payment_method').value = document.getElementById('payment_method').value;
                document.getElementById('hidden_total_amount').value = parseFloat(grandTotalSpan.textContent.replace(/\./g, '').replace(',', '.')); // Konversi kembali ke float
                document.getElementById('hidden_sales_items_json').value = JSON.stringify(salesCart);

                // Konfirmasi terakhir
                if (!confirm('Apakah Anda yakin ingin mencatat penjualan ini?')) {
                    event.preventDefault();
                    return false;
                }
            });

            // Fungsi helper untuk HTML escaping
            function htmlspecialchars(str) {
                let map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return str.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            renderCart(); // Render keranjang saat halaman pertama kali dimuat
        });
    </script>
</body>
</html>
