<?php
declare(strict_types=1);

class Tag
{
    public static function all(): array
    {
        return db()->query('SELECT * FROM tags ORDER BY sort_order, id')->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $stmt = db()->prepare('SELECT * FROM tags WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $maxSort = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM tags')->fetchColumn();
        $stmt = db()->prepare('INSERT INTO tags (name, color, sort_order) VALUES (?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $data['color'] ?? '#a78bfa',
            $maxSort + 1,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare('UPDATE tags SET name = ?, color = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['color'] ?? '#a78bfa', $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM tags WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function reorder(array $ids): void
    {
        $stmt = db()->prepare('UPDATE tags SET sort_order = ? WHERE id = ?');
        foreach ($ids as $i => $id) {
            $stmt->execute([$i + 1, $id]);
        }
    }

    /** Get tag IDs for a client */
    public static function forClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT t.* FROM tags t
               JOIN client_tags ct ON ct.tag_id = t.id
              WHERE ct.client_id = ?
              ORDER BY t.sort_order, t.id'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    /** Sync tags for a client */
    public static function syncClient(int $clientId, array $tagIds): void
    {
        $pdo = db();
        $pdo->prepare('DELETE FROM client_tags WHERE client_id = ?')->execute([$clientId]);
        if (!empty($tagIds)) {
            $stmt = $pdo->prepare('INSERT INTO client_tags (client_id, tag_id) VALUES (?, ?)');
            foreach ($tagIds as $tagId) {
                $stmt->execute([$clientId, (int)$tagId]);
            }
        }
    }
}
