-- Skrip SQL untuk membuat database dan tabel Sistem Informasi Apotek

-- 1. Membuat database jika belum ada
CREATE DATABASE IF NOT EXISTS pharmacy_management;

-- Menggunakan database yang baru dibuat atau yang sudah ada
USE pharmacy_management;

-- Menghapus tabel jika sudah ada untuk memastikan proses import yang bersih
-- Urutan penghapusan harus terbalik dari urutan pembuatan karena dependensi foreign key
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;

-- 2. Tabel `users` untuk menyimpan informasi pengguna (admin/staff)
-- Dibuat pertama karena tidak memiliki foreign key
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Penting: Simpan password yang sudah di-hash!
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff', -- Peran pengguna
    profile_picture VARCHAR(255) DEFAULT 'default_profile.jpg', -- KOLOM BARU UNTUK GAMBAR PROFIL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Waktu pembuatan akun
);

-- Memasukkan data pengguna admin default
-- Password 'admin123' di-hash menggunakan PASSWORD_HASH('admin123', PASSWORD_DEFAULT)
-- GANTI HASH INI DENGAN HASH YANG ANDA HASILKAN DARI PHP (misal: echo password_hash('admin123', PASSWORD_DEFAULT);)
INSERT INTO users (username, password, role, profile_picture) VALUES
('admin', '$2y$10$w/yGk.L.z.Fz.Hq0R.fXy.eZ.W/yS.J.S.d.Q.V.a.N.e.X.r.Y.Z.0.1.2.3.4.5.6.7.8.9.a.b.c.d.e', 'admin', 'prof.jpg'), -- Contoh hash untuk 'admin123'
('staff1', '$2y$10$w/yGk.L.z.Fz.Hq0R.fXy.eZ.W/yS.J.S.d.Q.V.a.N.e.X.r.Y.Z.0.1.2.3.4.5.6.7.8.9.a.b.c.d.e', 'staff', 'default_profile.jpg'); -- Contoh hash untuk 'admin123'
-- Pastikan hash di atas adalah hash yang valid untuk 'admin123' yang Anda hasilkan sendiri.

-- 3. Tabel `suppliers` untuk menyimpan informasi pemasok obat
-- Dibuat kedua karena tidak memiliki foreign key
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contoh data dummy untuk suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('PT. Farma Jaya', 'Budi Santoso', '08123456789', 'budi@farmajaya.com', 'Jl. Merdeka No. 10, Jakarta'),
('CV. Obat Sehat', 'Siti Aminah', '08765432100', 'siti@obatsehat.com', 'Jl. Pahlawan No. 5, Surabaya');

-- 4. Tabel `medicines` untuk menyimpan data obat-obatan
-- Dibuat setelah `suppliers` karena memiliki foreign key ke `suppliers`
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL, -- Harga jual obat
    stock INT NOT NULL DEFAULT 0, -- Jumlah stok obat
    category VARCHAR(50), -- Kategori obat (misal: Analgesik, Antibiotik)
    expiry_date DATE, -- Tanggal kadaluarsa
    supplier_id INT, -- ID supplier (foreign key ke tabel suppliers)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL -- Jika supplier dihapus, set NULL
);

-- Contoh data dummy untuk medicines (membutuhkan supplier_id yang valid)
INSERT INTO medicines (name, description, price, stock, category, expiry_date, supplier_id) VALUES
('Paracetamol 500mg', 'Obat pereda nyeri dan demam', 1500.00, 200, 'Analgesik', '2026-12-31', 1),
('Amoxicillin 500mg', 'Antibiotik untuk infeksi bakteri', 5000.00, 100, 'Antibiotik', '2025-10-15', 1),
('Vitamin C 100mg', 'Suplemen vitamin C', 1000.00, 300, 'Vitamin', '2027-06-01', 2),
('Obat Batuk Sirup', 'Sirup pereda batuk', 8500.00, 75, 'Obat Batuk', '2026-03-20', 2);

-- 5. Tabel `sales` untuk mencatat setiap transaksi penjualan
-- Dibuat setelah `users` karena memiliki foreign key ke `users`
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) DEFAULT 'Umum', -- KOLOM BARU! Nama pelanggan (opsional)
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Tanggal dan waktu penjualan
    total_amount DECIMAL(10, 2) NOT NULL, -- Total jumlah penjualan
    payment_method VARCHAR(50) NOT NULL, -- Metode pembayaran
    user_id INT NOT NULL, -- ID pengguna yang melakukan penjualan
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- Jika pengguna dihapus, hapus penjualan terkait
);

-- 6. Tabel `sale_items` untuk detail item dalam setiap penjualan
-- Dibuat setelah `sales` dan `medicines` karena memiliki foreign key ke keduanya
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL, -- ID penjualan (foreign key ke tabel sales)
    medicine_id INT NOT NULL, -- ID obat yang terjual (foreign key ke tabel medicines)
    quantity INT NOT NULL, -- Jumlah obat yang terjual
    price_at_sale DECIMAL(10, 2) NOT NULL, -- Harga per unit saat penjualan
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT -- Jangan hapus obat jika masih ada di penjualan
);

-- 7. Tabel `audit_logs` (opsional, untuk melacak aktivitas penting)
-- Dibuat setelah `users` karena memiliki foreign key ke `users`
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL, -- Deskripsi aksi (misal: 'Login', 'Tambah Obat', 'Hapus Penjualan')
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
