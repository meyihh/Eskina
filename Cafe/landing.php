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
  <style>
    /* Page Loader */
    .loader-wrapper {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      display: none;
      align-items: center;
      justify-content: center;
      width: 180px;
      height: 180px;
      font-family: "Inter", sans-serif;
      font-size: 1.2em;
      font-weight: 300;
      color: white;
      border-radius: 50%;
      background-color: transparent;
      z-index: 9999;
    }

    .loader {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: 50%;
      background-color: transparent;
      animation: loader-rotate 2s linear infinite;
      z-index: 0;
    }

    @keyframes loader-rotate {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 10px 20px 0 #fff8dc inset,   /* bright beige */
      0 20px 30px 0 #d2691e inset,   /* chocolate brown */
      0 60px 60px 0 #8b4513 inset;   /* dark brown */
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 10px 20px 0 #fff8dc inset,
      0 20px 10px 0 #f5deb3 inset,   /* wheat/tan */
      0 40px 60px 0 #5c3d2e inset;   /* coffee brown */
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 10px 20px 0 #fff8dc inset,
      0 20px 30px 0 #d2691e inset,
      0 60px 60px 0 #8b4513 inset;
  }
}

.loader-letter {
  color: #fff8dc; /* bright beige letters for maximum contrast */
}


    .loader-letter {
      display: inline-block;
      opacity: 0.4;
      transform: translateY(0);
      animation: loader-letter-anim 2s infinite;
      z-index: 1;
      border-radius: 50ch;
      border: none;
    }

    .loader-letter:nth-child(1) { animation-delay: 0s; }
    .loader-letter:nth-child(2) { animation-delay: 0.1s; }
    .loader-letter:nth-child(3) { animation-delay: 0.2s; }
    .loader-letter:nth-child(4) { animation-delay: 0.3s; }
    .loader-letter:nth-child(5) { animation-delay: 0.4s; }
    .loader-letter:nth-child(6) { animation-delay: 0.5s; }
    .loader-letter:nth-child(7) { animation-delay: 0.6s; }
    .loader-letter:nth-child(8) { animation-delay: 0.7s; }
    .loader-letter:nth-child(9) { animation-delay: 0.8s; }
    .loader-letter:nth-child(10) { animation-delay: 0.9s; }

    @keyframes loader-letter-anim {
      0%, 100% { opacity: 0.4; transform: translateY(0); }
      20% { opacity: 1; transform: scale(1.15); }
      40% { opacity: 0.7; transform: translateY(0); }
    }
  </style>
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
