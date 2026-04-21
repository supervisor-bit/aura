SET NAMES utf8mb4;

-- Štítky ke klientkám (tag IDs: 6=Blond, 7=Stálý, 8=Alergie, 9=VIP, 10=Nový)
INSERT INTO client_tags (client_id, tag_id) VALUES
(1, 7), (1, 9),
(2, 10),
(3, 8),
(4, 6), (4, 7),
(6, 6),
(7, 7),
(10, 6), (10, 9);

-- Poznámky
INSERT INTO client_notes (client_id, content, created_at) VALUES
(1, 'Domluvena na příští návštěvu koncem dubna', '2026-04-05 14:30:00'),
(3, 'Upozornit na novou řadu Metal Detox', '2026-03-20 10:00:00'),
(4, 'Naposledy chtěla studenější tón, příště zkusit 7.1', '2026-04-01 16:00:00'),
(8, 'Volala, vrátí se v červenci', '2026-03-15 11:00:00');

-- Návštěvy s recepturami

-- Jana Nováková
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(1, '2026-02-10', 'Barvení + střih + foukání',
 '{"actions":["Odrost","Délky"],"bowls":[{"label":"Miska 1","color":"#e53935","products":[{"name":"Inoa 6.0","amount":40},{"name":"Inoa 6.3","amount":20}],"oxidant":{"name":"Oxidant Inoa Oleo Vyvíječ 6% (20 Vol)","ratio":"1:1","amount":60}}],"note":"30 min působení"}',
 'Spokojená s výsledkem', 2200.00, 'paid', 2200.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(1, '2026-03-24', 'Odrost + toner + střih',
 '{"actions":["Odrost","Toner"],"bowls":[{"label":"Miska 1","color":"#e53935","products":[{"name":"Inoa 6.0","amount":30},{"name":"Inoa 6.13","amount":30}],"oxidant":{"name":"Oxidant Inoa Oleo Vyvíječ 6% (20 Vol)","ratio":"1:1","amount":60}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 9.12","amount":50}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Odrost 35 min, toner 15 min"}',
 NULL, 2500.00, 'paid', 2500.00);

-- Petra Svobodová
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(2, '2026-01-15', 'Kompletní barvení',
 '{"actions":["Odrost","Délky"],"bowls":[{"label":"Miska 1","color":"#e53935","products":[{"name":"Majirel 5.0","amount":40},{"name":"Majirel 5.52","amount":20}],"oxidant":{"name":"Oxidant Majirel Ox. Krém 6%","ratio":"1:1.5","amount":90}}],"note":""}',
 'Přechod na tmavší odstín', 1800.00, 'paid', 1800.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(2, '2026-03-05', 'Melír + toner + foukání',
 '{"actions":["Melír","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":30}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 9% (30 Vol)","ratio":"1:2","amount":60}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 10.12","amount":50}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Folky celá hlava"}',
 NULL, 3200.00, 'paid', 3500.00);

-- Lucie Dvořáková
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(3, '2026-02-28', 'Barvení + Metal Detox',
 '{"actions":["Odrost"],"bowls":[{"label":"Miska 1","color":"#e53935","products":[{"name":"Inoa 7.0","amount":30},{"name":"Inoa 7.35","amount":30}],"oxidant":{"name":"Oxidant Inoa Oleo Vyvíječ 6% (20 Vol)","ratio":"1:1","amount":60}}],"note":"Metal Detox pred barvením"}',
 'Vlasy v dobrém stavu', 2000.00, 'paid', 2000.00);

-- Eva Černá
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(4, '2026-01-20', 'Melír + střih',
 '{"actions":["Melír"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":40}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 6% (20 Vol)","ratio":"1:2","amount":80}}],"note":"Jemné proužky kolem obličeje"}',
 NULL, 2800.00, 'paid', 3000.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(4, '2026-03-03', 'Melír + toner + foukání',
 '{"actions":["Melír","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":35}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 6% (20 Vol)","ratio":"1:2","amount":70}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 9.13","amount":40},{"name":"DIALight 10.12","amount":10}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Folky půlka hlavy + toner"}',
 'Příště chce studenější tón', 3500.00, 'paid', 3500.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(4, '2026-04-10', 'Melír + toner',
 '{"actions":["Melír","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":35}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 6% (20 Vol)","ratio":"1:2","amount":70}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 9.11","amount":50}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Studený tón dle přání"}',
 NULL, 3500.00, 'unpaid', NULL);

-- Markéta Procházková
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(5, '2026-03-18', 'Střih + foukání', NULL, 'Jen střih, bez barvení', 800.00, 'paid', 800.00);

-- Kateřina Veselá
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(6, '2026-02-05', 'Balayage + toner',
 '{"actions":["Balayage","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":50}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 6% (20 Vol)","ratio":"1:2","amount":100}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 9.13","amount":30},{"name":"DIALight 10.12","amount":20}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Ručně malované prameny"}',
 'Krásný přirozený výsledek', 4000.00, 'paid', 4000.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(6, '2026-04-08', 'Refresh balayage + toner',
 '{"actions":["Balayage","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":30}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 6% (20 Vol)","ratio":"1:2","amount":60}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 9.13","amount":50}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Refresh konečků"}',
 NULL, 3500.00, 'paid', 3500.00);

-- Tereza Horáková
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(7, '2026-03-10', 'Střih + foukání', NULL, 'Pixie cut refresh', 600.00, 'paid', 600.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(7, '2026-04-07', 'Střih + žehlení', NULL, 'Krátký bob', 700.00, 'paid', 700.00);

-- Simona Pokorná
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(9, '2026-03-28', 'Barvení + střih',
 '{"actions":["Odrost","Délky"],"bowls":[{"label":"Miska 1","color":"#e53935","products":[{"name":"Majirel 4.0","amount":30},{"name":"Majirel 4.56","amount":20},{"name":"Majirel 5.0","amount":10}],"oxidant":{"name":"Oxidant Majirel Ox. Krém 6%","ratio":"1:1.5","amount":90}}],"note":"35 min"}',
 NULL, 1900.00, 'paid', 2000.00);

-- Barbora Němcová
INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(10, '2026-02-20', 'Melír + balayage + toner',
 '{"actions":["Melír","Balayage","Toner"],"bowls":[{"label":"Miska 1","color":"#fdd835","products":[{"name":"Blond Studio Platinum Plus-7","amount":45}],"oxidant":{"name":"Oxidant Blond Studio Oleo Vyvíječ 9% (30 Vol)","ratio":"1:2","amount":90}},{"label":"Miska 2","color":"#1e88e5","products":[{"name":"DIALight 10.12","amount":30},{"name":"DIALight 9.13","amount":20}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Babylights + balayage"}',
 'Nadšená, sdílela na IG', 4500.00, 'paid', 4500.00);

INSERT INTO client_visits (client_id, visit_date, service_name, color_formula, note, price, billing_status, billing_amount) VALUES
(10, '2026-04-05', 'Toner refresh',
 '{"actions":["Toner"],"bowls":[{"label":"Miska 1","color":"#1e88e5","products":[{"name":"DIALight 10.12","amount":50}],"oxidant":{"name":"Oxidant DIA Vyvíječ 9 Vol (2.7%)","ratio":"1:1.5","amount":75}}],"note":"Jen osvěžení tónu"}',
 NULL, 1200.00, 'paid', 1200.00);
