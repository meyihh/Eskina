<?php
session_start();

// 🔒 Prevent cached pages (forces reload from server)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // HTTP 1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// ✅ Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: landing.php");
    exit();
}
?>
