<?php
// PHP built-in server router - serves both API and frontend
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Route /api/* requests to api/index.php
if (strpos($path, '/api') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Try to serve static frontend files
$frontendDir = __DIR__ . '/../frontend/dist';
$filePath = $frontendDir . $path;

// Serve static file if it exists
if ($path !== '/' && file_exists($filePath) && is_file($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($filePath);
    return true;
}

// For all other routes, serve index.html (SPA routing)
$indexFile = $frontendDir . '/index.html';
if (file_exists($indexFile)) {
    header('Content-Type: text/html');
    readfile($indexFile);
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
