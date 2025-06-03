<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Apotek - Login</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" href="images/logo.png" type="image/png">
</head>
<body>
    <div class="login-container">
        <img src="images/logo.png" alt="Pharmacy Icon" class="login-icon">
        <h2>Selamat Datang di Sistem Apotek</h2>
        <p>Silakan masuk untuk mengelola apotek Anda.</p>
        <form action="php/login_process.php" method="POST" onsubmit="return validateLoginForm()">
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="error-message" id="loginError">
            <?php
            //Menampilkan pesan error jika ada dari proses login (misal: dari redirect)
            if (isset($_GET['error'])) {
                echo htmlspecialchars($_GET['error']);
            }
            ?>
        </div>
    </div>

    <script src="js/index.js"></script>
    <script src="js/validateForm.js"></script>
</body>
</html>
