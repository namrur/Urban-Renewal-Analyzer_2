<?php
// admin/prompts.php

class URA_Prompts_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_ura_save_prompt', array($this, 'save_prompt'));
        add_action('wp_ajax_ura_delete_prompt', array($this, 'delete_prompt'));
        add_action('wp_ajax_ura_optimize_prompt', array($this, 'optimize_prompt'));
        add_action('wp_ajax_ura_get_prompt_history', array($this, 'get_prompt_history'));
        add_action('wp_ajax_ura_restore_prompt_version', array($this, 'restore_prompt_version'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'urban-renewal',
            'ניהול פרומפטים',
            'פרומפטים',
            'manage_options',
            'urban-renewal-prompts',
            array($this, 'display_prompts_page')
        );
    }
    
    public function display_prompts_page() {
        ?>
        <div class="wrap ura-admin">
            <h1>ניהול פרומפטים</h1>
            
            <div class="ura-tabs">
                <a href="#" class="ura-tab active" data-tab="prompts-list">רשימת פרומפטים</a>
                <a href="#" class="ura-tab" data-tab="add-prompt">פרומפט חדש</a>
                <a href="#" class="ura-tab" data-tab="analysis-settings">הגדרות ניתוח</a>
            </div>
            
            <!-- רשימת פרומפטים -->
            <div id="prompts-list" class="ura-tab-content">
                <div class="ura-table-container">
                    <table class="ura-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>שם פרומפט</th>
                                <th width="200">בסיסי ידע משויכים</th>
                                <th width="120">רמת ניתוח</th>
                                <th width="100">סטטוס</th>
                                <th width="120">שימוש אחרון</th>
                                <th width="200">פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $this->display_prompts_list(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- הוספת/עריכת פרומפט -->
            <div id="add-prompt" class="ura-tab-content" style="display: none;">
                <div class="ura-form">
                    <form id="ura-prompt-form">
                        <input type="hidden" id="ura-prompt-id" name="prompt_id" value="">
                        
                        <div class="ura-form-group">
                            <label for="ura-prompt-name" class="ura-form-label">שם הפרומפט *</label>
                            <input type="text" id="ura-prompt-name" name="prompt_name" class="ura-form-input" required>
                        </div>
                        
                        <div class="ura-form-row">
                            <div class="ura-form-group">
                                <label for="ura-analysis-level" class="ura-form-label">רמת ניתוח</label>
                                <select id="ura-analysis-level" name="analysis_level" class="ura-form-input">
                                    <option value="standard">רגיל - איזון בין מהירות לדיוק</option>
                                    <option value="detailed">מפורט - ניתוח מעמיק יותר</option>
                                    <option value="strict">קפדני - ניתוח מלא ומקיף</option>
                                </select>
                            </div>
                            
                            <div class="ura-form-group">
                                <label class="ura-form-label">הערכת זמן</label>
                                <div id="ura-time-estimate" class="ura-time-estimate">
                                    <span id="ura-estimated-time">3-5 דקות</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label class="ura-form-label">בסיסי ידע משויכים</label>
                            <div id="ura-knowledge-bases-selection" class="ura-knowledge-bases-selection">
                                <?php $this->display_knowledge_bases_checkboxes(); ?>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-prompt-content" class="ura-form-label">תוכן הפרומפט *</label>
                            <textarea id="ura-prompt-content" name="prompt_content" class="ura-form-textarea" rows="15" required></textarea>
                            <div class="ura-prompt-actions">
                                <button type="button" id="ura-optimize-prompt" class="ura-btn ura-btn-secondary">
                                    🔧 אופטימיזציית AI
                                </button>
                                <button type="button" id="ura-show-prompt-history" class="ura-btn ura-btn-link">
                                    📜 היסטוריית גרסאות
                                </button>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label class="ura-form-label">הגדרות נוספות</label>
                            <div class="ura-checkbox-group">
                                <label>
                                    <input type="checkbox" id="ura-prompt-active" name="prompt_active" value="1" checked>
                                    פרומפט פעיל
                                </label>
                                <label>
                                    <input type="checkbox" id="ura-prompt-default" name="prompt_default" value="1">
                                    פרומפט ברירת מחדל
                                </label>
                            </div>
                        </div>
                        
                        <div class="ura-form-actions">
                            <button type="submit" class="ura-btn ura-btn-primary">שמור פרומפט</button>
                            <button type="button" id="ura-prompt-cancel" class="ura-btn ura-btn-link">ביטול</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- הגדרות ניתוח -->
            <div id="analysis-settings" class="ura-tab-content" style="display: none;">
                <div class="ura-form">
                    <form id="ura-analysis-settings-form">
                        <div class="ura-form-group">
                            <label for="ura-max-tokens" class="ura-form-label">מקסימום tokens לניתוח</label>
                            <input type="number" id="ura-max-tokens" name="max_tokens" class="ura-form-input" 
                                   value="4000" min="1000" max="16000">
                            <p class="ura-description">מספר ה-tokens המקסימלי לשימוש בכל ניתוח</p>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-temperature" class="ura-form-label">רמת יצירתיות (Temperature)</label>
                            <input type="range" id="ura-temperature" name="temperature" class="ura-slider" 
                                   min="0" max="1" step="0.1" value="0.3">
                            <div class="ura-slider-labels">
                                <span>מדויק</span>
                                <span>יצירתי</span>
                            </div>
                            <p class="ura-description">ערך נמוך = תשובות מדויקות יותר, ערך גבוה = תשובות יצירתיות יותר</p>
                        </div>
                        
                        <div class="ura-form-group">
                            <label class="ura-form-label">הגדרות מסמכים גדולים</label>
                            <div class="ura-checkbox-group">
                                <label>
                                    <input type="checkbox" name="split_large_docs" value="1" checked>
                                    חלוקה אוטומטית למקטעים
                                </label>
                                <label>
                                    <input type="checkbox" name="enable_context" value="1" checked>
                                    שמירת הקשר בין מקטעים
                                </label>
                            </div>
                        </div>
                        
                        <div class="ura-form-group">
                            <label for="ura-timeout" class="ura-form-label">זמן timeout (שניות)</label>
                            <input type="number" id="ura-timeout" name="timeout" class="ura-form-input" 
                                   value="30" min="10" max="120">
                        </div>
                        
                        <div class="ura-form-actions">
                            <button type="submit" class="ura-btn ura-btn-primary">שמור הגדרות</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal for AI optimization -->
        <div id="ura-optimization-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <h3>אופטימיזציית פרומפט</h3>
                <div class="ura-optimization-comparison">
                    <div class="ura-original-prompt">
                        <h4>פרומפט מקורי</h4>
                        <textarea id="ura-original-prompt-text" readonly></textarea>
                    </div>
                    <div class="ura-optimized-prompt">
                        <h4>פרומפט משופר</h4>
                        <textarea id="ura-optimized-prompt-text"></textarea>
                    </div>
                </div>
                <div class="ura-optimization-actions">
                    <button id="ura-apply-optimization" class="ura-btn ura-btn-primary">החל שיפורים</button>
                    <button id="ura-cancel-optimization" class="ura-btn ura-btn-link">בטל</button>
                </div>
            </div>
        </div>
        
        <!-- Modal for prompt history -->
        <div id="ura-history-modal" class="ura-modal">
            <div class="ura-modal-content">
                <span class="ura-modal-close">&times;</span>
                <h3>היסטוריית גרסאות פרומפט</h3>
                <div id="ura-prompt-history-content"></div>
            </div>
        </div>
        <?php
    }
    
    private function display_prompts_list() {
        global $wpdb;
        
        $prompts = $wpdb->get_results("
            SELECT p.*, 
                   COUNT(DISTINCT o.id) as usage_count,
                   MAX(o.created_at) as last_used
            FROM {$wpdb->prefix}ura_prompts p
            LEFT JOIN {$wpdb->prefix}ura_orders o ON p.id = o.prompt_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        
        if (empty($prompts)) {
            echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">לא נמצאו פרומפטים</td></tr>';
            return;
        }
        
        foreach ($prompts as $prompt) {
            $knowledge_bases = $this->get_prompt_knowledge_bases($prompt->id);
            $status_class = $prompt->is_active ? 'ura-status-active' : 'ura-status-inactive';
            $status_text = $prompt->is_active ? 'פעיל' : 'לא פעיל';
            $last_used = $prompt->last_used ? date('d/m/Y', strtotime($prompt->last_used)) : 'טרם נעשה שימוש';
            
            echo "
            <tr data-prompt-id='{$prompt->id}'>
                <td>
                    <strong>{$prompt->name}</strong>
                    " . ($prompt->is_default ? ' <span class=\"ura-badge\">ברירת מחדל</span>' : '') . "
                </td>
                <td>{$knowledge_bases}</td>
                <td>{$this->get_analysis_level_label($prompt->analysis_level)}</td>
                <td><span class='ura-status {$status_class}'>{$status_text}</span></td>
                <td>{$last_used}</td>
                <td>
                    <button class='ura-btn ura-btn-small ura-edit-prompt' data-prompt-id='{$prompt->id}'>עריכה</button>
                    <button class='ura-btn ura-btn-small ura-btn-danger ura-delete-prompt' data-prompt-id='{$prompt->id}'>מחיקה</button>
                    <button class='ura-btn ura-btn-small ura-test-prompt' data-prompt-id='{$prompt->id}'>בדיקה</button>
                </td>
            </tr>";
        }
    }
    
    private function display_knowledge_bases_checkboxes($selected_bases = array()) {
        global $wpdb;
        
        $knowledge_bases = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}ura_knowledge_base 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        
        if (empty($knowledge_bases)) {
            echo '<p>לא נמצאו בסיסי ידע. <a href="?page=urban-renewal-knowledge-base">צור בסיס ידע ראשון</a></p>';
            return;
        }
        
        foreach ($knowledge_bases as $kb) {
            $checked = in_array($kb->id, $selected_bases) ? 'checked' : '';
            echo "
            <label class='ura-checkbox-label'>
                <input type='checkbox' name='knowledge_bases[]' value='{$kb->id}' {$checked}>
                <span>{$kb->name}</span>
            </label>";
        }
    }
    
    public function save_prompt() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $prompt_data = array(
            'name' => sanitize_text_field($_POST['prompt_name']),
            'content' => wp_kses_post($_POST['prompt_content']),
            'analysis_level' => sanitize_text_field($_POST['analysis_level']),
            'is_active' => isset($_POST['prompt_active']) ? 1 : 0,
            'is_default' => isset($_POST['prompt_default']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        );
        
        $knowledge_bases = isset($_POST['knowledge_bases']) ? array_map('intval', $_POST['knowledge_bases']) : array();
        
        global $wpdb;
        
        if (!empty($_POST['prompt_id'])) {
            // עדכון פרומפט קיים
            $prompt_id = intval($_POST['prompt_id']);
            
            $result = $wpdb->update(
                "{$wpdb->prefix}ura_prompts",
                $prompt_data,
                array('id' => $prompt_id)
            );
            
            $message = 'הפרומפט עודכן בהצלחה';
        } else {
            // יצירת פרומפט חדש
            $prompt_data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                "{$wpdb->prefix}ura_prompts",
                $prompt_data
            );
            
            $prompt_id = $wpdb->insert_id;
            $message = 'הפרומפט נוצר בהצלחה';
        }
        
        if ($result !== false) {
            // עדכון בסיסי הידע המשויכים
            $this->update_prompt_knowledge_bases($prompt_id, $knowledge_bases);
            
            // אם זה פרומפט ברירת מחדל, בטל ברירת מחדל מאחרים
            if ($prompt_data['is_default']) {
                $wpdb->update(
                    "{$wpdb->prefix}ura_prompts",
                    array('is_default' => 0),
                    array('id !=' => $prompt_id)
                );
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'prompt_id' => $prompt_id
            ));
        } else {
            wp_send_json_error('שגיאה בשמירת הפרומפט');
        }
    }
    
    public function optimize_prompt() {
        check_ajax_referer('ura_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין הרשאות מתאימות');
        }
        
        $original_prompt = wp_kses_post($_POST['prompt_content']);
        $prompt_id = !empty($_POST['prompt_id']) ? intval($_POST['prompt_id']) : null;
        
        $deepseek_api = new URA_DeepSeek_API();
        $optimized_prompt = $deepseek_api->optimize_prompt($original_prompt, $prompt_id);
        
        if (is_wp_error($optimized_prompt)) {
            wp_send_json_error('שגיאה באופטימיזציה: ' . $optimized_prompt->get_error_message());
        }
        
        wp_send_json_success(array(
            'optimized_prompt' => $optimized_prompt
        ));
    }
    
    private function get_prompt_knowledge_bases($prompt_id) {
        global $wpdb;
        
        $knowledge_bases = $wpdb->get_results($wpdb->prepare("
            SELECT kb.name 
            FROM {$wpdb->prefix}ura_knowledge_base kb
            INNER JOIN {$wpdb->prefix}ura_prompt_knowledge pk ON kb.id = pk.knowledge_base_id
            WHERE pk.prompt_id = %d AND kb.is_active = 1
        ", $prompt_id));
        
        if (empty($knowledge_bases)) {
            return 'ללא שיוך';
        }
        
        $names = array();
        foreach ($knowledge_bases as $kb) {
            $names[] = $kb->name;
        }
        
        return implode(', ', $names);
    }
    
    private function update_prompt_knowledge_bases($prompt_id, $knowledge_base_ids) {
        global $wpdb;
        
        // מחיקת שיוכים קיימים
        $wpdb->delete(
            "{$wpdb->prefix}ura_prompt_knowledge",
            array('prompt_id' => $prompt_id)
        );
        
        // הוספת שיוכים חדשים
        foreach ($knowledge_base_ids as $kb_id) {
            $wpdb->insert(
                "{$wpdb->prefix}ura_prompt_knowledge",
                array(
                    'prompt_id' => $prompt_id,
                    'knowledge_base_id' => $kb_id
                )
            );
        }
    }
    
    private function get_analysis_level_label($level) {
        $labels = array(
            'standard' => 'רגיל',
            'detailed' => 'מפורט',
            'strict' => 'קפדני'
        );
        
        return isset($labels[$level]) ? $labels[$level] : $level;
    }
}

new URA_Prompts_Manager();
?>