<?php
session_start();
require_once __DIR__ . '/../db/setup_db.php';
require_once __DIR__ . '/../src/Repository/ProductRepository.php';

$repo = new App\Repository\ProductRepository($link);

/* ─── Add to cart via ?add=ID ─── */
if (isset($_GET['add'])) {
  $id = (int) $_GET['add'];
  $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
  header('Location: index.php');
  exit;
}

$items = $repo->all();
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Online E-Store</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
  <style>
    /* Base font sizing */
    html {
      font-size: 18px;
    }

    body {
      background: #f4f4f4;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      line-height: 1.6;
      color: #333;
    }

    .container {
      max-width: 1000px;
      margin: 3rem auto;
      background: #fff;
      padding: 3rem;
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
      text-decoration: none;
      color: #555;
      padding: 0.25rem 0.5rem;
    }

    nav a:hover {
      color: #5a2d82;
    }

    h2 {
      color: #5a2d82;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 2rem;
    }

    .product-card {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 1.5rem;
      background: #fafafa;
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .product-card h3 {
      margin: 0 0 1rem;
      font-size: 1.5rem;
      color: #333;
    }

    .product-card .price {
      font-weight: bold;
      margin-bottom: 1.5rem;
      font-size: 1.25rem;
      color: #222;
    }

    .product-card .button-outline {
      display: inline-block;
      padding: 0.75rem 1.25rem;
      border: 2px solid #5a2d82;
      border-radius: 6px;
      color: #5a2d82;
      font-size: 1rem;
      text-decoration: none;
      transition: background 0.2s, color 0.2s;
    }

    .product-card .button-outline:hover {
      background: #5a2d82;
      color: #fff;
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

    <h2>Products</h2>
    <div class="products-grid">
      <?php foreach ($items as $p): ?>
        <div class="product-card">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <img src="<?= htmlspecialchars($p['img_source']) ?>" alt="Product Image">
          <p class="price">$<?= number_format($p['price_cents'] / 100, 2) ?></p>
          <a href="?add=<?= $p['product_id'] ?>" class="button-outline">Add to Cart</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>

</html>