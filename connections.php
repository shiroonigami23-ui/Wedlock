<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login_or_redirect($user);

layout_head('Wedlock - Connections', 'connections');
layout_flash();
layout_nav($user);
?>
<main class="app">
  <section class="card">
    <h3>Connection Requests</h3>
    <div id="requestsBox" class="loading">Loading requests...</div>
  </section>
</main>
<?php layout_close(); ?>

