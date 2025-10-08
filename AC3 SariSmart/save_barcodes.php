<?php
// DB connection
require "db_connect.php"; // your mysqli_connect code

$data = json_decode(file_get_contents("php://input"), true);

if ($data && is_array($data)) {
    foreach ($data as $item) {
        $id = (int)$item['id'];
        $barcode = mysqli_real_escape_string($link, $item['barcode']);
        $sql = "UPDATE products SET barcode='$barcode' WHERE id=$id";
        mysqli_query($link, $sql);
    }
    echo "OK";
} else {
    echo "No data";
}
?>
