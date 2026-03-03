<?php
require 'config.php';

if(isset($_GET['delete'])) {
    $user_id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
}
?>