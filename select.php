<?php

require 'config.php';

$stmt = $pdo->query("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        o.product,
        o.amount
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    ORDER BY u.user_id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>