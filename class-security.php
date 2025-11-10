<?php
// includes/class-security.php

class URA_Security {
    
    public function __construct() {
        add_action('init', array($this, 'security_headers'));
        add_filter('wp_headers', array($this, 'add_security_headers'));
    }
    
    /**
     * הוספת security headers
     */
    public function security_headers() {
        if (!is_admin()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // HSTS - רק ב-HTTPS
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }
    
    public function add_security_headers($headers) {
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Frame-Options'] = 'SAMEORIGIN';
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        
        return $headers;
    }
    
    /**
     * סריקת קובץ לוירוסים
     */
    public function scan_file_for_viruses($file_path) {
        $settings = get_option('ura_settings');
        
        if (!isset($settings['virus_scan_enabled']) || !$settings['virus_scan_enabled']) {
            return array('clean' => true, 'threats' => array());
        }
        
        // ניסיון עם ClamAV
        $clamav_result = $this->scan_with_clamav($file_path);
        if ($clamav_result !== false) {
            return $clamav_result;
        }
        
        // גיבוי - סריקה בסיסית
        return $this->basic_file_security_scan($file_path);
    }
    
    /**
     * סריקה עם ClamAV
     */
    private function scan_with_clamav($file_path) {
        $settings = get_option('ura_settings');
        $clamav_socket = isset($settings['clamav_socket']) ? $settings['clamav_socket'] : '/var/run/clamav/clamd.ctl';
        
        if (!file_exists($clamav_socket)) {
            return false;
        }
        
        try {
            $clamd = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if (!$clamd) {
                return false;
            }
            
            $connected = socket_connect($clamd, $clamav_socket);
            if (!$connected) {
                return false;
            }
            
            $command = "SCAN " . $file_path . "\n";
            socket_write($clamd, $command, strlen($command));
            
            $response = socket_read($clamd, 4096);
            socket_close($clamd);
            
            if (strpos($response, 'OK') !== false) {
                return array('clean' => true, 'threats' => array());
            } else {
                preg_match('/: (.+) FOUND/', $response, $matches);
                $threat_name = isset($matches[1]) ? $matches[1] : 'Unknown threat';
                return array('clean' => false, 'threats' => array($threat_name));
            }
            
        } catch (Exception $e) {
            error_log('ClamAV scan failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * סריקת אבטחה בסיסית
     */
    private function basic_file_security_scan($file_path) {
        $file_content = file_get_contents($file_path);
        
        // בדיקת קוד זדוני נפוץ
        $malicious_patterns = array(
            '/<\?php\s*@?eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/proc_open\s*\(/i',
            '/popen\s*\(/i',
            '/phpinfo\s*\(/i',
            '/assert\s*\(/i',
            '/chmod\s*\(/i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $file_content)) {
                return array('clean' => false, 'threats' => array('Suspicious code pattern detected'));
            }
        }
        
        return array('clean' => true, 'threats' => array());
    }
    
    /**
     * אימות reCAPTCHA
     */
    public function verify_recaptcha($recaptcha_response) {
        $settings = get_option('ura_settings');
        
        if (!isset($settings['recaptcha_enabled']) || !$settings['recaptcha_enabled']) {
            return true;
        }
        
        $secret_key = isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';
        
        if (empty($secret_key) || empty($recaptcha_response)) {
            return false;
        }
        
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $this->get_client_ip()
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['success']) && $body['success'] === true;
    }
    
    /**
     * הגנה מפני brute force
     */
    public function check_brute_force_protection($action, $user_id = null, $max_attempts = 5, $timeframe = 900) {
        $transient_key = 'ura_bf_' . $action . '_' . ($user_id ?: $this->get_client_ip());
        $attempts = get_transient($transient_key) ?: 0;
        
        if ($attempts >= $max_attempts) {
            return false;
        }
        
        $attempts++;
        set_transient($transient_key, $attempts, $timeframe);
        
        return true;
    }
    
    /**
     * בדיקת סוג קובץ
     */
    public function validate_file_type($file_path, $allowed_types = array('pdf', 'doc', 'docx')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_mimes = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        
        return in_array($mime_type, $allowed_mimes);
    }
    
    /**
     * הגנה against SQL injection
     */
    public function sanitize_sql_input($input) {
        if (is_array($input)) {
            return array_map(array($this, 'sanitize_sql_input'), $input);
        }
        
        global $wpdb;
        return $wpdb->_real_escape($input);
    }
    
    /**
     * CSRF protection
     */
    public function generate_csrf_token($action = 'general') {
        $token = wp_create_nonce('ura_csrf_' . $action);
        return $token;
    }
    
    public function verify_csrf_token($token, $action = 'general') {
        return wp_verify_nonce($token, 'ura_csrf_' . $action);
    }
    
    /**
     * הגנה against XSS
     */
    public function sanitize_xss($input) {
        if (is_array($input)) {
            return array_map(array($this, 'sanitize_xss'), $input);
        }
        
        return wp_kses_post($input);
    }
    
    /**
     * קבלת IP הלקוח
     */
    public function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return sanitize_text_field($ip);
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * רישום אירועי אבטחה
     */
    public function log_security_event($event_type, $details = array()) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}ura_security_logs",
            array(
                'event_type' => $event_type,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
                'details' => json_encode($details),
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * בדיקת חוזק סיסמה
     */
    public function check_password_strength($password) {
        $score = 0;
        
        // אורך
        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        
        // מגוון תווים
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;
        
        return $score >= 5; // דורש לפחות 5/6 נקודות
    }
}
?>