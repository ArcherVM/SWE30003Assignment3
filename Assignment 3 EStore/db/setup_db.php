<?php
/*************************************************************
 * MariaDB bootstrap via mysqli – creates tables (IF NOT EXISTS)
 * and seeds two demo products.
 *************************************************************/
$config = require __DIR__ . '/db_config.php';

$link = mysqli_connect(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['dbname']
) or die('❌ DB connect failed: ' . mysqli_connect_error());

mysqli_set_charset($link, 'utf8mb4');

/* ---- schema ---- */
$schemaSQL = <<<SQL
CREATE TABLE IF NOT EXISTS user (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) UNIQUE NOT NULL,           -- 191 chars fits 767-byte key
  password_hash CHAR(60) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  role ENUM('CUSTOMER','ADMIN') NOT NULL
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(64) UNIQUE NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price_cents INT NOT NULL,
  stock_qty INT NOT NULL,
  img_source VARCHAR(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart_item (
  cart_id CHAR(36),
  product_id INT,
  qty INT NOT NULL,
  PRIMARY KEY (cart_id, product_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_header (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  order_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_cents INT,
  payment_status ENUM('PENDING','PAID','FAILED') NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES user(user_id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_item (
  order_id INT,
  product_id INT,
  qty INT NOT NULL,
  unit_price_cents INT NOT NULL,
  PRIMARY KEY (order_id, product_id),
  FOREIGN KEY (order_id)  REFERENCES order_header(order_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice (
  invoice_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNIQUE,
  pdf_path VARCHAR(512),
  created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES order_header(order_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shipment (
  shipment_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNIQUE,
  courier VARCHAR(64),
  tracking_no VARCHAR(64),
  status ENUM('PENDING','DISPATCHED','IN_TRANSIT','DELIVERED'),
  FOREIGN KEY (order_id) REFERENCES order_header(order_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4;
SQL;

mysqli_multi_query($link, $schemaSQL);
while (mysqli_more_results($link) && mysqli_next_result($link)) { /* flush multi-query results */ }

/* ---- seed ---- */
$check = mysqli_query($link, "SELECT COUNT(*) AS c FROM product");
$row   = mysqli_fetch_assoc($check);
if ($row['c'] == 0) {
    mysqli_query(
        $link,
        "INSERT INTO product (sku,name,description,price_cents,stock_qty) VALUES
         ('TV42UHD','42\" 4K Smart TV','Demo item',59900,15),
         ('HDMI2M','2 m HDMI cable','Gold-plated',1500,100)"
    );
}

echo "✅ MariaDB schema ready in {$config['dbname']}\n";
