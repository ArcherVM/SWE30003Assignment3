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
  <style>
    html { font-size: 18px; }
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
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
    nav a:hover { color: #5a2d82; }
    h2 {
      font-size: 2rem;
      color: #5a2d82;
      margin-bottom: 1.5rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    thead {
      background: #fafafa;
      border-bottom: 2px solid #ddd;
    }
    th, td {
      padding: 0.75rem 1rem;
      text-align: left;
    }
    thead th {
      font-size: 1.1rem;
      color: #444;
    }
    tbody tr:nth-child(even) {
      background: #f9f9f9;
    }
    td {
      font-size: 1rem;
      color: #333;
    }
    .no-data {
      font-size: 1.1rem;
      font-style: italic;
      color: #777;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Navigation Bar -->
    <nav>
      <a href="/index.php">Shop</a>
      <a href="/cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
      <a href="/admin.php">Admin</a>
      <a href="/stats.php">Stats</a>
      <a href="/admin.php?logout=1">Logout</a>
    </nav>

    <h2>Top-10 Best-Selling Products</h2>

    <?php if (mysqli_num_rows($result) === 0): ?>
      <p class="no-data">No sales yet.</p>
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
