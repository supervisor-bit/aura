<?php
declare(strict_types=1);

class ProductController
{
    /** GET /products/search?q=...&cat=...&retail=1 — autocomplete */
    public function search(?int $id, array $body): void
    {
        $q = trim(input('q', 'get'));
        $cat = trim(input('cat', 'get')) ?: null;
        $retail = input('retail', 'get');
        $isRetail = $retail !== '' ? (bool)(int)$retail : null;
        json_response(Product::search($q, 20, $cat, $isRetail));
    }

    /** GET /products/index — seznam všech produktů */
    public function index(?int $id, array $body): void
    {
        json_response(Product::all());
    }

    /** GET /products/grouped — produkty seskupené podle řady */
    public function grouped(?int $id, array $body): void
    {
        json_response(Product::grouped());
    }

    /** GET /products/grouped-retail — retail produkty seskupené podle řady */
    public function groupedRetail(?int $id, array $body): void
    {
        json_response(Product::groupedRetail());
    }

    /** POST /products/store */
    public function store(?int $id, array $body): void
    {
        $title = trim(($body ?: $_POST)['title'] ?? '');
        if ($title === '') {
            json_response(['error' => 'Název produktu je povinný'], 422);
        }
        $newId = Product::create([
            'title'         => $title,
            'category'      => trim(($body ?: $_POST)['category']      ?? ''),
            'series'        => trim(($body ?: $_POST)['series']        ?? ''),
            'volume'        => trim(($body ?: $_POST)['volume']        ?? '') ?: null,
            'default_price' => ($body ?: $_POST)['default_price'] ?? null,
            'is_retail'     => (int)(($body ?: $_POST)['is_retail'] ?? 0),
        ]);
        json_response(['id' => $newId, 'message' => 'Produkt přidán'], 201);
    }

    /** POST /products/update/{id} */
    public function update(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí ID'], 400); }
        $title = trim($body['title'] ?? '');
        if ($title === '') { json_response(['error' => 'Název je povinný'], 422); }
        $data = [
            'title'    => $title,
            'category' => $body['category'] ?? null,
            'series'   => $body['series']   ?? null,
        ];
        if (array_key_exists('volume', $body)) {
            $data['volume'] = $body['volume'];
        }
        if (array_key_exists('default_price', $body)) {
            $data['default_price'] = $body['default_price'];
        }
        Product::update($id, $data);
        json_response(['ok' => true]);
    }

    /** POST /products/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí ID'], 400); }
        Product::delete($id);
        json_response(['ok' => true]);
    }

    /** GET /products/export — export všech aktivních produktů pro sklad */
    public function exportAll(?int $id, array $body): void
    {
        $stmt = db()->query(
            "SELECT id, title, category, series, volume, default_price, is_retail
               FROM price_list_items
              WHERE is_active = 1
              ORDER BY is_retail, series, title"
        );
        json_response($stmt->fetchAll());
    }
}
