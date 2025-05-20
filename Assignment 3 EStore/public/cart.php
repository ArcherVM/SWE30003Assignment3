<?php
session_start();
require_once __DIR__ . '/../db/setup_db.php';         // sets $link
require_once __DIR__ . '/../src/Repository/ProductRepository.php';

$repo = new App\Repository\ProductRepository($link);  // pass mysqli handle

/* ─── Remove item via ?del=PID ─── */
if (isset($_GET['del'])) {
    unset($_SESSION['cart'][(int) $_GET['del']]);
    header('Location: cart.php');
    exit;
}

/* ─── Build cart lines & total ─── */
$cart  = $_SESSION['cart'] ?? [];
$lines = [];
$total = 0;

foreach ($cart as $pid => $qty) {
    if ($p = $repo->find($pid)) {
        $sub      = $p['price_cents'] * $qty;
        $lines[]  = [$pid, $p['name'], $qty, $sub];
        $total   += $sub;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Cart</title>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head>
<body>
<div class="container">

  <!-- Navigation Bar -->
  <nav>
    <a href="index.php">Shop</a> |
    <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
    <?php if (!empty($_SESSION['is_admin'])): ?>
      | <a href="admin.php">Admin</a>
      | <a href="stats.php">Stats</a>
    <?php endif; ?>
  </nav>

  <h2>Your Cart</h2>

  <?php if (empty($lines)): ?>
    <p>(empty)</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Item</th><th>Qty</th><th>Subtotal</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($lines as [$pid, $name, $qty, $sub]): ?>
        <tr>
          <td><?= htmlspecialchars($name) ?></td>
          <td><?= $qty ?></td>
          <td>$<?= number_format($sub / 100, 2) ?></td>
          <td><a href="?del=<?= $pid ?>">×</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p><strong>Total: $<?= number_format($total / 100, 2) ?></strong></p>
    <p><a class="button-primary" href="checkout.php">Proceed to Checkout</a></p>
  <?php endif; ?>

  <p><a href="index.php">← Continue shopping</a></p>
</div>
</body>
</html>
