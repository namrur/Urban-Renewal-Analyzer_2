<?php
// templates/sms-templates.php

class URA_SMS_Templates {
    
    public static function get_default_templates() {
        return array(
            // Customer SMS Templates
            'customer_verification_code' => array(
                'enabled' => true,
                'message' => 'קוד אימות: {verification_code}\nתוקף: 10 דקות\n{site_name}'
            ),
            
            'customer_order_confirmation' => array(
                'enabled' => true,
                'message' => 'הזמנה #{order_number} התקבלה. נשלח אימייל לפרטים. {site_name}'
            ),
            
            'customer_payment_confirmation' => array(
                'enabled' => true, 
                'message' => 'תשלום אושר! ניתן להעלות קבצים. פרטים באימייל. {site_name}'
            ),
            
            'customer_report_ready' => array(
                'enabled' => true,
                'message' => 'הדוח שלך מוכן! בדוק אימייל לקישור הורדה. {site_name}'
            ),
            
            'customer_password_reset' => array(
                'enabled' => true,
                'message' => 'קישור איפוס סיסמה: {reset_link}\nתוקף: 24 שעות\n{site_name}'
            ),
            
            // Admin SMS Templates
            'admin_new_registration' => array(
                'enabled' => true,
                'message' => 'הרשמה חדשה: {customer_name} ({customer_phone})'
            ),
            
            'admin_new_order' => array(
                'enabled' => true,
                'message' => 'הזמנה חדשה: #{order_number} - {customer_name}'
            ),
            
            'admin_file_uploaded' => array(
                'enabled' => true,
                'message' => 'קובץ חדש: {customer_name} - {file_name}'
            ),
            
            'admin_payment_received' => array(
                'enabled' => true,
                'message' => 'תשלום התקבל: #{order_number} - {amount}₪'
            ),
            
            'admin_report_generated' => array(
                'enabled' => true,
                'message' => 'דוח הופק: #{order_number} - {customer_name}'
            )
        );
    }
    
    public static function replace_sms_variables($message, $variables = array()) {
        $default_variables = array(
            '{site_name}' => get_bloginfo('name')
        );
        
        $all_variables = array_merge($default_variables, $variables);
        
        foreach ($all_variables as $key => $value) {
            $message = str_replace($key, $value, $message);
        }
        
        // Replace newline characters with actual newlines
        $message = str_replace('\n', "\n", $message);
        
        return $message;
    }
    
    public static function validate_sms_length($message) {
        // Hebrew SMS are 70 characters per segment
        $max_length = 70;
        $length = mb_strlen($message, 'UTF-8');
        
        if ($length <= $max_length) {
            return array(
                'valid' => true,
                'segments' => 1,
                'length' => $length
            );
        } else {
            $segments = ceil($length / $max_length);
            return array(
                'valid' => true, // Still valid, just multiple segments
                'segments' => $segments,
                'length' => $length
            );
        }
    }
}
?>