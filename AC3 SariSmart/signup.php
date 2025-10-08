<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        header("Location: index.php?signup=error_empty");
        exit;
    }

    // Handle image upload
    $profile_image = null; // default null if no image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imgTmpPath = $_FILES['profile_image']['tmp_name'];
        $imgName = $_FILES['profile_image']['name'];
        $imgSize = $_FILES['profile_image']['size'];
        $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imgExt, $allowed)) {
            $newImgName = uniqid("IMG_", true) . "." . $imgExt;
            $uploadDir = "images/profile/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // create directory if not exists
            }
            $destination = $uploadDir . $newImgName;

            if (move_uploaded_file($imgTmpPath, $destination)) {
                $profile_image = $newImgName;
            } else {
                header("Location: index.php?signup=upload_error");
                exit;
            }
        } else {
            header("Location: index.php?signup=invalid_image");
            exit;
        }
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // DB Connection
    $conn = new mysqli("localhost", "root", "", "ac_sarismart");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if username exists
    $check = $conn->prepare("SELECT id FROM login WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        header("Location: index.php?signup=exists");
        exit;
    }
    $check->close();

    // Insert new user with profile image
    $stmt = $conn->prepare("INSERT INTO login (username, password, profile_image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $profile_image);

    if ($stmt->execute()) {
        header("Location: index.php?signup=success");
    } else {
        header("Location: index.php?signup=error");
    }

    $stmt->close();
    $conn->close();
}
?>
