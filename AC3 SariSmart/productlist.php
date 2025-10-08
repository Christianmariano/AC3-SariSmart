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

// Initialize popup message to avoid undefined variable
$popup_message = "";

// Handle Add/Edit/Delete/Save Barcode/Add Sale Actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // ---------------- ADD SALE ----------------
    if ($action === "add_sale") {
        $product_id = (int)($_POST["product_id"] ?? 0);
        $quantity_sold = (int)($_POST["quantity_sold"] ?? 0);

        // Get product details (prepared)
        if ($stmt = mysqli_prepare($link, "SELECT product_name, price, quantity FROM products WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $product_name, $price, $remaining_qty);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $remaining_qty = 0;
        }

        // Check if enough stock
        if ($quantity_sold > $remaining_qty) {
            $popup_message = "❌ Not enough stock!";
        } else {
            // Record sale (prepared)
            $total = $quantity_sold * $price;
            if ($stmt = mysqli_prepare($link, "INSERT INTO sales (product_id, product_name, quantity_sold, price, total) VALUES (?, ?, ?, ?, ?)")) {
                mysqli_stmt_bind_param($stmt, "isidd", $product_id, $product_name, $quantity_sold, $price, $total);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // Update product stock & status
            $new_qty = $remaining_qty - $quantity_sold;
            $status = ($new_qty > 0) ? "active" : "sold out";
            if ($stmt = mysqli_prepare($link, "UPDATE products SET quantity = ?, status = ? WHERE id = ?")) {
                mysqli_stmt_bind_param($stmt, "isi", $new_qty, $status, $product_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $popup_message = "✅ Sale recorded.";
        }
    }

    // ---------------- SAVE BARCODE ----------------
    if ($action === "save_barcode") {
        $id = (int)($_POST['id'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');
        $product_name = trim($_POST['product_name'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 1);
        $price = floatval($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? 'default_image.png');
        $status = ($quantity > 0) ? 'active' : 'sold out';

        if ($barcode !== '') {
            if ($id > 0) {
                // Update existing product
                if ($stmt = mysqli_prepare($link, "UPDATE products SET barcode = ? WHERE id = ?")) {
                    mysqli_stmt_bind_param($stmt, "si", $barcode, $id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $popup_message = "✅ Barcode saved to existing product.";
                }
            } else if ($product_name !== '') {
                // Insert new product
                if ($stmt = mysqli_prepare($link, "INSERT INTO products (product_name, quantity, price, image, status, barcode) VALUES (?, ?, ?, ?, ?, ?)")) {
                    mysqli_stmt_bind_param($stmt, "sdssss", $product_name, $quantity, $price, $image, $status, $barcode);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $popup_message = "✅ New product added with scanned barcode.";
                }
            } else {
                $popup_message = "❌ Product name required for new product.";
            }
        } else {
            $popup_message = "❌ Invalid barcode data.";
        }
    }

  // ---------------- ADD PRODUCT ----------------
if ($action === "add") {
    $product_name = trim($_POST['product_name'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    // Handle image upload if provided
    $image_name = "default_image.png"; // ✅ fallback image
    $target_dir = __DIR__ . "/images/product_upload/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('prod_') . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
    }

    if ($product_name !== '') {
        if ($stmt = mysqli_prepare($link, "INSERT INTO products (product_name, quantity, price, image, status) VALUES (?, ?, ?, ?, ?)")) {
            mysqli_stmt_bind_param($stmt, "sdiss", $product_name, $quantity, $price, $image_name, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $popup_message = "✅ Product added.";
        }
    } else {
        $popup_message = "❌ Product name required.";
    }
}


    // ---------------- EDIT PRODUCT ----------------
if ($action === "edit") {
    $id = (int)($_POST['id'] ?? 0);
    $product_name = trim($_POST['product_name'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);

    // ✅ Auto-update status based on quantity
    $status = ($quantity > 0) ? "active" : "sold out";

    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/images/product_upload/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('prod_') . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);

        if ($stmt = mysqli_prepare($link, "UPDATE products SET product_name = ?, quantity = ?, price = ?, image = ?, status = ? WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, "sdsssi", $product_name, $quantity, $price, $image_name, $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $popup_message = "✅ Product updated (image changed).";
        }
    } else {
        if ($stmt = mysqli_prepare($link, "UPDATE products SET product_name = ?, quantity = ?, price = ?, status = ? WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, "sdssi", $product_name, $quantity, $price, $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $popup_message = "✅ Product updated.";
        }
    }
}


    // ---------------- DELETE PRODUCT ----------------
    if ($action === "delete") {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            if ($stmt = mysqli_prepare($link, "DELETE FROM products WHERE id = ?")) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $popup_message = "✅ Product deleted.";
            }
        } else {
            $popup_message = "❌ Invalid ID.";
        }
    }

    // After handling POST actions, redirect to avoid form re-submission on refresh
    $_SESSION['popup_message'] = $popup_message;
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// When rendering page, pick up popup message from session (if any) and then clear it
if (!empty($_SESSION['popup_message'])) {
    $popup_message = $_SESSION['popup_message'];
    unset($_SESSION['popup_message']);
}

// Full path for header and form images
$profile_image_path = "./images/profile/" . htmlspecialchars($profile_image);

// ✅ Fetch all products EXCLUDING sold out
$result = mysqli_query($link, "SELECT * FROM products WHERE status != 'sold out' ORDER BY id DESC");
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./asset//css/productlist.css">
    <link rel="website icon" type="png/jpg" href="asset/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    
    <title>AC³SariSmart - Product List</title>
    <style>
      /* (your existing styles preserved) */
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
      .highlight { background-color: yellow; font-weight: bold; }
      .barcode-svg { max-width: 100%; height: auto; display: block; margin: 0 auto; }
      #barcode { width: 100%; max-width: 300px; height: auto; margin: 0 auto; }

      /* print/responsive styles kept as before (omitted here for brevity) */
      @media print {
        body * { visibility: hidden; }
        #printContainer, #printContainer * { visibility: visible; }
        #printContainer { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 10px; background: #fff; }
        #printContainer p { font-size: 12px; }
        .action-btn { display: none !important; }
        @page { margin: 5mm; }
      }
      @media (max-width: 768px) {
        th, td { font-size: 12px; padding: 8px 10px; }
        .action-btn { font-size: 12px; padding: 8px 12px; }
      }
      .action-btn.print-btn { float: right; margin-right: 10px; }
      .image-container { position: relative; display: inline-block; width: 60px; height: 60px; }
      .image-container img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ccc; }
      .soldout-badge {
        position: absolute; top: 5px; left: 5px;
        background: rgba(255, 0, 0, 0.9); color: white;
        font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 4px;
        text-transform: uppercase; transform: rotate(-15deg); box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        z-index: 10; pointer-events: none;
      }

      .image-container {
        position: relative;
        display: inline-block;
      }

      .image-container img {
          border-radius: 5px;
          display: block;
      }

      .soldout-badge {
          position: absolute;
          top: 5px;
          left: 5px;
          background: red;
          color: white;
          font-size: 12px;
          font-weight: bold;
          padding: 2px 6px;
          border-radius: 3px;
          opacity: 0.9;
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
      &#9776;
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
        <div class="search-bar" style="position: relative; width: 300px; margin-right: 20px;">
          <input type="text" id="searchInput" placeholder="Search..." style="width:100%;padding:10px 40px 10px 40px;border:1px solid #ddd;border-radius:50px;font-size:14px;outline:none;box-shadow:0 1px 4px rgba(0,0,0,0.1);">
          <i class="fas fa-search" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#888;"></i>
          <i class="fas fa-microphone" onclick="startDictation()" style="position:absolute;right:15px;top:50%;transform:translateY(-50%);color:#4285F4;cursor:pointer;"></i>
        </div>

        <div class="user">
          <span><p><?php echo htmlspecialchars($_SESSION["username"] ?? 'guest'); ?></p></span>
          <div class="img-case" id="profile-img" style="display:inline-block;">
              <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="User Image">
          </div>
          <div class="dropdown-menu" id="dropdown-menu">
            <div class="arrow" id="dropdown-arrow">&#x25BC;</div>
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
          <h2>Product List</h2>
        </div>

        <div class="action-buttons" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <div class="left-buttons" style="display:flex;gap:10px;">
            <button class="action-btn" id="addBtn"><i class="fas fa-plus-circle"></i>  Add</button>
            <button class="action-btn" id="generateBarcodeBtn"><i class="fas fa-barcode"></i> Generate Barcode</button>
            <!--<button class="action-btn" id="scanBarcodeBtn"><i class="fas fa-qrcode"></i> Scan Barcode</button>  -->
          </div>
          <div class="right-buttons">
            <button class="action-btn print-btn" id="printAllBtn"><i class="fas fa-print"></i> Print Barcode</button>
          </div>
        </div>

        <?php
          // Sort products: active first, sold out last
          usort($products, function($a, $b) {
              $statusA = $a['status'] ?? 'active';
              $statusB = $b['status'] ?? 'active';
              if ($statusA === $statusB) return 0;
              return ($statusA === 'active') ? -1 : 1;
          });
        ?>

        <table id="recordsTable" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr>
                <th>Product Name</th>
                <th>Image</th>
                <th>Barcode</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr data-id="<?= (int)$p['id'] ?>"
                    data-product_name="<?= htmlspecialchars($p['product_name']) ?>"
                    data-quantity="<?= htmlspecialchars($p['quantity']) ?>"
                    data-price="<?= htmlspecialchars($p['price']) ?>"
                    data-barcode="<?= htmlspecialchars($p['barcode'] ?? '') ?>"
                    data-status="<?= htmlspecialchars($p['status'] ?? 'active') ?>">
                  <td><?= htmlspecialchars($p['product_name']) ?></td>

                <!-- Image -->
                <td>
                  <div class="image-container">
                    <?php if (!empty($p['image'])): ?>
                      <img src="images/product_upload/<?= htmlspecialchars($p['image']) ?>" alt="Product Image">
                    <?php else: ?>
                      <img src="images/product_upload/default.png" alt="No Image">
                    <?php endif; ?>

                    <?php if ((int)$p['quantity'] <= 0): ?>
                      <div class="soldout-badge">Sold Out</div>
                    <?php endif; ?>
                  </div>
                </td>
   

                  <td>
                    <?php if (!empty($p['barcode'])): ?>
                      <svg class="barcode-svg"
                          jsbarcode-value="<?= htmlspecialchars($p['barcode']) ?>"
                          jsbarcode-format="CODE128"
                          jsbarcode-displayValue="true"
                          jsbarcode-width="2"
                          jsbarcode-height="40"></svg>
                    <?php endif; ?>
                  </td>

                  <td><?= htmlspecialchars($p['quantity']) ?></td>
                  <td><?= htmlspecialchars($p['price']) ?></td>
                  <td>
                    <button class="action-btn editBtn"><i class="fas fa-edit"></i> Edit</button>
                    <br><br>
                    <button class="action-btn deleteBtn"><i class="fas fa-trash-alt"></i> Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Hidden container for printing -->
        <div id="printContainer" style="display:none;"></div>

        <!-- Hidden form for saving barcode -->
        <form id="saveBarcodeForm" method="POST" style="display:none;">
          <input type="hidden" name="action" value="save_barcode">
          <input type="hidden" name="id" id="barcodeProductId">
          <input type="hidden" name="barcode" id="barcodeValue">

          <!-- New fields for inserting a product -->
          <input type="hidden" name="product_name" id="barcodeProductName">
          <input type="hidden" name="quantity" id="barcodeQuantity" value="1">
          <input type="hidden" name="price" id="barcodePrice" value="0">
          <input type="hidden" name="image" id="barcodeImage" value="default_image.png">
        </form>

        <!-- Barcode Modal -->
        <div id="barcodeModal" class="modal" style="display:none;">
          <div class="modal-content">
            <span class="close-btn" data-close="barcodeModal">&times;</span>
            <h2>Generated Barcode</h2>
            <div id="barcodeContainer" style="text-align:center; margin:20px 0;">
              <svg id="barcode"></svg>
            </div>
          </div>
        </div>

        <!-- Scanner Modal -->
        <div id="scannerModal" class="popup" style="display:none;">
          <div class="popup-content" style="width:90%;max-width:600px;text-align:center;">
            <h3>Scan Product Barcode</h3>
            <div id="reader" style="width:100%;height:400px;"></div>
            <br>
            <button class="action-btn" id="closeScanner">Close</button>
          </div>
        </div>

        <!-- Add Modal -->
        <div class="modal" id="addModal" style="display:none;">
          <div class="modal-content">
            <span class="close-btn" data-close="addModal">&times;</span>
            <h2>Add Product</h2>
            <form method="POST" id="addForm" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add" />
              <input type="hidden" name="status" value="active" />
              <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="product_name" required />
              </div>
              <div class="form-group">
                <label>Quantity:</label>
                <input type="number" step="0.01" name="quantity" required />
              </div>
              <div class="form-group">
                <label>Price:</label>
                <input type="number" step="0.01" name="price" required />
              </div>
              <div class="form-group">
                <label>Product Image:</label>
                <input type="file" name="image" accept="image/*" />
              </div>
              <button type="submit" class="action-btn">Add Record</button>
            </form>
          </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal" id="editModal" style="display:none;">
          <div class="modal-content">
            <span class="close-btn" data-close="editModal">&times;</span>
            <h2>Edit Product</h2>
            <form method="POST" id="editForm" enctype="multipart/form-data">
              <input type="hidden" name="action" value="edit" />
              <input type="hidden" name="id" id="editId" />
              <input type="hidden" name="status" id="editStatus" value="active" />
              <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="product_name" id="editProductName" required />
              </div>
              <div class="form-group">
                <label>Quantity:</label>
                <input type="number" step="0.01" name="quantity" id="editQuantity" required />
              </div>
              <div class="form-group">
                <label>Price:</label>
                <input type="number" step="0.01" name="price" id="editPrice" required />
              </div>
              <div class="form-group">
                <label>Change Image:</label>
                <input type="file" name="image" id="editImage" accept=".jpg,.jpeg,.png,.tif,.tiff,.svg" />
              </div>
              <button type="submit" class="action-btn">Save Changes</button>
            </form>
          </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal" id="deleteModal" style="display:none;">
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

<!-- SCRIPTS -->
<<script>
document.getElementById("printAllBtn").addEventListener("click", () => {
  const rows = Array.from(document.querySelectorAll("#recordsTable tbody tr"));
  const total = rows.length;
  const rowsCount = 4; // number of rows per "row"
  const perRow = Math.ceil(total / rowsCount);

  const printWindow = window.open('', '', 'height=900,width=1000');
  printWindow.document.write('<html><head><title>Print Barcodes</title>');
  printWindow.document.write(`
    <style>
      body { font-family: Arial, sans-serif; margin: 5mm; display:flex;flex-direction:column;justify-content:flex-start; }
      .barcode-row { display:flex; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; }
      .barcode-box { border:1px solid #333; padding:10px; border-radius:4px; text-align:center; flex:1; margin:5px; max-width:calc((100% - 40px)/8); }
      svg { display:block; margin:0 auto; width:100%; height:auto; }
      @media print { body { margin:5mm } .barcode-box { page-break-inside: avoid; } }
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
        displayValue: true,
        fontSize: 14,
        textMargin: 2,
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
document.addEventListener("DOMContentLoaded", () => {
  // Render all existing barcodes in table
  try { JsBarcode(".barcode-svg").init(); } catch(e) { /* ignore */ }

  const recordsTable = document.querySelector("#recordsTable tbody");
  const generateBarcodeBtn = document.getElementById('generateBarcodeBtn');
  const barcodeModal = document.getElementById('barcodeModal');
  const closeButtons = document.querySelectorAll('.close-btn');
  let selectedRowBarcode = null;

  // Row selection specifically for barcode (click on row)
  recordsTable.addEventListener("click", (e) => {
    const row = e.target.closest("tr");
    if (!row) return;

    // store selected row for barcode generation
    if (selectedRowBarcode) selectedRowBarcode.style.outline = "";
    selectedRowBarcode = row;
    row.style.outline = "2px solid #4285F4";
  });

  // Generate barcode
  generateBarcodeBtn.addEventListener("click", () => {
    if (!selectedRowBarcode) return alert("Select a row to generate barcode.");

    const productId = selectedRowBarcode.dataset.id;
    const productName = selectedRowBarcode.dataset.product_name + "-" + selectedRowBarcode.dataset.quantity + "-" + selectedRowBarcode.dataset.price;

    // Generate barcode (bars only, no text)
    document.getElementById("barcode").innerHTML = "";
    JsBarcode("#barcode", productName, {
      format: "CODE128",
      displayValue: false,
      width: 2,
      height: 50,
      margin: 5
    });

    // Save barcode in DB via hidden form
    document.getElementById("barcodeProductId").value = productId;
    document.getElementById("barcodeValue").value = productName;
    document.getElementById("saveBarcodeForm").submit();

    // Show barcode modal
    barcodeModal.style.display = "block";
  });

  // Close barcode modal and other modals
  closeButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      const modalId = btn.dataset.close;
      if (modalId) document.getElementById(modalId).style.display = "none";
      else btn.closest(".modal").style.display = "none";
    });
  });

  // Generic outside click close for modal
  const modals = {
    add: document.getElementById("addModal"),
    edit: document.getElementById("editModal"),
    delete: document.getElementById("deleteModal"),
    barcode: document.getElementById("barcodeModal")
  };
  Object.values(modals).forEach(modal => {
    if (!modal) return;
    modal.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
    });
  });

  // Add modal open
  const addBtn = document.getElementById("addBtn");
  addBtn.addEventListener("click", () => {
    document.getElementById("addForm").reset();
    document.getElementById("addModal").style.display = "block";
  });

  // Edit / Delete buttons (delegated)
  recordsTable.addEventListener("click", (e) => {
    const row = e.target.closest("tr");
    if (!row) return;

    // EDIT BUTTON
    if (e.target.closest(".editBtn")) {
      const id = row.dataset.id;
      const name = row.dataset.product_name;
      const quantity = row.dataset.quantity;
      const price = row.dataset.price;
      const status = row.dataset.status;

      document.getElementById("editId").value = id;
      document.getElementById("editProductName").value = name;
      document.getElementById("editQuantity").value = quantity;
      document.getElementById("editPrice").value = price;
      document.getElementById("editStatus").value = status;

      document.getElementById("editModal").style.display = "block";
    }

    // DELETE BUTTON
    if (e.target.closest(".deleteBtn")) {
      const id = row.dataset.id;
      document.getElementById("deleteId").value = id;
      document.getElementById("deleteModal").style.display = "block";
    }
  });

});
</script>

<!-- search & voice -->
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
        const regex = new RegExp(`(${escapeRegExp(input)})`, "gi");
        cell.innerHTML = cell.textContent.replace(regex, `<span class="highlight">$1</span>`);
      }
    });

    // show/hide row
    row.style.display = (input === "" || matchFound) ? "" : "none";
  });
}

function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
      searchInput.value = transcript;
      recognition.stop();
      searchTable();
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

<script>
  // burger & profile dropdown
  const burger = document.getElementById('burger');
  const sideMenu = document.getElementById('side-menu');

  burger.addEventListener('click', () => {
    sideMenu.classList.toggle('active');
  });

  const profileImg = document.getElementById('profile-img');
  const dropdownMenu = document.getElementById('dropdown-menu');
  const dropdownArrow = document.getElementById('dropdown-arrow');

  profileImg.addEventListener('click', (e) => {
    if (!dropdownMenu.classList.contains('show')) {
      dropdownMenu.classList.add('show');
      dropdownArrow.textContent = '▲';
    }
  });

  dropdownArrow.addEventListener('click', (e) => {
    e.stopPropagation();
    if (dropdownMenu.classList.contains('show')) {
      dropdownMenu.classList.remove('show');
      dropdownArrow.textContent = '▼';
    } else {
      dropdownMenu.classList.add('show');
      dropdownArrow.textContent = '▲';
    }
  });
</script>

<script>
  // popup message handling when page loads
  document.addEventListener("DOMContentLoaded", function() {
    const message = <?php echo json_encode($popup_message); ?>;
    if (message) {
      const popup = document.getElementById('popupMessage');
      const popupText = document.getElementById('popupText');
      const closeBtn = document.getElementById('closePopupBtn');

      popupText.textContent = message;
      popup.style.display = "flex";

      closeBtn.addEventListener('click', () => {
        popup.style.display = 'none';
      });

      popup.addEventListener('click', (e) => {
        if (e.target === popup) popup.style.display = 'none';
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

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const scanBtn = document.getElementById("scanBarcodeBtn");
    const modal = document.getElementById("scannerModal");
    const closeBtn = document.getElementById("closeScanner");
    let html5QrCode;

    scanBtn.addEventListener("click", function () {
        modal.style.display = "flex";
        startScanner();
    });

    closeBtn.addEventListener("click", function () {
        stopScanner();
        modal.style.display = "none";
    });

    function startScanner() {
        const qrCodeRegionId = "reader";
        html5QrCode = new Html5Qrcode(qrCodeRegionId);

        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 350, height: 120 } },
            (decodedText) => {
                stopScanner();
                modal.style.display = "none";

                // ✅ Send scanned barcode to PHP for INSERT
                fetch("insert_product.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "barcode=" + encodeURIComponent(decodedText)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ Product inserted! ID: " + data.id);
                        location.reload(); // reload to show in table
                    } else {
                        alert("❌ Insert failed: " + data.message);
                    }
                })
                .catch(err => console.error(err));
            },
            (errorMessage) => { /* ignore scan errors */ }
        ).catch(err => {
            console.error("Unable to start scanning:", err);
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
            }).catch(err => console.error(err));
        }
    }
});
</script>
</body>
</html>
