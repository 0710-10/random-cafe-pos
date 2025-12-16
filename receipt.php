<?php
include 'global.php';
session_start();

if (!isset($_GET['order_id'])) {
    die("No order ID provided.");
}

$orderID = $_GET['order_id'];

$serverName = "LAPTOP-T6HO0TLL\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$sql = "SELECT o.PARENT_ORDER_ID, o.DATE_ORDERED, o.ORDERED_BY, o.QUANTITY, p.PRICE, p.PRODUCT_NAME, o.TOTAL_AMOUNT
        FROM CAFEORDERS o
        JOIN CAFEPRODUCTS p ON o.PRODUCT_ID = p.PRODUCT_ID
        WHERE o.PARENT_ORDER_ID = ?";
$query = sqlsrv_query($conn, $sql, [$orderID]);

$items = [];
$subtotal = 0;
$total = 0;
$orderInfo = null;

while ($row = sqlsrv_fetch_array($query)) {
    if (!$orderInfo) {
        $orderInfo = $row;
    }
    $rowTotal = $row['PRICE'] * $row['QUANTITY'];
    $subtotal += $rowTotal;
    $total = $row['TOTAL_AMOUNT'];
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Receipt #<?= $orderID ?></title>
    <link rel="icon" type="image/png" href="assets/logonotext.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 380px;
            margin: auto;
            padding: 20px;
            border-style: dotted;
        }

        @font-face {
            font-family: "Consolas";
            src: url('fonts/Consolas.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        h2,
        h4 {
            text-align: center;
            margin: 4px 0;
        }

        hr {
            border: 1px dashed #000;
            margin: 10px 0;
        }

        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .totals {
            display: flex;
            font-weight: bold;
            justify-content: space-between;
            margin-top: 10px;
        }

        .center {
            text-align: center;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body style="font-family: Consolas, sans-serif;">
    <img src="assets/logo.png" style="width: 80px; height: auto; margin: 5px auto 0; display:block;">
    <h2>Manhattan Café</h2>
    <h4>Official Receipt</h4>

    <hr>

    <div>Date: <?= $orderInfo['DATE_ORDERED']->format('m-d-Y') ?></div>
    <div>Customer: <?= htmlspecialchars($orderInfo['ORDERED_BY']) ?></div>
    <div>Order ID #: <?= $orderID ?></div>

    <hr>

    <?php foreach ($items as $item): ?>
        <div class="item">
            <span><?= htmlspecialchars($item['PRODUCT_NAME']) ?> × <?= $item['QUANTITY'] ?></span>
            <span>₱<?= number_format($item['PRICE'] * $item['QUANTITY'], 2) ?></span>
        </div>
    <?php endforeach; ?>

    <hr>

    <div class="totals">
        <span>Order Subtotal: </span>
        <span>₱<?= number_format($subtotal, 2) ?></span>
    </div>
    <div class="totals">
        <span>Order Total (discounts applied): </span>
        <span>₱<?= number_format($total, 2) ?></span>
    </div>
    <hr>

    <div class="center">Thank you for your purchase!</div><br>

    <div class="center">
        <button onclick="window.print()">Print Receipt</button>
    </div>

</body>

</html>