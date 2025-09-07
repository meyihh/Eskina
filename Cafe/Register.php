<?php
require_once 'config.php';
session_start();
$showOTPForm = false;
$error = null;

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendOTPEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cambriblessmae.bsit@gmail.com';
        $mail->Password = 'kelf yifr huuh wavw'; // ⚠️ Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('cambriblessmae.bsit@gmail.com', 'Eskina Coffee');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = '☕ Welcome to Eskina Coffee! Verify Your Email';
        $mail->Body = "
            <h2>Hi there, Coffee Lover! 🌟</h2>
            <p>Welcome to <strong>Eskina Coffee</strong>! Please verify your email.</p>
            <p>Your OTP is:</p>
            <h1 style='color: #4CAF50;'>$otp</h1>
            <p>Enter this code to complete your registration.</p>
            <p style='color: #888;'>☕ Keep it safe and don’t share it with anyone.</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function isDomainValid($email) {
    $domain = substr(strrchr($email, "@"), 1);
    return $domain && checkdnsrr($domain, "MX");
}

function isPasswordStrong($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register'])) {
        $full_name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $contact = trim($_POST["contact"]);
        $address = trim($_POST["address"]);
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        $confirm = $_POST["confirm"];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (!isDomainValid($email)) {
            $error = "This email domain does not exist. Use a real email.";
        } elseif (!preg_match("/^(09\d{9}|\+639\d{9})$/", $contact)) {
            $error = "Invalid contact number format.";
        } elseif (!isPasswordStrong($password)) {
            $error = "Password must have uppercase, lowercase, number, and special character.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match!";
        } else {
            $check = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $check->bind_param("ss", $email, $username);
            $check->execute();
            $existing = $check->get_result();

            if ($existing->num_rows > 0) {
                $error = "Email or username already in use.";
            } else {
                $otp = generateOTP();
                $_SESSION['register'] = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'contact' => $contact,
                    'address' => $address,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'otp' => $otp
                ];
                if (sendOTPEmail($email, $otp)) {
                    $showOTPForm = true;
                } else {
                    $error = "Failed to send OTP. Please try again later.";
                }
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $userOTP = trim($_POST['otp']);
        $sessionData = $_SESSION['register'] ?? null;

        if ($sessionData && $userOTP == $sessionData['otp']) {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, contact, address, username, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $sessionData['full_name'], $sessionData['email'], $sessionData['contact'], $sessionData['address'], $sessionData['username'], $sessionData['password']);
            if ($stmt->execute()) {
                unset($_SESSION['register']);
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    Swal.fire({title: 'Registration Successful!', text: 'Redirecting...', icon: 'success'});
                </script>";
                header("Refresh: 2; URL=landing.php");
                exit();
            }
        } else {
            $error = "Invalid OTP.";
            $showOTPForm = true;
        }
    }
}

// ✅ AJAX endpoint for email validation
if (isset($_GET['check_email'])) {
    $email = $_GET['check_email'];
    $valid = (filter_var($email, FILTER_VALIDATE_EMAIL) && isDomainValid($email));
    echo json_encode(['valid' => $valid]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Eskina Coffee | Register</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
    body, html { height: 100%; }
    .container { display: flex; height: 100vh; }
    .left, .right { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; }
    .left { background: #fff; flex-direction: column; text-align: center; }
    .left img { max-width: 100%; max-height: 80%; object-fit: contain; opacity: 0.7; }
    .right { background: #5c3d2e; color: white; flex-direction: column; }
    .form-wrapper { background-color: rgba(255, 255, 255, 0.08); padding: 30px 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); width: 100%; max-width: 400px; backdrop-filter: blur(4px); }
    .form-box h2 { margin-bottom: 20px; color: white; text-align: center; }
    form { display: flex; flex-direction: column; }
    input { padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; background: white; color: #333; font-size: 15px; }
    button.submit-btn { padding: 12px; background-color: #fff; color: #5c3d2e; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-bottom: 10px; }
    .toggle-text { font-size: 14px; color: #eee; text-align: center; }
    .toggle-text a { color: #fff; text-decoration: underline; font-weight: bold; cursor: pointer; }
    .error-message { color: #ffbdbd; background: rgba(255,0,0,0.1); padding: 10px; margin-bottom: 10px; border-radius: 6px; font-size: 14px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <img src="eslogo.jpg" alt="Eskina Coffee Logo" />
    </div>
    <div class="right">
      <div class="form-wrapper">
        <div class="form-box">
          <h2><?= $showOTPForm ? 'Verify Your Email' : 'Create an Account' ?></h2>
          <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
          <?php endif; ?>

          <?php if ($showOTPForm): ?>
            <form method="POST">
              <input type="text" name="otp" placeholder="Enter OTP" required />
              <button type="submit" name="verify_otp" class="submit-btn">Verify</button>
            </form>
          <?php else: ?>
            <form method="POST" id="registerForm">
              <input type="text" name="full_name" placeholder="Full Name" required>
              <input type="email" name="email" id="email" placeholder="Email" required>
              <div id="email-status" style="font-size:13px; margin-bottom:10px;"></div>
              <input type="text" name="contact" placeholder="Contact" required>
              <input type="text" name="address" placeholder="Address">
              <input type="text" name="username" placeholder="Username" required>
              <input type="password" name="password" id="password" placeholder="Password" required>
              <input type="password" name="confirm" placeholder="Confirm Password" required>
              <button type="submit" name="register" class="submit-btn" id="signupBtn" disabled>Sign Up</button>
              <div class="toggle-text">Already have an account? <a href="landing.php">Login</a></div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<script>
const emailInput = document.getElementById("email");
const statusDiv = document.getElementById("email-status");
const signupBtn = document.getElementById("signupBtn");
const form = document.getElementById("registerForm");
const passwordInput = document.getElementById("password");

// ✅ Live Email Validation
emailInput.addEventListener("input", () => {
    const email = emailInput.value;
    signupBtn.disabled = true;
    statusDiv.textContent = "Checking...";
    if (email.length > 5) {
        fetch("?check_email=" + encodeURIComponent(email))
            .then(res => res.json())
            .then(data => {
                if (data.valid) {
                    statusDiv.textContent = "✅ Email is valid.";
                    signupBtn.disabled = false;
                } else {
                    statusDiv.textContent = "❌ Invalid or fake email.";
                    signupBtn.disabled = true;
                }
            });
    } else {
        statusDiv.textContent = "";
    }
});

// ✅ Password Strength Validation before submit
form.addEventListener("submit", function(e) {
    const password = passwordInput.value;
    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (!strongRegex.test(password)) {
        e.preventDefault();
        alert("Password must have uppercase, lowercase, number, and special character.");
    }
});
</script>
</body>
</html>
