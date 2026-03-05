<?php
require_once 'db.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();

    if ($method === 'POST' && $action === 'add') {
        $body = json_decode(file_get_contents('php://input'), true);

        $type        = $body['type']        ?? '';
        $category    = $body['category']    ?? '';
        $description = trim($body['description'] ?? '');
        $amount      = (float)($body['amount'] ?? 0);
        $date        = $body['date']        ?? date('Y-m-d');

        if (!in_array($type, ['income','expense'])) throw new Exception('Invalid type');
        if (!$category)    throw new Exception('Category required');
        if (!$description) throw new Exception('Description required');
        if ($amount <= 0)  throw new Exception('Amount must be positive');

        $stmt = $db->prepare(
            "INSERT INTO transactions (type, category, description, amount, date)
             VALUES (:type, :category, :description, :amount, :date)"
        );
        $stmt->execute([
            ':type'        => $type,
            ':category'    => $category,
            ':description' => $description,
            ':amount'      => $amount,
            ':date'        => $date,
        ]);

        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        exit;
    }

    if ($method === 'DELETE' && $action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception('Invalid ID');
        $db->prepare("DELETE FROM transactions WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'GET' && $action === 'list') {
        $where  = [];
        $params = [];

        if (!empty($_GET['month'])) {
            $where[]  = "strftime('%Y-%m', date) = :month";
            $params[':month'] = $_GET['month'];
        }
        if (!empty($_GET['type']) && in_array($_GET['type'], ['income','expense'])) {
            $where[]  = "type = :type";
            $params[':type'] = $_GET['type'];
        }
        if (!empty($_GET['category'])) {
            $where[]  = "category = :category";
            $params[':category'] = $_GET['category'];
        }

        $sql = "SELECT * FROM transactions";
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY date DESC, created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($method === 'GET' && $action === 'summary') {
        $month  = $_GET['month'] ?? date('Y-m');
        $params = [':month' => $month];

        // Total income & expense for selected month
        $totals = $db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS total_expense
            FROM transactions
            WHERE strftime('%Y-%m', date) = :month
        ");
        $totals->execute($params);
        $summary = $totals->fetch();

        // Category breakdown (expenses only) for pie chart
        $cats = $db->prepare("
            SELECT category,
                   SUM(amount)  AS total,
                   COUNT(*)     AS count
            FROM transactions
            WHERE type = 'expense'
              AND strftime('%Y-%m', date) = :month
            GROUP BY category
            ORDER BY total DESC
        ");
        $cats->execute($params);
        $categories = $cats->fetchAll();

        // Monthly trend (last 6 months)
        $trend = $db->query("
            SELECT strftime('%Y-%m', date)                                    AS month,
                   COALESCE(SUM(CASE WHEN type='income'  THEN amount END), 0) AS income,
                   COALESCE(SUM(CASE WHEN type='expense' THEN amount END), 0) AS expense
            FROM transactions
            GROUP BY strftime('%Y-%m', date)
            ORDER BY month DESC
            LIMIT 6
        ")->fetchAll();

        echo json_encode([
            'success'    => true,
            'summary'    => $summary,
            'categories' => $categories,
            'trend'      => array_reverse($trend),
        ]);
        exit;
    }

    throw new Exception('Unknown action');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
