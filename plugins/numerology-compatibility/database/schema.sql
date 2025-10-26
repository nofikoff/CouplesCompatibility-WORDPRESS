-- Numerology Compatibility Plugin Database Schema

-- Calculations table
CREATE TABLE IF NOT EXISTS `{prefix}nc_calculations` (
                                                         `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED NOT NULL,
    `calculation_id` varchar(255) NOT NULL,
    `package_type` varchar(50) NOT NULL DEFAULT 'free',
    `person1_date` date NOT NULL,
    `person2_date` date NOT NULL,
    `person1_name` varchar(255) DEFAULT NULL,
    `person2_name` varchar(255) DEFAULT NULL,
    `person1_time` time DEFAULT NULL,
    `person2_time` time DEFAULT NULL,
    `person1_place` varchar(255) DEFAULT NULL,
    `person2_place` varchar(255) DEFAULT NULL,
    `result_summary` longtext DEFAULT NULL,
    `pdf_url` varchar(500) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `calculation_id` (`calculation_id`),
    KEY `package_type` (`package_type`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User consents table
CREATE TABLE IF NOT EXISTS `{prefix}nc_consents` (
                                                     `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED NOT NULL,
    `consent_type` varchar(50) NOT NULL,
    `consent_value` tinyint(1) NOT NULL DEFAULT 0,
    `consent_text` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `consent_type` (`consent_type`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API usage tracking
CREATE TABLE IF NOT EXISTS `{prefix}nc_api_usage` (
                                                      `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL,
    `endpoint` varchar(255) NOT NULL,
    `method` varchar(10) NOT NULL,
    `status_code` int(3) DEFAULT NULL,
    `response_time` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `endpoint` (`endpoint`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions
CREATE TABLE IF NOT EXISTS `{prefix}nc_transactions` (
                                                         `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED NOT NULL,
    `calculation_id` varchar(255) DEFAULT NULL,
    `gateway_payment_id` varchar(255) DEFAULT NULL COMMENT 'Payment ID from gateway (Stripe, PayPal, etc)',
    `package_type` varchar(50) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `status` varchar(50) NOT NULL DEFAULT 'pending',
    `payment_method` varchar(50) DEFAULT NULL COMMENT 'Gateway name: stripe, paypal, etc',
    `metadata` longtext DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `calculation_id` (`calculation_id`),
    KEY `gateway_payment_id` (`gateway_payment_id`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics events
CREATE TABLE IF NOT EXISTS `{prefix}nc_analytics` (
                                                      `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `event_type` varchar(100) NOT NULL,
    `event_data` longtext DEFAULT NULL,
    `page_url` varchar(500) DEFAULT NULL,
    `referrer` varchar(500) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `session_id` (`session_id`),
    KEY `event_type` (`event_type`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error logs
CREATE TABLE IF NOT EXISTS `{prefix}nc_error_logs` (
                                                       `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL,
    `error_type` varchar(100) NOT NULL,
    `error_message` text NOT NULL,
    `error_details` longtext DEFAULT NULL,
    `file` varchar(500) DEFAULT NULL,
    `line` int(11) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `error_type` (`error_type`),
    KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
ALTER TABLE `{prefix}nc_calculations`
    ADD INDEX `idx_user_created` (`user_id`, `created_at`),
  ADD INDEX `idx_dates` (`person1_date`, `person2_date`);

ALTER TABLE `{prefix}nc_transactions`
    ADD INDEX `idx_user_status` (`user_id`, `status`),
  ADD INDEX `idx_date_amount` (`created_at`, `amount`);

ALTER TABLE `{prefix}nc_analytics`
    ADD INDEX `idx_session_event` (`session_id`, `event_type`),
  ADD INDEX `idx_user_event_date` (`user_id`, `event_type`, `created_at`);