<?php
/**
 * Admin Shell — Header
 *
 * Sidebar nav, dark theme, used by every admin view.
 * Module-registered admin menu items appear automatically.
 *
 * Footer credit "Made by Solopreneur Systems" lives in admin-footer.php.
 */

use ASOC\Core\App;
use ASOC\Core\Config;

if (!App::auth()->isAdmin()) {
    http_response_code(403);
    exit('Access denied.');
}

$lang        = App::lang();
$adminMenu   = App::modules()->adminMenu();
$siteName    = App::settings()->get('association_short_name', 'ICCWA');
$pageTitle   = $pageTitle ?? 'Admin';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (Config::basePath() && str_starts_with($currentPath, Config::basePath())) {
    $currentPath = substr($currentPath, strlen(Config::basePath()));
}
if (empty($currentPath)) $currentPath = '/';
?><!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang->current()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — Admin · <?= htmlspecialchars($siteName) ?></title>
<style>
:root {
    --primary: <?= htmlspecialchars(App::settings()->get('theme_colour_primary', '#AA0101')) ?>;
    --secondary: <?= htmlspecialchars(App::settings()->get('theme_colour_secondary', '#15AAFF')) ?>;
    --dark: <?= htmlspecialchars(App::settings()->get('theme_colour_dark', '#20314F')) ?>;
    --bg: #F5F5F7;
    --surface: #FFFFFF;
    --text: #1A1A1A;
    --grey-light: #F5F5F7;
    --grey-mid: #D1D5DB;
    --grey-text: #6B7280;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.5;
    min-height: 100vh;
    display: flex;
}
a { color: var(--secondary); text-decoration: none; }
.admin-sidebar {
    width: 240px;
    background: var(--dark);
    color: rgba(255,255,255,0.85);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    flex-shrink: 0;
}
.admin-brand {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.admin-brand strong { color: white; font-size: 1.1rem; font-weight: 700; letter-spacing: -0.01em; }
.admin-brand small { display: block; color: rgba(255,255,255,0.55); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 0.25rem; }
.admin-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}
.admin-nav-section { margin-bottom: 0.5rem; }
.admin-nav-item {
    display: block;
    padding: 0.6rem 1.5rem;
    color: rgba(255,255,255,0.85);
    font-size: 0.9rem;
    border-left: 3px solid transparent;
}
.admin-nav-item:hover { background: rgba(255,255,255,0.05); color: white; text-decoration: none; }
.admin-nav-item.active { background: rgba(255,255,255,0.08); color: white; border-left-color: var(--primary); font-weight: 600; }
.admin-nav-children { padding-left: 1rem; }
.admin-nav-children .admin-nav-item { font-size: 0.85rem; padding: 0.4rem 1.5rem; color: rgba(255,255,255,0.65); }
.admin-nav-children .admin-nav-item.active { color: white; background: transparent; border-left-color: transparent; font-weight: 600; }
.admin-sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
}
.admin-sidebar-footer a { color: rgba(255,255,255,0.85); }
.admin-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.admin-topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--grey-mid);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.admin-page-title { font-size: 1.25rem; font-weight: 600; letter-spacing: -0.01em; }
.admin-topbar-meta { font-size: 0.85rem; color: var(--grey-text); }
.admin-topbar-meta a { color: var(--text); }
.admin-main {
    flex: 1;
    padding: 2rem;
}
.admin-footer {
    padding: 1rem 2rem;
    border-top: 1px solid var(--grey-mid);
    font-size: 0.8rem;
    color: var(--grey-text);
    text-align: center;
}
.admin-footer em { font-style: italic; }
.alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
.alert-success { background: #D1FAE5; color: #065F46; border-left: 4px solid #059669; }
.alert-error { background: #FEE2E2; color: #991B1B; border-left: 4px solid #DC2626; }
.alert-info { background: #DBEAFE; color: #1E40AF; border-left: 4px solid var(--secondary); }
.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-family: inherit;
}
.btn:hover { background: #880101; text-decoration: none; }
.btn-secondary { background: var(--grey-mid); color: var(--text); }
.btn-success { background: #059669; }
.btn-danger { background: #DC2626; }
.btn-sm { padding: 0.35rem 0.7rem; font-size: 0.8rem; }
.card {
    background: var(--surface);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 1.5rem;
}
table { width: 100%; border-collapse: collapse; background: var(--surface); }
th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--grey-light); }
th { background: var(--grey-light); font-weight: 600; font-size: 0.85rem; color: var(--grey-text); text-transform: uppercase; letter-spacing: 0.05em; }
tbody tr:hover { background: rgba(0,0,0,0.02); }
.badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
.badge-pending { background: #FEF3C7; color: #92400E; }
.badge-active { background: #D1FAE5; color: #065F46; }
.badge-expired { background: #FEE2E2; color: #991B1B; }
.badge-cancelled { background: var(--grey-mid); color: var(--text); }
@media (max-width: 900px) {
    body { flex-direction: column; }
    .admin-sidebar { width: 100%; height: auto; position: static; }
    .admin-nav { display: flex; flex-wrap: wrap; padding: 0.5rem; }
    .admin-nav-section { width: auto; }
    .admin-nav-children { display: none; }
}
</style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="admin-brand">
        <strong>ASOC Admin</strong>
        <small><?= htmlspecialchars($siteName) ?></small>
    </div>
    <nav class="admin-nav">
        <a href="<?php echo url('admin'); ?>" class="admin-nav-item <?= $currentPath === '/admin' ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('admin.dashboard')) ?></a>
        <a href="<?php echo url('admin/pages'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/pages') ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('pagebuilder.pages')) ?></a>

        <?php foreach ($adminMenu as $section):
            $sectionUrl = Config::basePath() . $section['url'];
        ?>
            <div class="admin-nav-section">
                <a href="<?= htmlspecialchars($sectionUrl) ?>" class="admin-nav-item <?= str_starts_with($currentPath, $section['url']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($section['label']) ?>
                </a>
                <?php if (!empty($section['children'])): ?>
                <div class="admin-nav-children">
                    <?php foreach ($section['children'] as $child):
                        $childUrl = Config::basePath() . $child['url'];
                    ?>
                        <a href="<?= htmlspecialchars($childUrl) ?>" class="admin-nav-item <?= $currentPath === $child['url'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($child['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="admin-nav-section" style="margin-top: 2rem;">
            <a href="<?php echo url('admin/financials'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/financials') ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('financials.dashboard')) ?></a>
            <div class="admin-nav-children">
                <a href="<?php echo url('admin/financials/gateways'); ?>" class="admin-nav-item <?= $currentPath === '/admin/financials/gateways' ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('financials.gateways')) ?></a>
                <a href="<?php echo url('admin/financials/transactions'); ?>" class="admin-nav-item <?= $currentPath === '/admin/financials/transactions' ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('financials.transactions')) ?></a>
            </div>
        </div>

        <div class="admin-nav-section">
            <a href="<?php echo url('admin/articles'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/articles') ? 'active' : '' ?>">Articles</a>
        </div>
        <div class="admin-nav-section">
            <a href="<?php echo url('admin/email'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/email') ? 'active' : '' ?>">Email</a>
            <a href="<?php echo url('admin/crm'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/crm') ? 'active' : '' ?>">CRM</a>
        </div>
        <div class="admin-nav-section">
            <a href="<?php echo url('admin/media'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/media') ? 'active' : '' ?>">Media</a>
            <a href="<?php echo url('admin/gallery'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/gallery') ? 'active' : '' ?>">Galleries</a>
        </div>
        <div class="admin-nav-section" style="margin-top:1rem;">
            <a href="<?php echo url('admin/settings'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/settings') ? 'active' : '' ?>"><?= htmlspecialchars($lang->get('admin.settings')) ?></a>
            <a href="<?php echo url('admin/modules'); ?>" class="admin-nav-item <?= str_starts_with($currentPath, '/admin/modules') ? 'active' : '' ?>">Modules</a>
        </div>
    </nav>
    <div class="admin-sidebar-footer">
        <div><?= htmlspecialchars(App::auth()->user()['email']) ?></div>
        <div style="margin-top: 0.5rem;"><a href="<?php echo url(''); ?>">View site</a> · <a href="<?php echo url('logout'); ?>">Log out</a></div>
    </div>
</aside>

<div class="admin-content">
    <div class="admin-topbar">
        <div class="admin-page-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="admin-topbar-meta">
            <?= date('l, j F Y') ?>
        </div>
    </div>
    <main class="admin-main">

    <?php
    if (!empty($_SESSION['flash_success'])):
        $flashSuccess = $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php
    if (!empty($_SESSION['flash_error'])):
        $flashError = $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
