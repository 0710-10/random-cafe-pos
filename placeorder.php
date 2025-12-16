<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
session_start();

include 'global.php';

$serverName = "LAPTOP-T6HO0TLL\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$cart = $data['cart'];
$orderedBy = $data['orderedBy'];
$paidAmount = $data['paid'];
$totalAmount = $data['totalAmount'];
$placedBy = $_SESSION['sessionUser'];

if (empty($cart)) {
    echo json_encode(['status'=>'error','message'=>'Cart is empty']);
    exit;
}

try {

    $sqlGetLastID = "SELECT TOP 1 PARENT_ORDER_ID FROM CAFEORDERS ORDER BY PARENT_ORDER_ID DESC";
    $queryLastID = sqlsrv_query($conn, $sqlGetLastID);
    $lastID = sqlsrv_fetch_array($queryLastID);

    $sqlGetUserID = "SELECT USERID FROM CAFEUSERS WHERE USERNAME = '$placedBy'";
    $queryUserID = sqlsrv_query($conn, $sqlGetUserID);
    $userID = sqlsrv_fetch_array($queryUserID);

    $parentOrderID = $lastID[0] + 1;

    //random date for placeholder
    //DATEADD(DAY, ABS(CHECKSUM(NEWID()) % 31 ), '2025-11-01')
    //GETDATE() //get date of current day


    foreach ($cart as $index => $item) {
        $productId = $item['id'];
        $productName = $item['name'];
        $qty = intval($item['qty']);
        $totalAmountFloat = floatval($totalAmount);

        if ($index === 0) {
            $sql = "INSERT INTO CAFEORDERS (PARENT_ORDER_ID, PRODUCT_NAME, PRODUCT_ID, QUANTITY, DATE_ORDERED, ORDERED_BY, PLACED_BY , USERID, PAID_AMOUNT, TOTAL_AMOUNT) 
                    VALUES ('$parentOrderID', '$productName', '$productId', '$qty', GETDATE(), '$orderedBy', '$placedBy', '$userID[0]', '$paidAmount', '$totalAmountFloat')";
            $pushOrder = sqlsrv_query($conn, $sql);
            if ($pushOrder === false) {
                throw new Exception('SQL Error: ' . print_r(sqlsrv_errors(), true));
            }
        } else {
            $sql = "INSERT INTO CAFEORDERS (PARENT_ORDER_ID, PRODUCT_NAME, PRODUCT_ID, QUANTITY, DATE_ORDERED, ORDERED_BY, PLACED_BY , USERID, PAID_AMOUNT, TOTAL_AMOUNT) 
                    VALUES ('$parentOrderID', '$productName', '$productId', '$qty', GETDATE(), '$orderedBy', '$placedBy', '$userID[0]', '$paidAmount', '$totalAmountFloat')";
            $pushOrder = sqlsrv_query($conn, $sql);
            if ($pushOrder === false) {
                throw new Exception('SQL Error: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }

    sqlsrv_commit($conn);

    echo json_encode(['status'=>'success', 'message'=>'Order placed successfully', 'order_id'=>$parentOrderID]);
    exit;

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
?>