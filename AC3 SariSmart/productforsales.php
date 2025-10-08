<?php 
session_start();

// ✅ Fix timezone
date_default_timezone_set('Asia/Manila');

// DB connection
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ac_sarismart');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($link === false) die("ERROR: Could not connect. " . mysqli_connect_error());

// Get username from session
$username = $_SESSION["username"] ?? 'guest';

// Fetch profile image
$sql = "SELECT profile_image FROM login WHERE username = ?";
$profile_image = "";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $profile_image);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
if (empty($profile_image)) $profile_image = "usericon.jpg";

// -------------------- Handle POST --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ✅ SAVE SALE
    if ($action === "save_sale") {
        $id = (int)$_POST["id"];
        $quantity_sale = (int)$_POST["quantity_sale"];

        // Fetch product
        $result = mysqli_query($link, "SELECT * FROM products WHERE id=$id");
        $product = mysqli_fetch_assoc($result);

        if ($product) {
            $currentQty = (int)$product['quantity'];
            $price = (float)$product['price'];

            if ($quantity_sale > 0 && $quantity_sale <= $currentQty) {
                $transactionSale = $quantity_sale * $price;

                // Update product stock and accumulated sale
                $newQty = $currentQty - $quantity_sale;
                $status = ($newQty <= 0) ? "sold_out" : "available";

                $sqlUpdate = "UPDATE products 
                              SET quantity=?, 
                                  sale=sale+?, 
                                  status=? 
                              WHERE id=?";
                if ($stmt = mysqli_prepare($link, $sqlUpdate)) {
                    mysqli_stmt_bind_param($stmt, "idsi", $newQty, $transactionSale, $status, $id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                // ✅ After updating → compute total of all products.sale
                $resTotal = mysqli_query($link, "SELECT SUM(sale) AS total_sale FROM products");
                $rowTotal = mysqli_fetch_assoc($resTotal);
                $grandTotal = (float)($rowTotal['total_sale'] ?? 0);

                // ✅ Update all rows with current grand total in `sale_amount`
                $sqlUpdateAll = "UPDATE products SET sale_amount=?";
                if ($stmt = mysqli_prepare($link, $sqlUpdateAll)) {
                    mysqli_stmt_bind_param($stmt, "d", $grandTotal);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            } else {
                echo "<script>alert('❌ Not enough stock available.');</script>";
            }
        }

        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    }

    // ✅ DELETE PRODUCT
    if ($action === "delete") {
        $id = (int)$_POST["id"];

        // Delete product
        $sqlDelete = "DELETE FROM products WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sqlDelete)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // ✅ Recalculate total sales (grand total)
        $resTotal = mysqli_query($link, "SELECT SUM(sale) AS total_sale FROM products");
        $rowTotal = mysqli_fetch_assoc($resTotal);
        $grandTotal = (float)($rowTotal['total_sale'] ?? 0);

        // ✅ Update all rows with current grand total in `sale_amount`
        $sqlUpdateAll = "UPDATE products SET sale_amount=?";
        if ($stmt = mysqli_prepare($link, $sqlUpdateAll)) {
            mysqli_stmt_bind_param($stmt, "d", $grandTotal);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    }
}

// ------------------ Fetch Products (exclude sold_out) ------------------
$result = mysqli_query($link, "SELECT * FROM products WHERE status != 'sold_out' ORDER BY quantity DESC, id DESC");
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ✅ Compute overall total sales from products
$totalSales = 0;
$resSales = mysqli_query($link, "SELECT SUM(sale) AS total FROM products");
if ($row = mysqli_fetch_assoc($resSales)) {
    $totalSales = (float)($row['total'] ?? 0);

    // ✅ Sync sale_amount column with the latest total
    $sqlUpdateAll = "UPDATE products SET sale_amount=?";
    if ($stmt = mysqli_prepare($link, $sqlUpdateAll)) {
        mysqli_stmt_bind_param($stmt, "d", $totalSales);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
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
    <link rel="stylesheet" href="asset/css/productlist.css">
    <link rel="website icon" type="png/jpg" href="images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    
    <title>AC³SariSmart - Product For Sale</title>
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
  /* Barcode container inside table */
.barcode-svg {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* Modal barcode */
#barcode {
  width: 100%;
  max-width: 300px;   /* Limit max size for readability */
  height: auto;
  margin: 0 auto;
}
/* =======================
   Print Styles
   ======================= */
@media print {
  body * {
    visibility: hidden;
  }

  #printContainer, #printContainer * {
    visibility: visible;
  }

  #printContainer {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10px;
    background: #fff;
  }

  #printContainer p {
    font-size: 12px;
  }

  .action-btn {
    display: none !important;
  }

  @page {
    margin: 5mm;
  }
}

/* =======================
   Responsive Table
   ======================= */
@media (max-width: 768px) {
  th, td {
    font-size: 12px;
    padding: 8px 10px;
  }

  .action-btn {
    font-size: 12px;
    padding: 8px 12px;
  }
}
/* Align print button to the right */
.action-btn.print-btn {
  float: right;
  margin-right: 10px;
}

.image-container {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 60px;
}

.image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ccc;
}

/* Sold Out Overlay */
.soldout-badge {
  position: absolute;
  top: 5px;
  left: 5px;
  background: rgba(255, 0, 0, 0.9); /* brighter red */
  color: white;
  font-size: 12px;         /* larger font */
  font-weight: bold;
  padding: 4px 8px;        /* bigger padding */
  border-radius: 4px;
  text-transform: uppercase;
  transform: rotate(-15deg); /* slight tilt for effect */
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 10;
  pointer-events: none;     /* prevent clicking the badge */
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
    
            <div class="content-2">
                <div class="recent-payment">
                    <div class="title">
                        <h2>Product For Sale</h2>
                    </div>
                    
                  <table id="recordsTable">
                    <thead>
                      <tr>
                        <th>Product Name</th>
                        <th>Image</th>
                        <th>Sale Quantity</th>
                        <th>Remaining Quantity</th>
                        <th>Price</th>
                        <th>Sale (₱)</th> <!-- ✅ Added column header -->
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach($products as $p): 
                          $quantity_sale = (int)($p['quantity_sale'] ?? 0);
                          $remainingQty = (int)$p['quantity'] - $quantity_sale;
                      ?>
                        <tr data-id="<?= $p['id'] ?>">
                          <td><?= htmlspecialchars($p['product_name']) ?></td>
                          <td>
                            <div class="image-container">
                              <?php if(!empty($p['image'])): ?>
                                <img src="images/product_upload/<?= htmlspecialchars($p['image']) ?>" alt="Image">
                              <?php else: ?>
                                <img src="images/product_upload/default.png" alt="No Image">
                              <?php endif; ?>
                              <?php if($remainingQty <= 0): ?>
                                <div class="soldout-badge">Sold Out</div>
                              <?php endif; ?>
                            </div>
                          </td>
                          <td>
                            <form method="POST" style="display:flex;gap:5px;align-items:center;">
                              <input type="hidden" name="action" value="save_sale">
                              <input type="hidden" name="id" value="<?= $p['id'] ?>">
                              <input type="number" 
                                    name="quantity_sale" 
                                    value="<?= $quantity_sale ?>" 
                                    min="0" 
                                    max="<?= $p['quantity'] ?>" 
                                    <?= ($remainingQty <= 0)?'disabled':'' ?> 
                                    style="width:60px;">
                              <button type="submit" <?= ($remainingQty <= 0)?'disabled':'' ?>>✔</button>
                            </form>
                          </td>
                          <td><?= $remainingQty ?></td>
                          <td><?= number_format($p['price'],2) ?></td>
                          <td><?= number_format($p['sale'] ?? 0,2) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="5" style="text-align:right; font-weight:bold;">Total Sales:</td>
                        <td><?= number_format($totalSales, 2) ?></td>
                      </tr>
                    </tfoot>
                  </table>

                   <!-- Hidden container for printing -->
                  <div id="printContainer" style="display:none;"></div>

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
                    <!--END Delete Modal -->
                </div>
            </div>
        </div>
    </div>
</body>
<script>
document.getElementById("printAllBtn").addEventListener("click", () => {
  const rows = document.querySelectorAll("#recordsTable tbody tr");
  const total = rows.length;
  const rowsCount = 4; // number of rows per page
  const perRow = Math.ceil(total / rowsCount);

  const printWindow = window.open('', '', 'height=900,width=1000');
  printWindow.document.write('<html><head><title>Print Barcodes</title>');
  printWindow.document.write(`
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 5mm;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
      }
      .barcode-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      .barcode-box {
        border: 1px solid #333;
        padding: 10px;
        border-radius: 4px;
        text-align: center;
        flex: 1;
        margin: 5px;
        max-width: calc((100% - 40px)/8);
      }
      svg { display: block; margin: 0 auto; width: 100%; height: auto; }
      @media print {
        body { margin: 5mm; }
        .barcode-box { page-break-inside: avoid; }
      }
    </style>
  `);
  printWindow.document.write('</head><body>');

  // generate rows
  for (let i = 0; i < rowsCount; i++) {
    printWindow.document.write('<div class="barcode-row">');
    const start = i * perRow;
    const end = Math.min(start + perRow, total);
    for (let j = start; j < end; j++) {
      const row = rows[j];
      const productName = row.dataset.product_name + "-" + row.dataset.quantity + "-" + row.dataset.price;
      printWindow.document.write(`<div class="barcode-box"><svg data-code="${productName}"></svg></div>`);
    }
    printWindow.document.write('</div>');
  }

  printWindow.document.write('</body></html>');
  printWindow.document.close();

  // Load JsBarcode inside print window
  const script = printWindow.document.createElement("script");
  script.src = "https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js";
  script.onload = () => {
    const svgs = printWindow.document.querySelectorAll("svg");
    svgs.forEach(svg => {
      const text = svg.getAttribute("data-code");
      JsBarcode(svg, text, {
        format: "CODE128",
        displayValue: true,   // ✅ show text under barcode
        fontSize: 40,         // ✅ adjust text size
        textMargin: 2,        // ✅ space between barcode & text
        width: 2,
        height: 50,
        margin: 5
      });
    });
    printWindow.print();
  };
  printWindow.document.body.appendChild(script);
});
</script>

<script>
  const burger = document.getElementById('burger');
  const sideMenu = document.getElementById('side-menu');

  burger.addEventListener('click', () => {
    sideMenu.classList.toggle('active');
  });

const profileImg = document.getElementById('profile-img');
const dropdownMenu = document.getElementById('dropdown-menu');
const dropdownArrow = document.getElementById('dropdown-arrow');

profileImg.addEventListener('click', (e) => {
  // Only open dropdown if it's closed
  if (!dropdownMenu.classList.contains('show')) {
    dropdownMenu.classList.add('show');
    dropdownArrow.textContent = '▲'; // up arrow when open
  }
});

dropdownArrow.addEventListener('click', (e) => {
  e.stopPropagation(); // prevent bubbling
  if (dropdownMenu.classList.contains('show')) {
    dropdownMenu.classList.remove('show');
    dropdownArrow.textContent = '▼'; // down arrow when closed
  } else {
    dropdownMenu.classList.add('show');
    dropdownArrow.textContent = '▲'; // up arrow when open
  }
});
</script>

<!-- delete modal only -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  // Modals
  const deleteModal = document.getElementById('deleteModal');

  // Action button
  const deleteBtn = document.getElementById('deleteBtn');

  // Close buttons
  const closeButtons = document.querySelectorAll('.close-btn');

  // Table and row selection
  const recordsTableBody = document.querySelector('#recordsTable tbody');
  let selectedRow = null;

  // Event delegation for row click
  recordsTableBody.addEventListener('click', (e) => {
    const row = e.target.closest('tr');
    if (!row) return;

    if (selectedRow) selectedRow.style.backgroundColor = '';
    selectedRow = row;
    row.style.backgroundColor = '#d3d3d3'; // highlight current row
  });

  // Open Delete Modal
  deleteBtn.addEventListener('click', () => {
    if (!selectedRow) return alert('Please select a record to delete.');

    document.getElementById('deleteId').value = selectedRow.dataset.id;
    deleteModal.style.display = 'block';
  });

  // Close modals on close button click
  closeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.close;
      const modal = document.getElementById(modalId);
      if (modal) modal.style.display = 'none';
    });
  });

  // Close modals when clicking outside content
  window.addEventListener('click', (e) => {
    if (e.target === deleteModal) deleteModal.style.display = 'none';
  });
});
</script>
<!--END delete modals-->

<!--search bar and microphone-->
<script>
const searchInput = document.getElementById("searchInput");
const rows = document.querySelectorAll("#recordsTable tbody tr");

// Live filter while typing
searchInput.addEventListener("keyup", searchTable);

function searchTable() {
  const input = searchInput.value.toLowerCase();
  rows.forEach(row => {
    const cells = row.querySelectorAll("td");
    let matchFound = false;

    // Reset old highlights
    cells.forEach(cell => {
      cell.innerHTML = cell.textContent;
    });

    // Check each cell
    cells.forEach(cell => {
      const text = cell.textContent.toLowerCase();
      if (text.includes(input) && input !== "") {
        matchFound = true;
        // Highlight matching part
        const regex = new RegExp(`(${input})`, "gi");
        cell.innerHTML = cell.textContent.replace(regex, `<span class="highlight">$1</span>`);
      }
    });

    /* Show/hide row
    row.style.display = (input === "" || matchFound) ? "" : "none";*/
  });
}

// 🎤 Voice Search
function startDictation() {
  if ('webkitSpeechRecognition' in window) {
    const recognition = new webkitSpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = "en-US";

    recognition.start();

    recognition.onresult = function(event) {
      const transcript = event.results[0][0].transcript;
      searchInput.value = transcript; // show in search box
      recognition.stop();
      searchTable(); // filter & highlight instantly
    };

    recognition.onerror = function(event) {
      recognition.stop();
      alert("Speech recognition error: " + event.error);
    };
  } else {
    alert("Speech recognition not supported in this browser. Try Chrome.");
  }
}
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
</body>
</html>
