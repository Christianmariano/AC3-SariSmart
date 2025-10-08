<?php
session_start();

// ✅ Fix timezone to Philippines
date_default_timezone_set('Asia/Manila');

// ✅ Today’s date (local time)
$today = date('Y-m-d');

// DB connection
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Get username from session
$username = $_SESSION["username"] ?? 'guest';

// Fetch profile image from database
$sql = "SELECT profile_image FROM login WHERE username = ?";
$profile_image = "";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $profile_image);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// ✅ Fallback if no profile image
if (empty($profile_image)) {
    $profile_image = "usericon.jpg";
}

/**
 * ✅ Function to recalculate and update income_summary for a given date
 * Now includes GCash in the net income formula
 */
function updateIncomeSummary($link, $date) {
    $sql = "SELECT 
                SUM(income_store) AS total_store, 
                SUM(income_school_service) AS total_school, 
                SUM(expense_store) AS total_expense_store, 
                SUM(expense_school_service) AS total_expense_school, 
                SUM(saving) AS total_saving,
                SUM(gcash) AS total_gcash
            FROM daily_records WHERE date=?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_store, $total_school, $exp_store, $exp_school, $total_saving, $total_gcash);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $total_income = floatval($total_store) + floatval($total_school);
        $total_expense = floatval($exp_store) + floatval($exp_school);
        $total_gcash = floatval($total_gcash);

        // ✅ Updated net income formula (includes GCash)
        $net_income = $total_income - $total_expense + floatval($total_saving) + floatval($total_gcash);

        // If nothing exists for this date, remove summary row; else insert/update
        if ($total_income == 0 && $total_expense == 0 && $total_saving == 0 && $total_gcash == 0) {
            $del_sql = "DELETE FROM income_summary WHERE date=?";
            if ($del_stmt = mysqli_prepare($link, $del_sql)) {
                mysqli_stmt_bind_param($del_stmt, "s", $date);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);
            }
        } else {
            // Check if row exists
            $check_sql = "SELECT id FROM income_summary WHERE date=?";
            if ($check_stmt = mysqli_prepare($link, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "s", $date);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);

                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    // Update existing record
                    $update_sql = "UPDATE income_summary 
                                   SET total_income=?, total_expense=?, net_income=?, saving=?, gcash=? 
                                   WHERE date=?";
                    if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "ddddds", $total_income, $total_expense, $net_income, $total_saving, $total_gcash, $date);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    // Insert new record
                    $insert_sql = "INSERT INTO income_summary (date, total_income, total_expense, net_income, saving, gcash) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    if ($insert_stmt = mysqli_prepare($link, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "sddddd", $date, $total_income, $total_expense, $net_income, $total_saving, $total_gcash);
                        mysqli_stmt_execute($insert_stmt);
                        mysqli_stmt_close($insert_stmt);
                    }
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    }
}

// ✅ Handle CRUD actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $income_store = floatval($_POST['income_store'] ?? 0);
    $income_school_service = floatval($_POST['income_school_service'] ?? 0);
    $expense_store = floatval($_POST['expense_store'] ?? 0);
    $expense_school_service = floatval($_POST['expense_school_service'] ?? 0);
    $saving = floatval($_POST['saving'] ?? 0);
    $gcash = floatval($_POST['gcash'] ?? 0);

    if ($action === 'add') {
        // ✅ Allow add only for today
        if ($date !== $today) {
            $_SESSION['popup_message'] = "You can only add records for today's date ($today).";
            header("Location: daily_income.php");
            exit;
        }

        $sql = "INSERT INTO daily_records (date, income_store, income_school_service, expense_store, expense_school_service, gcash, saving) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sdddddd", $date, $income_store, $income_school_service, $expense_store, $expense_school_service, $gcash, $saving);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            updateIncomeSummary($link, $date);
            $_SESSION['popup_message'] = "Record added successfully!";
        } else {
            $_SESSION['popup_message'] = "Failed to add record.";
        }
        header("Location: daily_income.php");
        exit;
    }

    if ($action === 'edit') {
        // ✅ Edit allowed for any date
        $sql = "UPDATE daily_records 
                SET date=?, income_store=?, income_school_service=?, expense_store=?, expense_school_service=?, gcash=?, saving=? 
                WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sddddddi", $date, $income_store, $income_school_service, $expense_store, $expense_school_service, $gcash, $saving, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            updateIncomeSummary($link, $date);
            $_SESSION['popup_message'] = "Record updated successfully!";
        } else {
            $_SESSION['popup_message'] = "Failed to update record.";
        }
        header("Location: daily_income.php");
        exit;
    }

    if ($action === 'delete') {
        // Get the date of the record before deleting
        $date_sql = "SELECT date FROM daily_records WHERE id=?";
        $record_date = null;

        if ($stmt = mysqli_prepare($link, $date_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $record_date);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        // Delete record
        $sql = "DELETE FROM daily_records WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($record_date) {
                updateIncomeSummary($link, $record_date);
            }

            $_SESSION['popup_message'] = "Record deleted successfully!";
        } else {
            $_SESSION['popup_message'] = "Failed to delete record.";
        }
        header("Location: daily_income.php");
        exit;
    }
}

// ✅ Fetch all daily records
$sql = "SELECT * FROM daily_records ORDER BY date DESC";
$result = mysqli_query($link, $sql);

$records = [];
$total_income = 0;
$total_expense = 0;
$saving = 0;
$gcash_total = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
        $total_income += floatval($row['income_store']) + floatval($row['income_school_service']);
        $total_expense += floatval($row['expense_store']) + floatval($row['expense_school_service']);
        $saving += floatval($row['saving']);
        $gcash_total += floatval($row['gcash'] ?? 0);
    }
}

// ✅ Updated net income formula (includes GCash)
$net_income = $total_income - $total_expense + $saving + $gcash_total;

// ✅ Always update today’s summary
updateIncomeSummary($link, $today);

// Get popup message
$popup_message = '';
if (!empty($_SESSION['popup_message'])) {
    $popup_message = $_SESSION['popup_message'];
    unset($_SESSION['popup_message']);
}

// ✅ Full path for header and form images
$profile_image_path = "./images/profile/" . htmlspecialchars($profile_image);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./asset/css/dailyincome.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="website icon" type="png/jpg" href="images/logo.png">
    
    <title>AC³SariSmart - Daily Income</title>
    <style>
  .popup {
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .popup-content {
    background: white;
    padding: 20px 30px;
    border-radius: 8px;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  }
  .close-btn {
    margin-top: 15px;
    background-color: #f44336;
    border: none;
    color: white;
    padding: 8px 16px;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
  }
  .close-btn:hover {
    background-color: #d73225;
  }
  
  /* Highlighted text style */
  .highlight {
    background-color: yellow;
    font-weight: bold;
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
  <!-- Popup -->
<div id="popupMessage" class="popup" style="display:none;">
  <div class="popup-content">
    <p id="popupText"></p>
    <button id="closePopupBtn" class="close-btn">Close</button>
  </div>
</div>
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
    
            <div class="content-2">
                <div class="recent-payment">
                    <div class="title">
                        <h2>Daily Income</h2>
                    </div>
                    <div class="action-buttons" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <!-- Left side buttons -->
                        <div class="left-buttons" style="display: flex; gap: 10px;">
                            <button class="action-btn" id="addBtn"><i class="fas fa-plus-circle"></i> Add</button>
                            <button class="action-btn" id="editBtn"><i class="fas fa-edit"></i> Edit</button>
                            <button class="action-btn" id="deleteBtn"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>

                        <!-- Right side button -->
                        <div class="right-buttons">
                            <button class="action-btn" id="exportBtn"><i class="fas fa-file-export"></i> Export into Excel</button>
                        </div>
                    </div>
                    
                    <table id="recordsTable">
                      <tr>
                        <th rowspan="3">Date</th>
                        <th colspan="2">Income</th>
                        <th colspan="2">Expenses</th>
                      </tr>
                      <tr>
                        <th>Store</th>
                        <th>School Service</th>
                        <th>Store</th>
                        <th>School Service</th>
                      </tr>
                      
                       <tbody>
                          <?php if (!empty($records)): ?>
                            <?php foreach ($records as $record): ?>
                              <tr data-id="<?php echo $record['id']; ?>"
                                  data-date="<?php echo htmlspecialchars($record['date']); ?>"
                                  data-income_store="<?php echo $record['income_store']; ?>"
                                  data-income_school_service="<?php echo $record['income_school_service']; ?>"
                                  data-expense_store="<?php echo $record['expense_store']; ?>"
                                  data-expense_school_service="<?php echo $record['expense_school_service']; ?>"
                                  data-saving="<?php echo $record['saving']; ?>"
                              >
                                <td><?php echo htmlspecialchars($record['date']); ?></td>
                                <td><?php echo number_format($record['income_store'], 2); ?></td>
                                <td><?php echo number_format($record['income_school_service'], 2); ?></td>
                                <td><?php echo number_format($record['expense_store'], 2); ?></td>
                                <td><?php echo number_format($record['expense_school_service'], 2); ?></td>
                              </tr>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr><td colspan="5">No records found.</td></tr>
                          <?php endif; ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <th colspan="1" style="text-align: left;">Total Income</th>
                            <th colspan="2"><?php echo number_format($total_income, 2); ?></th>
                            <th colspan="1" style="text-align: left;">Total Expense</th>
                            <th colspan="2"><?php echo number_format($total_expense, 2); ?></th>
                          </tr>
                          <tr>
                            <th colspan="1" style="text-align: left;">Saving:</th>
                            <th colspan="4"><?php echo number_format($saving, 2); ?></th>
                          </tr>
                          <tr>
                            <th colspan="1" style="text-align: left;">GCash:</th>
                            <th colspan="4"><?php echo number_format($gcash_total, 2); ?></th>
                          </tr>

                          <tr>
                            <th colspan="1" style="text-align: left;">Net Income:</th>
                            <th colspan="4"><?php echo number_format($total_income - $total_expense + $saving + $gcash_total, 2); ?></th>
                          </tr>
                        </tfoot>
                      </table>

                      <canvas id="incomeExpenseChart" style="max-width: 700px; margin-top: 30px;"></canvas>
                    </div>

                    <!-- Add Modal -->
                    <div class="modal" id="addModal">
                      <div class="modal-content">
                        <span class="close-btn" data-close="addModal">&times;</span>
                        <h2>Add Record</h2>
                        <form method="POST" id="addForm">
                          <input type="hidden" name="action" value="add" />
                          <label>Date:</label>
                          <input type="date" name="date" required /><br/><br/>
                          
                          <label>Income - Store:</label>
                          <input type="number" step="0.01" name="income_store" /><br/><br/>
                          
                          <label>Income - School Service:</label>
                          <input type="number" step="0.01" name="income_school_service" /><br/><br/>
                          
                          <label>Expense - Store:</label>
                          <input type="number" step="0.01" name="expense_store" /><br/><br/>
                          
                          <label>Expense - School Service:</label>
                          <input type="number" step="0.01" name="expense_school_service" /><br/><br/>

                          <!-- ✅ GCash Saving -->
                          <label>GCash:</label>
                          <input type="number" step="0.01" name="gcash" /><br/><br/>
                          
                          <!-- ✅ Added Saving -->
                          <label>Saving:</label>
                          <input type="number" step="0.01" name="saving" /><br/><br/>

                          <button type="submit" class="action-btn">Add Record</button>
                        </form>
                      </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal" id="editModal">
                      <div class="modal-content">
                        <span class="close-btn" data-close="editModal">&times;</span>
                        <h2>Edit Record</h2>
                        <form method="POST" id="editForm">
                          <input type="hidden" name="action" value="edit" />
                          <input type="hidden" name="id" id="editId" />
                          
                          <label>Date:</label>
                          <input type="date" name="date" id="editDate" required /><br/><br/>
                          
                          <label>Income - Store:</label>
                          <input type="number" step="0.01" name="income_store" id="editIncomeStore" /><br/><br/>
                          
                          <label>Income - School Service:</label>
                          <input type="number" step="0.01" name="income_school_service" id="editIncomeSchool" /><br/><br/>
                          
                          <label>Expense - Store:</label>
                          <input type="number" step="0.01" name="expense_store" id="editExpenseStore" /><br/><br/>
                          
                          <label>Expense - School Service:</label>
                          <input type="number" step="0.01" name="expense_school_service" id="editExpenseSchool" /><br/><br/>
                          
                          <!-- ✅ GCash -->
                          <label>GCash:</label>
                          <input type="number" step="0.01" name="gcash" id="editgcash" /><br/><br/>

                          <!-- ✅ Added Saving -->
                          <label>Saving:</label>
                          <input type="number" step="0.01" name="saving" id="editSaving" /><br/><br/>

                          <button type="submit" class="action-btn">Save Changes</button>
                        </form>
                      </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal" id="deleteModal">
                      <div class="modal-content">
                        <span class="close-btn" data-close="deleteModal">&times;</span>
                        <h2>Delete Record</h2>
                        <form method="POST" id="deleteForm">
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="id" id="deleteId" />
                          <p>Are you sure you want to delete this record?</p>
                          <button type="submit" class="action-btn">Confirm Delete</button>
                        </form>
                      </div>
                    </div>       
                </div>
            </div>
        </div>
    </div>
</body>

<script>
  // Modal controls
  const addModal = document.getElementById('addModal');
  const editModal = document.getElementById('editModal');
  const deleteModal = document.getElementById('deleteModal');

  const addBtn = document.getElementById('addBtn');
  const editBtn = document.getElementById('editBtn');
  const deleteBtn = document.getElementById('deleteBtn');

  const closeButtons = document.querySelectorAll('.close-btn');

  const recordsTable = document.getElementById('recordsTable');
  let selectedRow = null;

  // Select row on click
  recordsTable.querySelectorAll('tbody tr').forEach(row => {
    row.addEventListener('click', () => {
      // Clear previous selection
      if(selectedRow) {
        selectedRow.style.backgroundColor = '';
      }
      selectedRow = row;
      row.style.backgroundColor = '#d3d3d3'; // highlight
    });
  });

  // Open Add Modal
  addBtn.addEventListener('click', () => {
    addModal.style.display = 'block';
  });

  // Open Edit Modal - needs a row selected
   editBtn.addEventListener('click', () => {
    if(!selectedRow) {
        alert('Please select a record to edit.');
        return;
    }

    document.getElementById('editId').value = selectedRow.dataset.id;
    document.getElementById('editDate').value = selectedRow.dataset.date;
    document.getElementById('editIncomeStore').value = selectedRow.dataset.income_store;
    document.getElementById('editIncomeSchool').value = selectedRow.dataset.income_school_service;
    document.getElementById('editExpenseStore').value = selectedRow.dataset.expense_store;
    document.getElementById('editExpenseSchool').value = selectedRow.dataset.expense_school_service;

    // ✅ Populate Saving
    document.getElementById('editSaving').value = selectedRow.dataset.saving;

    editModal.style.display = 'block';
});

  // Open Delete Modal - needs a row selected
  deleteBtn.addEventListener('click', () => {
    if(!selectedRow) {
      alert('Please select a record to delete.');
      return;
    }
    document.getElementById('deleteId').value = selectedRow.dataset.id;
    deleteModal.style.display = 'block';
  });

  // Close modals on clicking close buttons
  closeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.getAttribute('data-close');
      document.getElementById(modalId).style.display = 'none';
    });
  });

  // Close modals on clicking outside modal content
  window.addEventListener('click', e => {
    if (e.target === addModal) addModal.style.display = 'none';
    if (e.target === editModal) editModal.style.display = 'none';
    if (e.target === deleteModal) deleteModal.style.display = 'none';
  });
</script>

<!--search bar and microphone-->
<script>

</script>

<!--END search bar and microphone-->

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const message = <?php echo json_encode($popup_message); ?>;
    if (message) {
      const popup = document.getElementById('popupMessage');
      const popupText = document.getElementById('popupText');
      const closeBtn = document.getElementById('closePopupBtn');

      popupText.textContent = message;
      popup.style.display = 'flex';

      closeBtn.addEventListener('click', () => {
        popup.style.display = 'none';
      });

      // Optional: close when clicking outside popup content
      popup.addEventListener('click', (e) => {
        if (e.target === popup) {
          popup.style.display = 'none';
        }
      });
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
<script src="asset/js/search_bar_microphone_daily_income.js"></script>
<script src="asset/js/export_barcode.js"></script>
<script src="asset/js/burger_menu.js"></script>
</body>
</html>