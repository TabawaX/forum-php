<?php
$maintenance_mode = false;

if ($maintenance_mode) {
    include 'maintenance.php';
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($path, '/public/') === 0) {
    return false;
}

if ($path == '/' || $path == '/home') {
    include 'home.php';
} elseif ($path == '/request-waifu') {
    include 'room-rwaifu.php';
} elseif ($path == '/report-bug') {
    include 'room-rbug.php';
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Halaman tidak ditemukan.</p>";
}
?>