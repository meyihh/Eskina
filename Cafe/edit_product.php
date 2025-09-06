<?php
header('Content-Type: application/json');

$mysqli = new mysqli("localhost", "root", "", "eskina");
if ($mysqli->connect_errno) { echo json_encode(["success"=>false]); exit; }

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id']);
$name = $mysqli->real_escape_string($data['name']);
$price = floatval($data['price']);

$mysqli->query("UPDATE products SET name='$name', price=$price WHERE id=$id");

echo json_encode(["success" => $mysqli->affected_rows > 0]);
