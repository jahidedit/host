<?php
$slug = $_GET['slug'] ?? '';
$path = __DIR__ . '/uploads/' . $slug . '.pdf';

if (!file_exists($path)) {
    http_response_code(404);
    die('<h1>404 Book Not Found</h1>');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
?>