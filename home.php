<?php
include 'global.php';
session_start();
$serverName="LAPTOP-T6HO0TLL\SQLEXPRESS";
$connectionOptions=[
"Database"=>"DLSU",
"Uid"=>"",
"PWD"=>""
];
$conn=sqlsrv_connect($serverName, $connectionOptions);
if($conn==false)
die(print_r(sqlsrv_errors(),true));

$username=$_POST['uname'] ?? '';
$password=$_POST['password'] ?? '';
$logintype=$_POST['logintype'] ?? '';

// if no data redirect to login
if(empty($username) || empty($password) || empty($logintype)) {
    header("Location: login.html");
    exit();
}

// query to check if username exists
$checkuname="SELECT USERNAME, PASSWORD, USERTYPE FROM CAFEUSERS WHERE USERNAME = ?";
$params = array($username);
$resultcheck=sqlsrv_query($conn, $checkuname, $params);

if($resultcheck === false) {
    header("Location: login.html?error=database");
    exit();
}

$user = sqlsrv_fetch_array($resultcheck, SQLSRV_FETCH_ASSOC);

// check if user exists
if($user === null) {
    // user doesnt exist
    header("Location: login.html?error=invalid");
    exit();
}

$passwordDB = $user['PASSWORD'];
$usertype = $user['USERTYPE'];

// check password
if ($password != $passwordDB) {
    header("Location: login.html?error=invalid");
    exit();
}

// check user type
if ($logintype != $usertype) {
    header("Location: login.html?error=role");
    exit();
}

// set session variables
$_SESSION['sessionUser'] = $username;
$_SESSION['userType'] = $usertype;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manhattan Café</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="homestyle.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logonotext.png">
</head>

<body style="font-family: KeiJi, sans-serif; font-weight: 600;">
    <main>
    <div class="container d-flex flex-column justify-content-center align-items-center" style="height: 100vh;">

    <div class="menu-wrapper">

        <h2 id="welcome-text" class="mb-4 text-center" style="font-family: KeiJi, sans-serif; font-weight: 1000;">Welcome, <?php echo htmlspecialchars($username) ?></h2>

        <div class="card-area">

            <div id="card-take-orders" class="menu-card">
                <img src="assets/takeorderslogo.png" class="card-img">
                <div class="card-label">Take Orders</div>
            </div>

            <div id="card-sales" class="menu-card">
                <img src="assets/salesdashboardlogo.png" class="card-img">
                <div class="card-label">Sales Dashboard</div>
            </div>

            <div id="card-products" class="menu-card">
                <img src="assets/settingslogo.png" class="card-img">
                <div class="card-label">Modify Products</div>
            </div>

        </div>

        <button id="logout-btn" class="logout-btn">Log Out</button>

    </div>
</div>
</main>
    <script>
        const role = <?= json_encode($usertype, JSON_HEX_TAG); ?>;

        const cardSales = document.getElementById("card-sales");
        const cardProducts = document.getElementById("card-products");

        if (role === "cashier") {
            cardSales.style.display = "none";
            cardProducts.style.display = "none";
        }

        document.getElementById("card-take-orders").onclick = () => window.location.href = "takeorders.php";
        document.getElementById("card-sales").onclick = () => window.location.href = "dashboard.php";
        document.getElementById("card-products").onclick = () => window.location.href = "modifyproducts.php";
        document.getElementById("logout-btn").onclick = () => window.location.href = "login.html";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>