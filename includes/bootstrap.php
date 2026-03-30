<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/core.php';

$pdo = null;
$user = null;
$settings = [];
$bootError = null;

try {
    $pdo = pdo_conn();
    ensure_schema($pdo);
    $user = current_user($pdo);
    $settingsRows = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
    foreach ($settingsRows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Throwable $e) {
    $bootError = 'Database init failed';
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    @file_put_contents(
        $logDir . '/boot.log',
        date('c') . ' ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
}

function redirect_to(string $path): void {
    header("Location: {$path}");
    exit;
}

function flash_set(string $kind, string $message): void {
    $_SESSION["flash_{$kind}"] = $message;
}

function flash_get(string $kind): ?string {
    $k = "flash_{$kind}";
    if (!isset($_SESSION[$k])) return null;
    $v = (string)$_SESSION[$k];
    unset($_SESSION[$k]);
    return $v;
}

function require_login_or_redirect(?array $user): void {
    if (!$user) redirect_to('login.php');
}

function require_admin_or_redirect(?array $user): void {
    if (!$user || ($user['role'] ?? '') !== 'admin') redirect_to('dashboard.php');
}
