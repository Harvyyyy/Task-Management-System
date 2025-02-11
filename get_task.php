<?php
require 'db.php';

$taskId = $_GET['id'] ?? null;

if ($taskId) {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($task);
}
?>