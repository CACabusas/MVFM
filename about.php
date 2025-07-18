<?php
session_start();
require_once "db_connect.php";

$contacts = $conn->query("SELECT * FROM contact ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>About - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color:rgb(37, 47, 65);
            color: #f0f2f5;
            text-align: center;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1E293B;
            padding: 10px 20px;
        }
        .logo {
            display: flex;
            align-items: center;
            height: 35px;
        }
        .logo img {
            width: 100%;
        }
        .nav-links {
            list-style: none;
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
        }
        .nav-links li {
            position: relative;
        }
        .nav-links a {
            color: #f0f2f5;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            padding: 10px;
        }
        .nav-links a:hover {
            cursor: pointer;
        }
        .nav-links li a {
            display: flex;
            align-items: center;
            height: 50%;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
            flex: 1;
        }
        .button {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #ffe400;
            color: black;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        .button:hover {
            background-color:rgb(255, 235, 55);
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
        .about-cards {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .card {
            background-color: #f0f2f5;
            padding: 20px;
            border-radius: 12px;
            flex: 1;
            min-width: 280px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        h1, p {
            color: #1E293B;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php">
            <img class="logo" src="logo simple.png" alt="Logo">
        </a>
        <ul class="nav-links">
            <li><a href="login.php">Log In</a></li>
        </ul>
    </nav>

    <div class="container">
        <div style="text-align: left;">
            <a class="button" href="login.php">Back</a>
        </div>

        <div class="about-cards">
            <div class="card" style="padding: 0;">
                <img src="uncropped photo.jpg" alt="Photo">
            </div>

            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <h1>About</h1>
                <p>
                    <?php
                    $about_text = file_exists('about_content.txt') ? file_get_contents('about_content.txt') : 'Description not available.';
                    echo nl2br(htmlspecialchars($about_text));
                    ?>
                </p>
                <p style="font-size: 12px; margin-bottom: 6px;">This system is developed by:</p>
                <div style="text-align: center; color: #1E293B;">
                    <div style="margin-bottom: 4px;">
                        <div style="font-weight: bold; font-size: 9px;">System Designer / Project Leader</div>
                        <div style="font-size: 12px;">Princess Jane Fatima C. Longcob</div>
                    </div>
                    <div style="margin-bottom: 4px;">
                        <div style="font-weight: bold; font-size: 9px;">UI/UX Designer</div>
                        <div style="font-size: 12px;">Christine Jay F. Fuentes</div>
                    </div>
                    <div style="margin-bottom: 4px;">
                        <div style="font-weight: bold; font-size: 9px;">Fullstack Developer</div>
                        <div style="font-size: 12px;">Carl Axel C. Cabusas</div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <img src="company-logo.png" alt="Company Logo" style="height: 100px; width: 100px;">
                </div>
            </div>
        </div>
    </div>

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