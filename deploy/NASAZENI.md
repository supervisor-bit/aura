# AURA – Nasazení na Synology DS716+

## Požadavky

- **Synology DS716+** s DSM 7+
- **Web Station** (nainstalovat z Package Center)
- **PHP 8.0+** (nainstalovat z Package Center → Web Station → Script Language Settings)
- **MariaDB 10** (nainstalovat z Package Center)

---

## 1. Vytvoření deploy archivu

Na vývojovém Macu spusť:

```bash
cd ~/Developer/Aura\ Code
bash deploy/deploy.sh
```

Vytvoří se `deploy/aura-deploy.tar.gz` (~136 KB).

### Automatický upload na Synology

```bash
bash deploy/deploy.sh --upload
```

Nahraje archiv přes SCP a rozbalí do `/volume1/web/aura`.  
Vyžaduje SSH přístup na `192.168.1.61` jako `admin`.

---

## 2. Ruční nasazení (bez --upload)

### 2.1 Nahrání souborů

1. Zkopíruj `aura-deploy.tar.gz` na Synology (File Station, SCP, nebo SMB)
2. Připoj se přes SSH:
   ```bash
   ssh admin@192.168.1.61
   ```
3. Rozbal archiv:
   ```bash
   cd /tmp
   tar -xzf aura-deploy.tar.gz
   sudo mkdir -p /volume1/web/aura
   sudo cp -r aura/* /volume1/web/aura/
   sudo chown -R http:http /volume1/web/aura
   sudo chmod -R 755 /volume1/web/aura
   rm -rf /tmp/aura /tmp/aura-deploy.tar.gz
   ```

---

## 3. Nastavení MariaDB

### 3.1 Přihlášení do MariaDB

```bash
# Přes socket (preferováno)
mysql -u root -p -S /run/mysqld/mysqld10.sock

# Nebo přes TCP
mysql -u root -p -h 127.0.0.1 -P 3307
```

### 3.2 Vytvoření databáze a uživatele

```sql
CREATE DATABASE aura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'aura'@'localhost' IDENTIFIED BY 'TVOJE_HESLO';
GRANT ALL PRIVILEGES ON aura.* TO 'aura'@'localhost';
FLUSH PRIVILEGES;
```

### 3.3 Import schématu

```bash
mysql -u aura -p -S /run/mysqld/mysqld10.sock aura < /volume1/web/aura/sql/schema.sql
```

> **Poznámka:** Schéma obsahuje výchozí katalog produktů L'Oréal (339 položek).  
> Tabulky se vytvoří jen pokud neexistují (`CREATE TABLE IF NOT EXISTS`).

---

## 4. Konfigurace PHP

### 4.1 Web Station – PHP profil

V DSM → **Web Station** → **Script Language Settings** → PHP 8.0+:

Povinná rozšíření:
- `pdo_mysql`
- `json`
- `mbstring`
- `gd` (volitelné, pro budoucí použití)

### 4.2 Úprava config.php

Otevři `/volume1/web/aura/config.php` a nastav heslo DB:

```php
define('DB_PASS', 'TVOJE_HESLO');   // ← stejné heslo jako v kroku 3.2
```

Výchozí připojení:
- **Socket:** `/run/mysqld/mysqld10.sock` (primární)
- **TCP fallback:** `127.0.0.1:3307` (automaticky, pokud socket nefunguje)

---

## 5. Nastavení Web Station

### Varianta A: Podsložka `/aura/` (doporučeno)

Pokud máš Web Station nastavenou na `/volume1/web`, aplikace bude dostupná na:

```
http://192.168.1.61/aura/
```

`.htaccess` v balíčku je nakonfigurovaný s `RewriteBase /aura/`.

Ověř, že v **Web Station** → **General Settings**:
- HTTP backend server: **Apache** (nutné pro mod_rewrite / .htaccess)

### Varianta B: Virtual Host

V **Web Station** → **Web Service Portal** → Vytvořit:
- **Service:** Apache s PHP 8.0+ profilem
- **Document root:** `/volume1/web/aura`
- **Port / hostname:** dle potřeby

Pokud aplikace běží v kořeni virtual hostu, uprav v `config.php`:
```php
define('BASE_URL', '/');
```
a v `.htaccess`:
```
RewriteBase /
```

---

## 6. První spuštění

1. Otevři v prohlížeči `http://192.168.1.61/aura/`
2. Zobrazí se formulář pro **vytvoření přihlašovacích údajů** (PIN/heslo)
3. Po nastavení se zobrazí přihlašovací obrazovka
4. Aplikace je PWA — možno přidat na plochu přes prohlížeč

---

## 7. Aktualizace aplikace

Na Macu:

```bash
cd ~/Developer/Aura\ Code
bash deploy/deploy.sh --upload
```

Nebo ručně:
```bash
bash deploy/deploy.sh
# Zkopíruj aura-deploy.tar.gz na Synology a rozbal do /volume1/web/aura
```

> Data v databázi se aktualizací **nezmění** — deploy přepisuje jen PHP/JS/CSS soubory.

---

## Troubleshooting

| Problém | Řešení |
|---------|--------|
| 403 Forbidden | `sudo chown -R http:http /volume1/web/aura && sudo chmod -R 755 /volume1/web/aura` |
| 404 na podstránkách | Ověř, že Apache má povolený mod_rewrite a `.htaccess` je v `/volume1/web/aura/` |
| DB connection refused | Ověř socket cestu: `ls -la /run/mysqld/mysqld10.sock`. Pokud neexistuje, použij TCP (config.php automaticky fallbackuje) |
| Bílá stránka | SSH: `tail -f /var/log/httpd/error_log` nebo zapni dočasně `display_errors` v config.php |
| Import schema selhává | Schéma používá `aura_v2` jako DB jméno → buď vytvoř DB jako `aura_v2`, nebo uprav řádek `USE aura_v2;` v schema.sql na `USE aura;` |

---

## Síťové údaje

| Parametr | Hodnota |
|----------|---------|
| IP Synology | `192.168.1.61` |
| SSH uživatel | `admin` |
| Web root | `/volume1/web/aura` |
| URL aplikace | `http://192.168.1.61/aura/` |
| MariaDB port | `3307` |
| MariaDB socket | `/run/mysqld/mysqld10.sock` |
| DB jméno | `aura` |
| DB uživatel | `aura` |
