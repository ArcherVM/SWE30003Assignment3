<?php
session_start();
require_once __DIR__ . '/../db/setup_db.php';  // sets $link

// ─── Access control: only admin ─────────────────────────────────────────
if (empty($_SESSION['is_admin'])) {
    header('Location: /admin.php');
    exit;
}

// ─── Aggregate sales stats ──────────────────────────────────────────────
$sql = <<<SQL
SELECT
  p.product_id,
  p.sku,
  p.name,
  SUM(oi.qty) AS units_sold,
  SUM(oi.qty * oi.unit_price_cents) AS revenue_cents
FROM order_item AS oi
JOIN product    AS p ON p.product_id = oi.product_id
GROUP BY p.product_id, p.sku, p.name
ORDER BY units_sold DESC
LIMIT 10
SQL;

$result = mysqli_query($link, $sql);
if (!$result) {
    die('Error retrieving statistics: ' . mysqli_error($link));
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales Statistics</title>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head>
<body>
  <div class="container">
    <!-- Navigation Bar -->
    <nav>
      <a href="/index.php">Shop</a> |
      <a href="/cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a> |
      <a href="/admin.php">Admin</a> |
      <a href="/stats.php">Stats</a> |
      <a href="/admin.php?logout=1">Logout</a>
    </nav>

    <h2>Top-10 Best-Selling Products</h2>

    <?php if (mysqli_num_rows($result) === 0): ?>
      <p>No sales yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Units Sold</th>
            <th>Revenue</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['sku']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= (int)$row['units_sold'] ?></td>
              <td>$<?= number_format($row['revenue_cents']/100, 2) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</body>
</html>
