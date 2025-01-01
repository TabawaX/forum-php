$temp_dir = realpath(__DIR__ . '/..') . '/online';

if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/online/';

$files = glob($temp_dir . '/*');
foreach ($files as $file) {
    if (is_file($file) && time() - filemtime($file) > 1800 && strpos(basename($file), 'permanent_') === false) {
        unlink($file);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Gagal mengunggah file.']);
        exit;
    }

    $file_buffer = file_get_contents($file['tmp_name'], false, null, 0, 12);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $file_buffer);
    finfo_close($finfo);

    $ext_map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt'
    ];
    $file_ext = isset($ext_map[$mime_type]) ? $ext_map[$mime_type] : 'unknown';

    $is_permanent = isset($_SERVER['HTTP_PERMANENT']) && strtolower($_SERVER['HTTP_PERMANENT']) === 'true';

    $id = uniqid();
    $new_filename = ($is_permanent ? "permanent_$id" : "upload_$id") . '.' . $file_ext;
    $destination = $temp_dir . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $file_url = $base_url . $new_filename;
        echo json_encode([
            'success' => true,
            'file_name' => $new_filename,
            'file_extension' => $file_ext,
            'mime_type' => $mime_type,
            'temp_path' => $destination,
            'file_url' => $file_url,
            'permanent' => $is_permanent
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal memindahkan file ke direktori sementara.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak didukung atau tidak ada file yang diunggah.']);
}