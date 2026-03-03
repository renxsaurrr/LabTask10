<?php
// This file demonstrates an explicit JOIN query you can reuse anywhere.
require 'config.php';

$stmt = $pdo->query("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        o.order_id,
        o.product,
        o.amount
    FROM users u
    INNER JOIN orders o ON u.user_id = o.user_id
    ORDER BY u.user_id DESC
");
$joined = $stmt->fetchAll(PDO::FETCH_ASSOC);