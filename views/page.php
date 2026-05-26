<?php
declare(strict_types=1);
use ASOC\Core\App;
use ASOC\Core\Config;

require_once __DIR__ . '/../asoc-system/modules/pagebuilder/src/BlockRenderer.php';

$lang     = App::lang();
$settings = App::settings();
$isAdmin  = App::auth()->isAdmin();
$builderMode = $isAdmin && isset($_GET['pb']);

// Auth check
if (!empty($page['requires_auth']) && !App::auth()->check()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . url('login'));
    exit;
}

// Decode blocks
if (is_string($page['content_blocks'])) {
    $page['content_blocks'] = json_decode($page['content_blocks'], true) ?: [];
}

$pageTitle = $page['meta_title'] ?: $page['title'];
$metaDesc  = $page['meta_description'] ?? '';

// In builder mode: skip normal header/footer, render full custom shell
if ($builderMode):
    $blocks    = $page['content_blocks'];
    $blocksJ   = json_encode($blocks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    $saveUrl   = url('admin/pages/ajax-save');
    $mediaUrl  = url('admin/media/json');
    $mediaPage = url('admin/media');
    $pagesUrl  = url('admin/pages');
    $pageId    = (int)$page['id'];
    $pageSlug  = $page['slug'] ?? '';
    $pageStatus = $page['status'] ?? 'published';
    $primary   = $settings->get('theme_colour_primary', '#AA0101');
    $dark      = $settings->get('theme_colour_dark', '#20314F');
    $secondary = $settings->get('theme_colour_secondary', '#15AAFF');
    $greyLight = '#F5F5F7'; $greyMid = '#D1D5DB'; $greyText = '#6B7280';
    require __DIR__ . '/../asoc-system/modules/pagebuilder/views/front/builder.php';
    exit;
endif;

// Normal page render
require __DIR__ . '/partials/header.php';
?>

<?php if ($isAdmin): ?>
<div id="asoc-admin-bar">
    <div class="aab-inner">
        <a href="<?= url('admin') ?>" class="aab-logo">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            ASOC
        </a>
        <span class="aab-sep"></span>
        <span class="aab-pname"><?= htmlspecialchars($page['title']) ?></span>
        <span class="aab-stat aab-stat--<?= $page['status'] ?>"><?= ucfirst($page['status']) ?></span>
        <span style="flex:1"></span>
        <a href="<?= url('admin/pages/' . $page['id'] . '/edit') ?>" class="aab-btn aab-btn--primary">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            Edit page
        </a>
        <a href="<?= url('admin/pages') ?>" class="aab-btn">All pages</a>
        <a href="<?= url('admin') ?>" class="aab-btn">Dashboard</a>
    </div>
</div>
<style>
#asoc-admin-bar{position:fixed;top:0;left:0;right:0;height:40px;background:#1e293b;display:flex;align-items:center;z-index:99999;border-bottom:2px solid #3b82f6;font-family:system-ui,sans-serif;}
.aab-inner{display:flex;align-items:center;gap:.5rem;padding:0 1rem;width:100%;}
.aab-logo{display:flex;align-items:center;gap:.35rem;color:white;text-decoration:none;font-size:.75rem;font-weight:700;}
.aab-sep{width:1px;height:16px;background:rgba(255,255,255,.15);}
.aab-pname{font-size:.75rem;color:rgba(255,255,255,.55);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;}
.aab-stat{padding:.12rem .5rem;border-radius:4px;font-size:.65rem;font-weight:700;text-transform:uppercase;}
.aab-stat--published{background:#d1fae5;color:#065f46;} .aab-stat--draft{background:#fef3c7;color:#92400e;}
.aab-btn{padding:.28rem .65rem;border-radius:5px;font-size:.72rem;font-weight:600;color:rgba(255,255,255,.7);text-decoration:none;transition:background .12s;white-space:nowrap;}
.aab-btn:hover{background:rgba(255,255,255,.1);color:white;text-decoration:none;}
.aab-btn--primary{background:#3b82f6;color:white;} .aab-btn--primary:hover{background:#2563eb;}
body{padding-top:40px;}
</style>
<?php endif; ?>

<?php
$renderer = new \ASOC\Modules\PageBuilder\BlockRenderer();
// Homepage is exempt from the universal hero — it controls its own hero via the page builder
if (empty($page['is_homepage'])) {
    $heroTitle    = $page['title'];
    $heroSubtitle = $page['meta_description'] ?? '';
    require __DIR__ . '/partials/page-hero.php';
}
echo $renderer->render($page['content_blocks'] ?? []);

require __DIR__ . '/partials/footer.php';
