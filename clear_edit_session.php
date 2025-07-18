<?php
session_start();
if (isset($_SESSION['edit_contact'])) {
    unset($_SESSION['edit_contact']);
}
?>