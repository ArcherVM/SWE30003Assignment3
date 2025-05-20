<?php
require_once __DIR__.'/../db/setup_db.php';
require_once __DIR__.'/../src/Repository/ProductRepository.php';

session_start();
$repo = new App\Repository\ProductRepository($pdo);

/* remove via ?del=ID */
if (isset($_GET['del'])) {
    unset($_SESSION['cart'][(int)$_GET['del']]);
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$lines = [];
$total = 0;

foreach ($cart as $pid => $qty) {
    if ($p = $repo->find($pid)) {
        $lineTotal = $p['price_cents'] * $qty;
        $lines[] = [$p['name'], $qty, $lineTotal];
        $total += $lineTotal;
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Cart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head><body><div class="container">
<h2>Your Cart</h2>
<?php if (!$lines): ?>
    <p>(empty)</p>
<?php else: ?>
<table>
  <thead><tr><th>Item</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($lines as $i => [$name,$qty,$sub]): ?>
    <tr>
      <td><?= htmlspecialchars($name) ?></td>
      <td><?= $qty ?></td>
      <td>$<?= number_format($sub/100,2) ?></td>
      <td><a href="?del=<?= $i ?>">x</a></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<p><strong>Total : $<?= number_format($total/100,2) ?></strong></p>
<a href="checkout.php">Proceed to Checkout</a>
<?php endif ?>
<br><br>
<a href="index.php">← Continue shopping</a>
</div></body></html>
