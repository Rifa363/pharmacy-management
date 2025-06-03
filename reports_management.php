<?php
session_start(); // Memulai sesi PHP

include 'php/db_connection.php';
include 'php/header.php'; // Termasuk header aplikasi dan tombol logout

// Memeriksa apakah pengguna sudah login. Jika belum, arahkan kembali ke halaman login.
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Pembatasan akses: hanya admin yang boleh mengakses halaman laporan (atau bisa disesuaikan)
// Untuk saat ini, kita bisa biarkan staff juga mengakses agar bisa melihat laporan
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo "<p style='color: red; text-align: center; margin-top: 50px;'>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
    exit();
}

// Inisialisasi variabel filter laporan penjualan
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default: awal bulan ini
$end_date = $_GET['end_date'] ?? date('Y-m-d');     // Default: hari ini

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Laporan - Apotek</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/sidenav.css">
    <link rel="icon" href="images/icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php displayHeader(); ?>
    <?php include 'php/sidenav.php'; ?>

    <div class="main-content" id="mainContent">
        <h1>Laporan Apotek</h1>
        <p>Lihat berbagai laporan untuk memantau operasional apotek Anda.</p>

        <div class="card">
            <h2>Laporan Penjualan</h2>
            <form action="reports_management.php" method="GET" class="filter-form">
                <input type="hidden" name="report_type" value="sales">
                <div class="input-group-inline">
                    <label for="start_date">Dari Tanggal:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="input-group-inline">
                    <label for="end_date">Sampai Tanggal:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <button type="submit" class="btn-primary">Tampilkan Laporan</button>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Penjualan</th>
                            <th>Tanggal Penjualan</th>
                            <th>Nama Pelanggan</th>
                            <th>Total Amount</th>
                            <th>Metode Bayar</th>
                            <th>User (Kasir)</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ambil data penjualan berdasarkan filter tanggal
                        $sql_sales = "SELECT s.id, s.sale_date, s.customer_name, s.total_amount, s.payment_method, u.username AS cashier_name
                                      FROM sales s
                                      LEFT JOIN users u ON s.user_id = u.id
                                      WHERE DATE(s.sale_date) BETWEEN ? AND ?
                                      ORDER BY s.sale_date DESC";
                        $stmt_sales = $conn->prepare($sql_sales);
                        $stmt_sales->bind_param("ss", $start_date, $end_date);
                        $stmt_sales->execute();
                        $result_sales = $stmt_sales->get_result();

                        $grand_total_sales = 0; // Untuk menghitung total penjualan
                        if ($result_sales->num_rows > 0) {
                            while($row_sale = $result_sales->fetch_assoc()) {
                                $grand_total_sales += $row_sale['total_amount'];
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row_sale['id']) . "</td>";
                                echo "<td>" . date('d-m-Y H:i', strtotime($row_sale['sale_date'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row_sale['customer_name'] ?: 'Umum') . "</td>";
                                echo "<td>Rp " . number_format($row_sale['total_amount'], 2, ',', '.') . "</td>";
                                echo "<td>" . htmlspecialchars($row_sale['payment_method']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_sale['cashier_name'] ?: 'N/A') . "</td>";
                                echo "<td class='actions'>";
                                // Tombol detail untuk melihat item penjualan
                                echo "<button type='button' class='action-btn edit-btn view-sale-detail' data-sale-id='" . htmlspecialchars($row_sale['id']) . "' title='Lihat Detail'><i class='fas fa-info-circle'></i></button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>Tidak ada data penjualan dalam periode ini.</td></tr>";
                        }
                        $stmt_sales->close();
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL PENJUALAN:</td>
                            <td colspan="4" style="font-weight: bold;">Rp <?php echo number_format($grand_total_sales, 2, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h2>Laporan Stok Obat Rendah & Hampir Kadaluarsa</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Obat</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Tanggal Kadaluarsa</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Ambil data obat dengan stok rendah (misal < 10) atau mendekati kadaluarsa (misal dalam 30 hari)
                        $low_stock_threshold = 10; // Batas stok rendah
                        $expiry_days_threshold = 90; // Kadaluarsa dalam 90 hari

                        $sql_stock = "SELECT m.id, m.name, m.category, m.stock, m.expiry_date, s.name AS supplier_name
                                      FROM medicines m
                                      LEFT JOIN suppliers s ON m.supplier_id = s.id
                                      WHERE m.stock <= ? OR m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                                      ORDER BY m.expiry_date ASC, m.stock ASC";
                        $stmt_stock = $conn->prepare($sql_stock);
                        $stmt_stock->bind_param("ii", $low_stock_threshold, $expiry_days_threshold);
                        $stmt_stock->execute();
                        $result_stock = $stmt_stock->get_result();

                        if ($result_stock->num_rows > 0) {
                            while($row_stock = $result_stock->fetch_assoc()) {
                                $is_low_stock = $row_stock['stock'] <= $low_stock_threshold;
                                $is_expiring = strtotime($row_stock['expiry_date']) <= strtotime("+$expiry_days_threshold days");
                                $row_class = '';
                                if ($is_expiring && $is_low_stock) {
                                    $row_class = 'highlight-critical'; // Kedua-duanya
                                } elseif ($is_expiring) {
                                    $row_class = 'highlight-expiry'; // Hanya kadaluarsa
                                } elseif ($is_low_stock) {
                                    $row_class = 'highlight-low-stock'; // Hanya stok rendah
                                }


                                echo "<tr class='" . $row_class . "'>";
                                echo "<td>" . htmlspecialchars($row_stock['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_stock['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_stock['category']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_stock['stock']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_stock['expiry_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_stock['supplier_name'] ?: 'N/A') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Tidak ada obat dengan stok rendah atau mendekati kadaluarsa.</td></tr>";
                        }
                        $stmt_stock->close();
                        ?>
                    </tbody>
                </table>
            </div>
            <p style="margin-top: 15px; font-size: 0.9em; color: #555;">
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #ffcccc; border: 1px solid #ff0000; vertical-align: middle; margin-right: 5px;"></span> = Stok rendah (<= <?php echo $low_stock_threshold; ?>)
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #ffffcc; border: 1px solid #ffcc00; vertical-align: middle; margin-left: 15px; margin-right: 5px;"></span> = Mendekati kadaluarsa (dalam <?php echo $expiry_days_threshold; ?> hari)
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #ff9999; border: 1px solid #cc0000; vertical-align: middle; margin-left: 15px; margin-right: 5px;"></span> = Stok rendah DAN mendekati kadaluarsa
            </p>
        </div>

        <div id="saleDetailModal" class="modal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Detail Penjualan <span id="modalSaleId"></span></h2>
                <p>Tanggal: <span id="modalSaleDate"></span></p>
                <p>Pelanggan: <span id="modalCustomerName"></span></p>
                <p>Metode Pembayaran: <span id="modalPaymentMethod"></span></p>
                <h3>Item Penjualan:</h3>
                <div class="table-responsive">
                    <table id="modalSaleItems">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Qty</th>
                                <th>Harga Satuan</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                <div style="text-align: right; margin-top: 15px; font-weight: bold;">
                    Total Penjualan: Rp <span id="modalTotalAmount"></span>
                </div>
            </div>
        </div>

    </div>

    <script src="js/restrict.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Fungsionalitas Modal Detail Penjualan ---
            const saleDetailModal = document.getElementById('saleDetailModal');
            const closeButton = saleDetailModal.querySelector('.close-button');
            const viewSaleDetailButtons = document.querySelectorAll('.view-sale-detail');

            viewSaleDetailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const saleId = this.dataset.saleId;
                    fetch(`reports_management.php?action=get_sale_details&sale_id=${saleId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }
                            document.getElementById('modalSaleId').textContent = data.sale.id;
                            document.getElementById('modalSaleDate').textContent = new Date(data.sale.sale_date).toLocaleString('id-ID', {
                                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                            });
                            document.getElementById('modalCustomerName').textContent = data.sale.customer_name || 'Umum';
                            document.getElementById('modalPaymentMethod').textContent = data.sale.payment_method;
                            document.getElementById('modalTotalAmount').textContent = parseFloat(data.sale.total_amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                            const modalSaleItemsBody = document.querySelector('#modalSaleItems tbody');
                            modalSaleItemsBody.innerHTML = '';
                            data.items.forEach(item => {
                                const row = modalSaleItemsBody.insertRow();
                                row.innerHTML = `
                                    <td>${htmlspecialchars(item.medicine_name)}</td>
                                    <td>${item.quantity}</td>
                                    <td>Rp ${parseFloat(item.price_at_sale).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td>Rp ${(item.quantity * item.price_at_sale).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                `;
                            });
                            saleDetailModal.style.display = 'block';
                        })
                        .catch(error => console.error('Error fetching sale details:', error));
                });
            });

            closeButton.addEventListener('click', function() {
                saleDetailModal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == saleDetailModal) {
                    saleDetailModal.style.display = 'none';
                }
            });

            // Helper untuk HTML escaping
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
        });
    </script>
</body>
</html>

<?php
// --- Fungsionalitas AJAX untuk Mendapatkan Detail Penjualan (dipanggil oleh JS) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_sale_details' && isset($_GET['sale_id'])) {
    header('Content-Type: application/json'); // Penting: memberitahu browser bahwa ini adalah JSON
    $sale_id = htmlspecialchars($_GET['sale_id']);

    $response = ['error' => null, 'sale' => null, 'items' => []];

    // Ambil data penjualan utama
    $stmt_sale_main = $conn->prepare("SELECT id, sale_date, customer_name, total_amount, payment_method, user_id FROM sales WHERE id = ?");
    $stmt_sale_main->bind_param("i", $sale_id);
    $stmt_sale_main->execute();
    $result_sale_main = $stmt_sale_main->get_result();

    if ($result_sale_main->num_rows === 1) {
        $response['sale'] = $result_sale_main->fetch_assoc();

        // Ambil item-item penjualan
        $stmt_items = $conn->prepare("SELECT si.quantity, si.price_at_sale, m.name AS medicine_name
                                      FROM sale_items si
                                      JOIN medicines m ON si.medicine_id = m.id
                                      WHERE si.sale_id = ?");
        $stmt_items->bind_param("i", $sale_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($item_row = $result_items->fetch_assoc()) {
            $response['items'][] = $item_row;
        }
        $stmt_items->close();

    } else {
        $response['error'] = "Detail penjualan tidak ditemukan.";
    }
    $stmt_sale_main->close();

    echo json_encode($response);
    exit(); // Penting: Hentikan eksekusi PHP setelah mengirim JSON
}
?>
