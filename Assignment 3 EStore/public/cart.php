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
  <style>
    html { font-size: 18px; }
    body {
      background: #f4f4f4;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #333;
      line-height: 1.6;
    }
    .container {
      max-width: 900px;
      margin: 3rem auto;
      background: #fff;
      padding: 2.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    nav {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 2rem;
      font-size: 1.1rem;
    }
    nav a {
      text-decoration: none;
      color: #555;
      padding: 0.25rem 0.5rem;
    }
    nav a:hover {
      color: #5a2d82;
    }
    h2 {
      font-size: 2rem;
      color: #5a2d82;
      margin-bottom: 1.5rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
    }
    th, td {
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
    th {
      font-size: 1.1rem;
      color: #444;
    }
    td {
      font-size: 1rem;
      color: #333;
    }
    .remove-link {
      color: #c0392b;
      text-decoration: none;
      font-weight: bold;
      font-size: 1.2rem;
    }
    .remove-link:hover {
      color: #e74c3c;
    }
    .total {
      font-size: 1.3rem;
      margin-bottom: 1.5rem;
    }
    .button-primary {
      background: #5a2d82;
      color: #fff;
      padding: 0.75rem 1.5rem;
      border-radius: 6px;
      text-decoration: none;
      font-size: 1rem;
    }
    .button-primary:hover {
      background: #6e3a9e;
    }
    .empty {
      font-size: 1.1rem;
      font-style: italic;
      color: #777;
      margin-bottom: 1.5rem;
    }
    .continue {
      font-size: 0.95rem;
      color: #555;
      text-decoration: none;
    }
    .continue:hover {
      color: #5a2d82;
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- Navigation Bar -->
    <nav>
      <a href="index.php">Shop</a>
      <a href="cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
      <a href="admin.php">Admin</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="stats.php">Stats</a>
      <?php endif; ?>
    </nav>

    <h2>Your Cart</h2>

    <?php if (empty($lines)): ?>
      <p class="empty">(Your cart is empty.)</p>
      <p><a class="continue" href="index.php">← Continue Shopping</a></p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lines as [$pid, $name, $qty, $sub]): ?>
          <tr>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= $qty ?></td>
            <td>$<?= number_format($sub / 100, 2) ?></td>
            <td><a class="remove-link" href="?del=<?= $pid ?>">&times;</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p class="total"><strong>Total: $<?= number_format($total / 100, 2) ?></strong></p>
      <p><a class="button-primary" href="checkout.php">Proceed to Checkout</a></p>
      <p><a class="continue" href="index.php">← Continue Shopping</a></p>
    <?php endif; ?>

  </div>
</body>
</html>
