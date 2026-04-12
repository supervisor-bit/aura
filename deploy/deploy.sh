#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# AURA — Deploy skript pro nasazení na Synology NAS
# ═══════════════════════════════════════════════════════════════
#
# Použití:
#   ./deploy.sh               — vytvoří archiv aura-deploy.tar.gz
#   ./deploy.sh --upload      — vytvoří archiv a nahraje přes SCP
#
# Před použitím --upload nastav:
SYNOLOGY_HOST="192.168.1.61"    # IP nebo hostname NAS (lokální síť)
SYNOLOGY_USER="hairdresser"   # SSH uživatel
SYNOLOGY_PORT=33              # SSH port
SYNOLOGY_PATH="/volume1/web/aura"
# ═══════════════════════════════════════════════════════════════

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_DIR="$SCRIPT_DIR/build"
ARCHIVE="$SCRIPT_DIR/aura-deploy.tar.gz"

echo "🔨 Příprava deploy balíčku..."

# Vyčistit build složku
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/aura"

# Kopírovat soubory aplikace
cp "$PROJECT_DIR/index.php"    "$BUILD_DIR/aura/"
cp "$PROJECT_DIR/router.php"   "$BUILD_DIR/aura/"
cp "$PROJECT_DIR/sw.js"        "$BUILD_DIR/aura/"

# Produkční config (přepíše vývojový)
cp "$SCRIPT_DIR/config.php"    "$BUILD_DIR/aura/"

# .htaccess pro Apache
cp "$SCRIPT_DIR/.htaccess"     "$BUILD_DIR/aura/"

# Složky
cp -r "$PROJECT_DIR/controllers" "$BUILD_DIR/aura/"
cp -r "$PROJECT_DIR/models"      "$BUILD_DIR/aura/"
cp -r "$PROJECT_DIR/views"       "$BUILD_DIR/aura/"
cp -r "$PROJECT_DIR/public"      "$BUILD_DIR/aura/"

# SQL schéma
mkdir -p "$BUILD_DIR/aura/sql"
cp "$SCRIPT_DIR/schema.sql"    "$BUILD_DIR/aura/sql/"

# Odstranit dev soubory
rm -f "$BUILD_DIR/aura"/*.csv
rm -f "$BUILD_DIR/aura"/*.csv.bak

# Vytvořit archiv
cd "$BUILD_DIR"
tar -czf "$ARCHIVE" aura/

# Úklid
rm -rf "$BUILD_DIR"

echo "✅ Archiv vytvořen: $ARCHIVE"
echo "   Velikost: $(du -h "$ARCHIVE" | cut -f1)"

# Upload na Synology
if [[ "$1" == "--upload" ]]; then
    if [[ -z "$SYNOLOGY_HOST" ]]; then
        echo "❌ Nastav SYNOLOGY_HOST v deploy.sh"
        exit 1
    fi
    echo ""
    echo "📦 Nahrávám na $SYNOLOGY_HOST:$SYNOLOGY_PATH ..."
    scp -O -P $SYNOLOGY_PORT "$ARCHIVE" "$SYNOLOGY_USER@$SYNOLOGY_HOST:/tmp/aura-deploy.tar.gz"
    ssh -p $SYNOLOGY_PORT "$SYNOLOGY_USER@$SYNOLOGY_HOST" "
        cd /tmp &&
        tar -xzf aura-deploy.tar.gz 2>/dev/null &&
        mkdir -p $SYNOLOGY_PATH &&
        cp -r aura/* aura/.htaccess $SYNOLOGY_PATH/ &&
        rm -rf /tmp/aura /tmp/aura-deploy.tar.gz
    "
    echo "✅ Nasazeno na $SYNOLOGY_HOST:$SYNOLOGY_PATH"
    echo ""
    echo "📋 Zbývá:"
    echo "   1. Vytvořit DB 'aura' v phpMyAdmin na Synology"
    echo "   2. Importovat sql/schema.sql"
    echo "   3. Upravit config.php (DB heslo)"
fi

echo ""
echo "═══════════════════════════════════════════════════════"
echo " POSTUP NASAZENÍ NA SYNOLOGY:"
echo "═══════════════════════════════════════════════════════"
echo ""
echo " 1. Rozbal aura-deploy.tar.gz do /volume1/web/aura"
echo ""
echo " 2. MariaDB — vytvořit databázi a uživatele:"
echo "    CREATE DATABASE aura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "    CREATE USER 'aura'@'localhost' IDENTIFIED BY 'TVOJE_HESLO';"
echo "    GRANT ALL ON aura.* TO 'aura'@'localhost';"
echo ""
echo " 3. Importovat schéma:"
echo "    mysql -u aura -p aura < /volume1/web/aura/sql/schema.sql"
echo ""
echo " 4. Upravit /volume1/web/aura/config.php:"
echo "    - DB_PASS → nastavit heslo"
echo "    - DB_SOCKET → ověřit cestu (zkus /run/mysqld/mysqld10.sock)"
echo ""
echo " 5. Web Station → Virtual Host nebo web/aura složka"
echo "    - PHP 8.0+ s rozšířeními: pdo_mysql, json, gd, mbstring"
echo ""
echo " 6. Při prvním spuštění se zobrazí formulář"
echo "    pro vytvoření přihlašovacích údajů"
echo "═══════════════════════════════════════════════════════"
