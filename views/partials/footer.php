<?php
/**
 * Site Footer Partial
 *
 * Closes <main>, renders footer, closes body and html.
 */

use ASOC\Core\App;

$settings = App::settings();
$lang = App::lang();

$siteName = $settings->get('association_name', 'ICCWA');
$footerCredit = $settings->get('footer_credit', 'Made by Solopreneur Systems');

// Pull footer nav from menus table
$footerMenu = App::db()->find('navigation_menus', ['slug' => 'footer']);
$footerItems = $footerMenu ? json_decode($footerMenu['items'], true) : [];
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4><?= htmlspecialchars($siteName) ?></h4>
                <p class="footer-meta"><?= htmlspecialchars($lang->get('welcome.tagline')) ?></p>
                <address class="footer-meta">
                    <?= nl2br(htmlspecialchars($settings->get('association_address_physical', ''))) ?><br>
                    <?= htmlspecialchars($settings->get('association_email', '')) ?><br>
                    <?= htmlspecialchars($settings->get('association_phone', '')) ?>
                </address>
            </div>

            <div class="footer-col">
                <h4><?= htmlspecialchars($lang->get('footer.links')) ?></h4>
                <ul class="footer-list">
                    <?php
                    $footerKeyMap = [
                        '/privacy-policy' => 'footer.privacy',
                        '/terms-of-use'   => 'footer.terms',
                        '/contact'        => 'footer.contact',
                        '/about'          => 'footer.about',
                    ];
                    foreach ($footerItems as $item):
                        $label = isset($footerKeyMap[$item['url']]) ? $lang->get($footerKeyMap[$item['url']]) : $item['label'];
                    ?>
                        <li><a href="<?php echo url(ltrim($item['url'], '/')); ?>"><?= htmlspecialchars($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4><?= htmlspecialchars($lang->get('nav.support')) ?></h4>
                <ul class="footer-list">
                    <li><a href="<?php echo url('support'); ?>"><?= htmlspecialchars($lang->get('footer.support')) ?></a></li>
                    <li><a href="<?php echo url('contact'); ?>"><?= htmlspecialchars($lang->get('footer.contact')) ?></a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                <?= htmlspecialchars($lang->get('footer.copyright', ['year' => date('Y'), 'name' => $siteName])) ?>
                <?php if (!empty($settings->get('association_abn', ''))): ?>
                    · ABN <?= htmlspecialchars($settings->get('association_abn', '')) ?>
                <?php endif; ?>
            </div>
            <div class="footer-credit"><?= htmlspecialchars($footerCredit) ?> <?= date('Y') ?></div>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background: var(--dark);
    color: rgba(255,255,255,0.85);
    padding: 3rem 0 1.5rem;
    margin-top: 4rem;
}
.site-footer h4 {
    color: white;
    font-size: 1rem;
    margin-bottom: 1rem;
    font-weight: 600;
}
.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 3rem;
    margin-bottom: 2.5rem;
}
.footer-meta {
    font-size: 0.9rem;
    line-height: 1.6;
    color: rgba(255,255,255,0.7);
    margin-bottom: 1rem;
    font-style: normal;
}
.footer-list { list-style: none; }
.footer-list li { margin-bottom: 0.5rem; }
.footer-list a {
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
}
.footer-list a:hover { color: white; }
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 0.85rem;
    color: rgba(255,255,255,0.6);
}
.footer-credit { font-style: italic; }
@media (max-width: 768px) {
    .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
    .footer-bottom { flex-direction: column; text-align: center; }
}
</style>

</body>
</html>
