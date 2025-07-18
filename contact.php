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
    <title>Contact - NPC MVFM System</title>
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
        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .contact-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .contact-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .contact-info {
            padding: 15px;
        }
        .contact-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #1e293b;
        }
        .contact-detail {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 3px;
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
        .search-filter-container {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
        }
        .search-bar {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .search-bar i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
            max-width: 1280px;
        }
        .filter-btn {
            margin-bottom: 20px;
            padding: 5px 20px;
            font-size: 16px;
            background-color:#e2e8f0;
            color: #1e293b;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-btn:hover {
            background: #e0e0e0;
        }
        .filter-btn.active {
            background: #ffe400;
            color: #1e293b;
        }
        .filter-btn i {
            font-size: 14px;
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

        <h1>Contact</h1>

        <div class="search-filter-container">
            <div class="search-bar">
                <input type="text" id="contactSearch" placeholder="Search contacts..." onkeyup="filterContacts()">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterByType('all')">All</button>
                <button class="filter-btn" onclick="filterByType('Driver')">
                    <i class="fas fa-car"></i> Drivers
                </button>
                <button class="filter-btn" onclick="filterByType('Supplier')">
                    <i class="fas fa-user-tag"></i> Suppliers
                </button>
                <button class="filter-btn" onclick="filterByType('End-User')">
                    <i class="fas fa-user-check"></i> End Users
                </button>
            </div>
        </div>

        <div class="contacts-grid">
            <?php if ($contacts->num_rows > 0): ?>
                <?php while ($contact = $contacts->fetch_assoc()): ?>
                    <div class="contact-card" data-name="<?php echo htmlspecialchars(strtolower($contact['name'])); ?>" data-type="<?php echo htmlspecialchars($contact['contact_type']); ?>">
                        <img src="<?php echo htmlspecialchars($contact['image_url']); ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>" class="contact-photo" onerror="this.src='uploads/404.png'">
                        <div class="contact-info">
                            <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                            <div class="contact-detail"><i class="fas fa-user"></i> <?php echo htmlspecialchars($contact['contact_type']); ?></div>
                            <div class="contact-detail"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['number']); ?></div>
                            <div class="contact-detail"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1;">No contacts found.</p>
            <?php endif; ?>
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

    <script>
        // Filter contacts by search term
        function filterContacts() {
            const searchTerm = document.getElementById('contactSearch').value.toLowerCase();
            const contactCards = document.querySelectorAll('.contact-card');
            
            contactCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const isVisible = name.includes(searchTerm);
                card.style.display = isVisible ? '' : 'none';
            });
        }

        // Filter contacts by type
        function filterByType(type) {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(type) || (type === 'all' && btn.textContent.includes('All'))) {
                    btn.classList.add('active');
                }
            });
            
            const contactCards = document.querySelectorAll('.contact-card');
            
            contactCards.forEach(card => {
                const cardType = card.getAttribute('data-type');
                const shouldShow = type === 'all' || cardType === type;
                card.style.display = shouldShow ? '' : 'none';
            });
        }

        // Initialize - show all contacts by default
        document.addEventListener('DOMContentLoaded', function() {
            filterByType('all');
        });
    </script>
</body>
</html>