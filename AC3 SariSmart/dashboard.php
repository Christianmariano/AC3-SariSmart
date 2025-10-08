<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

// Connect to DB
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Fetch profile image
$profile_image = "usericon.jpg"; // default
$username = $_SESSION["username"];
$sql_profile = "SELECT profile_image FROM login WHERE username = ?";
if ($stmt = mysqli_prepare($link, $sql_profile)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_profile_image);
    mysqli_stmt_fetch($stmt);
    if (!empty($db_profile_image)) $profile_image = $db_profile_image;
    mysqli_stmt_close($stmt);
}

// Fetch total net income & savings (all users)
$net_income = 0;
$saving = 0;
$total_income = 0;
$total_expense = 0;

$sql_income_summary = "SELECT 
        SUM(total_income) AS total_income,
        SUM(total_expense) AS total_expense,
        SUM(net_income) AS net_income,
        SUM(saving) AS saving
    FROM income_summary";

$result_summary = mysqli_query($link, $sql_income_summary);
if ($result_summary && mysqli_num_rows($result_summary) > 0) {
    $row = mysqli_fetch_assoc($result_summary);
    $total_income = (float)$row['total_income'];
    $total_expense = (float)$row['total_expense'];
    $net_income = (float)$row['net_income'];
    $saving = (float)$row['saving'];
}

// Fetch aggregated daily_records for all users
$income_store_total = 0;
$income_school_service_total = 0;
$expense_store_total = 0;
$expense_school_service_total = 0;

$sql_all_users = "SELECT 
        SUM(income_store) AS income_store_total,
        SUM(income_school_service) AS income_school_service_total,
        SUM(expense_store) AS expense_store_total,
        SUM(expense_school_service) AS expense_school_service_total
    FROM daily_records";

$result_all = mysqli_query($link, $sql_all_users);
if ($result_all && mysqli_num_rows($result_all) > 0) {
    $row_all = mysqli_fetch_assoc($result_all);
    $income_store_total = (float)$row_all['income_store_total'];
    $income_school_service_total = (float)$row_all['income_school_service_total'];
    $expense_store_total = (float)$row_all['expense_store_total'];
    $expense_school_service_total = (float)$row_all['expense_school_service_total'];
}

// Prepare data for Chart.js
$incomeVsExpensesData = [
    'labels' => ['Income - Store', 'Income - School Service', 'Expense - Store', 'Expense - School Service'],
    'data' => [$income_store_total, $income_school_service_total, $expense_store_total, $expense_school_service_total]
];

// ✅ Fetch last 7 days for Line Chart
$sql_trend = "SELECT date, (income_store + income_school_service) AS total_income,
                     (expense_store + expense_school_service) AS total_expense
              FROM daily_records 
              ORDER BY date DESC 
              LIMIT 7";
$result_trend = mysqli_query($link, $sql_trend);

$dates = [];
$daily_income = [];
$daily_expenses = [];

if ($result_trend && mysqli_num_rows($result_trend) > 0) {
    while ($row = mysqli_fetch_assoc($result_trend)) {
        $dates[] = $row['date'];
        $daily_income[] = (float)$row['total_income'];
        $daily_expenses[] = (float)$row['total_expense'];
    }
}
$dates = array_reverse($dates);
$daily_income = array_reverse($daily_income);
$daily_expenses = array_reverse($daily_expenses);

// ✅ Daily Reminder Popup Logic
date_default_timezone_set('Asia/Manila'); 
$today = date("Y-m-d");
$daily_message = "📌 Don't forget to record today's income and expenses!";
if (!isset($_SESSION['last_reminder_date']) || $_SESSION['last_reminder_date'] !== $today) {
    $_SESSION['show_reminder'] = true;
    $_SESSION['last_reminder_date'] = $today;
}

// Full path for header and form images
$profile_image_path = "./images/profile/" . htmlspecialchars($profile_image);
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./asset/css/barchart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="website icon" type="png/jpg" href="images/logo.png">

    <title>AC³SariSmart - Dashboard</title>

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

        /* Container for each canvas chart to control height */
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px; /* Adjust for mobile */
            margin-bottom: 30px;
        }
        .canva{
            position: relative;
            width: 100%;
            height: 400px; /* Adjust for mobile */
            margin-bottom: 30px;
        }

        @media (max-width: 600px) {
            .chart-container {
                height: 300px;
            }
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }
        /* Highlighted text style */
        .highlight {
        background-color: yellow;
        font-weight: bold;
        }
    /* Header/Nav */

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
    .nav {
            flex-direction: row; /* keep row */
            justify-content: space-between;
    }
    .search-bar {
        flex: 1;
        margin-right: 10px;
    }

    </style>
</head>
<body>
    <div class="burger" id="burger">
        &#9776; <!-- This is the hamburger icon ☰ -->
    </div>
    <!-- ✅ Daily Reminder Popup -->
    <div id="daily-reminder-popup" class="popup" style="display: none; background-color: #ff9800;">
        <div class="popup-content">
            <p>📌 <?php echo $daily_message; ?></p>
            <button onclick="closeDailyReminder()">Close</button>
        </div>
    </div>

    <div class="side-menu" id="side-menu">
    <div class="brand-name" style="background-color: aqua; color: black">
        <img src="images/logo.png" alt="Error" style="width: 50px; height: 100px;">
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
                <img src="images/bar_chart_logo.jpg" alt="" style="border-radius: 50%; width: 24px; height: 24px; margin-right: 8px;">
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
                <!--navbar
                <div class="search">
                    <input type="text" placeholder="Search...">
                    <button type="submit"><img src="images/search.png" alt=""></button>
                </div>
                -->
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
        <div class="content">
            <div class="cards">
                <div class="card">
                    <div class="box">
                         <h3>Total Net Income: ₱<?php echo number_format($net_income, 2); ?></h3>
                    </div>
                    <div class="icon-case">
                        <img src="./images/net_income.png" alt="Error" style="width: 120px; height: 120px;">
                    </div>
                </div>
                <div class="card savings-card">
                    <div class="box">
                        <h3>Savings: ₱<?php echo number_format($saving, 2); ?></h3>
                    </div>
                    <div class="icon-case">
                        <img src="./images/OIP.jpg" alt="Error" style="width: 120px; height: 120px;">
                    </div>
                </div>
                <br>
            </div>

            <div class="cards">
                <div class="card expense-card">
                    <div class="box">
                        <h3>Total Expenses: ₱<?php echo number_format($total_expense, 2); ?></h3>
                    </div>
                    <div class="icon-case">
                        <img src="./images/expense.jpg" alt="Error" style="width: 120px; height: 120px;">
                    </div>
                </div>
                <div class="card income-card">
                    <div class="box">
                        <h3>Total Income: ₱<?php echo number_format($total_income, 2); ?></h3>
                    </div>
                    <div class="icon-case">
                        <img src="./images/income.png" alt="Error" style="width: 120px; height: 120px;">
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-payment">
            <div class="title">
                <div class="LineGraph-card">
                    <h2>Daily Income & Expenses Trend</h2>
                </div>
            </div>
            <div class="canva">
                <canvas id="lineChart" width="600" height="400"></canvas>
            </div>
        </div>
        
            <br><br>
            <!-- PIE & BAR chart -->
            <div class="content-2">
                <!-- ✅ Daily Income Chart -->
                <div class="recent-payment">
                    <div class="title">
                        <div class="Daily_Income-card">
                            <h2 >Daily Income</h2>
                        </div>
                    </div>
                    <div class="canva">
                        <canvas id="myChart" width="600" height="400"></canvas>
                    </div>
                </div>
                <!-- ✅ 2nd Income vs Expenses Chart -->
                <div class="recent-payment">
                    <div class="title">
                        <div class="Income_vs_Expenses-card">
                            <h2>Income vs Expenses</h2>
                        </div>
                    </div>
                    <div>
                        <canvas id="incomePieChart"></canvas>
                    </div>
                </div>
            </div>
            <!--End PIE  & BAR chart -->
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
    <!-- ✅ LOGIN SUCCESS POPUP -->
    <div id="login-popup" class="popup" style="display: none; background-color: #28a745;">
        <div class="popup-content">
            <p>👋 Welcome back! You’re logged in.</p>
            <button onclick="closeLoginPopup()">Close</button>
        </div>
    </div>
    
</body>



<script>
document.querySelectorAll('.card').forEach((card, index) => {
  const links = [
    'daily_income.php'
  ];
  card.style.cursor = 'pointer';
  card.addEventListener('click', () => {
    window.location.href = links[index];
  });
});
</script>


<script>
  // ✅ Redirect only when clicking on the Daily Income card
  const DailyIncomecard = document.querySelector('.Daily_Income-card');
  if (DailyIncomecard) {
    DailyIncomecard.addEventListener('click', () => {
      window.location.href = 'barchart_daily_income.php';
    });
  }
</script>

<script>
  // ✅ Redirect only when clicking on the Savings card
  const savingsCard = document.querySelector('.savings-card');
  if (savingsCard) {
    savingsCard.addEventListener('click', () => {
      window.location.href = 'daily_income.php';
    });
  }
</script>

<script>
  // ✅ Redirect only when clicking on the Income vs Expenses card
  const incomeVsExpensesCard = document.querySelector('.Income_vs_Expenses-card');
  if (incomeVsExpensesCard) {
    incomeVsExpensesCard.addEventListener('click', () => {
      window.location.href = 'piechart.php';
    });
  }
</script>

<script>
  // ✅ Redirect only when clicking on the expense card
  const expenseCard = document.querySelector('.expense-card');
  if (expenseCard) {
    expenseCard.addEventListener('click', () => {
      window.location.href = 'daily_income.php';
    });
  }
</script>

<script>
  // ✅ Redirect only when clicking on the Income card
  const incomeCard = document.querySelector('.income-card');
  if (incomeCard) {
    incomeCard.addEventListener('click', () => {
      window.location.href = 'daily_income.php'; // change URL if needed
    });
  }
</script>

<!-- ✅ Pie Chart Script for Income Distribution -->
<script>
const incomeVsExpenses = <?php echo json_encode($incomeVsExpensesData, JSON_NUMERIC_CHECK); ?>;

const ctxIncomePie = document.getElementById('incomePieChart').getContext('2d');
const incomePieChart = new Chart(ctxIncomePie, {
    type: 'pie',
    data: {
        labels: incomeVsExpenses.labels,
        datasets: [{
            label: 'Income vs Expenses (All Users)',
            data: incomeVsExpenses.data,
            backgroundColor: [
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 99, 132, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Income vs Expenses (All Users)' }
        }
    }
});
</script>

<script>
    const netIncomeData = {
        labels: [
            "Total Income",
            "Total Expenses"
        ],
        datasets: [{
            label: 'Total Income vs Total Expenses',
            data: [
                <?php echo $total_income ?: 0; ?>,
                <?php echo $total_expense ?: 0; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)', // Income - Blue
                'rgba(255, 99, 132, 0.7)'  // Expense - Red
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(255, 99, 132, 1)'
            ],
            borderWidth: 1
        }]
    };

    const ctxNetPie = document.getElementById('net_incomePieChart').getContext('2d');
    const netIncomePieChart = new Chart(ctxNetPie, {
        type: 'pie',
        data: netIncomeData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Total Income vs Total Expenses'
                }
            }
        }
    });
</script>
    <!-- ✅ Daily reminder -->
    <script>
        function closeDailyReminder() {
            document.getElementById("daily-reminder-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        window.addEventListener("DOMContentLoaded", () => {
            <?php if (!empty($_SESSION['show_reminder'])): ?>
                document.getElementById("daily-reminder-popup").style.display = "block";
                <?php unset($_SESSION['show_reminder']); ?>
            <?php endif; ?>
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


<script>
// ✅ Line Chart Data
const lineLabels = <?php echo json_encode($dates); ?>;
const dailyIncome = <?php echo json_encode($daily_income, JSON_NUMERIC_CHECK); ?>;
const dailyExpenses = <?php echo json_encode($daily_expenses, JSON_NUMERIC_CHECK); ?>;

const ctxLine = document.getElementById('lineChart').getContext('2d');
const lineChart = new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: lineLabels,
        datasets: [
            {
                label: 'Daily Income',
                data: dailyIncome,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Daily Expenses',
                data: dailyExpenses,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Daily Income vs Expenses' }
        },
        scales: {
            x: { title: { display: true, text: 'Date' } },
            y: { title: { display: true, text: 'Amount (₱)' }, beginAtZero: true }
        }
    }
});
</script>

<script src="asset/js/POPUP_message_dasboard.js"></script>
<script src="script_barchart.js"></script>
<script src="script_barchart_B.js"></script>
<script src="asset/js/burger_menu.js"></script>
<script src="asset/js/search_dashbord.js"></script>
</body>
</html>
