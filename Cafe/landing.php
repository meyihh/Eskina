<?php
require_once 'config.php';
session_start();

$loginError = "";
$redirect = false;

// Clear session if redirected from reset password (only once)
if (isset($_GET['reset']) && $_GET['reset'] === "success") {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    }
}

// Login function
function loginUser($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

// Handle login submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (loginUser($conn, $username, $password)) {
        $redirect = true;
    } else {
        $loginError = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Eskina Coffee | Login</title>
  <link rel="stylesheet" href="landing.css" />
</head>
<body>
  <div class="fade-container" id="pageContainer">
    <div class="container">
      <div class="left">
        <img src="eslogo.jpg" alt="Eskina Coffee Logo" />
      </div>
      <div class="right">
        <div class="form-wrapper">
          <div class="form-box">
            <h2>Login to Eskina</h2>
            <form method="POST" id="loginForm">
              <input type="text" name="username" placeholder="Username" required />
              <input type="password" name="password" placeholder="Password" required />
              <?php if (!empty($loginError)): ?>
                <div class="error-message"><?= $loginError ?></div>
              <?php endif; ?>
              <button type="submit" name="login" class="submit-btn">Login</button>
              <div class="toggle-text">
                <a href="forgot_password.php">Forgot Password?</a>
              </div>
              <div class="toggle-text">
                Don’t have an account? <a href="Register.php">Sign up</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loader -->
  <div class="loader-wrapper" id="loader">
    <div class="loader"></div>
    <span class="loader-letter">L</span>
    <span class="loader-letter">o</span>
    <span class="loader-letter">a</span>
    <span class="loader-letter">d</span>
    <span class="loader-letter">i</span>
    <span class="loader-letter">n</span>
    <span class="loader-letter">g</span>
    <span class="loader-letter">.</span>
    <span class="loader-letter">.</span>
    <span class="loader-letter">.</span>
  </div>

<script>
  const form = document.getElementById("loginForm");
  const loader = document.getElementById("loader");
  const page = document.getElementById("pageContainer");

  // Show loader on form submit
  form.addEventListener("submit", () => {
    loader.style.display = "flex";
    page.classList.add("fade-out");
  });

  <?php if ($redirect): ?>
    // Show loader during redirect
    loader.style.display = "flex";
    setTimeout(() => {
      const target = "<?= (substr(strtolower(trim($_SESSION['user']['username'])), -4) === '.dtr') ? 'dtr.php' : 'main.php'; ?>";
      window.location.href = target;
    }, 800);
  <?php endif; ?>
</script>

</body>
</html>
