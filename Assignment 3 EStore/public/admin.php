<?php
// ─── Debug / Error Reporting ─────────────────────────────────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../db/setup_db.php';         // $link
require_once __DIR__ . '/../src/Repository/ProductRepository.php';

$repo = new App\Repository\ProductRepository($link);
$ADMIN_USER = 'admin';
$ADMIN_HASH = 'secret123';   // plain-text password

// ─── Handle login / logout ───────────────────────────────────────────────
if (isset($_POST['login'])) {
  if ($_POST['user'] === $ADMIN_USER && $_POST['pass'] === $ADMIN_HASH) {
    $_SESSION['is_admin'] = true;
    header('Location: admin.php');
    exit;
  } else {
    $err = 'Invalid credentials';
  }
}
if (isset($_GET['logout'])) {
  unset($_SESSION['is_admin']);
  header('Location: admin.php');
  exit;
}

// ─── If not logged in, show login form ───────────────────────────────────
if (empty($_SESSION['is_admin'])):
  ?>
  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
    <style>
      html {
        font-size: 18px;
      }

      body {
        background: #f4f4f4;
        font-family: 'Helvetica Neue', Arial, sans-serif;
        color: #333;
        line-height: 1.6;
      }

      .container {
        max-width: 500px;
        margin: 4rem auto;
        background: #fff;
        padding: 2.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }

      nav {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 1.1rem;
      }

      nav a {
        margin: 0 1rem;
        color: #555;
        text-decoration: none;
      }

      nav a:hover {
        color: #5a2d82;
      }

      h2 {
        font-size: 2rem;
        color: #5a2d82;
        margin-bottom: 1.5rem;
        text-align: center;
      }

      form label {
        display: block;
        margin-bottom: 1rem;
        font-weight: bold;
      }

      form input[type="text"],
      form input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-top: 0.5rem;
      }

      .button-primary {
        background: #5a2d82;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 1rem;
        width: 100%;
      }

      .button-primary:hover {
        background: #6e3a9e;
      }

      .error {
        color: #c0392b;
        margin-bottom: 1rem;
        font-weight: bold;
        text-align: center;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <nav>
        <a href="index.php">Shop</a>
        <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
        <a href="admin.php">Admin</a>
        <a href="admin.php?logout=1">Logout</a>
      </nav>

      <h2>Admin Login</h2>
      <?php if (isset($err)): ?>
        <p class="error"><?= htmlspecialchars($err) ?></p>
      <?php endif; ?>
      <form method="post">
        <label>Username
          <input type="text" name="user" required>
        </label>
        <label>Password
          <input type="password" name="pass" required>
        </label>
        <button class="button-primary" type="submit" name="login">Login</button>
      </form>
    </div>
  </body>

  </html>
  <?php
  exit;
endif;

// ─── Handle Create / Update / Delete actions ─────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($action === 'delete' && isset($_GET['id'])) {
  $repo->delete((int) $_GET['id']);
  header('Location: admin.php');
  exit;
}

$errors = [];
if (in_array($action, ['add', 'edit'], true)) {
  $id = (int) ($_POST['id'] ?? 0);
  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['desc'] ?? '');
  $price = (int) ($_POST['price'] ?? -1);
  $stock = (int) ($_POST['stock'] ?? -1);

  if ($sku === '')
    $errors[] = 'SKU is required';
  if ($repo->skuExists($sku, $action === 'edit' ? $id : null))
    $errors[] = 'SKU must be unique';
  if ($name === '')
    $errors[] = 'Name is required';
  if ($price < 0)
    $errors[] = 'Price must be ≥ 0';
  if ($stock < 0)
    $errors[] = 'Stock must be ≥ 0';

  if (isset($_FILES['productImg']) && $_FILES['productImg']['error'] === 0) {
    $fileTmpPath = $_FILES['productImg']['tmp_name'];
    $fileName = $_FILES['productImg']['name'];
    $fileSize = $_FILES['productImg']['size'];
    $fileType = $_FILES['productImg']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Allowed file extensions
    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExtension, $allowedfileExtensions)) {

      $uploadFileDir = './product_images/';
      if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
      }
      $dest_path = $uploadFileDir . $fileName;

      if (move_uploaded_file($fileTmpPath, $dest_path)) {
        echo "<p>File is successfully uploaded.</p>";
        echo "<img src='" . htmlspecialchars($dest_path) . "' alt='Uploaded Image' style='max-width:300px;'>";
      } else {
        echo "<p>There was an error moving the uploaded file.</p>";
      }
    } else {
      echo "<p>Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions) . "</p>";
    }
  } else {
    echo "<p>No file uploaded or there was an upload error.</p>";
  }

  if (empty($errors)) {
    if ($action === 'add') {
      $repo->create($sku, $name, $desc, $price, $stock, $dest_path);
    } else {
      $repo->update($id, $sku, $name, $desc, $price, $stock, $dest_path);
    }
    //header('Location: admin.php');
    //exit;
  }
}

$products = $repo->all();
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Admin – Products</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
  <style>
    html {
      font-size: 18px;
    }

    body {
      background: #f4f4f4;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #333;
      line-height: 1.6;
    }

    .container {
      max-width: 1000px;
      margin: 3rem auto;
      background: #fff;
      padding: 2.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    nav {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 2rem;
      font-size: 1.1rem;
    }

    nav a {
      color: #555;
      text-decoration: none;
      padding: 0.25rem 0.5rem;
    }

    nav a:hover {
      color: #5a2d82;
    }

    h2,
    h3 {
      color: #5a2d82;
    }

    h2 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    h3 {
      font-size: 1.5rem;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .error-list p {
      color: #c0392b;
      margin: 0.25rem 0;
    }

    form label {
      display: block;
      margin-bottom: 1rem;
      font-weight: bold;
    }

    form input[type="text"],
    form input[type="number"],
    form textarea {
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-top: 0.5rem;
    }

    .button-primary {
      background: #5a2d82;
      color: #fff;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 1rem;
    }

    .button-primary:hover {
      background: #6e3a9e;
    }

    hr {
      margin: 2rem 0;
      border: none;
      border-top: 1px solid #eee;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    th,
    td {
      padding: 0.75rem 1rem;
      text-align: left;
    }

    thead {
      background: #fafafa;
      border-bottom: 2px solid #ddd;
    }

    tbody tr:nth-child(even) {
      background: #f9f9f9;
    }

    a.action-link {
      color: #5a2d82;
      text-decoration: none;
      margin-right: 0.5rem;
    }

    a.action-link:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container">
    <nav>
      <a href="admin.php">Admin</a>
      <a href="index.php">Shop</a>
      <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
      <a href="stats.php">Stats</a>
      <a href="admin.php?logout=1">Logout</a>
    </nav>

    <h2>Product Maintenance</h2>

    <?php if (!empty($errors)): ?>
      <div class="error-list">
        <?php foreach ($errors as $e): ?>
          <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
    $editing = ($action === 'edit' && isset($_GET['id']))
      ? $repo->find((int) $_GET['id'])
      : null;
    ?>
    <h3><?= $editing ? 'Edit Product' : 'Add New Product' ?></h3>
    <form method="post" enctype="multipart/form-data">
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= $editing['product_id'] ?>">
      <?php endif; ?>
      <label>SKU
        <input type="text" name="sku" required value="<?= htmlspecialchars($editing['sku'] ?? '') ?>">
      </label>
      <label>Name
        <input type="text" name="name" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
      </label>
      <label>Description
        <textarea name="desc" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
      </label>
      <label>Price (cents)
        <input type="number" name="price" min="0" required
          value="<?= htmlspecialchars($editing['price_cents'] ?? '') ?>">
      </label>
      <label>Stock Qty
        <input type="number" name="stock" min="0" required value="<?= htmlspecialchars($editing['stock_qty'] ?? '') ?>">
      </label>

      <label>Product Image
        <input type="file" name="productImg" accept="image/*" required>
      </label>
      <button class="button-primary" type="submit" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
        <?= $editing ? 'Save Changes' : 'Add Product' ?>
      </button>
    </form>

    <hr>

    <h3>Current Products</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>SKU</th>
          <th>Name</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= $p['product_id'] ?></td>
            <td><?= htmlspecialchars($p['sku']) ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>$<?= number_format($p['price_cents'] / 100, 2) ?></td>
            <td><?= $p['stock_qty'] ?></td>
            <td>
              <a class="action-link" href="?action=edit&id=<?= $p['product_id'] ?>">Edit</a>
              <a class="action-link" href="?action=delete&id=<?= $p['product_id'] ?>"
                onclick="return confirm('Delete this product?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>