<?php
declare(strict_types=1);

class SaleController
{
    /** GET /sales/index/{clientId} — prodeje klienta */
    public function index(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí client_id'], 400); }
        json_response(Sale::forClient($id));
    }

    /** GET /sales/for-visit/{visitId} — prodej svázaný s návštěvou */
    public function forVisit(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí visit_id'], 400); }
        $sale = Sale::forVisit($id);
        json_response($sale ?: ['items' => []]);
    }

    /** POST /sales/store */
    public function store(?int $id, array $body): void
    {
        $items = $body['items'] ?? [];
        if (empty($items)) {
            json_response(['error' => 'Žádné položky'], 422);
        }
        $total = 0;
        foreach ($items as &$item) {
            $qty = max(1, (int)($item['qty'] ?? 1));
            $price = max(0.0, (float)($item['unit_price'] ?? 0));
            $item['qty'] = $qty;
            $item['unit_price'] = $price;
            $total += $qty * $price;
        }
        $newId = Sale::create([
            'client_id' => $body['client_id'] ?? null,
            'visit_id'  => $body['visit_id'] ?? null,
            'items'     => $items,
            'total'     => $total,
            'note'      => trim($body['note'] ?? '') ?: null,
        ]);
        json_response(['id' => $newId, 'total' => $total, 'message' => 'Prodej uložen'], 201);
    }

    /** POST /sales/update/{id} */
    public function update(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí ID'], 400); }
        $items = $body['items'] ?? [];
        if (empty($items)) {
            json_response(['error' => 'Žádné položky'], 422);
        }
        $total = 0;
        foreach ($items as &$item) {
            $qty = max(1, (int)($item['qty'] ?? 1));
            $price = max(0.0, (float)($item['unit_price'] ?? 0));
            $item['qty'] = $qty;
            $item['unit_price'] = $price;
            $total += $qty * $price;
        }
        Sale::update($id, [
            'items' => $items,
            'total' => $total,
            'note'  => trim($body['note'] ?? '') ?: null,
        ]);
        json_response(['ok' => true, 'total' => $total]);
    }

    /** POST /sales/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí ID'], 400); }
        Sale::delete($id);
        json_response(['ok' => true]);
    }

    /** POST /sales/delete-by-visit/{visitId} */
    public function deleteByVisit(?int $id, array $body): void
    {
        if (!$id) { json_response(['error' => 'Chybí visit_id'], 400); }
        Sale::deleteByVisit($id);
        json_response(['ok' => true]);
    }
}
