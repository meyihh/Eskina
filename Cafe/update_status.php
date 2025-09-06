<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Only allow PREPARING or DONE
    if (!in_array($status, ['PREPARING', 'DONE'])) {
        echo "invalid";
        exit;
    }

    $stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
