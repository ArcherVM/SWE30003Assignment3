<?php
session_start();

/* very lightweight “field check then success” */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = $_POST['cc']      && preg_match('/^\d{16}$/', $_POST['cc']) &&
          $_POST['exp']     && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $_POST['exp']) &&
          $_POST['cvv']     && preg_match('/^\d{3}$/',  $_POST['cvv']) &&
          $_POST['name'];
    if ($ok) {
        $_SESSION['cart'] = [];  // clear cart
        $msg = '✅ Payment accepted (simulated). Thank you!';
    } else {
        $msg = '❌ Please correct the highlighted fields.';
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Checkout</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
</head><body><div class="container">
<h2>Fake Payment</h2>
<?php if (isset($msg)) echo "<p>$msg</p>"; ?>

<form method="post">
  <label>Cardholder Name
    <input type="text" name="name" required value="<?= $_POST['name'] ?? '' ?>">
  </label>
  <label>Card Number (16 digits)
    <input type="text" name="cc" pattern="\d{16}" required value="<?= $_POST['cc'] ?? '' ?>">
  </label>
  <div class="row">
    <div class="column">
      <label>Expiry MM/YY
        <input type="text" name="exp" pattern="(0[1-9]|1[0-2])/\d{2}" required placeholder="07/27" value="<?= $_POST['exp'] ?? '' ?>">
      </label>
    </div>
    <div class="column">
      <label>CVV
        <input type="text" name="cvv" pattern="\d{3}" required value="<?= $_POST['cvv'] ?? '' ?>">
      </label>
    </div>
  </div>
  <input class="button-primary" type="submit" value="Pay Now">
</form>

<p><a href="cart.php">← Back to cart</a></p>
</div></body></html>
