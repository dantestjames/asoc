<?php
use ASOC\Core\App;

$lang    = App::lang();
$csrf    = App::csrf();
$db      = App::db();

// Handle enable/disable
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validate($_POST['_csrf'] ?? '')) {
        $_SESSION['flash_error'] = $lang->get('error.csrf');
    } else {
        $slug    = $_POST['module_slug'] ?? '';
        $enabled = ($_POST['action'] ?? '') === 'enable' ? 1 : 0;
        $db->update('modules', ['is_enabled' => $enabled], ['module_slug' => $slug]);
        $_SESSION['flash_success'] = 'Module ' . ($enabled ? 'enabled' : 'disabled') . '.';
    }
    header('Location: ' . url('admin/modules'));
    exit;
}

$modules   = $db->all('modules', [], '*', 'is_core DESC, module_name ASC');
$pageTitle = 'Modules';
require __DIR__ . '/header.php';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?><?php unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>

<p style="color:var(--muted);margin-bottom:1.5rem;">
    Enabling or disabling a module takes effect immediately. Core modules cannot be disabled.
</p>

<table class="data-table">
    <thead><tr>
        <th>Module</th>
        <th>Version</th>
        <th>Type</th>
        <th>Status</th>
        <th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($modules as $mod): ?>
    <tr>
        <td>
            <strong><?= htmlspecialchars($mod['module_name']) ?></strong>
            <div style="font-size:.8rem;color:var(--muted);"><?= htmlspecialchars($mod['module_slug']) ?></div>
        </td>
        <td><?= htmlspecialchars($mod['version'] ?? '—') ?></td>
        <td><?= $mod['is_core'] ? '<span class="badge badge-completed">Core</span>' : 'Optional' ?></td>
        <td>
            <span class="badge <?= $mod['is_enabled'] ? 'badge-completed' : 'badge-cancelled' ?>">
                <?= $mod['is_enabled'] ? 'Enabled' : 'Disabled' ?>
            </span>
        </td>
        <td>
            <?php if (!$mod['is_core']): ?>
            <form method="POST" action="<?php echo url('admin/modules'); ?>" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= $csrf->token() ?>">
                <input type="hidden" name="module_slug" value="<?= htmlspecialchars($mod['module_slug']) ?>">
                <input type="hidden" name="action" value="<?= $mod['is_enabled'] ? 'disable' : 'enable' ?>">
                <button class="btn btn-sm <?= $mod['is_enabled'] ? 'btn-warning' : 'btn-primary' ?>">
                    <?= $mod['is_enabled'] ? 'Disable' : 'Enable' ?>
                </button>
            </form>
            <?php else: ?>
            <span style="font-size:.8rem;color:var(--muted);">Always on</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<style>
.badge { display:inline-block;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600; }
.badge-completed { background:#D1FAE5;color:#065F46; }
.badge-cancelled { background:#F3F4F6;color:#374151; }
.btn-warning { background:#FEF3C7;color:#92400E;border-color:#F59E0B; }
.btn-warning:hover { background:#F59E0B;color:white; }
.alert { padding:.875rem 1rem;border-radius:8px;margin-bottom:1.5rem; }
.alert-success { background:#D1FAE5;color:#065F46; }
</style>

<?php require __DIR__ . '/footer.php'; ?>
