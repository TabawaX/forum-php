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

// Routing utama
if ($path == '/' || $path == '/home') {
    if (file_exists('home.php')) {
        include 'home.php';
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>File home.php tidak ditemukan.</p>";
    }
} elseif ($path == '/postings') {
    if (file_exists('edit_post.php')) {
        include 'edit_post.php';
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>File edit_post.php tidak ditemukan.</p>";
    }
} elseif ($path == '/dash') {
    if (file_exists('dash.php')) {
        include 'dash.php';
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>File dash.php tidak ditemukan.</p>";
    }
} elseif ($path == '/forum') {
    if (file_exists('thread.php')) {
        include 'thread.php';
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>File thread.php tidak ditemukan.</p>";
    }
} elseif ($path == '/staff-login') {
    if (file_exists('staff.php')) {
        include 'staff.php';
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>File staff.php tidak ditemukan.</p>";
    }
} elseif (strpos($path, '/server') === 0) {
    $api_path = substr($path, strlen('/server'));

    if ($api_path == '/upload') {
        if (file_exists('api/upload.php')) {
            include 'api/upload.php';
        } else {
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1><p>File api/upload.php tidak ditemukan.</p>";
        }
    } elseif ($api_path == '/say') {
        if (file_exists('api/say.php')) {
            include 'api/say.php';
        } else {
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1><p>File api/say.php tidak ditemukan.</p>";
        }
    } else {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>API endpoint tidak ditemukan.</p>";
    }
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Halaman tidak ditemukan.</p>";
}