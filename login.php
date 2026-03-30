<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if ($user) redirect_to('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($pass, $row['password_hash'])) {
        flash_set('err', 'Invalid login credentials.');
        redirect_to('login.php');
    }
    $_SESSION['uid'] = (int)$row['id'];
    flash_set('ok', 'Welcome back.');
    redirect_to('dashboard.php');
}

layout_head('Wedlock - Login', 'login');
layout_flash();
layout_nav($user);
?>
<section class="auth-grid" style="max-width:780px;margin:16px auto;">
  <article class="card">
    <h2>Sign In</h2>
    <form method="post">
      <label>Email</label><input type="email" name="email" required>
      <label>Password</label><input type="password" name="password" required>
      <button class="btn">Login</button>
    </form>
  </article>
</section>
<?php layout_close(); ?>
