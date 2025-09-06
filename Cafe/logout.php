<?php
session_start();


// 🔒 Prevent cached pages (works with logout.php too)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    session_unset();
    session_destroy();

    if (isset($_COOKIE['remember_token'])) {
        setcookie("remember_token", "", time() - 3600, "/", "", false, true);
    }

    header("Location: landing.php");
    exit();
} else {
    header("Location: landing.php");
    exit();
}
