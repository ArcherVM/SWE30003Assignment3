<?php
/*********************************************************************
 * SQLite bootstrap – creates estore.db with schema + seed rows
 *********************************************************************/

// 1️⃣  open (or create) the file-based DB
$dbPath = __DIR__ . '/estore.db';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 2️⃣  turn on FK constraints for SQLite
$pdo->exec('PRAGMA foreign_keys = ON');

// 3️⃣  schema (idempotent)
$schema = [

"CREATE TABLE IF NOT EXISTS user (
  user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
  email         TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  full_name     TEXT NOT NULL,
  role          TEXT CHECK(role IN ('CUSTOMER','ADMIN')) NOT NULL
)",

"CREATE TABLE IF NOT EXISTS product (
  product_id    INTEGER PRIMARY KEY AUTOINCREMENT,
  sku           TEXT UNIQUE NOT NULL,
  name          TEXT NOT NULL,
  description   TEXT,
  price_cents   INTEGER NOT NULL,
  stock_qty     INTEGER NOT NULL
)",

"CREATE TABLE IF NOT EXISTS cart_item (
  cart_id       TEXT,
  product_id    INTEGER,
  qty           INTEGER NOT NULL,
  PRIMARY KEY (cart_id, product_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
)",

"CREATE TABLE IF NOT EXISTS order_header (
  order_id      INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id   INTEGER,
  order_ts      TEXT DEFAULT (datetime('now')),
  total_cents   INTEGER,
  payment_status TEXT CHECK(payment_status IN ('PENDING','PAID','FAILED')) NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES user(user_id)
)",

"CREATE TABLE IF NOT EXISTS order_item (
  order_id      INTEGER,
  product_id    INTEGER,
  qty           INTEGER NOT NULL,
  unit_price_cents INTEGER NOT NULL,
  PRIMARY KEY (order_id, product_id),
  FOREIGN KEY (order_id)  REFERENCES order_header(order_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
)",

"CREATE TABLE IF NOT EXISTS invoice (
  invoice_id    INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id      INTEGER UNIQUE,
  pdf_path      TEXT,
  created_ts    TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (order_id) REFERENCES order_header(order_id)
)",

"CREATE TABLE IF NOT EXISTS shipment (
  shipment_id   INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id      INTEGER UNIQUE,
  courier       TEXT,
  tracking_no   TEXT,
  status        TEXT CHECK(status IN ('PENDING','DISPATCHED','IN_TRANSIT','DELIVERED')),
  FOREIGN KEY (order_id) REFERENCES order_header(order_id)
)"
];

foreach ($schema as $sql) $pdo->exec($sql);

// 4️⃣  seed demo products once
if ($pdo->query("SELECT COUNT(*) FROM product")->fetchColumn() == 0) {
    $pdo->exec("
       INSERT INTO product (sku,name,description,price_cents,stock_qty) VALUES
       ('TV42UHD','42\" 4K Smart TV','Demo item',59900,15),
       ('HDMI2M','2 m HDMI cable','Gold-plated',1500,100)
    ");
}

echo "✅  SQLite database ready at $dbPath\n";
