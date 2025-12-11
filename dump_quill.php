<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$row = $pdo->query('SELECT * FROM quill_lite_documents LIMIT 1')->fetch(PDO::FETCH_ASSOC);
var_export($row);
