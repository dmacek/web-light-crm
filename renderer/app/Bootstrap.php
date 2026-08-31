<?php

declare(strict_types=1);

namespace Renderer;

final class Bootstrap
{
    private static ?\PDO $pdo = null;
    private static ?\Predis\Client $redis = null;

    public static function getDatabase(): \PDO
    {
        if (self::$pdo === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'postgres://user:password@localhost:5432/app_db?sslmode=disable';
            $parsed = parse_url($dbUrl);

            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? 5432;
            $user = $parsed['user'] ?? 'user';
            $pass = $parsed['pass'] ?? 'password';
            $dbName = ltrim($parsed['path'] ?? 'app_db', '/');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
            self::$pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }

    public static function getRedis(): \Predis\Client
    {
        if (self::$redis === null) {
            $redisUrl = getenv('REDIS_URL') ?: 'redis://localhost:6379';
            self::$redis = new \Predis\Client($redisUrl);
        }

        return self::$redis;
    }
}
