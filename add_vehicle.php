<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST['type'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $age = $_POST['age'];
    $plate = $_POST['plate'];
    $lto_mv = $_POST['lto_mv'];
    $npc_mv = $_POST['npc_mv'];
    $reg_no = $_POST['reg_no'];
    $reg_exp = $_POST['reg_exp'];
    $chassis = $_POST['chassis'];
    $engine = $_POST['engine'];
    $fuel = $_POST['fuel'];
    $assignment = $_POST['assignment'];
    $cost = $_POST['cost'];
    $acquisition = $_POST['acquisition'];
    $par_to = $_POST['par_to'];
    $position = $_POST['position'];
    $salary_grade = $_POST['salary_grade'];

    $imageName = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageTmp = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $uploadPath = 'uploads/' . $imageName;

        if (!move_uploaded_file($imageTmp, $uploadPath)) {
            die("Failed to upload image.");
        }
    }

    $acquisitionDate = new DateTime($acquisition);
    $currentDate = new DateTime();
    $age = $currentDate->diff($acquisitionDate)->y;

    $stmt = $conn->prepare("INSERT INTO vehicles (
        type, brand, model, year, age, plate, lto_mv, npc_mv,
        reg_no, reg_exp, chassis, engine, fuel, assignment,
        cost, acquisition, par_to, position, salary_grade, image_url
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssiisssssssssssssss", $type, $brand, $model, $year, $age, $plate, $lto_mv, $npc_mv,
        $reg_no, $reg_exp, $chassis, $engine, $fuel, $assignment,
        $cost, $acquisition, $par_to, $position, $salary_grade, $imageName);

    if ($stmt->execute()) {
        header("Location: vehicles.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>