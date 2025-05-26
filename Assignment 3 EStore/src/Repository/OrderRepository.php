<?php
namespace App\Repository;

/**
 * Simple mysqli-based repository for orders.
 * All public methods throw mysqli_sql_exception on DB errors.
 */
class OrderRepository
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Persist an order and its line-items.
     *
     * @param array<int,int> $cart      // product_id => quantity
     * @param int|null       $customerId
     * @return int                      // newly created order_id
     */
    public function createOrder(array $cart, ?int $customerId = null): int
    {
        // 1) Compute total
        $totalCents = 0;
        $prices     = [];

        $stmt = $this->db->prepare(
            'SELECT product_id, price_cents FROM product WHERE product_id = ?'
        );
        foreach ($cart as $pid => $qty) {
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $stmt->bind_result($fetchedPid, $priceCents);
            if ($stmt->fetch()) {
                $prices[$pid] = $priceCents;
                $totalCents  += $priceCents * $qty;
            }
        }
        $stmt->close();

        // 2) Insert header
        $status = 'PAID'; // fake payment
        $stmt   = $this->db->prepare(
            'INSERT INTO order_header (customer_id, total_cents, payment_status)
             VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $customerId, $totalCents, $status);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        // 3) Insert items
        $stmt = $this->db->prepare(
            'INSERT INTO order_item (order_id, product_id, qty, unit_price_cents)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($cart as $pid => $qty) {
            $unitPrice = $prices[$pid] ?? 0;
            $stmt->bind_param('iiii', $orderId, $pid, $qty, $unitPrice);
            $stmt->execute();
        }
        $stmt->close();

        return $orderId;
    }
}
