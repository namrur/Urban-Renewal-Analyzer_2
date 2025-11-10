<?php
// templates/email-templates.php

class URA_Email_Templates {
    
    public static function get_default_templates() {
        return array(
            // Registration Templates
            'customer_registration' => array(
                'enabled' => true,
                'subject' => 'ברוך הבא ל{site_name} - הרשמתך אושרה',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #2c3e50, #34495e); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">ברוך הבא ל{site_name}!</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">הרשמתך למערכת ניתוח הסכמי התחדשות עירונית אושרה בהצלחה.</p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <h3 style="color: #2c3e50; margin-top: 0;">פרטי החשבון שלך:</h3>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">שם:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">אימייל:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_email}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">טלפון:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_phone}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{login_link}" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                                🔐 כניסה לחשבון שלך
                            </a>
                        </div>
                        
                        <p style="font-size: 14px; color: #7f8c8d;">
                            ניתן כעת לבחור שירותים ולהעלות קבצים לניתוח מקצועי.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; text-align: center; color: #7f8c8d; font-size: 12px;">
                        <p>© {current_year} {site_name}. כל הזכויות שמורות.</p>
                        <p>הודעה זו נשלחה אוטומטית, אנא אל תשיב עליה.</p>
                    </div>
                </div>'
            ),

            // Order Confirmation
            'customer_order_confirmation' => array(
                'enabled' => true,
                'subject' => 'אישור הזמנה #{order_number} - {site_name}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #27ae60, #229954); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">הזמנתך התקבלה! 🎉</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">תודה שבחרת בשירותינו. הזמנתך התקבלה ותטופל בהקדם.</p>
                        
                        <div style="background: #f0f9f4; border: 2px solid #27ae60; border-radius: 8px; padding: 20px; margin: 20px 0;">
                            <h3 style="color: #229954; margin-top: 0;">פרטי ההזמנה:</h3>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">מספר הזמנה:</td>
                                    <td style="padding: 8px 0; font-weight: bold; color: #229954;">#{order_number}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">שירות:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{service_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">מחיר:</td>
                                    <td style="padding: 8px 0; font-weight: bold; color: #229954;">{order_amount} ₪</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">תאריך:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{order_date}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <h4 style="color: #856404; margin: 0 0 10px 0;">📋 השלב הבא:</h4>
                            <p style="margin: 0; color: #856404;">
                                לאחר אישור התשלום, תוכל להעלות את קבצי ההסכם לניתוח.
                            </p>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{order_link}" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                                👁️ צפייה בהזמנה
                            </a>
                        </div>
                    </div>
                </div>'
            ),

            // Payment Confirmation
            'customer_payment_confirmation' => array(
                'enabled' => true,
                'subject' => 'תשלומך אושר - ניתן להעלות קבצים | #{order_number}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #f39c12, #e67e22); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">תשלומך אושר! ✅</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">תשלומך עבור הזמנה #{order_number} אושר בהצלחה.</p>
                        
                        <div style="background: #fef9e7; border: 2px solid #f39c12; border-radius: 8px; padding: 25px; margin: 20px 0; text-align: center;">
                            <h3 style="color: #e67e22; margin-top: 0;">📤 מוכן להעלות קבצים!</h3>
                            <p style="font-size: 18px; margin: 15px 0;">
                                כעת באפשרותך להעלות את קבצי ההסכם לניתוח מקצועי.
                            </p>
                            
                            <div style="margin: 20px 0;">
                                <a href="{upload_link}" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px;">
                                    🚀 העלה קבצים עכשיו
                                </a>
                            </div>
                            
                            <p style="font-size: 14px; color: #7f8c8d; margin: 10px 0 0 0;">
                                ⏰ הקישור תקף ל-7 ימים
                            </p>
                        </div>
                        
                        <div style="background: #e8f4fd; border: 1px solid #3498db; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <h4 style="color: #2980b9; margin: 0 0 10px 0;">💡 מה כדאי להעלות?</h4>
                            <ul style="margin: 0; padding-left: 20px; color: #2980b9;">
                                <li>הסכם התחדשות עירונית מלא</li>
                                <li>נספחים טכניים (אם קיימים)</li>
                                <li>תכניות ושרטוטים</li>
                                <li>כל מסמך רלוונטי נוסף</li>
                            </ul>
                        </div>
                    </div>
                </div>'
            ),

            // Report Ready
            'customer_report_ready' => array(
                'enabled' => true,
                'subject' => 'הדוח שלך מוכן! | #{order_number}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #9b59b6, #8e44ad); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">הדוח שלך מוכן! 📊</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">הדוח המקצועי עבור {service_name} מוכן וזמין להורדה.</p>
                        
                        <div style="background: #f4ecf7; border: 2px solid #9b59b6; border-radius: 8px; padding: 25px; margin: 20px 0;">
                            <h3 style="color: #8e44ad; margin-top: 0;">📄 פרטי הדוח:</h3>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">מספר הזמנה:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">#{order_number}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">סוג שירות:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{service_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">תאריך הפקה:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{report_date}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{download_link}" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px;">
                                📥 הורדת דוח PDF
                            </a>
                        </div>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <h4 style="color: #856404; margin: 0 0 10px 0;">🔒 מידע אבטחה:</h4>
                            <p style="margin: 0; color: #856404;">
                                <strong>סיסמת הדוח:</strong> {report_password}<br>
                                הדוח מוגן בסיסמה וזמין להורדה למשך 30 יום.
                            </p>
                        </div>
                        
                        <div style="background: #e8f4fd; border: 1px solid #3498db; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <h4 style="color: #2980b9; margin: 0 0 10px 0;">💼 המלצה מקצועית:</h4>
                            <p style="margin: 0; color: #2980b9;">
                                מומלץ להתייעץ עם עורך דין מומחה לפני חתימה על ההסכם.
                            </p>
                        </div>
                    </div>
                </div>'
            ),

            // Admin Notifications
            'admin_new_registration' => array(
                'enabled' => true,
                'subject' => 'הרשמה חדשה - {site_name}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); padding: 20px; text-align: center; color: white;">
                        <h2 style="margin: 0; font-size: 24px;">👤 הרשמה חדשה</h2>
                    </div>
                    
                    <div style="padding: 25px;">
                        <p>משתמש חדש נרסם למערכת:</p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d; width: 120px;">שם:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">אימייל:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_email}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">טלפון:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_phone}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">כתובת:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_address}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">תאריך:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{registration_date}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">IP:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_ip}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin: 20px 0;">
                            <a href="{admin_user_link}" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                                👁️ צפייה במשתמש
                            </a>
                        </div>
                    </div>
                </div>'
            ),

            'admin_file_uploaded' => array(
                'enabled' => true,
                'subject' => 'קובץ חדש הועלה | #{order_number}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #3498db, #2980b9); padding: 20px; text-align: center; color: white;">
                        <h2 style="margin: 0; font-size: 24px;">📎 קובץ חדש הועלה</h2>
                    </div>
                    
                    <div style="padding: 25px;">
                        <p>לקוח העלה קובץ חדש למערכת:</p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d; width: 120px;">לקוח:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{customer_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">מספר הזמנה:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">#{order_number}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">שם קובץ:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{file_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">גודל:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{file_size}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">תאריך:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{upload_date}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin: 20px 0;">
                            <a href="{admin_order_link}" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                                👁️ צפייה בהזמנה
                            </a>
                        </div>
                    </div>
                </div>'
            ),

            // Password Reset
            'customer_password_reset' => array(
                'enabled' => true,
                'subject' => 'איפוס סיסמה - {site_name}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #95a5a6, #7f8c8d); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">איפוס סיסמה</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">קיבלנו בקשה לאיפוס הסיסמה שלך.</p>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{reset_link}" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                                🔐 אפס סיסמה
                            </a>
                        </div>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <p style="margin: 0; color: #856404;">
                                <strong>⏰ חשוב:</strong> הקישור תקף ל-24 שעות בלבד.<br>
                                אם לא ביקשת לאפס סיסמה, התעלם מהודעה זו.
                            </p>
                        </div>
                    </div>
                </div>'
            ),

            'customer_password_reset_success' => array(
                'enabled' => true,
                'subject' => 'הסיסמה שלך אופסה - {site_name}',
                'message' => '
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background: linear-gradient(135deg, #27ae60, #229954); padding: 30px; text-align: center; color: white;">
                        <h1 style="margin: 0; font-size: 28px;">סיסמתך אופסה! ✅</h1>
                    </div>
                    
                    <div style="padding: 30px;">
                        <p style="font-size: 16px;">שלום {customer_name},</p>
                        
                        <p style="font-size: 16px;">סיסמתך עודכנה בהצלחה.</p>
                        
                        <div style="background: #f0f9f4; border: 2px solid #27ae60; border-radius: 8px; padding: 20px; margin: 20px 0;">
                            <h3 style="color: #229954; margin-top: 0;">פרטי הפעולה:</h3>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">תאריך:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{reset_date}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #7f8c8d;">כתובת IP:</td>
                                    <td style="padding: 8px 0; font-weight: bold;">{ip_address}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{login_link}" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
                                🔐 התחבר לחשבון
                            </a>
                        </div>
                        
                        <div style="background: #fdedec; border: 1px solid #e74c3c; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <p style="margin: 0; color: #c0392b;">
                                <strong>⚠️ אם לא ביצעת פעולה זו:</strong><br>
                                <a href="{support_link}" style="color: #c0392b;">צור איתנו קשר מיידית</a>
                            </p>
                        </div>
                    </div>
                </div>'
            )
        );
    }
    
    public static function replace_template_variables($content, $variables = array()) {
        $default_variables = array(
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{current_year}' => date('Y'),
            '{current_date}' => date('d/m/Y'),
            '{current_time}' => date('H:i')
        );
        
        $all_variables = array_merge($default_variables, $variables);
        
        foreach ($all_variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        
        return $content;
    }
    
    public static function wrap_email_template($content) {
        return '
        <!DOCTYPE html>
        <html dir="rtl" lang="he">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Template</title>
            <style>
                @media only screen and (max-width: 600px) {
                    .mobile-full {
                        width: 100% !important;
                    }
                    .mobile-padding {
                        padding: 15px !important;
                    }
                    .mobile-text-center {
                        text-align: center !important;
                    }
                }
            </style>
        </head>
        <body style="margin: 0; padding: 0; background: #f5f5f5;">
            <div style="max-width: 600px; margin: 0 auto; background: white;">
                ' . $content . '
            </div>
        </body>
        </html>';
    }
}
?>