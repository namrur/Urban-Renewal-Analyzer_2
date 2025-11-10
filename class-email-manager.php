<?php
// includes/class-email-manager.php

class URA_Email_Manager {
    
    private $smtp_enabled;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->load_settings();
    }
    
    private function load_settings() {
        $settings = get_option('ura_email_settings');
        
        $this->smtp_enabled = isset($settings['smtp_enabled']) ? $settings['smtp_enabled'] : false;
        $this->smtp_host = isset($settings['smtp_host']) ? $settings['smtp_host'] : '';
        $this->smtp_port = isset($settings['smtp_port']) ? $settings['smtp_port'] : 587;
        $this->smtp_username = isset($settings['smtp_username']) ? $settings['smtp_username'] : '';
        $this->smtp_password = isset($settings['smtp_password']) ? $settings['smtp_password'] : '';
        $this->from_email = isset($settings['from_email']) ? $settings['from_email'] : get_option('admin_email');
        $this->from_name = isset($settings['from_name']) ? $settings['from_name'] : get_bloginfo('name');
    }
    
    /**
     * שליחת אימייל ללקוח
     */
    public function send_customer_email($email_type, $user_data, $additional_data = array()) {
        $template = $this->get_email_template($email_type, 'customer');
        
        if (!$template || !$template['enabled']) {
            return false;
        }
        
        $to = $user_data['email'];
        $subject = $this->replace_template_variables($template['subject'], $user_data, $additional_data);
        $message = $this->replace_template_variables($template['message'], $user_data, $additional_data);
        $headers = $this->prepare_headers();
        
        if ($this->smtp_enabled) {
            return $this->send_via_smtp($to, $subject, $message, $headers);
        } else {
            return wp_mail($to, $subject, $message, $headers);
        }
    }
    
    /**
     * שליחת התראה למנהל
     */
    public function send_admin_notification($notification_type, $data = array()) {
        $template = $this->get_email_template($notification_type, 'admin');
        
        if (!$template || !$template['enabled']) {
            return false;
        }
        
        $admin_email = get_option('admin_email');
        $subject = $this->replace_template_variables($template['subject'], array(), $data);
        $message = $this->replace_template_variables($template['message'], array(), $data);
        $headers = $this->prepare_headers();
        
        if ($this->smtp_enabled) {
            return $this->send_via_smtp($admin_email, $subject, $message, $headers);
        } else {
            return wp_mail($admin_email, $subject, $message, $headers);
        }
    }
    
    /**
     * שליחת אימייל באמצעות SMTP
     */
    private function send_via_smtp($to, $subject, $message, $headers) {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // הגדרות SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // נמענים
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            // תוכן
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->wrap_in_html_template($message);
            $mail->AltBody = strip_tags($message);
            
            return $mail->send();
            
        } catch (Exception $e) {
            error_log('URA SMTP Error: ' . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * קבלת תבנית אימייל
     */
    private function get_email_template($type, $recipient) {
        $templates = get_option('ura_email_templates', array());
        $template_key = $recipient . '_' . $type;
        
        return isset($templates[$template_key]) ? $templates[$template_key] : $this->get_default_template($type, $recipient);
    }
    
    /**
     * תבניות ברירת מחדל
     */
    private function get_default_template($type, $recipient) {
        $default_templates = array(
            'customer_registration' => array(
                'enabled' => true,
                'subject' => 'ברוך הבא - הרשמתך אושרה',
                'message' => '
                <h2>ברוך הבא ל{site_name}!</h2>
                <p>שלום {customer_name},</p>
                <p>הרשמתך למערכת ניתוח הסכמי התחדשות עירונית אושרה.</p>
                <p><strong>פרטי ההתחברות:</strong></p>
                <ul>
                    <li>אימייל: {customer_email}</li>
                    <li>טלפון: {customer_phone}</li>
                </ul>
                <p>כעת באפשרותך לבחור שירותים ולהעלות קבצים לניתוח.</p>
                <p><a href="{login_link}">לכניסה לחשבון שלך</a></p>
                <p>בברכה,<br>צוות {site_name}</p>'
            ),
            
            'customer_order_confirmation' => array(
                'enabled' => true,
                'subject' => 'אישור הזמנה #{order_number}',
                'message' => '
                <h2>הזמנתך התקבלה!</h2>
                <p>שלום {customer_name},</p>
                <p>הזמנתך מספר {order_number} התקבלה בהצלחה.</p>
                <p><strong>פרטי ההזמנה:</strong></p>
                <ul>
                    <li>מספר הזמנה: {order_number}</li>
                    <li>שירות: {service_name}</li>
                    <li>מחיר: {order_amount} ₪</li>
                    <li>תאריך: {order_date}</li>
                </ul>
                <p>ניתן יהיה להעלות קבצים לאחר אישור התשלום.</p>
                <p><a href="{order_link}">לצפייה בהזמנה</a></p>'
            ),
            
            'customer_payment_confirmation' => array(
                'enabled' => true,
                'subject' => 'תשלומך אושר - ניתן להעלות קבצים',
                'message' => '
                <h2>תשלומך אושר!</h2>
                <p>שלום {customer_name},</p>
                <p>תשלומך עבור הזמנה {order_number} אושר.</p>
                <p>כעת באפשרותך להעלות את קבצי ההסכם לניתוח.</p>
                <p><strong>קישור להעלאת קבצים:</strong><br>
                <a href="{upload_link}">{upload_link}</a></p>
                <p>הקישור תקף ל-7 ימים.</p>'
            ),
            
            'customer_report_ready' => array(
                'enabled' => true,
                'subject' => 'הדוח שלך מוכן!',
                'message' => '
                <h2>הדוח שלך מוכן!</h2>
                <p>שלום {customer_name},</p>
                <p>הדוח עבור {service_name} מוכן וזמין להורדה.</p>
                <p><strong>פרטי הדוח:</strong></p>
                <ul>
                    <li>מספר הזמנה: {order_number}</li>
                    <li>סוג שירות: {service_name}</li>
                    <li>תאריך הפקה: {report_date}</li>
                </ul>
                <p><strong>קישור להורדה:</strong><br>
                <a href="{download_link}">הורדת דוח PDF</a></p>
                <p><strong>סיסמת הדוח:</strong> {report_password}</p>
                <p>הדוח זמין להורדה למשך 30 יום.</p>'
            ),
            
            'admin_new_registration' => array(
                'enabled' => true,
                'subject' => 'הרשמה חדשה - {site_name}',
                'message' => '
                <h2>הרשמה חדשה</h2>
                <p>משתמש חדש נרסם למערכת:</p>
                <ul>
                    <li>שם: {customer_name}</li>
                    <li>אימייל: {customer_email}</li>
                    <li>טלפון: {customer_phone}</li>
                    <li>כתובת: {customer_address}</li>
                    <li>תאריך: {registration_date}</li>
                    <li>IP: {customer_ip}</li>
                </ul>
                <p><a href="{admin_user_link}">לצפייה במשתמש</a></p>'
            ),
            
            'admin_file_uploaded' => array(
                'enabled' => true,
                'subject' => 'קובץ חדש הועלה - {order_number}',
                'message' => '
                <h2>קובץ חדש הועלה</h2>
                <p>לקוח העלה קובץ חדש:</p>
                <ul>
                    <li>לקוח: {customer_name}</li>
                    <li>מספר הזמנה: {order_number}</li>
                    <li>שם קובץ: {file_name}</li>
                    <li>גודל: {file_size}</li>
                    <li>תאריך: {upload_date}</li>
                </ul>
                <p><a href="{admin_order_link}">לצפייה בהזמנה</a></p>'
            )
        );
        
        $template_key = $recipient . '_' . $type;
        return isset($default_templates[$template_key]) ? $default_templates[$template_key] : null;
    }
    
    /**
     * החלפת משתנים בתבנית
     */
    private function replace_template_variables($content, $user_data, $additional_data) {
        $variables = array_merge($user_data, $additional_data);
        
        // משתנים בסיסיים
        $base_variables = array(
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{current_date}' => date('d/m/Y'),
            '{current_time}' => date('H:i'),
        );
        
        $variables = array_merge($base_variables, $variables);
        
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * עטיפת ההודעה בתבנית HTML
     */
    private function wrap_in_html_template($content) {
        return '
        <!DOCTYPE html>
        <html dir="rtl" lang="he">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>אימייל</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #2271b1; }
                .content { padding: 20px; background: #fff; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                a { color: #2271b1; text-decoration: none; }
                .button { display: inline-block; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . get_bloginfo('name') . '</h1>
                </div>
                <div class="content">' . $content . '</div>
                <div class="footer">
                    <p>© ' . date('Y') . ' ' . get_bloginfo('name') . '. כל הזכויות שמורות.</p>
                    <p>הודעה זו נשלחה אוטומטית, אנא אל תשיב עליה.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * הכנת headers לאימייל
     */
    private function prepare_headers() {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>'
        );
        
        return $headers;
    }
    
    /**
     * בדיקת חיבור SMTP
     */
    public function test_smtp_connection() {
        if (!$this->smtp_enabled) {
            return array('success' => false, 'message' => 'SMTP not enabled');
        }
        
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->Timeout = 10;
            
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return array('success' => true, 'message' => 'SMTP connection successful');
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
?>