<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_id'])) {
    $id = $_POST['vehicle_id'];

    // Default to current image
    $imageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageTmp = $_FILES['image']['tmp_name'];
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $uploadPath = 'uploads/' . $imageName;

        if (!move_uploaded_file($imageTmp, $uploadPath)) {
            die("Failed to upload image.");
        }
    }

    $acquisitionDate = new DateTime($_POST['acquisition']);
    $currentDate = new DateTime();
    $age = $currentDate->diff($acquisitionDate)->y;

    // If new image uploaded, include image_url in update query
    if ($imageName) {
        $stmt = $conn->prepare("UPDATE vehicles SET
            type=?, brand=?, model=?, year=?, age=?, plate=?, lto_mv=?, npc_mv=?,
            reg_no=?, reg_exp=?, chassis=?, engine=?, fuel=?, assignment=?,
            cost=?, acquisition=?, par_to=?, position=?, salary_grade=?, image_url=?
            WHERE vehicle_id=?");

        $stmt->bind_param(
            "sssiiissssssssdsssisi",
            $_POST['type'],
            $_POST['brand'],
            $_POST['model'],
            $_POST['year'],
            $age,
            $_POST['plate'],
            $_POST['lto_mv'],
            $_POST['npc_mv'],
            $_POST['reg_no'],
            $_POST['reg_exp'],
            $_POST['chassis'],
            $_POST['engine'],
            $_POST['fuel'],
            $_POST['assignment'],
            $_POST['cost'],
            $_POST['acquisition'],
            $_POST['par_to'],
            $_POST['position'],
            $_POST['salary_grade'],
            $imageName,
            $id
        );
    } else {
        // No image update
        $stmt = $conn->prepare("UPDATE vehicles SET
            type=?, brand=?, model=?, year=?, age=?, plate=?, lto_mv=?, npc_mv=?,
            reg_no=?, reg_exp=?, chassis=?, engine=?, fuel=?, assignment=?,
            cost=?, acquisition=?, par_to=?, position=?, salary_grade=?
            WHERE vehicle_id=?");

        $stmt->bind_param(
            "sssiisssssssssdssssi",
            $_POST['type'],
            $_POST['brand'],
            $_POST['model'],
            $_POST['year'],
            $age,
            $_POST['plate'],
            $_POST['lto_mv'],
            $_POST['npc_mv'],
            $_POST['reg_no'],
            $_POST['reg_exp'],
            $_POST['chassis'],
            $_POST['engine'],
            $_POST['fuel'],
            $_POST['assignment'],
            $_POST['cost'],
            $_POST['acquisition'],
            $_POST['par_to'],
            $_POST['position'],
            $_POST['salary_grade'],
            $id
        );
    }

    if ($stmt->execute()) {
        header("Location: vehicles.php");
        exit();
    } else {
        echo "Error updating vehicle: " . $stmt->error;
    }
}
?>