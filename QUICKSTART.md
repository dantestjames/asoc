# ASOC Quick Start (Flat Layout)

## What's in this drop

ASOC Foundation Build (Phase 1) — modular PHP association-management platform built for ICCWA. Membership module fully wired, foundation ready for Phase 2 (Financials, Directory, Articles, Events, Support, Gallery).

## Layout

This drop ships in **flat layout**. The contents go straight into your site's document root (typically `public_html/` on cPanel or SiteGround). Nothing needs to live above the web root.

```
public_html/                          ← document root
├── index.php                          ← front controller
├── .env                               ← secrets (created by installer)
├── .htaccess                          ← rewrites + denies for asoc-system/ and views/
├── views/                             ← templates (require'd by PHP)
├── uploads/                           ← user-uploaded media
├── install/                           ← one-time install wizard
├── README.md                          ← full architecture docs
├── QUICKSTART.md                      ← this file
└── asoc-system/                       ← all sensitive code
    ├── .htaccess                      ← Require all denied
    ├── core/                          ← framework services
    ├── config/                        ← config + .env loader
    ├── modules/                       ← feature modules (membership, etc.)
    ├── languages/                     ← EN-AU and ID-ID translations
    ├── database/migrations/           ← schema SQL
    ├── database/seeds/                ← initial data SQL
    ├── storage/                       ← logs, install lock, cache
    └── widgets/                       ← page builder widgets
```

The `asoc-system/` folder is double-blocked: a `Require all denied` in `asoc-system/.htaccess` plus a `RewriteRule ^asoc-system(/|$) - [F,L]` in the root `.htaccess`. Anything in there is loaded by PHP through `require`, never served via HTTP.

## Pre-deployment

**Server requirements:**
- PHP 8.2 or higher (the install wizard checks)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (or Nginx with equivalent rewrite rules)
- PHP extensions: PDO, pdo_mysql, openssl, json, mbstring, gd or imagick

**Create a database** in cPanel and note the database name, username, and password.

## Deployment

1. **Upload everything** in this archive to your `public_html/` (or whatever your document root is). Don't place anything above the web root — the flat layout has it all in one place.

2. **Set permissions:**
   ```
   chmod 755 asoc-system/storage uploads
   ```
   These two need to be writable by PHP. The installer also writes a fresh `.env` to the document root, so that needs to be writable on first install.

3. **Visit your domain.** Anything except `/install/` redirects to the installer.

4. **The install wizard** walks you through:
   - Pre-flight checks (PHP version, extensions, writable folders)
   - Database connection (uses the credentials you created)
   - Admin password
   - Done

5. **Critical post-install steps:**
   - **Back up `.env`.** The encryption key in there protects payment credentials. Lose it and encrypted credentials are unrecoverable. Keep a copy in 1Password or similar.
   - **Block or delete `/install/`.** Either delete the folder, or add a `Require all denied` `.htaccess` inside it. The installer self-blocks via the lock file but defence in depth helps.
   - Configure SMTP in admin (Settings, Email) so password resets actually send (Phase 2).

## First login

After install, log in with the email and password you set in the wizard.

If you accepted defaults:
- Email: `secretary@iccwa.net.au`
- Password: `ChangeMe123!` (**change immediately on first login**)

## Test the membership flow end-to-end

1. Visit `/membership` — see the Professional and Corporate tier cards
2. Click Register Now on Professional
3. Fill the form (use a real-looking email)
4. Submit — you'll see a "thank you, pending approval" page
5. Log in as admin, go to `/admin/members/pending` — your test application is there
6. Click Approve — status changes to active

To simulate a payment-triggered activation manually until the Financials module ships:

```sql
UPDATE asoc_members
SET membership_status='active',
    expires_at=DATE_ADD(NOW(), INTERVAL 1 YEAR)
WHERE id=YOUR_TEST_MEMBER_ID;

UPDATE asoc_users SET status='active' WHERE id=THE_USER_ID;
```

## What works in this drop

- Public homepage, login, member registration (individual and business), thank-you flow
- Member dashboard (overview, profile editing)
- Admin dashboard (member list, pending approval queue, tier viewer, member detail page)
- Bilingual EN/ID switching with EN-AU/UK/US spelling variants
- AES-256-GCM encryption for sensitive credentials
- CSRF protection, login throttling, audit logging
- Privacy Policy and Terms of Use scaffolds (Australian APP-compliant)

## What's NOT here yet

- Payment processing (Stripe/Square/PayPal) — coming in Phase 2
- Email actually sending (queue is built, processor is Phase 2)
- Page builder UI (storage layer exists, drag-drop editor is Phase 3)
- Directory, Articles, Events, Support, Gallery modules (Phase 2 and 3)

## Want the full architecture?

Read `README.md` in this directory for module manifest format, the security model, and what comes next.

---

Made by Solopreneur Systems
