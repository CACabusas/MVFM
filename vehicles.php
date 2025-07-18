<?php
session_start();
require 'db_connect.php';

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

$vehicles = [];
$sql = "SELECT *, 
        TIMESTAMPDIFF(YEAR, acquisition, CURDATE()) AS calculated_age 
        FROM vehicles ORDER BY brand, model";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Vehicles - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        h2 {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .vehicle-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px;
            justify-items: center;
        }
        .vehicle {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            width: 80%;
            height: 100%;
            max-width: 500px;
            max-height: 400px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .vehicle:hover {
            transform: scale(1.03);
        }
        .vehicle img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #ccc;
        }
        .vehicle-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .vehicle-info {
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .add-button {
            width: 40%;
            height: 30px;
            font-size: 16px;
            padding: 3px;
            margin: 15px auto;
            border: none;
            cursor: pointer;
            background: #1e293b;
            color: white;
            font-weight: 600;
            border-radius: 3px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .add-button:hover {
            background: #334155;
            transform: translateY(-2px);
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            width: 700px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 20px;
        }
        .form-grid > div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-grid label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 0;
        }
        .close-icon {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
            transition: 0.3s;
        }
        .close-icon:hover {
            color: red;
            transform: scale(1.2);
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .save-button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            border-radius: 6px;
            background: #3182ce;
            color: white;
            transition: all 0.3s ease-in-out;
        }
        .save-button:hover {
            background: #2b6cb0;
            transform: translateY(-2px);
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
        <div style="text-align: left;">
            <a class="button" href="dashboard.php">Back</a>
        </div>

        <h1>Vehicles</h1>

        <!-- Add Vehicle Button -->
        <div id="showModalBtn" class="add-button"> Add Vehicle</div>

        <button onclick="printVehicle()" class="add-button" style="width: 18%;">Print All Vehicles</button>
        
        <div id="vehicleList" class="vehicle-list"></div>
    </div>

    <!-- Modal (Hidden by Default) -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span id="closeModalBtn" class="close-icon">&times;</span>
            <h3>Vehicle Details</h3>
            <form id="vehicleForm" action="add_vehicle.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div>
                        <label>Vehicle Type</label>
                        <input type="text" name="type" required>

                        <label>Brand / Make</label>
                        <input type="text" name="brand" required>

                        <label>Model</label>
                        <input type="text" name="model" required>

                        <label>Year</label>
                        <input type="number" name="year" min="1900" max="2099" required>

                        <label>Age</label>
                        <input type="number" name="age" min="0" required>

                        <label>Plate Number</label>
                        <input type="text" name="plate" required>

                        <label>LTO M.V. Number</label>
                        <input type="text" name="lto_mv" required>

                        <label>NPC M.V. Number</label>
                        <input type="text" name="npc_mv" required>

                        <label>Registration Number</label>
                        <input type="text" name="reg_no" required>

                        <label>Registration Expiration</label>
                        <input type="date" name="reg_exp" required>
                    </div>
                    <div>
                        <label>Chassis Number</label>
                        <input type="text" name="chassis" required>

                        <label>Engine Number</label>
                        <input type="text" name="engine" required>

                        <label>Fuel Type</label>
                        <input type="text" name="fuel" required>

                        <label>Assignment</label>
                        <input type="text" name="assignment" required>

                        <label>Cost</label>
                        <input type="number" step="0.01" name="cost" required>

                        <label>Acquisition Date</label>
                        <input type="date" name="acquisition" required>

                        <label>PAR To</label>
                        <input type="text" name="par_to" required>

                        <label>Position</label>
                        <input type="text" name="position" required>

                        <label>Salary Grade</label>
                        <input type="number" name="salary_grade" required>

                        <label>Vehicle Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="save-button">Save</button>
            </form>
        </div>
    </div>

    <!-- Vehicle Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px; width: 100%;">
            <span class="close-icon" onclick="document.getElementById('detailsModal').style.display='none'">&times;</span>
            <div id="vehicleDetailsContent"></div>
            <div style="margin-top: 20px;">
                <button class="save-button" onclick="openEditModal()" style="background: #28a745; width: 20%; margin-right: 25px;">Edit Details</button>
                <button class="save-button" style="background:#e53e3e; width: 20%; margin-right: 25px;" onclick="deleteVehicle()">Delete Vehicle</button>
                <button class="save-button" onclick="printVehicle(selectedVehicle)" style="width: 15%; background-color: #1e293b;">Print</button>
            </div>
        </div>
    </div>

    <script>
        let selectedVehicle = null;
        const vehicles = <?php echo json_encode($vehicles); ?>;

        document.addEventListener("DOMContentLoaded", function () {
            const vehicleList = document.getElementById("vehicleList");
            const acquisitionInput = document.querySelector("input[name='acquisition']");
            const ageInput = document.querySelector("input[name='age']");
            
            if (acquisitionInput && ageInput) {
                acquisitionInput.addEventListener("change", function() {
                    const acquisitionDate = new Date(this.value);
                    const currentDate = new Date();
                    const age = currentDate.getFullYear() - acquisitionDate.getFullYear();
                    
                    // Adjust if birthday hasn't occurred yet this year
                    if (currentDate.getMonth() < acquisitionDate.getMonth() || 
                        (currentDate.getMonth() === acquisitionDate.getMonth() && 
                        currentDate.getDate() < acquisitionDate.getDate())) {
                        age--;
                    }
                    
                    ageInput.value = age;
                });
            }

            vehicles.forEach(vehicle => {
                const imagePath = `uploads/${vehicle.image_url && vehicle.image_url.trim() !== '' ? vehicle.image_url : '404.png'}`;
                const vehicleDiv = document.createElement("div");
                vehicleDiv.className = "vehicle";
                vehicleDiv.innerHTML = `
                    <img src="${imagePath}" onerror="this.src='uploads/404.png'" alt="Vehicle Image">
                    <div class="vehicle-info">${(vehicle.brand + ' ' + vehicle.model).toUpperCase()}</div>
                `;

                vehicleDiv.addEventListener("click", function () {
                    selectedVehicle = vehicle;
                    showVehicleDetails(vehicle);
                });

                vehicleList.appendChild(vehicleDiv);
            });

            // Modal logic
            document.getElementById("showModalBtn").addEventListener("click", function () {
                const form = document.getElementById("vehicleForm");
                form.reset();
                form.action = "add_vehicle.php";

                // Remove vehicle_id if it exists (so it doesn't attempt update)
                const hiddenId = form.querySelector("input[name='vehicle_id']");
                if (hiddenId) {
                    hiddenId.remove();
                }

                document.getElementById("modal").style.display = "flex";
            });

            document.getElementById("closeModalBtn").addEventListener("click", function () {
                document.getElementById("modal").style.display = "none";
            });
        });

        function showVehicleDetails(vehicle) {
            const fallbackImage = vehicle.image_url && vehicle.image_url.trim() !== "" ? vehicle.image_url : '404.png';
            const content = `
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1; max-width: 250px;">
                        <img src="uploads/${fallbackImage}" onerror="this.onerror=null; this.src='uploads/404.png';" alt="Vehicle Image" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                    </div>
                <div style="flex: 2; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; font-size: 14px;">
                    <div><strong>Vehicle Type:</strong> ${vehicle.type}</div>
                    <div><strong>Brand / Make:</strong> ${vehicle.brand}</div>
                    <div><strong>Model:</strong> ${vehicle.model}</div>
                    <div><strong>Year:</strong> ${vehicle.year}</div>
                    <div><strong>Age:</strong> ${vehicle.calculated_age}</div>
                    <div><strong>Plate No.:</strong> ${vehicle.plate}</div>
                    <div><strong>LTO M.V. No.:</strong> ${vehicle.lto_mv}</div>
                    <div><strong>NPC M.V. No.:</strong> ${vehicle.npc_mv}</div>
                    <div><strong>Reg No.:</strong> ${vehicle.reg_no}</div>
                    <div><strong>Reg Expiration:</strong> ${vehicle.reg_exp}</div>
                    <div><strong>Chassis No.:</strong> ${vehicle.chassis}</div>
                    <div><strong>Engine No.:</strong> ${vehicle.engine}</div>
                    <div><strong>Fuel:</strong> ${vehicle.fuel}</div>
                    <div><strong>Assignment:</strong> ${vehicle.assignment}</div>
                    <div><strong>Cost:</strong> ₱${Number(vehicle.cost).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    <div><strong>Acquisition Date:</strong> ${vehicle.acquisition}</div>
                    <div><strong>PAR To:</strong> ${vehicle.par_to}</div>
                    <div><strong>Position:</strong> ${vehicle.position}</div>
                    <div><strong>Salary Grade:</strong> ${vehicle.salary_grade}</div>
                </div>
            </div>
            `;
            document.getElementById("vehicleDetailsContent").innerHTML = content;
            document.getElementById("detailsModal").style.display = "flex";
        }

        function openEditModal() {
            if (!selectedVehicle) return;

            const form = document.getElementById("vehicleForm");
            const fields = ['type', 'brand', 'model', 'year', 'age', 'plate', 'lto_mv', 'npc_mv', 'reg_no', 'reg_exp', 'chassis', 'engine', 'fuel', 'assignment', 'cost', 'acquisition', 'par_to', 'position', 'salary_grade'];

            fields.forEach(field => {
                form.elements[field].value = selectedVehicle[field];
            });

            // Add or update vehicle_id hidden input
            let hidden = form.querySelector("input[name='vehicle_id']");
            if (!hidden) {
                hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = "vehicle_id";
                form.appendChild(hidden);
            }
            hidden.value = selectedVehicle.vehicle_id;

            // Change form action to update
            form.action = "update_vehicle.php";

            // Show modal
            document.getElementById("detailsModal").style.display = "none";
            document.getElementById("modal").style.display = "flex";
        }

        function deleteVehicle() {
            if (!selectedVehicle) return;
            if (confirm(`Are you sure you want to delete [${selectedVehicle.brand} ${selectedVehicle.model}]?`)) {
                if (confirm(`Deleting [${selectedVehicle.brand} ${selectedVehicle.model}] will permanently delete all its data. Proceed?`)){
                    window.location.href = `delete_vehicle.php?id=${selectedVehicle.vehicle_id}`;
                }
            }
        }

        function printVehicle(vehicle = null) {
            let html = `
                <html>
                <head>
                    <title>Vehicle Details</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h2 { text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
                        th { background-color: #f0f0f0; }
                        img { max-width: 200px; height: auto; }
                    </style>
                </head>
                <body>
                    <h2>${vehicle ? 'Vehicle Details' : 'All Vehicles'}</h2>
            `;

            const renderVehicle = (v) => `
                <table>
                    <tr><th>Image</th><td><img src="uploads/${v.image_url || '404.png'}" onerror="this.src='uploads/404.png'" /></td></tr>
                    <tr><th>Vehicle Type</th><td>${v.type}</td></tr>
                    <tr><th>Brand / Make</th><td>${v.brand}</td></tr>
                    <tr><th>Model</th><td>${v.model}</td></tr>
                    <tr><th>Year</th><td>${v.year}</td></tr>
                    <tr><th>Age</th><td>${v.calculated_age || v.age}</td></tr>
                    <tr><th>Plate No.</th><td>${v.plate}</td></tr>
                    <tr><th>LTO M.V. No.</th><td>${v.lto_mv}</td></tr>
                    <tr><th>NPC M.V. No.</th><td>${v.npc_mv}</td></tr>
                    <tr><th>Registration No.</th><td>${v.reg_no}</td></tr>
                    <tr><th>Registration Expiration</th><td>${v.reg_exp}</td></tr>
                    <tr><th>Chassis No.</th><td>${v.chassis}</td></tr>
                    <tr><th>Engine No.</th><td>${v.engine}</td></tr>
                    <tr><th>Fuel</th><td>${v.fuel}</td></tr>
                    <tr><th>Assignment</th><td>${v.assignment}</td></tr>
                    <tr><th>Cost</th><td>₱${Number(v.cost).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td></tr>
                    <tr><th>Acquisition Date</th><td>${v.acquisition}</td></tr>
                    <tr><th>PAR To</th><td>${v.par_to}</td></tr>
                    <tr><th>Position</th><td>${v.position}</td></tr>
                    <tr><th>Salary Grade</th><td>${v.salary_grade}</td></tr>
                </table>
                <br><br>
            `;

            if (vehicle) {
                html += renderVehicle(vehicle);
            } else {
                vehicles.forEach(v => {
                    html += renderVehicle(v);
                });
            }

            html += `</body></html>`;

            const printWindow = window.open('', '', 'width=1000,height=800');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }
    </script>
</body>
</html>