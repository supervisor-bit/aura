<?php
declare(strict_types=1);

class ClientNote
{
    public static function forClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT * FROM client_notes WHERE client_id = ? ORDER BY created_at DESC');
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $stmt = db()->prepare('SELECT * FROM client_notes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(int $clientId, string $content): int
    {
        $stmt = db()->prepare('INSERT INTO client_notes (client_id, content) VALUES (?, ?)');
        $stmt->execute([$clientId, $content]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, string $content): void
    {
        $stmt = db()->prepare('UPDATE client_notes SET content = ? WHERE id = ?');
        $stmt->execute([$content, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM client_notes WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function countForClient(int $clientId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM client_notes WHERE client_id = ?');
        $stmt->execute([$clientId]);
        return (int)$stmt->fetchColumn();
    }
}
