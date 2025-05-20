<?php
session_start();
require_once __DIR__ . '/../db/setup_db.php';         // $link
require_once __DIR__ . '/../src/Repository/ProductRepository.php';

$repo        = new App\Repository\ProductRepository($link);
$ADMIN_USER  = 'admin';
$ADMIN_HASH  = 'secret123';   // plain-text password

/* ─── Handle login / logout ─── */
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

/* ─── If not logged in, show login form ─── */
if (empty($_SESSION['is_admin'])):
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head>
<body>
<div class="container">
<nav>
    <a href="admin.php">Admin</a> |
    <a href="index.php">Shop</a> |
    <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a> |
    <a href="stats.php">Stats</a> |
    <a href="admin.php?logout=1">Logout</a>
  </nav>

  <h2>Admin Login</h2>
  <?php if (isset($err)): ?>
    <p style="color:red"><?= htmlspecialchars($err) ?></p>
  <?php endif; ?>
  <form method="post">
    <label>Username
      <input type="text" name="user" required>
    </label>
    <label>Password
      <input type="password" name="pass" required>
    </label>
    <input class="button-primary" type="submit" name="login" value="Login">
  </form>
</div>
</body>
</html>
<?php
exit;
endif;

/* ─── Handle Create / Update / Delete actions ─── */
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($action === 'delete' && isset($_GET['id'])) {
    $repo->delete((int)$_GET['id']);
    header('Location: admin.php');
    exit;
}

$errors = [];
if (in_array($action, ['add','edit'], true)) {
    $id    = (int)($_POST['id'] ?? 0);
    $sku   = trim($_POST['sku'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['desc'] ?? '');
    $price = (int)($_POST['price'] ?? -1);
    $stock = (int)($_POST['stock'] ?? -1);

    if ($sku === '')                        $errors[] = 'SKU is required';
    if ($repo->skuExists($sku, $action==='edit'?$id:null))
                                            $errors[] = 'SKU must be unique';
    if ($name === '')                       $errors[] = 'Name is required';
    if ($price < 0)                         $errors[] = 'Price must be ≥ 0';
    if ($stock < 0)                         $errors[] = 'Stock must be ≥ 0';

    if (empty($errors)) {
        if ($action === 'add') {
            $repo->create($sku, $name, $desc, $price, $stock);
        } else {
            $repo->update($id, $sku, $name, $desc, $price, $stock);
        }
        header('Location: admin.php');
        exit;
    }
}

/* ─── Fetch all products for display ─── */
$products = $repo->all();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin – Products</title>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
  <style>form.inline { display: inline; }</style>
</head>
<body>
<div class="container">
  <nav>
    <a href="admin.php">Admin</a> |
    <a href="index.php">Shop</a> |
    <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a> |
    <a href="admin.php?logout=1">Logout</a>
  </nav>

  <h2>Product Maintenance</h2>

  <?php if (!empty($errors)): ?>
    <div style="color:red">
      <?php foreach ($errors as $e): ?>
        <p><?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Add / Edit Form -->
  <?php
  $editing = ($action === 'edit' && isset($_GET['id']))
           ? $repo->find((int)$_GET['id'])
           : null;
  ?>
  <h3><?= $editing ? 'Edit Product' : 'Add New Product' ?></h3>
  <form method="post">
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= $editing['product_id'] ?>">
    <?php endif; ?>
    <label>SKU
      <input type="text" name="sku" required
             value="<?= htmlspecialchars($editing['sku']   ?? '') ?>">
    </label>
    <label>Name
      <input type="text" name="name" required
             value="<?= htmlspecialchars($editing['name']  ?? '') ?>">
    </label>
    <label>Description
      <textarea name="desc"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
    </label>
    <label>Price (cents)
      <input type="number" name="price" min="0" required
             value="<?= htmlspecialchars($editing['price_cents'] ?? '') ?>">
    </label>
    <label>Stock Qty
      <input type="number" name="stock" min="0" required
             value="<?= htmlspecialchars($editing['stock_qty']   ?? '') ?>">
    </label>
    <input class="button-primary" type="submit"
           name="action" value="<?= $editing ? 'edit' : 'add' ?>">
  </form>

  <hr>

  <h3>Current Products</h3>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>SKU</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><?= $p['product_id'] ?></td>
        <td><?= htmlspecialchars($p['sku']) ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>$<?= number_format($p['price_cents']/100,2) ?></td>
        <td><?= $p['stock_qty'] ?></td>
        <td>
          <a href="?action=edit&id=<?= $p['product_id'] ?>">Edit</a> |
          <a href="?action=delete&id=<?= $p['product_id'] ?>"
             onclick="return confirm('Delete this product?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
