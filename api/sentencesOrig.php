<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=skwazlwj_notesync;charset=utf8', 'skwazlwj', 'z7PXBkkJ4JYE');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT id, text FROM basics_sentences ORDER BY sort_order LIMIT 6');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('UPDATE basics_sentences SET text = ? WHERE id = ?');
    $ok   = $stmt->execute([$body['text'], $body['id']]);
    echo json_encode(['success' => $ok]);
}

 