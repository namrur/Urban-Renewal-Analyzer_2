<?php
// includes/class-database.php

class URA_Database {
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            "CREATE TABLE {$wpdb->prefix}ura_users (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20),
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                email VARCHAR(255),
                phone VARCHAR(20),
                address_street VARCHAR(255),
                address_building VARCHAR(50),
                address_apartment VARCHAR(50),
                address_zipcode VARCHAR(10),
                registration_date DATETIME,
                status VARCHAR(20) DEFAULT 'active',
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_orders (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                order_number VARCHAR(50) UNIQUE,
                user_id BIGINT(20),
                service_id BIGINT(20),
                amount DECIMAL(10,2),
                status VARCHAR(50) DEFAULT 'new',
                payment_status VARCHAR(50) DEFAULT 'pending',
                payment_method VARCHAR(50),
                created_at DATETIME,
                updated_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_services (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255),
                description TEXT,
                price DECIMAL(10,2),
                is_active TINYINT(1) DEFAULT 1,
                display_order INT(11),
                created_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_prompts (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255),
                content TEXT,
                knowledge_bases TEXT,
                analysis_level VARCHAR(50) DEFAULT 'standard',
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_knowledge_base (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255),
                description TEXT,
                content LONGTEXT,
                files TEXT,
                category VARCHAR(100),
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_files (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20),
                order_id BIGINT(20),
                file_name VARCHAR(255),
                file_path VARCHAR(500),
                file_size BIGINT(20),
                file_type VARCHAR(50),
                upload_date DATETIME,
                status VARCHAR(50) DEFAULT 'uploaded',
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_reports (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                order_id BIGINT(20),
                prompt_id BIGINT(20),
                report_content LONGTEXT,
                report_file_path VARCHAR(500),
                password VARCHAR(100),
                download_count INT(11) DEFAULT 0,
                max_downloads INT(11) DEFAULT 5,
                created_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}ura_order_status_history (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                order_id BIGINT(20),
                old_status VARCHAR(50),
                new_status VARCHAR(50),
                notes TEXT,
                changed_by BIGINT(20),
                changed_at DATETIME,
                PRIMARY KEY (id)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }
}
?>