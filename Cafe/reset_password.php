<?php
require_once 'config.php';
session_start();
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "Passwords do not match.";
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);

        // ✅ Update password and clear remember_token
        $stmt = $conn->prepare("UPDATE users SET password = ?, remember_token = NULL WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['reset_user_id']);

        if ($stmt->execute()) {
            $success = true;

            // ✅ Remove remember_token cookie
            if (isset($_COOKIE['remember_token'])) {
                setcookie("remember_token", "", time() - 3600, "/", "", false, true);
            }

            // ✅ End session
            session_destroy();
        } else {
            $message = "Error resetting password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
    }

    html, body {
      height: 100%;
      overflow: hidden;
      background: #5c3d2e;
    }

    .fade-container {
      opacity: 0;
      animation: fadeIn 1s ease-out forwards;
    }

    .fade-out {
      animation: fadeOut 0.8s ease-in forwards;
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }

    .container {
      display: flex;
      height: 100vh;
    }

    .left, .right {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }

    .left {
      background: #ffffff;
      flex-direction: column;
      text-align: center;
    }

    .left img {
      max-width: 100%;
      height: auto;
      max-height: 80%;
      object-fit: contain;
      opacity: 0.7;
    }

    .right {
      background: #5c3d2e;
      color: white;
      flex-direction: column;
    }

    .form-wrapper {
      background-color: rgba(255, 255, 255, 0.08);
      padding: 30px 25px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 400px;
      backdrop-filter: blur(4px);
    }

    .form-box {
      text-align: center;
    }

    .form-box h2 {
      margin-bottom: 20px;
      color: white;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    .password-wrapper {
      position: relative;
      margin-bottom: 15px;
    }

    input {
      padding: 12px;
      width: 100%;
      border: 1px solid #ccc;
      border-radius: 6px;
      background: white;
      color: #333;
      font-size: 15px;
    }

    input::placeholder {
      color: #888;
    }

 .eye-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: #5c3d2e;
  }

    button.submit-btn {
      padding: 12px;
      background-color: #ffffff;
      color: #5c3d2e;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 10px;
    }

    button.submit-btn:hover {
      background-color: #e0d4cd;
    }

    .toggle-text {
      font-size: 14px;
      color: #eee;
      text-align: center;
    }

    .toggle-text a {
      color: #ffffff;
      text-decoration: underline;
      font-weight: bold;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }

      .left, .right {
        flex: none;
        height: 50%;
        padding: 20px;
      }

      .form-wrapper {
        padding: 25px 20px;
      }
    }

    .error-message {
      color: #ffbdbd;
      background: rgba(255, 0, 0, 0.1);
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 6px;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="fade-container">
    <div class="container">
      <div class="left">
        <img src="eslogo.jpg" alt="Eskina Coffee Logo" />
      </div>
      <div class="right">
        <div class="form-wrapper">
          <div class="form-box">
            <h2>Reset Password</h2>

            <?php if (!empty($message)): ?>
              <div class="error-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="password-wrapper">
                <input type="password" name="password" placeholder="New Password" required>
                <i class="fa-solid fa-eye eye-toggle" onclick="togglePassword(this)"></i>
                </div>
                <div class="password-wrapper">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <i class="fa-solid fa-eye eye-toggle" onclick="togglePassword(this)"></i>
                </div>
              <button type="submit" class="submit-btn">Reset Password</button>
            </form>

            <p class="toggle-text"><a href="landing.php">Back to Login</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php if ($success): ?>
  <script>
    Swal.fire({
      title: "Password Changed!",
      text: "Please log in with your new password.",
      icon: "success",
      confirmButtonText: "Go to Login",
      allowOutsideClick: false
    }).then(() => {
      window.location.href = "landing.php?reset=success";
    });
  </script>
<?php endif; ?>

<script>
  function togglePassword(el) {
    const input = el.previousElementSibling;
    if (input.type === "password") {
      input.type = "text";
      el.classList.remove("fa-eye");
      el.classList.add("fa-eye-slash");
    } else {
      input.type = "password";
      el.classList.remove("fa-eye-slash");
      el.classList.add("fa-eye");
    }
  }
</script>

</body>
</html>
