<?php
$pdo2 = new PDO('mysql:host=localhost;dbname=skwazlwj_notesync;charset=utf8', 'skwazlwj', 'z7PXBkkJ4JYE');

$stmt2 = $pdo2->query('SELECT id, text FROM book_lines ORDER BY sort_order ASC');
$books = $stmt2->fetchAll();

//error_log('Books: ' . print_r($books, true));

?>