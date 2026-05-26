<?php
use ASOC\Core\App;
$lang    = App::lang();
$auth    = App::auth();
$user    = $auth->user();
$db      = App::db();

// Redirect non-members who haven't joined yet
$member = $db->find('members', ['user_id' => $auth->id()]);

// Get member tier if exists
$tier = $member && $member['tier_id']
    ? $db->find('membership_tiers', ['id' => $member['tier_id']])
    : null;

$pageTitle = $lang->get('nav.dashboard');
require __DIR__ . '/partials/header.php';

$statusColour = 'var(--grey-text)';
if ($member) {
    $statusColour = match($member['membership_status']) {
        'active'  => '#10B981',
        'pending' => '#F59E0B',
        'expired', 'cancelled' => '#EF4444',
        default   => 'var(--grey-text)',
    };
}
?>

<div class="db-wrap">
    <div class="container">

        <div class="db-header">
            <div>
                <h1>G'day, <?= htmlspecialchars($user['first_name'] ?? 'there') ?> 👋</h1>
                <?php if ($member): ?>
                <p><?= htmlspecialchars($member['membership_number']) ?> · <?= htmlspecialchars($tier['name'] ?? 'Member') ?></p>
                <?php else: ?>
                <p>You don't have a membership yet.</p>
                <?php endif; ?>
            </div>
            <?php if ($member): ?>
            <span class="db-status-pill" style="background:<?= match($member['membership_status']) {
                'active'  => '#D1FAE5', 'pending' => '#FEF3C7',
                'expired','cancelled','suspended' => '#FEE2E2', default => '#F3F4F6'
            } ?>;color:<?= match($member['membership_status']) {
                'active' => '#065F46', 'pending' => '#92400E',
                'expired','cancelled','suspended' => '#991B1B', default => '#374151'
            } ?>">
                <?= htmlspecialchars($lang->get('membership.status.' . $member['membership_status'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <?php if ($member): ?>
        <nav class="db-tabs">
            <a href="<?php echo url('dashboard/membership'); ?>" class="db-tab">My Membership</a>
            <a href="<?php echo url('dashboard/profile'); ?>" class="db-tab">Edit Profile</a>
            <a href="<?php echo url('dashboard/tickets'); ?>" class="db-tab">Support</a>
            <?php if ($auth->isAdmin()): ?>
            <a href="<?php echo url('admin'); ?>" class="db-tab db-tab--admin">Admin panel</a>
            <?php endif; ?>
            <a href="<?php echo url('logout'); ?>" class="db-tab db-tab--logout">Sign out</a>
        </nav>
        <?php elseif ($auth->isAdmin()): ?>
        <nav class="db-tabs">
            <a href="<?php echo url('admin'); ?>" class="db-tab db-tab--admin">Admin panel</a>
            <a href="<?php echo url('logout'); ?>" class="db-tab db-tab--logout">Sign out</a>
        </nav>
        <?php else: ?>
        <nav class="db-tabs">
            <a href="<?php echo url('logout'); ?>" class="db-tab db-tab--logout">Sign out</a>
        </nav>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="db-alert db-alert--success">✓ <?= htmlspecialchars($_SESSION['flash_success']) ?><?php unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>

        <?php if (!$member): ?>
        <!-- Not yet a member -->
        <?php if ($auth->isAdmin()): ?>
        <div class="db-grid" style="margin-top:.5rem">
            <a href="<?php echo url('admin'); ?>" class="db-action-card db-action-card--admin">
                <div class="db-action-icon">⚙️</div>
                <div class="db-action-title">Admin panel</div>
                <div class="db-action-desc">Manage members, pages, settings and modules</div>
            </a>
            <a href="<?php echo url('membership'); ?>" class="db-action-card">
                <div class="db-action-icon">🏛️</div>
                <div class="db-action-title">View membership options</div>
                <div class="db-action-desc">Join as a member to access member benefits</div>
            </a>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:4rem 0;">
            <div style="font-size:3rem;margin-bottom:1rem">🏛️</div>
            <h2 style="margin-bottom:1rem">You're not a member yet</h2>
            <p style="color:var(--grey-text);margin-bottom:2rem">Join <?= htmlspecialchars(App::settings()->get('association_name','')) ?> to access member benefits, events and the directory.</p>
            <a href="<?php echo url('membership'); ?>" class="btn btn-primary" style="font-size:1.1rem;padding:.9rem 2.5rem">View membership options</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Quick actions grid -->
        <div class="db-grid" style="margin-top:.5rem">

            <a href="<?php echo url('dashboard/membership'); ?>" class="db-action-card">
                <div class="db-action-icon">🏷️</div>
                <div class="db-action-title">My Membership</div>
                <div class="db-action-desc">View status, renewal dates and payment history</div>
            </a>

            <a href="<?php echo url('dashboard/profile'); ?>" class="db-action-card">
                <div class="db-action-icon">✏️</div>
                <div class="db-action-title">Edit Profile</div>
                <div class="db-action-desc">Update your contact details and directory listing</div>
            </a>

            <?php if ($auth->isAdmin()): ?>
            <a href="<?php echo url('admin'); ?>" class="db-action-card db-action-card--admin">
                <div class="db-action-icon">⚙️</div>
                <div class="db-action-title">Admin panel</div>
                <div class="db-action-desc">Manage members, pages, settings and modules</div>
            </a>
            <?php endif; ?>

            <a href="<?php echo url('dashboard/tickets'); ?>" class="db-action-card">
                <div class="db-action-icon">🎫</div>
                <div class="db-action-title">Support</div>
                <div class="db-action-desc">Open or track a support request</div>
            </a>

            <?php if ($member['membership_status'] === 'active'): ?>
            <a href="<?php echo url('membership'); ?>" class="db-action-card">
                <div class="db-action-icon">👥</div>
                <div class="db-action-title">Member directory</div>
                <div class="db-action-desc">Browse and connect with other members</div>
            </a>
            <?php endif; ?>

        </div>

        <!-- Membership status strip -->
        <div class="db-card" style="margin-top:1.5rem;display:flex;gap:2rem;align-items:center;flex-wrap:wrap">
            <div>
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--grey-text);margin-bottom:.3rem">Tier</div>
                <div style="font-weight:700"><?= htmlspecialchars($tier['name'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--grey-text);margin-bottom:.3rem">Status</div>
                <div style="font-weight:700;color:<?= $statusColour ?>"><?= htmlspecialchars($lang->get('membership.status.' . $member['membership_status'])) ?></div>
            </div>
            <?php if ($member['expires_at']): ?>
            <div>
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--grey-text);margin-bottom:.3rem">Expires</div>
                <div style="font-weight:700"><?= date('j M Y', strtotime($member['expires_at'])) ?></div>
            </div>
            <?php endif; ?>
            <div style="margin-left:auto">
                <?php if (in_array($member['membership_status'], ['expired','cancelled'])): ?>
                <a href="<?php echo url('dashboard/renew'); ?>" class="btn btn-primary btn-sm">Renew now</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
.db-action-card { display:flex;flex-direction:column;gap:.4rem;background:#fff;border-radius:14px;padding:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.06);text-decoration:none;color:var(--text);border:2px solid transparent;transition:border-color .15s,box-shadow .15s; }
.db-action-card:hover { border-color:var(--primary);box-shadow:0 4px 16px rgba(0,0,0,.1);text-decoration:none; }
.db-action-card--admin { border-color:var(--secondary); }
.db-action-card--admin:hover { border-color:var(--secondary);box-shadow:0 4px 16px rgba(0,0,0,.1); }
.db-action-icon { font-size:1.75rem; }
.db-action-title { font-weight:700;font-size:1rem; }
.db-action-desc { font-size:.83rem;color:var(--grey-text);line-height:1.4; }
.db-tab--admin { color:var(--secondary);font-weight:700; }
.db-tab--admin:hover { color:var(--secondary); }
</style>

<?php require __DIR__ . '/partials/dashboard-styles.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
