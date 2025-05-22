<?php
session_start();
require_once __DIR__ . '/../db/setup_db.php';
require_once __DIR__ . '/../src/Repository/ProductRepository.php';

$repo = new App\Repository\ProductRepository($link);
$cart = $_SESSION['cart'] ?? [];

/* 1️⃣ Fake payment validation */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ok =
    !empty($_POST['cc']) && preg_match('/^\d{16}$/', $_POST['cc']) &&
    !empty($_POST['exp']) && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $_POST['exp']) &&
    !empty($_POST['cvv']) && preg_match('/^\d{3}$/', $_POST['cvv']) &&
    !empty($_POST['name']);

  if ($ok) {
    $_SESSION['cart'] = [];
    $msg = '✅ Payment accepted (simulated). Thank you!';
  } else {
    $msg = '❌ Please correct the highlighted fields.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
  foreach ($cart as $itemID => $qty) {
    echo ($itemID);
    echo (': ');
    echo ($qty);
    echo (' - ');

    $sql = "UPDATE product SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE product_id = ?";

    $stmt = $link->prepare($sql);
    $stmt->bind_param('ii', $qty, $itemID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
      echo "Stock updated successfully.";
    } else {
      echo "No rows affected or error occurred.";
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Checkout</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head>

<body>
  <div class="container">

    <!-- Navigation Bar -->
    <nav>
      <a href="/public/index.php">Shop</a> |
      <a href="/public/cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        | <a href="/public/admin.php">Admin</a>
        | <a href="/public/stats.php">Stats</a>
      <?php endif; ?>
    </nav>

    <h2>Checkout (Fake Payment)</h2>

    <?php if (isset($msg)): ?>
      <p><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="post">
      <label>Cardholder Name
        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </label>

      <label>Card Number (16 digits)
        <input type="text" name="cc" pattern="\d{16}" required value="<?= htmlspecialchars($_POST['cc'] ?? '') ?>">
      </label>

      <div class="row">
        <div class="column">
          <label>Expiry MM/YY
            <input type="text" name="exp" pattern="(0[1-9]|1[0-2])/\d{2}" required placeholder="07/27"
              value="<?= htmlspecialchars($_POST['exp'] ?? '') ?>">
          </label>
        </div>
        <div class="column">
          <label>CVV
            <input type="text" name="cvv" pattern="\d{3}" required value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>">
          </label>
        </div>
      </div>

      <input class="button-primary" type="submit" name="pay" value="Pay Now">
    </form>

    <p><a href="cart.php">← Back to Cart</a></p>
  </div>
</body>

</html>