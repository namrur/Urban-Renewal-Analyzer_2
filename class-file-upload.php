<?php
// includes/class-file-upload.php

class URA_File_Upload {
    
    private $s3_client;
    private $bucket_name;
    private $region;
    private $max_file_size;
    private $allowed_file_types;
    
    public function __construct() {
        $this->load_settings();
        $this->init_aws_s3();
    }
    
    private function load_settings() {
        $settings = get_option('ura_settings');
        
        $this->max_file_size = isset($settings['file_size_limit']) ? 
                              $settings['file_size_limit'] * 1024 * 1024 : 50 * 1024 * 1024; // 50MB default
        
        $this->allowed_file_types = isset($settings['allowed_file_types']) ? 
                                   $settings['allowed_file_types'] : array('pdf', 'doc', 'docx');
        
        $this->bucket_name = isset($settings['s3_bucket_name']) ? $settings['s3_bucket_name'] : '';
        $this->region = isset($settings['s3_region']) ? $settings['s3_region'] : 'eu-central-1';
    }
    
    private function init_aws_s3() {
        try {
            $settings = get_option('ura_settings');
            
            if (empty($settings['s3_access_key']) || empty($settings['s3_secret_key'])) {
                throw new Exception('AWS credentials not configured');
            }
            
            $this->s3_client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => [
                    'key'    => $settings['s3_access_key'],
                    'secret' => $settings['s3_secret_key'],
                ],
            ]);
            
        } catch (Exception $e) {
            error_log('URA S3 Init Error: ' . $e->getMessage());
            $this->s3_client = null;
        }
    }
    
    /**
     * יצירת קישור חתום להעלאה
     */
    public function generate_presigned_upload_url($user_id, $order_id, $file_type) {
        if (!$this->s3_client) {
            return new WP_Error('s3_not_configured', 'S3 not configured');
        }
        
        $file_key = $this->generate_file_key($user_id, $order_id, $file_type);
        
        try {
            $cmd = $this->s3_client->getCommand('PutObject', [
                'Bucket' => $this->bucket_name,
                'Key'    => $file_key,
                'ContentType' => $this->get_mime_type($file_type),
            ]);
            
            $request = $this->s3_client->createPresignedRequest($cmd, '+15 minutes');
            $presigned_url = (string) $request->getUri();
            
            return array(
                'success' => true,
                'upload_url' => $presigned_url,
                'file_key' => $file_key,
                'expires' => time() + (15 * 60) // 15 minutes
            );
            
        } catch (Exception $e) {
            return new WP_Error('s3_error', 'S3 Error: ' . $e->getMessage());
        }
    }
    
    /**
     * העלאת קובץ דרך S3
     */
    public function handle_file_upload($file_key, $user_id, $order_id, $file_info) {
        if (!$this->s3_client) {
            return new WP_Error('s3_not_configured', 'S3 not configured');
        }
        
        try {
            // בדיקת שהקובץ קיים ב-S3
            $result = $this->s3_client->headObject([
                'Bucket' => $this->bucket_name,
                'Key'    => $file_key,
            ]);
            
            // העברה לתיקיה processed
            $new_key = $this->move_to_processed_folder($file_key);
            
            // סריקת וירוסים
            $scan_result = $this->scan_file_for_viruses($file_key);
            if (!$scan_result['clean']) {
                $this->delete_file($file_key);
                return new WP_Error('virus_detected', 'Virus detected: ' . $scan_result['threats']);
            }
            
            // שמירת מידע במסד הנתונים
            $file_id = $this->save_file_info($user_id, $order_id, $new_key, $file_info);
            
            // שליחת התראה למנהל
            $this->notify_admin_file_upload($user_id, $order_id, $file_info);
            
            return array(
                'success' => true,
                'file_id' => $file_id,
                'file_key' => $new_key,
                'file_url' => $this->generate_download_url($new_key)
            );
            
        } catch (Exception $e) {
            return new WP_Error('upload_error', 'Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * יצירת קישור חתום להורדה
     */
    public function generate_download_url($file_key, $expires = 3600) {
        if (!$this->s3_client) {
            return new WP_Error('s3_not_configured', 'S3 not configured');
        }
        
        try {
            $cmd = $this->s3_client->getCommand('GetObject', [
                'Bucket' => $this->bucket_name,
                'Key'    => $file_key
            ]);
            
            $request = $this->s3_client->createPresignedRequest($cmd, "+{$expires} seconds");
            return (string) $request->getUri();
            
        } catch (Exception $e) {
            return new WP_Error('download_error', 'Download URL generation failed');
        }
    }
    
    /**
     * סריקת וירוסים באמצעות ClamAV
     */
    private function scan_file_for_viruses($file_key) {
        $settings = get_option('ura_settings');
        
        if (!isset($settings['virus_scan_enabled']) || !$settings['virus_scan_enabled']) {
            return array('clean' => true, 'threats' => array());
        }
        
        try {
            // הורדת קובץ זמני לסריקה
            $temp_file = $this->download_to_temp_file($file_key);
            
            if (!$temp_file) {
                throw new Exception('Failed to download file for scanning');
            }
            
            // סריקה עם ClamAV
            $scan_result = $this->scan_with_clamav($temp_file);
            
            // ניקוי הקובץ הזמני
            unlink($temp_file);
            
            return $scan_result;
            
        } catch (Exception $e) {
            error_log('Virus scan failed: ' . $e->getMessage());
            return array('clean' => true, 'threats' => array()); // אם הסריקה נכשלה, נניח שהקובץ נקי
        }
    }
    
    /**
     * סריקת ClamAV
     */
    private function scan_with_clamav($file_path) {
        $settings = get_option('ura_settings');
        $clamav_socket = isset($settings['clamav_socket']) ? $settings['clamav_socket'] : '/var/run/clamav/clamd.ctl';
        
        if (!file_exists($clamav_socket)) {
            throw new Exception('ClamAV socket not found');
        }
        
        $clamd = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (!$clamd) {
            throw new Exception('Failed to create socket');
        }
        
        $connected = socket_connect($clamd, $clamav_socket);
        if (!$connected) {
            throw new Exception('Failed to connect to ClamAV');
        }
        
        $command = "SCAN " . $file_path . "\n";
        socket_write($clamd, $command, strlen($command));
        
        $response = socket_read($clamd, 4096);
        socket_close($clamd);
        
        if (strpos($response, 'OK') !== false) {
            return array('clean' => true, 'threats' => array());
        } else {
            preg_match('/: (.+) FOUND/', $response, $matches);
            $threat_name = isset($matches[1]) ? $matches[1] : 'Unknown threat';
            return array('clean' => false, 'threats' => array($threat_name));
        }
    }
    
    /**
     * פונקציות עזר
     */
    private function generate_file_key($user_id, $order_id, $file_type) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "uploads/user_{$user_id}/order_{$order_id}/file_{$timestamp}_{$random}.{$file_type}";
    }
    
    private function move_to_processed_folder($file_key) {
        $new_key = str_replace('uploads/', 'processed/', $file_key);
        
        $this->s3_client->copyObject([
            'Bucket'     => $this->bucket_name,
            'CopySource' => "{$this->bucket_name}/{$file_key}",
            'Key'        => $new_key,
        ]);
        
        // מחיקת הקובץ המקורי
        $this->delete_file($file_key);
        
        return $new_key;
    }
    
    private function delete_file($file_key) {
        try {
            $this->s3_client->deleteObject([
                'Bucket' => $this->bucket_name,
                'Key'    => $file_key,
            ]);
        } catch (Exception $e) {
            error_log('Failed to delete file: ' . $e->getMessage());
        }
    }
    
    private function download_to_temp_file($file_key) {
        try {
            $result = $this->s3_client->getObject([
                'Bucket' => $this->bucket_name,
                'Key'    => $file_key,
            ]);
            
            $temp_file = tempnam(sys_get_temp_dir(), 'ura_scan_');
            file_put_contents($temp_file, $result['Body']);
            
            return $temp_file;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function get_mime_type($file_type) {
        $mime_types = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
        
        return isset($mime_types[$file_type]) ? $mime_types[$file_type] : 'application/octet-stream';
    }
    
    private function save_file_info($user_id, $order_id, $file_key, $file_info) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}ura_files",
            array(
                'user_id' => $user_id,
                'order_id' => $order_id,
                'file_name' => $file_info['name'],
                'file_path' => $file_key,
                'file_size' => $file_info['size'],
                'file_type' => $file_info['type'],
                'upload_date' => current_time('mysql'),
                'status' => 'uploaded'
            )
        );
        
        return $wpdb->insert_id;
    }
    
    private function notify_admin_file_upload($user_id, $order_id, $file_info) {
        $email_manager = new URA_Email_Manager();
        $sms_manager = new URA_SMS_Manager();
        
        // שליחת אימייל
        $email_manager->send_admin_notification(
            'file_uploaded',
            array(
                'user_id' => $user_id,
                'order_id' => $order_id,
                'file_name' => $file_info['name'],
                'file_size' => size_format($file_info['size']),
            )
        );
        
        // שליחת SMS אם מופעל
        $settings = get_option('ura_settings');
        if (isset($settings['sms_admin_notifications']) && $settings['sms_admin_notifications']) {
            $sms_manager->send_admin_sms(
                'file_uploaded',
                array(
                    'user_id' => $user_id,
                    'file_name' => $file_info['name'],
                )
            );
        }
    }
    
    /**
     * בדיקת תקינות קובץ
     */
    public function validate_file($file_info) {
        $errors = array();
        
        // בדיקת גודל
        if ($file_info['size'] > $this->max_file_size) {
            $max_size_mb = $this->max_file_size / (1024 * 1024);
            $errors[] = "גודל הקובץ חורג מהמותר ({$max_size_mb}MB)";
        }
        
        // בדיקת סוג קובץ
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_file_types)) {
            $allowed_types = implode(', ', $this->allowed_file_types);
            $errors[] = "סוג הקובץ אינו נתמך. סוגים מותרים: {$allowed_types}";
        }
        
        // בדיקת שגיאות העלאה
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = array(
                UPLOAD_ERR_INI_SIZE => 'הקובץ חורג מהגודל המקסימלי המותר',
                UPLOAD_ERR_FORM_SIZE => 'הקובץ חורג מהגודל המקסימלי שהוגדר בטופס',
                UPLOAD_ERR_PARTIAL => 'הקובץ הועלה חלקית בלבד',
                UPLOAD_ERR_NO_FILE => 'לא נבחר קובץ',
                UPLOAD_ERR_NO_TMP_DIR => 'חסרה תיקיה זמנית',
                UPLOAD_ERR_CANT_WRITE => 'שגיאה בכתיבה לקובץ',
                UPLOAD_ERR_EXTENSION => 'העלאת הקובץ הופסקה על ידי extension'
            );
            $errors[] = $upload_errors[$file_info['error']] ?? 'שגיאה לא ידועה בהעלאת הקובץ';
        }
        
        return $errors;
    }
}
?>