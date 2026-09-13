<?php
header('Content-Type: application/json');

$pdo = new PDO(
    'mysql:host=localhost;dbname=skwazlwj_notesync;charset=utf8',
    'skwazlwj',
    'z7PXBkkJ4JYE',
    [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
);



// Determine which table we're working with.
// GET  → ?type=books|amazon  (default: sentences)
// POST → body JSON includes "type": "books"|"amazon"  (default: sentences)

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? 'sentences';
} else {
    $body = json_decode(file_get_contents('php://input'), true);
    $type = $body['type'] ?? 'sentences';
}

if ($type === 'books') {
    $table = 'book_lines';
} elseif ($type === 'amazon') {
    $table = 'amazon_list';
} else {
    $table = 'basics_sentences';
}


/* ── GET: return all rows ordered by sort_order ── */
if ($method === 'GET') {
    if ( $table === 'book_lines' ) {
      $limit = 7; // Limit to 6 rows for performance
    } else {
      $limit = 10; // No limit for sentences
    }
    
    $stmt = $pdo->prepare("SELECT id, text FROM `$table` ORDER BY sort_order LIMIT $limit");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ── POST: update a single row ── */
if ($method === 'POST') {
    $id   = isset($body['id'])   ? (int)$body['id']          : 0;
    $text = isset($body['text']) ? trim($body['text'])        : '';

    if (!$id || $text === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id or text']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE `$table` SET text = ? WHERE id = ?");
    $ok   = $stmt->execute([$text, $id]);
    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);