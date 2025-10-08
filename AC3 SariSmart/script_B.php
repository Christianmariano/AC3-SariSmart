<?php  
// Enable error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to MySQL
$mysqli = new mysqli("localhost", "root", "", "ac_sarismart");

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to connect to MySQL: " . $mysqli->connect_error]);
    exit();
}

// Query the database
$sql = "SELECT * FROM income_summary";
$res = $mysqli->query($sql);

// Check for query error
if (!$res) {
    http_response_code(500);
    echo json_encode(["error" => "Query failed: " . $mysqli->error]);
    exit();
}

// Fetch data
$datab = [];
while ($row = $res->fetch_assoc()) {
    $datab[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($datab);
?>
