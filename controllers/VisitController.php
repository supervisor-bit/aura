<?php
declare(strict_types=1);

class VisitController
{
    /** GET /visits/index/{clientId}?limit=20&offset=0&months=&from=&to= */
    public function index(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí client_id'], 400);
        }
        $limit    = min((int) ($_GET['limit']  ?? 20), 100);
        $offset   = max((int) ($_GET['offset'] ?? 0), 0);
        $months   = isset($_GET['months']) && $_GET['months'] !== '' ? (int) $_GET['months'] : null;
        $dateFrom = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null;
        $dateTo   = isset($_GET['to'])   && $_GET['to']   !== '' ? $_GET['to']   : null;
        json_response(Visit::forClient($id, $limit, $offset, $months, $dateFrom, $dateTo));
    }

    /** GET /visits/show/{id} — detail návštěvy (včetně receptury) */
    public function show(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        $visit = Visit::find($id);
        if (!$visit) {
            json_response(['error' => 'Návštěva nenalezena'], 404);
        }
        // Dekóduj JSON recepturu
        if ($visit['color_formula']) {
            $visit['color_formula'] = json_decode($visit['color_formula'], true);
        }
        json_response($visit);
    }

    /** POST /visits/store — nová návštěva */
    public function store(?int $id, array $body): void
    {
        $data = $this->validate($body ?: $_POST);
        if (isset($data['error'])) {
            json_response($data, 422);
        }
        $newId = Visit::create($data);
        json_response(['id' => $newId, 'message' => 'Návštěva uložena'], 201);
    }

    /** POST /visits/update/{id} — uložení změn */
    public function update(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        $input = $body ?: $_POST;

        // Pokud jde jen o změnu billing_status, nemazat ostatní pole
        if (isset($input['billing_status']) && !isset($input['color_formula'])) {
            $amount = isset($input['billing_amount']) ? (float)$input['billing_amount'] : null;
            $change = isset($input['billing_change']) ? (float)$input['billing_change'] : null;
            Visit::updateBilling($id, $input['billing_status'], $amount, $change);
            json_response(['message' => 'Návštěva uložena']);
            return;
        }

        $data = $this->validate($input);
        if (isset($data['error'])) {
            json_response($data, 422);
        }
        Visit::update($id, $data);
        json_response(['message' => 'Návštěva uložena']);
    }

    /** POST /visits/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        Visit::delete($id);
        json_response(['message' => 'Návštěva smazána']);
    }

    // ── Privátní ──────────────────────────────────────────────────────────────

    /** POST /visits/billing/{id} — atomic billing: update visit + replace sale */
    public function billing(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        $clientId = (int)($body['client_id'] ?? 0);
        $amount   = isset($body['billing_amount']) ? (float)$body['billing_amount'] : null;
        $change   = isset($body['billing_change']) ? (float)$body['billing_change'] : null;
        $items    = $body['items'] ?? [];

        $db = db();
        $db->beginTransaction();
        try {
            Visit::updateBilling($id, 'paid', $amount, $change);

            Sale::deleteByVisit($id);

            if (!empty($items)) {
                $total = 0;
                foreach ($items as &$item) {
                    $qty = max(1, (int)($item['qty'] ?? 1));
                    $price = max(0.0, (float)($item['unit_price'] ?? 0));
                    $item['qty'] = $qty;
                    $item['unit_price'] = $price;
                    $total += $qty * $price;
                }
                Sale::create([
                    'client_id' => $clientId ?: null,
                    'visit_id'  => $id,
                    'items'     => $items,
                    'total'     => $total,
                    'note'      => null,
                ]);
            }

            $db->commit();
            json_response(['message' => 'Vyúčtováno']);
        } catch (\Throwable $e) {
            $db->rollBack();
            json_response(['error' => 'Chyba při ukládání: ' . $e->getMessage()], 500);
        }
    }

    /** GET /visits/export — spotřebované materiály + retail prodeje za dnešní den */
    public function export(?int $id, array $body): void
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            json_response(['error' => 'Neplatný formát data (YYYY-MM-DD)'], 400);
        }

        // 1. Všechny návštěvy za daný den s recepturou
        $stmt = db()->prepare(
            "SELECT v.id, v.visit_date, v.service_name, v.color_formula,
                    c.full_name AS client_name
               FROM client_visits v
               JOIN clients c ON c.id = v.client_id
              WHERE v.visit_date = :date
              ORDER BY v.id"
        );
        $stmt->execute([':date' => $date]);
        $visits = $stmt->fetchAll();

        // 2. Extrahovat materiály z color_formula
        $materials = [];
        foreach ($visits as $visit) {
            if (empty($visit['color_formula'])) continue;
            $formula = json_decode($visit['color_formula'], true);
            if (!is_array($formula) || empty($formula['bowls'])) continue;
            foreach ($formula['bowls'] as $bowl) {
                if (!empty($bowl['products'])) {
                    foreach ($bowl['products'] as $prod) {
                        $name = $prod['name'] ?? '';
                        $amount = (float)($prod['amount'] ?? 0);
                        if ($name === '') continue;
                        if (!isset($materials[$name])) {
                            $materials[$name] = ['name' => $name, 'total_amount_g' => 0, 'unit' => 'g'];
                        }
                        $materials[$name]['total_amount_g'] += $amount;
                    }
                }
                if (!empty($bowl['oxidant']['name'])) {
                    $oxName = $bowl['oxidant']['name'];
                    $oxAmount = (float)($bowl['oxidant']['amount'] ?? 0);
                    if (!isset($materials[$oxName])) {
                        $materials[$oxName] = ['name' => $oxName, 'total_amount_g' => 0, 'unit' => 'g'];
                    }
                    $materials[$oxName]['total_amount_g'] += $oxAmount;
                }
            }
        }

        // 3. Retail prodeje za dnešní návštěvy
        $visitIds = array_column($visits, 'id');
        $retailSales = [];
        if (!empty($visitIds)) {
            $placeholders = implode(',', array_fill(0, count($visitIds), '?'));
            $stmt = db()->prepare(
                "SELECT id, visit_id, items, total, note
                   FROM retail_sales
                  WHERE visit_id IN ({$placeholders})"
            );
            $stmt->execute($visitIds);
            foreach ($stmt->fetchAll() as $sale) {
                $sale['items'] = json_decode($sale['items'] ?? '[]', true);
                $retailSales[] = $sale;
            }
        }

        json_response([
            'date'      => $date,
            'materials' => array_values($materials),
            'retail'    => $retailSales,
        ]);
    }

    private function validate(array $data): array
    {
        $clientId = (int)($data['client_id'] ?? 0);
        if ($clientId <= 0) {
            return ['error' => 'Chybí client_id'];
        }
        $allowedBilling = ['unpaid', 'paid', 'complimentary'];
        $billing = $data['billing_status'] ?? 'unpaid';
        if (!in_array($billing, $allowedBilling, true)) {
            $billing = 'unpaid';
        }
        return [
            'client_id'      => $clientId,
            'visit_date'     => $data['visit_date']    ?? date('Y-m-d'),
            'service_name'   => trim($data['service_name'] ?? ''),
            'color_formula'  => $data['color_formula'] ?? null,
            'note'           => trim($data['note']     ?? ''),
            'price'          => isset($data['price']) ? (float)$data['price'] : null,
            'billing_status' => $billing,
        ];
    }
}
