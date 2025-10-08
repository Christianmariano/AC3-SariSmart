<?php
session_start();
date_default_timezone_set('Asia/Manila');

// DB connection
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($link === false) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

if (isset($_POST['barcode'])) {
    $barcode = mysqli_real_escape_string($link, $_POST['barcode']);

    // ✅ Insert new product with only barcode, others blank/default
    $sql = "INSERT INTO products (product_name, quantity, price, barcode, status) 
            VALUES ('New Product', 0, 0.00, '$barcode', 'active')";

    if (mysqli_query($link, $sql)) {
        echo json_encode([
            "success" => true,
            "id" => mysqli_insert_id($link),
            "barcode" => $barcode
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => mysqli_error($link)
        ]);
    }
}
?>
