<?php
declare(strict_types=1);

class Sale
{
    public static function forClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT id, client_id, visit_id, items, total, note, created_at
               FROM retail_sales
              WHERE client_id = :cid
              ORDER BY created_at DESC'
        );
        $stmt->execute([':cid' => $clientId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['items'] = json_decode($r['items'], true) ?: [];
        }
        return $rows;
    }

    public static function forVisit(int $visitId): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, client_id, visit_id, items, total, note, created_at
               FROM retail_sales
              WHERE visit_id = :vid'
        );
        $stmt->execute([':vid' => $visitId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['items'] = json_decode($row['items'], true) ?: [];
        }
        return $row ?: null;
    }

    public static function deleteByVisit(int $visitId): void
    {
        $stmt = db()->prepare('DELETE FROM retail_sales WHERE visit_id = :vid');
        $stmt->execute([':vid' => $visitId]);
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, client_id, visit_id, items, total, note, created_at
               FROM retail_sales
              WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['items'] = json_decode($row['items'], true) ?: [];
        }
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO retail_sales (client_id, visit_id, items, total, note)
                  VALUES (:client_id, :visit_id, :items, :total, :note)'
        );
        $stmt->execute([
            ':client_id' => $data['client_id'] ?: null,
            ':visit_id'  => $data['visit_id'] ?? null,
            ':items'     => json_encode($data['items'] ?? [], JSON_UNESCAPED_UNICODE),
            ':total'     => $data['total'] ?? 0,
            ':note'      => $data['note'] ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE retail_sales
                SET items = :items, total = :total, note = :note
              WHERE id = :id'
        );
        $stmt->execute([
            ':id'    => $id,
            ':items' => json_encode($data['items'] ?? [], JSON_UNESCAPED_UNICODE),
            ':total' => $data['total'] ?? 0,
            ':note'  => $data['note'] ?? null,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM retail_sales WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
