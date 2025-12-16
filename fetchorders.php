<?php
$serverName = "LAPTOP-T6HO0TLL\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database"=>"DLSU"]);

$sql = "SELECT 
          o.PARENT_ORDER_ID,
          CONVERT(date, o.DATE_ORDERED) AS ORDER_DATE,
          p.PRODUCT_NAME,
          o.QUANTITY,
          o.ORDERED_BY,
          o.PLACED_BY,
          (o.QUANTITY * p.PRICE) AS LINE_TOTAL
        FROM CAFEORDERS o
        JOIN CAFEPRODUCTS p ON o.PRODUCT_ID = p.PRODUCT_ID
        ORDER BY o.PARENT_ORDER_ID DESC";

$q = sqlsrv_query($conn, $sql);
$data = [];

while ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
  $r['ORDER_DATE'] = $r['ORDER_DATE']->format('Y-m-d');
  $data[] = $r;
}

echo json_encode($data);
?>