<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
    $code = isset($_FILES['audio_file']) ? $_FILES['audio_file']['error'] : 'missing';
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Upload error: ' . $code]);
    exit;
}

$file    = $_FILES['audio_file'];
$allowed = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'mp4'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File type not allowed: ' . $ext]);
    exit;
}

if ($file['size'] > 700 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File too large (max 700 MB)']);
    exit;
}

$filename  = preg_replace('/[^a-zA-Z0-9._\-() ]/', '_', basename($file['name']));
$uploadDir = __DIR__ . '/data/';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cannot create upload directory']);
    exit;
}

$dest = $uploadDir . $filename;
if (file_exists($dest)) {
    $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file — check directory permissions']);
    exit;
}

$note = isset($_POST['message']) ? preg_replace('/[\r\n]+/', ' ', substr($_POST['message'], 0, 500)) : '';
$log  = date('Y-m-d H:i:s') . "\t" . $filename . "\t" . $note . "\n";
file_put_contents(__DIR__ . '/upload.log', $log, FILE_APPEND | LOCK_EX);

echo json_encode([
    'status'   => 'ok',
    'message'  => 'Upload successful',
    'filename' => $filename,
]);
