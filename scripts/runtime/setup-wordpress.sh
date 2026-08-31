#!/usr/bin/env bash
set -eo pipefail

echo "=========================================================="
echo "APEX SEO - WORDPRESS RUNTIME ENVIRONMENT BOOTSTRAP"
echo "=========================================================="

WP_PATH="/var/www/html"
WP_CLI="wp --allow-root --path=${WP_PATH}"

# 1. Wait for MySQL to be fully ready
echo "[1/7] Waiting for MySQL database readiness..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h db -u wp_test_user -pwp_test_pass_123! --silent; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "ERROR: Database connection timed out after $MAX_TRIES seconds."
        exit 1
    fi
    echo "Waiting for database (${COUNT}/${MAX_TRIES})..."
    sleep 2
done
echo "Database is ready!"

# 2. Check if WordPress core is installed
echo "[2/7] Checking WordPress Core Installation..."
if ! $WP_CLI core is-installed 2>/dev/null; then
    echo "Installing WordPress Core..."
    $WP_CLI core install \
        --url="http://localhost:8080" \
        --title="Apex SEO Runtime Testbed" \
        --admin_user="apex_admin" \
        --admin_password="AdminPassword123!" \
        --admin_email="admin@apexseo.local" \
        --skip-email
    echo "WordPress Core installed successfully."
else
    echo "WordPress Core is already installed."
fi

# 3. Configure Permalinks & Settings
echo "[3/7] Configuring Permalinks and Core Options..."
$WP_CLI rewrite structure '/%postname%/' --hard
$WP_CLI option update blogdescription "Production-Grade WordPress SEO Runtime Validation Site"
$WP_CLI option update timezone_string "UTC"

# 4. Create Standard and Test Roles / Users & REST Auth
echo "[4/7] Setting up Test Users and Application Passwords..."
if ! $WP_CLI user get editor_user --field=ID 2>/dev/null; then
    $WP_CLI user create editor_user editor@apexseo.local \
        --role=editor \
        --user_pass="EditorPassword123!" \
        --display_name="Apex Test Editor"
fi

# Generate Application Password for apex_admin for reliable REST API authentication
APP_PASS=$($WP_CLI user application-password create apex_admin "ApexCiTest" --porcelain 2>/dev/null || echo "")
if [ -n "$APP_PASS" ]; then
    echo "apex_admin:$APP_PASS" > /tmp/apex_admin_auth
    echo "Application Password generated successfully for apex_admin."
else
    echo "apex_admin:AdminPassword123!" > /tmp/apex_admin_auth
fi

# 5. Install / Update Composer dependencies in plugin
echo "[5/7] Installing Plugin Composer Dependencies..."
if [ -d "/var/www/html/wp-content/plugins/apexseo" ]; then
    cd /var/www/html/wp-content/plugins/apexseo
    composer install --no-interaction --prefer-dist
    cd /var/www/html
fi

# 6. Activate APEX SEO Plugin
echo "[6/7] Activating APEX SEO Plugin..."
$WP_CLI plugin activate apexseo

if ! $WP_CLI plugin is-active apexseo; then
    echo "ERROR: APEX SEO plugin failed to activate!"
    exit 1
fi
echo "Plugin APEX SEO is active and verified."

# 7. Verify Database Tables Created by Activation
echo "[7/7] Verifying APEX Relational Schema..."
TABLES=$($WP_CLI db query "SHOW TABLES LIKE 'wp_apex_%';" --skip-column-names)
echo "Discovered APEX Tables:"
echo "$TABLES"

echo "=========================================================="
echo "WORDPRESS RUNTIME INITIALIZATION COMPLETE"
echo "=========================================================="
