<?php
declare(strict_types=1);

class AccountingController
{
    /** GET /accounting/yearly?year=2026 — monthly breakdown for given year */
    public function yearly(?int $id, array $body): void
    {
        $year = (int)(input('year', 'get') ?: date('Y'));
        $db = db();

        // Services by month (only paid visits)
        $stmtServices = $db->prepare(
            "SELECT DATE_FORMAT(visit_date, '%Y-%m') AS month,
                    COUNT(*) AS visits_count,
                    COALESCE(SUM(price), 0) AS services_total
               FROM client_visits
              WHERE YEAR(visit_date) = :y AND billing_status = 'paid'
              GROUP BY month
              ORDER BY month"
        );
        $stmtServices->execute([':y' => $year]);
        $serviceRows = $stmtServices->fetchAll();

        // Products by month (all retail_sales including anonymous)
        $stmtProducts = $db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS sales_count,
                    COALESCE(SUM(total), 0) AS products_total
               FROM retail_sales
              WHERE YEAR(created_at) = :y
              GROUP BY month
              ORDER BY month"
        );
        $stmtProducts->execute([':y' => $year]);
        $productRows = $stmtProducts->fetchAll();

        // Merge into 12 months
        $sMap = [];
        foreach ($serviceRows as $r) $sMap[$r['month']] = $r;
        $pMap = [];
        foreach ($productRows as $r) $pMap[$r['month']] = $r;

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%04d-%02d', $year, $m);
            $sv = $sMap[$key] ?? null;
            $pv = $pMap[$key] ?? null;
            $sTotal = (float)($sv['services_total'] ?? 0);
            $pTotal = (float)($pv['products_total'] ?? 0);
            $months[] = [
                'month'          => $key,
                'visits_count'   => (int)($sv['visits_count'] ?? 0),
                'services_total' => $sTotal,
                'sales_count'    => (int)($pv['sales_count'] ?? 0),
                'products_total' => $pTotal,
                'total'          => $sTotal + $pTotal,
            ];
        }

        $yearServices = array_sum(array_column($months, 'services_total'));
        $yearProducts = array_sum(array_column($months, 'products_total'));

        json_response([
            'year'           => $year,
            'months'         => $months,
            'year_services'  => $yearServices,
            'year_products'  => $yearProducts,
            'year_total'     => $yearServices + $yearProducts,
        ]);
    }

    /** GET /accounting/export-csv?year=2026 — CSV download */
    public function exportCsv(?int $id, array $body): void
    {
        $year = (int)(input('year', 'get') ?: date('Y'));
        $db = db();

        $stmtServices = $db->prepare(
            "SELECT DATE_FORMAT(visit_date, '%Y-%m') AS month,
                    COUNT(*) AS visits_count,
                    COALESCE(SUM(price), 0) AS services_total
               FROM client_visits
              WHERE YEAR(visit_date) = :y AND billing_status = 'paid'
              GROUP BY month
              ORDER BY month"
        );
        $stmtServices->execute([':y' => $year]);
        $serviceRows = $stmtServices->fetchAll();

        $stmtProducts = $db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS sales_count,
                    COALESCE(SUM(total), 0) AS products_total
               FROM retail_sales
              WHERE YEAR(created_at) = :y
              GROUP BY month
              ORDER BY month"
        );
        $stmtProducts->execute([':y' => $year]);
        $productRows = $stmtProducts->fetchAll();

        $sMap = [];
        foreach ($serviceRows as $r) $sMap[$r['month']] = $r;
        $pMap = [];
        foreach ($productRows as $r) $pMap[$r['month']] = $r;

        $monthNames = ['','Leden','Únor','Březen','Duben','Květen','Červen',
                        'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"ucetni-prehled-{$year}.csv\"");
        $out = fopen('php://output', 'w');
        // BOM for Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Měsíc', 'Počet návštěv', 'Služby (Kč)', 'Počet prodejů', 'Produkty (Kč)', 'Celkem (Kč)'], ';');

        $totalS = 0; $totalP = 0; $totalV = 0; $totalSales = 0;
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%04d-%02d', $year, $m);
            $sv = $sMap[$key] ?? null;
            $pv = $pMap[$key] ?? null;
            $sTotal = (float)($sv['services_total'] ?? 0);
            $pTotal = (float)($pv['products_total'] ?? 0);
            $vc = (int)($sv['visits_count'] ?? 0);
            $sc = (int)($pv['sales_count'] ?? 0);
            $totalS += $sTotal; $totalP += $pTotal; $totalV += $vc; $totalSales += $sc;
            fputcsv($out, [
                $monthNames[$m] . ' ' . $year,
                $vc,
                number_format($sTotal, 2, ',', ' '),
                $sc,
                number_format($pTotal, 2, ',', ' '),
                number_format($sTotal + $pTotal, 2, ',', ' '),
            ], ';');
        }
        fputcsv($out, [
            'CELKEM ' . $year,
            $totalV,
            number_format($totalS, 2, ',', ' '),
            $totalSales,
            number_format($totalP, 2, ',', ' '),
            number_format($totalS + $totalP, 2, ',', ' '),
        ], ';');

        fclose($out);
        exit;
    }

    /** GET /accounting/daily?date=2026-04-11 — daily summary */
    public function daily(?int $id, array $body): void
    {
        $date = input('date', 'get') ?: date('Y-m-d');
        $db = db();

        // Paid visits for this day
        $stmt = $db->prepare(
            "SELECT cv.id, cv.visit_date, cv.service_name, cv.price, cv.billing_status,
                    c.full_name
               FROM client_visits cv
               JOIN clients c ON c.id = cv.client_id
              WHERE cv.visit_date = :d AND cv.billing_status = 'paid'
              ORDER BY cv.id"
        );
        $stmt->execute([':d' => $date]);
        $visits = $stmt->fetchAll();

        // All retail sales for this day (including anonymous)
        $stmt2 = $db->prepare(
            "SELECT rs.id, rs.client_id, rs.items, rs.total, rs.note, rs.created_at,
                    c.full_name
               FROM retail_sales rs
               LEFT JOIN clients c ON c.id = rs.client_id
              WHERE DATE(rs.created_at) = :d
              ORDER BY rs.id"
        );
        $stmt2->execute([':d' => $date]);
        $sales = $stmt2->fetchAll();
        foreach ($sales as &$s) {
            $s['items'] = json_decode($s['items'], true) ?: [];
            $s['full_name'] = $s['full_name'] ?: 'Anonymní zákazník';
        }

        $servicesTotal = array_sum(array_map(fn($v) => (float)$v['price'], $visits));
        $productsTotal = array_sum(array_map(fn($s) => (float)$s['total'], $sales));

        // Check if closing exists
        $stmtC = $db->prepare('SELECT * FROM daily_closings WHERE closing_date = :d');
        $stmtC->execute([':d' => $date]);
        $closing = $stmtC->fetch() ?: null;

        // Unpaid visits for this day
        $stmtU = $db->prepare(
            "SELECT COUNT(*) FROM client_visits
              WHERE visit_date = :d AND billing_status != 'paid'"
        );
        $stmtU->execute([':d' => $date]);
        $unpaidCount = (int)$stmtU->fetchColumn();

        json_response([
            'date'           => $date,
            'visits'         => $visits,
            'sales'          => $sales,
            'services_total' => $servicesTotal,
            'products_total' => $productsTotal,
            'total'          => $servicesTotal + $productsTotal,
            'visits_count'   => count($visits),
            'sales_count'    => count($sales),
            'unpaid_count'   => $unpaidCount,
            'closing'        => $closing,
        ]);
    }

    /** POST /accounting/close-day — create daily closing */
    public function closeDay(?int $id, array $body): void
    {
        $date = $body['date'] ?? date('Y-m-d');
        $db = db();

        // Calculate totals
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(price), 0) AS services_total, COUNT(*) AS visits_count
               FROM client_visits
              WHERE visit_date = :d AND billing_status = 'paid'"
        );
        $stmt->execute([':d' => $date]);
        $sv = $stmt->fetch();

        $stmt2 = $db->prepare(
            "SELECT COALESCE(SUM(total), 0) AS products_total, COUNT(*) AS sales_count
               FROM retail_sales
              WHERE DATE(created_at) = :d"
        );
        $stmt2->execute([':d' => $date]);
        $pv = $stmt2->fetch();

        $sTotal = (float)$sv['services_total'];
        $pTotal = (float)$pv['products_total'];

        $stmt3 = $db->prepare(
            'INSERT INTO daily_closings (closing_date, services_total, products_total, total, visits_count, sales_count, note)
             VALUES (:d, :st, :pt, :t, :vc, :sc, :n)
             ON DUPLICATE KEY UPDATE
                services_total = VALUES(services_total),
                products_total = VALUES(products_total),
                total = VALUES(total),
                visits_count = VALUES(visits_count),
                sales_count = VALUES(sales_count),
                note = VALUES(note),
                created_at = CURRENT_TIMESTAMP'
        );
        $stmt3->execute([
            ':d'  => $date,
            ':st' => $sTotal,
            ':pt' => $pTotal,
            ':t'  => $sTotal + $pTotal,
            ':vc' => (int)$sv['visits_count'],
            ':sc' => (int)$pv['sales_count'],
            ':n'  => $body['note'] ?? null,
        ]);

        json_response(['message' => 'Denní uzávěrka uložena', 'date' => $date, 'total' => $sTotal + $pTotal]);
    }

    /** GET /accounting/closings?year=2026 — list of daily closings */
    public function closings(?int $id, array $body): void
    {
        $year = (int)(input('year', 'get') ?: date('Y'));
        $db = db();
        $stmt = $db->prepare(
            'SELECT * FROM daily_closings
              WHERE YEAR(closing_date) = :y
              ORDER BY closing_date DESC'
        );
        $stmt->execute([':y' => $year]);
        json_response($stmt->fetchAll());
    }
}
