<?php
ob_start();
session_start();

require_once 'public/func/func2.php';

$staffFile = 'databases/staff.json';
$threadFile = 'databases/threads.json';
$chatFile = 'databases/chats.json';

$staffData = readData($staffFile);
$threadData = readData($threadFile);
$chatData = readData($chatFile);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$ip = getUserIP();
$device = getUserDevice();

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: /staff-login');
    ob_end_flush();
    exit();
}

if (isset($_SESSION['username'])) {
    if ($_SESSION['ip'] === $ip && $_SESSION['device'] === $device) {
        echo 'Anda sudah login dari perangkat ini.<br>';
        echo 'Username: ' . $_SESSION['username'] . '<br>';
        echo 'Password: ' . ($_SESSION['password'] ?? 'Tidak tersedia') . '<br>';
        echo 'Role: ' . (isset($_SESSION['roles']) ? implode(', ', $_SESSION['roles']) : 'Tidak tersedia') . '<br>';
        echo '<form method="POST" action=""><button type="submit" name="logout">Logout</button></form>';

        // Tampilkan daftar threads
        echo '<h2>Daftar Threads</h2>';
        echo '<ul>';
        foreach ($threadData as $key => $thread) {
            echo '<li>ID: ' . htmlspecialchars($thread['id']) . ' - Judul: ' . htmlspecialchars($thread['title']) . '</li>';
        }
        echo '</ul>';

        // Form untuk menambahkan kategori #Verified
        echo '<h2>Tambah Kategori #Verified</h2>';
        echo '<form method="POST" action="">
                <label for="verify_thread">Masukkan ID Thread yang akan diverifikasi:</label>
                <input type="text" id="verify_thread" name="verify_thread" required>
                <button type="submit" name="verify_thread_btn">Verifikasi Thread</button>
              </form>';

        // Proses verifikasi thread
        if (isset($_POST['verify_thread_btn'])) {
            $threadId = $_POST['verify_thread'];

            // Cari thread berdasarkan ID
            $foundKey = null;
            foreach ($threadData as $key => $thread) {
                if ($thread['id'] === $threadId) {
                    $foundKey = $key;
                    break;
                }
            }

            if ($foundKey !== null) {
                $roles = isset($_SESSION['roles']) ? implode(', ', $_SESSION['roles']) : 'Unknown';
                
                // Format kategori Verified By Role
                $verifiedInfo = "#Verified By $roles";
                
                // Cek jika kategori sudah ada
                if (!isset($threadData[$foundKey]['category'])) {
                    $threadData[$foundKey]['category'] = $verifiedInfo;
                } elseif (strpos($threadData[$foundKey]['category'], $verifiedInfo) === false) {
                    $threadData[$foundKey]['category'] .= ', ' . $verifiedInfo;
                }

                // Tulis ulang data ke file
                if (writeData($threadFile, $threadData)) {
                    echo 'Kategori ' . htmlspecialchars($verifiedInfo) . ' telah ditambahkan pada thread dengan ID ' . htmlspecialchars($threadId) . '.<br>';
                } else {
                    echo 'Gagal menyimpan data ke file.<br>';
                }
            } else {
                echo 'Thread dengan ID ' . htmlspecialchars($threadId) . ' tidak ditemukan.<br>';
            }
        }

        // Form untuk hapus thread
        echo '<h2>Hapus Thread</h2>';
        echo '<form method="POST" action="">
                <label for="delete_thread">Masukkan ID Thread yang akan dihapus:</label>
                <input type="text" id="delete_thread" name="delete_thread" required>
                <button type="submit" name="delete_thread_btn">Hapus Thread</button>
              </form>';

        // Proses hapus thread
        if (isset($_POST['delete_thread_btn'])) {
            $threadId = $_POST['delete_thread'];

            if (deleteThread($threadData, $chatData, $threadId)) {
                writeData($threadFile, $threadData);
                writeData($chatFile, $chatData);

                echo 'Thread dengan ID ' . htmlspecialchars($threadId) . ' telah dihapus.<br>';
                header('Location: /dash');
                exit();
            } else {
                echo 'Thread dengan ID ' . htmlspecialchars($threadId) . ' tidak ditemukan.<br>';
            }
        }
    } else {
        session_destroy();
        echo 'Login dari perangkat atau IP baru. Silakan login kembali.';
    }
} else {
    $staff = array_filter($staffData, function($user) use ($username, $password) {
        return $user['username'] === $username && $user['password'] === $password;
    });

    if (!empty($staff)) {
        $staff = array_values($staff)[0];
        $_SESSION['username'] = $staff['username'];
        $_SESSION['password'] = $staff['password'];
        $_SESSION['roles'] = $staff['roles'];
        $_SESSION['ip'] = $ip;
        $_SESSION['device'] = $device;

        header('Location: /dash');
        ob_end_flush();
        exit();
    } else {
        echo 'Username atau password salah.';
    }
}

ob_end_flush();
?>