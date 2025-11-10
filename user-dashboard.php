<?php
// public/user-dashboard.php

class URA_User_Dashboard {
    
    public function __construct() {
        add_shortcode('ura_user_dashboard', array($this, 'user_dashboard_shortcode'));
        add_action('wp_ajax_ura_update_user_address', array($this, 'update_user_address'));
        add_action('wp_ajax_ura_download_report', array($this, 'download_report'));
    }
    
    public function user_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->display_login_required();
        }
        
        ob_start();
        $this->display_user_dashboard();
        return ob_get_clean();
    }
    
    private function display_login_required() {
        return '
        <div class="ura-login-required">
            <h2>נדרשת התחברות</h2>
            <p>עליך להתחבר כדי לצפות בלוח הבקרה שלך.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="ura-btn ura-btn-primary">התחבר לחשבון</a>
        </div>';
    }
    
    private function display_user_dashboard() {
        $user_data = $this->get_current_user_data();
        $user_orders = $this->get_user_orders();
        ?>
        <div class="ura-user-dashboard">
            <div class="ura-dashboard-header">
                <h1>לוח הבקרה שלי</h1>
                <div class="ura-user-welcome">
                    <span>שלום, <?php echo esc_html($user_data['first_name']); ?>!</span>
                </div>
            </div>
            
            <div class="ura-dashboard-content">
                <!-- פרטי משתמש -->
                <div class="ura-user-section">
                    <h2>👤 פרטיי</h2>
                    <div class="ura-user-info">
                        <div class="ura-info-item">
                            <label>שם מלא:</label>
                            <span><?php echo esc_html($user_data['first_name'] . ' ' . $user_data['last_name']); ?></span>
                        </div>
                        <div class="ura-info-item">
                            <label>טלפון:</label>
                            <span><?php echo esc_html($user_data['phone']); ?></span>
                        </div>
                        <div class="ura-info-item">
                            <label>אימייל:</label>
                            <span><?php echo esc_html($user_data['email']); ?></span>
                        </div>
                        <div class="ura-info-item">
                            <label>כתובת:</label>
                            <span id="ura-current-address"><?php echo $this->format_address($user_data); ?></span>
                            <button id="ura-edit-address" class="ura-btn ura-btn-small">✏️ ערוך כתובת</button>
                        </div>
                    </div>
                    
                    <!-- טופס עריכת כתובת -->
                    <div id="ura-edit-address-form" class="ura-edit-form" style="display: none;">
                        <form id="ura-address-update-form">
                            <div class="ura-form-row">
                                <div class="ura-form-group">
                                    <label for="ura-edit-street">רחוב</label>
                                    <input type="text" id="ura-edit-street" name="street" value="<?php echo esc_attr($user_data['address_street']); ?>" required>
                                </div>
                                <div class="ura-form-group">
                                    <label for="ura-edit-building">מספר בניין</label>
                                    <input type="text" id="ura-edit-building" name="building" value="<?php echo esc_attr($user_data['address_building']); ?>" required>
                                </div>
                            </div>
                            <div class="ura-form-row">
                                <div class="ura-form-group">
                                    <label for="ura-edit-apartment">מספר דירה</label>
                                    <input type="text" id="ura-edit-apartment" name="apartment" value="<?php echo esc_attr($user_data['address_apartment']); ?>">
                                </div>
                                <div class="ura-form-group">
                                    <label for="ura-edit-zipcode">מיקוד</label>
                                    <input type="text" id="ura-edit-zipcode" name="zipcode" value="<?php echo esc_attr($user_data['address_zipcode']); ?>" required>
                                    <button type="button" id="ura-find-zipcode-dash" class="ura-btn ura-btn-link">איתור מיקוד</button>
                                </div>
                            </div>
                            <div class="ura-form-actions">
                                <button type="submit" class="ura-btn ura-btn-primary">💾 שמור שינויים</button>
                                <button type="button" id="ura-cancel-edit" class="ura-btn ura-btn-link">❌ ביטול</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- הזמנות שלי -->
                <div class="ura-orders-section">
                    <h2>📦 ההזמנות שלי</h2>
                    <?php if (empty($user_orders)): ?>
                        <div class="ura-empty-orders">
                            <p>עדיין אין לך הזמנות.</p>
                            <a href="/registration" class="ura-btn ura-btn-primary">🛒 הזמן שירות חדש</a>
                        </div>
                    <?php else: ?>
                        <div class="ura-orders-list">
                            <?php foreach ($user_orders as $order): ?>
                                <div class="ura-order-card" data-order-id="<?php echo $order->id; ?>">
                                    <div class="ura-order-header">
                                        <h3>הזמנה #<?php echo $order->order_number; ?></h3>
                                        <span class="ura-order-status ura-status-<?php echo $order->status; ?>">
                                            <?php echo $this->get_status_label($order->status); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="ura-order-details">
                                        <div class="ura-order-service">
                                            <strong>שירות:</strong> <?php echo $order->service_name; ?>
                                        </div>
                                        <div class="ura-order-price">
                                            <strong>מחיר:</strong> <?php echo $order->amount; ?> ₪
                                        </div>
                                        <div class="ura-order-date">
                                            <strong>תאריך:</strong> <?php echo date('d/m/Y', strtotime($order->created_at)); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="ura-order-actions">
                                        <?php if ($order->status === 'paid'): ?>
                                            <button class="ura-btn ura-btn-small ura-upload-files" data-order-id="<?php echo $order->id; ?>">
                                                📤 העלה קבצים
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($order->status === 'completed' && $order->report_count > 0): ?>
                                            <button class="ura-btn ura-btn-small ura-download-report" data-order-id="<?php echo $order->id; ?>">
                                                📥 הורד דוח
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="ura-btn ura-btn-small ura-view-order" data-order-id="<?php echo $order->id; ?>">
                                            👁️ צפה בפרטים
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="ura-new-order-cta">
                            <a href="/registration" class="ura-btn ura-btn-primary">➕ הזמן שירות נוסף</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Modal for order details -->
        <div id="ura-user-order-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <div id="ura-user-order-content"></div>
            </div>
        </div>
        <?php
    }
    
    private function get_current_user_data() {
        // לוגיקה לקבלת פרטי המשתמש הנוכחי
    }
    
    private function get_user_orders() {
        // לוגיקה לקבלת ההזמנות של המשתמש
    }
    
    private function format_address($user_data) {
        // לוגיקה לעיצוב כתובת
    }
    
    private function get_status_label($status) {
        $labels = array(
            'new' => 'חדש',
            'processing' => 'בטיפול',
            'paid' => 'שולם',
            'completed' => 'הושלם',
            'cancelled' => 'בוטל'
        );
        return $labels[$status] ?? $status;
    }
}

new URA_User_Dashboard();
?>