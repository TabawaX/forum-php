<?php
session_start();
ob_start();

require_once 'public/func/func1.php';

$file_path = 'databases/chats.json';
$threads_file = 'databases/threads.json';

// Load data JSON
$chats = load_json($file_path);
$threads = load_json($threads_file);

$thread_id = $_GET['id'] ?? null;
$debug_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['username'])) {
    $debug_message = process_comment_submission($thread_id, $file_path, $chats);
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$thread = null;
$messages = [];

if ($thread_id !== null) {
    list($thread, $messages) = find_thread_and_messages($threads, $chats, $thread_id);
    if (!$thread) {
        header("Location: /");
        exit;
    }
}

// Filter messages berdasarkan thread_id
$messages = array_filter($chats, function($chat) use ($thread_id) {
    return isset($chat['thread_id']) && $chat['thread_id'] === $thread_id;
});

// Sort threads by timestamp in descending order (newest first)
usort($threads, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Sortir berdasarkan timestamp
usort($messages, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

function process_comment_submission($thread_id, $file_path, &$chats) {
    try {
        $new_comment = [
            'id' => uniqid(),
            'thread_id' => $thread_id,
            'username' => $_POST['username'],
            'message' => $_POST['message'],
            'timestamp' => date('Y-m-d H:i:s'),
            'roles' => isset($_SESSION['roles']) ? $_SESSION['roles'] : [],
        ];

        $chats[] = $new_comment;
        file_put_contents($file_path, json_encode($chats));
        
        // Return success message instead of var_dump
        return "Comment successfully added";
    } catch (Exception $e) {
        error_log("Error in process_comment_submission: " . $e->getMessage());
        return "Error processing comment";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($thread['title'] ?? 'Shije Forum') ?></title>
    <link rel="stylesheet" href="/public/css/thrd.css">
    <style>
        .verified-category {
            color: green;
            font-weight: bold;
        }
        .verified-logo {
            width: 30px;
            height: 30px;
            vertical-align: middle;
            margin-left: 5px;
        }
        .message-header {
            display: flex;
            align-items: center;
        }
        .message-header img {
            margin-right: 10px;
        }
        .debug-message {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<header>
    <h1 class="centered-title"><?= htmlspecialchars($thread['title'] ?? 'Shije Forum') ?></h1>
    <p><?= htmlspecialchars($thread['description'] ?? '') ?></p>
</header>
<section class="navigation">
    <nav>
        <ul class="breadcrumb">
            <li><a href="/">Home</a></li>
            <li><a href="/postings">Buat Postingan</a></li>
        </ul>
    </nav>
</section>

<main>
    <?php if (!empty($debug_message)): ?>
        <div class="debug-message">
            <?= htmlspecialchars($debug_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($thread_id && $thread): ?>
        <!-- Title and Description Section -->
        <section class="thread-info">
            <h2><?= htmlspecialchars($thread['title'] ?? 'No Title') ?></h2>
            <p><?= htmlspecialchars($thread['description'] ?? 'No Description') ?></p>
            <?php if (!empty($thread['category'])): ?>
                <div class="category-label <?= strpos($thread['category'], 'Verified') !== false ? 'verified-category' : '' ?>">
                    <?= htmlspecialchars($thread['category']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($thread['image'])): ?>
                <img src="<?= htmlspecialchars($thread['image']) ?>" alt="Thread Image" class="thread-image">
            <?php endif; ?>
        </section>
        <hr>

        <!-- Comments Section -->
        <section class="messages">
            <h2>Diskusi</h2>
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <article class="message">
                        <div class="message-header">
                            <span><strong><?= htmlspecialchars($message['username']) ?></strong></span>
                            <?php if (!empty($message['roles'])): ?>
                                <span class="role-label">(<?= htmlspecialchars(implode(', ', $message['roles'])) ?>)</span>
                                <img src="/public/images/verifed.png" alt="Verified Logo" class="verified-logo">
                            <?php endif; ?>
                        </div>
                        <p><?= htmlspecialchars($message['message']) ?></p>
                        <time><?= htmlspecialchars($message['timestamp']) ?></time>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Belum ada pesan. Jadilah yang pertama memberikan komentar!</p>
            <?php endif; ?>
        </section>
        <hr>

        <!-- Comment Form Section -->
        <section class="form-container">
            <h2>Tambahkan Komentar</h2>
            <form method="POST">
                <label for="username">Namamu:</label>
                <?php if (isset($_SESSION['username'])): ?>
                    <?php if (in_array('staff', $_SESSION['roles'] ?? [])): ?>
                        <p><strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
                        <input type="hidden" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>">
                    <?php else: ?>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>" required>
                    <?php endif; ?>
                <?php else: ?>
                    <input type="text" id="username" name="username" required>
                <?php endif; ?>
                
                <label for="message">Pesan:</label>
                <textarea id="message" name="message" required></textarea>
                <button type="submit">Kirim Komentar</button>
            </form>
            <a href="/forum" class="back-button">Go Back Bro</a>
        </section>
    <?php else: ?>
        <!-- Thread List Section -->
        <section class="thread-list">
            <h2>Recently Activity</h2>
            <?php if (empty($threads)): ?>
                <p>Belum ada thread. Jadilah yang pertama membuat thread!</p>
            <?php else: ?>
                <ul class="thread-list">
                    <?php foreach ($threads as $t): ?>
                        <li>
                            <div class="thread-header"><?= htmlspecialchars($t['title'] ?? 'Tidak Ada Judul') ?></div>
                            <div class="thread-time"><?= time_ago($t['timestamp'] ?? '') ?></div>
                            <?php if (!empty($t['category'])): ?>
                                <div class="category-label <?= strpos($t['category'], 'Verified') !== false ? 'verified-category' : '' ?>">
                                    <?= htmlspecialchars($t['category']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="thread-content"><?= htmlspecialchars($t['description'] ?? 'Deskripsi Tidak Tersedia') ?></div>
                            <?php if (!empty($t['image'])): ?>
                                <div class="image-list-preview">
                                    <img src="<?= htmlspecialchars($t['image']) ?>" alt="Thread Image Preview" class="blurred-image">
                                </div>
                            <?php endif; ?>
                            <a class="thread-link" href="thread.php?id=<?= htmlspecialchars($t['id']) ?>">Klik untuk melihat</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<script>
    function toggleMenu() {
        const menu = document.getElementById('menu');
        const hamburger = document.getElementById('hamburger');
        const closeArrow = document.getElementById('closeArrow');
        menu.classList.toggle('show');
        hamburger.style.display = hamburger.style.display === 'none' ? 'block' : 'none';
        closeArrow.style.display = closeArrow.style.display === 'none' ? 'block' : 'none';
    }
</script>
</body>
</html>

