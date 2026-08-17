<?php
require __DIR__ . '/../../db.php';
$rows = $pdo->query("SELECT c.name cat, COUNT(*) n, SUM(mi.image_url IS NULL OR mi.image_url = '') empty_img FROM menu_items mi JOIN categories c ON c.id = mi.category_id WHERE mi.is_available = 1 AND c.status = 'active' GROUP BY c.id ORDER BY c.sort_order")->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($rows as $r) { $total += (int)$r['n']; echo str_pad($r['cat'], 20) . ' items=' . $r['n'] . ' empty_img=' . $r['empty_img'] . PHP_EOL; }
echo 'TOTAL=' . $total . PHP_EOL . '--- sample image_url ---' . PHP_EOL;
foreach ($pdo->query("SELECT name, image_url FROM menu_items WHERE image_url <> '' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $r) echo $r['name'] . ' => ' . $r['image_url'] . PHP_EOL;
echo '--- desc lengths ---' . PHP_EOL;
foreach ($pdo->query("SELECT name, description FROM menu_items LIMIT 6")->fetchAll(PDO::FETCH_ASSOC) as $r) echo $r['name'] . ' => [' . $r['description'] . ']' . PHP_EOL;
echo '--- empty desc count ---' . PHP_EOL;
echo $pdo->query("SELECT COUNT(*) FROM menu_items WHERE description IS NULL OR description = ''")->fetchColumn() . PHP_EOL;
echo '--- best sellers ---' . PHP_EOL;
echo $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_best_seller = 1")->fetchColumn() . PHP_EOL;
