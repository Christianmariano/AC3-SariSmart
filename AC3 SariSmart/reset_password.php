<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if ($new_password !== $confirm_password) {
        header("Location: index.php?reset=nomatch");
        exit;
    }

    // Hash both passwords (better for security)
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
    $hashed_confirm_password = password_hash($confirm_password, PASSWORD_DEFAULT);

    $conn = new mysqli("localhost", "root", "", "ac_sarismart");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update password, new_password, and confirm_password columns
    $stmt = $conn->prepare("UPDATE login 
                            SET password = ?, new_password = ?, confirm_password = ? 
                            WHERE username = ?");
    $stmt->bind_param("ssss", $hashed_new_password, $hashed_new_password, $hashed_confirm_password, $username);

    if ($stmt->execute()) {
        header("Location: index.php?reset=success");
    } else {
        header("Location: index.php?reset=error");
    }

    $stmt->close();
    $conn->close();
}
?> 