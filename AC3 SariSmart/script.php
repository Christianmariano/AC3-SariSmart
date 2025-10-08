<?php  
    $mysqli = new mysqli("localhost", "root", "", "ac_sarismart");
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: " . $mysqli->connect_error;
        exit();
    }
    $sql = "SELECT * FROM daily_records";
    $res = $mysqli->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        array_push($data, $row);
    }
    echo json_encode($data);
?>