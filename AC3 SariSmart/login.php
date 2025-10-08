<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $conn = new mysqli("localhost", "root", "", "ac_sarismart");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, password FROM login WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;

            // ✅ Redirect to dashboard.html with login success
            header("Location: dashboard.php?login=success");
        } else {
            header("Location: dashboard.php?login=invalid");
        }
    } else {
        header("Location: dashboard.php?login=notfound");
    }

    $stmt->close();
    $conn->close();
}
?>