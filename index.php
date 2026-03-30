<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/includes/core.php';

$pdo = pdo_conn();
ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = (string)$_POST['action'];
    if ($a === 'register') {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        if ($name === '' || $email === '' || strlen($pass) < 6) {
            $_SESSION['err'] = 'Please provide valid registration details.'; header('Location: index.php'); exit;
        }
        try {
            $pdo->prepare("INSERT INTO users(full_name,email,phone,password_hash,role,status) VALUES(?,?,?,?, 'user','pending')")
                ->execute([$name,$email,$phone,password_hash($pass,PASSWORD_BCRYPT)]);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO profiles(user_id,gender,seeking_gender,bio,about_me) VALUES(?, 'Other','Other','','')")->execute([$uid]);
            $_SESSION['uid'] = $uid;
            $_SESSION['ok'] = 'Registration done. Awaiting admin approval.';
            header('Location: index.php?page=dashboard'); exit;
        } catch (Throwable) {
            $_SESSION['err'] = 'Email already exists.'; header('Location: index.php'); exit;
        }
    }
    if ($a === 'login') {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $s = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $s->execute([$email]); $u = $s->fetch();
        if (!$u || !password_verify($pass, $u['password_hash'])) { $_SESSION['err'] = 'Invalid login.'; header('Location: index.php'); exit; }
        $_SESSION['uid'] = (int)$u['id']; $_SESSION['ok'] = 'Welcome back.';
        header('Location: index.php?page=dashboard'); exit;
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

$user = current_user($pdo);
$page = (string)($_GET['page'] ?? ($user ? 'dashboard' : 'home'));
$setRows = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
$settings = []; foreach ($setRows as $r) $settings[$r['setting_key']] = $r['setting_value'];
$ok = $_SESSION['ok'] ?? null; $err = $_SESSION['err'] ?? null; unset($_SESSION['ok'], $_SESSION['err']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Wedlock - Matrimony Platform</title>
  <link rel="stylesheet" href="assets/style.css">
  <script defer src="assets/app.js"></script>
</head>
<body data-page="<?= e($page) ?>">
<?php if ($ok): ?><div class="flash ok"><?= e((string)$ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= e((string)$err) ?></div><?php endif; ?>

<?php if (!$user): ?>
  <header class="topbar"><div class="brand">Wedlock</div><a class="btn ghost" href="#auth">Sign In</a></header>
  <main class="landing" style="background-image:url('<?= e($settings['landing_image_url'] ?? 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1800&auto=format&fit=crop') ?>')">
    <div class="overlay"><h1>Find your forever partner</h1><p>Modern matrimonial system with admin moderation, smart matching, and premium packages.</p><a class="btn" href="#auth">Create Free Profile</a></div>
  </main>
  <section id="auth" class="auth-grid">
    <form method="post" class="card"><h2>Register</h2><input type="hidden" name="action" value="register"><label>Name</label><input name="full_name" required><label>Email</label><input type="email" name="email" required><label>Phone</label><input name="phone"><label>Password</label><input type="password" name="password" minlength="6" required><button class="btn">Register</button></form>
    <form method="post" class="card"><h2>Login</h2><input type="hidden" name="action" value="login"><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required><button class="btn">Sign In</button><p class="note">Admin: <code>admin@wedlock.local</code> / <code>admin123456</code></p></form>
  </section>
<?php else: ?>
  <header class="topbar">
    <div class="brand">Wedlock</div>
    <nav><a href="?page=dashboard">Dashboard</a><a href="?page=profile">Profile</a><a href="?page=packages">Packages</a><a href="?page=connections">Connections</a><?php if ($user['role']==='admin'): ?><a href="?page=admin">Admin</a><?php endif; ?><a href="?logout=1">Logout</a></nav>
  </header>
  <main class="app">
    <?php if ($page === 'dashboard'): ?>
      <section class="card"><h3>Hello, <?= e((string)$user['full_name']) ?></h3><p>Account Status: <strong><?= e(strtoupper((string)$user['status'])) ?></strong></p><div id="dashboardStats" class="loading">Loading stats...</div></section>
      <section class="card"><div class="row"><h3>Smart Matches</h3><button class="btn small" id="reloadMatches">Reload</button></div><div id="matchesSkeleton" class="skeleton-wrap"><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div><div id="matchesGrid" class="grid"></div></section>
    <?php elseif ($page === 'profile'):
      $ps = $pdo->prepare("SELECT * FROM profiles WHERE user_id=?"); $ps->execute([(int)$user['id']]); $p = $ps->fetch() ?: [];
    ?>
      <section class="card"><h3>Edit Profile / About / Bio</h3><form id="profileForm" class="form-grid">
        <label>Gender</label><select name="gender"><option <?= (($p['gender']??'')==='Male')?'selected':'' ?>>Male</option><option <?= (($p['gender']??'')==='Female')?'selected':'' ?>>Female</option><option <?= (($p['gender']??'')==='Other')?'selected':'' ?>>Other</option></select>
        <label>Seeking Gender</label><select name="seeking_gender"><option <?= (($p['seeking_gender']??'')==='Female')?'selected':'' ?>>Female</option><option <?= (($p['seeking_gender']??'')==='Male')?'selected':'' ?>>Male</option><option <?= (($p['seeking_gender']??'')==='Other')?'selected':'' ?>>Other</option></select>
        <label>DOB</label><input type="date" name="dob" value="<?= e((string)($p['dob'] ?? '')) ?>">
        <label>City</label><input name="city" value="<?= e((string)($p['city'] ?? '')) ?>">
        <label>Religion</label><input name="religion" value="<?= e((string)($p['religion'] ?? '')) ?>">
        <label>Education</label><input name="education" value="<?= e((string)($p['education'] ?? '')) ?>">
        <label>Occupation</label><input name="occupation" value="<?= e((string)($p['occupation'] ?? '')) ?>">
        <label>Annual Income LPA</label><input type="number" step="0.01" name="annual_income_lpa" value="<?= e((string)($p['annual_income_lpa'] ?? '')) ?>">
        <label>Profile Image URL</label><input name="profile_photo_url" value="<?= e((string)($p['profile_photo_url'] ?? '')) ?>">
        <label>Cover Image URL</label><input name="cover_photo_url" value="<?= e((string)($p['cover_photo_url'] ?? '')) ?>">
        <label>About Me</label><textarea name="about_me"><?= e((string)($p['about_me'] ?? '')) ?></textarea>
        <label>Bio</label><textarea name="bio"><?= e((string)($p['bio'] ?? '')) ?></textarea>
        <button class="btn">Save</button>
      </form></section>
    <?php elseif ($page === 'packages'): ?>
      <section class="card"><h3>Membership Packages</h3><div id="plansGrid" class="grid"></div></section>
      <section class="card"><h3>Payment Template</h3><p>Set your QR URL from Admin settings key <code>payment_qr_url</code>.</p><img class="qr" src="<?= e($settings['payment_qr_url'] ?? 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?q=80&w=900&auto=format&fit=crop') ?>" alt="qr"></section>
    <?php elseif ($page === 'connections'): ?>
      <section class="card"><h3>Connection Requests</h3><div id="requestsBox" class="loading">Loading requests...</div></section>
    <?php elseif ($page === 'admin' && $user['role'] === 'admin'): ?>
      <section class="card"><h3>Pending Approval Queue</h3><div id="pendingBox" class="loading">Loading pending profiles...</div></section>
      <section class="card"><h3>Plan Manager</h3><div id="adminPlans" class="loading">Loading plans...</div><form id="planForm" class="form-grid"><input type="hidden" name="id"><label>Name</label><input name="plan_name" required><label>Price</label><input type="number" step="0.01" name="price_inr" required><label>Duration Days</label><input type="number" name="duration_days" required><label>Max Contacts</label><input type="number" name="max_contact_views" required><label>Sort</label><input type="number" name="sort_order" value="9"><label><input type="checkbox" name="has_priority_listing"> Priority Listing</label><label><input type="checkbox" name="has_advanced_filters"> Advanced Filters</label><label><input type="checkbox" name="is_active" checked> Active</label><button class="btn">Save Plan</button></form></section>
      <section class="card"><h3>Template/Image Settings</h3><form id="settingsForm" class="form-grid"><label>Landing Image URL</label><input name="landing_image_url" value="<?= e((string)($settings['landing_image_url'] ?? '')) ?>"><label>Payment QR URL</label><input name="payment_qr_url" value="<?= e((string)($settings['payment_qr_url'] ?? '')) ?>"><button class="btn">Save Settings</button></form></section>
    <?php endif; ?>
  </main>
<?php endif; ?>
</body>
</html>

