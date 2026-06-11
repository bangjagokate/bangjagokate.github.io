<?php
define('BOT_TOKEN', '8829198130:AAF-srGV8B1AlwjM06Ai1eHokIycCKGxhaE');
define('CHAT_ID', '-1003436527056');

// Fungsi universal untuk mengirim data ke Telegram API
function telegram_request($method, $params = [], $is_file = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($is_file) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
