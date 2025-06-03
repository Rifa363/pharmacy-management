document.addEventListener('DOMContentLoaded', function() {
    // Mendapatkan elemen form login
    const loginForm = document.querySelector('.login-container form');
    // Mendapatkan elemen input username dan password
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    // Mendapatkan elemen untuk menampilkan pesan error
    const loginError = document.getElementById('loginError');

    // Menambahkan event listener untuk submit form
    loginForm.addEventListener('submit', function(event) {
        // Validasi dasar sisi klien
        if (usernameInput.value.trim() === '' || passwordInput.value.trim() === '') {
            loginError.textContent = 'Username dan password tidak boleh kosong!';
            event.preventDefault(); // Mencegah form disubmit jika ada error
        } else {
            loginError.textContent = ''; // Hapus pesan error jika validasi berhasil
        }
    });

    // Anda bisa menambahkan efek interaktif lain di sini,
    // seperti placeholder yang bergerak atau validasi real-time saat mengetik.
});
