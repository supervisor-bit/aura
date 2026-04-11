-- AURA — Kartotéka klientů pro kadeřnický salon
-- MySQL 8+ schema

SET NAMES utf8mb4;
SET time_zone = '+01:00';

CREATE DATABASE IF NOT EXISTS aura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aura;

-- ─── Klienti ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clients (
    id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    full_name  VARCHAR(120)     NOT NULL,
    phone      VARCHAR(30)      DEFAULT NULL,
    status     ENUM('active','inactive','vip') NOT NULL DEFAULT 'active',
    notes      TEXT             DEFAULT NULL,
    created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_full_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Návštěvy ──────────────────────────────────────────────────────────────────
-- color_formula JSON struktura:
-- {
--   "actions": ["Odrost","Toner"],
--   "bowls": [
--     {
--       "label": "Miska 1",
--       "color": "#e53935",
--       "products": [
--         {"name": "Igora Royal 6-0", "amount": 60},
--         {"name": "Igora Royal 0-11", "amount": 10}
--       ],
--       "oxidant": {"name": "Igora Royal Oil Developer 6%", "ratio": "1:1", "amount": 70}
--     }
--   ],
--   "note": "Nechat působit 35 min"
-- }
CREATE TABLE IF NOT EXISTS client_visits (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    client_id      INT UNSIGNED     NOT NULL,
    visit_date     DATE             NOT NULL,
    service_name   VARCHAR(200)     NOT NULL DEFAULT '',
    color_formula  JSON             DEFAULT NULL,
    note           TEXT             DEFAULT NULL,
    billing_status ENUM('unpaid','paid','complimentary') NOT NULL DEFAULT 'unpaid',
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client_id (client_id),
    KEY idx_visit_date (visit_date),
    CONSTRAINT fk_visit_client
        FOREIGN KEY (client_id) REFERENCES clients (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Katalog produktů / ceník ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS price_list_items (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    title         VARCHAR(200)     NOT NULL,
    category      VARCHAR(80)      DEFAULT NULL,
    series        VARCHAR(100)     DEFAULT NULL,
    default_price DECIMAL(8,2)     DEFAULT NULL,
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_title (title),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Ukázková data ────────────────────────────────────────────────────────────
INSERT INTO price_list_items (title, category, default_price) VALUES
-- Barvy
('Igora Royal 1-0 Přírodní Černá', 'Barva', NULL),
('Igora Royal 3-0 Přírodní Tmavě Hnědá', 'Barva', NULL),
('Igora Royal 4-0 Přírodní Středně Hnědá', 'Barva', NULL),
('Igora Royal 5-0 Přírodní Světle Hnědá', 'Barva', NULL),
('Igora Royal 6-0 Přírodní Tmavě Plavá', 'Barva', NULL),
('Igora Royal 7-0 Přírodní Středně Plavá', 'Barva', NULL),
('Igora Royal 8-0 Přírodní Světle Plavá', 'Barva', NULL),
('Igora Royal 9-0 Přírodní Extra Světle Plavá', 'Barva', NULL),
('Igora Royal 10-0 Přírodní Ultra Světle Plavá', 'Barva', NULL),
('Igora Royal 0-11 Popelavá intenzita', 'Barva', NULL),
('Igora Royal 0-22 Perlová intenzita', 'Barva', NULL),
('Igora Royal 0-33 Zlatá intenzita', 'Barva', NULL),
('Igora Royal 0-44 Měděná intenzita', 'Barva', NULL),
('Igora Royal 0-55 Mahagonová intenzita', 'Barva', NULL),
('Igora Royal 0-77 Hnědá intenzita', 'Barva', NULL),
('Igora Royal 0-88 Červená intenzita', 'Barva', NULL),
('Igora Royal 0-99 Fialová intenzita', 'Barva', NULL),
-- Oxidanty
('Igora Royal Oil Developer 1,9%', 'Oxidant', NULL),
('Igora Royal Oil Developer 3%', 'Oxidant', NULL),
('Igora Royal Oil Developer 6%', 'Oxidant', NULL),
('Igora Royal Oil Developer 9%', 'Oxidant', NULL),
('Igora Royal Oil Developer 12%', 'Oxidant', NULL),
('Welloxon Perfect 1,9%', 'Oxidant', NULL),
('Welloxon Perfect 4%', 'Oxidant', NULL),
('Welloxon Perfect 6%', 'Oxidant', NULL),
('Welloxon Perfect 9%', 'Oxidant', NULL),
('Welloxon Perfect 12%', 'Oxidant', NULL),
-- Blondy / odbarvení
('Blondor Freelights', 'Odbarvení', NULL),
('Blondor Multi Blonde Powder', 'Odbarvení', NULL),
('BlondMe Premium Lightener 9+', 'Odbarvení', NULL),
('Igora Vario Blond Plus', 'Odbarvení', NULL),
-- Tonery
('Igora Vibrance 9,5-19 Speciální Blond', 'Toner', NULL),
('Igora Vibrance 10-19 Ultra Světle Plavá Popelavá', 'Toner', NULL),
('Igora Vibrance 9-19 Extra Světle Plavá Popelavá', 'Toner', NULL),
('Koleston Perfect Innosense 8/81', 'Toner', NULL),
('Koleston Perfect Innosense 9/81', 'Toner', NULL),
('Koleston Perfect Innosense 10/81', 'Toner', NULL),
-- Ošetření
('Olaplex No. 1 Bond Multiplier', 'Ošetření', NULL),
('Olaplex No. 2 Bond Perfector', 'Ošetření', NULL),
('Fibreplex No. 1', 'Ošetření', NULL),
('Fibreplex No. 2', 'Ošetření', NULL);

-- ─── Nastavení aplikace ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key   VARCHAR(50)  NOT NULL,
    setting_value TEXT         DEFAULT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
