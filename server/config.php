<?php
// Dito mo mababago ang settings para sa Owllet API.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Login sa database. Ang nasa ibaba ang gamit kapag lokal (XAMPP) ang server.
$dbHost = 'localhost';
$dbName = 'owllet_db';
$dbUser = 'root';
$dbPass = '';

// Kapag may hiwalay na file ng login sa database (ibinubuhos lang sa server,
// hindi kasama sa submission), iyon ang masusunod.
if (file_exists(__DIR__ . '/db_credentials.php')) {
  require __DIR__ . '/db_credentials.php';
}

// Cloud hosting (Render, atbp.): environment variables ang masusunod.
$dbPort = getenv('DB_PORT') ?: '3306';
if (getenv('DB_HOST')) {
  $dbHost = getenv('DB_HOST');
  $dbName = getenv('DB_NAME');
  $dbUser = getenv('DB_USER');
  $dbPass = getenv('DB_PASS');
}

try {
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];
  // Karamihan ng cloud MySQL (TiDB, Aiven) ay nangangailangan ng SSL.
  if (getenv('DB_SSL') === '1') {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
  }
  $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, $options);
} catch (PDOException $e) {
  error_log('Owllet database connection failed: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed. Check the server configuration.']);
  exit;
}

function jsonResponse($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function getBody() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(['error' => 'Invalid JSON'], 400);
  }
  return $data ?? [];
}

function normalizeExpense($row) {
  return [
    'id'            => (string) $row['id'],
    'title'         => (string) $row['title'],
    'amount'        => (float) $row['amount'],
    'category'      => (string) $row['category'],
    'date'          => (string) $row['date'],
    'note'          => $row['note'] !== null ? (string) $row['note'] : null,
    'paymentMethod' => isset($row['payment_method']) && $row['payment_method'] !== null
      ? (string) $row['payment_method'] : null,
    'hasReceipt'    => (int) ($row['receipt_count'] ?? 0) > 0,
    'createdAt'     => (int) $row['created_at'],
  ];
}

