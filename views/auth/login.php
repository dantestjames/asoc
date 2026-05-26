<?php
/**
 * Login Page
 */

use ASOC\Core\App;

$lang = App::lang();
$csrf = App::csrf();
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$pageTitle = $lang->get('auth.login');
require __DIR__ . '/../partials/header.php';
?>

<section class="login-section">
    <div class="container container--narrow">
        <div class="login-card">
            <h1><?= htmlspecialchars($lang->get('auth.login')) ?></h1>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <form method="GET" action="<?php echo url('login'); ?>" class="form">
                <?= $csrf->field() ?>
                <div class="field">
                    <label for="email"><?= htmlspecialchars($lang->get('auth.email')) ?></label>
                    <input type="email" id="email" name="email" required autocomplete="email" autofocus>
                </div>
                <div class="field">
                    <label for="password"><?= htmlspecialchars($lang->get('auth.password')) ?></label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <div class="field-row">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="remember" value="1">
                        <?= htmlspecialchars($lang->get('auth.remember_me')) ?>
                    </label>
                    <a href="<?php echo url('forgot-password'); ?>" class="forgot-link">
                        <?= htmlspecialchars($lang->get('auth.forgot_password')) ?>
                    </a>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-large">
                    <?= htmlspecialchars($lang->get('auth.login')) ?>
                </button>
            </form>

            <p class="register-prompt">
                <?= htmlspecialchars($lang->get('auth.not_a_member')) ?> <a href="<?php echo url('membership'); ?>"><?= htmlspecialchars($lang->get('auth.join')) ?></a>
            </p>
        </div>
    </div>
</section>

<style>
.container--narrow { max-width: 480px; margin: 0 auto; padding: 0 1rem; }
.login-section { padding: 4rem 0; }
.login-card {
    background: var(--bg);
    border: 1px solid var(--grey-mid);
    border-radius: 16px;
    padding: 2.5rem;
}
.login-card h1 {
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    text-align: center;
}
.field { margin-bottom: 1.25rem; }
.field label { display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem; }
.field input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--grey-mid);
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
}
.field input:focus {
    outline: 2px solid var(--secondary);
    outline-offset: 2px;
    border-color: var(--secondary);
}
.field-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}
.checkbox-inline { display: flex; gap: 0.5rem; align-items: center; }
.forgot-link { color: var(--secondary); }
.btn-block { width: 100%; }
.btn-large { padding: 1rem; font-size: 1.05rem; }
.register-prompt {
    text-align: center;
    margin-top: 1.5rem;
    color: var(--grey-text);
    font-size: 0.95rem;
}
.alert { padding: 0.875rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; }
.alert-error { background: #FEE2E2; color: #991B1B; border-left: 4px solid #DC2626; }
</style>

<?php require __DIR__ . '/../partials/footer.php'; ?>
