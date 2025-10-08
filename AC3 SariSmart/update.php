<?php
session_start();
if (!isset($_SESSION["username"])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

$date = $data['date'] ?? null;
$income_store = floatval($data['income_store'] ?? 0);
$income_school_service = floatval($data['income_school_service'] ?? 0);
$expense_store = floatval($data['expense_store'] ?? 0);
$expense_school_service = floatval($data['expense_school_service'] ?? 0);

if (!$date) {
    http_response_code(400);
    echo json_encode(["error" => "Date is required"]);
    exit;
}

// Calculate totals
$total_income_school = $income_school_service;
$total_income_store = $income_store;
$total_expense_store = $expense_store;
$total_expense_school = $expense_school_service;
$total_income = $income_store + $income_school_service;
$total_expenses = $expense_store + $expense_school_service;

$sql = "UPDATE daily_records SET
    income_store = ?, income_school_service = ?, expense_store = ?, expense_school_service = ?,
    total_income_school = ?, total_income_store = ?, total_expense_store = ?, total_expense_school = ?,
    total_income = ?, total_expenses = ?
    WHERE date = ?";

if ($stmt = $link->prepare($sql)) {
    $stmt->bind_param(
        "dddddddddds",
        $income_store, $income_school_service, $expense_store, $expense_school_service,
        $total_income_school, $total_income_store, $total_expense_store, $total_expense_school,
        $total_income, $total_expenses, $date
    );
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => "Record updated"]);
        } else {
            echo json_encode(["error" => "No record found for that date"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update record: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed: " . $link->error]);
}
$link->close();
