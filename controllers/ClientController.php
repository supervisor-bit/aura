<?php
declare(strict_types=1);

class ClientController
{
    /** GET / nebo GET /clients/index — hlavní SPA stránka */
    public function index(?int $id, array $body): void
    {
        $clients = Client::all();
        view('layout', ['clients' => $clients]);
    }

    /** GET /clients/search?q=...&inactive=1 — AJAX vyhledávání */
    public function search(?int $id, array $body): void
    {
        $q = trim(input('q', 'get'));
        $includeInactive = input('inactive', 'get') === '1';
        if ($q === '') {
            json_response(Client::all($includeInactive));
        }
        json_response(Client::search($q, $includeInactive));
    }

    /** GET /clients/show/{id} — AJAX detail klienta */
    public function show(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        $client = Client::find($id);
        if (!$client) {
            json_response(['error' => 'Klient nenalezen'], 404);
        }
        $client['visit_count']      = Client::visitCount($id);
        $client['last_visit']       = Client::lastVisitDate($id);
        $client['total_spent']      = Client::totalSpent($id);
        $client['total_retail']     = Client::totalRetailSpent($id);
        $client['formula_summary']  = Client::formulaSummary($id);
        $client['tags']             = Tag::forClient($id);
        json_response($client);
    }

    /** POST /clients/store — vytvoření klienta */
    public function store(?int $id, array $body): void
    {
        $data = $this->validate($body ?: $_POST);
        if (isset($data['error'])) {
            json_response($data, 422);
        }
        $tagIds = $data['tags'] ?? [];
        unset($data['tags']);
        $newId = Client::create($data);
        Tag::syncClient($newId, $tagIds);
        json_response(['id' => $newId, 'message' => 'Klient vytvořen'], 201);
    }

    /** POST /clients/update/{id} — uložení změn */
    public function update(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        $data = $this->validate($body ?: $_POST);
        if (isset($data['error'])) {
            json_response($data, 422);
        }
        $tagIds = $data['tags'] ?? [];
        unset($data['tags']);
        Client::update($id, $data);
        Tag::syncClient($id, $tagIds);
        json_response(['message' => 'Klient uložen']);
    }

    /** POST /clients/delete/{id} — smazání */
    public function delete(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        Client::delete($id);
        json_response(['message' => 'Klient smazán']);
    }

    /** GET /dashboard/stats — statistiky pro dashboard */
    public function dashboardStats(?int $id, array $body): void
    {
        $db = db();

        $clientCount = (int) $db->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        $visitCount  = (int) $db->query('SELECT COUNT(*) FROM client_visits')->fetchColumn();

        $monthStart = date('Y-m-01');
        $stmt = $db->prepare('SELECT COUNT(*) FROM client_visits WHERE visit_date >= :d');
        $stmt->execute([':d' => $monthStart]);
        $monthVisits = (int) $stmt->fetchColumn();

        $revenue = (float) $db->query("SELECT COALESCE(SUM(price), 0) FROM client_visits WHERE billing_status = 'paid'")->fetchColumn();

        $retailRevenue = (float) $db->query('SELECT COALESCE(SUM(total), 0) FROM retail_sales')->fetchColumn();
        $retailCount = (int) $db->query('SELECT COUNT(*) FROM retail_sales')->fetchColumn();

        $monthRetail = $db->prepare('SELECT COALESCE(SUM(total), 0) FROM retail_sales WHERE created_at >= :d');
        $monthRetail->execute([':d' => $monthStart]);
        $monthRetailRev = (float) $monthRetail->fetchColumn();

        $recent = $db->query(
            'SELECT cv.visit_date, cv.service_name, c.full_name
               FROM client_visits cv
               JOIN clients c ON c.id = cv.client_id
              ORDER BY cv.visit_date DESC, cv.id DESC
              LIMIT 8'
        )->fetchAll();

        $unpaid = $db->query(
            'SELECT cv.id, cv.visit_date, cv.service_name, cv.price, c.full_name, c.id AS client_id
               FROM client_visits cv
               JOIN clients c ON c.id = cv.client_id
              WHERE cv.billing_status != \'paid\'
              ORDER BY cv.visit_date DESC
              LIMIT 20'
        )->fetchAll();

        // Monthly visits for chart (last 6 months)
        $monthly = $db->query(
            "SELECT DATE_FORMAT(visit_date, '%Y-%m') AS month,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(price), 0) AS rev
               FROM client_visits
              WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
              GROUP BY month
              ORDER BY month ASC"
        )->fetchAll();

        // Retention: clients whose last visit was > 42 days ago
        $retention = $db->query(
            "SELECT c.id, c.full_name,
                    MAX(cv.visit_date) AS last_visit,
                    DATEDIFF(CURDATE(), MAX(cv.visit_date)) AS days_since
               FROM clients c
               JOIN client_visits cv ON cv.client_id = c.id
              WHERE c.status != 'inactive'
              GROUP BY c.id
             HAVING days_since > 42
              ORDER BY days_since DESC
              LIMIT 20"
        )->fetchAll();

        json_response([
            'client_count' => $clientCount,
            'visit_count'  => $visitCount,
            'month_visits' => $monthVisits,
            'revenue'      => $revenue,
            'retail_revenue' => $retailRevenue,
            'retail_count'   => $retailCount,
            'month_retail'   => $monthRetailRev,
            'recent'       => $recent,
            'unpaid'       => $unpaid,
            'monthly'      => $monthly,
            'retention'    => $retention,
        ]);
    }

    // ── Privátní ──────────────────────────────────────────────────────────────

    private function validate(array $data): array
    {
        $name = trim($data['full_name'] ?? '');
        if ($name === '') {
            return ['error' => 'Jméno klienta je povinné'];
        }
        $allowedStatus = ['active', 'inactive', 'vip'];
        $status = $data['status'] ?? 'active';
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'active';
        }
        return [
            'full_name' => $name,
            'phone'     => trim($data['phone']  ?? ''),
            'status'    => $status,
            'notes'     => trim($data['notes']  ?? ''),
            'tags'      => array_map('intval', $data['tags'] ?? []),
        ];
    }
}
