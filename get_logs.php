<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_SESSION['role'] === 'admin'
    ? "SELECT l.*, u.username FROM syscall_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50"
    : "SELECT l.*, u.username FROM syscall_logs l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 50";

$stmt = $db->prepare($query);
$stmt->execute($_SESSION['role'] === 'admin' ? [] : [$_SESSION['user_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($logs);
