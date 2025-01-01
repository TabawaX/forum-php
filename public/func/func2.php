<?php
// Fungsi untuk membaca data dari file JSON
function readData($file) {
    $json = file_get_contents($file);
    return json_decode($json, true);
}

// Fungsi untuk menulis data ke file JSON
function writeData($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($file, $json);
}

// Fungsi untuk mendapatkan IP user
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Fungsi untuk mendapatkan device ID (contoh: menggunakan User-Agent)
function getUserDevice() {
    return md5($_SERVER['HTTP_USER_AGENT']);
}

// Fungsi untuk menghapus thread berdasarkan ID
function deleteThread(&$threads, &$chats, $threadId) {
    $key = array_search($threadId, array_column($threads, 'id'));
    if ($key !== false) {
        unset($threads[$key]);
        unset($chats[$threadId]);
        return true;
    }
    return false;
}

// Fungsi untuk menghapus chat berdasarkan thread ID
function deleteChat(&$chats, $threadId) {
    if (isset($chats[$threadId])) {
        unset($chats[$threadId]);
        return true;
    }
    return false;
}
?>