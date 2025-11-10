<?php
// public/registration.php

class URA_Registration {
    
    public function __construct() {
        add_shortcode('ura_registration', array($this, 'registration_shortcode'));
        add_action('wp_ajax_ura_send_sms_code', array($this, 'send_sms_code'));
        add_action('wp_ajax_nopriv_ura_send_sms_code', array($this, 'send_sms_code'));
        add_action('wp_ajax_ura_verify_sms_code', array($this, 'verify_sms_code'));
        add_action('wp_ajax_nopriv_ura_verify_sms_code', array($this, 'verify_sms_code'));
        add_action('wp_ajax_ura_complete_registration', array($this, 'complete_registration'));
        add_action('wp_ajax_nopriv_ura_complete_registration', array($this, 'complete_registration'));
    }
    
    public function registration_shortcode($atts) {
        ob_start();
        $this->display_registration_form();
        return ob_get_clean();
    }
    
    private function display_registration_form() {
        ?>
        <div id="ura-registration" class="ura-registration-container">
            <!-- שלב 1: אימות טלפון -->
            <div id="ura-step-phone" class="ura-step active">
                <h2>הרשמה עם אימות טלפון</h2>
                <form id="ura-phone-form">
                    <div class="ura-form-group">
                        <label for="ura-phone" class="ura-form-label">מספר טלפון נייד</label>
                        <input type="tel" id="ura-phone" name="phone" class="ura-form-input" 
                               placeholder="05X-XXX-XXXX" required>
                    </div>
                    <button type="submit" class="ura-btn ura-btn-primary">שלח קוד אימות</button>
                </form>
            </div>
            
            <!-- שלב 2: אימות קוד SMS -->
            <div id="ura-step-verify" class="ura-step">
                <h2>אימות קוד SMS</h2>
                <form id="ura-verify-form">
                    <div class="ura-form-group">
                        <label class="ura-form-label">קוד אימות נשלח ל-<span id="ura-phone-display"></span></label>
                        <div class="ura-sms-code-inputs">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="1">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="2">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="3">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="4">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="5">
                            <input type="text" maxlength="1" class="ura-sms-digit" data-index="6">
                        </div>
                        <input type="hidden" id="ura-sms-code" name="sms_code">
                    </div>
                    <div class="ura-timer">
                        <span id="ura-timer-text">ניתן לשלוח קוד חדש בעוד: <span id="ura-countdown">60</span> שניות</span>
                        <button type="button" id="ura-resend-sms" class="ura-btn ura-btn-link" disabled>שלח קוד מחדש</button>
                    </div>
                    <button type="submit" class="ura-btn ura-btn-primary">אמת קוד</button>
                </form>
            </div>
            
            <!-- שלב 3: פרטים אישיים -->
            <div id="ura-step-details" class="ura-step">
                <h2>פרטים אישיים</h2>
                <form id="ura-details-form">
                    <div class="ura-form-group">
                        <label for="ura-first-name" class="ura-form-label">שם פרטי</label>
                        <input type="text" id="ura-first-name" name="first_name" class="ura-form-input" required>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-last-name" class="ura-form-label">שם משפחה</label>
                        <input type="text" id="ura-last-name" name="last_name" class="ura-form-input" required>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-email" class="ura-form-label">אימייל</label>
                        <input type="email" id="ura-email" name="email" class="ura-form-input" required>
                    </div>
                    
                    <button type="submit" class="ura-btn ura-btn-primary">המשך לבחירת שירותים</button>
                </form>
            </div>
            
            <!-- שלב 4: בחירת שירותים -->
            <div id="ura-step-services" class="ura-step">
                <h2>בחירת שירותים</h2>
                <div id="ura-services-list" class="ura-services-grid">
                    <!-- יוטען dynamically -->
                </div>
                <div class="ura-total-price">
                    <strong>סה"כ: <span id="ura-total-amount">0</span> ₪</strong>
                </div>
                <button id="ura-continue-to-address" class="ura-btn ura-btn-primary">המשך לפרטי כתובת</button>
            </div>
            
            <!-- שלב 5: פרטי כתובת -->
            <div id="ura-step-address" class="ura-step">
                <h2>פרטי כתובת</h2>
                <form id="ura-address-form">
                    <div class="ura-form-group">
                        <label for="ura-street" class="ura-form-label">רחוב</label>
                        <input type="text" id="ura-street" name="street" class="ura-form-input" required>
                    </div>
                    
                    <div class="ura-form-row">
                        <div class="ura-form-group">
                            <label for="ura-building" class="ura-form-label">מספר בניין</label>
                            <input type="text" id="ura-building" name="building" class="ura-form-input" required>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-apartment" class="ura-form-label">מספר דירה</label>
                            <input type="text" id="ura-apartment" name="apartment" class="ura-form-input">
                        </div>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-zipcode" class="ura-form-label">מיקוד</label>
                        <input type="text" id="ura-zipcode" name="zipcode" class="ura-form-input" required>
                        <button type="button" id="ura-find-zipcode" class="ura-btn ura-btn-link">אינך יודע מה המיקוד?</button>
                    </div>
                    
                    <button type="submit" class="ura-btn ura-btn-primary">סיום הרשמה</button>
                </form>
            </div>
            
            <!-- שלב 6: סיום -->
            <div id="ura-step-complete" class="ura-step">
                <div class="ura-success-message">
                    <h2>✅ ההרשמה הושלמה successfully!</h2>
                    <p>מספר ההזמנה: <strong id="ura-order-number"></strong></p>
                    <p>קישור לניהול ההזמנה נשלח לנייד ולאימייל שלך.</p>
                    <a href="<?php echo home_url('/my-account'); ?>" class="ura-btn ura-btn-primary">לכניסה לחשבון</a>
                </div>
            </div>
        </div>
        
        <!-- מודל איתור מיקוד -->
        <div id="ura-zipcode-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <h3>איתור מיקוד לפי כתובת</h3>
                <form id="ura-zipcode-form">
                    <div class="ura-form-group">
                        <label for="ura-zipcode-city" class="ura-form-label">עיר</label>
                        <input type="text" id="ura-zipcode-city" class="ura-form-input" required>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-zipcode-street" class="ura-form-label">רחוב</label>
                        <input type="text" id="ura-zipcode-street" class="ura-form-input" required>
                    </div>
                    
                    <div class="ura-form-group">
                        <label for="ura-zipcode-building" class="ura-form-label">מספר בניין</label>
                        <input type="text" id="ura-zipcode-building" class="ura-form-input" required>
                    </div>
                    
                    <button type="submit" class="ura-btn ura-btn-primary">חפש מיקוד</button>
                </form>
                
                <div id="ura-zipcode-result" class="ura-zipcode-result"></div>
            </div>
        </div>
        <?php
    }
    
    public function send_sms_code() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone']);
        
        if (!$this->validate_phone($phone)) {
            wp_send_json_error('מספר הטלפון אינו תקין');
        }
        
        // בדיקה אם המשתמש כבר קיים
        if ($this->user_exists($phone)) {
            wp_send_json_error('מספר טלפון זה כבר רשום במערכת');
        }
        
        // יצירת קוד SMS
        $sms_code = $this->generate_sms_code();
        
        // שמירת הקוד במסד הנתונים
        $this->save_sms_code($phone, $sms_code);
        
        // שליחת SMS
        $sms_sent = $this->send_sms($phone, $sms_code);
        
        if ($sms_sent) {
            wp_send_json_success(array(
                'message' => 'קוד אימות נשלח בהצלחה',
                'phone_display' => $this->mask_phone($phone)
            ));
        } else {
            wp_send_json_error('שגיאה בשליחת ה-SMS. נסה שוב.');
        }
    }
    
    public function verify_sms_code() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        $phone = sanitize_text_field($_POST['phone']);
        $entered_code = sanitize_text_field($_POST['sms_code']);
        
        if ($this->verify_sms_code_db($phone, $entered_code)) {
            // יצירת session או token
            $this->create_verification_session($phone);
            wp_send_json_success('קוד אימות אומת בהצלחה');
        } else {
            wp_send_json_error('קוד האימות אינו תקין');
        }
    }
    
    public function complete_registration() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        $user_data = array(
            'phone' => sanitize_text_field($_POST['phone']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'street' => sanitize_text_field($_POST['street']),
            'building' => sanitize_text_field($_POST['building']),
            'apartment' => sanitize_text_field($_POST['apartment']),
            'zipcode' => sanitize_text_field($_POST['zipcode']),
            'services' => array_map('intval', $_POST['services']),
            'user_ip' => $this->get_client_ip()
        );
        
        // יצירת משתמש
        $user_id = $this->create_user($user_data);
        
        if ($user_id) {
            // יצירת הזמנה
            $order_number = $this->create_order($user_id, $user_data['services']);
            
            // שליחת אימייל אישור
            $this->send_confirmation_email($user_data, $order_number);
            
            // שליחת SMS אישור
            $this->send_confirmation_sms($user_data['phone'], $order_number);
            
            wp_send_json_success(array(
                'order_number' => $order_number,
                'message' => 'ההרשמה הושלמה successfully'
            ));
        } else {
            wp_send_json_error('שגיאה ביצירת המשתמש');
        }
    }
    
    private function validate_phone($phone) {
        // הסרת תווים מיוחדים
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($clean_phone) === 10 && substr($clean_phone, 0, 2) === '05';
    }
    
    private function generate_sms_code() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    private function mask_phone($phone) {
        return substr($phone, 0, 3) . '***' . substr($phone, -3);
    }
    
    private function get_client_ip() {
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
}

new URA_Registration();
?>