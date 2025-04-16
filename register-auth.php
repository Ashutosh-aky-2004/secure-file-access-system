<?php
session_start();
include 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Check if username exists
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
  header("Location: register.php?error=exists");
  exit;
}

// Insert new user (default role: 'user')
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
if ($stmt->execute([$username, $hash])) {
  header("Location: index.php?registered=1");
} else {
  header("Location: register.php?error=failed");
}
exit;
?>