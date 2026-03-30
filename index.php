<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if ($user) redirect_to('dashboard.php');

layout_head('Wedlock - Home', 'home');
layout_flash();
layout_nav($user);
$landing = $settings['landing_image_url'] ?? 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1800&auto=format&fit=crop';
?>
<main class="landing" style="background-image:url('<?= e($landing) ?>')">
  <div class="overlay">
    <h1>Find your forever partner</h1>
    <p>Modern matrimony with smart matching, admin moderation, premium plans, and direct owner support.</p>
    <div class="tab-strip">
      <a class="active" href="register.php">REGISTER</a>
      <a href="login.php">SIGN IN</a>
    </div>
    <form class="hero-search" action="register.php" method="get">
      <select><option>Looking For</option><option>Female</option><option>Male</option></select>
      <input placeholder="Min Age" type="number">
      <input placeholder="Max Age" type="number">
      <button type="submit">Search Partner</button>
    </form>
    <div class="row">
      <a class="btn" href="register.php">Create Profile</a>
      <a class="btn ghost" href="login.php">Sign In</a>
    </div>
  </div>
</main>
<section class="card" style="max-width:980px;margin:16px auto;">
  <h3>How It Works</h3>
  <div class="grid">
    <article class="match"><h4>1. Register</h4><p class="muted">Create your account and submit profile details.</p></article>
    <article class="match"><h4>2. Admin Approval</h4><p class="muted">Owner/admin reviews profile for quality and safety.</p></article>
    <article class="match"><h4>3. Smart Matching</h4><p class="muted">Unsupervised profile clustering suggests better matches.</p></article>
  </div>
</section>
<?php layout_close(); ?>
