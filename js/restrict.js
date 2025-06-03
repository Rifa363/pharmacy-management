/**
 * Fungsi untuk membuka atau menutup sidebar navigasi.
 * Ini akan menggeser sidebar dan konten utama.
 */
function toggleNav() {
    const sidenav = document.getElementById("mySidenav");
    const mainContent = document.getElementById("mainContent");
    const body = document.body; // Mengambil elemen body

    // Memeriksa lebar sidebar saat ini
    if (sidenav.style.width === "250px") {
        // Jika sidebar terbuka, tutup sidebar
        sidenav.style.width = "0";
        mainContent.style.marginLeft = "0"; // Geser konten utama kembali
        body.classList.remove('sidenav-open'); // Hapus kelas dari body
    } else {
        // Jika sidebar tertutup, buka sidebar
        sidenav.style.width = "250px";
        mainContent.style.marginLeft = "250px"; // Geser konten utama
        body.classList.add('sidenav-open'); // Tambahkan kelas ke body
    }
}

// Menutup sidebar jika di-klik di luar area sidebar (opsional)
document.addEventListener('click', function(event) {
    const sidenav = document.getElementById("mySidenav");
    const openBtn = document.querySelector(".openbtn"); // Tombol hamburger

    // Jika sidebar terbuka dan klik bukan pada sidebar atau tombol pembuka
    if (sidenav.style.width === "250px" && !sidenav.contains(event.target) && !openBtn.contains(event.target)) {
        toggleNav(); // Tutup sidebar
    }
});

// Anda bisa menambahkan fungsi pembatasan lain di sini, misalnya:
// - Memeriksa aktivitas pengguna untuk logout otomatis setelah waktu tertentu.
// - Pembatasan akses ke elemen tertentu berdasarkan peran pengguna (meskipun ini lebih baik di sisi server).

// Contoh: Fungsi untuk menampilkan pesan kustom (pengganti alert())
function showCustomMessage(message, type = 'info') {
    // Buat elemen div untuk pesan
    const msgBox = document.createElement('div');
    msgBox.className = `custom-message ${type}`; // Tambahkan kelas untuk styling
    msgBox.textContent = message;

    // Tambahkan ke body atau ke elemen spesifik
    document.body.appendChild(msgBox);

    // Hapus pesan setelah beberapa detik
    setTimeout(() => {
        msgBox.remove();
    }, 3000); // Pesan akan hilang setelah 3 detik
}

// Contoh CSS untuk custom-message (tambahkan di home.css atau file CSS terpisah)
/*
.custom-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    font-size: 16px;
    color: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    opacity: 0;
    animation: fadeInOut 3.5s forwards;
}

.custom-message.info {
    background-color: #2196F3; // Biru
}

.custom-message.success {
    background-color: #4CAF50; // Hijau
}

.custom-message.error {
    background-color: #f44336; // Merah
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    10% { opacity: 1; transform: translateY(0); }
    90% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
*/
