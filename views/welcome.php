<?php
/**
 * Default Welcome Page
 *
 * Shown when no page is marked as homepage in the pages table.
 * Once admin sets a homepage in the page builder, this won't appear.
 */

use ASOC\Core\App;

$lang = App::lang();
$settings = App::settings();
$siteName = $settings->get('association_name');

$pageTitle = 'Welcome';
require __DIR__ . '/partials/header.php';

// Show admin bar so the admin can get to the page builder
if (App::auth()->isAdmin()): ?>
<div style="position:fixed;top:0;left:0;right:0;height:40px;background:#1e293b;display:flex;align-items:center;gap:.75rem;padding:0 1rem;z-index:99999;border-bottom:2px solid #f59e0b;font-family:system-ui,sans-serif;">
    <span style="font-size:.75rem;font-weight:700;color:#f59e0b">⚠ No homepage set</span>
    <span style="font-size:.75rem;color:rgba(255,255,255,.5)">—</span>
    <span style="font-size:.75rem;color:rgba(255,255,255,.55)">Go to Admin → Pages and set a page as "Home page", or the site will show this placeholder.</span>
    <span style="flex:1"></span>
    <a href="<?= url('admin/pages') ?>" style="padding:.3rem .75rem;background:#3b82f6;color:white;border-radius:5px;font-size:.75rem;font-weight:600;text-decoration:none">Manage pages →</a>
    <a href="<?= url('admin') ?>"       style="padding:.3rem .75rem;background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);border-radius:5px;font-size:.75rem;font-weight:600;text-decoration:none">Dashboard</a>
</div>
<style>body{padding-top:40px}</style>
<?php endif;
?>

<section class="hero">
    <div class="container">
        <h1><?= htmlspecialchars($lang->get('welcome.title', ['name' => $siteName])) ?></h1>
        <p class="hero-tagline"><?= htmlspecialchars($lang->get('welcome.tagline')) ?></p>
        <div class="hero-actions">
            <a href="<?php echo url('membership'); ?>" class="btn btn-primary btn-large"><?= htmlspecialchars($lang->get('welcome.become_member')) ?></a>
            <a href="<?php echo url('about'); ?>" class="btn btn-outline btn-large"><?= htmlspecialchars($lang->get('welcome.learn_more')) ?></a>
        </div>
    </div>
</section>

<style>
.hero {
    padding: 6rem 0;
    text-align: center;
    background: linear-gradient(135deg, var(--bg) 0%, var(--grey-light) 100%);
}
.hero h1 {
    font-size: 3rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    margin-bottom: 1rem;
}
.hero-tagline {
    font-size: 1.25rem;
    color: var(--grey-text);
    margin-bottom: 2.5rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}
.hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
.btn-large { padding: 1rem 2rem; font-size: 1.05rem; }
@media (max-width: 600px) {
    .hero h1 { font-size: 2rem; }
    .hero-tagline { font-size: 1.05rem; }
}
</style>

<?php require __DIR__ . '/partials/footer.php'; ?>
