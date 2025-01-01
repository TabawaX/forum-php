<?php
session_start();
$threads_file = 'databases/threads.json';

// Fungsi untuk membersihkan input
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk menghapus gambar terlama jika melebihi batas
function remove_oldest_images($dir, $limit = 20) {
    $files = array_diff(scandir($dir), array('.', '..'));
    if (count($files) > $limit) {
        $files = array_map(function($file) use ($dir) {
            return $dir . '/' . $file;
        }, $files);
        array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_ASC, $files);
        while (count($files) > $limit) {
            $oldest_file = array_shift($files);
            unlink($oldest_file);
        }
    }
}

// Daftar kategori
$categories = [
    '#error' => 'Error',
    '#request-waifu' => 'Request Waifu',
    '#ask' => 'Ask',
    '#support' => 'Support',
    '#discussion' => 'Discussion',
    '#tutorials' => 'Tutorials',
    '#showcase' => 'Showcase',
    '#news' => 'News',
    '#off-topic' => 'Off-topic',
    '#feedback' => 'Feedback',
    '#announcements' => 'Announcements',
    '#req-fitur' => 'Req Fitur',
    '#fun' => 'Fun'
];

// Pastikan file JSON tersedia dan terbaca
$threads = file_exists($threads_file) ? json_decode(file_get_contents($threads_file), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['thread_title'], $_POST['thread_description'], $_POST['thread_category'])) {
        $title = clean_input($_POST['thread_title']);
        $description = clean_input($_POST['thread_description']);
        $category = clean_input($_POST['thread_category']);
        
        // Proses unggahan file
        if (isset($_FILES['thread_image']) && $_FILES['thread_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['thread_image']['tmp_name'];
            $fileName = $_FILES['thread_image']['name'];
            $fileSize = $_FILES['thread_image']['size'];
            $fileType = $_FILES['thread_image']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Tentukan direktori penyimpanan
            $uploadFileDir = './uploaded_images/';
            $dest_path = $uploadFileDir . $fileName;

            // Pindahkan file ke direktori penyimpanan
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedImagePath = $dest_path;
                remove_oldest_images($uploadFileDir); // Hapus gambar terlama jika melebihi batas
            } else {
                $uploadedImagePath = '';
            }
        } else {
            $uploadedImagePath = '';
        }

        $new_thread = [
            'id' => uniqid(),
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'timestamp' => date('Y-m-d H:i'),
            'image' => $uploadedImagePath
        ];

        $threads[] = $new_thread;
        file_put_contents($threads_file, json_encode($threads, JSON_PRETTY_PRINT));
        header('Location: thread.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Thread Baru</title>
    <link rel="stylesheet" href="/public/css/postings.css">
</head>
<body>
    <h1>Buat Thread Baru</h1>
    <section class="form-container">
        <a href="/forum" class="return-btn">Kembali</a>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" id="selectedCategories" name="thread_category" value="">
            <div id="selectedCategoriesBox"></div>

            <input type="text" name="thread_title" placeholder="Judul thread..." required>
            <textarea name="thread_description" placeholder="Deskripsi thread..." required></textarea>
            <div class="upload-box">
                <input type="file" name="thread_image" accept="image/*" class="upload-button">
            </div>
            <button type="submit">Buat Thread</button>
            <button type="button" onclick="openPopup()">Ketik untuk melihat list kategori</button>

            <!-- Pop-up untuk memilih kategori -->
            <div id="categoryPopup" class="popup">
                <div class="popup-content">
                    <span onclick="closePopup()" style="float:right;cursor:pointer;">&times;</span>
                    <h2>Pilih Kategori</h2>
                    <ul>
                        <?php foreach ($categories as $key => $value): ?>
                            <li onclick="pickCategory('<?php echo $key; ?>')"><?php echo $value; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" onclick="confirmCategory()">Konfirmasi</button>
                </div>
            </div>
        </form>
    </section>

    <script>
let selectedCategories = [];

function openPopup() {
    document.getElementById('categoryPopup').style.display = 'block';
}

function closePopup() {
    document.getElementById('categoryPopup').style.display = 'none';
}

function pickCategory(category) {
    if (selectedCategories.includes(category)) {
        // Jika kategori sudah dipilih, maka hapus dari array
        const index = selectedCategories.indexOf(category);
        if (index > -1) {
            selectedCategories.splice(index, 1);
        }
    } else if (selectedCategories.length < 4) {
        // Jika kategori belum dipilih dan kurang dari 4, tambahkan ke array
        selectedCategories.push(category);
    } else {
        alert('Anda hanya dapat memilih maksimal 4 kategori.');
    }
    displaySelectedCategories();
    updateCategoryColors();
}

function confirmCategory() {
    document.getElementById('selectedCategories').value = selectedCategories.join(', ');
    closePopup();
}

function displaySelectedCategories() {
    const selectedCategoriesBox = document.getElementById('selectedCategoriesBox');
    selectedCategoriesBox.innerHTML = 'Kategori dipilih: ' + selectedCategories.join(', ');
}

function updateCategoryColors() {
    const categoryItems = document.querySelectorAll('#categoryPopup li');
    categoryItems.forEach(item => {
        const category = item.getAttribute('onclick').match(/'([^']+)'/)[1];
        if (selectedCategories.includes(category)) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}
    </script>
</body>
</html>