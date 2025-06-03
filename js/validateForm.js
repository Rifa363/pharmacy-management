/**
 * Fungsi ini melakukan validasi dasar pada form login.
 * Dipanggil saat form disubmit (melalui atribut onsubmit di index.html).
 * @returns {boolean} Mengembalikan true jika form valid, false jika tidak.
 */
function validateLoginForm() {
    // Mendapatkan nilai dari input username dan password
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    // Mendapatkan elemen untuk menampilkan pesan error
    const loginError = document.getElementById('loginError');

    // Menghapus pesan error sebelumnya
    loginError.textContent = '';

    // Memeriksa apakah username kosong
    if (username.trim() === '') {
        loginError.textContent = 'Username tidak boleh kosong!';
        return false; // Mencegah submit form
    }

    // Memeriksa apakah password kosong
    if (password.trim() === '') {
        loginError.textContent = 'Password tidak boleh kosong!';
        return false; // Mencegah submit form
    }

    // Jika kedua input tidak kosong, form dianggap valid secara klien-side
    // Penting: Validasi sisi server (di PHP) tetap HARUS dilakukan untuk keamanan!
    return true; // Izinkan submit form
}
