<?php
require_once 'config.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: landing.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$username = strtolower(trim($_SESSION['user']['username']));

// Only allow .dtr accounts
if (substr($username, -4) !== ".dtr") {
    header("Location: main.php");
    exit();
}

$message = "";

// Fetch user details from the database
$stmt = $conn->prepare("SELECT full_name, email, contact, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Define $uploadDir at the top to make it available everywhere
$uploadDir = "uploads/dtr/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Handle Time In / Time Out submissions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    $photoPath = null;

    // Save selfie if taken with camera
    if (!empty($_POST['selfie'])) {
        $data = $_POST['selfie'];
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $fileName = time() . "_selfie.png";
        $photoPath = $uploadDir . $fileName;
        file_put_contents($photoPath, $data);
    }

    $timestamp = date("Y-m-d H:i:s");

    if ($action === "time_in") {
        if (empty($_POST['selfie'])) {
            $message = "⚠️ Please capture a photo before recording Time In!";
        } else {
            $stmt = $conn->prepare("INSERT INTO dtr_logs (user_id, time_in, photo_in) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $timestamp, $photoPath);
            if ($stmt->execute()) $message = "✅ Time In recorded successfully!";
        }
    } elseif ($action === "time_out") {
        $stmt = $conn->prepare("SELECT id FROM dtr_logs WHERE user_id=? AND time_out IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $log_id = $row['id'];
            if (empty($_POST['selfie'])) {
                $message = "⚠️ Please capture a photo before recording Time Out!";
            } else {
                $update = $conn->prepare("UPDATE dtr_logs SET time_out=?, photo_out=? WHERE id=?");
                $update->bind_param("ssi", $timestamp, $photoPath, $log_id);
                if ($update->execute()) $message = "✅ Time Out recorded successfully!";
            }
        } else {
            $message = "⚠️ No active Time In found!";
        }
    }
}

// Disable automatic photo update to prevent filling missing photos
// Removed the previous logic to ensure new captures don't affect old entries

// Fetch logs
$stmt = $conn->prepare("SELECT * FROM dtr_logs WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DTR Dashboard</title>
<link rel="stylesheet" href="dtr.css" />
</head>
<body>

<header>
<h1>LOGO</h1>
<a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <div class="card profile-card">
        <h2>Profile</h2>
        <form method="post" style="text-align:left;">
            <label>Name</label><input type="text" name="name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly>
            <label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
            <label>Contact</label><input type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>" readonly>
            <label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" readonly>
            <label>New Password</label><input type="password" name="password" placeholder="Enter new password" readonly>
            <label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Confirm new password" readonly>
            <div class="profile-btns">
                <button type="button" class="btn" name="edit" onclick="enableEdit()">Edit</button>
                <button type="submit" class="btn" name="save" style="display:none;" onclick="saveEdit()">Save</button>
            </div>
        </form>
    </div>

    <div class="card time-record-card">
        <h2>Daily Time Record</h2>
        <?php if ($message): ?>
            <?php if (strpos($message, '⚠️') === 0): ?>
                <div class="warning"><?= $message ?></div>
            <?php else: ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" id="dtrForm">
            <div class="camera-container">
                <video id="video" autoplay></video>
                <button type="button" class="btn" id="captureBtn">Capture</button>
            </div>
            <div style="margin-top:5px;"> <!-- Unchanged -->
                <button type="submit" name="action" value="time_in" class="btn">Time In</button>
                <button type="submit" name="action" value="time_out" class="btn">Time Out</button>
            </div>
            <input type="hidden" name="selfie" id="selfie">
            <canvas id="canvas" style="display:none;"></canvas>
            <img id="previewImg" style="display:none;" alt="Preview">
        </form>
    </div>

    <div class="card logs-card">
        <h2>Attendance Logs</h2>
        <table id="logsTable">
            <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Photo In</th>
                <th>Time Out</th>
                <th>Photo Out</th>
            </tr>
            <?php while($row = $logs->fetch_assoc()): ?>
            <tr>
                <td><?= date("Y-m-d", strtotime($row['time_in'])) ?></td>
                <td><?= $row['time_in'] ?></td>
                <td>
                    <?php if ($row['photo_in'] && file_exists($row['photo_in'])): ?>
                        <img src="<?= htmlspecialchars($row['photo_in']) ?>" alt="Photo In" class="log-img">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= $row['time_out'] ?? '-' ?></td>
                <td>
                    <?php if ($row['photo_out'] && file_exists($row['photo_out'])): ?>
                        <img src="<?= htmlspecialchars($row['photo_out']) ?>" alt="Photo Out" class="log-img">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- Full-size image modal -->
<div id="imgModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:9999;">
    <span style="position:absolute; top:20px; right:30px; color:white; font-size:30px; cursor:pointer;" onclick="closeModal()">&times;</span>
    <img id="modalImg" style="max-width:90%; max-height:90%; border-radius:10px;">
</div>

<script>
// Camera setup
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const selfieInput = document.getElementById('selfie');
const captureBtn = document.getElementById('captureBtn');
const previewImg = document.getElementById('previewImg');
const logsTable = document.getElementById('logsTable');

navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
.then(stream => { video.srcObject = stream; })
.catch(err => console.error("Camera access denied:", err));

captureBtn.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataURL = canvas.toDataURL('image/png');
    selfieInput.value = dataURL;
    previewImg.src = dataURL;
    previewImg.style.display = 'block';
    video.style.display = 'none'; // Hide video after capture
});

// Submit form when Time In or Time Out is clicked
document.querySelectorAll('button[name="action"]').forEach(button => {
    button.addEventListener('click', (e) => {
        const form = document.getElementById('dtrForm');
        if (!selfieInput.value) {
            alert("Please capture a photo before recording Time In or Time Out!");
            e.preventDefault(); // Prevent form submission
        } else {
            form.submit();
            // Clear selfie input after submission to require a new photo
            setTimeout(() => {
                selfieInput.value = '';
                previewImg.style.display = 'none';
                video.style.display = 'block'; // Show video again
            }, 100); // Slight delay to ensure submission completes
        }
    });
});

// Enable edit mode for profile
function enableEdit() {
    const inputs = document.querySelectorAll('.profile-card input');
    inputs.forEach(input => {
        input.removeAttribute('readonly');
    });
    document.querySelector('.profile-btns .btn[name="edit"]').style.display = 'none';
    document.querySelector('.profile-btns .btn[name="save"]').style.display = 'inline-block';
}

// Handle save action (basic implementation)
function saveEdit() {
    const full_name = document.querySelector('input[name="name"]').value;
    const email = document.querySelector('input[name="email"]').value;
    const contact = document.querySelector('input[name="contact"]').value;
    const address = document.querySelector('input[name="address"]').value;
    const password = document.querySelector('input[name="password"]').value;
    const confirm_password = document.querySelector('input[name="confirm_password"]').value;

    // Simple validation
    if (!full_name || !email || !contact || !address) {
        alert("All fields except password are required!");
        return;
    }
    if (password !== confirm_password) {
        alert("Passwords do not match!");
        return;
    }
    if (password && password.length < 6) {
        alert("Password must be at least 6 characters long!");
        return;
    }

    // AJAX call to update_profile.php
    fetch('update_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `full_name=${encodeURIComponent(full_name)}&email=${encodeURIComponent(email)}&contact=${encodeURIComponent(contact)}&address=${encodeURIComponent(address)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            const inputs = document.querySelectorAll('.profile-card input');
            inputs.forEach(input => {
                input.setAttribute('readonly', 'readonly');
                if (input.name === 'password' || input.name === 'confirm_password') {
                    input.value = ''; // Clear password fields after save
                }
            });
            document.querySelector('.profile-btns .btn[name="save"]').style.display = 'none';
            document.querySelector('.profile-btns .btn[name="edit"]').style.display = 'inline-block';
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('An error occurred: ' + error.message);
    });
}
</script>

</body>
</html>