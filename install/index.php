<?php
/**
 * ASOC Install Wizard
 *
 * Runs once when the system is first deployed. Walks through:
 *   1. Pre-flight checks (PHP version, extensions, file permissions)
 *   2. Database connection details
 *   3. Generate encryption key
 *   4. Run schema and seeds
 *   5. Create admin user (or use the seeded one)
 *   6. Mark installed (drops storage/installed.lock)
 *
 * After install, this file blocks itself by checking the lock file.
 */

declare(strict_types=1);

// Auto-detect the base path (e.g., /iccwa/) from where this script lives
// SCRIPT_NAME = /iccwa/install/index.php → dirname = /iccwa/install/ → dirname = /iccwa/
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (empty($basePath)) {
    $basePath = ''; // Running at root, not in a subfolder
}

// In the flat layout:
//   /                 = $rootPath  (document root, where this install/ folder lives)
//   /asoc-system      = $systemPath  (migrations, seeds, storage all live in here)
//   /.env             = config file at the root
//   /.env.example     = template at the root
$rootPath        = dirname(__DIR__);
$systemPath      = $rootPath . '/asoc-system';
$installLockPath = $systemPath . '/storage/installed.lock';
$envPath         = $rootPath . '/.env';
$envExamplePath  = $rootPath . '/.env.example';

// Already installed? Block — BUT: allow the installer to continue if it's currently installing
// (the $step might not be set yet if this is the first page load, so check $_GET and $_POST)
$isInstallerActive = isset($_GET['step']) || isset($_POST['step']) || !empty($_SESSION['install_db']);

if (file_exists($installLockPath) && !$isInstallerActive) {
    http_response_code(403);
    die('ASOC is already installed. The lock file is at: <code>' . htmlspecialchars($installLockPath) . '</code><br><br>To reinstall, delete that file and refresh this page. This will not erase your data.');
}

// Start session for multi-step wizard
session_start();

$step = $_GET['step'] ?? '1';
$errors = [];
$success = '';

// ============================================================
// STEP HANDLERS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 2: Save database config and test connection
    if ($step === '2') {
        $dbConfig = [
            'host'     => trim($_POST['db_host'] ?? 'localhost'),
            'port'     => (int)($_POST['db_port'] ?? 3306),
            'database' => trim($_POST['db_database'] ?? ''),
            'username' => trim($_POST['db_username'] ?? ''),
            'password' => $_POST['db_password'] ?? '',
        ];

        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $_SESSION['install_db'] = $dbConfig;
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not connect to the database. ' . $e->getMessage();
        }
    }

    // STEP 3: Run installation
    if ($step === '3') {
        if (!isset($_SESSION['install_db'])) {
            header('Location: ?step=2');
            exit;
        }

        $dbConfig = $_SESSION['install_db'];

        try {
            // Connect
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Run schema (migrations live inside asoc-system/)
            $schemaPath = $systemPath . '/database/migrations/001_core_schema.sql';
            if (!file_exists($schemaPath)) {
                throw new Exception("Cannot find schema file at: $schemaPath");
            }
            $schema = file_get_contents($schemaPath);
            if ($schema === false) {
                throw new Exception("Cannot read schema file (permission denied): $schemaPath");
            }
            $pdo->exec($schema);

            // Run seeds
            $seedsPath = $systemPath . '/database/seeds/001_iccwa_initial_data.sql';
            if (!file_exists($seedsPath)) {
                throw new Exception("Cannot find seeds file at: $seedsPath");
            }
            $seeds = file_get_contents($seedsPath);
            if ($seeds === false) {
                throw new Exception("Cannot read seeds file (permission denied): $seedsPath");
            }
            $pdo->exec($seeds);

            // Generate encryption key
            $encryptionKey = bin2hex(random_bytes(32));

            // Write .env — load from .env.example
            if (!file_exists($envExamplePath)) {
                throw new Exception("Cannot find .env.example at: $envExamplePath");
            }
            
            $envContent = file_get_contents($envExamplePath);
            if ($envContent === false) {
                throw new Exception("Cannot read .env.example (permission denied): $envExamplePath");
            }
            
            $envContent = preg_replace('/^DB_HOST=.*/m', "DB_HOST={$dbConfig['host']}", $envContent);
            $envContent = preg_replace('/^DB_PORT=.*/m', "DB_PORT={$dbConfig['port']}", $envContent);
            $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbConfig['database']}", $envContent);
            $envContent = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$dbConfig['username']}", $envContent);
            $envContent = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$dbConfig['password']}", $envContent);
            $envContent = preg_replace('/^APP_ENCRYPTION_KEY=.*/m', "APP_ENCRYPTION_KEY=$encryptionKey", $envContent);

            // Auto-detect APP_URL from the current request
            // install/index.php lives one level below the app root
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // SCRIPT_NAME is /iccwa/install/index.php — strip /install/index.php to get /iccwa
            $appPath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php')), '/');
            $appUrl  = $scheme . '://' . $host . $appPath;
            $envContent = preg_replace('/^APP_URL=.*/m', "APP_URL=$appUrl", $envContent);

            file_put_contents($envPath, $envContent);
            chmod($envPath, 0600);

            // Update admin user with custom email/password if provided
            $adminEmail = trim($_POST['admin_email'] ?? 'secretary@iccwa.net.au');
            $adminPassword = $_POST['admin_password'] ?? '';

            if (!empty($adminPassword) && strlen($adminPassword) >= 10) {
                $hash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "UPDATE asoc_users SET email = ?, password_hash = ? WHERE role = 'admin' LIMIT 1"
                );
                $stmt->execute([$adminEmail, $hash]);
            }

            // Drop the install lock
            $lockData = json_encode([
                'installed_at' => date('c'),
                'version'      => '1.0.0',
            ]);
            
            $lockDir = dirname($installLockPath);
            if (!is_dir($lockDir)) {
                throw new Exception("Lock file directory does not exist: $lockDir");
            }
            if (!is_writable($lockDir)) {
                throw new Exception("Lock file directory is not writable: $lockDir");
            }
            
            $result = file_put_contents($installLockPath, $lockData);
            if ($result === false) {
                throw new Exception("Cannot write lock file to: $installLockPath (permission denied or disk full)");
            }
            
            chmod($installLockPath, 0644);

            unset($_SESSION['install_db']);
            header('Location: ?step=4');
            exit;

        } catch (\Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}

// ============================================================
// PRE-FLIGHT CHECKS
// ============================================================
function preflightChecks(): array {
    $checks = [];

    $checks['PHP version 8.2 or higher'] = version_compare(PHP_VERSION, '8.2.0', '>=');
    $checks['PDO extension loaded'] = extension_loaded('pdo');
    $checks['PDO MySQL driver'] = extension_loaded('pdo_mysql');
    $checks['OpenSSL extension'] = extension_loaded('openssl');
    $checks['JSON extension'] = extension_loaded('json');
    $checks['Mbstring extension'] = extension_loaded('mbstring');
    $checks['GD or Imagick'] = extension_loaded('gd') || extension_loaded('imagick');

    $rootPath   = dirname(__DIR__);
    $systemPath = $rootPath . '/asoc-system';

    $checks['asoc-system/storage/ writable'] = is_writable($systemPath . '/storage');
    $checks['Document root writable (for .env)'] = is_writable($rootPath);
    $checks['uploads/ directory writable']   = is_dir($rootPath . '/uploads')
        ? is_writable($rootPath . '/uploads')
        : @mkdir($rootPath . '/uploads', 0755, true);

    return $checks;
}

$checks = preflightChecks();
$allChecksPassed = !in_array(false, $checks, true);
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ASOC Installer — ICCWA</title>
<style>
:root {
    --bg: #FFFFFF;
    --text: #1A1A1A;
    --primary: #AA0101;
    --secondary: #15AAFF;
    --dark: #20314F;
    --grey-light: #F5F5F7;
    --grey-mid: #D1D5DB;
    --grey-text: #6B7280;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, "Helvetica Neue", Arial, sans-serif;
    background: var(--grey-light);
    color: var(--text);
    line-height: 1.6;
    padding: 2rem 1rem;
}
.container {
    max-width: 640px;
    margin: 0 auto;
    background: var(--bg);
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    overflow: hidden;
}
.header {
    background: var(--dark);
    color: white;
    padding: 2rem;
    text-align: center;
}
.header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}
.header p { opacity: 0.85; margin-top: 0.25rem; font-size: 0.9rem; }
.steps {
    display: flex;
    background: var(--grey-light);
    border-bottom: 1px solid var(--grey-mid);
}
.step-tab {
    flex: 1;
    padding: 1rem;
    text-align: center;
    font-size: 0.85rem;
    color: var(--grey-text);
    border-right: 1px solid var(--grey-mid);
}
.step-tab:last-child { border-right: none; }
.step-tab.active {
    background: var(--bg);
    color: var(--primary);
    font-weight: 600;
}
.step-tab.done { color: var(--secondary); }
.content { padding: 2rem; }
.content h2 { margin-bottom: 1rem; font-size: 1.25rem; }
.content p { margin-bottom: 1rem; color: var(--grey-text); }
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}
.alert-error {
    background: #FEE2E2;
    color: #991B1B;
    border-left: 4px solid #DC2626;
}
.alert-success {
    background: #D1FAE5;
    color: #065F46;
    border-left: 4px solid #059669;
}
.alert-info {
    background: #DBEAFE;
    color: #1E40AF;
    border-left: 4px solid var(--secondary);
}
.check-list { list-style: none; }
.check-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--grey-light);
    display: flex;
    justify-content: space-between;
}
.check-list li:last-child { border-bottom: none; }
.check-pass { color: #059669; font-weight: 600; }
.check-fail { color: #DC2626; font-weight: 600; }
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}
.field { margin-bottom: 1rem; }
.field-help {
    font-size: 0.8rem;
    color: var(--grey-text);
    margin-top: 0.25rem;
}
input[type="text"], input[type="email"], input[type="password"], input[type="number"] {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--grey-mid);
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
}
input:focus { outline: 2px solid var(--secondary); outline-offset: 2px; }
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-family: inherit;
}
.btn:hover { background: #880101; }
.btn-secondary {
    background: var(--grey-mid);
    color: var(--text);
}
.row { display: flex; gap: 1rem; }
.row .field { flex: 1; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>ASOC Installer</h1>
        <p>Setting up the Indonesian Chamber of Commerce WA platform</p>
    </div>
    <div class="steps">
        <div class="step-tab <?= $step === '1' ? 'active' : ($step > 1 ? 'done' : '') ?>">1. Pre-flight</div>
        <div class="step-tab <?= $step === '2' ? 'active' : ($step > 2 ? 'done' : '') ?>">2. Database</div>
        <div class="step-tab <?= $step === '3' ? 'active' : ($step > 3 ? 'done' : '') ?>">3. Configure</div>
        <div class="step-tab <?= $step === '4' ? 'active' : '' ?>">4. Done</div>
    </div>
    <div class="content">

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if ($step === '1'): ?>
        <h2>Pre-flight checks</h2>
        <p>Before we get started, let's check the server has what it needs.</p>
        <ul class="check-list">
            <?php foreach ($checks as $name => $passed): ?>
                <li>
                    <span><?= htmlspecialchars($name) ?></span>
                    <span class="<?= $passed ? 'check-pass' : 'check-fail' ?>">
                        <?= $passed ? '✓ Pass' : '✗ Fail' ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($allChecksPassed): ?>
            <p style="margin-top: 1.5rem;">All checks passed. Ready to continue.</p>
            <a href="?step=2" class="btn">Continue</a>
        <?php else: ?>
            <div class="alert alert-error" style="margin-top: 1.5rem;">
                Some checks failed. Fix the items above before continuing. If a directory needs to be writable, use <code>chmod 755</code> on it.
            </div>
        <?php endif; ?>

    <?php elseif ($step === '2'): ?>
        <h2>Database connection</h2>
        <p>Enter the connection details for the MySQL or MariaDB database. The database must already exist (create it via cPanel or your hosting control panel before continuing).</p>
        <form method="POST">
            <div class="row">
                <div class="field">
                    <label for="db_host">Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="field" style="flex: 0 0 100px;">
                    <label for="db_port">Port</label>
                    <input type="number" id="db_port" name="db_port" value="3306" required>
                </div>
            </div>
            <div class="field">
                <label for="db_database">Database name</label>
                <input type="text" id="db_database" name="db_database" required>
            </div>
            <div class="field">
                <label for="db_username">Username</label>
                <input type="text" id="db_username" name="db_username" required>
            </div>
            <div class="field">
                <label for="db_password">Password</label>
                <input type="password" id="db_password" name="db_password">
            </div>
            <button type="submit" class="btn">Test connection &amp; continue</button>
        </form>

    <?php elseif ($step === '3'): ?>
        <h2>Final configuration</h2>
        <p>Set up the admin account. The default email is the ICCWA secretary address. You can change it now or later in the admin panel.</p>
        <div class="alert alert-info">
            An encryption key will be generated automatically. This key protects payment gateway credentials and must never change once set, so back up your <code>.env</code> file somewhere safe.
        </div>
        <form method="POST">
            <div class="field">
                <label for="admin_email">Admin email</label>
                <input type="email" id="admin_email" name="admin_email" value="secretary@iccwa.net.au" required>
            </div>
            <div class="field">
                <label for="admin_password">Admin password</label>
                <input type="password" id="admin_password" name="admin_password" minlength="10" required>
                <div class="field-help">Minimum 10 characters. Use something strong, this account has full access.</div>
            </div>
            <button type="submit" class="btn">Install ASOC</button>
        </form>

    <?php elseif ($step === '4'): ?>
        <h2>Installation complete</h2>
        <div class="alert alert-success">
            ASOC is now installed and ready to use.
        </div>
        
        <!-- Debug: Show lock file status -->
        <div style="background: #f0f0f0; padding: 1rem; margin-bottom: 1.5rem; font-family: monospace; font-size: 0.85rem; border-radius: 4px;">
            <strong>Debug Info:</strong><br>
            Lock file path: <?php echo htmlspecialchars($installLockPath); ?><br>
            Lock file exists: <?php echo file_exists($installLockPath) ? 'YES' : 'NO'; ?><br>
            Lock file is readable: <?php echo file_exists($installLockPath) && is_readable($installLockPath) ? 'YES' : 'NO'; ?><br>
            basePath: <?php echo htmlspecialchars($basePath); ?><br>
            rootPath: <?php echo htmlspecialchars($rootPath); ?><br>
            systemPath: <?php echo htmlspecialchars($systemPath); ?>
        </div>
        
        <p>What to do next:</p>
        <ul style="margin-left: 1.5rem; margin-bottom: 1.5rem;">
            <li>Log in with your admin email and password</li>
            <li>Configure payment gateways in Admin → Financials → Gateways</li>
            <li>Customise the homepage in Admin → Pages</li>
            <li>Review the privacy policy and terms of use templates and edit to suit</li>
            <li>Delete or restrict access to the <code>/install/</code> directory</li>
        </ul>
        <a href="/iccwa/login" class="btn">Go to login</a>

    <?php endif; ?>

    </div>
</div>
</body>
</html>
