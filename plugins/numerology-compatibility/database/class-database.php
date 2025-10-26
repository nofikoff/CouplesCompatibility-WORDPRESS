<?php
// plugins/numerology-compatibility/database/class-database.php
namespace NC\Database;

class Database {

	private static $instance = null;
	private $wpdb;
	private $tables = [];

	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->init_table_names();
	}

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function init_table_names() {
		$this->tables = [
			'calculations' => $this->wpdb->prefix . 'nc_calculations',
			'consents' => $this->wpdb->prefix . 'nc_consents',
			'api_usage' => $this->wpdb->prefix . 'nc_api_usage',
			'transactions' => $this->wpdb->prefix . 'nc_transactions',
			'analytics' => $this->wpdb->prefix . 'nc_analytics',
			'error_logs' => $this->wpdb->prefix . 'nc_error_logs'
		];
	}

	/**
	 * Create database tables
	 */
	public function create_tables() {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $this->wpdb->get_charset_collate();

		// Calculations table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['calculations']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            calculation_id varchar(255) NOT NULL,
            package_type varchar(50) NOT NULL DEFAULT 'free',
            person1_date date NOT NULL,
            person2_date date NOT NULL,
            person1_name varchar(255) DEFAULT NULL,
            person2_name varchar(255) DEFAULT NULL,
            person1_time time DEFAULT NULL,
            person2_time time DEFAULT NULL,
            person1_place varchar(255) DEFAULT NULL,
            person2_place varchar(255) DEFAULT NULL,
            result_summary longtext DEFAULT NULL,
            pdf_sent tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY calculation_id (calculation_id),
            KEY package_type (package_type),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// Consents table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['consents']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            consent_type varchar(50) NOT NULL,
            consent_value tinyint(1) NOT NULL DEFAULT 0,
            consent_text text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY consent_type (consent_type),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// API usage table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['api_usage']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) DEFAULT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            status_code int(3) DEFAULT NULL,
            response_time int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY endpoint (endpoint),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// Transactions table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['transactions']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            calculation_id varchar(255) DEFAULT NULL,
            gateway_payment_id varchar(255) DEFAULT NULL COMMENT 'Payment ID from gateway (Stripe, PayPal, etc)',
            package_type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            status varchar(50) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) DEFAULT NULL COMMENT 'Gateway name: stripe, paypal, etc',
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY calculation_id (calculation_id),
            KEY gateway_payment_id (gateway_payment_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// Analytics table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['analytics']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            event_type varchar(100) NOT NULL,
            event_data longtext DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY session_id (session_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// Error logs table
		$sql = "CREATE TABLE IF NOT EXISTS {$this->tables['error_logs']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) DEFAULT NULL,
            error_type varchar(100) NOT NULL,
            error_message text NOT NULL,
            error_details longtext DEFAULT NULL,
            file varchar(500) DEFAULT NULL,
            line int(11) DEFAULT NULL,
            url varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY error_type (error_type),
            KEY created_at (created_at)
        ) $charset_collate;";
		dbDelta($sql);

		// Store database version
		update_option('nc_db_version', '1.0.0');
	}

	/**
	 * Drop all tables
	 */
	public function drop_tables() {
		foreach ($this->tables as $table) {
			$this->wpdb->query("DROP TABLE IF EXISTS $table");
		}
		delete_option('nc_db_version');
	}

	/**
	 * Get table name
	 */
	public function get_table($name) {
		return $this->tables[$name] ?? null;
	}

	/**
	 * Track API usage
	 */
	public function track_api_usage($data) {
		return $this->wpdb->insert(
			$this->tables['api_usage'],
			[
				'email' => $data['email'] ?? null,
				'endpoint' => $data['endpoint'],
				'method' => $data['method'],
				'status_code' => $data['status_code'] ?? null,
				'response_time' => $data['response_time'] ?? null,
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%d', '%d', '%s', '%s']
		);
	}

	/**
	 * Log error
	 */
	public function log_error($error_data) {
		return $this->wpdb->insert(
			$this->tables['error_logs'],
			[
				'email' => $error_data['email'] ?? null,
				'error_type' => $error_data['type'],
				'error_message' => $error_data['message'],
				'error_details' => json_encode($error_data['details'] ?? []),
				'file' => $error_data['file'] ?? null,
				'line' => $error_data['line'] ?? null,
				'url' => $_SERVER['REQUEST_URI'] ?? null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
		);
	}

	/**
	 * Track analytics event
	 */
	public function track_event($event_type, $event_data = [], $email = null) {
		if (!session_id()) {
			session_start();
		}

		$session_id = $_SESSION['nc_session_id'] ?? wp_generate_uuid4();
		$_SESSION['nc_session_id'] = $session_id;

		return $this->wpdb->insert(
			$this->tables['analytics'],
			[
				'email' => $email,
				'session_id' => $session_id,
				'event_type' => $event_type,
				'event_data' => json_encode($event_data),
				'page_url' => $_SERVER['REQUEST_URI'] ?? null,
				'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
		);
	}

	/**
	 * Get statistics
	 */
	public function get_statistics() {
		$stats = [];

		// Total calculations
		$stats['total_calculations'] = $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->tables['calculations']}"
		);

		// Unique emails
		$stats['unique_customers'] = $this->wpdb->get_var(
			"SELECT COUNT(DISTINCT email) FROM {$this->tables['calculations']}"
		);

		// Revenue
		$stats['total_revenue'] = $this->wpdb->get_var(
			"SELECT SUM(amount) FROM {$this->tables['transactions']} WHERE status = 'completed'"
		);

		// Today's calculations
		$stats['today_calculations'] = $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->tables['calculations']} WHERE DATE(created_at) = CURDATE()"
		);

		// Package distribution
		$stats['packages'] = $this->wpdb->get_results(
			"SELECT package_type, COUNT(*) as count 
             FROM {$this->tables['calculations']} 
             GROUP BY package_type"
		);

		return $stats;
	}
}