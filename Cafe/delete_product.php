<?php
header('Content-Type: application/json');

$mysqli = new mysqli("localhost", "root", "", "eskina");
if ($mysqli->connect_errno) { echo json_encode(["success"=>false]); exit; }

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id']);

$mysqli->query("DELETE FROM products WHERE id=$id");

echo json_encode(["success" => $mysqli->affected_rows > 0]);
