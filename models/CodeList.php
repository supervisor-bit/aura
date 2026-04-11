<?php
declare(strict_types=1);

class CodeList
{
    public static function byType(string $type): array
    {
        $stmt = db()->prepare(
            'SELECT id, type, name, icon, sort_order
               FROM code_lists
              WHERE type = :type
              ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = db()->query(
            'SELECT id, type, name, icon, sort_order
               FROM code_lists
              ORDER BY type ASC, sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $stmt = db()->prepare('SELECT * FROM code_lists WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $maxStmt = db()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM code_lists WHERE type = :type'
        );
        $maxStmt->execute([':type' => $data['type']]);
        $nextOrder = (int) $maxStmt->fetchColumn();

        $stmt = db()->prepare(
            'INSERT INTO code_lists (type, name, icon, sort_order)
             VALUES (:type, :name, :icon, :sort_order)'
        );
        $stmt->execute([
            ':type'       => $data['type'],
            ':name'       => $data['name'],
            ':icon'       => $data['icon'] ?? null,
            ':sort_order' => $nextOrder,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE code_lists SET name = :name, icon = :icon, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute([
            ':id'         => $id,
            ':name'       => $data['name'],
            ':icon'       => $data['icon'] ?? null,
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public static function reorder(array $ids): void
    {
        $db = db();
        $stmt = $db->prepare('UPDATE code_lists SET sort_order = :pos WHERE id = :id');
        foreach ($ids as $pos => $id) {
            $stmt->execute([':pos' => $pos, ':id' => (int) $id]);
        }
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM code_lists WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
