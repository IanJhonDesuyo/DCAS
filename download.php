<?php
session_start();

require_once 'includes/helpers.php';

$file = basename($_GET['file'] ?? '');
$name = basename($_GET['name'] ?? $file);

if (!$file) {
    http_response_code(400);
    exit('No file specified.');
}

$path = dcas_uploads_dir() . '/' . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;