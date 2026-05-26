<?php
use ASOC\Core\App;
use ASOC\Core\Config;

$lang     = App::lang();
$settings = App::settings();
$csrf     = App::csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validate($_POST['_csrf'] ?? '')) {
        $_SESSION['flash_error'] = $lang->get('error.csrf');
    } else {
        $tab = $_POST['tab'] ?? 'general';
        $keys = match($tab) {
            'general'    => ['association_name','association_short_name','association_abn',
                             'association_email','association_phone',
                             'association_address_physical','association_address_postal',
                             'site_hero_image',
                             'site_tagline','app_url'],
            'membership' => ['member_approval_method','default_auto_renew',
                             'renewal_reminder_days','membership_terms'],
            'email'      => ['mail_driver','mail_host','mail_port','mail_username',
                             'mail_password','mail_from_address','mail_from_name','mail_encryption'],
            default      => [],
        };
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $settings->set($key, trim($_POST[$key]));
            }
        }
        // Special: update APP_URL in .env if changed
        if ($tab === 'general' && !empty($_POST['app_url'])) {
            $envPath = dirname(__DIR__, 2) . '/asoc-system/../../.env';
            if (file_exists($envPath)) {
                $env = file_get_contents($envPath);
                $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . trim($_POST['app_url']), $env);
                file_put_contents($envPath, $env);
                Config::reset();
            }
        }
        $_SESSION['flash_success'] = 'Settings saved.';
        header('Location: ' . url('admin/settings?tab=' . $tab));
        exit;
    }
}

$activeTab = $_GET['tab'] ?? 'general';
$pageTitle = $lang->get('admin.settings');
require __DIR__ . '/header.php';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?><?php unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?><?php unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="settings-tabs">
    <?php foreach (['general' => 'General', 'membership' => 'Membership', 'email' => 'Email / SMTP'] as $tab => $label): ?>
    <a href="<?php echo url('admin/settings?tab=' . $tab); ?>"
       class="settings-tab <?= $activeTab === $tab ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>

<form method="POST" action="<?php echo url('admin/settings'); ?>" class="settings-form">
    <input type="hidden" name="_csrf" value="<?= $csrf->token() ?>">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">

    <?php if ($activeTab === 'general'): ?>

    <fieldset><legend>Association details</legend>
        <div class="row">
            <div class="field"><label>Association name *</label>
                <input type="text" name="association_name" value="<?= htmlspecialchars($settings->get('association_name','')) ?>" required></div>
            <div class="field"><label>Short name / abbreviation</label>
                <input type="text" name="association_short_name" value="<?= htmlspecialchars($settings->get('association_short_name','')) ?>"></div>
        </div>
        <div class="row">
            <div class="field"><label>ABN</label>
                <input type="text" name="association_abn" value="<?= htmlspecialchars($settings->get('association_abn','')) ?>"></div>
            <div class="field"><label>Contact email</label>
                <input type="email" name="association_email" value="<?= htmlspecialchars($settings->get('association_email','')) ?>"></div>
            <div class="field"><label>Phone</label>
                <input type="text" name="association_phone" value="<?= htmlspecialchars($settings->get('association_phone','')) ?>"></div>
        </div>
        <div class="field"><label>Physical address</label>
            <textarea name="association_address_physical" rows="2"><?= htmlspecialchars($settings->get('association_address_physical','')) ?></textarea></div>
        <div class="field"><label>Postal address</label>
            <textarea name="association_address_postal" rows="2"><?= htmlspecialchars($settings->get('association_address_postal','')) ?></textarea></div>
    </fieldset>

    <fieldset><legend>Site</legend>
        <div class="field"><label>Site tagline</label>
            <input type="text" name="site_tagline" value="<?= htmlspecialchars($settings->get('site_tagline','')) ?>"></div>
        <div class="field">
            <label>Default page hero image</label>
            <div style="display:flex;gap:.5rem;align-items:center">
                <input type="url" name="site_hero_image" id="hero-img-input"
                       value="<?= htmlspecialchars($settings->get('site_hero_image','')) ?>"
                       placeholder="https://… paste image URL or pick from media"
                       style="flex:1">
                <button type="button" class="btn btn-sm" onclick="pickHeroImg()">📁 Media</button>
            </div>
            <?php $hi = $settings->get('site_hero_image',''); if ($hi): ?>
            <div style="margin-top:.5rem;border-radius:8px;overflow:hidden;max-height:100px">
                <img id="hero-img-prev" src="<?= htmlspecialchars($hi) ?>" style="width:100%;height:100px;object-fit:cover">
            </div>
            <?php else: ?>
            <div id="hero-img-prev-wrap" style="display:none;margin-top:.5rem;border-radius:8px;overflow:hidden;max-height:100px">
                <img id="hero-img-prev" src="" style="width:100%;height:100px;object-fit:cover">
            </div>
            <?php endif; ?>
            <p class="field-hint">Used as the background on all page hero sections (News, Events, Directory, Membership, etc.). Set via the Page Builder for the home page separately.</p>
        </div>
        <div class="field"><label>Application URL</label>
            <input type="url" name="app_url" value="<?= htmlspecialchars(Config::appUrl()) ?>">
            <div class="field-help">Full URL including any subfolder — e.g. https://asoc.com.au/iccwa. Changing this also updates .env.</div>
        </div>
    </fieldset>

    <?php elseif ($activeTab === 'membership'): ?>

    <fieldset><legend>Approval</legend>
        <div class="field"><label>New member approval method</label>
            <select name="member_approval_method">
                <?php foreach ([
                    'none'        => 'No approval required (instant activation)',
                    'admin_only'  => 'Admin approval only',
                    'majority'    => 'Board majority vote',
                    'quorum'      => 'Board quorum vote',
                ] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $settings->get('member_approval_method','admin_only') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </fieldset>

    <fieldset><legend>Renewals</legend>
        <div class="field-checkbox">
            <input type="checkbox" id="default_auto_renew" name="default_auto_renew" value="1"
                   <?= $settings->get('default_auto_renew','1') === '1' ? 'checked' : '' ?>>
            <label for="default_auto_renew">Auto-renewal on by default for new members</label>
        </div>
        <div class="field"><label>Send renewal reminder (days before expiry)</label>
            <input type="number" name="renewal_reminder_days" min="1" max="90"
                   value="<?= htmlspecialchars($settings->get('renewal_reminder_days','30')) ?>">
        </div>
    </fieldset>

    <fieldset><legend>Membership terms (shown on registration form)</legend>
        <div class="field">
            <textarea name="membership_terms" rows="6"><?= htmlspecialchars($settings->get('membership_terms','')) ?></textarea>
        </div>
    </fieldset>

    <?php elseif ($activeTab === 'email'): ?>

    <fieldset><legend>SMTP settings</legend>
        <div class="row">
            <div class="field"><label>Mail driver</label>
                <select name="mail_driver">
                    <option value="smtp" <?= $settings->get('mail_driver','smtp') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                    <option value="sendmail" <?= $settings->get('mail_driver','') === 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                </select>
            </div>
            <div class="field"><label>Encryption</label>
                <select name="mail_encryption">
                    <option value="tls" <?= $settings->get('mail_encryption','tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= $settings->get('mail_encryption','') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value=""    <?= $settings->get('mail_encryption','') === '' ? 'selected' : '' ?>>None</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="field"><label>SMTP host</label>
                <input type="text" name="mail_host" value="<?= htmlspecialchars($settings->get('mail_host','')) ?>"></div>
            <div class="field"><label>Port</label>
                <input type="number" name="mail_port" value="<?= htmlspecialchars($settings->get('mail_port','587')) ?>"></div>
        </div>
        <div class="row">
            <div class="field"><label>Username</label>
                <input type="text" name="mail_username" value="<?= htmlspecialchars($settings->get('mail_username','')) ?>"></div>
            <div class="field"><label>Password</label>
                <input type="password" name="mail_password" value="" placeholder="Leave blank to keep existing">
                </div>
        </div>
        <div class="row">
            <div class="field"><label>From address</label>
                <input type="email" name="mail_from_address" value="<?= htmlspecialchars($settings->get('mail_from_address','')) ?>"></div>
            <div class="field"><label>From name</label>
                <input type="text" name="mail_from_name" value="<?= htmlspecialchars($settings->get('mail_from_name','')) ?>"></div>
        </div>
    </fieldset>

    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang->get('form.save')) ?></button>
    </div>
</form>

<style>
.settings-tabs { display:flex;gap:.25rem;border-bottom:1px solid var(--border);margin-bottom:1.5rem; }
.settings-tab { padding:.6rem 1rem;font-size:.9rem;text-decoration:none;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px; }
.settings-tab.active { color:var(--primary);border-bottom-color:var(--primary);font-weight:600; }
.settings-form fieldset { border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem; }
.settings-form legend { padding:0 .5rem;font-weight:600; }
.field { margin-bottom:1rem; }
.field label { display:block;font-size:.85rem;font-weight:600;margin-bottom:.3rem; }
.field input,.field select,.field textarea { width:100%;padding:.6rem .9rem;border:1px solid var(--border);border-radius:8px;font-size:.95rem;font-family:inherit; }
.field textarea { resize:vertical; }
.field-help { font-size:.78rem;color:var(--muted);margin-top:.2rem; }
.field-checkbox { display:flex;gap:.5rem;align-items:center;margin-bottom:.75rem; }
.field-checkbox input { width:auto; }
.field-checkbox label { margin:0;font-weight:normal; }
.row { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem; }
.form-actions { margin-top:1rem; }
.alert { padding:.875rem 1rem;border-radius:8px;margin-bottom:1.5rem; }
.alert-success { background:#D1FAE5;color:#065F46; }
.alert-error { background:#FEE2E2;color:#991B1B; }
</style>

<!-- Hero image media picker -->
<div id="hero-media-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:88%;max-width:860px;max-height:84vh;overflow:hidden;display:flex;flex-direction:column">
        <div style="padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between">
            <strong>Select hero image</strong>
            <button onclick="document.getElementById('hero-media-modal').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:1.1rem">✕</button>
        </div>
        <div id="hero-media-grid" style="padding:1rem;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.5rem;flex:1"></div>
        <div style="padding:.6rem 1.25rem;border-top:1px solid #e2e8f0;font-size:.78rem;color:#9ca3af;display:flex;justify-content:space-between">
            <span>Click to select</span>
            <a href="<?= url('admin/media') ?>" target="_blank" style="color:#3b82f6;font-weight:600;text-decoration:none">Upload ↗</a>
        </div>
    </div>
</div>
<script>
document.getElementById('hero-img-input').addEventListener('input', function() {
    var prev = document.getElementById('hero-img-prev');
    var wrap = document.getElementById('hero-img-prev-wrap');
    if (this.value) { if(prev){prev.src=this.value;} if(wrap)wrap.style.display=''; }
    else { if(wrap)wrap.style.display='none'; }
});
function pickHeroImg() {
    document.getElementById('hero-media-modal').style.display = 'flex';
    var grid = document.getElementById('hero-media-grid');
    if (grid.children.length) return;
    grid.innerHTML = '<p style="padding:1.5rem;color:#9ca3af;text-align:center;grid-column:1/-1">Loading…</p>';
    fetch('<?= url('admin/media/json') ?>?type=image')
        .then(function(r){ return r.json(); })
        .then(function(items) {
            grid.innerHTML = '';
            items.forEach(function(m) {
                var el = document.createElement('div');
                el.style.cssText = 'aspect-ratio:16/9;border-radius:6px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color .12s';
                el.innerHTML = '<img src="'+m.thumb+'" style="width:100%;height:100%;object-fit:cover" loading="lazy">';
                el.addEventListener('mouseenter', function(){ el.style.borderColor='#3b82f6'; });
                el.addEventListener('mouseleave', function(){ el.style.borderColor='transparent'; });
                el.addEventListener('click', function() {
                    document.getElementById('hero-img-input').value = m.url;
                    var prev = document.getElementById('hero-img-prev');
                    var wrap = document.getElementById('hero-img-prev-wrap');
                    if(prev) prev.src = m.url;
                    if(wrap) wrap.style.display = '';
                    document.getElementById('hero-media-modal').style.display = 'none';
                });
                grid.appendChild(el);
            });
        });
}
document.getElementById('hero-media-modal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
</script>
<?php require __DIR__ . '/footer.php'; ?>
