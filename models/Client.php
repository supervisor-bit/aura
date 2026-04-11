<?php
declare(strict_types=1);

class Client
{
    // ── Čtení ─────────────────────────────────────────────────────────────────

    public static function all(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : "WHERE c.status != 'inactive'";
        $stmt = db()->query(
            "SELECT c.id, c.full_name, c.phone, c.status, c.created_at,
                    (SELECT cv.service_name FROM client_visits cv
                      WHERE cv.client_id = c.id
                      ORDER BY cv.visit_date DESC, cv.id DESC LIMIT 1) AS last_service,
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT(\"id\", t.id, \"name\", t.name, \"color\", t.color))
                       FROM client_tags ct
                       JOIN tags t ON t.id = ct.tag_id
                      WHERE ct.client_id = c.id) AS tags_json
               FROM clients c
              {$where}
              ORDER BY c.full_name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function search(string $q, bool $includeInactive = false): array
    {
        // Strip diacritics for search comparison
        $translitMap = [
            'á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o',
            'ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z',
            'Á'=>'A','Č'=>'C','Ď'=>'D','É'=>'E','Ě'=>'E','Í'=>'I','Ň'=>'N','Ó'=>'O',
            'Ř'=>'R','Š'=>'S','Ť'=>'T','Ú'=>'U','Ů'=>'U','Ý'=>'Y','Ž'=>'Z',
        ];
        $qNorm = strtr($q, $translitMap);
        $like = '%' . $qNorm . '%';

        // Build REPLACE chain for DB comparison (strip diacritics in SQL)
        $replaces = '';
        foreach ($translitMap as $from => $to) {
            $replaces = $replaces === ''
                ? "REPLACE(CONCAT(c.full_name, ' ', COALESCE(c.phone, '')), '{$from}', '{$to}')"
                : "REPLACE({$replaces}, '{$from}', '{$to}')";
        }

        $statusFilter = $includeInactive ? '' : "AND c.status != 'inactive'";

        $stmt = db()->prepare(
            "SELECT c.id, c.full_name, c.phone, c.status,
                    (SELECT cv.service_name FROM client_visits cv
                      WHERE cv.client_id = c.id
                      ORDER BY cv.visit_date DESC, cv.id DESC LIMIT 1) AS last_service,
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', t.id, 'name', t.name, 'color', t.color))
                       FROM client_tags ct
                       JOIN tags t ON t.id = ct.tag_id
                      WHERE ct.client_id = c.id) AS tags_json
               FROM clients c
              WHERE {$replaces} LIKE :q
              {$statusFilter}
              ORDER BY c.full_name ASC
              LIMIT 100"
        );
        $stmt->execute([':q' => $like]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $stmt = db()->prepare(
            'SELECT * FROM clients WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── Zápis ─────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO clients (full_name, phone, status, notes)
                  VALUES (:full_name, :phone, :status, :notes)'
        );
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone'     => $data['phone']  ?? null,
            ':status'    => $data['status'] ?? 'active',
            ':notes'     => $data['notes']  ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = db()->prepare(
            'UPDATE clients
                SET full_name = :full_name,
                    phone     = :phone,
                    status    = :status,
                    notes     = :notes
              WHERE id = :id'
        );
        return $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone'     => $data['phone']  ?? null,
            ':status'    => $data['status'] ?? 'active',
            ':notes'     => $data['notes']  ?? null,
            ':id'        => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM clients WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ── Statistiky ────────────────────────────────────────────────────────────

    public static function visitCount(int $id): int
    {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM client_visits WHERE client_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public static function lastVisitDate(int $id): ?string
    {
        $stmt = db()->prepare(
            'SELECT visit_date FROM client_visits
              WHERE client_id = :id
              ORDER BY visit_date DESC LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetchColumn();
        return $row ?: null;
    }

    public static function totalSpent(int $id): float
    {
        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(price), 0) FROM client_visits WHERE client_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return (float) $stmt->fetchColumn();
    }

    public static function totalRetailSpent(int $id): float
    {
        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM retail_sales WHERE client_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return (float) $stmt->fetchColumn();
    }

    public static function formulaSummary(int $id): ?string
    {
        $stmt = db()->prepare(
            'SELECT color_formula FROM client_visits
              WHERE client_id = :id AND color_formula IS NOT NULL
              ORDER BY visit_date DESC, id DESC LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $formula = $stmt->fetchColumn();
        if (!$formula) return null;
        $data = json_decode($formula, true);
        if (!$data || empty($data['bowls'])) return null;
        $parts = [];
        foreach ($data['bowls'] as $bowl) {
            foreach ($bowl['products'] ?? [] as $prod) {
                $name = $prod['name'] ?? '';
                if (preg_match('/(\d[\d.\-\/]+)/', $name, $m)) {
                    $parts[] = $m[1];
                }
            }
        }
        return $parts ? implode(' + ', $parts) : null;
    }
}
