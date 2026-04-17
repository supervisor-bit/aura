<?php
declare(strict_types=1);

class StatsController
{
    /** GET /stats/consumption?period=month|quarter|year|all */
    public function consumption(?int $id, array $body): void
    {
        $period = trim(input('period', 'get')) ?: 'month';

        $where = '';
        $params = [];
        $now = date('Y-m-d');
        switch ($period) {
            case 'month':
                $where = 'AND visit_date >= DATE_SUB(:now, INTERVAL 1 MONTH)';
                $params[':now'] = $now;
                break;
            case 'prev-month':
                $where = 'AND visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), "%Y-%m-01")
                          AND visit_date < DATE_FORMAT(CURDATE(), "%Y-%m-01")';
                break;
            case 'quarter':
                $where = 'AND visit_date >= DATE_SUB(:now, INTERVAL 3 MONTH)';
                $params[':now'] = $now;
                break;
            case 'year':
                $where = 'AND visit_date >= DATE_SUB(:now, INTERVAL 1 YEAR)';
                $params[':now'] = $now;
                break;
            // 'all' → no date filter
        }

        $stmt = db()->prepare(
            "SELECT id, visit_date, color_formula
               FROM client_visits
              WHERE color_formula IS NOT NULL {$where}
              ORDER BY visit_date DESC"
        );
        $stmt->execute($params);
        $visits = $stmt->fetchAll();

        // Aggregate product usage
        $products = [];
        $totalVisits = count($visits);

        foreach ($visits as $v) {
            $formula = json_decode($v['color_formula'], true);
            if (!$formula || empty($formula['bowls'])) continue;

            foreach ($formula['bowls'] as $bowl) {
                foreach ($bowl['products'] ?? [] as $p) {
                    $name = trim($p['name'] ?? '');
                    $amount = (float) ($p['amount'] ?? 0);
                    if (!$name) continue;
                    if (!isset($products[$name])) {
                        $products[$name] = ['name' => $name, 'total_grams' => 0, 'usage_count' => 0, 'unit' => 'g', 'last_used' => null];
                    }
                    $products[$name]['total_grams'] += $amount;
                    $products[$name]['usage_count']++;
                    if (!$products[$name]['last_used'] || $v['visit_date'] > $products[$name]['last_used']) {
                        $products[$name]['last_used'] = $v['visit_date'];
                    }
                }

                // Oxidant
                $ox = $bowl['oxidant'] ?? null;
                if ($ox && !empty($ox['name'])) {
                    $name = trim($ox['name']);
                    $amount = (float) ($ox['amount'] ?? 0);
                    if (!isset($products[$name])) {
                        $products[$name] = ['name' => $name, 'total_grams' => 0, 'usage_count' => 0, 'unit' => 'ml', 'last_used' => null];
                    }
                    $products[$name]['total_grams'] += $amount;
                    $products[$name]['usage_count']++;
                    if (!$products[$name]['last_used'] || $v['visit_date'] > $products[$name]['last_used']) {
                        $products[$name]['last_used'] = $v['visit_date'];
                    }
                }
            }
        }

        // Sort by total grams desc
        $list = array_values($products);
        usort($list, fn($a, $b) => $b['total_grams'] <=> $a['total_grams']);

        // Unused products (not used in 3+ months)
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        $unused = [];
        // Get all material products
        $allProducts = db()->query(
            "SELECT id, title FROM price_list_items WHERE is_retail = 0 AND is_active = 1"
        )->fetchAll();

        // Build last-usage map from already-fetched visits when period covers all,
        // otherwise do a targeted query limited to visits with formulas
        if ($period === 'all') {
            $sourceVisits = $visits;
        } else {
            $sourceVisits = db()->query(
                "SELECT color_formula, visit_date FROM client_visits
                  WHERE color_formula IS NOT NULL
                  ORDER BY visit_date DESC"
            )->fetchAll();
        }

        $lastUsage = [];
        foreach ($sourceVisits as $av) {
            $formula = $av['color_formula'];
            if (is_string($formula)) {
                $formula = json_decode($formula, true);
            }
            if (!$formula || empty($formula['bowls'])) continue;
            foreach ($formula['bowls'] as $bowl) {
                foreach ($bowl['products'] ?? [] as $p) {
                    $n = trim($p['name'] ?? '');
                    if ($n && !isset($lastUsage[$n])) $lastUsage[$n] = $av['visit_date'];
                }
                if (!empty($bowl['oxidant']['name'])) {
                    $n = trim($bowl['oxidant']['name']);
                    if (!isset($lastUsage[$n])) $lastUsage[$n] = $av['visit_date'];
                }
            }
        }

        foreach ($allProducts as $ap) {
            $lu = $lastUsage[$ap['title']] ?? null;
            if ($lu === null || $lu < $threeMonthsAgo) {
                $unused[] = ['name' => $ap['title'], 'last_used' => $lu];
            }
        }
        usort($unused, fn($a, $b) => ($a['last_used'] ?? '') <=> ($b['last_used'] ?? ''));

        json_response([
            'period'       => $period,
            'total_visits' => $totalVisits,
            'products'     => $list,
            'unused'       => array_slice($unused, 0, 20),
        ]);
    }
}
