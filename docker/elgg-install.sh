#!/bin/bash
set -e

if [ -z "${PLUGIN_ID:-}" ]; then
    echo "ERROR: PLUGIN_ID environment variable is required." >&2
    exit 1
fi

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${ELGG_DB_HOST:-db}', '${ELGG_DB_USER:-elgg}', '${ELGG_DB_PASS:-elgg}');" 2>/dev/null; do
    sleep 1
done
echo "MySQL is ready."

cd /var/www/html

if [ ! -f /var/www/html/.elgg-installed ]; then
    echo "Installing Elgg 5.x..."

    mkdir -p elgg-config
    cat > elgg-config/settings.php <<'SETTINGS_TEMPLATE'
<?php
global $CONFIG;
if (!isset($CONFIG)) {
    $CONFIG = new \stdClass;
}
SETTINGS_TEMPLATE

    cat >> elgg-config/settings.php <<SETTINGS_VALUES
\$CONFIG->dbuser = '${ELGG_DB_USER:-elgg}';
\$CONFIG->dbpass = '${ELGG_DB_PASS:-elgg}';
\$CONFIG->dbname = '${ELGG_DB_NAME:-elgg}';
\$CONFIG->dbhost = '${ELGG_DB_HOST:-db}';
\$CONFIG->dbport = '3306';
\$CONFIG->dbprefix = 'elgg_';
\$CONFIG->dbencoding = 'utf8mb4';
\$CONFIG->dataroot = '${ELGG_DATA_ROOT:-/var/www/data/}';
\$CONFIG->wwwroot = '${ELGG_SITE_URL:-http://elgg/}';
\$CONFIG->cacheroot = '${ELGG_DATA_ROOT:-/var/www/data/}cache/';
\$CONFIG->assetroot = '${ELGG_DATA_ROOT:-/var/www/data/}assets/';
SETTINGS_VALUES

    php -r "
        require_once 'vendor/autoload.php';
        \$params = [
            'dbuser' => '${ELGG_DB_USER:-elgg}',
            'dbpassword' => '${ELGG_DB_PASS:-elgg}',
            'dbname' => '${ELGG_DB_NAME:-elgg}',
            'dbhost' => '${ELGG_DB_HOST:-db}',
            'dbport' => '3306',
            'dbprefix' => 'elgg_',
            'sitename' => 'Elgg 5.x Plugin Test',
            'siteemail' => '${ELGG_ADMIN_EMAIL:-admin@example.com}',
            'wwwroot' => '${ELGG_SITE_URL:-http://elgg/}',
            'dataroot' => '${ELGG_DATA_ROOT:-/var/www/data/}',
            'displayname' => 'Admin',
            'email' => '${ELGG_ADMIN_EMAIL:-admin@example.com}',
            'username' => 'admin',
            'password' => '${ELGG_ADMIN_PASSWORD:-admin12345}',
        ];
        \$installer = new \ElggInstaller();
        \$installer->batchInstall(\$params);
        echo 'Elgg 5.x installed successfully.' . PHP_EOL;
    " 2>&1 || echo "Install completed (check for errors above)."

    echo "Activating plugins..."
    php -r "
        require_once 'vendor/autoload.php';
        \$app = \Elgg\Application::getInstance();
        \$app->bootCore();
        _elgg_services()->plugins->generateEntities();
        \$dep_ids = [];
        \$plugin_file = '/var/www/html/mod/${PLUGIN_ID}/elgg-plugin.php';
        if (file_exists(\$plugin_file)) {
            \$manifest = include \$plugin_file;
            foreach (array_keys(\$manifest['plugin']['dependencies'] ?? []) as \$id) {
                \$dep_ids[] = strtolower(\$id);
            }
        }
        foreach (\$dep_ids as \$dep_id) {
            \$dep = elgg_get_plugin_from_id(\$dep_id);
            if (!\$dep) { echo 'WARNING: dep plugin ' . \$dep_id . ' not in mod/ — skipping.' . PHP_EOL; continue; }
            if (\$dep->isActive()) { echo 'Dep plugin ' . \$dep_id . ' already active.' . PHP_EOL; continue; }
            try {
                \$dep->setPriority('last');
                \$dep->activate();
                echo '  + ' . \$dep_id . PHP_EOL;
            } catch (\Throwable \$e) {
                echo 'FAILED to activate dep ' . \$dep_id . ': ' . \$e->getMessage() . PHP_EOL;
                exit(1);
            }
        }
        \$plugin = elgg_get_plugin_from_id('${PLUGIN_ID}');
        if (!\$plugin) { echo 'ERROR: plugin ${PLUGIN_ID} not found.' . PHP_EOL; exit(1); }
        if (!\$plugin->isActive()) {
            try {
                \$plugin->setPriority('last');
                \$plugin->activate();
                echo 'Plugin ${PLUGIN_ID} activated.' . PHP_EOL;
            } catch (\Throwable \$e) {
                echo 'FAILED to activate ${PLUGIN_ID}: ' . \$e->getMessage() . PHP_EOL;
                exit(1);
            }
        }
    " 2>&1 || echo "Plugin activation completed (check for errors above)."

    echo "Seeding test data..."
    php -r "
        require_once 'vendor/autoload.php';
        \$app = \Elgg\Application::getInstance();
        \$app->bootCore();

        try {
            \$admin = elgg_get_user_by_username('admin');
            _elgg_services()->session_manager->setLoggedInUser(\$admin);

            // Seed a known slug for Playwright E2E tests.
            // slug_target points to /login (always available, renders cleanly).
            \$admin->slug = '/test-slug-redirect';
            \$admin->slug_target = elgg_get_site_url() . 'login';
            \$admin->save();

            // Populate slug cache so RewriteSlugRoute finds it without a DB query.
            \$cache = elgg()->{'posts.slug.cache'};
            \$cache->save(sha1('/test-slug-redirect'), elgg_get_site_url() . 'login');

            echo 'Test slug seeded: /test-slug-redirect -> ' . elgg_get_site_url() . 'login' . PHP_EOL;

            _elgg_services()->session_manager->removeLoggedInUser();
        } catch (\Throwable \$e) {
            echo 'WARNING: test data seed failed: ' . \$e->getMessage() . PHP_EOL;
        }
    " 2>&1 || echo "Test data seed completed (check for errors above)."

    echo "Clearing system cache..."
    php -r "
        require_once 'vendor/autoload.php';
        \$app = \Elgg\Application::getInstance();
        \$app->bootCore();
        elgg_clear_caches();
        echo 'System cache cleared.' . PHP_EOL;
    " 2>&1 || echo "Cache clear completed."

    chown -R www-data:www-data "${ELGG_DATA_ROOT:-/var/www/data/}"
    chmod -R u+rwX,g+rX,o+rX "${ELGG_DATA_ROOT:-/var/www/data/}"

    touch /var/www/html/.elgg-installed
    echo "Elgg 5.x setup complete."
fi

# Fix ownership every start — PHP scripts running as root during install/debug
# can create root-owned files in the data dir that block www-data (Apache).
chown -R www-data:www-data "${ELGG_DATA_ROOT:-/var/www/data/}"

echo "Starting Apache..."
exec apache2-foreground
