<?php

function vercel_env_value(string $key): ?string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    return $value === false || $value === '' || $value === null ? null : (string) $value;
}

function vercel_key_status(): array
{
    $key = vercel_env_value('APP_KEY');
    $decoded = null;

    if (is_string($key) && str_starts_with($key, 'base64:')) {
        $decoded = base64_decode(substr($key, 7), true);
    }

    return [
        'present' => is_string($key),
        'looks_valid' => is_string($decoded) ? strlen($decoded) === 32 : is_string($key) && strlen($key) >= 32,
        'format' => is_string($key) && str_starts_with($key, 'base64:') ? 'base64' : 'plain_or_missing',
    ];
}

function vercel_database_url(): ?string
{
    return vercel_env_value('DB_URL') ?: vercel_env_value('DATABASE_URL') ?: vercel_env_value('POSTGRES_URL');
}

function vercel_database_config(): array
{
    $url = vercel_database_url();
    $driver = vercel_env_value('DB_CONNECTION');

    if (! $driver && is_string($url) && preg_match('/^postgres(?:ql)?:\/\//i', $url)) {
        $driver = 'pgsql';
    }

    if (! $driver && is_string($url) && preg_match('/^mysql:\/\//i', $url)) {
        $driver = 'mysql';
    }

    $driver = $driver ?: 'sqlite';

    if (is_string($url)) {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? $driver));

        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $driver = 'pgsql';
        } elseif ($scheme === 'mysql') {
            $driver = 'mysql';
        }

        if ($driver === 'pgsql' || $driver === 'mysql') {
            $database = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
            parse_str((string) ($parts['query'] ?? ''), $query);

            return [
                'driver' => $driver,
                'url_present' => true,
                'dsn' => $driver === 'pgsql'
                    ? sprintf(
                        'pgsql:host=%s;port=%s;dbname=%s%s',
                        $parts['host'] ?? 'localhost',
                        $parts['port'] ?? 5432,
                        $database,
                        isset($query['sslmode']) ? ';sslmode='.$query['sslmode'] : ''
                    )
                    : sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                        $parts['host'] ?? 'localhost',
                        $parts['port'] ?? 3306,
                        $database
                    ),
                'username' => isset($parts['user']) ? urldecode($parts['user']) : '',
                'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
            ];
        }
    }

    if ($driver === 'pgsql' || $driver === 'mysql' || $driver === 'mariadb') {
        $host = vercel_env_value('DB_HOST') ?: '127.0.0.1';
        $port = vercel_env_value('DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306');
        $database = vercel_env_value('DB_DATABASE') ?: 'laravel';

        return [
            'driver' => $driver === 'mariadb' ? 'mysql' : $driver,
            'url_present' => false,
            'dsn' => ($driver === 'pgsql')
                ? "pgsql:host={$host};port={$port};dbname={$database}"
                : "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            'username' => vercel_env_value('DB_USERNAME') ?: 'root',
            'password' => vercel_env_value('DB_PASSWORD') ?: '',
        ];
    }

    return [
        'driver' => 'sqlite',
        'url_present' => false,
        'path' => vercel_env_value('DB_DATABASE') ?: __DIR__.'/../database/database.sqlite',
    ];
}

function vercel_table_exists(PDO $pdo, string $driver, string $table): bool
{
    if ($driver === 'pgsql') {
        return (bool) $pdo->query("select to_regclass('public.{$table}') is not null")->fetchColumn();
    }

    if ($driver === 'mysql') {
        $statement = $pdo->prepare('show tables like ?');
        $statement->execute([$table]);

        return (bool) $statement->fetchColumn();
    }

    $statement = $pdo->prepare("select name from sqlite_master where type = 'table' and name = ?");
    $statement->execute([$table]);

    return (bool) $statement->fetchColumn();
}

function vercel_database_status(): array
{
    $config = vercel_database_config();

    try {
        if ($config['driver'] === 'sqlite') {
            if (! is_file($config['path'])) {
                return [
                    'driver' => 'sqlite',
                    'url_present' => false,
                    'connection' => 'failed',
                    'networks_table' => null,
                    'error' => 'SQLite file is missing. Use an online database on Vercel.',
                ];
            }

            $pdo = new PDO('sqlite:'.$config['path']);
        } else {
            $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return [
            'driver' => $config['driver'],
            'url_present' => (bool) ($config['url_present'] ?? false),
            'connection' => 'ok',
            'networks_table' => vercel_table_exists($pdo, $config['driver'], 'networks'),
        ];
    } catch (Throwable $exception) {
        return [
            'driver' => $config['driver'],
            'url_present' => (bool) ($config['url_present'] ?? false),
            'connection' => 'failed',
            'networks_table' => null,
            'error' => preg_replace('/password=[^;\\s]+/i', 'password=hidden', $exception->getMessage()),
        ];
    }
}

function vercel_reset_postgres_schema(): ?string
{
    $config = vercel_database_config();

    if (($config['driver'] ?? null) !== 'pgsql') {
        return null;
    }

    $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('drop schema if exists public cascade');
    $pdo->exec('create schema public');

    $user = (string) $pdo->query('select current_user')->fetchColumn();
    $escapedUser = str_replace('"', '""', $user);
    $pdo->exec('grant all on schema public to public');
    $pdo->exec(sprintf('grant all on schema public to "%s"', $escapedUser));

    return 'PostgreSQL public schema reset.';
}

function vercel_set_runtime_env(string $key, string $value): void
{
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

function vercel_prepare_database_environment(): void
{
    $url = vercel_database_url();

    if (! is_string($url)) {
        return;
    }

    if (! vercel_env_value('DB_URL')) {
        vercel_set_runtime_env('DB_URL', $url);
    }

    if (preg_match('/^postgres(?:ql)?:\/\//i', $url)) {
        vercel_set_runtime_env('DB_CONNECTION', 'pgsql');
    }
}

function vercel_prepare_app_key_environment(): void
{
    $key = vercel_env_value('APP_KEY');

    if (! is_string($key)) {
        return;
    }

    $key = trim($key, " \t\n\r\0\x0B\"'");

    if (str_starts_with($key, 'APP_KEY=')) {
        $key = substr($key, 8);
    }

    $key = trim($key, " \t\n\r\0\x0B\"'");

    if (! str_starts_with($key, 'base64:')) {
        $decoded = base64_decode($key, true);

        if (is_string($decoded) && strlen($decoded) === 32) {
            $key = 'base64:'.$key;
        }
    }

    vercel_set_runtime_env('APP_KEY', $key);
}

function vercel_prepare_url_environment(): void
{
    $host = $_SERVER['HTTP_HOST'] ?? vercel_env_value('VERCEL_URL');

    if (! is_string($host) || $host === '') {
        return;
    }

    $url = 'https://'.preg_replace('/^https?:\/\//i', '', $host);

    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_SCHEME'] = 'https';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    $_SERVER['HTTP_X_FORWARDED_SSL'] = 'on';

    vercel_set_runtime_env('APP_URL', $url);
    vercel_set_runtime_env('ASSET_URL', $url);
}

function vercel_diagnostics_enabled(): bool
{
    return vercel_env_value('VERCEL_MIGRATE_ENABLED') === 'true';
}

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/__vercel-health') {
    $key = vercel_key_status();
    $database = vercel_database_status();

    header('Content-Type: application/json');
    echo json_encode([
        'status' => $key['looks_valid'] && $database['connection'] === 'ok' && $database['networks_table'] === true ? 'ok' : 'needs_setup',
        'checks' => [
            'app_key' => $key,
            'database' => $database,
            'cloudinary' => [
                'cloudinary_url_present' => (bool) vercel_env_value('CLOUDINARY_URL'),
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

foreach (['/tmp/views', '/tmp/cache'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

foreach (['/tmp/cache/config.php', '/tmp/cache/services.php', '/tmp/cache/packages.php', '/tmp/cache/routes.php', '/tmp/cache/events.php'] as $cacheFile) {
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

vercel_prepare_database_environment();
vercel_prepare_app_key_environment();
vercel_prepare_url_environment();

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/__vercel-debug-home') {
    vercel_set_runtime_env('APP_DEBUG', 'true');
}

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/__vercel-migrate') {
    if (! vercel_diagnostics_enabled()) {
        http_response_code(404);
        exit;
    }

    $expectedToken = vercel_env_value('VERCEL_MIGRATE_TOKEN');
    $providedToken = (string) ($_GET['token'] ?? '');

    if (! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'forbidden',
            'message' => 'Set VERCEL_MIGRATE_TOKEN in Vercel and pass it as ?token=...',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Content-Type: application/json');

    $queries = [];

    try {
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;

            if (count($queries) > 50) {
                array_shift($queries);
            }
        });

        $fresh = (string) ($_GET['fresh'] ?? '') === '1';

        $resetOutput = $fresh ? vercel_reset_postgres_schema() : null;

        $kernel->call('migrate', ['--force' => true]);
        $migrationOutput = $kernel->output();

        $kernel->call('db:seed', ['--force' => true]);
        $seedOutput = $kernel->output();

        echo json_encode([
            'status' => 'ok',
            'reset' => $resetOutput,
            'migrate' => trim($migrationOutput),
            'seed' => trim($seedOutput),
            'health' => vercel_database_status(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $exception) {
        $messages = [];

        for ($current = $exception; $current instanceof Throwable; $current = $current->getPrevious()) {
            $messages[] = $current->getMessage();
        }

        http_response_code(500);
        echo json_encode([
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'messages' => $messages,
            'queries' => $queries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/__vercel-debug-home') {
    if (! vercel_diagnostics_enabled()) {
        http_response_code(404);
        exit;
    }

    $expectedToken = vercel_env_value('VERCEL_MIGRATE_TOKEN');
    $providedToken = (string) ($_GET['token'] ?? '');

    if (! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'forbidden'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Content-Type: application/json');

    try {
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $response = $app->handle($request, \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST, false);
        $content = (string) $response->getContent();
        $readableContent = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $content);
        $readableContent = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $readableContent);

        echo json_encode([
            'status' => 'ok',
            'response_status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('content-type'),
            'html_length' => strlen($content),
            'text_sample' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $readableContent))), 0, 3000),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $exception) {
        $messages = [];

        for ($current = $exception; $current instanceof Throwable; $current = $current->getPrevious()) {
            $messages[] = $current->getMessage();
        }

        http_response_code(500);
        echo json_encode([
            'status' => 'failed',
            'messages' => $messages,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

$app->handleRequest(\Illuminate\Http\Request::capture());
