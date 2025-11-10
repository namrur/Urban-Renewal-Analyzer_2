<?php
// includes/class-pdf-generator.php

require_once URA_PLUGIN_PATH . 'vendor/autoload.php'; // עבור TCPDF

use TCPDF as TCPDF;

class URA_PDF_Generator {
    
    private $pdf;
    private $header_content;
    private $watermark_path;
    private $password_protection;
    
    public function __construct() {
        $this->init_pdf();
        $this->load_settings();
    }
    
    private function init_pdf() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // הגדרות בסיסיות
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('Urban Renewal Analyzer');
        $this->pdf->SetTitle('דוח ניתוח הסכם');
        $this->pdf->SetSubject('ניתוח הסכם התחדשות עירונית');
        $this->pdf->SetKeywords('התחדשות, עירונית, ניתוח, הסכם');
        
        // הגדרות עברית
        $this->pdf->setRTL(true);
        $this->pdf->setFontSubsetting(true);
        $this->pdf->SetFont('dejavusans', '', 10, '', true);
        
        // ריווחים
        $this->pdf->SetMargins(15, 40, 15);
        $this->pdf->SetHeaderMargin(10);
        $this->pdf->SetFooterMargin(10);
        $this->pdf->SetAutoPageBreak(TRUE, 25);
    }
    
    private function load_settings() {
        $settings = get_option('ura_pdf_settings', array());
        
        $this->header_content = isset($settings['header_content']) ? 
                               $settings['header_content'] : $this->get_default_header();
        $this->watermark_path = isset($settings['watermark_path']) ? $settings['watermark_path'] : '';
        $this->password_protection = isset($settings['password_protection']) ? 
                                   $settings['password_protection'] : true;
    }
    
    /**
     * יצירת דוח PDF
     */
    public function generate_report($analysis_content, $user_data, $order_data, $password = null) {
        try {
            // הגדרת סיסמה אם נדרש
            if ($this->password_protection && $password) {
                $this->pdf->SetProtection(
                    array('print', 'copy'), 
                    $password, 
                    $password, 
                    0, 
                    null
                );
            }
            
            // הוספת עמוד ראשון
            $this->add_cover_page($user_data, $order_data);
            
            // הוספת תוכן הניתוח
            $this->add_analysis_content($analysis_content, $user_data);
            
            // הוספת עמוד סיכום
            $this->add_summary_page($analysis_content);
            
            // שמירת הקובץ
            $filename = $this->generate_filename($user_data, $order_data);
            $file_path = $this->get_upload_path() . $filename;
            
            $this->pdf->Output($file_path, 'F');
            
            return array(
                'success' => true,
                'file_path' => $file_path,
                'file_name' => $filename,
                'password' => $password
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * עמוד שער
     */
    private function add_cover_page($user_data, $order_data) {
        $this->pdf->AddPage();
        
        // לוגו וכותרת
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 15, 'דוח ניתוח הסכם התחדשות עירונית', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // פרטי הלקוח
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'פרטי הלקוח:', 0, 1);
        $this->pdf->SetFont('dejavusans', '', 11);
        
        $client_info = array(
            'שם מלא' => $user_data['first_name'] . ' ' . $user_data['last_name'],
            'טלפון' => $user_data['phone'],
            'אימייל' => $user_data['email'],
            'כתובת' => $this->format_address($user_data),
            'מספר הזמנה' => $order_data['order_number'],
            'תאריך הדוח' => date('d/m/Y'),
            'סוג השירות' => $order_data['service_name']
        );
        
        foreach ($client_info as $label => $value) {
            $this->pdf->Cell(40, 8, $label . ':', 0, 0, 'R');
            $this->pdf->Cell(0, 8, $value, 0, 1);
        }
        
        $this->pdf->Ln(15);
        
        // הערת סודיות
        $this->pdf->SetFont('dejavusans', 'I', 10);
        $this->pdf->MultiCell(0, 8, 
            'הערה: דוח זה הוכן באופן מקצועי ומהווה חוות דעת מקצועית. ' .
            'המידע בדוח זה הינו סודי ומיועד לשימוש הלקוח בלבד.', 
            0, 'C'
        );
    }
    
    /**
     * תוכן הניתוח
     */
    private function add_analysis_content($analysis_content, $user_data) {
        $this->pdf->AddPage();
        
        // כותרת תוכן עניינים
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'תוכן עניינים', 0, 1, 'C');
        $this->pdf->Ln(5);
        
        // חלוקת התוכן לסעיפים
        $sections = $this->parse_analysis_sections($analysis_content);
        
        // תוכן עניינים
        $this->pdf->SetFont('dejavusans', '', 11);
        $page_num = 3; // מתחיל מעמוד 3
        
        foreach ($sections as $section) {
            $this->pdf->Cell(0, 8, $section['title'], 0, 1);
            $this->pdf->Cell(0, 8, '...... ' . $page_num, 0, 1, 'R');
            $page_num += ceil(strlen($section['content']) / 1500); // הערכת עמודים
        }
        
        // הוספת הסעיפים
        foreach ($sections as $section) {
            $this->pdf->AddPage();
            $this->add_section($section['title'], $section['content']);
        }
    }
    
    /**
     * עמוד סיכום
     */
    private function add_summary_page($analysis_content) {
        $this->pdf->AddPage();
        
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, 'סיכום ומסקנות', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // חילוץ נקודות מפתח
        $key_points = $this->extract_key_points($analysis_content);
        
        $this->pdf->SetFont('dejavusans', '', 11);
        foreach ($key_points as $point) {
            $this->pdf->MultiCell(0, 8, '• ' . $point, 0, 'R');
            $this->pdf->Ln(2);
        }
        
        $this->pdf->Ln(10);
        
        // המלצות
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'המלצות:', 0, 1);
        $this->pdf->SetFont('dejavusans', '', 11);
        $this->pdf->MultiCell(0, 8, 
            'מומלץ להתייעץ עם עורך דין מומחה בתחום התחדשות עירונית ' .
            'לפני חתימה על ההסכם. הדוח מהווה חוות דעת מקצועית אך ' .
            'אינו מחליף ייעוץ משפטי פרטני.'
        );
    }
    
    /**
     * הוספת סעיף
     */
    private function add_section($title, $content) {
        // כותרת הסעיף
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, $title, 0, 1);
        $this->pdf->Ln(5);
        
        // תוכן הסעיף
        $this->pdf->SetFont('dejavusans', '', 11);
        $this->pdf->MultiCell(0, 8, $content, 0, 'R');
    }
    
    /**
     * Header עם לוגו ופרטים
     */
    public function Header() {
        if ($this->pdf->PageNo() === 1) {
            return; // לא להציג header בעמוד השער
        }
        
        $this->pdf->SetY(10);
        $this->pdf->SetFont('dejavusans', '', 8);
        
        // קו separator
        $this->pdf->Line(15, 18, 195, 18);
        
        // תוכן header
        $header_html = $this->header_content;
        $this->pdf->writeHTML($header_html, true, false, true, false, '');
    }
    
    /**
     * Footer עם מספר עמוד
     */
    public function Footer() {
        $this->pdf->SetY(-15);
        $this->pdf->SetFont('dejavusans', 'I', 8);
        $this->pdf->Cell(0, 10, 'עמוד ' . $this->pdf->PageNo(), 0, 0, 'C');
    }
    
    /**
     * פונקציות עזר
     */
    private function get_default_header() {
        return '
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="33%" align="right">דוח ניתוח הסכם התחדשות עירונית</td>
                <td width="34%" align="center">' . date('d/m/Y') . '</td>
                <td width="33%" align="left">סודי - לשימוש הלקוח בלבד</td>
            </tr>
        </table>';
    }
    
    private function format_address($user_data) {
        $address = $user_data['street'] . ' ' . $user_data['building'];
        if (!empty($user_data['apartment'])) {
            $address .= ', דירה ' . $user_data['apartment'];
        }
        $address .= ', ' . $user_data['zipcode'];
        return $address;
    }
    
    private function generate_filename($user_data, $order_data) {
        $name = sanitize_file_name($user_data['first_name'] . '_' . $user_data['last_name']);
        $order = sanitize_file_name($order_data['order_number']);
        return 'report_' . $name . '_' . $order . '_' . time() . '.pdf';
    }
    
    private function get_upload_path() {
        $upload_dir = wp_upload_dir();
        $ura_dir = $upload_dir['basedir'] . '/ura-reports/';
        
        if (!file_exists($ura_dir)) {
            wp_mkdir_p($ura_dir);
        }
        
        return $ura_dir;
    }
    
    private function parse_analysis_sections($content) {
        // חלוקה לפי כותרות (##, ###)
        $pattern = '/#{2,3}\s+(.+)/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        $sections = array();
        $current_pos = 0;
        
        foreach ($matches as $match) {
            $title = trim($match[1]);
            $title_pos = strpos($content, $match[0], $current_pos);
            
            if ($current_pos > 0) {
                $previous_section_content = substr($content, $current_pos, $title_pos - $current_pos);
                $sections[] = array(
                    'title' => $previous_title,
                    'content' => trim($previous_section_content)
                );
            }
            
            $previous_title = $title;
            $current_pos = $title_pos + strlen($match[0]);
        }
        
        // סעיף אחרון
        if ($current_pos > 0) {
            $last_section_content = substr($content, $current_pos);
            $sections[] = array(
                'title' => $previous_title,
                'content' => trim($last_section_content)
            );
        }
        
        // אם אין כותרות, יצירת סעיף אחד
        if (empty($sections)) {
            $sections[] = array(
                'title' => 'ניתוח ההסכם',
                'content' => $content
            );
        }
        
        return $sections;
    }
    
    private function extract_key_points($content) {
        // חילוץ נקודות מפתח (רשימות, נקודות חשובות)
        $key_points = array();
        
        // חיפוש אחר רשימות
        if (preg_match_all('/•\s*(.+)|-\s*(.+)/', $content, $matches)) {
            foreach ($matches[0] as $point) {
                $clean_point = preg_replace('/^[•\-]\s*/', '', $point);
                if (strlen($clean_point) > 10) { // נקודות משמעותיות בלבד
                    $key_points[] = $clean_point;
                }
            }
        }
        
        // אם לא נמצאו רשימות, חלוקה למשפטים משמעותיים
        if (empty($key_points)) {
            $sentences = preg_split('/(?<=[.?!])\s+/', $content);
            $key_points = array_slice(array_filter($sentences), 0, 5); // 5 משפטים ראשונים
        }
        
        return array_slice($key_points, 0, 10); // מקסימום 10 נקודות
    }
}
?>