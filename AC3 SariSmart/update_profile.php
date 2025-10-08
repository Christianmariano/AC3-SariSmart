<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$currentUsername = $_SESSION["username"];
$newUsername = $_POST['username'];

// Step 1: Get current profile image from DB
$sql = "SELECT profile_image FROM login WHERE username = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $currentUsername);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $currentImage);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Step 2: Handle new image upload (optional)
$newImageName = $currentImage;

if (isset($_FILES['new_profile_image']) && $_FILES['new_profile_image']['error'] === 0) {
    $imageTmp = $_FILES['new_profile_image']['tmp_name'];
    $imageName = basename($_FILES['new_profile_image']['name']);
    $targetDir = "images/profile/";
    $targetFile = $targetDir . $imageName;

    if (move_uploaded_file($imageTmp, $targetFile)) {
        $newImageName = $imageName;
    }
}

// Step 3: Update query (without password)
$sql = "UPDATE login SET username = ?, profile_image = ? WHERE username = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "sss", $newUsername, $newImageName, $currentUsername);

// Execute update
if (mysqli_stmt_execute($stmt)) {
    $_SESSION["username"] = $newUsername;
    header("Location: profile.php?success=1");
    exit;
} else {
    echo "Something went wrong. Please try again later.";
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>
