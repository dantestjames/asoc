<?php
use ASOC\Core\App;
$lang = App::lang();
$pageTitle = $lang->get('error.404');
require __DIR__ . '/../partials/header.php';
?>

<section class="error-section">
    <div class="container">
        <div class="error-card">
            <div class="error-code">404</div>
            <h1><?= htmlspecialchars($lang->get('error.404')) ?></h1>
            <p><?= htmlspecialchars($lang->get('error.404_desc')) ?></p>
            <a href="<?php echo url(''); ?>" class="btn btn-primary"><?= htmlspecialchars($lang->get('error.return_home')) ?></a>
        </div>
    </div>
</section>

<style>
.error-section { padding: 6rem 0; }
.error-card { text-align: center; max-width: 480px; margin: 0 auto; }
.error-code {
    font-size: 8rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
    letter-spacing: -0.05em;
}
.error-card h1 { font-size: 1.75rem; margin: 1rem 0; }
.error-card p { color: var(--grey-text); margin-bottom: 2rem; }
</style>

<?php require __DIR__ . '/../partials/footer.php'; ?>
