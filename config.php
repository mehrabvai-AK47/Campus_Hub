<?php
declare(strict_types=1);

session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'campus_digital_hub';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Campus Digital Hub';
const APP_CURRENCY = 'BDT';
const APP_URL = 'http://localhost/Campus_Hub';
const SSLCOMMERZ_STORE_ID = '';
const SSLCOMMERZ_STORE_PASSWORD = '';
const SSLCOMMERZ_SANDBOX = true;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $connection;
    if (!$connection) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) redirect('login.php');
}

function require_admin(): void
{
    if (!current_user() || !in_array(current_user()['role'], ['super_admin', 'editor'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function money(float $amount): string
{
    return '৳' . number_format($amount, 2);
}

function page_start(string $title, string $active = ''): void
{
    $user = current_user();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/assets/css/app.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/assets/css/theme.css" rel="stylesheet">
    <script>document.documentElement.dataset.theme = localStorage.getItem('campus-theme') || 'light';</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom">
  <div class="container py-2"><a class="navbar-brand brand" href="index.php">campus<span>.</span></a>
    <button class="navbar-toggler" data-mdb-toggle="collapse" data-mdb-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
      <li class="nav-item"><a class="nav-link <?= $active === 'shop' ? 'active' : '' ?>" href="products.php">Explore library</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php#faq">FAQ</a></li>
      <?php if ($user): ?><li class="nav-item"><a class="nav-link" href="account.php">My library</a></li><?php endif; ?>
      <?php if ($user && in_array($user['role'], ['super_admin', 'editor'], true)): ?><li class="nav-item"><a class="nav-link" href="admin/index.php">Admin</a></li><?php endif; ?>
    <li class="nav-item"><button class="theme-toggle nav-link" type="button" data-theme-toggle aria-label="Switch to dark mode"><span data-theme-icon aria-hidden="true">☾</span><span data-theme-label>Dark mode</span></button></li>
      <li class="nav-item"><a class="btn btn-dark btn-rounded px-3" href="cart.php">Cart <span class="badge badge-light text-dark ms-1"><?= cart_count() ?></span></a></li>
      <?php if ($user): ?><li class="nav-item"><a class="nav-link" href="logout.php">Sign out</a></li><?php else: ?><li class="nav-item"><a class="nav-link" href="login.php">Sign in</a></li><?php endif; ?>
    </ul></div>
  </div>
</nav>
<main>
<?php
}

function page_end(): void
{
    ?><footer class="footer mt-5"><div class="container py-5 d-flex justify-content-between flex-wrap gap-3"><div><div class="brand text-white">campus<span>.</span></div><p class="text-white-50 mt-2 mb-0">Useful digital tools for the work ahead.</p></div><div class="text-white-50">© <?= date('Y') ?> Campus Digital Hub</div></div></footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script>
(() => {
    const root = document.documentElement;
    const button = document.querySelector('[data-theme-toggle]');
    const icon = document.querySelector('[data-theme-icon]');
    const label = document.querySelector('[data-theme-label]');
    if (!button) return;
    const update = () => {
        const dark = root.dataset.theme === 'dark';
        icon.textContent = dark ? '☀' : '☾';
        label.textContent = dark ? 'Light mode' : 'Dark mode';
        button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    };
    update();
    button.addEventListener('click', () => {
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('campus-theme', root.dataset.theme);
        update();
    });
})();
</script>
</body></html><?php
}
