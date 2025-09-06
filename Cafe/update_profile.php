<?php
require_once 'config.php';
session_start();

// Force JSON response
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user']['id'];

// Get data from POST request
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
$contact   = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$address   = isset($_POST['address']) ? trim($_POST['address']) : '';
$password  = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate inputs
if (empty($full_name) || empty($email) || empty($contact) || empty($address)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'All fields except password are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if ($password && strlen($password) < 6) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit();
}

// Build query depending on whether password is updated
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users 
        SET full_name = ?, email = ?, contact = ?, address = ?, password = ?
        WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $email, $contact, $address, $hashed_password, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users 
        SET full_name = ?, email = ?, contact = ?, address = ?
        WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $contact, $address, $user_id);
}

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database preparation failed: ' . $conn->error]);
    exit();
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
