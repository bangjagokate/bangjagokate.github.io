<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: auth.php"); exit; }
require_once 'config.php';

$upload_status = "";
$max_size = 45 * 1024 * 1024; // 45 Megabytes

if (isset($_FILES['cloud_file'])) {
    $file_name = $_FILES['cloud_file']['name'];
    $file_tmp  = $_FILES['cloud_file']['tmp_name'];
    $file_size = $_FILES['cloud_file']['size'];
    
    if ($file_size > $max_size) {
        // PROSES AUTO SPLIT (JIKA > 45MB)
        $upload_status .= "<p style='color:#eab308;'>File besar terdeteksi ({$file_size} bytes). Membagi menjadi 2 part...</p>";
        
        $handle = fopen($file_tmp, "rb");
        $part_size = ceil($file_size / 2);
        
        for ($i = 1; $i <= 2; $i++) {
            $chunk = fread($handle, $part_size);
            $part_name = $file_name . ".part" . $i;
            $part_path = sys_get_temp_dir() . '/' . $part_name;
            
            file_put_contents($part_path, $chunk);
            
            // Kirim Part ke Telegram
            $cFile = new CURLFile($part_path, mime_content_type($part_path), $part_name);
            $response = telegram_request('sendDocument', [
                'chat_id' => CHAT_ID,
                'document' => $cFile,
                'caption' => "[SPLIT-PART{$i}] User: {$_SESSION['user']} | Asli: {$file_name}"
            ], true);
            
            unlink($part_path); // Hapus temporary part file di server
        }
        fclose($handle);
        $upload_status .= "<p style='color:#22c55e;'>✅ File berhasil di-split dan tersimpan aman di Telegram!</p>";
        
    } else {
        // PROSES UPLOAD NORMAL (JIKA < 45MB)
        $cFile = new CURLFile($file_tmp, $_FILES['cloud_file']['type'], $file_name);
        $response = telegram_request('sendDocument', [
            'chat_id' => CHAT_ID,
            'document' => $cFile,
            'caption' => "[SINGLE] User: {$_SESSION['user']} | Nama: {$file_name}"
        ], true);
        
        if ($response['ok']) {
            $upload_status = "<p style='color:#22c55e;'>✅ File berhasil diunggah ke Telegram Cloud!</p>";
        } else {
            $upload_status = "<p style='color:#ef4444;'>❌ Gagal mengunggah file.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BangJago Cloud - Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #fff; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 15px; margin-bottom: 20px;}
        h1 { color: #38bdf8; margin: 0; }
        .logout { color: #ef4444; text-decoration: none; font-weight: bold; }
        .upload-zone { border: 2px dashed #38bdf8; padding: 40px; text-align: center; border-radius: 8px; background: #1e293b; transition: 0.3s; cursor: pointer; }
        .upload-zone:hover { background: #334155; }
        input[type="file"] { display: none; }
        .btn-submit { background: #22c55e; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>BangJago Cloud Storage</h1>
        <div>
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['user']); ?></strong></span> | 
            <a href="auth.php" class="logout">Keluar</a>
        </div>
    </header>

    <div class="upload-box">
        <h3>Upload File ke Telegram</h3>
        <form method="POST" enctype="multipart/form-data">
            <label class="upload-zone" for="file-field">
                <p>Klik di sini untuk memilih file</p>
                <span style="font-size: 13px; color: #94a3b8;">Sistem otomatis memotong file jika ukuran > 45MB</span>
                <input type="file" id="file-field" name="cloud_file" required onchange="displayFileName()">
                <p id="file-name-display" style="color: #38bdf8; font-weight: bold;"></p>
            </label>
            <br>
            <button type="submit" class="btn-submit">Mulai Upload</button>
        </form>
        <div style="margin-top: 15px;"><?= $upload_status; ?></div>
    </div>
</div>

<script>
function displayFileName() {
    var input = document.getElementById('file-field');
    var output = document.getElementById('file-name-display');
    output.innerText = "File terpilih: " + input.files[0].name;
}
</script>
</body>
</html>
