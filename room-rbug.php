<?php
session_start();
ob_start();

$file_path = 'databases/chats.json';
$chats = json_decode(file_get_contents($file_path), true);
if (!$chats) {
    $chats = [];
}

$staff_file_path = 'databases/staff.json';
$staff_data = json_decode(file_get_contents($staff_file_path), true);
if (!$staff_data) {
    $staff_data = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $username = isset($_SESSION['staff_username']) ? $_SESSION['staff_username'] : htmlspecialchars($_POST['username']);
    $message = htmlspecialchars($_POST['message']);
    $role = 'member';

    if (isset($_SESSION['staff_username'])) {
        foreach ($staff_data as $staff) {
            if ($staff['username'] == $username) {
                $role = implode(", ", $staff['roles']);
                break;
            }
        }
    }

    $room = 'report-waifu';  // Menggunakan key report-waifu untuk menyimpan laporan bug
    if (!isset($chats[$room])) {
        $chats[$room] = [];
    }

    $message_id = uniqid();  // Menggunakan ID unik untuk setiap pesan
    $timestamp = date('H:i');
    $chats[$room][] = ['id' => $message_id, 'username' => $username, 'message' => $message, 'timestamp' => $timestamp, 'role' => $role, 'approved' => false];
    file_put_contents($file_path, json_encode($chats, JSON_PRETTY_PRINT));

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['staff_username'], $_POST['staff_password'])) {
    $staff_username = htmlspecialchars($_POST['staff_username']);
    $staff_password = htmlspecialchars($_POST['staff_password']);

    foreach ($staff_data as $staff) {
        if ($staff['username'] == $staff_username && $staff['password'] == $staff_password) {
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_roles'] = $staff['roles'];
            $_SESSION['staff_username'] = $staff['username'];
            echo "<script>alert('Login berhasil!');</script>";
            break;
        }
    }
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$is_staff_logged_in = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'];

function has_role($role) {
    return isset($_SESSION['staff_roles']) && in_array($role, $_SESSION['staff_roles']);
}

if (isset($_POST['approve_message'])) {
    if (isset($_POST['message_id'])) {
        $message_id = $_POST['message_id'];

        // Semua staff yang login dapat approve
        if ($is_staff_logged_in) {
            foreach ($chats['report-waifu'] as &$report) {
                if ($report['id'] == $message_id) {
                    $report['approved'] = true;
                    break;
                }
            }
            file_put_contents($file_path, json_encode($chats, JSON_PRETTY_PRINT));
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Bug Room</title>
    <link rel="stylesheet" href="/public/css/room.css">
</head>
<body>
    <div class="container">
        <h1>Report Bug Room</h1>
        <p>Di sini Anda dapat melaporkan bug yang Anda temui.</p>

        <div class="menu-burger">
            <span class="burger-icon" onclick="toggleMenu()">&#9776;</span>
        </div>

        <div id="menu" class="menu">
            <?php if (!$is_staff_logged_in): ?>
                <form method="POST" action="room-rbug.php">
                    <input type="text" name="staff_username" placeholder="Username Staff" required />
                    <input type="password" name="staff_password" placeholder="Password" required />
                    <button type="submit">Login Staff</button>
                </form>
            <?php else: ?>
                <p>Welcome, <?= $_SESSION['staff_username']; ?> (Roles: <?= implode(", ", $_SESSION['staff_roles']); ?>)</p>
                <form method="POST" action="room-rbug.php">
                    <button type="submit" name="logout">Logout</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="chat-box">
            <?php
            if (isset($chats['report-waifu'])):
                foreach ($chats['report-waifu'] as $report):
            ?>
            <div class="message">
                <span class="role"><?= isset($report['role']) ? '[' . $report['role'] . ']' : '[member]'; ?></span>
                <strong><?= $report['username']; ?>:</strong> <?= $report['message']; ?>
                <span class="timestamp"><?= $report['timestamp']; ?></span>
                <?php if (isset($report['approved']) && $report['approved']): ?>
                    <span style="color: green;">[Approved]</span>
                <?php endif; ?>

                <!-- Tampilkan tombol Approve hanya jika staff login -->
                <?php 
                    if ($is_staff_logged_in) {
                        if (!isset($report['approved']) || !$report['approved']):
                ?>
                <form method="POST" action="room-rbug.php" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $report['id']; ?>" />
                    <button type="submit" name="approve_message">Approve</button>
                </form>
                <?php 
                        endif;
                    }
                ?>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <div class="message">Belum ada laporan bug di ruang ini.</div>
            <?php endif; ?>
        </div>

        <form class="chat-form" method="POST" action="room-rbug.php">
            <?php if ($is_staff_logged_in): ?>
                <input type="text" name="username" value="<?= $_SESSION['staff_username']; ?>" disabled />
            <?php else: ?>
                <input type="text" name="username" placeholder="Masukkan username Anda..." required />
            <?php endif; ?>
            <input type="text" name="message" placeholder="Ketik pesan Anda..." required />
            <button type="submit">Kirim</button>
        </form>

        <script>
            window.onload = function() {
                var chatBox = document.querySelector('.chat-box');
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            function toggleMenu() {
                var menu = document.getElementById('menu');
                if (menu.style.display === "none" || menu.style.display === "") {
                    menu.style.display = "block";
                } else {
                    menu.style.display = "none";
                }
            }
        </script>
    </div>
</body>
</html>

<?php
ob_end_flush();
?>