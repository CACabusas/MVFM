<?php
session_start();
require_once "db_connect.php";
require_once 'lib/phpqrcode/qrlib.php'; // Make sure to uncomment ";extension=gd" in [\xampp\php\php.ini], then restart Apache

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables for edit mode
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_type = '';
$edit_url = '';
$edit_source_type = 'file';
$qr_url = isset($_SESSION['qr_url']) ? $_SESSION['qr_url'] : '';
$qr_file = isset($_SESSION['qr_file']) ? $_SESSION['qr_file'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_form'])) {
        // Handle new form/policy addition or update
        $form_name = $conn->real_escape_string($_POST['form_name']);
        $form_type = $conn->real_escape_string($_POST['form_type']);
        
        // Check if we're in edit mode
        $edit_mode = isset($_POST['edit_id']) && !empty($_POST['edit_id']);
        $edit_id = $edit_mode ? intval($_POST['edit_id']) : 0;
        
        // Handle file upload or URL
        if (isset($_FILES['form_file']) && $_FILES['form_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'resources/';
            $file_name = basename($_FILES['form_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['form_file']['tmp_name'], $file_path)) {
                $form_url = $file_path;
                
                // If editing, delete old file if it's a local resource
                if ($edit_mode) {
                    $old_file = $conn->query("SELECT form_url FROM forms WHERE form_id = $edit_id")->fetch_assoc();
                    if ($old_file && strpos($old_file['form_url'], 'http') !== 0 && file_exists($old_file['form_url'])) {
                        unlink($old_file['form_url']);
                    }
                }
                
                if ($edit_mode) {
                    $conn->query("UPDATE forms SET form_name = '$form_name', form_url = '$form_url', form_type = '$form_type' WHERE form_id = $edit_id");
                    $success = "Form/Policy updated successfully!";
                } else {
                    $conn->query("INSERT INTO forms (form_name, form_url, form_type) VALUES ('$form_name', '$form_url', '$form_type')");
                    $success = "Form/Policy added successfully!";
                }
            } else {
                $error = "Failed to upload file.";
            }
        } elseif (isset($_POST['form_url']) && !empty($_POST['form_url'])) {
            // Handle external URL
            $form_url = $conn->real_escape_string($_POST['form_url']);
            
            // If editing, delete old file if it's a local resource
            if ($edit_mode) {
                $old_file = $conn->query("SELECT form_url FROM forms WHERE form_id = $edit_id")->fetch_assoc();
                if ($old_file && strpos($old_file['form_url'], 'http') !== 0 && file_exists($old_file['form_url'])) {
                    unlink($old_file['form_url']);
                }
            }
            
            if ($edit_mode) {
                $conn->query("UPDATE forms SET form_name = '$form_name', form_url = '$form_url', form_type = '$form_type' WHERE form_id = $edit_id");
                $success = "Form/Policy updated successfully!";
            } else {
                $conn->query("INSERT INTO forms (form_name, form_url, form_type) VALUES ('$form_name', '$form_url', '$form_type')");
                $success = "Form/Policy added successfully!";
            }
        } else {
            // No file or URL provided, but if we're editing and not changing the file/URL, just update the name/type
            if ($edit_mode) {
                $conn->query("UPDATE forms SET form_name = '$form_name', form_type = '$form_type' WHERE form_id = $edit_id");
                $success = "Form/Policy updated successfully!";
            } else {
                $error = "Please provide either a file or URL.";
            }
        }
        
        // Reset edit mode after submission
        if (isset($success)) {
            $_SESSION['success'] = $success;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['delete_form'])) {
        // Handle form deletion
        $form_id = intval($_POST['form_id']);
        $result = $conn->query("SELECT form_url FROM forms WHERE form_id = $form_id");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Delete file if it's a local resource
            if (strpos($row['form_url'], 'http') !== 0 && file_exists($row['form_url'])) {
                unlink($row['form_url']);
            }
            $conn->query("DELETE FROM forms WHERE form_id = $form_id");
            $success = "Form/Policy deleted successfully!";
        }

        if (isset($success)) {
            $_SESSION['success'] = $success;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['edit_form'])) {
        // Prepare edit mode
        $form_id = intval($_POST['form_id']);
        $result = $conn->query("SELECT * FROM forms WHERE form_id = $form_id");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $edit_mode = true;
            $edit_id = $row['form_id'];
            $edit_name = $row['form_name'];
            $edit_type = $row['form_type'];
            $edit_url = $row['form_url'];
            $edit_source_type = strpos($row['form_url'], 'http') === 0 ? 'url' : 'file';
        }
    } elseif (isset($_POST['cancel_edit'])) {
        // Cancel edit mode
        $edit_mode = false;
        $edit_id = 0;
    } elseif (isset($_POST['change_password'])) {
        // Handle password change - only for admin
        if ($_SESSION['user_type'] !== 'admin') {
            $error = "Unauthorized access.";
        } else {
            $account_type = $_POST['account_type']; // Get selected account type (admin/officer)
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify admin's current password or officer's current password based on selection
            $admin_id = $_SESSION['user_id'];
            if ($account_type === 'admin') {
                // If changing password for admin, use current logged-in user's id
                $result = $conn->query("SELECT password_hash FROM user WHERE user_id = $admin_id AND user_type = 'admin'");
            } else {
                // If changing password for officer, find the officer user ID
                $result = $conn->query("SELECT password_hash FROM user WHERE user_type = 'officer' LIMIT 1");
            }

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (!password_verify($current_password, $row['password_hash'])) {
                    $error = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New password and confirm password do not match.";
                } else {
                    // Get the target user ID based on account type
                    $target_user_result = $conn->query("SELECT user_id FROM user WHERE user_type = '$account_type' LIMIT 1");
                    if ($target_user_result->num_rows === 1) {
                        $target_user = $target_user_result->fetch_assoc();
                        $target_user_id = $target_user['user_id'];

                        // Update the password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $conn->query("UPDATE user SET password_hash = '$hashed_password' WHERE user_id = $target_user_id");
                        $success = ucfirst($account_type) . " password changed successfully!";
                    } else {
                        $error = ucfirst($account_type) . " account not found.";
                    }
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }

        if (isset($success)) {
            $_SESSION['success'] = $success;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['add_contact'])) {
        $name = $_POST['name'];
        $contact_type = $_POST['contact_type'];
        $number = $_POST['number'];
        $email = $_POST['email'];

        // Handle file upload
        $image_url = 'uploads/404.png'; // default image
        if (isset($_FILES['contact_photo']) && $_FILES['contact_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'contact/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($_FILES['contact_photo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('contact_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['contact_photo']['tmp_name'], $file_path)) {
                $image_url = $file_path;
            }
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO contact (name, image_url, contact_type, number, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $image_url, $contact_type, $number, $email);

        if ($stmt->execute()) {
            $success = "Contact added successfully!";
        } else {
            $error = "Error adding contact: " . $conn->error;
        }

        if (isset($success)) {
            $_SESSION['success'] = $success;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['edit_contact'])) {
        $contact_id = intval($_POST['contact_id']);
        $result = $conn->query("SELECT * FROM contact WHERE contact_id = $contact_id");
        if ($result->num_rows > 0) {
            $contact = $result->fetch_assoc();
            $_SESSION['edit_contact'] = $contact;
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['update_contact'])) {
        $contact_id = intval($_POST['contact_id']);
        $name = $_POST['name'];
        $contact_type = $_POST['contact_type'];
        $number = $_POST['number'];
        $email = $_POST['email'];
        
        // Handle file upload
        if (isset($_FILES['contact_photo']) && $_FILES['contact_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'contact/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['contact_photo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('contact_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['contact_photo']['tmp_name'], $file_path)) {
                // Delete old image if it's not the default
                $old_image = $conn->query("SELECT image_url FROM contact WHERE contact_id = $contact_id")->fetch_assoc()['image_url'];
                if ($old_image !== 'uploads/404.png' && file_exists($old_image)) {
                    unlink($old_image);
                }
                $image_url = $file_path;
                $conn->query("UPDATE contact SET image_url = '$image_url' WHERE contact_id = $contact_id");
            }
        }
        
        $stmt = $conn->prepare("UPDATE contact SET name = ?, contact_type = ?, number = ?, email = ? WHERE contact_id = ?");
        $stmt->bind_param("ssssi", $name, $contact_type, $number, $email, $contact_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Contact updated successfully!";
            unset($_SESSION['edit_contact']);
        } else {
            $_SESSION['error'] = "Error updating contact: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['cancel_edit_contact'])) {
        unset($_SESSION['edit_contact']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['delete_contact'])) {
        $contact_id = intval($_POST['contact_id']);
        $result = $conn->query("SELECT image_url FROM contact WHERE contact_id = $contact_id");
        if ($result->num_rows > 0) {
            $image_url = $result->fetch_assoc()['image_url'];
            // Delete image if it's not the default
            if ($image_url !== 'uploads/404.png' && file_exists($image_url)) {
                unlink($image_url);
            }
            $conn->query("DELETE FROM contact WHERE contact_id = $contact_id");
            $_SESSION['success'] = "Contact deleted successfully!";
        } else {
            $_SESSION['error'] = "Contact not found!";
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['generate_qr'])) {
        $qr_url = trim($_POST['qr_url']);
        if (!empty($qr_url)) {
            $qr_file = 'uploads/feedback_qr.png';
            QRcode::png($qr_url, $qr_file, QR_ECLEVEL_H, 6);

            $_SESSION['qr_url'] = $qr_url;
            $_SESSION['qr_file'] = $qr_file;

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['delete_qr'])) {
        $qr_file = isset($_SESSION['qr_file']) ? $_SESSION['qr_file'] : '';
        if (!empty($qr_file) && file_exists($qr_file)) {
            unlink($qr_file);
        }
        unset($_SESSION['qr_url'], $_SESSION['qr_file']);
        $_SESSION['success'] = "QR code deleted successfully.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_about'])) {
        if ($_SESSION['user_type'] === 'admin') {
            $about_text = trim($_POST['about_text']);
            file_put_contents('about_content.txt', $about_text);
            $_SESSION['success'] = "About section updated successfully!";
        } else {
            $_SESSION['error'] = "Unauthorized access.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all forms and policies
$forms = $conn->query("SELECT * FROM forms WHERE form_type = 'form' ORDER BY form_name");
$policies = $conn->query("SELECT * FROM forms WHERE form_type = 'policy' ORDER BY form_name");

// Fetch all contacts
$contacts = $conn->query("SELECT * FROM contact ORDER BY name ASC");

// Check for success messages in session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Check for error messages in session
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Miscellaneous - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
            text-align: center;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1e293b;
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
            color: white;
            text-decoration: none;
            font-size: 16px;
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
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(30, 41, 59, 0.95);
            border-radius: 5px;
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 200px;
            z-index: 10;
        }
        .dropdown-menu li a {
            padding: 10px;
            display: block;
            color: white;
            text-decoration: none;
        }
        .dropdown-menu li a:hover {
            background-color: #FFC107;
            color: black;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(30, 41, 59, 0.95);
            border-radius: 5px;
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 200px;
            z-index: 10;
        }
        .dropdown-menu li a {
            padding: 10px;
            display: block;
            color: white;
            text-decoration: none;
        }
        .dropdown-menu li a:hover {
            background-color: #FFC107;
            color: black;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .nav-links .dropdown {
            position: relative;
            }
        .nav-links .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(30, 41, 59, 0.9);
            list-style: none;
            padding: 0;
            margin: 0;
            display: none;
            min-width: 160px;
            border-radius: 5px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        .nav-links .dropdown-menu li {
            padding: 10px;
        }
        .nav-links .dropdown-menu a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .nav-links .dropdown-menu a:hover {
            background: #FFC107;
            color: black;
        }
        .nav-links .dropdown:hover .dropdown-menu {
            display: block;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 60%;
            position: relative;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .radio-group {
            display: flex;
            gap: 15px;
            margin: 10px 0;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-submit, .btn-cancel {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-submit {
            background-color: #1e293b;
            color: white;
        }
        .btn-cancel {
            background-color: #6b7280;
            color: white;
        }
        .btn-submit:hover {
            background-color: #334155;
        }
        .btn-cancel:hover {
            background-color: #4b5563;
        }
        .close-modal {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }
        .close-modal:hover {
            color: #000;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        table {
            width: 100%;
            max-width: 900px;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            white-space: normal;
            padding: 6px;
            white-space: normal;
            word-wrap: break-word;
            border-bottom: 1px solid #ddd;
            text-align: center;
            font-size: 15px;
        }
        th {
            background-color: #1E293B;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }
        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }
        tr:hover td {
            background-color: #d5e4f5;
        }
        .button {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .button:hover {
            background-color: #334155;
        }
        button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            border-radius: 5px;
            transition: 0.3s ease-in-out;
        }
        button i {
            font-size: 18px;
            margin: 5px;
        }
        button:hover i {
            opacity: 0.7;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .edit-btn:hover {
            background-color: #388E3C;
        }
        .delete-btn:hover {
            background-color: #D32F2F;
        }
        .collapsible {
            cursor: pointer;
            padding: 15px;
            width: 100%;
            border: none;
            text-align: left;
            outline: none;
            font-size: 18px;
            font-weight: 600;
            background-color: #fff;
            border-radius: 8px;
            margin-bottom: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .collapsible:after {
            content: '\002B';
            font-size: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        /* .active:after {
            content: '\2212';
        } */
        .collapsible-content {
            padding: 0 15px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
            background-color: white;
            border-radius: 0 0 8px 8px;
        }
        .collapsible-card {
            margin-bottom: 20px;
        }
        .count-badge {
            background-color: #e2e8f0;
            color: #1e293b;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: normal;
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
        .contact-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        #contactModal .modal-content {
            width: 90%;
            max-width: 500px;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin: 10px auto;
            display: block;
        }
        .search-filter-container {
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .search-bar {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }
        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
            background: #1e293b;
            color: white;
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
            <li><a href="vehicles.php">Vehicles</a></li>
            <li class="dropdown">
                <a>Reports &#9662;</a>
                <ul class="dropdown-menu">
                    <li><a href="selectvehicleReport.php?report=maintenance">Maintenance Reports</a></li>
                    <li><a href="selectvehicleReport.php?report=mileage">Fuel & Mileage Reports</a></li>
                    <li><a href="selectvehicleReport.php?report=history">Vehicle History Reports</a></li>
                </ul>
            </li>
            <li><a href="misc.php">Misc</a></li>
            <li>
                <a href="?logout=true">
                    <img src="icons/logout.png" alt="Log out" style="width:35px; height:35px;">
                </a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="text-align: left;">
            <a class="button" href="dashboard.php">Back</a>
        </div>

        <h1>Miscellaneous</h1>

        <button class="button" onclick="openModal()">Add Form/Policy</button>

        <!-- Modal for Add/Edit Form -->
        <div id="formModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h2><?php echo $edit_mode ? 'Edit Form/Policy' : 'Add New Form/Policy'; ?></h2>

                <?php if ($edit_mode): ?>
                    <div class="edit-notice">
                        <span>You are currently editing: <strong><?php echo htmlspecialchars($edit_name); ?></strong></span>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="form_name">Name</label>
                        <input type="text" id="form_name" name="form_name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Type</label>
                        <select name="form_type" required>
                            <option value="form" <?php echo $edit_type === 'form' ? 'selected' : ''; ?>>Form</option>
                            <option value="policy" <?php echo $edit_type === 'policy' ? 'selected' : ''; ?>>Policy</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Source</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="source_file" name="source_type" value="file" <?php echo $edit_source_type === 'file' ? 'checked' : ''; ?>>
                                <label for="source_file">Upload File</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="source_url" name="source_type" value="url" <?php echo $edit_source_type === 'url' ? 'checked' : ''; ?>>
                                <label for="source_url">External URL</label>
                            </div>
                        </div>

                        <div id="file_source" style="<?php echo $edit_source_type === 'file' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="file" name="form_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <p><small>Upload PDF, Word, or Excel files to the resources folder</small></p>
                            <?php if ($edit_mode && $edit_source_type === 'file'): ?>
                                <p><small>Current file: <a href="<?php echo htmlspecialchars($edit_url); ?>" target="_blank"><?php echo basename($edit_url); ?></a></small></p>
                                <p><small>Leave blank to keep current file</small></p>
                            <?php endif; ?>
                        </div>

                        <div id="url_source" style="<?php echo $edit_source_type === 'url' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="url" name="form_url" placeholder="https://example.com/form" value="<?php echo $edit_source_type === 'url' ? htmlspecialchars($edit_url) : ''; ?>">
                        </div>
                    </div>

                    <button type="submit" name="add_form" class="btn btn-submit">
                        <?php echo $edit_mode ? 'Update Form/Policy' : 'Add Form/Policy'; ?>
                    </button>

                    <?php if ($edit_mode): ?>
                        <button type="submit" name="cancel_edit_contact" name="cancel_edit" class="btn btn-cancel">Cancel</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="collapsible-card" style="flex: 1; min-width: 300px;">
                <button type="button" class="collapsible" style="font-family: 'Poppins', sans-serif;">Manage Forms <span class="count-badge"><?php echo $forms->num_rows; ?></span></button>
                <div class="collapsible-content">
                    <div class="card">
                        <?php if ($forms->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Source</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($form = $forms->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($form['form_name']); ?></td>
                                            <td>
                                                <?php if (strpos($form['form_url'], 'http') === 0): ?>
                                                    <a href="<?php echo htmlspecialchars($form['form_url']); ?>" target="_blank">External Link</a>
                                                <?php else: ?>
                                                    <a href="<?php echo htmlspecialchars($form['form_url']); ?>" target="_blank">Local File</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="form_id" value="<?php echo $form['form_id']; ?>">
                                                    <button type="submit" name="edit_form" class="edit-btn">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="form_id" value="<?php echo $form['form_id']; ?>">
                                                    <button type="submit" name="delete_form" class="delete-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No forms found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="collapsible-card" style="flex: 1; min-width: 300px;">
                <button type="button" class="collapsible" style="font-family: 'Poppins', sans-serif;">Manage Policies <span class="count-badge"><?php echo $policies->num_rows; ?></span></button>
                <div class="collapsible-content">
                    <div class="card">
                        <?php if ($policies->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Source</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($policy = $policies->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($policy['form_name']); ?></td>
                                            <td>
                                                <?php if (strpos($policy['form_url'], 'http') === 0): ?>
                                                    <a href="<?php echo htmlspecialchars($policy['form_url']); ?>" target="_blank">External Link</a>
                                                <?php else: ?>
                                                    <a href="<?php echo htmlspecialchars($policy['form_url']); ?>" target="_blank">Local File</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="form_id" value="<?php echo $policy['form_id']; ?>">
                                                    <button type="submit" name="edit_form" class="edit-btn">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="form_id" value="<?php echo $policy['form_id']; ?>">
                                                    <button type="submit" name="delete_form" class="delete-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No policies found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <h2>Feedback QR Code</h2>
        <form method="POST" style="margin-bottom: 20px;">
            <input type="text" name="qr_url" value="<?php echo htmlspecialchars($qr_url); ?>" placeholder="Enter URL..." required style="width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
            <button type="submit" name="generate_qr" class="button">Generate QR</button>
        </form>

        <?php if (!empty($qr_file) && file_exists($qr_file)): ?>
            <div style="margin-top: 20px;">
                <h3>Generated QR Code:</h3>
                <img src="<?php echo $qr_file; ?>" alt="QR Code" style="max-width: 200px;">
                <p><strong>URL:</strong> <?php echo htmlspecialchars($qr_url); ?></p>
                <form method="POST" style="margin-top: 10px;">
                    <button type="submit" name="delete_qr" class="button" style="background-color: #e74c3c;">Delete QR Code</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['user_type'] === 'admin'): ?>
            <div class="card" style="margin-top: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">    
                <h2>Change Password</h2>
                <form method="POST" style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; align-items: center;">
                    <label for="account_type">Account Type</label>
                    <select id="account_type" name="account_type" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="admin">Transportation Admin Account</option>
                        <option value="officer">System Officer Account</option>
                    </select>

                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

                    <div style="grid-column: span 2; text-align: center; margin-top: 15px;">
                        <button type="submit" name="change_password" class="button">Change Password</button>
                    </div>
                </form>

                <h2>Edit About</h2>
                <form method="POST" style="display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 100%; max-width: 600px;">
                        <textarea name="about_text" rows="6" style="width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;"><?php echo htmlspecialchars(file_exists('about_content.txt') ? file_get_contents('about_content.txt') : ''); ?></textarea>
                    </div>
                    <br>
                    <button type="submit" name="update_about" class="button">Save Changes</button>
                </form>
            </div>
            <br><br>
        <?php endif; ?>

        <h1>Manage Contacts</h1>
        <button class="button" onclick="openContactModal()">Add Contact</button>

        <div class="search-filter-container">
            <div class="search-bar">
                <input type="text" id="contactSearch" placeholder="Search contacts..." onkeyup="filterContacts()">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="search-filter-container">
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
                            <div class="contact-actions">
                                <button type="button" onclick="editContact(<?php echo $contact['contact_id']; ?>)" class="edit-btn"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['contact_id']; ?>">
                                    <button type="submit" name="delete_contact" class="delete-btn" onclick="return confirm('Are you sure you want to delete this contact?');"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1;">No contacts found</p>
            <?php endif; ?>
        </div>
        
        <!-- Contact Modal -->
        <div id="contactModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeContactModal()">&times;</span>
                <h2><?php echo isset($_SESSION['edit_contact']) ? 'Edit Contact' : 'Add New Contact'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if (isset($_SESSION['edit_contact'])): ?>
                        <input type="hidden" name="contact_id" value="<?php echo $_SESSION['edit_contact']['contact_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="contact_name">Name</label>
                        <input type="text" id="contact_name" name="name" required 
                            value="<?php echo isset($_SESSION['edit_contact']) ? htmlspecialchars($_SESSION['edit_contact']['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="contact_name">Type</label>
                        <select id="contact_type" name="contact_type" required>
                            <option value="">Select Type</option>
                            <option value="Driver" <?php echo (isset($_SESSION['edit_contact']) && $_SESSION['edit_contact']['contact_type'] === 'Driver') ? 'selected' : ''; ?>>Driver</option>
                            <option value="Supplier" <?php echo (isset($_SESSION['edit_contact']) && $_SESSION['edit_contact']['contact_type'] === 'Supplier') ? 'selected' : ''; ?>>Supplier</option>
                            <option value="End-User" <?php echo (isset($_SESSION['edit_contact']) && $_SESSION['edit_contact']['contact_type'] === 'End-User') ? 'selected' : ''; ?>>End-User</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="number" required
                            value="<?php echo isset($_SESSION['edit_contact']) ? htmlspecialchars($_SESSION['edit_contact']['number']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Email Address</label>
                        <input type="text" id="contact_email" name="email" required
                            value="<?php echo isset($_SESSION['edit_contact']) ? htmlspecialchars($_SESSION['edit_contact']['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_photo">Photo</label>
                        <input type="file" id="contact_photo" name="contact_photo" accept="image/*">
                        <img id="photoPreview" src="<?php echo isset($_SESSION['edit_contact']) ? htmlspecialchars($_SESSION['edit_contact']['image_url']) : 'uploads/404.png'; ?>" class="photo-preview">
                        <p><small>Leave blank to keep current image</small></p>
                    </div>
                    
                    <button type="submit" name="<?php echo isset($_SESSION['edit_contact']) ? 'update_contact' : 'add_contact'; ?>" class="btn-submit">
                        <?php echo isset($_SESSION['edit_contact']) ? 'Update Contact' : 'Add Contact'; ?>
                    </button>
                    <button type="button" onclick="closeContactModal()" class="btn-cancel">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const coll = document.getElementsByClassName("collapsible");
            
            for (let i = 0; i < coll.length; i++) {
                coll[i].addEventListener("click", function() {
                    this.classList.toggle("active");
                    const content = this.nextElementSibling;
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                    } else {
                        content.style.maxHeight = content.scrollHeight + "px";
                    } 
                });
            }
        });

        // Toggle between file upload and URL input
        document.querySelectorAll('input[name="source_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('file_source').style.display = 
                    this.value === 'file' ? 'block' : 'none';
                document.getElementById('url_source').style.display = 
                    this.value === 'url' ? 'block' : 'none';
                
                if (this.value === 'file') {
                    document.querySelector('input[name="form_url"]').value = '';
                } else {
                    document.querySelector('input[name="form_file"]').value = '';
                }
            });
        });

        function openModal() {
            document.getElementById('formModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('formModal').style.display = 'none';
        }

        // Show modal automatically if in edit mode
        <?php if ($edit_mode): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openModal();
            });
        <?php endif; ?>

        function openContactModal() {
            document.getElementById('contactModal').style.display = 'block';
        }
        
        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
            // Reset form when closing
            document.getElementById('contact_photo').value = '';
            document.getElementById('photoPreview').src = 'uploads/404.png';

            fetch('clear_edit_session.php', { method: 'POST' });
        }

        function editContact(contactId) {
            // Submit the edit form via AJAX
            fetch('update_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'contact_id=' + contactId + '&edit_contact=1'
            }).then(response => {
                // After successful submission, open the modal
                openContactModal();
                window.location.reload(); // Refresh to get the latest data
            });
        }
        
        // Photo preview functionality
        document.getElementById('contact_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('photoPreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('photoPreview').src = 'uploads/404.png';
            }
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        });

        // Auto-open modal if in edit mode
        <?php if (isset($_SESSION['edit_contact'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openContactModal();
            });
        <?php endif; ?>

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
