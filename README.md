# Wedlock (PHP Matrimony Platform)

Wedlock is now a **PHP-first matrimonial platform** designed for shared hosting like **InfinityFree**:

- User register/login
- Admin moderation panel (approve/reject profiles)
- Editable profile/about/bio
- Smart matching via unsupervised clustering (K-means style vectors)
- Membership packages and subscription activation
- Connection requests (send/accept/decline)
- File-cache based acceleration for heavy match loads
- Skeleton/loading animations for slow sections
- Template image settings (landing/dashboard/payment)

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- Vanilla JS + CSS

## Project Layout

- `index.php` landing page
- `register.php` registration page
- `login.php` login page
- `dashboard.php` user dashboard
- `profile.php` editable profile/about/bio
- `packages.php` package + payment confirmation flow
- `connections.php` requests and interaction page
- `admin.php` admin moderation + plans + settings
- `contact.php` direct owner support page (WhatsApp)
- `logout.php` logout endpoint
- `api.php` JSON APIs
- `includes/core.php` DB + auth + matching + caching helpers
- `includes/bootstrap.php` app boot/session setup
- `includes/layout.php` shared page layout helpers
- `assets/style.css` UI styles and loaders
- `assets/app.js` frontend behavior
- `database/schema.sql` optimized tables + indexes
- `.htaccess` rewrite support

## InfinityFree Setup

1. Create MySQL DB from InfinityFree panel.
2. Put project files in `htdocs/`.
3. Set DB credentials in control panel environment or directly in `includes/core.php` defaults.
4. Ensure `storage/` is writable.
5. Open site URL.

On first run, schema auto-creates and seeds:

- default admin:
  - email: `admin@wedlock.local`
  - password: `admin123456`
- default plans: Free, Gold, Platinum

## Using Your Template Images

From Admin page, set URLs for:

- `landing_image_url`
- `dashboard_image_url`
- `register_image_url`
- `profile_image_url`
- `payment_qr_url`

This lets you use your own dashboard/landing/register/profile templates without code changes.

## Notes

- Payment activation is currently manual (`MANUAL-UPI` reference) so users can pay and confirm over WhatsApp.
- Matching cache TTL is configured in `includes/core.php` (`cache_ttl`).
- Profile status defaults to `pending` until admin approves.
- Owner support WhatsApp: `7847948216` (also available on `contact.php`).
