<?php
session_start();
date_default_timezone_set('Asia/Manila');

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$link) {
    die(json_encode(["status"=>"error","message"=>mysqli_connect_error()]));
}

$barcode = $_POST['barcode'] ?? '';
$product_name = $_POST['product_name'] ?? 'Scanned Product';

if ($barcode) {
    $sql = "INSERT INTO products (product_name, barcode, quantity, price) VALUES (?, ?, 1, 0)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $product_name, $barcode);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","message"=>"DB insert failed"]);
    }
} else {
    echo json_encode(["status"=>"error","message"=>"No barcode received"]);
}
?>
