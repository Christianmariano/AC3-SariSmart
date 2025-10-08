<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');
 
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts Link For Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,0,0">
    <link rel="stylesheet" href="style.css">
    <link rel="website icon" type="png/jpg" href="images/logo.png">
    <script src="asset/js/showpassword.js" defer></script>
    <script src="asset/js/script.js" defer></script>

    <title>AC³SariSmart - Developer</title>

    <style>
        /* Popup Styles */
        .popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4BB543;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        }

        .popup-content button {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: white;
            color: #4BB543;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }


        /*show password*/
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }

        .toggle-password img {
            width: 28px;   /* Change this value as needed */
            height: 28px;  /* Change this value as needed */
            opacity: 0.6;
            transition: opacity 0.2s ease;
        }

        .toggle-password:hover img {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="images">
        <img src="images/income.jpg"  alt="Error">
    </div>
    <header>
        <nav class="navbar">
            <span class="hamburger-btn material-symbols-rounded">menu</span>
             <a href="index.php" class="logo">
                <img src="images/logo.png" alt="logo">
                <h2 style="color: orange;">AC<sup>3</sup>SariSmart</h2>
            </a>
            <ul class="links">
                <span class="close-btn material-symbols-rounded">close</span>
                <li><a href="index.php">Home</a></li>
                <li><a href="./AboutUs.php">About us</a></li>
                <li><a href="contact.php">Contact us</a></li>
                <li><a href="./developer.php">Developer</a></li>
            </ul>
            <button class="login-btn">LOG IN</button>
        </nav>
        <div class="aboutus-center">
        <br>
            <img src="./images/developer image.JPG" alt="Error" style="width:350px; height: 250px; margin-bottom:20px;">
            <h1>Developer Information</h1>
            <br>
            <p>
                Christian P. Mariano is a 23-year-old fresh graduate with a Bachelor of Science in Information Technology. 
                As part of his desire to apply his skills to real-life situations, he developed a web-based application called AC SariSmart for their family’s small sari-sari store. 
                The main purpose of this application is to help monitor and manage the store’s daily income and sales in a more organized and efficient way. 
                AC SariSmart features a simple interface where users can record daily transactions, track earnings, and generate basic income reports. 
                This project not only served as a valuable learning experience but also aimed to support the family business by introducing a digital solution to improve its daily operations.
            </p>
        <br><br>
        </div>
    </header>


    <div class="blur-bg-overlay"></div>
    <div class="form-popup">
        <span class="close-btn material-symbols-rounded">close</span>
        <!-- LOGIN FORM -->
        <div class="form-box login">
            <div class="form-details">
                <img src="images/logo.png" style="width: 350px; height: 350px; margin-left: 20px;" alt="Error">
            </div>
            <div class="form-content">
                <h2>LOGIN</h2>
                <form action="login.php" method="POST">
                    <div class="input-field">
                        <input type="text" name="username" required>
                        <label>Username</label>
                    </div>
                    <div class="input-field">
                        <input type="password" name="password" required id="loginpassword">
                        <label>Password</label>
                    </div>
                    <a href="#" class="forgot-pass-link">Forgot password?</a>
                    <button type="submit">Log In</button>
                </form>
            </div>
        </div>

        <!-- FORGOT PASSWORD FORM -->
        <div class="form-box forgot-password">
            <div class="form-details">
                <img src="images/logo.png" style="width: 350px; height: 350px; margin-left: 20px;" alt="Error">
            </div>
            <div class="form-content">
                <h2>RESET PASSWORD</h2>
                <form action="reset_password.php" method="POST">
                    <div class="input-field">
                        <input type="text" name="username" required>
                        <label>Username</label>
                    </div>
                    <div class="input-field">
                        <input type="password" name="new_password" required id="newpassword">
                        <label>New Password</label>
                    </div>

                    <div class="input-field">
                        <input type="password" name="confirm_password" required id="confirmpassword">
                        <label>Confirm Password</label>
                    </div>
                    <button type="submit">Reset Password</button>
                </form>
                <div class="bottom-link">
                    Remembered your password?
                    <a href="#" id="back-to-login-link">Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ SIGNUP SUCCESS POPUP -->
    <div id="signup-popup" class="popup" style="display: none;">
        <div class="popup-content">
            <p>✅ Signup successful! You can now log in.</p>
            <button onclick="closePopup()">Close</button>
        </div>
    </div>
    <!-- ✅ RESET PASSWORD SUCCESS POPUP -->
    <div id="reset-popup" class="popup" style="display: none; background-color: #007BFF;">
        <div class="popup-content">
            <p>🔐 Password reset successful! You can now log in.</p>
            <button onclick="closeResetPopup()">Close</button>
        </div>
    </div>

    <!-- ✅ JavaScript to Show Popup -->
    <script>
        function closePopup() {
            document.getElementById("signup-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        function closeResetPopup() {
            document.getElementById("reset-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        function closeLoginPopup() {
            document.getElementById("login-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        window.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get("signup") === "success") {
                document.getElementById("signup-popup").style.display = "block";
            }

            if (urlParams.get("reset") === "success") {
                document.getElementById("reset-popup").style.display = "block";
            }

            if (urlParams.get("login") === "success") {
                document.getElementById("login-popup").style.display = "block";
            }
        });
    </script>
</body>
</html>