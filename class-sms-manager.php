<?php
// includes/class-sms-manager.php

class URA_SMS_Manager {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $from_number;
    private $enabled;
    
    public function __construct() {
        $this->load_settings();
    }
    
    private function load_settings() {
        $settings = get_option('ura_sms_settings');
        
        $this->provider = isset($settings['sms_provider']) ? $settings['sms_provider'] : '';
        $this->api_key = isset($settings['sms_api_key']) ? $settings['sms_api_key'] : '';
        $this->api_secret = isset($settings['sms_api_secret']) ? $settings['sms_api_secret'] : '';
        $this->from_number = isset($settings['sms_from_number']) ? $settings['sms_from_number'] : '';
        $this->enabled = isset($settings['sms_enabled']) ? $settings['sms_enabled'] : false;
    }
    
    /**
     * שליחת SMS ללקוח
     */
    public function send_customer_sms($sms_type, $phone, $data = array()) {
        if (!$this->enabled || !$this->is_valid_phone($phone)) {
            return false;
        }
        
        $template = $this->get_sms_template($sms_type, 'customer');
        
        if (!$template || !$template['enabled']) {
            return false;
        }
        
        $message = $this->replace_sms_variables($template['message'], $data);
        
        return $this->send_sms($phone, $message);
    }
    
    /**
     * שליחת SMS למנהל
     */
    public function send_admin_sms($sms_type, $data = array()) {
        if (!$this->enabled) {
            return false;
        }
        
        $template = $this->get_sms_template($sms_type, 'admin');
        
        if (!$template || !$template['enabled']) {
            return false;
        }
        
        $admin_phone = get_option('ura_admin_phone');
        if (!$admin_phone || !$this->is_valid_phone($admin_phone)) {
            return false;
        }
        
        $message = $this->replace_sms_variables($template['message'], $data);
        
        return $this->send_sms($admin_phone, $message);
    }
    
    /**
     * שליחת קוד אימות SMS
     */
    public function send_verification_code($phone, $code) {
        if (!$this->enabled) {
            return false;
        }
        
        $message = "קוד אימות: {$code}\nתוקף: 10 דקות\n" . get_bloginfo('name');
        
        return $this->send_sms($phone, $message);
    }
    
    /**
     * שליחת SMS באמצעות הספק
     */
    private function send_sms($to, $message) {
        switch ($this->provider) {
            case 'msg91':
                return $this->send_via_msg91($to, $message);
            case 'twilio':
                return $this->send_via_twilio($to, $message);
            case 'cellact':
                return $this->send_via_cellact($to, $message);
            default:
                error_log("URA SMS: Unknown provider: {$this->provider}");
                return false;
        }
    }
    
    /**
     * שליחה באמצעות MSG91
     */
    private function send_via_msg91($to, $message) {
        $url = "https://api.msg91.com/api/v2/sendsms";
        
        $data = array(
            'sender' => $this->from_number,
            'route' => '4', // Transactional route
            'country' => '91', // India code - need to adjust for Israel
            'sms' => array(
                array(
                    'message' => $message,
                    'to' => array($this->format_phone_international($to))
                )
            )
        );
        
        $args = array(
            'headers' => array(
                'authkey' => $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 15
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('URA MSG91 Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['type']) && $body['type'] === 'success';
    }
    
    /**
     * שליחה באמצעות Twilio
     */
    private function send_via_twilio($to, $message) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->api_key}/Messages.json";
        
        $data = array(
            'From' => $this->from_number,
            'To' => $this->format_phone_international($to),
            'Body' => $message
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->api_key . ':' . $this->api_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
            'timeout' => 15
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('URA Twilio Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return !isset($body['error_code']);
    }
    
    /**
     * שליחה באמצעות Cellact (ישראל)
     */
    private function send_via_cellact($to, $message) {
        $url = "https://api.cellact.co.il/webservices/4.0/SendMessage";
        
        $data = array(
            'UserName' => $this->api_key,
            'Password' => $this->api_secret,
            'Source' => $this->from_number,
            'Destination' => $to,
            'MessageText' => $message,
            'CustomerMessageId' => uniqid(),
            'StatusURL' => '' // Optional status callback
        );
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/xml'
            ),
            'body' => $this->array_to_xml($data),
            'timeout' => 15
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('URA Cellact Error: ' . $response->get_error_message());
            return false;
        }
        
        // Cellact returns XML response
        $xml = simplexml_load_string(wp_remote_retrieve_body($response));
        return isset($xml->Status) && (string)$xml->Status === '1';
    }
    
    /**
     * קבלת תבנית SMS
     */
    private function get_sms_template($type, $recipient) {
        $templates = get_option('ura_sms_templates', array());
        $template_key = $recipient . '_' . $type;
        
        return isset($templates[$template_key]) ? $templates[$template_key] : $this->get_default_sms_template($type, $recipient);
    }
    
    /**
     * תבניות SMS ברירת מחדל
     */
    private function get_default_sms_template($type, $recipient) {
        $default_templates = array(
            'customer_verification_code' => array(
                'enabled' => true,
                'message' => 'קוד אימות: {verification_code} - תוקף 10 דקות'
            ),
            
            'customer_order_confirmation' => array(
                'enabled' => true,
                'message' => 'הזמנה {order_number} התקבלה. נשלח אימייל לפרטים.'
            ),
            
            'customer_payment_confirmation' => array(
                'enabled' => true,
                'message' => 'תשלום אושר! ניתן להעלות קבצים. פרטים באימייל.'
            ),
            
            'customer_report_ready' => array(
                'enabled' => true,
                'message' => 'הדוח שלך מוכן! בדוק אימייל לקישור הורדה.'
            ),
            
            'admin_new_registration' => array(
                'enabled' => true,
                'message' => 'הרשמה חדשה: {customer_name} ({customer_phone})'
            ),
            
            'admin_file_uploaded' => array(
                'enabled' => true,
                'message' => 'קובץ חדש: {customer_name} - {file_name}'
            )
        );
        
        $template_key = $recipient . '_' . $type;
        return isset($default_templates[$template_key]) ? $default_templates[$template_key] : null;
    }
    
    /**
     * החלפת משתנים בהודעת SMS
     */
    private function replace_sms_variables($message, $data) {
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
    
    /**
     * פונקציות עזר
     */
    private function is_valid_phone($phone) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($clean_phone) === 10 && substr($clean_phone, 0, 2) === '05';
    }
    
    private function format_phone_international($phone) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        return '972' . substr($clean_phone, 1); // Israel country code
    }
    
    private function array_to_xml($array, $root = 'Request') {
        $xml = new SimpleXMLElement('<' . $root . '/>');
        $this->array_to_xml_recursive($array, $xml);
        return $xml->asXML();
    }
    
    private function array_to_xml_recursive($array, &$xml) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->array_to_xml_recursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    /**
     * בדיקת חיבור SMS
     */
    public function test_sms_connection() {
        if (!$this->enabled) {
            return array('success' => false, 'message' => 'SMS not enabled');
        }
        
        $test_phone = get_option('ura_admin_phone');
        if (!$test_phone) {
            return array('success' => false, 'message' => 'Admin phone not set');
        }
        
        $result = $this->send_sms($test_phone, 'URA SMS Test - Connection successful');
        
        return array(
            'success' => $result,
            'message' => $result ? 'SMS sent successfully' : 'SMS sending failed'
        );
    }
}
?>