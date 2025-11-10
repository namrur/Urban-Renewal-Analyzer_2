<?php
// admin/knowledge-base.php

class URA_Knowledge_Base_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_ura_save_knowledge_item', array($this, 'save_knowledge_item'));
        add_action('wp_ajax_ura_delete_knowledge_item', array($this, 'delete_knowledge_item'));
        add_action('wp_ajax_ura_upload_knowledge_file', array($this, 'upload_knowledge_file'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'urban-renewal',
            'בסיס ידע',
            'בסיס ידע',
            'manage_options',
            'urban-renewal-knowledge-base',
            array($this, 'display_knowledge_base_page')
        );
    }
    
    public function display_knowledge_base_page() {
        ?>
        <div class="wrap ura-admin">
            <h1>ניהול בסיס ידע</h1>
            
            <div class="ura-tabs">
                <a href="#" class="ura-tab active" data-tab="kb-categories">קטגוריות</a>
                <a href="#" class="ura-tab" data-tab="kb-items">פריטי ידע</a>
                <a href="#" class="ura-tab" data-tab="add-kb-item">הוספת פריט</a>
                <a href="#" class="ura-tab" data-tab="kb-settings">הגדרות</a>
            </div>
            
            <!-- קטגוריות -->
            <div id="kb-categories" class="ura-tab-content">
                <div class="ura-categories-grid">
                    <?php $this->display_categories(); ?>
                </div>
                
                <div class="ura-add-category">
                    <h3>הוספת קטגוריה חדשה</h3>
                    <form id="ura-category-form" class="ura-form-inline">
                        <input type="text" name="category_name" placeholder="שם קטגוריה" required>
                        <textarea name="category_description" placeholder="תיאור הקטגוריה"></textarea>
                        <button type="submit" class="ura-btn ura-btn-primary">הוסף קטגוריה</button>
                    </form>
                </div>
            </div>
            
            <!-- פריטי ידע -->
            <div id="kb-items" class="ura-tab-content" style="display: none;">
                <div class="ura-search-box">
                    <input type="text" id="ura-kb-search" placeholder="חיפוש בפריטי ידע...">
                    <select id="ura-kb-category-filter">
                        <option value="">כל הקטגוריות</option>
                        <?php $this->display_category_options(); ?>
                    </select>
                </div>
                
                <div class="ura-knowledge-items" id="ura-knowledge-items">
                    <?php $this->display_knowledge_items(); ?>
                </div>
            </div>
            
            <!-- הוספת פריט ידע -->
            <div id="add-kb-item" class="ura-tab-content" style="display: none;">
                <div class="ura-form">
                    <form id="ura-knowledge-item-form">
                        <input type="hidden" id="ura-kb-item-id" name="item_id" value="">
                        
                        <div class="ura-form-group">
                            <label for="ura-kb-title" class="ura-form-label">כותרת הפריט *</label>
                            <input type="text" id="ura-kb-title" name="title" class="ura-form-input" required>
                        </div>
                        
                        <div class="ura-form-row">
                            <div class="ura-form-group">
                                <label for="ura-kb-category" class="ura-form-label">קטגוריה *</label>
                                <select id="ura-kb-category" name="category" class="ura-form-input" required>
                                    <option value="">בחר קטגוריה</option>
                                    <?php $this->display_category_options(); ?>
                                </select>
                            </div>
                            
                            <div class="ura-form-group">
                                <label class="ura-form-label">סטטוס</label>
                                <div class="ura-radio-group">
                                    <label>
                                        <input type="radio" name="status" value="active" checked>
                                        פעיל
                                    </label>
                                    <label>
                                        <input type="radio" name="status" value="inactive">
                                        לא פעיל
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-kb-content" class="ura-form-label">תוכן הפריט *</label>
                            <?php 
                            wp_editor('', 'ura-kb-content', array(
                                'textarea_name' => 'content',
                                'editor_height' => 300,
                                'media_buttons' => true,
                                'tinymce' => array(
                                    'directionality' => 'rtl'
                                )
                            )); 
                            ?>
                        </div>
                        
                        <div class="ura-form-group">
                            <label class="ura-form-label">קבצים מצורפים</label>
                            <div class="ura-file-upload-area" id="ura-kb-file-upload">
                                <div class="ura-upload-placeholder">
                                    <span>גרור קבצים לכאן או</span>
                                    <button type="button" class="ura-btn ura-btn-secondary">בחר קבצים</button>
                                    <input type="file" id="ura-kb-files" multiple style="display: none;">
                                </div>
                                <div class="ura-uploaded-files" id="ura-uploaded-files"></div>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-kb-tags" class="ura-form-label">תגיות</label>
                            <input type="text" id="ura-kb-tags" name="tags" class="ura-form-input" 
                                   placeholder="הוסף תגיות מופרדות בפסיק">
                            <p class="ura-description">תגיות לעזרה בחיפוש וסינון</p>
                        </div>
                        
                        <div class="ura-form-actions">
                            <button type="submit" class="ura-btn ura-btn-primary">שמור פריט</button>
                            <button type="button" id="ura-kb-cancel" class="ura-btn ura-btn-link">ביטול</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- הגדרות -->
            <div id="kb-settings" class="ura-tab-content" style="display: none;">
                <div class="ura-form">
                    <form id="ura-kb-settings-form">
                        <div class="ura-form-group">
                            <label class="ura-form-label">אבטחת בסיס ידע</label>
                            <div class="ura-checkbox-group">
                                <label>
                                    <input type="checkbox" name="prevent_indexing" value="1" checked>
                                    מניעת אינדוקס על ידי מנועי חיפוש
                                </label>
                                <label>
                                    <input type="checkbox" name="restrict_access" value="1" checked>
                                    הגבלת גישה למשתמשים מורשים בלבד
                                </label>
                                <label>
                                    <input type="checkbox" name="enable_search" value="1" checked>
                                    הפעלת חיפוש פנימי
                                </label>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-kb-backup" class="ura-form-label">גיבוי אוטומטי</label>
                            <select id="ura-kb-backup" name="backup_frequency" class="ura-form-input">
                                <option value="daily">יומי</option>
                                <option value="weekly" selected>שבועי</option>
                                <option value="monthly">חודשי</option>
                                <option value="never">ללא גיבוי אוטומטי</option>
                            </select>
                        </div>
                        
                        <div class="ura-form-group">
                            <label class="ura-form-label">גיבוי ידני</label>
                            <div>
                                <button type="button" id="ura-export-kb" class="ura-btn ura-btn-secondary">ייצוא בסיס ידע</button>
                                <button type="button" id="ura-import-kb" class="ura-btn ura-btn-secondary">ייבוא בסיס ידע</button>
                            </div>
                        </div>
                        
                        <div class="ura-form-actions">
                            <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal for category items -->
        <div id="ura-category-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <h3 id="ura-category-modal-title">פריטים בקטגוריה</h3>
                <div id="ura-category-items-list"></div>
            </div>
        </div>
        <?php
    }
    
    private function display_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_results("
            SELECT category, COUNT(*) as item_count 
            FROM {$wpdb->prefix}ura_knowledge_base 
            WHERE is_active = 1 
            GROUP BY category 
            ORDER BY category ASC
        ");
        
        if (empty($categories)) {
            echo '<div class="ura-empty-state">לא נמצאו קטגוריות</div>';
            return;
        }
        
        foreach ($categories as $category) {
            echo "
            <div class='ura-category-card' data-category='" . esc_attr($category->category) . "'>
                <div class='ura-category-header'>
                    <h3>{$category->category}</h3>
                    <span class='ura-item-count'>{$category->item_count} פריטים</span>
                </div>
                <div class='ura-category-actions'>
                    <button class='ura-btn ura-btn-small ura-view-category'>צפה בפריטים</button>
                    <button class='ura-btn ura-btn-small ura-edit-category'>עריכה</button>
                </div>
            </div>";
        }
    }
    
    private function display_category_options() {
        global $wpdb;
        
        $categories = $wpdb->get_results("
            SELECT DISTINCT category 
            FROM {$wpdb->prefix}ura_knowledge_base 
            ORDER BY category ASC
        ");
        
        foreach ($categories as $category) {
            echo "<option value='" . esc_attr($category->category) . "'>{$category->category}</option>";
        }
    }
    
    private function display_knowledge_items($category = '') {
        global $wpdb;
        
        $where = "WHERE is_active = 1";
        if ($category) {
            $where .= $wpdb->prepare(" AND category = %s", $category);
        }
        
        $items = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}ura_knowledge_base 
            {$where}
            ORDER BY created_at DESC
        ");
        
        if (empty($items)) {
            echo '<div class="ura-empty-state">לא נמצאו פריטי ידע</div>';
            return;
        }
        
        foreach ($items as $item) {
            $excerpt = wp_trim_words(strip_tags($item->content), 20);
            $file_count = $item->files ? count(explode(',', $item->files)) : 0;
            
            echo "
            <div class='ura-knowledge-item' data-item-id='{$item->id}'>
                <div class='ura-item-header'>
                    <h4>{$item->name}</h4>
                    <span class='ura-item-category'>{$item->category}</span>
                </div>
                <div class='ura-item-excerpt'>{$excerpt}</div>
                <div class='ura-item-meta'>
                    <span class='ura-file-count'>📎 {$file_count} קבצים</span>
                    <span class='ura-updated-date'>עודכן: " . date('d/m/Y', strtotime($item->updated_at ?: $item->created_at)) . "</span>
                </div>
                <div class='ura-item-actions'>
                    <button class='ura-btn ura-btn-small ura-edit-item' data-item-id='{$item->id}'>עריכה</button>
                    <button class='ura-btn ura-btn-small ura-btn-danger ura-delete-item' data-item-id='{$item->id}'>מחיקה</button>
                    <button class='ura-btn ura-btn-small ura-preview-item' data-item-id='{$item->id}'>תצוגה מקדימה</button>
                </div>
            </div>";
        }
    }
    
    public function save_knowledge_item() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $item_data = array(
            'name' => sanitize_text_field($_POST['title']),
            'category' => sanitize_text_field($_POST['category']),
            'content' => wp_kses_post($_POST['content']),
            'tags' => sanitize_text_field($_POST['tags']),
            'is_active' => ($_POST['status'] === 'active') ? 1 : 0,
            'updated_at' => current_time('mysql'),
        );
        
        global $wpdb;
        
        if (!empty($_POST['item_id'])) {
            // עדכון פריט קיים
            $item_id = intval($_POST['item_id']);
            
            $result = $wpdb->update(
                "{$wpdb->prefix}ura_knowledge_base",
                $item_data,
                array('id' => $item_id)
            );
            
            $message = 'פריט הידע עודכן בהצלחה';
        } else {
            // יצירת פריט חדש
            $item_data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                "{$wpdb->prefix}ura_knowledge_base",
                $item_data
            );
            
            $item_id = $wpdb->insert_id;
            $message = 'פריט הידע נוצר בהצלחה';
        }
        
        if ($result !== false) {
            // שמירת קבצים מצורפים
            if (!empty($_POST['attached_files'])) {
                $this->save_attached_files($item_id, $_POST['attached_files']);
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'item_id' => $item_id
            ));
        } else {
            wp_send_json_error('שגיאה בשמירת פריט הידע');
        }
    }
    
    private function save_attached_files($item_id, $files) {
        global $wpdb;
        
        // כאן תיושם הלוגיקה לשמירת קבצים ב-S3 ושמירת המידע במסד הנתונים
        // זה דורש אינטגרציה עם class-file-upload.php
    }
}

new URA_Knowledge_Base_Manager();

// אבטחת בסיס הידע - מניעת גישה ציבורית
function ura_protect_knowledge_base() {
    if (is_singular('ura_knowledge') || is_post_type_archive('ura_knowledge')) {
        if (!current_user_can('manage_options')) {
            wp_die('אין גישה לדף זה');
        }
    }
}
add_action('template_redirect', 'ura_protect_knowledge_base');

// מניעת אינדוקס
function ura_prevent_kb_indexing($robots) {
    if (is_post_type_archive('ura_knowledge') || is_singular('ura_knowledge')) {
        return 'noindex, nofollow';
    }
    return $robots;
}
add_filter('wp_robots', 'ura_prevent_kb_indexing');
?>