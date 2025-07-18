<?php
session_start();
require_once "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_contact'])) {
    $contact_id = intval($_POST['contact_id']);
    $result = $conn->query("SELECT * FROM contact WHERE contact_id = $contact_id");
    if ($result->num_rows > 0) {
        $_SESSION['edit_contact'] = $result->fetch_assoc();
    }
}
?>