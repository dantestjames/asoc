<?php
/**
 * Admin Dashboard
 *
 * The first thing admins see. Stats overview and quick links.
 */

use ASOC\Core\App;

$lang = App::lang();
$db   = App::db();

// Stats
$stats = [
    'total_members'   => $db->count('members'),
    'active_members'  => $db->count('members', ['membership_status' => 'active']),
    'pending_members' => $db->count('members', ['approval_status' => 'pending']),
    'expired_members' => $db->count('members', ['membership_status' => 'expired']),
];

// Recent activity
$recentActivity = $db->all('activity_log', [], '*', 'created_at DESC', 10);

// Members expiring in next 30 days
$thirtyDays = date('Y-m-d', strtotime('+30 days'));
$expiringSoon = $db->query(
    "SELECT m.*, u.email, u.first_name, u.last_name
     FROM asoc_members m
     JOIN asoc_users u ON u.id = m.user_id
     WHERE m.membership_status = 'active'
     AND m.expires_at IS NOT NULL
     AND m.expires_at <= :cutoff
     ORDER BY m.expires_at ASC
     LIMIT 5",
    ['cutoff' => $thirtyDays]
)->fetchAll();

$pageTitle = $lang->get('admin.dashboard');
require __DIR__ . '/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label"><?= htmlspecialchars($lang->get('admin.members')) ?></div>
        <div class="stat-value"><?= number_format($stats['total_members']) ?></div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-label"><?= htmlspecialchars($lang->get('admin.status_active')) ?></div>
        <div class="stat-value"><?= number_format($stats['active_members']) ?></div>
    </div>
    <div class="stat-card stat-card--amber">
        <div class="stat-label"><?= htmlspecialchars($lang->get('admin.pending_approval')) ?></div>
        <div class="stat-value"><?= number_format($stats['pending_members']) ?></div>
        <?php if ($stats['pending_members'] > 0): ?>
            <a href="<?php echo url('admin/members/pending'); ?>" class="stat-link">Review &rarr;</a>
        <?php endif; ?>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-label"><?= htmlspecialchars($lang->get('membership.status.expired')) ?></div>
        <div class="stat-value"><?= number_format($stats['expired_members']) ?></div>
    </div>
</div>

<div class="dash-grid">
    <div class="card">
        <h3><?= htmlspecialchars($lang->get('admin.members_expiring')) ?></h3>
        <?php if (empty($expiringSoon)): ?>
            <p class="empty">Nobody is expiring soon.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th><?= htmlspecialchars($lang->get('admin.name')) ?></th><th><?= htmlspecialchars($lang->get('admin.member_number')) ?></th><th><?= htmlspecialchars($lang->get('admin.expires')) ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoon as $m): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url('admin/members/' . $m['id']); ?>"><?= htmlspecialchars(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) ?: $m['email']) ?></a>
                        </td>
                        <td class="mono"><?= htmlspecialchars($m['membership_number']) ?></td>
                        <td><?= date('j M Y', strtotime($m['expires_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3><?= htmlspecialchars($lang->get('admin.recent_activity')) ?></h3>
        <?php if (empty($recentActivity)): ?>
            <p class="empty">No activity yet.</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($recentActivity as $log): ?>
                    <li>
                        <span class="activity-action"><?= htmlspecialchars($log['action']) ?></span>
                        <span class="activity-desc"><?= htmlspecialchars($log['description'] ?? '') ?></span>
                        <span class="activity-time"><?= date('j M, g:i a', strtotime($log['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: var(--surface);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border-left: 4px solid var(--secondary);
}
.stat-card--green { border-left-color: #059669; }
.stat-card--amber { border-left-color: #D97706; }
.stat-card--red { border-left-color: #DC2626; }
.stat-label {
    font-size: 0.8rem;
    color: var(--grey-text);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
.stat-value { font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; }
.stat-link { font-size: 0.85rem; margin-top: 0.5rem; display: inline-block; font-weight: 600; }
.dash-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 1.5rem;
}
.card h3 { font-size: 1rem; margin-bottom: 1rem; font-weight: 600; }
.card table { box-shadow: none; }
.card th { background: transparent; }
.empty { color: var(--grey-text); padding: 1rem 0; font-size: 0.9rem; }
.mono { font-family: ui-monospace, monospace; font-size: 0.85rem; }
.activity-list { list-style: none; }
.activity-list li {
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--grey-light);
    font-size: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.activity-list li:last-child { border-bottom: none; }
.activity-action { font-weight: 600; color: var(--text); }
.activity-desc { color: var(--grey-text); font-size: 0.8rem; }
.activity-time { color: var(--grey-text); font-size: 0.75rem; }
@media (max-width: 1000px) {
    .dash-grid { grid-template-columns: 1fr; }
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
