<?php
/**
 * backend.php - AJAX/JSON endpoint for the to-do list (todos.php).
 *
 * Actions (all return JSON):
 *   GET  ?action=list
 *   POST action=add      todo_name, todo_when
 *   POST action=update   id, [todo_name], [todo_when]
 *   POST action=toggle   id, done (0|1)
 *   POST action=delete   id
 *   POST action=reorder  ids (comma separated, in the new order)
 *
 * Success: {"ok":true, ...}
 * Failure: {"ok":false,"error":{"type","title","message","hint"}}
 */
declare(strict_types=1);

// ---- CONFIGURATION: edit these ------------------------------------------
const DB_HOST    = 'localhost';
const DB_NAME    = 'skwazlwj_rocktodo';
const DB_USER    = 'skwazlwj_todosteve';
const DB_PASS    = 'roadtripcarnie';
const DB_CHARSET = 'utf8mb4';
// -------------------------------------------------------------------------

// Never let PHP warnings/notices corrupt the JSON output.
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const WHEN_OPTIONS = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    'Next Monday', 'Next Tuesday', 'Next Wednesday', 'Next Thursday',
    'Next Friday', 'Next Saturday', 'Next Sunday',
    'Next Weekend', 'Next Month',
];

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $type, string $title, string $message, string $hint = '', int $status = 500): never
{
    respond(['ok' => false, 'error' => [
        'type'    => $type,
        'title'   => $title,
        'message' => $message,
        'hint'    => $hint,
    ]], $status);
}

function row_out(array $r): array
{
    return [
        'id'         => (int)$r['id'],
        'todo_name'  => (string)$r['todo_name'],
        'todo_when'  => $r['todo_when'] === null ? '' : (string)$r['todo_when'],
        'done'       => (int)$r['done'] === 1,
        'sort_order' => (int)$r['sort_order'],
    ];
}

function clean_when(mixed $v): ?string
{
    $v = trim((string)$v);
    if ($v === '') {
        return null;
    }
    if (!in_array($v, WHEN_OPTIONS, true)) {
        fail('validation', 'Invalid day', 'That "when" option is not recognized.', '', 422);
    }
    return $v;
}

function clean_name(mixed $v): string
{
    $v = trim((string)$v);
    if ($v === '') {
        fail('validation', 'Name required', 'Please enter a name for the to-do item.', '', 422);
    }
    if (mb_strlen($v) > 255) {
        fail('validation', 'Name too long', 'A to-do name can be at most 255 characters.', '', 422);
    }
    return $v;
}

function need_id(): int
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        fail('validation', 'Bad request', 'Missing or invalid item id.', '', 400);
    }
    return $id;
}

$action  = $_REQUEST['action'] ?? '';
$isWrite = ($action !== 'list');

if ($isWrite && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('validation', 'Bad request', 'This action must be sent with POST.', '', 405);
}

// ---- Connect ---------------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[todos] DB connect failed: ' . $e->getMessage());
    fail(
        'database',
        'Can\'t reach the database',
        'The to-do list could not connect to its MySQL database, so nothing can be loaded or saved right now.',
        'Check that MySQL is running and the credentials at the top of backend.php are correct.'
    );
}

// ---- Handle the request ----------------------------------------------------
try {
    // Created on first use. Note: `order` is a reserved word in SQL, so the column is sort_order.
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS todos (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            todo_name  VARCHAR(255) NOT NULL,
            todo_when  VARCHAR(20)  NULL DEFAULT NULL,
            done       TINYINT(1)   NOT NULL DEFAULT 0,
            sort_order INT          NOT NULL DEFAULT 0,
            KEY idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    switch ($action) {
        case 'list':
            $rows = $pdo->query('SELECT * FROM todos ORDER BY sort_order ASC, id ASC')->fetchAll();
            respond(['ok' => true, 'todos' => array_map('row_out', $rows)]);

        case 'add':
            $name = clean_name($_POST['todo_name'] ?? '');
            $when = clean_when($_POST['todo_when'] ?? '');
            $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM todos')->fetchColumn();
            $st = $pdo->prepare('INSERT INTO todos (todo_name, todo_when, done, sort_order) VALUES (?, ?, 0, ?)');
            $st->execute([$name, $when, $next]);
            $id = (int)$pdo->lastInsertId();
            $row = $pdo->prepare('SELECT * FROM todos WHERE id = ?');
            $row->execute([$id]);
            respond(['ok' => true, 'todo' => row_out($row->fetch())]);

        case 'update':
            $id = need_id();
            $sets = [];
            $args = [];
            if (array_key_exists('todo_name', $_POST)) {
                $sets[] = 'todo_name = ?';
                $args[] = clean_name($_POST['todo_name']);
            }
            if (array_key_exists('todo_when', $_POST)) {
                $sets[] = 'todo_when = ?';
                $args[] = clean_when($_POST['todo_when']);
            }
            if (!$sets) {
                fail('validation', 'Nothing to update', 'No fields were provided.', '', 400);
            }
            $args[] = $id;
            $pdo->prepare('UPDATE todos SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);
            respond(['ok' => true]);

        case 'toggle':
            $id   = need_id();
            $done = ((string)($_POST['done'] ?? '0') === '1') ? 1 : 0;
            $pdo->prepare('UPDATE todos SET done = ? WHERE id = ?')->execute([$done, $id]);
            respond(['ok' => true, 'done' => $done === 1]);

        case 'delete':
            $id = need_id();
            $pdo->prepare('DELETE FROM todos WHERE id = ?')->execute([$id]);
            respond(['ok' => true]);

        case 'reorder':
            $ids = array_values(array_filter(
                array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))),
                fn($n) => $n > 0
            ));
            if (!$ids) {
                fail('validation', 'Bad request', 'No ids were provided for reordering.', '', 400);
            }
            $pdo->beginTransaction();
            $st = $pdo->prepare('UPDATE todos SET sort_order = ? WHERE id = ?');
            foreach ($ids as $i => $id) {
                $st->execute([$i + 1, $id]);
            }
            $pdo->commit();
            respond(['ok' => true]);

        default:
            fail('validation', 'Unknown action', 'The requested action does not exist.', '', 400);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[todos] DB error during "' . $action . '": ' . $e->getMessage());
    if ($isWrite) {
        fail(
            'database',
            'Your change wasn\'t saved',
            'The database reported a problem while saving, so this change was not stored.',
            'Please try again. If it keeps happening, check the MySQL server and table permissions.'
        );
    }
    fail(
        'database',
        'Couldn\'t load your to-dos',
        'The database reported a problem while reading your list.',
        'Please try again. If it keeps happening, check the MySQL server and table permissions.'
    );
} catch (Throwable $e) {
    error_log('[todos] Unexpected error: ' . $e->getMessage());
    fail('server', 'Something went wrong', 'An unexpected error occurred on the server.', 'Please try again.');
}
