<?php
// ─────────────────────────────────────────────
//  db.php — SQLite connection + schema setup
//  Uses PDO + SQLite so it runs with zero config
// ─────────────────────────────────────────────

define('DB_PATH', __DIR__ . '/expense_tracker.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initSchema($pdo);
    }
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            type        TEXT    NOT NULL CHECK(type IN ('income','expense')),
            category    TEXT    NOT NULL,
            description TEXT    NOT NULL,
            amount      REAL    NOT NULL CHECK(amount > 0),
            date        TEXT    NOT NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Seed demo data only once
    $count = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    if ($count == 0) {
        $seeds = [
            ['income',  'Salary',      'Monthly salary',         45000.00, date('Y-m-d', strtotime('-2 days'))],
            ['expense', 'Food',        'Grocery run',             1850.50, date('Y-m-d', strtotime('-1 day'))],
            ['expense', 'Transport',   'Grab to work',             320.00, date('Y-m-d')],
            ['expense', 'Bills',       'Electricity bill',        2100.00, date('Y-m-d', strtotime('-5 days'))],
            ['income',  'Freelance',   'Web design project',     12000.00, date('Y-m-d', strtotime('-3 days'))],
            ['expense', 'Food',        'Restaurant dinner',        890.00, date('Y-m-d', strtotime('-4 days'))],
            ['expense', 'Shopping',    'New shoes',               3200.00, date('Y-m-d', strtotime('-6 days'))],
            ['expense', 'Health',      'Pharmacy',                 450.00, date('Y-m-d', strtotime('-7 days'))],
            ['income',  'Investment',  'Stock dividends',         5500.00, date('Y-m-d', strtotime('-8 days'))],
            ['expense', 'Bills',       'Internet & phone',        1299.00, date('Y-m-d', strtotime('-9 days'))],
        ];
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (type, category, description, amount, date)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($seeds as $s) $stmt->execute($s);
    }
}
