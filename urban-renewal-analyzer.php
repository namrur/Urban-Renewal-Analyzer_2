<?php
/**
 * Plugin Name: Urban Renewal Analyzer
 * Plugin URI: https://your-site.com
 * Description: מערכת מתקדמת לניתוח הסכמי התחדשות עירונית באמצעות AI
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: urban-renewal
 */

// מניעת גישה ישירה
if (!defined('ABSPATH')) {
    exit;
}

// הגדרות קבועות
define('URA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('URA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('URA_PLUGIN_VERSION', '1.0.0');

// קובץ ההפעלה הראשי
class UrbanRenewalAnalyzer {
    
    public function __construct() {
        $this->init_hooks();
        $this->include_files();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    private function include_files() {
        // קבצי מנהל
        require_once URA_PLUGIN_PATH . 'admin/admin.php';
        require_once URA_PLUGIN_PATH . 'admin/orders.php';
        require_once URA_PLUGIN_PATH . 'admin/services.php';
        require_once URA_PLUGIN_PATH . 'admin/knowledge-base.php';
        require_once URA_PLUGIN_PATH . 'admin/prompts.php';
        require_once URA_PLUGIN_PATH . 'admin/settings.php';
        
        // קבצי מערכת
        require_once URA_PLUGIN_PATH . 'includes/class-database.php';
        require_once URA_PLUGIN_PATH . 'includes/class-file-upload.php';
        require_once URA_PLUGIN_PATH . 'includes/class-deepseek-api.php';
        require_once URA_PLUGIN_PATH . 'includes/class-pdf-generator.php';
        require_once URA_PLUGIN_PATH . 'includes/class-email-manager.php';
        require_once URA_PLUGIN_PATH . 'includes/class-sms-manager.php';
        require_once URA_PLUGIN_PATH . 'includes/class-security.php';
        
        // קבצי frontend
        require_once URA_PLUGIN_PATH . 'public/registration.php';
        require_once URA_PLUGIN_PATH . 'public/user-dashboard.php';
        require_once URA_PLUGIN_PATH . 'public/file-upload.php';
    }
    
    public function activate() {
        // יצירת טבלאות במסד הנתונים
        $database = new URA_Database();
        $database->create_tables();
        
        // הגדרות ברירת מחדל
        $this->set_default_settings();
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        load_plugin_textdomain('urban-renewal', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function admin_menu() {
        add_menu_page(
            'Urban Renewal Analyzer',
            'התחדשות עירונית',
            'manage_options',
            'urban-renewal',
            array($this, 'admin_dashboard'),
            'dashicons-building',
            30
        );
    }
    
    public function admin_dashboard() {
        include URA_PLUGIN_PATH . 'admin/admin.php';
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('ura-public-css', URA_PLUGIN_URL . 'public/assets/public.css', array(), URA_PLUGIN_VERSION);
        wp_enqueue_script('ura-public-js', URA_PLUGIN_URL . 'public/assets/public.js', array('jquery'), URA_PLUGIN_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ura-public-js', 'ura_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ura_nonce')
        ));
    }
    
    public function admin_enqueue_scripts() {
        wp_enqueue_style('ura-admin-css', URA_PLUGIN_URL . 'admin/assets/admin.css', array(), URA_PLUGIN_VERSION);
        wp_enqueue_script('ura-admin-js', URA_PLUGIN_URL . 'admin/assets/admin.js', array('jquery'), URA_PLUGIN_VERSION, true);
    }
    
    private function set_default_settings() {
        $default_settings = array(
            'file_size_limit' => 50,
            'allowed_file_types' => array('pdf', 'doc', 'docx'),
            'max_downloads' => 5,
            'sms_enabled' => false,
            'email_enabled' => true,
        );
        
        update_option('ura_settings', $default_settings);
    }
}

// אתחול הפלאגין
new UrbanRenewalAnalyzer();
?>