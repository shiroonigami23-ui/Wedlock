# Wedlock (PHP Matrimony Platform)
[![Release APK + EXE](https://github.com/shiroonigami23-ui/Wedlock/actions/workflows/release-apk-exe.yml/badge.svg)](https://github.com/shiroonigami23-ui/Wedlock/actions/workflows/release-apk-exe.yml)
![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Optimized-4479A1?style=flat&logo=mysql)
![InfinityFree](https://img.shields.io/badge/Hosting-InfinityFree-6C2BD9?style=flat)

Live URL: **https://sobran.lovestoblog.com**

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
3. Copy `includes/config.local.sample.php` to `includes/config.local.php`.
4. Fill DB credentials in `includes/config.local.php`:
   - host: `sql101.infinityfree.com`
   - db: `if0_40800486_Wed_lock`
   - user: `if0_40800486`
   - password: your vPanel password
5. Ensure `storage/` is writable.
6. Open site URL.

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
- Multipage structure is now live: `index.php`, `register.php`, `login.php`, `dashboard.php`, `profile.php`, `packages.php`, `connections.php`, `admin.php`, `contact.php`.
