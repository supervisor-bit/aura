<?php
declare(strict_types=1);

class Product
{
    public static function search(string $q, int $limit = 20, ?string $category = null, ?bool $isRetail = null): array
    {
        $where = 'is_active = 1 AND title LIKE :q';
        $params = [':q' => '%' . $q . '%'];
        if ($category) {
            $where .= ' AND category = :cat';
            $params[':cat'] = $category;
        }
        if ($isRetail !== null) {
            $where .= ' AND is_retail = :ret';
            $params[':ret'] = $isRetail ? '1' : '0';
        }
        $stmt = db()->prepare(
            "SELECT id, title, category, series, volume, default_price
               FROM price_list_items
              WHERE $where
              ORDER BY series ASC, title ASC
              LIMIT :lim"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = db()->query(
            'SELECT id, title, category, series, default_price, is_active
               FROM price_list_items
              ORDER BY series ASC, title ASC'
        );
        return $stmt->fetchAll();
    }

    public static function grouped(): array
    {
        $stmt = db()->query(
            'SELECT id, title, category, series, default_price, is_active
               FROM price_list_items
              WHERE is_retail = 0
              ORDER BY series ASC, title ASC'
        );
        $items = $stmt->fetchAll();
        $groups = [];
        foreach ($items as $item) {
            $s = $item['series'] ?? 'Ostatní';
            $groups[$s][] = $item;
        }
        return $groups;
    }

    public static function groupedRetail(): array
    {
        $stmt = db()->query(
            'SELECT id, title, category, series, volume, default_price, is_active
               FROM price_list_items
              WHERE is_retail = 1
              ORDER BY series ASC, title ASC'
        );
        $items = $stmt->fetchAll();
        $groups = [];
        foreach ($items as $item) {
            $s = $item['series'] ?? 'Ostatní';
            $groups[$s][] = $item;
        }
        return $groups;
    }

    public static function bySeries(string $series): array
    {
        $stmt = db()->prepare(
            'SELECT id, title, category, series, default_price, is_active
               FROM price_list_items
              WHERE series = :series
              ORDER BY title ASC'
        );
        $stmt->execute([':series' => $series]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO price_list_items (title, category, series, volume, default_price, is_retail)
                  VALUES (:title, :category, :series, :volume, :default_price, :is_retail)'
        );
        $stmt->execute([
            ':title'         => $data['title'],
            ':category'      => $data['category']      ?? null,
            ':series'        => $data['series']         ?? null,
            ':volume'        => $data['volume']         ?? null,
            ':default_price' => $data['default_price'] ?? null,
            ':is_retail'     => (int)($data['is_retail'] ?? 0),
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = 'title = :title, category = :cat, series = :series';
        $params = [
            ':id'     => $id,
            ':title'  => $data['title'],
            ':cat'    => $data['category'] ?? null,
            ':series' => $data['series']   ?? null,
        ];
        if (array_key_exists('volume', $data)) {
            $fields .= ', volume = :vol';
            $params[':vol'] = $data['volume'];
        }
        if (array_key_exists('default_price', $data)) {
            $fields .= ', default_price = :price';
            $params[':price'] = $data['default_price'];
        }
        $stmt = db()->prepare("UPDATE price_list_items SET $fields WHERE id = :id");
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM price_list_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
