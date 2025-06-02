<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/setup_db.php';
require_once __DIR__ . '/../src/Repository/ProductRepository.php';
require_once __DIR__ . '/../src/Repository/OrderRepository.php';

$repo = new App\Repository\ProductRepository($link);
$cart = $_SESSION['cart'] ?? [];

$msg = '';
$invoiceHTML = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
  // 1️⃣ Gather & validate inputs
  $email = trim($_POST['email'] ?? '');
  $ok =
    filter_var($email, FILTER_VALIDATE_EMAIL) &&
    !empty($_POST['cc']) && preg_match('/^\d{16}$/', $_POST['cc']) &&
    !empty($_POST['exp']) && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $_POST['exp']) &&
    !empty($_POST['cvv']) && preg_match('/^\d{3}$/', $_POST['cvv']) &&
    !empty($_POST['name']);

  if ($ok) {
    // 2️⃣ Persist order + items
    $orderRepo = new App\Repository\OrderRepository($link);
    $orderId = $orderRepo->createOrder($cart, /* customer_id */ null);

    // 3️⃣ Deduct stock
    $stmt = $link->prepare("
            UPDATE product
               SET stock_qty = GREATEST(stock_qty - ?, 0)
             WHERE product_id = ?
        ");
    foreach ($cart as $pid => $qty) {
      $stmt->bind_param('ii', $qty, $pid);
      $stmt->execute();
    }
    $stmt->close();

    // 4️⃣ Build invoice content (plain‐text for email + HTML for page)
    $stmt = $link->prepare("
            SELECT p.name, oi.qty, oi.unit_price_cents
              FROM order_item oi
              JOIN product p ON oi.product_id = p.product_id
             WHERE oi.order_id = ?
        ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->bind_result($prodName, $qty, $unitPriceCents);

    $lines = [];
    $totalCents = 0;
    $htmlLines = '';
    while ($stmt->fetch()) {
      $lineTotalCents = $unitPriceCents * $qty;
      $totalCents += $lineTotalCents;
      $plainLine = sprintf(
        "%s – Qty: %d @ \$%.2f = \$%.2f",
        $prodName,
        $qty,
        $unitPriceCents / 100,
        $lineTotalCents / 100
      );
      $lines[] = $plainLine;
      $htmlLines .= '<li>' . htmlspecialchars($plainLine) . '</li>';
    }
    $stmt->close();

    $subject = "Invoice #{$orderId} from YourStore";
    $message = "Thank you for your order #{$orderId}!\n\n"
      . "Order details:\n"
      . implode("\n", $lines) . "\n\n"
      . "Order Total: \$" . number_format($totalCents / 100, 2) . "\n\n"
      . "We appreciate your business.\n";
    $headers = "From: no-reply@yourstore.com\r\n"
      . "Reply-To: support@yourstore.com\r\n"
      . "MIME-Version: 1.0\r\n"
      . "Content-Type: text/plain; charset=UTF-8\r\n";

    // 5️⃣ Send email & set feedback message
    if (mail($email, $subject, $message, $headers)) {
      $msg = "✅ Payment accepted. Your order #{$orderId} has been placed and an invoice was emailed to {$email}.";
    } else {
      error_log("Invoice email for order #{$orderId} failed to send.");
      $msg = "✅ Payment accepted. Your order #{$orderId} has been placed, but we couldn't send the invoice email.";
    }

    // 6️⃣ Build HTML invoice for display
    $invoiceHTML = "
        <section class=\"invoice-section\">
          <h3>Invoice #{$orderId}</h3>
          <ul>
            {$htmlLines}
          </ul>
          <p><strong>Total: \$" . number_format($totalCents / 100, 2) . "</strong></p>
        </section>";

    // 7️⃣ Clear the cart
    $_SESSION['cart'] = [];
  } else {
    $msg = '❌ Please correct the highlighted fields (including a valid email).';
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Checkout</title>
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
      max-width: 800px;
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

    h2 {
      font-size: 2rem;
      color: #5a2d82;
      margin-bottom: 1.5rem;
    }

    form label {
      display: block;
      margin-bottom: 1rem;
      font-weight: bold;
    }

    form input[type="email"],
    form input[type="text"] {
      width: 100%;
      padding: 0.75rem;
      font-size: 1rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-top: 0.5rem;
    }

    .row {
      display: flex;
      gap: 1rem;
    }

    .column {
      flex: 1;
    }

    .button-primary {
      background: #5a2d82;
      color: #fff;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 1.5rem;
    }

    .button-primary:hover {
      background: #6e3a9e;
    }

    p.message {
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }

    .invoice-section {
      margin-top: 2.5rem;
      background: #fafafa;
      padding: 1.5rem;
      border-radius: 6px;
      border: 1px solid #eee;
    }

    .invoice-section h3 {
      margin-top: 0;
      font-size: 1.5rem;
      color: #333;
    }

    .invoice-section ul {
      padding-left: 1.25rem;
      margin: 1rem 0;
    }

    .invoice-section li {
      margin-bottom: 0.5rem;
      font-size: 1rem;
    }

    .invoice-section p strong {
      font-size: 1.2rem;
    }

    .continue-link {
      display: block;
      margin-top: 2rem;
      color: #555;
      text-decoration: none;
    }

    .continue-link:hover {
      color: #5a2d82;
    }
  </style>
</head>

<body>
  <div class="container">

    <!-- Navigation Bar -->
    <nav>
      <a href="/index.php">Shop</a>
      <a href="/cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="/admin.php">Admin</a>
        <?php if (basename($_SERVER['PHP_SELF']) === 'admin.php'): ?>
          <a href="/stats.php">Stats</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>

    <h2>Checkout (Fake Payment)</h2>

    <?php if ($msg): ?>
      <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="post">
      <label>Email Address
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </label>

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

      <button class="button-primary" type="submit" name="pay">Pay Now</button>
    </form>

    <?php
    // 8️⃣ Display the invoice below the form if generated
    if ($invoiceHTML) {
      echo $invoiceHTML;
    }
    ?>

    <a href="/cart.php" class="continue-link">← Back to Cart</a>
  </div>
</body>

</html>