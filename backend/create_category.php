<?php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];
$rawName = $_POST['category_name'] ?? '';

$name = trim($rawName);
if ($name === '') { header("Location: dashboard.php?err=empty"); exit; }

$sanitized = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

$check = $conn->prepare("SELECT 1 FROM categories WHERE username=? AND name=?");
$check->bind_param("ss", $username, $sanitized);
$check->execute();
$check->store_result();
if ($check->num_rows) { header("Location: dashboard.php?err=exists"); exit; }

$stmt = $conn->prepare("INSERT INTO categories (username, name) VALUES (?,?)");
$stmt->bind_param("ss", $username, $sanitized);
$stmt->execute();

header("Location: dashboard.php?ok=cat_add");
?>