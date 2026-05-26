# ASOC — Association Management System

A modular PHP 8.2+ association management platform built for **ICCWA** (Indonesian Chamber of Commerce Western Australia). Runs on standard cPanel/SiteGround hosting with zero Composer dependencies.

## Modules

| Module | Status |
|--------|--------|
| Page Builder | ✅ Complete |
| Articles | ✅ Complete |
| Events | ✅ Complete |
| Member Directory | ✅ Complete |
| Membership Management | ✅ Complete |
| Email / Newsletter | ✅ Complete |
| Financials | ✅ Complete |
| Media Manager | ✅ Complete |
| Gallery Builder | ✅ Complete |
| CRM | ✅ Complete |
| Support Tickets | 🔄 Planned |

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.4+
- Apache with mod_rewrite

## Installation

1. Upload all files to your web root (e.g. `public_html/iccwa/`)
2. Copy `.env.example` to `.env` and fill in your database credentials
3. Create the database in cPanel
4. Visit `https://yourdomain.com/iccwa/fix-app-url` — this installs all tables and seeds defaults
5. Log in at `https://yourdomain.com/iccwa/login`
   - Default admin: `secretary@iccwa.net.au` / `ChangeMe123!`
   - **Change this password immediately**

## Deployment Notes

- The `uploads/` directory is excluded from git. Create it on the server and set permissions to `755`
- The `.env` file is excluded from git. Never commit it
- All database schema lives in `asoc-system/database/migrations/` and module `schema.sql` files
- The `fix-app-url` route is safe to run multiple times — all statements use `IF NOT EXISTS` / `INSERT IGNORE`

## Architecture

```
iccwa/
├── index.php                    ← Front controller (all routes)
├── .env                         ← Environment config (not in git)
├── .htaccess                    ← URL rewriting
├── assets/css/                  ← Site stylesheets
├── views/                       ← Public + admin views
│   ├── admin/                   ← Admin shell (header, footer, dashboard)
│   └── partials/                ← Shared partials (hero, header, footer)
├── uploads/                     ← User uploads (not in git)
└── asoc-system/
    ├── core/                    ← App, Auth, Config, Database, Router, Settings
    ├── database/                ← Migrations and seed data
    ├── modules/                 ← Feature modules (each self-contained)
    │   ├── articles/
    │   ├── crm/
    │   ├── directory/
    │   ├── email/
    │   ├── events/
    │   ├── financials/
    │   ├── gallery/
    │   ├── media/
    │   ├── membership/
    │   └── pagebuilder/
    └── storage/                 ← Logs (not in git)
```

## Built By

Solopreneur Systems © 2025  
Built with Claude (Anthropic)
