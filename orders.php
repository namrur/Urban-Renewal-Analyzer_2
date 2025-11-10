<?php
// admin/orders.php

class URA_Orders_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_ura_update_order_status', array($this, 'update_order_status'));
        add_action('wp_ajax_ura_get_order_details', array($this, 'get_order_details'));
        add_action('wp_ajax_ura_delete_order', array($this, 'delete_order'));
        add_action('wp_ajax_ura_export_orders', array($this, 'export_orders'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'urban-renewal',
            'ניהול הזמנות',
            'הזמנות',
            'manage_options',
            'urban-renewal-orders',
            array($this, 'display_orders_page')
        );
    }
    
    public function display_orders_page() {
        ?>
        <div class="wrap ura-admin">
            <h1>ניהול הזמנות</h1>
            
            <div class="ura-orders-header">
                <div class="ura-stats-overview">
                    <?php $this->display_orders_stats(); ?>
                </div>
                
                <div class="ura-orders-actions">
                    <button id="ura-export-orders" class="ura-btn ura-btn-secondary">📊 ייצוא הזמנות</button>
                    <button id="ura-refresh-orders" class="ura-btn ura-btn-link">🔄 רענן</button>
                </div>
            </div>
            
            <div class="ura-filters-box">
                <div class="ura-search-filter">
                    <input type="text" id="ura-orders-search" placeholder="חיפוש לפי מס' הזמנה, שם, טלפון...">
                    <button type="button" id="ura-search-orders" class="ura-btn ura-btn-primary">🔍 חפש</button>
                </div>
                
                <div class="ura-filter-controls">
                    <select id="ura-status-filter">
                        <option value="">כל הסטטוסים</option>
                        <option value="new">חדש</option>
                        <option value="processing">בטיפול</option>
                        <option value="paid">שולם</option>
                        <option value="completed">הושלם</option>
                        <option value="cancelled">בוטל</option>
                    </select>
                    
                    <select id="ura-service-filter">
                        <option value="">כל השירותים</option>
                        <?php $this->display_service_options(); ?>
                    </select>
                    
                    <input type="date" id="ura-date-from" placeholder="מתאריך">
                    <input type="date" id="ura-date-to" placeholder="עד תאריך">
                    
                    <button type="button" id="ura-apply-filters" class="ura-btn ura-btn-primary">החל סינונים</button>
                    <button type="button" id="ura-clear-filters" class="ura-btn ura-btn-link">נקה</button>
                </div>
            </div>
            
            <div class="ura-table-container">
                <table class="ura-table wp-list-table widefat fixed striped" id="ura-orders-table">
                    <thead>
                        <tr>
                            <th width="120">מספר הזמנה</th>
                            <th width="150">לקוח</th>
                            <th width="120">שירות</th>
                            <th width="100">מחיר</th>
                            <th width="120">סטטוס</th>
                            <th width="120">תשלום</th>
                            <th width="100">הורדות</th>
                            <th width="150">תאריך</th>
                            <th width="200">פעולות</th>
                        </tr>
                    </thead>
                    <tbody id="ura-orders-list">
                        <?php $this->display_orders_list(); ?>
                    </tbody>
                </table>
            </div>
            
            <div class="ura-pagination" id="ura-orders-pagination">
                <?php $this->display_pagination(); ?>
            </div>
        </div>
        
        <!-- Order Details Modal -->
        <div id="ura-order-details-modal" class="ura-modal">
            <div class="ura-modal-content ura-large-modal">
                <span class="ura-modal-close">&times;</span>
                <div id="ura-order-details-content"></div>
            </div>
        </div>
        <?php
    }
    
    private function display_orders_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM {$wpdb->prefix}ura_orders 
            GROUP BY status
        ");
        
        $total_orders = 0;
        $total_revenue = 0;
        $status_counts = array();
        
        foreach ($stats as $stat) {
            $total_orders += $stat->count;
            $total_revenue += $stat->total_amount;
            $status_counts[$stat->status] = $stat->count;
        }
        
        echo "
        <div class='ura-stat-card'>
            <div class='ura-stat-number'>{$total_orders}</div>
            <div class='ura-stat-label'>סה\"כ הזמנות</div>
        </div>
        
        <div class='ura-stat-card'>
            <div class='ura-stat-number'>" . number_format($total_revenue) . " ₪</div>
            <div class='ura-stat-label'>סה\"כ הכנסות</div>
        </div>
        
        <div class='ura-stat-card'>
            <div class='ura-stat-number'>" . ($status_counts['new'] ?? 0) . "</div>
            <div class='ura-stat-label'>הזמנות חדשות</div>
        </div>
        
        <div class='ura-stat-card'>
            <div class='ura-stat-number'>" . ($status_counts['completed'] ?? 0) . "</div>
            <div class='ura-stat-label'>הושלמו</div>
        </div>";
    }
    
    private function display_orders_list($page = 1, $per_page = 20) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $orders = $wpdb->get_results("
            SELECT o.*, 
                   u.first_name, 
                   u.last_name, 
                   u.phone, 
                   u.email,
                   s.name as service_name,
                   COUNT(DISTINCT f.id) as file_count,
                   COUNT(DISTINCT r.id) as report_count,
                   MAX(r.download_count) as download_count
            FROM {$wpdb->prefix}ura_orders o
            LEFT JOIN {$wpdb->prefix}ura_users u ON o.user_id = u.id
            LEFT JOIN {$wpdb->prefix}ura_services s ON o.service_id = s.id
            LEFT JOIN {$wpdb->prefix}ura_files f ON o.id = f.order_id
            LEFT JOIN {$wpdb->prefix}ura_reports r ON o.id = r.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT {$offset}, {$per_page}
        ");
        
        if (empty($orders)) {
            echo '<tr><td colspan="9" class="ura-empty-state">לא נמצאו הזמנות</td></tr>';
            return;
        }
        
        foreach ($orders as $order) {
            $customer_name = $order->first_name . ' ' . $order->last_name;
            $status_class = 'ura-status-' . $order->status;
            $payment_status_class = 'ura-payment-' . $order->payment_status;
            
            $download_indicator = $order->download_count > 0 ? 
                                "✅ ({$order->download_count})" : "📥";
            
            echo "
            <tr data-order-id='{$order->id}'>
                <td>
                    <strong>{$order->order_number}</strong>
                </td>
                <td>
                    <div class='ura-customer-info'>
                        <strong>{$customer_name}</strong>
                        <br><small>{$order->phone}</small>
                        <br><small>{$order->email}</small>
                    </div>
                </td>
                <td>{$order->service_name}</td>
                <td>{$order->amount} ₪</td>
                <td>
                    <select class='ura-order-status' data-order-id='{$order->id}' data-nonce='" . wp_create_nonce('ura_nonce') . "'>
                        " . $this->get_status_options($order->status) . "
                    </select>
                </td>
                <td>
                    <span class='ura-payment-status {$payment_status_class}'>
                        " . $this->get_payment_status_label($order->payment_status) . "
                    </span>
                </td>
                <td>{$download_indicator}</td>
                <td>" . date('d/m/Y H:i', strtotime($order->created_at)) . "</td>
                <td>
                    <button class='ura-btn ura-btn-small ura-view-order' data-order-id='{$order->id}'>👁️ צפה</button>
                    <button class='ura-btn ura-btn-small ura-send-email' data-order-id='{$order->id}'>📧 אימייל</button>
                    <button class='ura-btn ura-btn-small ura-btn-danger ura-delete-order' data-order-id='{$order->id}'>🗑️ מחק</button>
                </td>
            </tr>";
        }
    }
    
    private function get_status_options($current_status) {
        $statuses = array(
            'new' => '🆕 חדש',
            'processing' => '⚠️ בטיפול', 
            'paid' => '💰 שולם',
            'completed' => '✅ הושלם',
            'cancelled' => '❌ בוטל'
        );
        
        $options = '';
        foreach ($statuses as $value => $label) {
            $selected = $value === $current_status ? 'selected' : '';
            $options .= "<option value='{$value}' {$selected}>{$label}</option>";
        }
        
        return $options;
    }
    
    private function get_payment_status_label($status) {
        $labels = array(
            'pending' => '⏳ ממתין',
            'paid' => '✅ שולם',
            'failed' => '❌ נכשל',
            'refunded' => '↩️ הוחזר'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    private function display_service_options() {
        global $wpdb;
        
        $services = $wpdb->get_results("
            SELECT id, name FROM {$wpdb->prefix}ura_services 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        
        foreach ($services as $service) {
            echo "<option value='{$service->id}'>{$service->name}</option>";
        }
    }
    
    private function display_pagination($current_page = 1, $per_page = 20) {
        global $wpdb;
        
        $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ura_orders");
        $total_pages = ceil($total_orders / $per_page);
        
        if ($total_pages <= 1) return;
        
        echo '<div class="ura-pagination-links">';
        
        if ($current_page > 1) {
            echo '<button class="ura-page-link" data-page="' . ($current_page - 1) . '">← הקודם</button>';
        }
        
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = $i === $current_page ? 'ura-page-active' : '';
            echo '<button class="ura-page-link ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
        }
        
        if ($current_page < $total_pages) {
            echo '<button class="ura-page-link" data-page="' . ($current_page + 1) . '">הבא →</button>';
        }
        
        echo '</div>';
    }
    
    public function update_order_status() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        global $wpdb;
        
        // קבלת הסטטוס הנוכחי
        $current_status = $wpdb->get_var($wpdb->prepare("
            SELECT status FROM {$wpdb->prefix}ura_orders WHERE id = %d
        ", $order_id));
        
        // עדכון הסטטוס
        $result = $wpdb->update(
            "{$wpdb->prefix}ura_orders",
            array(
                'status' => $new_status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id)
        );
        
        if ($result !== false) {
            // רישום בהיסטוריה
            $wpdb->insert(
                "{$wpdb->prefix}ura_order_status_history",
                array(
                    'order_id' => $order_id,
                    'old_status' => $current_status,
                    'new_status' => $new_status,
                    'notes' => $notes,
                    'changed_by' => get_current_user_id(),
                    'changed_at' => current_time('mysql')
                )
            );
            
            // שליחת התראות אם רלוונטי
            $this->send_status_change_notifications($order_id, $current_status, $new_status);
            
            wp_send_json_success('סטטוס ההזמנה עודכן בהצלחה');
        } else {
            wp_send_json_error('שגיאה בעדכון הסטטוס');
        }
    }
    
    public function get_order_details() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $order_id = intval($_POST['order_id']);
        
        global $wpdb;
        
        $order = $wpdb->get_row($wpdb->prepare("
            SELECT o.*, 
                   u.first_name, u.last_name, u.phone, u.email,
                   u.address_street, u.address_building, u.address_apartment, u.address_zipcode,
                   s.name as service_name, s.description as service_description
            FROM {$wpdb->prefix}ura_orders o
            LEFT JOIN {$wpdb->prefix}ura_users u ON o.user_id = u.id
            LEFT JOIN {$wpdb->prefix}ura_services s ON o.service_id = s.id
            WHERE o.id = %d
        ", $order_id));
        
        if (!$order) {
            wp_send_json_error('הזמנה לא נמצאה');
        }
        
        // קבצים מצורפים
        $files = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ura_files 
            WHERE order_id = %d 
            ORDER BY upload_date DESC
        ", $order_id));
        
        // דוחות
        $reports = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ura_reports 
            WHERE order_id = %d 
            ORDER BY created_at DESC
        ", $order_id));
        
        // היסטוריית סטטוסים
        $status_history = $wpdb->get_results($wpdb->prepare("
            SELECT h.*, u.user_login as changed_by_name
            FROM {$wpdb->prefix}ura_order_status_history h
            LEFT JOIN {$wpdb->prefix}users u ON h.changed_by = u.ID
            WHERE h.order_id = %d 
            ORDER BY h.changed_at DESC
        ", $order_id));
        
        ob_start();
        ?>
        <div class="ura-order-details">
            <h2>פרטי הזמנה #<?php echo $order->order_number; ?></h2>
            
            <div class="ura-details-grid">
                <div class="ura-details-section">
                    <h3>📋 פרטי הזמנה</h3>
                    <table class="ura-details-table">
                        <tr>
                            <th>מספר הזמנה:</th>
                            <td><?php echo $order->order_number; ?></td>
                        </tr>
                        <tr>
                            <th>שירות:</th>
                            <td><?php echo $order->service_name; ?></td>
                        </tr>
                        <tr>
                            <th>מחיר:</th>
                            <td><strong><?php echo $order->amount; ?> ₪</strong></td>
                        </tr>
                        <tr>
                            <th>סטטוס:</th>
                            <td>
                                <select class="ura-order-status" data-order-id="<?php echo $order->id; ?>">
                                    <?php echo $this->get_status_options($order->status); ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>תשלום:</th>
                            <td><?php echo $this->get_payment_status_label($order->payment_status); ?></td>
                        </tr>
                        <tr>
                            <th>תאריך יצירה:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($order->created_at)); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="ura-details-section">
                    <h3>👤 פרטי לקוח</h3>
                    <table class="ura-details-table">
                        <tr>
                            <th>שם:</th>
                            <td><?php echo $order->first_name . ' ' . $order->last_name; ?></td>
                        </tr>
                        <tr>
                            <th>טלפון:</th>
                            <td><?php echo $order->phone; ?></td>
                        </tr>
                        <tr>
                            <th>אימייל:</th>
                            <td><?php echo $order->email; ?></td>
                        </tr>
                        <tr>
                            <th>כתובת:</th>
                            <td>
                                <?php 
                                $address = $order->address_street . ' ' . $order->address_building;
                                if ($order->address_apartment) {
                                    $address .= ', דירה ' . $order->address_apartment;
                                }
                                $address .= ', ' . $order->address_zipcode;
                                echo $address;
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($files)): ?>
            <div class="ura-details-section">
                <h3>📎 קבצים מצורפים</h3>
                <div class="ura-files-list">
                    <?php foreach ($files as $file): ?>
                    <div class="ura-file-item">
                        <span class="ura-file-name"><?php echo $file->file_name; ?></span>
                        <span class="ura-file-size">(<?php echo size_format($file->file_size); ?>)</span>
                        <span class="ura-file-date"><?php echo date('d/m/Y H:i', strtotime($file->upload_date)); ?></span>
                        <button class="ura-btn ura-btn-small ura-download-file" data-file-id="<?php echo $file->id; ?>">הורד</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reports)): ?>
            <div class="ura-details-section">
                <h3>📊 דוחות שנוצרו</h3>
                <div class="ura-reports-list">
                    <?php foreach ($reports as $report): ?>
                    <div class="ura-report-item">
                        <span class="ura-report-name">דוח ניתוח</span>
                        <span class="ura-report-date"><?php echo date('d/m/Y H:i', strtotime($report->created_at)); ?></span>
                        <span class="ura-report-downloads">הורדות: <?php echo $report->download_count; ?>/<?php echo $report->max_downloads; ?></span>
                        <button class="ura-btn ura-btn-small ura-download-report" data-report-id="<?php echo $report->id; ?>">הורד דוח</button>
                        <button class="ura-btn ura-btn-small ura-send-report" data-report-id="<?php echo $report->id; ?>">שלח ללקוח</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="ura-details-section">
                <h3>📜 היסטוריית סטטוסים</h3>
                <div class="ura-status-history">
                    <?php if (!empty($status_history)): ?>
                        <?php foreach ($status_history as $history): ?>
                        <div class="ura-status-item">
                            <span class="ura-status-change"><?php echo $history->old_status; ?> → <?php echo $history->new_status; ?></span>
                            <span class="ura-status-date"><?php echo date('d/m/Y H:i', strtotime($history->changed_at)); ?></span>
                            <span class="ura-status-by">ע"י: <?php echo $history->changed_by_name ?: 'מערכת'; ?></span>
                            <?php if ($history->notes): ?>
                            <div class="ura-status-notes">הערה: <?php echo $history->notes; ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>לא נמצאה היסטוריה</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        wp_send_json_success($content);
    }
    
    private function send_status_change_notifications($order_id, $old_status, $new_status) {
        // כאן תיושם הלוגיקה לשליחת התראות על שינוי סטטוס
        // באמצעות URA_Email_Manager ו-URA_SMS_Manager
    }
}

new URA_Orders_Manager();
?>