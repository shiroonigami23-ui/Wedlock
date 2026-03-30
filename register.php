<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if ($user) redirect_to('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    if ($name === '' || $email === '' || strlen($pass) < 6) {
        flash_set('err', 'Please provide valid registration details.');
        redirect_to('register.php');
    }
    try {
        $pdo->prepare("INSERT INTO users(full_name,email,phone,password_hash,role,status) VALUES(?,?,?,?, 'user','pending')")
            ->execute([$name,$email,$phone,password_hash($pass,PASSWORD_BCRYPT)]);
        $uid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO profiles(user_id,gender,seeking_gender,bio,about_me) VALUES(?, 'Other','Other','','')")->execute([$uid]);
        $_SESSION['uid'] = $uid;
        flash_set('ok', 'Registration done. Profile is pending admin approval.');
        redirect_to('dashboard.php');
    } catch (Throwable) {
        flash_set('err', 'Email already exists.');
        redirect_to('register.php');
    }
}

layout_head('Wedlock - Register', 'register');
layout_flash();
layout_nav($user);
$img = $settings['register_image_url'] ?? 'https://images.unsplash.com/photo-1529636798458-92182e662485?q=80&w=1500&auto=format&fit=crop';
?>
<section class="auth-grid">
  <article class="card">
    <h2>Create Account</h2>
    <form method="post">
      <label>Full Name</label><input name="full_name" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Phone</label><input name="phone">
      <label>Password</label><input type="password" name="password" minlength="6" required>
      <button class="btn">Register</button>
    </form>
  </article>
  <article class="card">
    <img class="qr" style="max-width:100%;width:100%;height:auto;" src="<?= e($img) ?>" alt="Register template">
  </article>
</section>
<?php layout_close(); ?>
