<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Activator
{
    public static function activate(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'mai_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $queries = [];

        $queries[] = "CREATE TABLE {$prefix}clients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            document_id VARCHAR(40) NOT NULL,
            phone VARCHAR(30) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}sedes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            timezone VARCHAR(100) NOT NULL DEFAULT 'America/Bogota',
            manager_user_id BIGINT UNSIGNED NULL,
            weekly_schedule LONGTEXT NULL,
            holidays LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY manager_user_id (manager_user_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}specialists (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            sede_id BIGINT UNSIGNED NOT NULL,
            specialties LONGTEXT NULL,
            own_schedule LONGTEXT NULL,
            blocked_slots LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sede_id (sede_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}services (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sede_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            duration_minutes INT NOT NULL DEFAULT 60,
            buffer_before INT NOT NULL DEFAULT 0,
            buffer_after INT NOT NULL DEFAULT 0,
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_rule VARCHAR(20) NOT NULL DEFAULT 'full',
            consent_required TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY sede_id (sede_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}packages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sede_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            details LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY sede_id (sede_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}appointments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id BIGINT UNSIGNED NOT NULL,
            sede_id BIGINT UNSIGNED NOT NULL,
            specialist_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NULL,
            package_id BIGINT UNSIGNED NULL,
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending_approval',
            price_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_required TINYINT(1) NOT NULL DEFAULT 1,
            payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY specialist_slot (specialist_id, start_datetime, end_datetime),
            KEY client_id (client_id),
            KEY sede_id (sede_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}appointment_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            appointment_id BIGINT UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL,
            reference_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}medical_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id BIGINT UNSIGNED NOT NULL,
            current_version_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY client_id (client_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}medical_record_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            medical_record_id BIGINT UNSIGNED NOT NULL,
            version_number INT NOT NULL,
            payload LONGTEXT NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY medical_record_id (medical_record_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}consents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            appointment_id BIGINT UNSIGNED NOT NULL,
            service_id BIGINT UNSIGNED NOT NULL,
            client_id BIGINT UNSIGNED NOT NULL,
            consent_text_version VARCHAR(50) NOT NULL,
            signed_name VARCHAR(191) NOT NULL,
            signed_id VARCHAR(60) NOT NULL,
            signature_image_path TEXT NOT NULL,
            pdf_path TEXT NULL,
            signed_at DATETIME NOT NULL,
            ip VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            hash VARCHAR(128) NOT NULL,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            appointment_id BIGINT UNSIGNED NOT NULL,
            gateway VARCHAR(30) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            currency VARCHAR(10) NOT NULL DEFAULT 'COP',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            gateway_reference VARCHAR(191) NULL,
            checkout_url TEXT NULL,
            webhook_payload LONGTEXT NULL,
            signature_valid TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id)
        ) {$charset_collate};";

        $queries[] = "CREATE TABLE {$prefix}audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id BIGINT UNSIGNED NULL,
            event_type VARCHAR(100) NOT NULL,
            entity_type VARCHAR(60) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            payload LONGTEXT NULL,
            ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity (entity_type, entity_id)
        ) {$charset_collate};";

        foreach ($queries as $sql) {
            dbDelta($sql);
        }

        Mai_Esteticas_Mai_Suite_Roles::register_roles();
    }
}
