<?php
require __DIR__ . '/../../db.php';
$rows = $pdo->query("SELECT mi.name, c.name cat FROM menu_items mi JOIN categories c ON c.id = mi.category_id WHERE mi.is_available = 1 ORDER BY c.sort_order, mi.sort_order, mi.name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo $r['cat'] . ' :: ' . $r['name'] . PHP_EOL;
