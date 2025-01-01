<?php
function load_json($file_path) {
    if (file_exists($file_path)) {
        $data = file_get_contents($file_path);
        return json_decode($data, true) ?: [];
    }
    return [];
}
function save_json($file_path, $data) {
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
}


function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) return $diff . ' detik lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    if ($diff < 2419200) return floor($diff / 604800) . ' minggu lalu';

    return date('Y-m-d', $time);
}

function find_thread_and_messages($threads, $chats, $thread_id) {
    foreach ($threads as $t) {
        if ($t['id'] === $thread_id) {
            return [$t, $chats[$thread_id] ?? []];
        }
    }
    return [null, []];
}
?>