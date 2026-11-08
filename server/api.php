<?php
//   GET  /api.php/health             — check kung buhay ang server
//   GET  /api.php/categories         — mga categories ng gastos
//   GET  /api.php/payment-methods    — mga way o method ng bayad
//   GET  /api.php/expenses           — lahat ng gastos
//   POST /api.php/expenses           — magdadagdag ng gastos
//   POST /api.php/expenses/update    — palitan ng details ung gastos
//   POST /api.php/expenses/delete    — burahin ung gastos
//   GET  /api.php/receipts/{id}      — photo ng gastos
//   POST /api.php/receipts           — magdagdag o palit ng photo
//   POST /api.php/receipts/delete    — burahin ung photo
//   GET  /api.php/budget             — budget ngayong buwan
//   POST /api.php/budget             — itakda ung budget
//   GET  /api.php/budgets            — history ng budget
require __DIR__ . '/config.php';

$allowedOrigins = [
    'capacitor://localhost',
    'ionic://localhost',
    'http://localhost',
    'http://localhost:8100'
];

const CATEGORIES = [
  ['name' => 'Food', 'icon' => 'restaurant-outline', 'color' => '#E67E22', 'bg' => '#FDEBD0'],
  ['name' => 'Transport', 'icon' => 'car-outline', 'color' => '#2A8C83', 'bg' => '#D5F0EE'],
  ['name' => 'Shopping', 'icon' => 'bag-outline', 'color' => '#8E44AD', 'bg' => '#EBDEF0'],
  ['name' => 'Bills', 'icon' => 'receipt-outline', 'color' => '#C0392B', 'bg' => '#FADBD8'],
  ['name' => 'Health', 'icon' => 'heart-outline', 'color' => '#E91E63', 'bg' => '#FCE4EC'],
  ['name' => 'Entertainment', 'icon' => 'game-controller-outline', 'color' => '#2980B9', 'bg' => '#D6EAF8'],
  ['name' => 'Other', 'icon' => 'ellipsis-horizontal-outline', 'color' => '#7B898B', 'bg' => '#EAECEE'],
];

const PAYMENT_METHODS = [
  ['name' => 'Cash', 'icon' => 'cash-outline'],
  ['name' => 'GCash', 'icon' => 'phone-portrait-outline'],
  ['name' => 'Maya', 'icon' => 'wallet-outline'],
  ['name' => 'Card', 'icon' => 'card-outline'],
  ['name' => 'Bank Transfer', 'icon' => 'business-outline'],
];

function isValidCategory($name) {
  foreach (CATEGORIES as $category) {
    if ($category['name'] === $name) return true;
  }
  return false;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

// Kinukuha ang path pagkatapos ng api.php at hindi kasama ang query string.
// Halimbawa: /owllet-server/api.php/expenses/update?x=1 → /expenses/update
$beforeAndAfter = explode('api.php', $uri);   // bago at pagkatapos ng "api.php"
$after = $beforeAndAfter[1] ?? '';            // hal. "/expenses/update?x=1"
$after = explode('?', $after)[0];             // tanggalin ang "?query=..."
$path = '/' . trim($after, '/');              // hal. "/expenses/update"

// Tsek kung buhay ang server at konektado sa database.
if ($path === '/health' && $method === 'GET') {
  jsonResponse(['ok' => true, 'service' => 'Owllet API', 'time' => date('c')]);
}

// Dito kinukuha ang mga kategorya.
if ($path === '/categories' && $method === 'GET') {
  jsonResponse(CATEGORIES);
}

// Dito kinukuha ang mga paraan ng bayad.
if ($path === '/payment-methods' && $method === 'GET') {
  jsonResponse(PAYMENT_METHODS);
}

// Lahat ng gastos (kasama ang bilang ng resibo para sa bawat isa).
if ($path === '/expenses' && $method === 'GET') {
  $stmt = $pdo->query(
    'SELECT e.*, (SELECT COUNT(*) FROM receipts r WHERE r.expense_id = e.id) AS receipt_count
     FROM expenses e ORDER BY e.date DESC, e.created_at DESC'
  );
  $list = [];
  foreach ($stmt->fetchAll() as $row) {
    $list[] = normalizeExpense($row);
  }
  jsonResponse($list);
}

// Dito madadagdag ang bagong gastos.
if ($path === '/expenses' && $method === 'POST') {
  $b = getBody();
  foreach (['id', 'title', 'amount', 'category', 'date', 'createdAt'] as $f) {
    if (!isset($b[$f]) || $b[$f] === '' || $b[$f] === null) {
      jsonResponse(['error' => "The required field '$f' is missing."], 400);
    }
  }
  if ((float) $b['amount'] <= 0) jsonResponse(['error' => 'Amount must be greater than zero.'], 400);
  if (!is_string($b['category']) || !isValidCategory($b['category'])) {
    jsonResponse(['error' => 'The selected category is not supported.'], 400);
  }

  $stmt = $pdo->prepare(
    'INSERT INTO expenses (id,title,amount,category,date,note,payment_method,created_at) VALUES (?,?,?,?,?,?,?,?)'
  );
  $stmt->execute([
    $b['id'], $b['title'], $b['amount'], $b['category'], $b['date'],
    $b['note'] ?? null, $b['paymentMethod'] ?? null, $b['createdAt'],
  ]);
  jsonResponse(normalizeExpense([
    'id' => $b['id'], 'title' => $b['title'], 'amount' => $b['amount'],
    'category' => $b['category'], 'date' => $b['date'], 'note' => $b['note'] ?? null,
    'payment_method' => $b['paymentMethod'] ?? null, 'created_at' => $b['createdAt'],
  ]), 201);
}

// Dito ina-update ang isang gastos (id ang nasa body ng request).
if ($path === '/expenses/update' && $method === 'POST') {
  $b = getBody();
  $id = (string) ($b['id'] ?? '');
  if ($id === '') jsonResponse(['error' => 'Expense ID is required.'], 400);
  if (empty($b['title']) || !isset($b['amount']) || (float) $b['amount'] <= 0) {
    jsonResponse(['error' => 'A valid title and positive amount are required.'], 400);
  }

  $category = $b['category'] ?? 'Other';
  if (!is_string($category) || !isValidCategory($category)) {
    jsonResponse(['error' => 'The selected category is not supported.'], 400);
  }

  $stmt = $pdo->prepare(
    'UPDATE expenses SET title=?, amount=?, category=?, date=?, note=?, payment_method=? WHERE id=?'
  );
  $stmt->execute([
    $b['title'], $b['amount'], $category, $b['date'] ?? date('Y-m-d'),
    $b['note'] ?? null, $b['paymentMethod'] ?? null, $id,
  ]);
  // Kapag walang nabago, baka wala talagang ganitong gastos.
  if ($stmt->rowCount() === 0) {
    $exists = $pdo->prepare('SELECT 1 FROM expenses WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) jsonResponse(['error' => 'Expense not found.'], 404);
  }
  jsonResponse(['ok' => true, 'id' => $id]);
}

// Dito binubura ang isang gastos (id ang nasa body ng request).
if ($path === '/expenses/delete' && $method === 'POST') {
  $b = getBody();
  $id = (string) ($b['id'] ?? '');
  if ($id === '') jsonResponse(['error' => 'Expense ID is required.'], 400);

  $stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ?');
  $stmt->execute([$id]);
  if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Expense not found.'], 404);
  jsonResponse(['ok' => true]); // Kusang nabubura rin ang resibo (ON DELETE CASCADE).
}

// Dito binubura ang photo ng resibo (expenseId ang nasa body ng request).
if ($path === '/receipts/delete' && $method === 'POST') {
  $b = getBody();
  $expenseId = $b['expenseId'] ?? null;
  if (!$expenseId) jsonResponse(['error' => 'Expense ID is required.'], 400);

  $stmt = $pdo->prepare('DELETE FROM receipts WHERE expense_id = ?');
  $stmt->execute([$expenseId]);
  jsonResponse(['ok' => true, 'deleted' => $stmt->rowCount()]);
}

// Dito kinukuha yung photo ng gastos: GET /receipts/{expenseId}
// Hinihiwalay lang ang path gamit ang slash — hal. ['', 'receipts', 'expense-123']
$pathParts = explode('/', $path);
if (count($pathParts) === 3 && $pathParts[1] === 'receipts' && $pathParts[2] !== '' && $method === 'GET') {
  $stmt = $pdo->prepare('SELECT expense_id, image_data, created_at FROM receipts WHERE expense_id = ?');
  $stmt->execute([$pathParts[2]]);
  $row = $stmt->fetch();
  if (!$row) jsonResponse(['error' => 'Expense photo not found.'], 404);
  jsonResponse([
    'expenseId' => $row['expense_id'],
    'imageData' => $row['image_data'],
    'createdAt' => (int) $row['created_at'],
  ]);
}

// Dito sine-save o pinalitan ang photo ng resibo.
if ($path === '/receipts' && $method === 'POST') {
  $b = getBody();
  $eid = $b['expenseId'] ?? null;
  $img = $b['imageData'] ?? null;
  if (!$eid || !$img) jsonResponse(['error' => 'Expense ID and image data are required.'], 400);
  if (strlen($img) > 4000000) jsonResponse(['error' => 'The image must not exceed 4 MB.'], 413);

  // Tinitingnan muna kung may ganitong gastos para malinaw ang error.
  $chk = $pdo->prepare('SELECT id FROM expenses WHERE id = ?');
  $chk->execute([$eid]);
  if (!$chk->fetch()) jsonResponse(['error' => 'Expense not found.'], 404);

  // 1 resibo lang bawat gastos (expense_id ang primary key) kaya ito ay
  // "insert or replace" — puwede itong ulit-ulitin nang ligtas.
  $stmt = $pdo->prepare(
    'INSERT INTO receipts (expense_id, image_data, created_at) VALUES (?,?,?)
     ON DUPLICATE KEY UPDATE image_data = VALUES(image_data), created_at = VALUES(created_at)'
  );
  $stmt->execute([$eid, $img, (int) (microtime(true) * 1000)]);
  jsonResponse(['ok' => true, 'expenseId' => $eid], 201);
}

// Dito kinukuha ang budget ngayong buwan (0 kapag wala pang naka-set).
if ($path === '/budget' && $method === 'GET') {
  $thisMonth = date('Y-m');
  $stmt = $pdo->prepare('SELECT amount FROM budgets WHERE month = ?');
  $stmt->execute([$thisMonth]);
  $row = $stmt->fetch();
  jsonResponse([
    'monthlyBudget' => $row ? (float) $row['amount'] : 0,
    'month' => $thisMonth,
  ]);
}

// Dito sine-save ang budget (1 row kada buwan; inu-update kapag pareho ang buwan).
if ($path === '/budget' && $method === 'POST') {
  $b = getBody();
  $amount = (float) ($b['monthlyBudget'] ?? $b['budget'] ?? 0);
  if ($amount <= 0) jsonResponse(['error' => 'Budget must be greater than zero.'], 400);

  $thisMonth = date('Y-m');
  $now = (int) (microtime(true) * 1000);
  $stmt = $pdo->prepare(
    'INSERT INTO budgets (month, amount, updated_at) VALUES (?,?,?)
     ON DUPLICATE KEY UPDATE amount = VALUES(amount), updated_at = VALUES(updated_at)'
  );
  $stmt->execute([$thisMonth, $amount, $now]);
  jsonResponse(['ok' => true, 'monthlyBudget' => $amount, 'month' => $thisMonth]);
}

// Dito kinukuha ang history ng budget (12 buwan pabalik).
if ($path === '/budgets' && $method === 'GET') {
  $rows = $pdo->query('SELECT month, amount, updated_at FROM budgets ORDER BY month DESC LIMIT 12')->fetchAll();
  $list = [];
  foreach ($rows as $r) {
    $list[] = ['month' => $r['month'], 'amount' => (float) $r['amount'], 'updatedAt' => (int) $r['updated_at']];
  }
  jsonResponse($list);
}

if ($path === '/profile' && $method === 'GET') {
  $row = $pdo->query('SELECT display_name, email, avatar_initials FROM profiles WHERE id = 1 LIMIT 1')->fetch();
  jsonResponse($row ? [
    'displayName' => (string) $row['display_name'],
    'email' => (string) ($row['email'] ?? ''),
    'avatarInitials' => (string) $row['avatar_initials'],
  ] : [
    'displayName' => 'Owllet User',
    'email' => '',
    'avatarInitials' => 'OU',
  ]);
}

jsonResponse(['error' => 'The requested endpoint was not found.'], 404);
