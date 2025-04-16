<?php
$host = 'localhost';
$dbname = 'secure_syscalls_db';
$user = 'root';     // Default XAMPP/WAMP username
$pass = '';         // Default password (blank for localhost)

try {
  $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>