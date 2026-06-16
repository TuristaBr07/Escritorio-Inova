<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (DB_TYPE === 'sqlite') {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH);
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (DB_TYPE === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }

    return $pdo;
}
