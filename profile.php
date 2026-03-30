<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login_or_redirect($user);

$stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id=?");
$stmt->execute([(int)$user['id']]);
$p = $stmt->fetch() ?: [];

layout_head('Wedlock - Profile', 'profile');
layout_flash();
layout_nav($user);
$profileTemplate = $settings['profile_image_url'] ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=1000&auto=format&fit=crop';
?>
<main class="app">
  <section class="card">
    <h3>Edit Profile / About / Bio</h3>
    <form id="profileForm" class="form-grid">
      <label>Gender</label><select name="gender"><option <?= (($p['gender']??'')==='Male')?'selected':'' ?>>Male</option><option <?= (($p['gender']??'')==='Female')?'selected':'' ?>>Female</option><option <?= (($p['gender']??'')==='Other')?'selected':'' ?>>Other</option></select>
      <label>Seeking Gender</label><select name="seeking_gender"><option <?= (($p['seeking_gender']??'')==='Female')?'selected':'' ?>>Female</option><option <?= (($p['seeking_gender']??'')==='Male')?'selected':'' ?>>Male</option><option <?= (($p['seeking_gender']??'')==='Other')?'selected':'' ?>>Other</option></select>
      <label>DOB</label><input type="date" name="dob" value="<?= e((string)($p['dob'] ?? '')) ?>">
      <label>City</label><input name="city" value="<?= e((string)($p['city'] ?? '')) ?>">
      <label>Religion</label><input name="religion" value="<?= e((string)($p['religion'] ?? '')) ?>">
      <label>Education</label><input name="education" value="<?= e((string)($p['education'] ?? '')) ?>">
      <label>Occupation</label><input name="occupation" value="<?= e((string)($p['occupation'] ?? '')) ?>">
      <label>Annual Income LPA</label><input type="number" step="0.01" name="annual_income_lpa" value="<?= e((string)($p['annual_income_lpa'] ?? '')) ?>">
      <label>Profile Photo URL</label><input name="profile_photo_url" value="<?= e((string)($p['profile_photo_url'] ?? '')) ?>">
      <label>Cover Photo URL</label><input name="cover_photo_url" value="<?= e((string)($p['cover_photo_url'] ?? '')) ?>">
      <label>About Me</label><textarea name="about_me"><?= e((string)($p['about_me'] ?? '')) ?></textarea>
      <label>Bio</label><textarea name="bio"><?= e((string)($p['bio'] ?? '')) ?></textarea>
      <button class="btn">Save Profile</button>
    </form>
  </section>
  <section class="card">
    <h3>Profile Template</h3>
    <img class="qr" style="max-width:100%;width:100%;" src="<?= e($profileTemplate) ?>" alt="Profile template">
  </section>
</main>
<?php layout_close(); ?>
