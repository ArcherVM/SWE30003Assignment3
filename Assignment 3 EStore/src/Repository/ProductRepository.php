<?php
namespace App\Repository;

class ProductRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo) { $this->pdo = $pdo; }

    /** @return array<int, array{product_id:int, name:string, price_cents:int}> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT product_id, name, price_cents FROM product ORDER BY name'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array{product_id:int, name:string, price_cents:int}|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT product_id, name, price_cents FROM product WHERE product_id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
