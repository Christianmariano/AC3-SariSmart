<?php
// Start session
session_start();

// Check if the user is logged in; if not, redirect to login page
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Get username from session
$username = $_SESSION["username"];

// Fetch profile image from database
$sql = "SELECT profile_image FROM login WHERE username = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $profile_image);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// Fallback image if user has no profile image
if (empty($profile_image)) {
    $profile_image = "default_profile.png"; // Make sure this exists in ./images/
}
// Full path for header and form images
$profile_image_path = "./images/profile/" . htmlspecialchars($profile_image);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="asset/css/contactinfo.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="website icon" type="png/jpg" href="images/logo.png">

    <title>AC³SariSmart - Contac</title>

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

         /* Default header style */
      .header {
          width: 100%;
          transition: all 0.3s ease;
      }

      /* Sticky header */
      .header.sticky {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          z-index: 999;
          background-color: #fff; /* or semi-transparent */
          box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      }
      .nav {
              display: flex;
              align-items: center;
              justify-content: space-between; /* space between search and user */
              flex-wrap: wrap; /* allow wrapping on very small screens */
              gap: 10px; /* optional spacing */
          }

    /* Search bar responsive */
    .search-bar {
        flex: 1; /* take available space */
        min-width: 200px; /* don't shrink too much */
        max-width: 400px;
    }

    /* User section responsive */
    .user {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Ensure username text and profile image stay inline */
    .user span p {
        margin: 0;
        white-space: nowrap;
    }

    /* On small screens, keep search and user inline but allow scaling */
    @media (max-width: 600px) {
        .nav {
            flex-direction: row; /* keep row */
            justify-content: space-between;
        }
        .search-bar {
            flex: 1;
            margin-right: 10px;
        }
    }
    </style>
</head>
<body>
    <div class="burger" id="burger">
        &#9776; <!-- This is the hamburger icon ☰ -->
    </div>

    <div class="side-menu" id="side-menu">
    <div class="brand-name" style="background-color: aqua; color: black">
        <img src="images/logo.png" alt="" style="width: 50px; height: 100px;">
        <br>
        <h1>AC<sup>3</sup>SariSmart</h1>
    </div>
    <ul>
        <li>
            <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/dashboard.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Dashboard
            </a>
        </li>
        <li>
            <a href="daily_income.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/dailyincome.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Daily Income
            </a>
        </li>
        <hr>
        <li>
            <a href="barchart_daily_income.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/bar_chart_logo.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Bar Chart
            </a>
        </li>
        <li>
            <a href="piechart.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/pie_chart_logo.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Pie Chart
            </a>
        </li>
        <hr>
        <hr>
        <li>
            <a href="productforsales.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/item.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Product For Sales
            </a>
        </li>
        <li>
            <a href="productdolsout.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/soldout.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Product Sold Out
            </a>
        </li>
        <li>
            <a href="productlist.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/productlist.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Product List
            </a>
        </li>
        <hr>
        <li>
            <a href="contactinfo.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="images/contact.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
                Contact
            </a>
        </li>

    </ul>
    </div>
    <div class="container">
        <div class="header">
            <div class="nav">
                <!-- ✅ Search Bar with Mic (Left of user profile) -->
                <div class="search-bar" style="position: relative; width: 300px; margin-right: 20px;">
                    <input type="text" id="searchInput" placeholder="Search..." 
                          style="width: 100%; padding: 10px 40px 10px 40px; 
                                border: 1px solid #ddd; border-radius: 50px; 
                                font-size: 14px; outline: none; 
                                box-shadow: 0 1px 4px rgba(0,0,0,0.1);"/>
                    <i class="fas fa-search" 
                      style="position: absolute; left: 15px; top: 50%; 
                              transform: translateY(-50%); color: #888;"></i>
                    <i class="fas fa-microphone" onclick="startDictation()" 
                      style="position: absolute; right: 15px; top: 50%; 
                              transform: translateY(-50%); color: #4285F4; cursor: pointer;"></i>
                </div>
                <!-- ✅ User Section -->
                <div class="user">
                    <span><p><?php echo htmlspecialchars($_SESSION["username"]); ?></p></span>
                    <div class="img-case" id="profile-img">
                        <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="User Image">
                    </div>
                    <div class="dropdown-menu" id="dropdown-menu">
                        <div class="arrow" id="dropdown-arrow">&#x25BC;</div> <!-- down arrow initially -->
                        <br>
                        <ul>
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="forgetpassword.php">Forget Password</a></li>
                            <li><a href="logout.php">Log Out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
          <!-- ✅ RESET PASSWORD SUCCESS POPUP
          <div id="reset-popup" class="popup" style="display: none; background-color: #007BFF;">
              <div class="popup-content">
                  <p>🔐 Password reset successful! You can now log in.</p>
                  <button onclick="closeResetPopup()">Close</button>
              </div>
          </div>
          -->

        <div class="profile-form-container">
            <div class="aboutus-center">
                <img src="./images/logo.png" alt="Error" style="width:150px; margin-bottom:10px;">
                <h1>AC<sup>3</sup>SariSmart</h1>
                <h2>Contact Us</h2>
                <br>
                <p>
                  For any inquiries or further information, you may contact AC<sup>3</sup>SariSmart through email at AC<sup>3</sup>SariSmart@gmail.com or via mobile at 09629310707. 
                  He is currently based in 53 Quezon Street Corner Perla A Metro Manila, Tondo Manila Philippines.
                  You can also connect with him on Facebook at facebook.com/AC<sup>3</sup>SariSmart.
                </p>
            <br><br>
            </div>
        </div> 
    </div>
</body>

<script>
  // Get elements
  const openModalBtn = document.getElementById("openModalBtn");
  const modal = document.getElementById("imageModal");
  const modalImg = document.getElementById("modalImage");
  const closeBtn = document.querySelector(".close");

  // Open modal on image click
  openModalBtn.addEventListener("click", () => {
    const profileImg = openModalBtn.querySelector("img");
    modalImg.src = profileImg.src;
    modal.style.display = "block";
  });

  // Close modal when X is clicked
  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // Close modal if user clicks outside the image
  window.addEventListener("click", (e) => {
    if (e.target == modal) {
      modal.style.display = "none";
    }
  });
</script>

<!-- Responsive Nav Bar-->
<script>
function updateStickyHeader() {
    const header = document.querySelector('.header');
    const container = document.querySelector('.container');

    // Only apply sticky for small screens (e.g., width < 1024px)
    if (window.innerWidth < 1024) {
        const headerHeight = header.offsetHeight;

        if (window.scrollY > 50) { // scroll threshold
            if (!header.classList.contains('sticky')) {
                header.classList.add('sticky');
            }
            container.style.paddingTop = headerHeight + 'px'; // push content down
        } else {
            if (header.classList.contains('sticky')) {
                header.classList.remove('sticky');
            }
            container.style.paddingTop = '0';
        }
    } else {
        // Remove sticky for large screens
        if (header.classList.contains('sticky')) {
            header.classList.remove('sticky');
        }
        container.style.paddingTop = '0';
    }
}

// Run on scroll
window.addEventListener('scroll', updateStickyHeader);

// Run on window resize
window.addEventListener('resize', updateStickyHeader);

// Run once on page load
document.addEventListener('DOMContentLoaded', updateStickyHeader);
</script>

<!--END Responsive Nav Bar-->
<script src="asset/js/burger_menu.js"></script>
<script src="asset/js/search_bar_microphone_contactinfo.js"></script>
</body>
</html>