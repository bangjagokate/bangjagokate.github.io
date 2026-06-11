<?php
session_start();
require_once 'config.php';

$message = "";

// PROSES REGISTRASI
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Format data yang akan disimpan di grup Telegram
    $text = "[REG] {$username} | {$password}";
    
    $send = telegram_request('sendMessage', [
        'chat_id' => CHAT_ID,
        'text' => $text
    ]);
    
    if ($send['ok']) {
        $message = "<div class='alert success'>Registrasi Berhasil! Silahkan Login.</div>";
    } else {
        $message = "<div class='alert danger'>Gagal registrasi ke Telegram Cloud.</div>";
    }
}

// PROSES LOGIN (Simulasi pembacaan data dari Telegram)
// Catatan: Bot Telegram tidak bisa membaca pesan lama dengan mudah tanpa webhook/database lokal.
// Sebagai alternatif demonstrasi sinkronisasi, kita asumsikan user terverifikasi jika data dikirim balik,
// Namun idealnya sesi ini menggunakan mekanisme pengecekan updates.
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Mengambil pesan terbaru dari grup untuk mencari kecocokan user
    $updates = telegram_request('getUpdates', ['limit' => 100]);
    $authenticated = false;
    
    if (isset($updates['result'])) {
        foreach (array_reverse($updates['result']) as $u) {
            if (isset($u['message']['text'])) {
                $msg = $u['message']['text'];
                if (strpos($msg, "[REG] {$username} |") === 0) {
                    $parts = explode(" | ", $msg);
                    $hashed_password = trim($parts[1]);
                    
                    if (password_verify($password, $hashed_password)) {
                        $authenticated = true;
                        break;
                    }
                }
            }
        }
    }
    
    if ($authenticated) {
        $_SESSION['user'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "<div class='alert danger'>Username atau Password salah / Tidak ditemukan di Cloud.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BangJago Cloud - Auth</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); width: 350px; }
        h2 { text-align: center; color: #38bdf8; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #475569; background: #334155; color: #fff; box-sizing: border-box;}
        button { width: 100%; padding: 12px; background: #0284c7; border: none; color: white; font-weight: bold; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        button:hover { background: #38bdf8; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; text-align: center;}
        .success { background: #16a34a; }
        .danger { background: #dc2626; }
        .switch { text-align: center; margin-top: 15px; font-size: 14px; color: #94a3b8; }
        .switch a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h2>BangJago Cloud</h2>
    <?= $message; ?>
    
    <div id="login-form">
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Masuk ke Cloud</button>
        </form>
        <div class="switch">Belum punya akun? <a href="#" onclick="toggleForm()">Daftar</a></div>
    </div>

    <div id="register-form" style="display:none;">
        <form method="POST">
            <input type="text" name="username" placeholder="Buat Username" required>
            <input type="password" name="password" placeholder="Buat Password" required>
            <button type="submit" name="register">Registrasi Akun</button>
        </form>
        <div class="switch">Sudah punya akun? <a href="#" onclick="toggleForm()">Login</a></div>
    </div>
</div>

<script>
function toggleForm() {
    var login = document.getElementById('login-form');
    var reg = document.getElementById('register-form');
    if(login.style.display === 'none') {
        login.style.display = 'block';
        reg.style.display = 'none';
    } else {
        login.style.display = 'none';
        reg.style.display = 'block';
    }
}
</script>
</body>
</html>
