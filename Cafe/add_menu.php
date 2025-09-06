<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'eskina';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) die("DB error");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id']);
    $name = trim($_POST['name']);
    $meta = trim($_POST['meta']);
    $price = floatval($_POST['price']);
    $best_seller = intval($_POST['best_seller']);

    $stmt = $mysqli->prepare("INSERT INTO products (category_id, name, meta, price, best_seller) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issdi", $category_id, $name, $meta, $price, $best_seller);
    $stmt->execute();
    $stmt->close();

    header("Location: im.php"); // back to dashboard
    exit;
}
