<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login_or_redirect($user);

layout_head('Wedlock - Dashboard', 'dashboard');
layout_flash();
layout_nav($user);
$img = $settings['dashboard_image_url'] ?? 'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?q=80&w=1500&auto=format&fit=crop';
?>
<main class="app">
  <section class="card">
    <h3>Hello, <?= e((string)$user['full_name']) ?></h3>
    <p>Status: <strong><?= e(strtoupper((string)$user['status'])) ?></strong></p>
    <div id="dashboardStats" class="loading">Loading dashboard stats...</div>
  </section>
  <section class="card">
    <img class="qr" style="max-width:100%;width:100%;" src="<?= e($img) ?>" alt="Dashboard template">
  </section>
  <section class="card">
    <div class="row"><h3>Smart Matches</h3><button class="btn small" id="reloadMatches">Refresh</button></div>
    <div id="matchesSkeleton" class="skeleton-wrap"><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div>
    <div id="matchesGrid" class="grid"></div>
  </section>
</main>
<?php layout_close(); ?>

