<?php
// admin/settings.php

class URA_Settings_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_ura_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_ura_test_smtp', array($this, 'test_smtp_connection'));
        add_action('wp_ajax_ura_test_sms', array($this, 'test_sms_connection'));
        add_action('wp_ajax_ura_test_s3', array($this, 'test_s3_connection'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'urban-renewal',
            'הגדרות מערכת',
            'הגדרות',
            'manage_options',
            'urban-renewal-settings',
            array($this, 'display_settings_page')
        );
    }
    
    public function display_settings_page() {
        $settings = $this->get_all_settings();
        ?>
        <div class="wrap ura-admin">
            <h1>הגדרות מערכת</h1>
            
            <div class="ura-tabs">
                <a href="#" class="ura-tab active" data-tab="general-settings">הגדרות כלליות</a>
                <a href="#" class="ura-tab" data-tab="file-settings">הגדרות קבצים</a>
                <a href="#" class="ura-tab" data-tab="email-settings">הגדרות אימייל</a>
                <a href="#" class="ura-tab" data-tab="sms-settings">הגדרות SMS</a>
                <a href="#" class="ura-tab" data-tab="api-settings">הגדרות API</a>
                <a href="#" class="ura-tab" data-tab="security-settings">אבטחה</a>
            </div>
            
            <!-- הגדרות כלליות -->
            <div id="general-settings" class="ura-tab-content">
                <form id="ura-general-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label for="ura-site-name" class="ura-form-label">שם האתר</label>
                        <input type="text" id="ura-site-name" name="site_name" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['site_name']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-admin-email" class="ura-form-label">אימייל מנהל</label>
                        <input type="email" id="ura-admin-email" name="admin_email" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['admin_email']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-admin-phone" class="ura-form-label">טלפון מנהל</label>
                        <input type="tel" id="ura-admin-phone" name="admin_phone" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['admin_phone']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label class="ura-form-label">הגדרות תצוגה</label>
                        <div class="ura-checkbox-group">
                            <label>
                                <input type="checkbox" name="show_prices" value="1" <?php checked($settings['show_prices'], 1); ?>>
                                הצג מחירים בממשק הרשמה
                            </label>
                            <label>
                                <input type="checkbox" name="allow_multiple_services" value="1" <?php checked($settings['allow_multiple_services'], 1); ?>>
                                אפשר בחירת מספר שירותים
                            </label>
                            <label>
                                <input type="checkbox" name="enable_sms_auth" value="1" <?php checked($settings['enable_sms_auth'], 1); ?>>
                                הפעל אימות SMS
                            </label>
                        </div>
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
            
            <!-- הגדרות קבצים -->
            <div id="file-settings" class="ura-tab-content" style="display: none;">
                <form id="ura-file-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label class="ura-form-label">סוגי קבצים מותרים</label>
                        <div class="ura-checkbox-group">
                            <label>
                                <input type="checkbox" name="file_types[]" value="pdf" <?php echo in_array('pdf', $settings['allowed_file_types']) ? 'checked' : ''; ?>>
                                PDF (.pdf)
                            </label>
                            <label>
                                <input type="checkbox" name="file_types[]" value="doc" <?php echo in_array('doc', $settings['allowed_file_types']) ? 'checked' : ''; ?>>
                                Word (.doc)
                            </label>
                            <label>
                                <input type="checkbox" name="file_types[]" value="docx" <?php echo in_array('docx', $settings['allowed_file_types']) ? 'checked' : ''; ?>>
                                Word (.docx)
                            </label>
                        </div>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-file-size-limit" class="ura-form-label">גודל קובץ מקסימלי (MB)</label>
                        <input type="number" id="ura-file-size-limit" name="file_size_limit" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['file_size_limit']); ?>" min="1" max="100">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-max-file-uploads" class="ura-form-label">מקסימום קבצים להעלאה</label>
                        <input type="number" id="ura-max-file-uploads" name="max_file_uploads" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['max_file_uploads']); ?>" min="1" max="10">
                    </div>
                    
                    <div class="ura-form-group">
                        <label class="ura-form-label">אחסון קבצים</label>
                        <div class="ura-radio-group">
                            <label>
                                <input type="radio" name="storage_type" value="s3" <?php checked($settings['storage_type'], 's3'); ?>>
                                Amazon S3
                            </label>
                            <label>
                                <input type="radio" name="storage_type" value="local" <?php checked($settings['storage_type'], 'local'); ?>>
                                אחסון מקומי
                            </label>
                        </div>
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
            
            <!-- הגדרות אימייל -->
            <div id="email-settings" class="ura-tab-content" style="display: none;">
                <form id="ura-email-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label class="ura-form-label">שיטת שליחת אימייל</label>
                        <div class="ura-radio-group">
                            <label>
                                <input type="radio" name="email_method" value="wp_mail" <?php checked($settings['email_method'], 'wp_mail'); ?>>
                                WordPress Default
                            </label>
                            <label>
                                <input type="radio" name="email_method" value="smtp" <?php checked($settings['email_method'], 'smtp'); ?>>
                                SMTP
                            </label>
                        </div>
                    </div>
                    
                    <div id="ura-smtp-settings" class="ura-smtp-settings" style="<?php echo $settings['email_method'] === 'smtp' ? '' : 'display: none;'; ?>">
                        <div class="ura-form-row">
                            <div class="ura-form-group">
                                <label for="ura-smtp-host" class="ura-form-label">SMTP Host</label>
                                <input type="text" id="ura-smtp-host" name="smtp_host" class="ura-form-input" 
                                       value="<?php echo esc_attr($settings['smtp_host']); ?>">
                            </div>
                            
                            <div class="ura-form-group">
                                <label for="ura-smtp-port" class="ura-form-label">SMTP Port</label>
                                <input type="number" id="ura-smtp-port" name="smtp_port" class="ura-form-input" 
                                       value="<?php echo esc_attr($settings['smtp_port']); ?>">
                            </div>
                        </div>
                        
                        <div class="ura-form-row">
                            <div class="ura-form-group">
                                <label for="ura-smtp-username" class="ura-form-label">SMTP Username</label>
                                <input type="text" id="ura-smtp-username" name="smtp_username" class="ura-form-input" 
                                       value="<?php echo esc_attr($settings['smtp_username']); ?>">
                            </div>
                            
                            <div class="ura-form-group">
                                <label for="ura-smtp-password" class="ura-form-label">SMTP Password</label>
                                <input type="password" id="ura-smtp-password" name="smtp_password" class="ura-form-input" 
                                       value="<?php echo esc_attr($settings['smtp_password']); ?>">
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <button type="button" id="ura-test-smtp" class="ura-btn ura-btn-secondary">בדיקת חיבור SMTP</button>
                            <div id="ura-smtp-test-result"></div>
                        </div>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-from-email" class="ura-form-label">From Email</label>
                        <input type="email" id="ura-from-email" name="from_email" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['from_email']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-from-name" class="ura-form-label">From Name</label>
                        <input type="text" id="ura-from-name" name="from_name" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['from_name']); ?>">
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
            
            <!-- הגדרות SMS -->
            <div id="sms-settings" class="ura-tab-content" style="display: none;">
                <form id="ura-sms-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label class="ura-form-label">
                            <input type="checkbox" name="sms_enabled" value="1" <?php checked($settings['sms_enabled'], 1); ?>>
                            הפעל שליחת SMS
                        </label>
                    </div>
                    
                    <div id="ura-sms-provider-settings" style="<?php echo $settings['sms_enabled'] ? '' : 'display: none;'; ?>">
                        <div class="ura-form-group">
                            <label for="ura-sms-provider" class="ura-form-label">ספק SMS</label>
                            <select id="ura-sms-provider" name="sms_provider" class="ura-form-input">
                                <option value="msg91" <?php selected($settings['sms_provider'], 'msg91'); ?>>MSG91</option>
                                <option value="twilio" <?php selected($settings['sms_provider'], 'twilio'); ?>>Twilio</option>
                                <option value="cellact" <?php selected($settings['sms_provider'], 'cellact'); ?>>Cellact (ישראל)</option>
                            </select>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-sms-api-key" class="ura-form-label">API Key</label>
                            <input type="text" id="ura-sms-api-key" name="sms_api_key" class="ura-form-input" 
                                   value="<?php echo esc_attr($settings['sms_api_key']); ?>">
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-sms-api-secret" class="ura-form-label">API Secret</label>
                            <input type="password" id="ura-sms-api-secret" name="sms_api_secret" class="ura-form-input" 
                                   value="<?php echo esc_attr($settings['sms_api_secret']); ?>">
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-sms-from-number" class="ura-form-label">מספר שולח</label>
                            <input type="text" id="ura-sms-from-number" name="sms_from_number" class="ura-form-input" 
                                   value="<?php echo esc_attr($settings['sms_from_number']); ?>">
                        </div>
                        
                        <div class="ura-form-group">
                            <button type="button" id="ura-test-sms" class="ura-btn ura-btn-secondary">בדיקת שליחת SMS</button>
                            <div id="ura-sms-test-result"></div>
                        </div>
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
            
            <!-- הגדרות API -->
            <div id="api-settings" class="ura-tab-content" style="display: none;">
                <form id="ura-api-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label for="ura-deepseek-api-key" class="ura-form-label">DeepSeek API Key</label>
                        <input type="password" id="ura-deepseek-api-key" name="deepseek_api_key" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['deepseek_api_key']); ?>">
                        <p class="ura-description">מפתח API משירות DeepSeek AI</p>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-s3-access-key" class="ura-form-label">AWS Access Key</label>
                        <input type="password" id="ura-s3-access-key" name="s3_access_key" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['s3_access_key']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-s3-secret-key" class="ura-form-label">AWS Secret Key</label>
                        <input type="password" id="ura-s3-secret-key" name="s3_secret_key" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['s3_secret_key']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-s3-bucket" class="ura-form-label">S3 Bucket Name</label>
                        <input type="text" id="ura-s3-bucket" name="s3_bucket_name" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['s3_bucket_name']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-s3-region" class="ura-form-label">S3 Region</label>
                        <input type="text" id="ura-s3-region" name="s3_region" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['s3_region']); ?>">
                    </div>
                    
                    <div class="ura-form-group">
                        <button type="button" id="ura-test-s3" class="ura-btn ura-btn-secondary">בדיקת חיבור S3</button>
                        <div id="ura-s3-test-result"></div>
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
            
            <!-- הגדרות אבטחה -->
            <div id="security-settings" class="ura-tab-content" style="display: none;">
                <form id="ura-security-settings-form" class="ura-form">
                    <div class="ura-form-group">
                        <label class="ura-form-label">אבטחת קבצים</label>
                        <div class="ura-checkbox-group">
                            <label>
                                <input type="checkbox" name="virus_scan_enabled" value="1" <?php checked($settings['virus_scan_enabled'], 1); ?>>
                                סריקת וירוסים עם ClamAV
                            </label>
                            <label>
                                <input type="checkbox" name="file_type_validation" value="1" <?php checked($settings['file_type_validation'], 1); ?>>
                                בדיקת סוג קובץ
                            </label>
                        </div>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-clamav-socket" class="ura-form-label">ClamAV Socket Path</label>
                        <input type="text" id="ura-clamav-socket" name="clamav_socket" class="ura-form-input" 
                               value="<?php echo esc_attr($settings['clamav_socket']); ?>" placeholder="/var/run/clamav/clamd.ctl">
                    </div>
                    
                    <div class="ura-form-group">
                        <label class="ura-form-label">reCAPTCHA</label>
                        <div class="ura-checkbox-group">
                            <label>
                                <input type="checkbox" name="recaptcha_enabled" value="1" <?php checked($settings['recaptcha_enabled'], 1); ?>>
                                הפעל reCAPTCHA
                            </label>
                        </div>
                    </div>
                    
                    <div id="ura-recaptcha-settings" style="<?php echo $settings['recaptcha_enabled'] ? '' : 'display: none;'; ?>">
                        <div class="ura-form-group">
                            <label for="ura-recaptcha-site-key" class="ura-form-label">reCAPTCHA Site Key</label>
                            <input type="text" id="ura-recaptcha-site-key" name="recaptcha_site_key" class="ura-form-input" 
                                   value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>">
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-recaptcha-secret-key" class="ura-form-label">reCAPTCHA Secret Key</label>
                            <input type="password" id="ura-recaptcha-secret-key" name="recaptcha_secret_key" class="ura-form-input" 
                                   value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>">
                        </div>
                    </div>
                    
                    <div class="ura-form-actions">
                        <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function get_all_settings() {
        return array(
            'site_name' => get_option('ura_site_name', get_bloginfo('name')),
            'admin_email' => get_option('ura_admin_email', get_option('admin_email')),
            'admin_phone' => get_option('ura_admin_phone', ''),
            'show_prices' => get_option('ura_show_prices', 1),
            'allow_multiple_services' => get_option('ura_allow_multiple_services', 1),
            'enable_sms_auth' => get_option('ura_enable_sms_auth', 1),
            
            'allowed_file_types' => get_option('ura_allowed_file_types', array('pdf', 'doc', 'docx')),
            'file_size_limit' => get_option('ura_file_size_limit', 50),
            'max_file_uploads' => get_option('ura_max_file_uploads', 3),
            'storage_type' => get_option('ura_storage_type', 's3'),
            
            'email_method' => get_option('ura_email_method', 'wp_mail'),
            'smtp_host' => get_option('ura_smtp_host', ''),
            'smtp_port' => get_option('ura_smtp_port', '587'),
            'smtp_username' => get_option('ura_smtp_username', ''),
            'smtp_password' => get_option('ura_smtp_password', ''),
            'from_email' => get_option('ura_from_email', get_option('admin_email')),
            'from_name' => get_option('ura_from_name', get_bloginfo('name')),
            
            'sms_enabled' => get_option('ura_sms_enabled', 0),
            'sms_provider' => get_option('ura_sms_provider', 'msg91'),
            'sms_api_key' => get_option('ura_sms_api_key', ''),
            'sms_api_secret' => get_option('ura_sms_api_secret', ''),
            'sms_from_number' => get_option('ura_sms_from_number', ''),
            
            'deepseek_api_key' => get_option('ura_deepseek_api_key', ''),
            's3_access_key' => get_option('ura_s3_access_key', ''),
            's3_secret_key' => get_option('ura_s3_secret_key', ''),
            's3_bucket_name' => get_option('ura_s3_bucket_name', ''),
            's3_region' => get_option('ura_s3_region', 'eu-central-1'),
            
            'virus_scan_enabled' => get_option('ura_virus_scan_enabled', 0),
            'file_type_validation' => get_option('ura_file_type_validation', 1),
            'clamav_socket' => get_option('ura_clamav_socket', '/var/run/clamav/clamd.ctl'),
            'recaptcha_enabled' => get_option('ura_recaptcha_enabled', 0),
            'recaptcha_site_key' => get_option('ura_recaptcha_site_key', ''),
            'recaptcha_secret_key' => get_option('ura_recaptcha_secret_key', ''),
        );
    }
    
    public function save_settings() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $tab = sanitize_text_field($_POST['tab']);
        $settings = $_POST['settings'];
        
        switch ($tab) {
            case 'general-settings':
                update_option('ura_site_name', sanitize_text_field($settings['site_name']));
                update_option('ura_admin_email', sanitize_email($settings['admin_email']));
                update_option('ura_admin_phone', sanitize_text_field($settings['admin_phone']));
                update_option('ura_show_prices', intval($settings['show_prices']));
                update_option('ura_allow_multiple_services', intval($settings['allow_multiple_services']));
                update_option('ura_enable_sms_auth', intval($settings['enable_sms_auth']));
                break;
                
            case 'file-settings':
                update_option('ura_allowed_file_types', array_map('sanitize_text_field', $settings['file_types']));
                update_option('ura_file_size_limit', intval($settings['file_size_limit']));
                update_option('ura_max_file_uploads', intval($settings['max_file_uploads']));
                update_option('ura_storage_type', sanitize_text_field($settings['storage_type']));
                break;
                
            case 'email-settings':
                update_option('ura_email_method', sanitize_text_field($settings['email_method']));
                update_option('ura_smtp_host', sanitize_text_field($settings['smtp_host']));
                update_option('ura_smtp_port', intval($settings['smtp_port']));
                update_option('ura_smtp_username', sanitize_text_field($settings['smtp_username']));
                update_option('ura_smtp_password', sanitize_text_field($settings['smtp_password']));
                update_option('ura_from_email', sanitize_email($settings['from_email']));
                update_option('ura_from_name', sanitize_text_field($settings['from_name']));
                break;
                
            case 'sms-settings':
                update_option('ura_sms_enabled', intval($settings['sms_enabled']));
                update_option('ura_sms_provider', sanitize_text_field($settings['sms_provider']));
                update_option('ura_sms_api_key', sanitize_text_field($settings['sms_api_key']));
                update_option('ura_sms_api_secret', sanitize_text_field($settings['sms_api_secret']));
                update_option('ura_sms_from_number', sanitize_text_field($settings['sms_from_number']));
                break;
                
            case 'api-settings':
                update_option('ura_deepseek_api_key', sanitize_text_field($settings['deepseek_api_key']));
                update_option('ura_s3_access_key', sanitize_text_field($settings['s3_access_key']));
                update_option('ura_s3_secret_key', sanitize_text_field($settings['s3_secret_key']));
                update_option('ura_s3_bucket_name', sanitize_text_field($settings['s3_bucket_name']));
                update_option('ura_s3_region', sanitize_text_field($settings['s3_region']));
                break;
                
            case 'security-settings':
                update_option('ura_virus_scan_enabled', intval($settings['virus_scan_enabled']));
                update_option('ura_file_type_validation', intval($settings['file_type_validation']));
                update_option('ura_clamav_socket', sanitize_text_field($settings['clamav_socket']));
                update_option('ura_recaptcha_enabled', intval($settings['recaptcha_enabled']));
                update_option('ura_recaptcha_site_key', sanitize_text_field($settings['recaptcha_site_key']));
                update_option('ura_recaptcha_secret_key', sanitize_text_field($settings['recaptcha_secret_key']));
                break;
        }
        
        wp_send_json_success('ההגדרות נשמרו בהצלחה');
    }
    
    public function test_smtp_connection() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        // For now, return a simple success message
        // In a real implementation, this would test actual SMTP connection
        wp_send_json_success(array(
            'success' => true,
            'message' => 'SMTP connection test would be implemented here'
        ));
    }
    
    public function test_sms_connection() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        // For now, return a simple success message
        wp_send_json_success(array(
            'success' => true,
            'message' => 'SMS connection test would be implemented here'
        ));
    }
    
    public function test_s3_connection() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        // For now, return a simple success message
        wp_send_json_success(array(
            'success' => true,
            'message' => 'S3 connection test would be implemented here'
        ));
    }
}

new URA_Settings_Manager();
?>