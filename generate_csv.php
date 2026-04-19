<?php
declare(strict_types=1);

/**
 * Generate CSV from price_list_items table
 * Usage: php generate_csv.php > materialy_produkty.csv
 */

// Vypnutí warningů
error_reporting(0);
ini_set('display_errors', 0);

// Načtení konfigu
require_once __DIR__ . '/config.php';

// Připojení k databázi
try {
    $pdo = new PDO(
        'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Chyba připojení k databázi: " . $e->getMessage() . "\n");
}

// Získání všech položek (materiály i produkty)
$stmt = $pdo->query(
    "SELECT id, title, category, series, volume, default_price, is_active, is_retail
     FROM price_list_items
     ORDER BY category, series, title"
);
$items = $stmt->fetchAll();

// Nastavení CSV headeru
header('Content-Type: text/csv; charset=utf-8', true);
header('Content-Disposition: attachment; filename="materialy_produkty.csv"', true);

$output = fopen('php://output', 'w');

// UTF-8 BOM pro Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header
$header = ['ID', 'Název', 'Kategorie', 'Řada', 'Objem', 'Výchozí cena', 'Aktivní', 'Retail'];
fwrite($output, implode(';', $header) . "\n");

// Data
foreach ($items as $item) {
    $row = [
        $item['id'],
        '"' . str_replace('"', '""', $item['title']) . '"',
        $item['category'] ?? '',
        $item['series'] ?? '',
        $item['volume'] ?? '',
        $item['default_price'] ?? '',
        $item['is_active'] ? 'Ano' : 'Ne',
        $item['is_retail'] ? 'Ano' : 'Ne'
    ];
    fwrite($output, implode(';', $row) . "\n");
}

fclose($output);
?>
