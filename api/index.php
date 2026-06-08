<?php

function vercel_env_value(string $key): ?string
{
    $value = getenv($key);

    return $value === false || $value === '' ? null : $value;
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

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(\Illuminate\Http\Request::capture());
