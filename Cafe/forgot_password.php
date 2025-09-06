<?php
require_once 'config.php';
session_start();
$message = "";
$success = false;

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_expiry'] = time() + 300; // valid 5 minutes

        // Send OTP using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cambriblessmae.bsit@gmail.com'; 
            $mail->Password   = 'kelf yifr huuh wavw'; // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('cambriblessmae.bsit@gmail.com', 'Eskina Coffee');
            $mail->addAddress($email, $user['username']);

            $mail->isHTML(true);
            $mail->Subject = "Your Eskina Coffee Password Reset OTP";
            $mail->Body    = "
                <p>Hello <b>{$user['username']}</b>,</p>
                <p>Your OTP is: <b>{$otp}</b></p>
                <p>This code is valid for 5 minutes.</p>
            ";
            $mail->AltBody = "Hello {$user['username']}, Your OTP is: $otp (valid for 5 minutes).";

            $mail->send();

            $success = true; // ✅ trigger SweetAlert
        } catch (Exception $e) {
            $message = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $message = "No account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
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

    .fade-out {
      animation: fadeOut 0.8s ease-in forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    @keyframes fadeOut {
      from { opacity: 1; }
      to   { opacity: 0; }
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
            <h2>Forgot Password</h2>
            <?php if ($message): ?>
              <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="forgot_password.php" method="POST">
              <input type="email" name="email" placeholder="Enter your email" required>
              <button type="submit" class="submit-btn">Send OTP</button>
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
      title: "OTP Sent!",
      text: "An OTP has been sent to your email.",
      icon: "success",
      confirmButtonColor: "#5c3d2e",
      confirmButtonText: "OK"
    }).then(() => {
      window.location.href = "verify_otp.php";
    });
  </script>
  <?php endif; ?>
</body>
</html>
