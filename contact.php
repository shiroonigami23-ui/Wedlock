<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

layout_head('Wedlock - Contact', 'contact');
layout_flash();
layout_nav($user);
?>
<main class="app">
  <section class="card" style="max-width:860px;">
    <h3>Contact Owner</h3>
    <p>For package confirmation, profile approval help, or direct support, connect on WhatsApp.</p>
    <p><strong>WhatsApp Number:</strong> 7847948216</p>
    <a class="btn" href="https://wa.me/917847948216?text=Hi%20Wedlock%20Owner,%20I%20need%20help%20with%20my%20profile/package." target="_blank" rel="noreferrer">Chat on WhatsApp</a>
  </section>
</main>
<?php layout_close(); ?>

