<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pokemon_game');

try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

function fmtTime($s) {
    if (!$s) return '0m';
    $h = floor($s / 3600); $m = floor(($s % 3600) / 60);
    return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
}

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? intval($_GET['id']) : null;

// ── GET all saves ─────────────────────────────────────────────
if ($method === 'GET' && !$id) {
    $stmt = $pdo->query('SELECT * FROM save_slots ORDER BY saved_at DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $saves = array_map(function($r) {
        return [
            'id'          => (int)$r['id'],
            'saveName'    => $r['save_name'],
            'playerX'     => (float)$r['player_x'],
            'playerY'     => (float)$r['player_y'],
            'cityName'    => $r['city_name'],
            'playTime'    => fmtTime($r['play_time_seconds']),
            'playTimeRaw' => (int)$r['play_time_seconds'],
            'savedAt'     => $r['saved_at'],
        ];
    }, $rows);
    echo json_encode(['success' => true, 'saves' => $saves]);
    exit;
}

// ── GET single save ───────────────────────────────────────────
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM save_slots WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Save not found']); exit; }
    echo json_encode(['success' => true, 'save' => [
        'id'          => (int)$row['id'],
        'saveName'    => $row['save_name'],
        'playerX'     => (float)$row['player_x'],
        'playerY'     => (float)$row['player_y'],
        'cityName'    => $row['city_name'],
        'playTimeRaw' => (int)$row['play_time_seconds'],
        'savedAt'     => $row['saved_at'],
    ]]);
    exit;
}

// ── POST — create new save ────────────────────────────────────
if ($method === 'POST' && !$id) {
    $body = json_decode(file_get_contents('php://input'), true);
    $name = trim($body['saveName'] ?? '');
    if ($name === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Save name is required']); exit; }
    if (!isset($body['playerX']) || !isset($body['playerY'])) {
        http_response_code(400); echo json_encode(['success'=>false,'error'=>'playerX and playerY required']); exit;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO save_slots (save_name, player_x, player_y, city_name, play_time_seconds)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $body['playerX'], $body['playerY'], $body['cityName'] ?? 'Unknown', $body['playTimeSeconds'] ?? 0]);
    $newId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => (int)$newId, 'message' => "Game saved as \"{$name}\"!"]);
    exit;
}

// ── DELETE — delete a save by id ──────────────────────────────
if ($method === 'DELETE' && $id) {
    $stmt = $pdo->prepare('DELETE FROM save_slots WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Save not found']); exit; }
    echo json_encode(['success' => true, 'message' => 'Save deleted']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);