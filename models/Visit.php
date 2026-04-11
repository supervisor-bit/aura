<?php
declare(strict_types=1);

class Visit
{
    public static function forClient(int $clientId, int $limit = 20, int $offset = 0, ?int $months = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = 'client_id = :cid';
        $params = [':cid' => $clientId];
        if ($months !== null && $months > 0) {
            $where .= ' AND visit_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)';
            $params[':months'] = $months;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $where .= ' AND visit_date >= :dfrom';
            $params[':dfrom'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where .= ' AND visit_date <= :dto';
            $params[':dto'] = $dateTo;
        }
        $stmt = db()->prepare(
            "SELECT id, visit_date, service_name, billing_status, billing_amount, billing_change, note, price,
                    color_formula
               FROM client_visits
              WHERE {$where}
              ORDER BY visit_date DESC, id DESC
              LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $stmt = db()->prepare(
            'SELECT * FROM client_visits WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO client_visits
                (client_id, visit_date, service_name, color_formula, note, price, billing_status)
             VALUES
                (:client_id, :visit_date, :service_name, :color_formula, :note, :price, :billing_status)'
        );
        $stmt->execute([
            ':client_id'     => $data['client_id'],
            ':visit_date'    => $data['visit_date']    ?? date('Y-m-d'),
            ':service_name'  => $data['service_name']  ?? '',
            ':color_formula' => isset($data['color_formula'])
                                    ? json_encode($data['color_formula'], JSON_UNESCAPED_UNICODE)
                                    : null,
            ':note'          => $data['note']          ?? null,
            ':price'         => $data['price']         ?? null,
            ':billing_status'=> $data['billing_status']?? 'unpaid',
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = db()->prepare(
            'UPDATE client_visits
                SET visit_date     = :visit_date,
                    service_name   = :service_name,
                    color_formula  = :color_formula,
                    note           = :note,
                    price          = :price,
                    billing_status = :billing_status
              WHERE id = :id'
        );
        return $stmt->execute([
            ':visit_date'    => $data['visit_date']    ?? date('Y-m-d'),
            ':service_name'  => $data['service_name']  ?? '',
            ':color_formula' => isset($data['color_formula'])
                                    ? json_encode($data['color_formula'], JSON_UNESCAPED_UNICODE)
                                    : null,
            ':note'          => $data['note']          ?? null,
            ':price'         => $data['price']         ?? null,
            ':billing_status'=> $data['billing_status']?? 'unpaid',
            ':id'            => $id,
        ]);
    }

    public static function updateBilling(int $id, string $status, ?float $amount = null, ?float $change = null): bool
    {
        $allowed = ['unpaid', 'paid', 'complimentary'];
        if (!in_array($status, $allowed, true)) {
            $status = 'unpaid';
        }
        $stmt = db()->prepare(
            'UPDATE client_visits
                SET billing_status = :status,
                    billing_amount = :amount,
                    billing_change = :change
              WHERE id = :id'
        );
        return $stmt->execute([
            ':status' => $status,
            ':amount' => $amount,
            ':change' => $change,
            ':id'     => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM client_visits WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
