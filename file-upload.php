<?php
// public/file-upload.php

class URA_File_Upload_Public {
    
    public function __construct() {
        add_shortcode('ura_file_upload', array($this, 'file_upload_shortcode'));
        add_action('wp_ajax_ura_generate_upload_url', array($this, 'generate_upload_url'));
        add_action('wp_ajax_ura_confirm_file_upload', array($this, 'confirm_file_upload'));
        add_action('wp_ajax_ura_get_upload_progress', array($this, 'get_upload_progress'));
    }
    
    public function file_upload_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->display_login_required();
        }
        
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if (!$order_id) {
            return $this->display_error('מספר הזמנה לא תקין');
        }
        
        if (!$this->verify_order_access($order_id)) {
            return $this->display_error('אין לך גישה להזמנה זו');
        }
        
        ob_start();
        $this->display_file_upload_interface($order_id);
        return ob_get_clean();
    }
    
    private function display_login_required() {
        return '
        <div class="ura-login-required">
            <h2>נדרשת התחברות</h2>
            <p>עליך להתחבר כדי להעלות קבצים.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="ura-btn ura-btn-primary">התחבר לחשבון</a>
        </div>';
    }
    
    private function display_error($message) {
        return '
        <div class="ura-upload-error">
            <h2>❌ שגיאה</h2>
            <p>' . esc_html($message) . '</p>
            <a href="/my-account" class="ura-btn ura-btn-primary">חזור ללוח הבקרה</a>
        </div>';
    }
    
    private function display_file_upload_interface($order_id) {
        $order_data = $this->get_order_data($order_id);
        $existing_files = $this->get_existing_files($order_id);
        ?>
        <div class="ura-file-upload-container">
            <div class="ura-upload-header">
                <h1>📤 העלאת קבצים</h1>
                <div class="ura-order-info">
                    <p><strong>הזמנה:</strong> #<?php echo $order_data->order_number; ?></p>
                    <p><strong>שירות:</strong> <?php echo $order_data->service_name; ?></p>
                    <p><strong>לקוח:</strong> <?php echo $order_data->first_name . ' ' . $order_data->last_name; ?></p>
                </div>
            </div>
            
            <!-- תנאי שימוש -->
            <div class="ura-upload-terms">
                <h3>📋 תנאי השימוש בהעלאת קבצים</h3>
                <div class="ura-terms-content">
                    <p>לפני ההעלאה, אנא קרא ואשר את התנאים הבאים:</p>
                    <ul>
                        <li>✅ הקבצים יישמרו באופן מאובטח ופרטי</li>
                        <li>✅ נעשה שימוש בסריקת וירוסים לפני השמירה</li>
                        <li>✅ לא ניתן למחוק קבצים לאחר ההעלאה</li>
                        <li>✅ רק קבצי PDF, DOC, DOCX מותרים</li>
                        <li>✅ גודל קובץ מקסימלי: 50MB</li>
                        <li>✅ מקסימום 5 קבצים להעלאה</li>
                    </ul>
                </div>
                <div class="ura-terms-acceptance">
                    <label>
                        <input type="checkbox" id="ura-accept-terms">
                        <span>קראתי ואני מסכים/מה לתנאים שלעיל</span>
                    </label>
                </div>
            </div>
            
            <!-- אזור העלאה -->
            <div class="ura-upload-area" id="ura-upload-area">
                <div class="ura-upload-placeholder" id="ura-upload-placeholder">
                    <div class="ura-upload-icon">📁</div>
                    <h3>גרור קבצים לכאן</h3>
                    <p>או</p>
                    <button type="button" class="ura-btn ura-btn-primary" id="ura-select-files">בחר קבצים מהמחשב</button>
                    <input type="file" id="ura-file-input" multiple accept=".pdf,.doc,.docx" style="display: none;">
                    <p class="ura-upload-hint">קבצים מותרים: PDF, DOC, DOCX | מקסימום 50MB לקובץ</p>
                </div>
                
                <div class="ura-upload-progress" id="ura-upload-progress" style="display: none;">
                    <h4>קבצים נבחרים:</h4>
                    <div id="ura-selected-files-list"></div>
                    <div class="ura-upload-actions">
                        <button type="button" class="ura-btn ura-btn-primary" id="ura-start-upload" disabled>התחל העלאה</button>
                        <button type="button" class="ura-btn ura-btn-link" id="ura-cancel-selection">בטל בחירה</button>
                    </div>
                </div>
            </div>
            
            <!-- קבצים קיימים -->
            <?php if (!empty($existing_files)): ?>
            <div class="ura-existing-files">
                <h3>📎 קבצים שהועלו כבר:</h3>
                <div class="ura-files-list">
                    <?php foreach ($existing_files as $file): ?>
                    <div class="ura-file-item">
                        <span class="ura-file-icon">📄</span>
                        <span class="ura-file-name"><?php echo esc_html($file->file_name); ?></span>
                        <span class="ura-file-size">(<?php echo size_format($file->file_size); ?>)</span>
                        <span class="ura-file-date"><?php echo date('d/m/Y H:i', strtotime($file->upload_date)); ?></span>
                        <span class="ura-file-status ura-status-<?php echo $file->status; ?>">
                            <?php echo $this->get_file_status_label($file->status); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- הוראות -->
            <div class="ura-upload-instructions">
                <h3>💡 הוראות חשובות:</h3>
                <div class="ura-instructions-content">
                    <ol>
                        <li>בחר את כל הקבצים שברצונך להעלות בבת אחת</li>
                        <li>ההעלאה מתבצעת באופן מאובטח לשרתים של אמזון</li>
                        <li>לאחר ההעלאה, הקבצים יסרקו לוירוסים אוטומטית</li>
                        <li>תקבל הודעה כאשר הניתוח יתחיל</li>
                        <li>ניתן לעקוב אחרי הסטטוס בלוח הבקרה שלך</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <!-- Progress Modal -->
        <div id="ura-upload-progress-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <h3>⚡ מתבצעת העלאה...</h3>
                <div id="ura-upload-progress-details">
                    <div class="ura-overall-progress">
                        <div class="ura-progress-bar">
                            <div class="ura-progress-fill" id="ura-overall-progress-bar" style="width: 0%"></div>
                        </div>
                        <span id="ura-overall-percentage">0%</span>
                    </div>
                    <div id="ura-current-file-info"></div>
                    <div id="ura-file-progress-list"></div>
                </div>
                <div class="ura-upload-stats">
                    <span id="ura-upload-speed">מהירות: 0 MB/s</span>
                    <span id="ura-time-remaining">זמן משוער: --</span>
                </div>
            </div>
        </div>
        
        <!-- Success Modal -->
        <div id="ura-upload-success-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <div class="ura-success-content">
                    <div class="ura-success-icon">🎉</div>
                    <h3>ההעלאה הושלמה בהצלחה!</h3>
                    <p>הקבצים הועלו ונשמרו באופן מאובטח.</p>
                    <div class="ura-success-actions">
                        <a href="/my-account" class="ura-btn ura-btn-primary">חזור ללוח הבקרה</a>
                        <button type="button" class="ura-btn ura-btn-link" id="ura-upload-more">העלה קבצים נוספים</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Logic for file upload handling
            // This would include drag & drop, progress tracking, etc.
        });
        </script>
        <?php
    }
    
    private function verify_order_access($order_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $order = $wpdb->get_row($wpdb->prepare("
            SELECT o.id 
            FROM {$wpdb->prefix}ura_orders o
            WHERE o.id = %d AND o.user_id = %d AND o.payment_status = 'paid'
        ", $order_id, $user_id));
        
        return !empty($order);
    }
    
    private function get_order_data($order_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT o.*, u.first_name, u.last_name, s.name as service_name
            FROM {$wpdb->prefix}ura_orders o
            LEFT JOIN {$wpdb->prefix}ura_users u ON o.user_id = u.id
            LEFT JOIN {$wpdb->prefix}ura_services s ON o.service_id = s.id
            WHERE o.id = %d
        ", $order_id));
    }
    
    private function get_existing_files($order_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ura_files 
            WHERE order_id = %d 
            ORDER BY upload_date DESC
        ", $order_id));
    }
    
    private function get_file_status_label($status) {
        $labels = array(
            'uploaded' => 'הועלה',
            'processing' => 'בסריקה',
            'approved' => 'אושר',
            'rejected' => 'נדחה'
        );
        return $labels[$status] ?? $status;
    }
    
    public function generate_upload_url() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('נדרשת התחברות');
        }
        
        $order_id = intval($_POST['order_id']);
        $file_name = sanitize_file_name($_POST['file_name']);
        $file_size = intval($_POST['file_size']);
        $file_type = sanitize_text_field($_POST['file_type']);
        
        if (!$this->verify_order_access($order_id)) {
            wp_send_json_error('אין גישה להזמנה זו');
        }
        
        // Validate file
        $validation_errors = $this->validate_file($file_name, $file_size, $file_type);
        if (!empty($validation_errors)) {
            wp_send_json_error(implode(', ', $validation_errors));
        }
        
        $file_upload = new URA_File_Upload();
        $result = $file_upload->generate_presigned_upload_url(
            get_current_user_id(),
            $order_id,
            pathinfo($file_name, PATHINFO_EXTENSION)
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function confirm_file_upload() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('נדרשת התחברות');
        }
        
        $order_id = intval($_POST['order_id']);
        $file_key = sanitize_text_field($_POST['file_key']);
        $file_info = array(
            'name' => sanitize_file_name($_POST['file_name']),
            'size' => intval($_POST['file_size']),
            'type' => sanitize_text_field($_POST['file_type'])
        );
        
        if (!$this->verify_order_access($order_id)) {
            wp_send_json_error('אין גישה להזמנה זו');
        }
        
        $file_upload = new URA_File_Upload();
        $result = $file_upload->handle_file_upload(
            $file_key,
            get_current_user_id(),
            $order_id,
            $file_info
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    private function validate_file($file_name, $file_size, $file_type) {
        $errors = array();
        
        $settings = get_option('ura_settings');
        $max_size = isset($settings['file_size_limit']) ? $settings['file_size_limit'] * 1024 * 1024 : 50 * 1024 * 1024;
        $allowed_types = isset($settings['allowed_file_types']) ? $settings['allowed_file_types'] : array('pdf', 'doc', 'docx');
        
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'סוג קובץ לא נתמך';
        }
        
        if ($file_size > $max_size) {
            $max_size_mb = $max_size / (1024 * 1024);
            $errors[] = "גודל הקובץ חורג מהמותר ({$max_size_mb}MB)";
        }
        
        if ($file_size == 0) {
            $errors[] = 'הקובץ ריק';
        }
        
        return $errors;
    }
}

new URA_File_Upload_Public();
?>