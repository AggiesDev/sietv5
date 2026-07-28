<?php
function detect_site_url(): string
{
    $configured = getenv('SIET_SITE_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost/sietv5';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(__DIR__ . '/..');
    $basePath = '';
    if ($documentRoot && $projectRoot && str_starts_with($projectRoot, $documentRoot)) {
        $basePath = trim(str_replace(DIRECTORY_SEPARATOR, '/', substr($projectRoot, strlen($documentRoot))), '/');
    }
    return rtrim($scheme . '://' . $host . ($basePath ? '/' . $basePath : ''), '/');
}

define('SITE_NAME', 'SIET');
define('SITE_TAGLINE', 'Society of Instrument Engineers & Technology');
define('SITE_URL', detect_site_url());
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');
define('PRIMARY_COLOR', '#1e3a5f');
define('ACCENT_COLOR', '#d4a017');
?>
