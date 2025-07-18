<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "mvfm";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die(json_encode(['success' => false, 'message' => 'Invalid JSON data']));
    }

    $inputUserType = $data['user_type'] ?? '';
    $inputPassword = $data['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT user_id, password_hash, user_type FROM user WHERE user_type = :user_type");
        $stmt->bindParam(':user_type', $inputUserType);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($inputPassword, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect admin to misc.php, others to dashboard.php
            $redirectPage = ($user['user_type'] === 'admin') ? 'misc.php' : 'dashboard.php';
            echo json_encode(['success' => true, 'redirect' => $redirectPage]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        html, body {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(to top, #1E293B, #334155, #64748B);
            color: #333;
        }
        .content-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .main-container {
            display: flex;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 1200px;
            overflow: visible;
            position: relative;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .login-section {
            width: 45%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #1E293B;
            z-index: 1;
        }
        .logo-container {
            margin-bottom: 30px;
            text-align: center;
        }
        .logo-container img {
            max-width: 200px;
            height: auto;
        }
        .system-title {
            color: #1E293B;
            font-size: 2rem;
            font-weight: 700;
            margin: 20px 0 10px;
            text-align: center;
        }
        .system-title span {
            color: #ffe400;
        }
        .login-form {
            width: 100%;
            margin-top: 20px;
        }
        .form-inner {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .input-box {
            position: relative;
            margin-bottom: 25px;
        }
        .input-field {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 15px 20px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 16px;
            color: black;
            background: white;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .input-field:focus {
            border-color: #1E293B;
            outline: none;
        }
        .label {
            position: absolute;
            top: 15px;
            left: 20px;
            color: #64748B;
            font-size: 16px;
            font-weight: 400;
            transition: all 0.3s;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }
        .input-field:focus ~ .label,
        .input-field:not(:placeholder-shown) ~ .label {
            top: -10px;
            left: 15px;
            font-size: 12px;
            color: #1E293B;
            background: white;
        }
        .icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            font-size: 20px;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #ffe400;
            color: black;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #ffe400;
            box-shadow: 0 0 3px rgb(255, 236, 65), 0 0 6px rgb(255, 236, 65), 0 0 9px rgb(255, 236, 65);
        }
        .video-section {
            width: 55%;
            position: relative;
        }
        .video-container {
            width: 100%;
            height: 100%;
        }
        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .resources-section {
            width: 100%;
            margin-top: 40px;
            padding: 20px;
            background: #F8FAFC;
            border-radius: 10px;
        }
        .resources-title {
            color: #1E293B;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }
        .dropdown-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }
        .dropdown-btn {
            width: 100%;
            padding: 12px 15px;
            background: #1E293B;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .dropdown-btn:hover {
            background: #0F172A;
        }
        @media (max-width: 900px) {
            .main-container {
                flex-direction: column;
                width: 95%;
            }
            .login-section, .video-section {
                width: 100%;
            }
            .video-section {
                height: 300px;
            }
        }
        @media (max-width: 500px) {
            .login-section {
                padding: 30px 20px;
            }
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px 40px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }
        .modal-body a {
            display: block;
            padding: 10px 0;
            color: #1E293B;
            font-size: 14px;
            text-decoration: none;
            border-bottom: 1px solid #E2E8F0;
        }

        .modal-body a:hover {
            color: #00BFFF;
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            color: #64748B;
            cursor: pointer;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .contact-about-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .info-btn {
            padding: 12px 20px;
            color: #ffe400;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .info-btn:hover {
            text-decoration: underline;
        }
        .divider {
            width: 1px;
            height: 20px;
            background-color: #ffe400;
            align-self: center;
        }
        .user-type-container {
            width: 100%;
            margin-bottom: 25px;
            position: relative;
        }
        .user-type-select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 16px;
            color: black;
            background: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
        }
        .user-type-select:focus {
            border-color: #1E293B;
            outline: none;
        }
        .user-type-label {
            position: absolute;
            top: -10px;
            left: 15px;
            font-size: 12px;
            color: #1E293B;
            background: white;
            padding: 0 5px;
        }
        .select-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            font-size: 20px;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="content-wrapper">
        <!-- Main Container -->
        <div class="main-container">
            <!-- Login Section -->
            <div class="login-section">
                <div class="logo-container">
                    <img src="logo simple.png" alt="NPC Logo">
                    <h1 class="system-title">
                        <span>MINGEN</span> 
                        <span>Vehicle</span> 
                        <span>Fleet</span> 
                        <span>Management</span>
                    </h1>
                </div>

                <!-- Login Form -->
                <form class="login-form" action="#" autocomplete="off">
                    <div class="form-inner">
                        <div class="user-type-container">
                            <select class="user-type-select" id="user-type" required>
                                <option value="" selected disabled>Select user type</option>
                                <option value="officer">Officer</option>
                                <option value="admin">Admin</option>
                            </select>
                            <label for="user-type" class="user-type-label">User Type</label>
                            <i class='bx bx-chevron-down select-icon'></i>
                        </div>
                        
                        <div class="input-box">
                            <input type="password" class="input-field" id="log-pass" placeholder=" " required>
                            <label for="log-pass" class="label">Password</label>
                            <i class='bx bx-lock-alt icon'></i>
                        </div>
                        <button type="submit" class="btn-submit">
                            Log In <i class='bx bx-log-in'></i>
                        </button>
                    </div>
                </form>

                <!-- Resources Section -->
                <div class="resources-section">
                    <h3 class="resources-title">Resources</h3>
                    <div class="dropdown-container">
                        <button class="dropdown-btn" onclick="openModal('forms')">Forms<i class='bx bx-chevron-right'></i></button>
                        <button class="dropdown-btn" onclick="openModal('policies')">Policies<i class='bx bx-chevron-right'></i></button>
                        <button class="dropdown-btn" onclick="openFeedbackModal()" style="background:rgb(0, 107, 126);">Send Feedback<i class='bx bx-message-dots'></i></button>
                    </div>
                </div>
            </div>

            <!-- Video Section -->
            <div class="video-section">
                <div class="video-container">
                    <video autoplay muted loop>
                        <source src="video-cropped-480p.mp4" type="video/mp4"> <!-- The original 1080p video is too big for GitHub, so the video is downscaled to 480p to bypass the issue -->
                    </video>
                </div>
            </div>
        </div>
    </div>

    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 id="modal-title"></h2>
            <div id="modal-body" class="modal-body">
                <!-- Dynamic content will go here -->
            </div>
        </div>
    </div>

    <div id="feedbackModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" onclick="closeModal('feedbackModal')">&times;</span>
            <h2>Send Us Feedback</h2>
            <p>Scan the QR code below to submit your feedback:</p>
            <img src="uploads/feedback_qr.png" alt="Feedback QR Code" style="max-width: 300px; margin-top: 20px;" onerror="this.onerror=null; this.src='uploads/404.png';">
        </div>
    </div>

    <script>
        document.querySelector(".login-form").addEventListener("submit", function(event) {
            event.preventDefault();

            const userType = document.getElementById("user-type").value;
            const passwordInput = document.getElementById("log-pass").value;

            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_type: userType,
                    password: passwordInput
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || "dashboard.php";
                } else {
                    alert(data.message || "Login failed.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Login failed. Please check console for details.");
            });
        });

        function printPDF(filename) {
            var link = document.createElement('a');
            link.href = filename;
            link.target = '_blank';
            link.click();
        }

        function openModal(type) {
            const modal = document.getElementById("resourceModal");
            const title = document.getElementById("modal-title");
            const body = document.getElementById("modal-body");

            // Fetch data from server
            fetch('get_resources.php?type=' + type)
                .then(response => response.json())
                .then(data => {
                    title.textContent = type === "forms" ? "Available Forms" : "Policies";
                    let html = '';
                    
                    data.forEach(item => {
                        html += `<a href="${item.form_url}" target="_blank">${item.form_name}</a>`;
                    });
                    
                    body.innerHTML = html;
                    modal.style.display = "block";
                })
                .catch(error => {
                    console.error("Error:", error);
                    body.innerHTML = "Failed to load resources. Please try again.";
                    modal.style.display = "block";
                });
        }

        function closeModal() {
            document.getElementById("resourceModal").style.display = "none";
        }

        function openFeedbackModal() {
            document.getElementById("feedbackModal").style.display = "block";
        }

        function closeModal(modalId = "resourceModal") {
            document.getElementById(modalId).style.display = "none";
        }

        // Optional: Close modal on outside click
        window.onclick = function(event) {
            const resourceModal = document.getElementById("resourceModal");
            const feedbackModal = document.getElementById("feedbackModal");
            if (event.target == resourceModal) {
                modal.style.display = "none";
            }
            if (event.target === feedbackModal) {
                feedbackModal.style.display = "none";
            }
        };
    </script>

    <footer style="width: 100%; text-align: center; padding: 20px 10px; background: #1E293B; color: #F8FAFC; font-size: 12px;">
        &copy; <?php echo date("Y"); ?> National Power Corporation - Mindanao Generation
        <br> Information System & Technology Division (ISTD)
        <br> All rights reserved

        <!-- Contact and About Buttons -->
        <div class="contact-about-container">
            <a class="info-btn" href="contact.php">Contact</a>
            <span class="divider"></span>
            <a class="info-btn" href="about.php">About</a>
        </div>
    </footer>
</body>
</html>
