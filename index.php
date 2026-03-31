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
  <div class="overlay hero-shell">
    <p class="eyebrow">Trusted Matrimony Platform</p>
    <h1>Find a life partner with dignity, trust, and clarity.</h1>
    <p class="hero-copy">Wedlock is built for genuine matrimonial introductions with verified onboarding, thoughtful profile discovery, and premium member support.</p>
    <div class="hero-actions">
      <a class="btn" href="register.php">Create Your Profile</a>
      <a class="btn ghost" href="login.php">Member Login</a>
      <a class="btn ghost" href="packages.php">View Packages</a>
    </div>
    <div class="hero-highlights">
      <article>
        <h3>Verified Onboarding</h3>
        <p>Every profile passes an approval step before going live.</p>
      </article>
      <article>
        <h3>Private & Secure</h3>
        <p>Controlled visibility with secure account-based access.</p>
      </article>
      <article>
        <h3>Direct Support</h3>
        <p>Talk to owner directly on WhatsApp for package confirmation.</p>
      </article>
    </div>
  </div>
</main>
<section class="card landing-strip">
  <div class="landing-strip-head">
    <h2>Start your profile today</h2>
    <p>Join Wedlock and connect with people who are genuinely looking for marriage.</p>
  </div>
  <div class="landing-strip-actions">
    <a class="btn" href="register.php">Register Now</a>
    <a class="btn ghost" href="contact.php">Talk on WhatsApp</a>
  </div>
</section>
<?php layout_close(); ?>
