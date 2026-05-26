<?php
/**
 * Site Header Partial
 *
 * Renders the <head>, opening <body>, and main navigation.
 * Theme colours and fonts are pulled from settings, written into CSS vars
 * so the whole site reskin works without touching code.
 */

use ASOC\Core\App;

$settings = App::settings();
$lang = App::lang();
$auth = App::auth();

$colours = [
    'bg'        => $settings->get('theme_colour_background', '#FFFFFF'),
    'text'      => $settings->get('theme_colour_text', '#1A1A1A'),
    'primary'   => $settings->get('theme_colour_primary', '#AA0101'),
    'secondary' => $settings->get('theme_colour_secondary', '#15AAFF'),
    'dark'      => $settings->get('theme_colour_dark', '#20314F'),
];

$fontHeading = $settings->get('theme_font_heading', 'system-ui, -apple-system, sans-serif');
$fontBody = $settings->get('theme_font_body', 'system-ui, -apple-system, sans-serif');

$siteName = $settings->get('association_short_name', 'ICCWA');
$tagline = $settings->get('site_tagline', '');

// Pull main nav from menus table
$mainMenu = App::db()->find('navigation_menus', ['slug' => 'main']);
$navItems = $mainMenu ? json_decode($mainMenu['items'], true) : [];

$pageTitle = $pageTitle ?? $siteName;
?><!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang->current()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($siteName) ?></title>
<style>
:root {
    --bg: <?= htmlspecialchars($colours['bg']) ?>;
    --text: <?= htmlspecialchars($colours['text']) ?>;
    --primary: <?= htmlspecialchars($colours['primary']) ?>;
    --secondary: <?= htmlspecialchars($colours['secondary']) ?>;
    --dark: <?= htmlspecialchars($colours['dark']) ?>;
    --grey-light: #F5F5F7;
    --grey-mid: #D1D5DB;
    --grey-text: #6B7280;
    --font-heading: <?= $fontHeading ?>;
    --font-body: <?= $fontBody ?>;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: var(--font-body);
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    letter-spacing: -0.01em;
    line-height: 1.2;
}
a { color: var(--secondary); text-decoration: none; }
a:hover { text-decoration: underline; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
main { flex: 1; }

/* Header */
.site-header {
    background: var(--bg);
    border-bottom: 1px solid var(--grey-mid);
    position: sticky;
    top: 0;
    z-index: 100;
}
.site-header__inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}
.site-logo {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: -0.02em;
}
.site-logo:hover { text-decoration: none; }
.site-nav { display: flex; align-items: center; gap: 1.5rem; }
.site-nav a {
    color: var(--text);
    font-weight: 500;
    font-size: 0.95rem;
    transition: color 0.15s;
}
.site-nav a:hover { color: var(--primary); text-decoration: none; }
.lang-switcher { display: flex; gap: 0.4rem; align-items: center; }
.lang-flag {
    font-size: 1.5rem;
    line-height: 1;
    opacity: 0.45;
    text-decoration: none;
    transition: opacity 0.15s, transform 0.15s;
    display: flex;
    align-items: center;
}
.lang-flag:hover { opacity: 0.85; transform: scale(1.1); text-decoration: none; }
.lang-flag.active { opacity: 1; transform: scale(1.15); }
.btn {
    display: inline-block;
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    font-family: inherit;
    transition: opacity 0.15s, background 0.15s;
}
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: #880101; text-decoration: none; }
.btn-secondary { background: var(--grey-light); color: var(--text); }
.btn-secondary:hover { background: var(--grey-mid); text-decoration: none; }
.btn-outline { border: 1px solid var(--primary); color: var(--primary); background: transparent; }
.btn-outline:hover { background: var(--primary); color: white; text-decoration: none; }

@media (max-width: 800px) {
    .site-nav { gap: 1rem; }
    .site-nav a:not(.btn) { display: none; }
}
</style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="site-header__inner">
            <a href="<?php echo url(''); ?>" class="site-logo"><?= htmlspecialchars($siteName) ?></a>
            <nav class="site-nav">
                <?php
                // Map URL paths to language keys for auto-translation
                $navKeyMap = [
                    '/'           => 'nav.home',
                    '/about'      => 'nav.about',
                    '/membership' => 'nav.membership',
                    '/directory'  => 'nav.directory',
                    '/events'     => 'nav.events',
                    '/articles'   => 'nav.articles',
                    '/contact'    => 'nav.contact',
                ];
                ?>
                <?php foreach ($navItems as $item):
                    // Use translated label if URL matches a known key, otherwise show stored label
                    $label = isset($navKeyMap[$item['url']]) ? $lang->get($navKeyMap[$item['url']]) : $item['label'];
                ?>
                    <a href="<?php echo url(ltrim($item['url'], '/')); ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>

                <div class="lang-switcher">
                    <a href="<?php echo url('set-language/en-AU'); ?>" class="lang-flag <?= $lang->current() === 'en-AU' ? 'active' : '' ?>" title="English (Australia)">🇦🇺</a>
                    <a href="<?php echo url('set-language/id-ID'); ?>" class="lang-flag <?= $lang->current() === 'id-ID' ? 'active' : '' ?>" title="Bahasa Indonesia">🇮🇩</a>
                </div>

                <?php if ($auth->check()): ?>
                    <a href="<?php echo url('dashboard'); ?>" class="btn btn-outline"><?= htmlspecialchars($lang->get('nav.dashboard')) ?></a>
                <?php else: ?>
                    <a href="<?php echo url('login'); ?>" class="btn btn-outline"><?= htmlspecialchars($lang->get('nav.login')) ?></a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<main>
