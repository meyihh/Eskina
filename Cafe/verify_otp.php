<?php
session_start();
$message = "";
$verified = false; // ✅ track if OTP was verified

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);

    if (
        isset($_SESSION['reset_otp'], $_SESSION['otp_expiry']) &&
        time() <= $_SESSION['otp_expiry'] &&
        intval($otp) == intval($_SESSION['reset_otp'])
    ) {
        // ✅ OTP valid
        $verified = true;
    } else {
        $message = "Invalid or expired OTP.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

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

    input {
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      background: white;
      color: #333;
      font-size: 15px;
    }

    input::placeholder {
      color: #888;
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
            <h2>Verify OTP</h2>

            <?php if (!empty($message)): ?>
              <div class="error-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
              <input type="text" name="otp" placeholder="Enter OTP" required>
              <button type="submit" class="submit-btn">Verify</button>
            </form>

            <p class="toggle-text"><a href="forgot_password.php">Resend OTP</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($verified): ?>
  <script>
    Swal.fire({
      icon: 'success',
      title: 'Verification complete',
      text: 'You may now reset your password.',
      confirmButtonColor: '#5c3d2e',
      confirmButtonText: 'OK'
    }).then(() => {
      window.location.href = 'reset_password.php';
    });
  </script>
  <?php endif; ?>
</body>
</html>
