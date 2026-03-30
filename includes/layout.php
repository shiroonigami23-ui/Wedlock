<?php
declare(strict_types=1);

function layout_head(string $title, string $pageId): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script></head>';
    echo '<body data-page="' . e($pageId) . '">';
}

function layout_flash(): void {
    $ok = flash_get('ok');
    $err = flash_get('err');
    if ($ok) echo '<div class="flash ok">' . e($ok) . '</div>';
    if ($err) echo '<div class="flash err">' . e($err) . '</div>';
}

function layout_nav(?array $user): void {
    echo '<header class="topbar"><div class="brand">Wedlock</div><button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">Menu</button><nav id="mainNav">';
    if (!$user) {
        echo '<a href="index.php">Home</a><a href="register.php">Register</a><a href="login.php">Login</a><a href="contact.php">Contact</a>';
    } else {
        echo '<a href="dashboard.php">Dashboard</a><a href="profile.php">Profile</a><a href="packages.php">Packages</a><a href="connections.php">Connections</a><a href="contact.php">Contact</a>';
        if (($user['role'] ?? '') === 'admin') echo '<a href="admin.php">Admin</a>';
        echo '<a href="logout.php">Logout</a>';
    }
    echo '</nav></header>';
    echo '<a class="wa-fab" href="https://wa.me/917847948216?text=Hi%20Wedlock%20Owner,%20I%20need%20support." target="_blank" rel="noreferrer" title="Chat on WhatsApp">WA</a>';
}

function layout_close(): void {
    echo '</body></html>';
}
