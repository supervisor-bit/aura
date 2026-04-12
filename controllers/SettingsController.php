<?php
declare(strict_types=1);

class SettingsController
{
    public function getUsername(?int $id = null, array $body = []): void
    {
        $stm = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stm->execute(['auth_user']);
        $username = $stm->fetchColumn() ?: '';
        json_response(['username' => $username]);
    }

    public function getSalon(?int $id = null, array $body = []): void
    {
        $keys = ['salon_name', 'salon_address', 'salon_phone', 'salon_ico', 'salon_note'];
        $result = [];
        $stm = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        foreach ($keys as $key) {
            $stm->execute([$key]);
            $result[$key] = $stm->fetchColumn() ?: '';
        }
        json_response($result);
    }

    public function saveSalon(?int $id = null, array $body = []): void
    {
        $phone = trim($body['salon_phone'] ?? '');
        $ico   = trim($body['salon_ico'] ?? '');

        if ($phone !== '' && !preg_match('/^[+\d][\d\s\-()]{5,}$/', $phone)) {
            json_response(['error' => 'Neplatný formát telefonu'], 422);
        }
        if ($ico !== '' && !preg_match('/^\d{8}$/', $ico)) {
            json_response(['error' => 'IČO musí mít přesně 8 číslic'], 422);
        }

        $keys = ['salon_name', 'salon_address', 'salon_phone', 'salon_ico', 'salon_note'];
        $stm = db()->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($keys as $key) {
            $stm->execute([$key, trim($body[$key] ?? '')]);
        }
        json_response(['message' => 'Údaje o salonu uloženy']);
    }

    public function changePassword(?int $id, array $body): void
    {
        $currentPass = $body['current_password'] ?? '';
        $newUser     = trim($body['username'] ?? '');
        $newPass     = $body['new_password'] ?? '';
        $newPass2    = $body['new_password2'] ?? '';

        if ($newUser === '') {
            json_response(['error' => 'Uživatelské jméno je povinné'], 422);
        }
        if ($currentPass === '') {
            json_response(['error' => 'Zadejte aktuální heslo'], 422);
        }

        // Verify current password
        $stm = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stm->execute(['auth_pass']);
        $dbPass = $stm->fetchColumn() ?: '';

        if (!password_verify($currentPass, $dbPass)) {
            json_response(['error' => 'Aktuální heslo je nesprávné'], 403);
        }

        // Validate new password if provided
        if ($newPass !== '') {
            if (strlen($newPass) < 4) {
                json_response(['error' => 'Nové heslo musí mít alespoň 4 znaky'], 422);
            }
            if ($newPass !== $newPass2) {
                json_response(['error' => 'Hesla se neshodují'], 422);
            }
        }

        // Update username
        $stm = db()->prepare('UPDATE app_settings SET setting_value = ? WHERE setting_key = ?');
        $stm->execute([$newUser, 'auth_user']);

        // Update password if changed
        if ($newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stm->execute([$hash, 'auth_pass']);
        }

        // Update session username
        $_SESSION['user'] = $newUser;

        json_response(['message' => 'Nastavení bylo uloženo']);
    }

    public function backup(?int $id = null, array $body = []): void
    {
        $pdo = db();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $dump = "-- AURA — Záloha databáze\n";
        $dump .= "-- Datum: " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Databáze: " . DB_NAME . "\n\n";
        $dump .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // CREATE TABLE statement
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $create['Create Table'] . ";\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $cols = array_keys($rows[0]);
                $colNames = implode('`, `', $cols);

                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote((string)$v);
                    }, array_values($row));
                    $dump .= "INSERT INTO `{$table}` (`{$colNames}`) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $dump .= "\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $filename = 'aura_backup_' . date('Y-m-d_His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($dump));
        echo $dump;
        exit;
    }

    /** GET /settings/data-stats — přehled počtu záznamů */
    public function dataStats(?int $id = null, array $body = []): void
    {
        $pdo = db();
        $tables = [
            ['key' => 'clients',        'label' => 'Klienti'],
            ['key' => 'client_visits',   'label' => 'Návštěvy'],
            ['key' => 'client_notes',    'label' => 'Poznámky'],
            ['key' => 'tags',            'label' => 'Štítky'],
            ['key' => 'retail_sales',    'label' => 'Prodeje'],
            ['key' => 'daily_closings',  'label' => 'Denní uzávěrky'],
            ['key' => 'code_lists',      'label' => 'Číselníky'],
            ['key' => 'price_list_items','label' => 'Produkty'],
        ];
        $stats = [];
        foreach ($tables as $t) {
            $cnt = (int) $pdo->query("SELECT COUNT(*) FROM `{$t['key']}`")->fetchColumn();
            $stats[] = ['key' => $t['key'], 'label' => $t['label'], 'count' => $cnt];
        }
        json_response($stats);
    }

    /** POST /settings/purge-data — vymazat provozní data (klienti, návštěvy, poznámky, prodeje, uzávěrky) */
    public function purgeData(?int $id = null, array $body = []): void
    {
        $confirm = $body['confirm'] ?? '';
        if ($confirm !== 'SMAZAT') {
            json_response(['error' => 'Potvrzení nesouhlasí'], 400);
        }

        $pdo = db();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE TABLE client_notes');
        $pdo->exec('TRUNCATE TABLE client_tags');
        $pdo->exec('TRUNCATE TABLE client_visits');
        $pdo->exec('TRUNCATE TABLE retail_sales');
        $pdo->exec('TRUNCATE TABLE daily_closings');
        $pdo->exec('TRUNCATE TABLE clients');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        json_response(['message' => 'Provozní data byla vymazána']);
    }
}
