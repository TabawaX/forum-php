<?php
session_start();
ob_start(); // Mulai buffer output

// Fungsi untuk membaca data staff dari file JSON
function readStaffData($file) {
    $json = file_get_contents($file);
    return json_decode($json, true);
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

// Fungsi untuk mendapatkan device ID (sebagai contoh, menggunakan User-Agent)
function getUserDevice() {
    return md5($_SERVER['HTTP_USER_AGENT']);
}

$staffFile = 'databases/staff.json';
$staffData = readStaffData($staffFile);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$ip = getUserIP();
$device = getUserDevice();

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: /staff-login');
    ob_end_flush(); // Akhiri buffer output sebelum keluar
    exit();
}

if (isset($_SESSION['username'])) {
    if ($_SESSION['ip'] === $ip && $_SESSION['device'] === $device) {
        echo 'Anda sudah login dari perangkat ini.<br>';
        echo 'Username: ' . $_SESSION['username'] . '<br>';
        echo 'Password: ' . ($_SESSION['password'] ?? 'Tidak tersedia') . '<br>';
        echo 'Role: ' . (isset($_SESSION['roles']) ? implode(', ', $_SESSION['roles']) : 'Tidak tersedia') . '<br>';
        echo '<form method="POST" action=""><button type="submit" name="logout">Logout</button></form>';
    } else {
        session_destroy();
        echo 'Login dari perangkat atau IP baru. Silakan login kembali.';
    }
} else {
    $staff = array_filter($staffData, function($user) use ($username, $password) {
        return $user['username'] === $username && $user['password'] === $password;
    });

    if (!empty($staff)) {
        $staff = array_values($staff)[0]; // Ambil data user yang ditemukan
        $_SESSION['username'] = $staff['username'];
        $_SESSION['password'] = $staff['password'];
        $_SESSION['roles'] = $staff['roles'];
        $_SESSION['ip'] = $ip;
        $_SESSION['device'] = $device;

        // Debugging session
        var_dump($_SESSION);

        echo 'Login berhasil. Selamat datang, ' . $staff['username'] . '<br>';
        echo 'Username: ' . $staff['username'] . '<br>';
        echo 'Password: ' . $staff['password'] . '<br>';
        echo 'Role: ' . implode(', ', $staff['roles']) . '<br>';
        echo '<form method="POST" action=""><button type="submit" name="logout">Logout</button></form>';

        // Redirect to /staff-login to avoid form resubmission
        header('Location: /dash');
        ob_end_flush(); // Akhiri buffer output sebelum keluar
        exit();
    } else {
        echo 'Username atau password salah.';
    }
}

ob_end_flush(); // Akhiri buffer output
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff</title>
    <link rel="stylesheet" href="/public/css/staff.css">
</head>
<body>
    <?php if (!isset($_SESSION['username'])): ?>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <?php endif; ?>
</body>
</html>