<?php
$pdo = new PDO('mysql:host=localhost;dbname=dms_adasi_rev1', 'root', '');
$migs = $pdo->query("SELECT * FROM migrations ORDER BY id ASC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($migs, JSON_PRETTY_PRINT);
