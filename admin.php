<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_admin_or_redirect($user);

layout_head('Wedlock - Admin', 'admin');
layout_flash();
layout_nav($user);
?>
<main class="app">
  <section class="card">
    <h3>Pending Approval Queue</h3>
    <div id="pendingBox" class="loading">Loading pending profiles...</div>
  </section>
  <section class="card">
    <h3>Plan Manager</h3>
    <div id="adminPlans" class="loading">Loading plans...</div>
    <form id="planForm" class="form-grid">
      <input type="hidden" name="id">
      <label>Name</label><input name="plan_name" required>
      <label>Price</label><input type="number" step="0.01" name="price_inr" required>
      <label>Duration Days</label><input type="number" name="duration_days" required>
      <label>Max Contacts</label><input type="number" name="max_contact_views" required>
      <label>Sort Order</label><input type="number" name="sort_order" value="9">
      <label><input type="checkbox" name="has_priority_listing"> Priority Listing</label>
      <label><input type="checkbox" name="has_advanced_filters"> Advanced Filters</label>
      <label><input type="checkbox" name="is_active" checked> Active</label>
      <button class="btn">Save Plan</button>
    </form>
  </section>
  <section class="card">
    <h3>Template / Asset Settings</h3>
    <form id="settingsForm" class="form-grid">
      <label>Landing Image URL</label><input name="landing_image_url" value="<?= e((string)($settings['landing_image_url'] ?? '')) ?>">
      <label>Dashboard Image URL</label><input name="dashboard_image_url" value="<?= e((string)($settings['dashboard_image_url'] ?? '')) ?>">
      <label>Register Image URL</label><input name="register_image_url" value="<?= e((string)($settings['register_image_url'] ?? '')) ?>">
      <label>Profile Image URL</label><input name="profile_image_url" value="<?= e((string)($settings['profile_image_url'] ?? '')) ?>">
      <label>Payment QR URL</label><input name="payment_qr_url" value="<?= e((string)($settings['payment_qr_url'] ?? '')) ?>">
      <button class="btn">Save Settings</button>
    </form>
  </section>
</main>
<?php layout_close(); ?>

