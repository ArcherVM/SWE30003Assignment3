<?php
require_once __DIR__.'/../db/setup_db.php';
require_once __DIR__.'/../src/Repository/ProductRepository.php';

session_start();
$repo = new App\Repository\ProductRepository($pdo);

/* add-to-cart via ?add=ID */
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    header('Location: index.php');   // PRG pattern
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
</head>
<body>
<div class="container">
    <h2>Products</h2>
    <table>
        <thead><tr><th>Name</th><th>Price</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td>$<?= number_format($p['price_cents']/100, 2) ?></td>
              <td><a href="?add=<?= $p['product_id'] ?>">Add to Cart</a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <p>
        <a href="cart.php">View Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
    </p>
</div>
</body>
</html>
