<?php
// includes/class-deepseek-api.php

class URA_DeepSeek_API {
    
    private $api_key;
    private $api_url = 'https://api.deepseek.com/v1/chat/completions';
    private $max_tokens = 4000;
    private $timeout = 30;
    
    public function __construct() {
        $settings = get_option('ura_settings');
        $this->api_key = isset($settings['deepseek_api_key']) ? $settings['deepseek_api_key'] : '';
    }
    
    /**
     * ניתוח מסמך באמצעות DeepSeek AI
     */
    public function analyze_document($document_content, $prompt_id, $knowledge_base_context = '') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'מפתח API לא הוגדר');
        }
        
        // קבלת הפרומפט מהמסד נתונים
        $prompt = $this->get_prompt($prompt_id);
        if (is_wp_error($prompt)) {
            return $prompt;
        }
        
        // בניית הקונטקסט המלא
        $full_prompt = $this->build_full_prompt($prompt, $document_content, $knowledge_base_context);
        
        // חלוקה למקטעים אם המסמך ארוך מדי
        if ($this->is_content_too_long($full_prompt)) {
            return $this->analyze_large_document($document_content, $prompt, $knowledge_base_context);
        }
        
        return $this->send_analysis_request($full_prompt);
    }
    
    /**
     * ניתוח מסמך גדול עם חלוקה למקטעים
     */
    private function analyze_large_document($document_content, $prompt, $knowledge_base_context) {
        $sections = $this->split_document_into_sections($document_content);
        $results = array();
        
        foreach ($sections as $index => $section) {
            $context = $this->build_context_for_section($results, $index);
            $full_prompt = $this->build_full_prompt($prompt, $section, $knowledge_base_context, $context);
            
            $section_result = $this->send_analysis_request($full_prompt);
            
            if (!is_wp_error($section_result)) {
                $results[] = array(
                    'section' => $index + 1,
                    'content' => $section_result,
                    'tokens_used' => $this->estimate_tokens($section_result)
                );
            }
            
            // הפסקה קצרה בין בקשות
            if (count($sections) > 1) {
                sleep(1);
            }
        }
        
        return $this->combine_section_results($results, $prompt);
    }
    
    /**
     * שליחת בקשה ל-API
     */
    private function send_analysis_request($prompt_content) {
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key
        );
        
        $body = array(
            'model' => 'deepseek-chat',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'אתה מומחה ישראלי להתחדשות עירונית עם ידע מעמיק בחוקי תכנון ובנייה, תקנות ונהלים.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt_content
                )
            ),
            'max_tokens' => $this->max_tokens,
            'temperature' => 0.3,
            'top_p' => 0.9
        );
        
        $args = array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => $this->timeout
        );
        
        $response = wp_remote_post($this->api_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200 && isset($response_body['choices'][0]['message']['content'])) {
            return $response_body['choices'][0]['message']['content'];
        } else {
            $error_message = isset($response_body['error']['message']) ? 
                            $response_body['error']['message'] : 'Unknown API error';
            return new WP_Error('api_error', $error_message);
        }
    }
    
    /**
     * אופטימיזציית פרומפט באמצעות AI
     */
    public function optimize_prompt($original_prompt, $prompt_id = null) {
        $optimization_prompt = "כמומחה לכתיבת פרומפטים ל-AI, אנא优化 את הפרומפט הבא לניתוח הסכמי התחדשות עירונית.
        
        הפרומפט המקורי:
        {$original_prompt}
        
        אנא:
        1. שפר את הבהירות והדיוק
        2. הוסף הוראות ספציפיות לניתוח משפטי
        3. ודא שהשפה מקצועית ומתאימה להקשר הישראלי
        4. הוסף הנחיות לפורמט התשובה
        5. שמור על עברית תקינה ומדויקת
        
        החזר רק את הפרומפט המשופר, ללא הסברים נוספים.";
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key
        );
        
        $body = array(
            'model' => 'deepseek-chat',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $optimization_prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.7
        );
        
        $args = array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => $this->timeout
        );
        
        $response = wp_remote_post($this->api_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['choices'][0]['message']['content'])) {
            $optimized_prompt = trim($response_body['choices'][0]['message']['content']);
            
            // שמירת הגרסה בהיסטוריה אם יש prompt_id
            if ($prompt_id) {
                $this->save_prompt_version($prompt_id, $original_prompt, $optimized_prompt);
            }
            
            return $optimized_prompt;
        }
        
        return new WP_Error('optimization_failed', 'Failed to optimize prompt');
    }
    
    /**
     * חלוקת מסמך ארוך למקטעים
     */
    private function split_document_into_sections($content, $max_section_length = 3000) {
        // חלוקה לפי פסקאות ראשיות
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $sections = array();
        $current_section = '';
        
        foreach ($paragraphs as $paragraph) {
            if (strlen($current_section) + strlen($paragraph) < $max_section_length) {
                $current_section .= $paragraph . "\n\n";
            } else {
                if (!empty($current_section)) {
                    $sections[] = trim($current_section);
                }
                $current_section = $paragraph . "\n\n";
            }
        }
        
        if (!empty($current_section)) {
            $sections[] = trim($current_section);
        }
        
        // אם עדיין יש מקטעים ארוכים מדי, חלוקה לפי משפטים
        if (count($sections) === 1 && strlen($sections[0]) > $max_section_length) {
            return $this->split_by_sentences($content, $max_section_length);
        }
        
        return $sections;
    }
    
    private function split_by_sentences($content, $max_length) {
        $sentences = preg_split('/(?<=[.?!])\s+/', $content);
        $sections = array();
        $current_section = '';
        
        foreach ($sentences as $sentence) {
            if (strlen($current_section) + strlen($sentence) < $max_length) {
                $current_section .= $sentence . ' ';
            } else {
                if (!empty($current_section)) {
                    $sections[] = trim($current_section);
                }
                $current_section = $sentence . ' ';
            }
        }
        
        if (!empty($current_section)) {
            $sections[] = trim($current_section);
        }
        
        return $sections;
    }
    
    /**
     * פונקציות עזר
     */
    private function get_prompt($prompt_id) {
        global $wpdb;
        $prompt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ura_prompts WHERE id = %d AND is_active = 1",
            $prompt_id
        ));
        
        if (!$prompt) {
            return new WP_Error('prompt_not_found', 'Prompt not found');
        }
        
        return $prompt;
    }
    
    private function build_full_prompt($prompt, $document_content, $knowledge_base_context = '', $previous_context = '') {
        $full_prompt = $prompt->content . "\n\n";
        
        if (!empty($knowledge_base_context)) {
            $full_prompt .= "קונטקסט נוסף:\n" . $knowledge_base_context . "\n\n";
        }
        
        if (!empty($previous_context)) {
            $full_prompt .= "ניתוחים קודמים:\n" . $previous_context . "\n\n";
        }
        
        $full_prompt .= "תוכן המסמך לניתוח:\n" . $document_content . "\n\n";
        $full_prompt .= "אנא בצע ניתוח מפורט לפי ההנחיות שלמעלה.";
        
        return $full_prompt;
    }
    
    private function is_content_too_long($content) {
        $estimated_tokens = $this->estimate_tokens($content);
        return $estimated_tokens > 3000; // רזרבה ל-max_tokens
    }
    
    private function estimate_tokens($text) {
        // הערכה גסה - 1 token ≈ 4 characters
        return strlen($text) / 4;
    }
    
    private function build_context_for_section($previous_results, $current_section_index) {
        if (empty($previous_results)) {
            return '';
        }
        
        $context = "סיכום ניתוחים קודמים:\n";
        foreach ($previous_results as $result) {
            $context .= "מקטע {$result['section']}:\n";
            $context .= substr($result['content'], 0, 500) . "...\n\n";
        }
        
        return $context;
    }
    
    private function combine_section_results($results, $prompt) {
        if (count($results) === 1) {
            return $results[0]['content'];
        }
        
        $combined_content = "דוח ניתוח מסמך - סיכום כולל\n\n";
        $combined_content .= "המסמך נותח ב-" . count($results) . " מקטעים.\n\n";
        
        foreach ($results as $result) {
            $combined_content .= "=== מקטע {$result['section']} ===\n";
            $combined_content .= $result['content'] . "\n\n";
        }
        
        return $combined_content;
    }
    
    private function save_prompt_version($prompt_id, $original_content, $optimized_content) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}ura_prompt_versions",
            array(
                'prompt_id' => $prompt_id,
                'original_content' => $original_content,
                'optimized_content' => $optimized_content,
                'created_at' => current_time('mysql')
            )
        );
    }
}
?>