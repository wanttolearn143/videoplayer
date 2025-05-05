<?php
declare(strict_types=1);

$targetUrl = $_GET['url'] ?? '';

if (!$targetUrl) {
    http_response_code(400);
    echo "Error: No URL provided.";
    exit;
}

// Common CORS and caching headers
header("Cache-Control: public, max-age=2592000");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Range");
header("Referrer-Policy: no-referrer");

// Determine content type
$contentType = 'video/mp4';
if (str_contains($targetUrl, '.txt')) {
    $contentType = 'application/vnd.apple.mpegurl';
} elseif (str_contains($targetUrl, '.a1')) {
    $contentType = 'audio/mp4';
}
header("Content-Type: $contentType");

// Prepare headers to send
$requestHeaders = [
    "Accept: */*",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36",
    "Referer: https://play.upns.online/",
    "Origin: https://play.upns.online"
];

// Forward client Range header if present
if (isset($_SERVER['HTTP_RANGE'])) {
    $requestHeaders[] = "Range: " . $_SERVER['HTTP_RANGE'];
}

// Setup cURL to fetch the content
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => $requestHeaders,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HEADER => true,
    CURLOPT_BINARYTRANSFER => true,
    CURLOPT_WRITEFUNCTION => function($ch, $data) {
        echo $data;
        return strlen($data);
    },
]);

// Execute and separate headers
ob_start();
curl_exec($ch);
$rawHeader = ob_get_clean();
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = explode("\r\n", substr($rawHeader, 0, $headerSize));
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

foreach ($headers as $header) {
    if (stripos($header, "Content-Length:") === 0 ||
        stripos($header, "Content-Range:") === 0 ||
        stripos($header, "Accept-Ranges:") === 0) {
        header($header);
    }
}

// Set appropriate status code (206 if partial)
http_response_code($httpCode);

curl_close($ch);
