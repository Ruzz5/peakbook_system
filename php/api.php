<?php
// CRUD API
require_once 'config.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? 'list';

// Allow session check without login
if ($entity === 'session' && $action === 'check') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['valid' => false]);
    } else {
        echo json_encode(['valid' => true, 'user' => $_SESSION['firstname']]);
    }
    exit;
}

// All other endpoints require login
requireLogin();    
$conn   = getConnection();

// ---- Allowed entities and their table/column definitions ----
$entities = [
    'books' => [
        'table'   => 'books',
        'fields'  => ['book_title', 'quantity', 'price', 'book_condition', 'supplier_id'],
        'joins'   => "LEFT JOIN suppliers s ON books.supplier_id = s.supplier_id",
        'select'  => "books.*, s.supplier_name",
        'search'  => ['book_title', 'book_condition'],
    ],
    'customers' => [
        'table'  => 'customers',
        'fields' => ['fullname', 'address', 'contact_number'],
        'select' => 'customers.*',
        'search' => ['fullname', 'address', 'contact_number'],
    ],
    'orders' => [
        'table'  => 'orders',
        'fields' => ['customer_id', 'courier_id', 'book_id', 'quantity', 'order_date', 'total_amount', 'order_status'],
        'joins'  => "LEFT JOIN customers c ON orders.customer_id = c.customer_id
                     LEFT JOIN books b      ON orders.book_id     = b.book_id
                     LEFT JOIN couriers cr  ON orders.courier_id  = cr.courier_id",
        'select' => "orders.*, c.fullname AS customer_name, b.book_title, cr.courier_name",
        'search' => ['c.fullname', 'b.book_title', 'orders.order_status'],
    ],
    'suppliers' => [
        'table'  => 'suppliers',
        'fields' => ['supplier_name', 'contact_number', 'address'],
        'select' => 'suppliers.*',
        'search' => ['supplier_name', 'contact_number'],
    ],
    'couriers' => [
        'table'  => 'couriers',
        'fields' => ['courier_name', 'contact_number'],
        'select' => 'couriers.*',
        'search' => ['courier_name', 'contact_number'],
    ],
    'payments' => [
        'table'  => 'payments',
        'fields' => ['order_id', 'customer_id', 'payment_date', 'payment_method', 'amount_paid'],
        'joins'  => "LEFT JOIN orders o       ON payments.order_id    = o.order_id
                     LEFT JOIN customers c    ON payments.customer_id = c.customer_id",
        'select' => "payments.*, c.fullname AS customer_name, o.order_date",
        'search' => ['c.fullname', 'payment_method'],
    ],
];

if (!array_key_exists($entity, $entities)) {
    echo json_encode(['error' => 'Unknown entity.']);
    $conn->close();
    exit;
}

$def   = $entities[$entity];
$table = $def['table'];


// ACTION: stats  
if ($action === 'stats') {
    $books     = $conn->query("SELECT COUNT(*) AS cnt FROM books")->fetch_assoc()['cnt'];
    $customers = $conn->query("SELECT COUNT(*) AS cnt FROM customers")->fetch_assoc()['cnt'];
    $income    = $conn->query("SELECT COALESCE(SUM(amount_paid),0) AS total FROM payments")->fetch_assoc()['total'];
    $orders    = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];

    // Weekly income (last 7 days per day)
    $weekly = $conn->query("
        SELECT DATE(payment_date) AS day, SUM(amount_paid) AS total
        FROM payments
        WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(payment_date)
        ORDER BY day ASC
    ");
    $weeklyData = [];
    while ($row = $weekly->fetch_assoc()) {
        $weeklyData[] = $row;
    }

    echo json_encode([
        'books'      => $books,
        'customers'  => $customers,
        'income'     => number_format($income, 2),
        'orders'     => $orders,
        'weekly'     => $weeklyData,
    ]);
    $conn->close();
    exit;
}

// ACTION: list  — paginated + optional search
if ($action === 'list') {
    $search  = trim($_GET['search'] ?? '');
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(1000, max(1, (int)($_GET['per_page'] ?? 10)));
    $offset  = ($page - 1) * $perPage;

    $joins  = $def['joins']  ?? '';
    $select = $def['select'] ?? "$table.*";
    $where  = '1=1';
    $params = [];
    $types  = '';

    // Build search WHERE clause
    if ($search !== '') {
        $conditions = [];
        foreach ($def['search'] as $col) {
            $conditions[] = "$col LIKE ?";
            $params[]     = "%$search%";
            $types       .= 's';
        }
        $where = '(' . implode(' OR ', $conditions) . ')';
    }

    // Count total rows for pagination
    $countSql  = "SELECT COUNT(*) AS cnt FROM $table $joins WHERE $where";
    $countStmt = $conn->prepare($countSql);
    if ($types) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['cnt'];

    // Fetch page
    $sql  = "SELECT $select FROM $table $joins WHERE $where ORDER BY $table.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    // Merge pagination params
    $allParams = $params;
    $allParams[] = $perPage;
    $allParams[] = $offset;
    $allTypes    = $types . 'ii';
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'data'       => $rows,
        'total'      => (int)$total,
        'page'       => $page,
        'per_page'   => $perPage,
        'last_page'  => (int)ceil($total / $perPage),
    ]);
    $conn->close();
    exit;
}

// ACTION: get  
if ($action === 'get') {
    $id     = (int)($_GET['id'] ?? 0);
    $joins  = $def['joins']  ?? '';
    $select = $def['select'] ?? "$table.*";
    $pk     = $table . '.' . rtrim($table, 's') . '_id';  

    // Handle irregular plurals (orders -> order_id, payments -> payment_id)
    $pkMap = [
        'books'     => 'books.book_id',
        'customers' => 'customers.customer_id',
        'orders'    => 'orders.order_id',
        'suppliers' => 'suppliers.supplier_id',
        'couriers'  => 'couriers.courier_id',
        'payments'  => 'payments.payment_id',
    ];
    $pk = $pkMap[$table] ?? $pk;

    $stmt = $conn->prepare("SELECT $select FROM $table $joins WHERE $pk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    echo json_encode($row ?: ['error' => 'Record not found.']);
    $conn->close();
    exit;
}
// ACTION: create  
if ($action === 'create') {
    $fields = $def['fields'];
    $values = [];
    $types  = '';

    foreach ($fields as $f) {
        $val = $_POST[$f] ?? null;
        // Convert empty strings to NULL for optional fields
        $values[] = ($val === '' || $val === null) ? null : $val;
        $types   .= 's';
    }

    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $cols         = implode(',', $fields);
    $stmt         = $conn->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        // If it's an order, auto-update book quantity (using prepared statement)
        if ($entity === 'orders') {
            $qty    = (int)($_POST['quantity']  ?? 1);
            $bookId = (int)($_POST['book_id']   ?? 0);
            $upd = $conn->prepare("UPDATE books SET quantity = quantity - ? WHERE book_id = ? AND quantity >= ?");
            $upd->bind_param('iii', $qty, $bookId, $qty);
            $upd->execute();
        }
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $conn->close();
    exit;
}

// ACTION: update 
if ($action === 'update') {
    $id     = (int)($_POST['id'] ?? 0);
    $fields = $def['fields'];
    $setParts = [];
    $values   = [];
    $types    = '';

    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $setParts[] = "$f = ?";
            $values[]   = $_POST[$f] === '' ? null : $_POST[$f];
            $types     .= 's';
        }
    }

    if (empty($setParts)) {
        echo json_encode(['success' => false, 'error' => 'No fields to update.']);
        $conn->close();
        exit;
    }

    $pkMap = [
        'books'     => 'book_id',
        'customers' => 'customer_id',
        'orders'    => 'order_id',
        'suppliers' => 'supplier_id',
        'couriers'  => 'courier_id',
        'payments'  => 'payment_id',
    ];
    $pk = $pkMap[$table];

    $values[] = $id;
    $types   .= 'i';
    $setStr   = implode(',', $setParts);
    $stmt     = $conn->prepare("UPDATE $table SET $setStr WHERE $pk = ?");
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $conn->close();
    exit;
}
// ACTION: delete  
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    $pkMap = [
        'books'     => 'book_id',
        'customers' => 'customer_id',
        'orders'    => 'order_id',
        'suppliers' => 'supplier_id',
        'couriers'  => 'courier_id',
        'payments'  => 'payment_id',
    ];
    $pk   = $pkMap[$table];
    $stmt = $conn->prepare("DELETE FROM $table WHERE $pk = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $conn->close();
    exit;
}

// ACTION: dropdown helpers 
if ($action === 'dropdown') {
    $dropdowns = [
        'suppliers' => "SELECT supplier_id AS id, supplier_name AS label FROM suppliers ORDER BY supplier_name",
        'customers' => "SELECT customer_id AS id, fullname AS label FROM customers ORDER BY fullname",
        'couriers'  => "SELECT courier_id AS id, courier_name AS label FROM couriers ORDER BY courier_name",
        'books'     => "SELECT book_id AS id, book_title AS label, price FROM books ORDER BY book_title",
        'orders'    => "SELECT order_id AS id, CONCAT('Order #', order_id) AS label FROM orders ORDER BY order_id",
    ];

    $sql = $dropdowns[$entity] ?? null;
    if (!$sql) {
        echo json_encode([]);
        $conn->close();
        exit;
    }

    $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows);
    $conn->close();
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
$conn->close();