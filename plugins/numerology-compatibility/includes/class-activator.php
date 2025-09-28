<?php
namespace NC;

class Activator {

    /**
     * Plugin activation - упрощенная версия для отладки
     */
    public static function activate() {
        error_log('NC Activator: Starting activation...');

        // 1. Проверка версии PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(NC_PLUGIN_FILE));
            wp_die('This plugin requires PHP 7.4 or higher.');
        }
        error_log('NC Activator: PHP version check passed');

        // 2. Создание таблиц - упрощенная версия
        self::create_tables();
        error_log('NC Activator: Tables created');

        // 3. Установка базовых опций
        self::set_default_options();
        error_log('NC Activator: Options set');

        // 4. Создание директорий
        self::create_directories();
        error_log('NC Activator: Directories created');

        // 5. Flush rewrite rules
        flush_rewrite_rules();
        error_log('NC Activator: Rewrite rules flushed');

        // 6. Установка флага для редиректа
        set_transient('nc_activation_redirect', true, 30);
        error_log('NC Activator: Activation completed successfully');
    }

    /**
     * Создание таблиц
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Простая таблица для тестирования
        $table_name = $wpdb->prefix . 'nc_calculations';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            calculation_id varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Сохраняем версию БД
        update_option('nc_db_version', '1.0.0');
    }

    /**
     * Установка опций по умолчанию
     */
    private static function set_default_options() {
        $defaults = [
            'nc_environment' => 'production',
            'nc_api_url' => 'https://api.your-domain.com',
            'nc_require_auth' => 1,
            'nc_currency' => 'USD',
            'nc_price_light' => 19,
            'nc_price_pro' => 49
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Создание необходимых директорий
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();

        $directories = [
            $upload_dir['basedir'] . '/numerology-compatibility',
            $upload_dir['basedir'] . '/nc-logs'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                error_log('NC Activator: Created directory - ' . $dir);
            }
        }

        // Защита директории логов
        $htaccess = $upload_dir['basedir'] . '/nc-logs/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'Deny from all');
        }
    }
}