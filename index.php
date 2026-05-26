<?php
/**
 * ASOC — Public Entry Point
 *
 * Every public request hits this file. The .htaccess in this directory
 * rewrites all requests here. From here:
 *   1. Bootstrap loads config, db, auth, language, modules
 *   2. Router collects routes from core + every enabled module
 *   3. Router dispatches the matched route or returns 404
 *
 * If the system isn't installed yet, redirects to the installer.
 */

declare(strict_types=1);

// Force all errors to display AND log to a file we control
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Write fatal errors to a log we can read
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $msg = date('[Y-m-d H:i:s]') . " [$errno] $errstr in $errfile:$errline\n";
    @file_put_contents(__DIR__ . '/asoc-system/storage/logs/php_errors.log', $msg, FILE_APPEND);
    return false; // Let PHP handle it too
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = date('[Y-m-d H:i:s]') . " [FATAL] {$error['message']} in {$error['file']}:{$error['line']}\n";
        @file_put_contents(__DIR__ . '/asoc-system/storage/logs/php_errors.log', $msg, FILE_APPEND);
        if (!headers_sent()) {
            http_response_code(500);
            echo '<h1>500 Error</h1><pre>' . htmlspecialchars($msg) . '</pre>';
        }
    }
});

// In the flat layout, asoc-system/ lives alongside this file.
// __DIR__ = document root.
$rootPath        = __DIR__;
$systemPath      = $rootPath . '/asoc-system';
$envPath         = $rootPath . '/.env';
$installLockPath = $systemPath . '/storage/installed.lock';

// Bootstrap Config first — it reads APP_URL from .env and derives basePath.
// This is the single source of truth for all URL generation.
require_once $systemPath . '/core/Config.php';
\ASOC\Core\Config::getInstance($envPath);

if (!file_exists($installLockPath)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath   = \ASOC\Core\Config::basePath();

    if (str_contains($requestUri, '/login') || str_contains($requestUri, '/admin') || str_contains($requestUri, '/dashboard')) {
        http_response_code(503);
        die('<h1>Installation Required</h1><p>ASOC is not yet installed.</p><p><a href="' . htmlspecialchars(Config::basePath() . '/install/') . '">Go to installer</a></p>');
    }

    if (!str_starts_with($requestUri, $basePath . '/install')) {
        header('Location: ' . Config::basePath() . '/install/');
        exit;
    }
}

require_once $systemPath . '/core/bootstrap.php';

use ASOC\Core\App;
use ASOC\Core\Config;
use ASOC\Core\Router;

$router = new Router();
$router->setBasePath(Config::basePath());

/**
 * url() — global URL helper.
 *
 * Works anywhere in the app — views, controllers, closures, cron, emails.
 * Falls back to SCRIPT_NAME detection when APP_URL in .env has no path.
 */
function url(string $path = ''): string {
    $base = \ASOC\Core\Config::basePath();

    // If Config has no basePath (APP_URL = https://domain.com with no subfolder),
    // detect from SCRIPT_NAME so links work even before fix-app-url is run
    if ($base === '' && !empty($_SERVER['SCRIPT_NAME'])) {
        $detected = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($detected !== '' && $detected !== '.' && $detected !== '/') {
            $base = $detected;
        }
    }

    return $base . '/' . ltrim($path, '/');
}

// =====================================================
// MIDDLEWARE
// =====================================================
$router->registerMiddleware('auth', function () {
    if (!App::auth()->check()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . Config::url('login'));
        return false;
    }
});

$router->registerMiddleware('admin', function () {
    if (!App::auth()->isAdmin()) {
        http_response_code(403);
        echo '<h1>403 — Access denied</h1>';
        return false;
    }
});

$router->registerMiddleware('member', function () {
    $user = App::auth()->user();
    if (!$user || !in_array($user['role'], ['member', 'board', 'admin'], true)) {
        header('Location: ' . Config::url('membership'));
        return false;
    }
});

$router->registerMiddleware('csrf', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!App::csrf()->validate($token)) {
            http_response_code(419);
            echo App::lang()->get('error.csrf');
            return false;
        }
    }
});

// =====================================================
// CORE ROUTES
// =====================================================

// One-time URL fixer — visit /fix-app-url to write the correct APP_URL to .env
// Also seeds default pages if none exist
$router->get('/fix-app-url', function () {
    global $rootPath, $envPath;
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $path    = dirname($script);
    if ($path === '/' || $path === '.' || $path === '\\') $path = '';
    $appUrl  = $scheme . '://' . $host . rtrim($path, '/');

    // Write APP_URL to .env
    if (file_exists($envPath)) {
        $env = file_get_contents($envPath);
        $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . $appUrl, $env);
        file_put_contents($envPath, $env);
    }

    \ASOC\Core\Config::reset();
    \ASOC\Core\Config::getInstance($envPath);

    // Install email module schema tables
    $emailSchema = file_get_contents(__DIR__ . '/asoc-system/modules/email/schema.sql');
    if ($emailSchema) {
        foreach (array_filter(array_map('trim', explode(';', $emailSchema))) as $stmt) {
            if ($stmt) try { App::db()->query($stmt . ';'); } catch (\Throwable) {}
        }
    }
    App::db()->query("INSERT IGNORE INTO asoc_modules (module_slug,module_name,version,is_enabled,is_core) VALUES ('email','Email','1.0.0',1,0)");

    // Install CRM module schema tables
    $crmSchema = file_get_contents(__DIR__ . '/asoc-system/modules/crm/schema.sql');
    if ($crmSchema) {
        foreach (array_filter(array_map('trim', explode(';', $crmSchema))) as $stmt) {
            if ($stmt) try { App::db()->query($stmt . ';'); } catch (\Throwable) {}
        }
    }
    App::db()->query("INSERT IGNORE INTO asoc_modules (module_slug,module_name,version,is_enabled,is_core) VALUES ('crm','CRM','1.0.0',1,0)");

    // Ensure site_hero_image setting exists
    App::db()->query("INSERT IGNORE INTO asoc_settings (setting_key, setting_value, setting_group, setting_type) VALUES ('site_hero_image', '', 'general', 'string')");
    // Ensure articles module is registered and enabled
    App::db()->query(
        "INSERT IGNORE INTO asoc_modules (module_slug, module_name, version, is_enabled, is_core)
         VALUES ('articles', 'Articles', '1.0.0', 1, 0)"
    );
    App::db()->query("UPDATE asoc_modules SET is_enabled = 1 WHERE module_slug = 'articles'");

    // Ensure events module is registered and enabled
    App::db()->query(
        "INSERT IGNORE INTO asoc_modules (module_slug, module_name, version, is_enabled, is_core)
         VALUES ('events', 'Event Management', '1.0.0', 1, 0)"
    );
    App::db()->query("UPDATE asoc_modules SET is_enabled = 1 WHERE module_slug = 'events'");

    // Ensure media module is registered and enabled
    App::db()->query(
        "INSERT IGNORE INTO asoc_modules (module_slug, module_name, version, is_enabled, is_core)
         VALUES ('media', 'Media Manager', '1.0.0', 1, 1)"
    );

    // Ensure directory module is registered and enabled
    App::db()->query(
        "INSERT IGNORE INTO asoc_modules (module_slug, module_name, version, is_enabled, is_core)
         VALUES ('directory', 'Member Directory', '1.0.0', 1, 0)"
    );
    App::db()->query(
        "UPDATE asoc_modules SET is_enabled = 1, module_name = 'Member Directory'
         WHERE module_slug = 'directory'"
    );

    // Seed default pages if none exist
    $db = App::db();
    $pageCount = (int)$db->query('SELECT COUNT(*) FROM asoc_pages')->fetchColumn();
    $seeded = [];
    if ($pageCount === 0) {
        $adminId = (int)$db->query('SELECT id FROM asoc_users WHERE role = "admin" LIMIT 1')->fetchColumn();
        $adminId = $adminId ?: 1;
        $defaultPages = [
            ['slug' => 'home',    'title' => 'Home',    'is_homepage' => 1, 'sort_order' => 1],
            ['slug' => 'about',   'title' => 'About',   'is_homepage' => 0, 'sort_order' => 2],
            ['slug' => 'contact', 'title' => 'Contact', 'is_homepage' => 0, 'sort_order' => 3],
        ];
        foreach ($defaultPages as $p) {
            $db->insert('pages', [
                'slug'           => $p['slug'],
                'title'          => $p['title'],
                'content_blocks' => '[]',
                'status'         => 'published',
                'is_homepage'    => $p['is_homepage'],
                'requires_auth'  => 0,
                'sort_order'     => $p['sort_order'],
                'created_by'     => $adminId,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $seeded[] = $p['title'];
        }
    }

    echo '<h2>Fix complete</h2>';
    echo '<p>APP_URL set to: <strong>' . htmlspecialchars($appUrl) . '</strong></p>';
    echo '<p>basePath: <strong>' . htmlspecialchars(\ASOC\Core\Config::basePath()) . '</strong></p>';
    if ($seeded) {
        echo '<p>Pages seeded: ' . htmlspecialchars(implode(', ', $seeded)) . '</p>';
    } else {
        echo '<p>Pages already exist — not reseeded.</p>';
    }
    echo '<p><strong>All done. You can now use the admin normally.</strong></p>';
    echo '<p><a href="' . htmlspecialchars($appUrl . '/admin') . '">Go to admin</a></p>';
});


// Homepage — looks up the page marked is_homepage in DB
$router->get('/', function () {
    // 1. Try published homepage by flag
    $page = App::db()->find('pages', ['is_homepage' => 1, 'status' => 'published']);

    // 2. Admins can see draft homepage too
    if (!$page && App::auth()->isAdmin()) {
        $page = App::db()->find('pages', ['is_homepage' => 1]);
    }

    // 3. Fall back to slug='home' if no is_homepage flag set yet
    if (!$page) {
        $page = App::db()->find('pages', ['slug' => 'home', 'status' => 'published']);
        if ($page) {
            App::db()->update('pages', (int)$page['id'], ['is_homepage' => 1]);
            $page['is_homepage'] = 1;
        }
    }
    if (!$page && App::auth()->isAdmin()) {
        $page = App::db()->find('pages', ['slug' => 'home']);
        if ($page) {
            App::db()->update('pages', (int)$page['id'], ['is_homepage' => 1]);
            $page['is_homepage'] = 1;
        }
    }

    if ($page) {
        if (is_string($page['content_blocks'])) {
            $page['content_blocks'] = json_decode($page['content_blocks'], true) ?: [];
        }
        require __DIR__ . '/views/page.php';
    } else {
        require __DIR__ . '/views/welcome.php';
    }
});

// Auth routes
$router->get('/login', function () {
    // If already logged in, redirect to dashboard
    if (App::auth()->check()) {
        
        header('Location: ' . Config::url('dashboard'));
        exit;
    }
    
    // Check if this is a login form submission (GET with email param)
    if (!empty($_GET['email']) && !empty($_GET['password'])) {
        
        
        $email = $_GET['email'] ?? '';
        $password = $_GET['password'] ?? '';

        if (!App::csrf()->validate($_GET['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = App::lang()->get('error.csrf');
            header('Location: ' . Config::url('login'));
            exit;
        }

        try {
            // Attempt login
            $loginSuccess = App::auth()->login($email, $password);
            
            if ($loginSuccess) {
                // Login succeeded
                // Determine redirect URL
                $redirect = $_SESSION['redirect_after_login'] ?? (Config::url('dashboard'));
                unset($_SESSION['redirect_after_login']);
                
                header("Location: $redirect");
                exit;
            }
            
            // Login failed
            $_SESSION['flash_error'] = App::lang()->get('auth.invalid_credentials');
            
        } catch (\RuntimeException $e) {
            // Exception during login
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        // Redirect back to login on any failure
        header('Location: ' . Config::url('login'));
        exit;
    }
    
    // Show login form
    require __DIR__ . '/views/auth/login.php';
});

$router->post('/login', function () {
    
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!App::csrf()->validate($_POST['_csrf'] ?? '')) {
        $_SESSION['flash_error'] = App::lang()->get('error.csrf');
        header('Location: ' . Config::url('login'));
        exit;
    }

    try {
        // Attempt login
        $loginSuccess = App::auth()->login($email, $password);
        
        if ($loginSuccess) {
            // Login succeeded — user_id should now be in session
            error_log("LOGIN SUCCESS for $email, user_id = " . ($_SESSION['user_id'] ?? 'MISSING'));
            
            // Determine redirect URL
            $redirect = $_SESSION['redirect_after_login'] ?? (Config::url('dashboard'));
            unset($_SESSION['redirect_after_login']);
            
            error_log("Redirecting to: $redirect");
            header("Location: $redirect");
            exit;
        }
        
        // Login failed
        error_log("LOGIN FAILED for $email: invalid credentials");
        $_SESSION['flash_error'] = App::lang()->get('auth.invalid_credentials');
        
    } catch (\RuntimeException $e) {
        // Exception during login
        error_log("LOGIN EXCEPTION for $email: " . $e->getMessage());
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    // Redirect back to login on any failure
    header('Location: ' . Config::url('login'));
    exit;
});

$router->get('/set-language/{language}', function ($params) {
    
    
    $language = $params['language'] ?? 'en-AU';
    $lang = App::lang();
    
    // Validate the language code
    if (!$lang->isValid($language)) {
        $language = 'en-AU';
    }
    
    // Save to session
    $_SESSION['language'] = $language;
    
    // Redirect back to the referrer or home
    $referrer = $_SERVER['HTTP_REFERER'] ?? Config::url('');
    
    // Remove lang parameter from referrer to avoid duplication
    $referrer = preg_replace('/[?&]lang=[^&]*/', '', $referrer);
    
    // Add the new lang parameter
    $separator = str_contains($referrer, '?') ? '&' : '?';
    $redirect = $referrer . $separator . 'lang=' . urlencode($language);
    
    header('Location: ' . $redirect);
    exit;
});

$router->any('/logout', function () {
    App::auth()->logout();
    header('Location: ' . Config::url(''));
    exit;
});

$router->get('/dashboard', function () {
    require __DIR__ . '/views/dashboard.php';
}, ['auth']);

// Admin dashboard
$router->get('/admin', function () {
    require __DIR__ . '/views/admin/dashboard.php';
}, ['auth', 'admin']);

// Legal pages (placeholders - replace via page builder when ready)
$router->get('/privacy-policy', function () {
    require __DIR__ . '/views/privacy-policy.php';
});

$router->get('/terms-of-use', function () {
    require __DIR__ . '/views/terms-of-use.php';
});

// =====================================================
// MEDIA MANAGER ROUTES
// =====================================================
$router->get('/admin/media', function () {
    (new \ASOC\Modules\Media\AdminMediaController())->index();
}, ['auth', 'admin']);

$router->post('/admin/media/upload', function () {
    (new \ASOC\Modules\Media\AdminMediaController())->upload();
}, ['auth', 'admin']);

$router->get('/admin/media/json', function () {
    (new \ASOC\Modules\Media\AdminMediaController())->json();
}, ['auth', 'admin']);

$router->get('/admin/media/{id}/edit', function ($p) {
    (new \ASOC\Modules\Media\AdminMediaController())->edit($p);
}, ['auth', 'admin']);

$router->post('/admin/media/{id}/edit', function ($p) {
    (new \ASOC\Modules\Media\AdminMediaController())->save($p);
}, ['auth', 'admin']);

$router->post('/admin/media/{id}/delete', function ($p) {
    (new \ASOC\Modules\Media\AdminMediaController())->delete($p);
}, ['auth', 'admin']);

$router->get('/media/{filename}', function ($p) {
    (new \ASOC\Modules\Media\AdminMediaController())->serve($p);
});

// Admin settings
$router->any('/admin/settings', function () {
    require __DIR__ . '/views/admin/settings.php';
}, ['auth', 'admin']);

// Admin modules
$router->any('/admin/modules', function () {
    require __DIR__ . '/views/admin/modules.php';
}, ['auth', 'admin']);

// =====================================================
// PAGE BUILDER ROUTES — CSS served from /assets/css/pagebuilder.css
// =====================================================
require_once __DIR__ . '/asoc-system/modules/pagebuilder/src/PageService.php';
require_once __DIR__ . '/asoc-system/modules/pagebuilder/src/BlockRenderer.php';
require_once __DIR__ . '/asoc-system/modules/pagebuilder/src/AdminPageController.php';

// Media Manager — core module, always loaded
require_once __DIR__ . '/asoc-system/modules/media/src/MediaService.php';
require_once __DIR__ . '/asoc-system/modules/media/src/AdminMediaController.php';

$router->get('/admin/pages', function () {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->index([]);
}, ['auth', 'admin']);

$router->get('/admin/pages/create', function () {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->create([]);
}, ['auth', 'admin']);

$router->post('/admin/pages/store', function () {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->store([]);
}, ['auth', 'admin']);

$router->post('/admin/pages/ajax-save', function () {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $svc = new \ASOC\Modules\PageBuilder\PageService(App::db());
    $id  = (int)($data['id'] ?? 0);
    try {
        if ($id) {
            $svc->update($id, $data);
        } else {
            $id = $svc->create($data, App::auth()->id());
        }
        echo json_encode(['ok'=>true,'id'=>$id,'slug'=>$data['slug']??'']);
    } catch (\Throwable $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}, ['auth','admin']);

// ── Page Builder AJAX create empty page ──────────────────────────
$router->post('/admin/pages/ajax-create', function () {
    header('Content-Type: application/json');
    $svc  = new \ASOC\Modules\PageBuilder\PageService(App::db());
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = $svc->create(array_merge(['title'=>'New page','status'=>'draft','content_blocks'=>[]],$data), App::auth()->id());
    $page = $svc->find($id);
    echo json_encode(['ok'=>true,'id'=>$id,'slug'=>$page['slug']??'']);
    exit;
}, ['auth','admin']);

$router->post('/admin/pages/render-preview', function () {
    // Live preview endpoint — must be BEFORE parameterised routes
    // No CSRF consumption — auth-gated read-only render endpoint
    require_once __DIR__ . '/asoc-system/modules/pagebuilder/src/BlockRenderer.php';
    header('Content-Type: text/html; charset=utf-8');
    $blocks    = json_decode($_POST['blocks_json'] ?? '[]', true) ?: [];
    $renderer  = new \ASOC\Modules\PageBuilder\BlockRenderer();
    $renderer->setBuilderMode(true);
    $content   = $renderer->render($blocks);
    $settings  = App::settings();
    $primary   = $settings->get('theme_colour_primary', '#AA0101');
    $dark      = $settings->get('theme_colour_dark', '#20314F');
    $secondary = $settings->get('theme_colour_secondary', '#15AAFF');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="' . url('assets/css/pagebuilder.css') . '">
<style>
:root{--primary:' . $primary . ';--dark:' . $dark . ';--secondary:' . $secondary . ';
--grey-light:#F5F5F7;--grey-mid:#D1D5DB;--grey-text:#6B7280;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:system-ui,-apple-system,sans-serif;line-height:1.6;color:#1a1a1a;background:#fff;}
.container{max-width:1200px;margin:0 auto;padding:0 1.5rem;}
</style></head><body>';
    echo $content ?: '<div style="padding:3rem;text-align:center;color:#9ca3af;font-family:system-ui,sans-serif"><div style="font-size:2.5rem;margin-bottom:.75rem">📄</div><p style="font-size:1rem;font-weight:600">Add blocks from the left panel to build your page</p></div>';
    echo '</body></html>';
    exit;
}, ['auth']);

$router->get('/admin/pages/{id}/edit', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->edit($p);
}, ['auth', 'admin']);

// POST to /edit and /update both call update — editor uses /edit, keeping /update for back-compat
$router->post('/admin/pages/{id}/edit', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->update($p);
}, ['auth', 'admin']);

$router->post('/admin/pages/{id}/update', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->update($p);
}, ['auth', 'admin']);

$router->get('/admin/pages/{id}/set-homepage', function ($p) {
    // Clear any existing homepage flags then set this page
    App::db()->query('UPDATE asoc_pages SET is_homepage = 0');
    App::db()->update('pages', (int)($p['id'] ?? 0), ['is_homepage' => 1]);
    $_SESSION['flash_success'] = 'Homepage updated.';
    header('Location: ' . url('admin/pages'));
    exit;
}, ['auth', 'admin']);

$router->get('/admin/pages/{id}/delete', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->delete($p);
}, ['auth', 'admin']);

$router->get('/admin/pages/{id}/duplicate', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->duplicate($p);
}, ['auth', 'admin']);

$router->get('/admin/pages/{id}/preview', function ($p) {
    $ctrl = new \ASOC\Modules\PageBuilder\AdminPageController(
        new \ASOC\Modules\PageBuilder\PageService(App::db())
    );
    $ctrl->preview($p);
}, ['auth', 'admin']);

// =====================================================
// ARTICLES MODULE
// =====================================================
require_once __DIR__ . '/asoc-system/modules/articles/src/ArticleService.php';
require_once __DIR__ . '/asoc-system/modules/articles/src/PublicArticleController.php';
require_once __DIR__ . '/asoc-system/modules/articles/src/AdminArticleController.php';

// Public
$router->get('/articles', function () {
    (new \ASOC\Modules\Articles\PublicArticleController())->index();
});
$router->get('/articles/{slug}', function ($p) {
    (new \ASOC\Modules\Articles\PublicArticleController())->show($p);
});
// Also support /news as an alias
$router->get('/news', function () {
    header('Location: ' . url('articles'), true, 301); exit;
});

// Admin — static routes BEFORE parameterised ones
$router->get('/admin/articles', function () {
    (new \ASOC\Modules\Articles\AdminArticleController())->index();
}, ['auth', 'admin']);
$router->get('/admin/articles/create', function () {
    (new \ASOC\Modules\Articles\AdminArticleController())->create();
}, ['auth', 'admin']);
$router->post('/admin/articles/create', function () {
    (new \ASOC\Modules\Articles\AdminArticleController())->store();
}, ['auth', 'admin']);
$router->get('/admin/articles/categories', function () {
    (new \ASOC\Modules\Articles\AdminArticleController())->categories();
}, ['auth', 'admin']);
$router->post('/admin/articles/categories', function () {
    (new \ASOC\Modules\Articles\AdminArticleController())->storeCategory();
}, ['auth', 'admin']);
$router->post('/admin/articles/categories/{id}/update', function ($p) {
    (new \ASOC\Modules\Articles\AdminArticleController())->updateCategory($p);
}, ['auth', 'admin']);
$router->get('/admin/articles/categories/{id}/delete', function ($p) {
    (new \ASOC\Modules\Articles\AdminArticleController())->deleteCategory($p);
}, ['auth', 'admin']);
// Parameterised article routes
$router->get('/admin/articles/{id}/edit', function ($p) {
    (new \ASOC\Modules\Articles\AdminArticleController())->edit($p);
}, ['auth', 'admin']);
$router->post('/admin/articles/{id}/edit', function ($p) {
    (new \ASOC\Modules\Articles\AdminArticleController())->update($p);
}, ['auth', 'admin']);
$router->get('/admin/articles/{id}/delete', function ($p) {
    (new \ASOC\Modules\Articles\AdminArticleController())->delete($p);
}, ['auth', 'admin']);


// Public newsletter subscribe endpoint (used by page builder widget)
$router->post('/ajax/newsletter-subscribe', function () {
    header('Content-Type: application/json');
    // Accept JSON body
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    // Fallback to POST form data if not JSON
    if (!$data) $data = $_POST;
    $email  = strtolower(trim($data['email'] ?? ''));
    $listId = (int)($data['list_id'] ?? 0);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'error'=>'Please enter a valid email address.']);
        exit;
    }
    try {
        // EmailService is already required at top of email module block — safe to instantiate directly
        if (!class_exists('\\ASOC\\Modules\\Email\\EmailService')) {
            require_once __DIR__ . '/asoc-system/modules/email/src/EmailService.php';
        }
        $svc = new \ASOC\Modules\Email\EmailService();
        if (!$listId) {
            $lists  = $svc->allLists();
            $listId = !empty($lists) ? (int)$lists[0]['id'] : 0;
        }
        if (!$listId) {
            echo json_encode(['ok'=>false,'error'=>'No mailing list is available yet. Please contact us directly.']);
            exit;
        }
        $res = $svc->subscribe($email, [$listId], [], 'form');
        // CRM: resolve or create contact and log subscription activity
        if (!empty($res['ok'])) {
            try {
                $tracker = \ASOC\Modules\CRM\CrmTracker::forEmail($email, [], 'email_subscribe');
                $tracker->track(
                    \ASOC\Modules\CRM\CrmService::ACT_EMAIL_SUBSCRIBED,
                    'Subscribed via website newsletter form',
                    ['entity_type' => 'email_list', 'entity_id' => $listId]
                );
                // Update contact type to subscriber if it was a lead
                $cid = $tracker->contactId();
                if ($cid) \ASOC\Modules\CRM\CrmService::updateContactType($cid, 'subscriber');
            } catch (\Throwable) {}
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        error_log('Newsletter subscribe error: ' . $e->getMessage());
        echo json_encode(['ok'=>false,'error'=>'Subscription failed. Please try again.']);
    }
    exit;
});

// =====================================================
// EMAIL MODULE
// =====================================================
require_once __DIR__ . '/asoc-system/modules/email/src/EmailService.php';
require_once __DIR__ . '/asoc-system/modules/email/src/AdminEmailController.php';

// CRM module
require_once __DIR__ . '/asoc-system/modules/crm/src/CrmService.php';
require_once __DIR__ . '/asoc-system/modules/crm/src/CrmTracker.php';
require_once __DIR__ . '/asoc-system/modules/crm/src/AdminCrmController.php';

// Gallery module
require_once __DIR__ . '/asoc-system/modules/gallery/src/GalleryService.php';
require_once __DIR__ . '/asoc-system/modules/gallery/src/AdminGalleryController.php';

$router->get('/admin/email', function() { (new \ASOC\Modules\Email\AdminEmailController())->dashboard(); }, ['auth','admin']);
$router->get('/admin/email/lists', function() { (new \ASOC\Modules\Email\AdminEmailController())->lists(); }, ['auth','admin']);
$router->post('/admin/email/lists', function() { (new \ASOC\Modules\Email\AdminEmailController())->storeList(); }, ['auth','admin']);
$router->post('/admin/email/lists/{id}/update', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->updateList($p); }, ['auth','admin']);
$router->get('/admin/email/lists/{id}/delete', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->deleteList($p); }, ['auth','admin']);
$router->get('/admin/email/subscribers', function() { (new \ASOC\Modules\Email\AdminEmailController())->subscribers(); }, ['auth','admin']);
$router->post('/admin/email/subscribers/import', function() { (new \ASOC\Modules\Email\AdminEmailController())->importSubscribers(); }, ['auth','admin']);
$router->get('/admin/email/subscribers/{id}/unsubscribe', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->unsubscribeSub($p); }, ['auth','admin']);
$router->get('/admin/email/campaigns', function() { (new \ASOC\Modules\Email\AdminEmailController())->campaigns(); }, ['auth','admin']);
$router->get('/admin/email/campaigns/create', function() { (new \ASOC\Modules\Email\AdminEmailController())->createCampaign(); }, ['auth','admin']);
$router->post('/admin/email/campaigns/create', function() { (new \ASOC\Modules\Email\AdminEmailController())->storeCampaign(); }, ['auth','admin']);
$router->get('/admin/email/campaigns/{id}/edit', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->editCampaign($p); }, ['auth','admin']);
$router->post('/admin/email/campaigns/{id}/edit', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->updateCampaign($p); }, ['auth','admin']);
$router->get('/admin/email/campaigns/{id}/send', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->sendCampaign($p); }, ['auth','admin']);
$router->get('/admin/email/campaigns/{id}/delete', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->deleteCampaign($p); }, ['auth','admin']);
$router->get('/admin/email/senders', function() { (new \ASOC\Modules\Email\AdminEmailController())->senders(); }, ['auth','admin']);
$router->post('/admin/email/senders', function() { (new \ASOC\Modules\Email\AdminEmailController())->storeSender(); }, ['auth','admin']);
$router->post('/admin/email/senders/{id}/update', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->updateSender($p); }, ['auth','admin']);
$router->get('/admin/email/senders/{id}/delete', function($p) { (new \ASOC\Modules\Email\AdminEmailController())->deleteSender($p); }, ['auth','admin']);

// ── CRM Module ────────────────────────────────────────────────
$router->get('/admin/crm',                              function()  { \ASOC\Modules\CRM\AdminCrmController::dashboard(); },             ['auth','admin']);
$router->get('/admin/crm/contacts',                     function()  { \ASOC\Modules\CRM\AdminCrmController::contacts(); },              ['auth','admin']);
$router->get('/admin/crm/contacts/create',              function()  { \ASOC\Modules\CRM\AdminCrmController::contactCreate(); },         ['auth','admin']);
$router->post('/admin/crm/contacts/create',             function()  { \ASOC\Modules\CRM\AdminCrmController::contactCreate(); },         ['auth','admin']);
$router->get('/admin/crm/contacts/{id}',                function($p){ \ASOC\Modules\CRM\AdminCrmController::contactShow((int)$p['id']); },   ['auth','admin']);
$router->post('/admin/crm/contacts/{id}/notes',         function($p){ \ASOC\Modules\CRM\AdminCrmController::noteAdd((int)$p['id']); },       ['auth','admin']);
$router->get('/admin/crm/notes/{id}/delete',            function($p){ \ASOC\Modules\CRM\AdminCrmController::noteDelete((int)$p['id']); },    ['auth','admin']);
$router->post('/admin/crm/contacts/{id}/stage',         function($p){ \ASOC\Modules\CRM\AdminCrmController::stageMove((int)$p['id']); },     ['auth','admin']);
$router->post('/admin/crm/contacts/{id}/tags',          function($p){ \ASOC\Modules\CRM\AdminCrmController::tagAjax((int)$p['id']); },       ['auth','admin']);
$router->get('/admin/crm/pipelines',                    function()  { \ASOC\Modules\CRM\AdminCrmController::pipelines(); },             ['auth','admin']);
$router->post('/admin/crm/pipelines/save',              function()  { \ASOC\Modules\CRM\AdminCrmController::pipelineSave(); },          ['auth','admin']);

// ── Gallery Module ────────────────────────────────────────────
$router->get('/admin/gallery',                        function()   { \ASOC\Modules\Gallery\AdminGalleryController::index(); },          ['auth','admin']);
$router->get('/admin/gallery/create',                 function()   { \ASOC\Modules\Gallery\AdminGalleryController::create(); },         ['auth','admin']);
$router->post('/admin/gallery/create',                function()   { \ASOC\Modules\Gallery\AdminGalleryController::create(); },         ['auth','admin']);
$router->get('/admin/gallery/{id}/edit',              function($p) { \ASOC\Modules\Gallery\AdminGalleryController::edit((int)$p['id']); },   ['auth','admin']);
$router->post('/admin/gallery/{id}/update',           function($p) { \ASOC\Modules\Gallery\AdminGalleryController::update((int)$p['id']); }, ['auth','admin']);
$router->get('/admin/gallery/{id}/delete',            function($p) { \ASOC\Modules\Gallery\AdminGalleryController::delete((int)$p['id']); }, ['auth','admin']);
$router->post('/admin/gallery/{id}/items/add',        function($p) { \ASOC\Modules\Gallery\AdminGalleryController::itemAdd((int)$p['id']); },     ['auth','admin']);
$router->post('/admin/gallery/{id}/items/remove',     function($p) { \ASOC\Modules\Gallery\AdminGalleryController::itemRemove((int)$p['id']); },  ['auth','admin']);
$router->post('/admin/gallery/{id}/items/reorder',    function($p) { \ASOC\Modules\Gallery\AdminGalleryController::itemReorder((int)$p['id']); }, ['auth','admin']);
$router->post('/admin/gallery/{id}/items/caption',    function($p) { \ASOC\Modules\Gallery\AdminGalleryController::itemCaption((int)$p['id']); }, ['auth','admin']);
$router->post('/admin/gallery/{id}/items/cover',      function($p) { \ASOC\Modules\Gallery\AdminGalleryController::itemCover((int)$p['id']); },   ['auth','admin']);
$router->get('/admin/gallery/{id}/media-json',        function($p) { \ASOC\Modules\Gallery\AdminGalleryController::mediaJson(); },     ['auth','admin']);
$router->post('/admin/gallery/create-ajax',           function()   { \ASOC\Modules\Gallery\AdminGalleryController::createAjax(); },         ['auth','admin']);
$router->get('/admin/gallery/list-json',              function()   { \ASOC\Modules\Gallery\AdminGalleryController::listJson(); },            ['auth','admin']);

// =====================================================
$router->get('/payment/checkout', function () {
    
    $db  = App::db();
    $enc = App::crypt();
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/PaymentController.php';
    $ctrl = new \ASOC\Modules\Financials\PaymentController(
        new \ASOC\Modules\Financials\GatewayManager($db, $enc),
        new \ASOC\Modules\Financials\TransactionService($db)
    );
    $ctrl->checkout([]);
}, ['auth']);

$router->get('/payment/success', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/PaymentController.php';
    $ctrl = new \ASOC\Modules\Financials\PaymentController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->success([]);
});

$router->get('/payment/cancel', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/PaymentController.php';
    $ctrl = new \ASOC\Modules\Financials\PaymentController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->cancel([]);
});

$router->get('/dashboard/renew', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/PaymentController.php';
    $ctrl = new \ASOC\Modules\Financials\PaymentController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->renewShow([]);
}, ['auth']);

$router->get('/dashboard/auto-renewal/{action}', function (array $params) {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/PaymentController.php';
    $ctrl = new \ASOC\Modules\Financials\PaymentController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->toggleAutoRenewal($params);
}, ['auth']);

// Webhooks — no auth, signature-verified internally
$router->any('/webhooks/stripe', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/WebhookHandler.php';
    $handler = new \ASOC\Modules\Financials\WebhookHandler(
        App::db(),
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $payload   = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $handler->handleStripe($payload, $sigHeader);
    exit;
});

$router->any('/webhooks/square', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/WebhookHandler.php';
    $handler = new \ASOC\Modules\Financials\WebhookHandler(
        App::db(),
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $payload   = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] ?? '';
    $handler->handleSquare($payload, $sigHeader);
    exit;
});

$router->any('/webhooks/paypal', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/WebhookHandler.php';
    $handler = new \ASOC\Modules\Financials\WebhookHandler(
        App::db(),
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $payload = file_get_contents('php://input');
    $headers = getallheaders();
    $handler->handlePayPal($payload, $headers);
    exit;
});

// Admin financials
$router->get('/admin/financials', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/AdminFinancialsController.php';
    $ctrl = new \ASOC\Modules\Financials\AdminFinancialsController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->dashboard([]);
}, ['auth', 'admin']);

$router->any('/admin/financials/gateways', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/AdminFinancialsController.php';
    $ctrl = new \ASOC\Modules\Financials\AdminFinancialsController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->gateways([]);
}, ['auth', 'admin']);

$router->get('/admin/financials/transactions', function () {
    require_once __DIR__ . '/asoc-system/modules/financials/src/GatewayManager.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/TransactionService.php';
    require_once __DIR__ . '/asoc-system/modules/financials/src/AdminFinancialsController.php';
    $ctrl = new \ASOC\Modules\Financials\AdminFinancialsController(
        new \ASOC\Modules\Financials\GatewayManager(App::db(), App::crypt()),
        new \ASOC\Modules\Financials\TransactionService(App::db())
    );
    $ctrl->transactions([]);
}, ['auth', 'admin']);

// =====================================================
// MODULE ROUTES — pulled from each loaded module's manifest
// MUST be registered BEFORE the slug catch-all below, otherwise
// the catch-all eats every URL and modules never get a look in.
// =====================================================
foreach (App::modules()->routes() as $route) {
    $method = $route['method'] ?? 'GET';
    $pattern = $route['path'];
    $handler = $route['handler'];
    $middleware = $route['middleware'] ?? [];
    $router->add($method, $pattern, $handler, $middleware);
}

// Dynamic page lookup — slug-matched against pages table.
// MUST be the last route, since {slug} matches anything.
$router->get('/{slug}', function (array $params) {
    $page = App::db()->find('pages', [
        'slug'   => $params['slug'],
        'status' => 'published',
    ]);

    // Admins can preview draft pages
    if (!$page && App::auth()->isAdmin()) {
        $page = App::db()->find('pages', ['slug' => $params['slug']]);
    }

    if (!$page) {
        http_response_code(404);
        require __DIR__ . '/views/errors/404.php';
        return;
    }

    // If this page is the homepage, redirect to / to avoid duplicate URLs
    // e.g. /home and / showing the same content
    if (!empty($page['is_homepage'])) {
        header('Location: ' . \ASOC\Core\Config::url(''), true, 301);
        return;
    }

    if ($page['requires_auth'] && !App::auth()->check()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . \ASOC\Core\Config::url('login'));
        return;
    }

    // Decode JSON string from raw db row before page.php renders it
    if (is_string($page['content_blocks'])) {
        $page['content_blocks'] = json_decode($page['content_blocks'], true) ?: [];
    }

    $GLOBALS['current_page'] = $page;
    require __DIR__ . '/views/page.php';
});

// =====================================================
// DISPATCH
// =====================================================
$router->dispatch();
