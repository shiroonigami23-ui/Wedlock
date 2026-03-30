<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login_or_redirect($user);

$qr = $settings['payment_qr_url'] ?? 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?q=80&w=900&auto=format&fit=crop';

layout_head('Wedlock - Packages', 'packages');
layout_flash();
layout_nav($user);
?>
<main class="app">
  <section class="card">
    <h3>Membership Packages</h3>
    <div id="plansGrid" class="grid"></div>
  </section>
  <section class="card">
    <h3>Payment QR</h3>
    <img class="qr" src="<?= e($qr) ?>" alt="Payment QR">
    <p class="note">After payment, confirm on WhatsApp for fast activation.</p>
    <a class="btn" href="https://wa.me/917847948216?text=Hi%20Wedlock%20Team,%20I%20paid%20for%20a%20package.%20Please%20verify." target="_blank" rel="noreferrer">Confirm on WhatsApp</a>
  </section>
</main>
<?php layout_close(); ?>

