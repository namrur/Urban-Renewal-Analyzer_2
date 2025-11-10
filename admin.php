<?php
// admin/admin.php

class URA_Admin {
    
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function admin_init() {
        // אתחול הגדרות מנהל
    }
    
    public function display_dashboard() {
        ?>
        <div class="wrap ura-admin">
            <h1>מערכת ניתוח הסכמי התחדשות עירונית</h1>
            
            <div class="ura-dashboard-widgets">
                <div class="ura-widget">
                    <h3>סטטיסטיקות</h3>
                    <div class="ura-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $this->get_total_orders(); ?></span>
                            <span class="stat-label">הזמנות</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $this->get_pending_orders(); ?></span>
                            <span class="stat-label">ממתינים לטיפול</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $this->get_total_users(); ?></span>
                            <span class="stat-label">לקוחות</span>
                        </div>
                    </div>
                </div>
                
                <div class="ura-widget">
                    <h3>פעולות מהירות</h3>
                    <div class="quick-actions">
                        <a href="?page=urban-renewal-orders" class="button button-primary">ניהול הזמנות</a>
                        <a href="?page=urban-renewal-services" class="button">ניהול שירותים</a>
                        <a href="?page=urban-renewal-knowledge-base" class="button">בסיס ידע</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_total_orders() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ura_orders");
    }
    
    private function get_pending_orders() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wp->prefix}ura_orders WHERE status = 'new'");
    }
    
    private function get_total_users() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ura_users");
    }
}
?>