<?php
namespace App\Repository;

/**
 * Simple mysqli-based repository for the `product` table.
 * All public methods throw mysqli_sql_exception on DB errors.
 */
class ProductRepository
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /** @return array<int, array{product_id:int,sku:string,name:string,description:?string,price_cents:int,stock_qty:int,img_source:string}> */
    public function all(): array
    {
        $sql = 'SELECT product_id, sku, name, description, price_cents, stock_qty, img_source
                  FROM product
              ORDER BY name';

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /** @return array{product_id:int,sku:string,name:string,description:?string,price_cents:int,stock_qty:int}|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT product_id, sku, name, description, price_cents, stock_qty
               FROM product
              WHERE product_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Insert a new product. Returns TRUE on success. */
    public function create(string $sku, string $name, string $desc,
                           int $priceCents, int $stockQty, string $imgSource): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product (sku, name, description, price_cents, stock_qty, img_source)
                       VALUES (?,?,?,?,?,?)'
        );
        $stmt->bind_param('sssiis', $sku, $name, $desc, $priceCents, $stockQty, $imgSource);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Update an existing record. Returns TRUE if at least one row changed. */
    public function update(int $id, string $sku, string $name, string $desc,
                           int $priceCents, int $stockQty, string $imgSource): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE product
                SET sku=?, name=?, description=?, price_cents=?, stock_qty=?, img_source=?
              WHERE product_id=?'
        );
        $stmt->bind_param('sssiiis', $sku, $name, $desc, $priceCents, $stockQty, $id, $imgSource);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    /** Hard-delete by id. Returns TRUE on success. */
    public function delete(int $id): bool
    {
        return $this->db->query(
            'DELETE FROM product WHERE product_id = ' . (int) $id
        );
    }

    /** Check for *unique* SKU (optionally excluding a given product id). */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM product WHERE sku = ?';
        if ($excludeId !== null) $sql .= ' AND product_id <> ' . (int) $excludeId;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $stmt->store_result();
        $found = $stmt->num_rows === 1;
        $stmt->close();
        return $found;
    }

    /** Check ID existence without fetching the row. */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM product WHERE product_id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->store_result();
        $found = $stmt->num_rows === 1;
        $stmt->close();
        return $found;
    }

    /** Optional helper to close the underlying mysqli link if desired. */
    public function close(): void
    {
        $this->db->close();
    }
}
