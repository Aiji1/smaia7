<?php
/**
 * Global Functions
 * File: includes/functions.php
 */

/**
 * Database connection helper
 */
function getDB() {
    return new Database();
}

/**
 * Security Functions
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateInput($data, $type = 'string') {
    $data = trim($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        case 'phone':
            return preg_match('/^[0-9\-\+\s\(\)]+$/', $data);
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9\s]+$/', $data);
        case 'alpha':
            return preg_match('/^[a-zA-Z\s]+$/', $data);
        case 'numeric':
            return is_numeric($data);
        default:
            return sanitize($data);
    }
}

/**
 * Password Functions
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * File Upload Functions
 */
function uploadFile($file, $target_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
    $upload_dir = UPLOAD_PATH . $target_dir;
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    if ($file_error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
    
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file_tmp, $target_file)) {
        return [
            'success' => true, 
            'message' => 'File uploaded successfully',
            'filename' => $new_filename,
            'path' => $target_file
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

function deleteFile($filename, $directory = 'uploads/') {
    $file_path = UPLOAD_PATH . $directory . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

/**
 * Date and Time Functions
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

function formatTime($time, $format = 'H:i') {
    if (empty($time)) {
        return '-';
    }
    return date($format, strtotime($time));
}

function getIndonesianDate($date) {
    $days = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    
    $months = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    $day = date('l', strtotime($date));
    $month = date('F', strtotime($date));
    
    $formatted_date = date('j', strtotime($date)) . ' ' . $months[$month] . ' ' . date('Y', strtotime($date));
    
    return $days[$day] . ', ' . $formatted_date;
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    return $today->diff($birth)->y;
}

/**
 * Presensi Helper Functions
 */
function getPresensiStatus($masuk_time, $keluar_time = null) {
    if (empty($masuk_time)) {
        return STATUS_ALPHA;
    }
    
    $masuk = strtotime($masuk_time);
    $batas_masuk = strtotime(PRESENSI_MASUK_END);
    
    if ($masuk > $batas_masuk) {
        return STATUS_TERLAMBAT;
    }
    
    return STATUS_HADIR;
}

function calculateAttendancePercentage($hadir, $total_hari) {
    if ($total_hari == 0) return 0;
    return round(($hadir / $total_hari) * 100, 2);
}

function getAttendanceColor($percentage) {
    if ($percentage >= 90) return 'success';
    if ($percentage >= 75) return 'warning';
    return 'danger';
}

/**
 * QR Code Functions
 */
function generateQRCode($data, $filename = null) {
    require_once '../vendor/phpqrcode/qrlib.php';
    
    $qr_dir = UPLOAD_PATH . 'qr-codes/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    if (!$filename) {
        $filename = uniqid() . '_qr.png';
    }
    
    $file_path = $qr_dir . $filename;
    
    QRcode::png($data, $file_path, QR_ECLEVEL_L, 8, 2);
    
    return $filename;
}

/**
 * Notification Functions
 */
function sendNotification($user_id, $title, $message, $type = 'info') {
    $db = getDB();
    $db->query("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (:user_id, :title, :message, :type, NOW())");
    $db->bind(':user_id', $user_id);
    $db->bind(':title', $title);
    $db->bind(':message', $message);
    $db->bind(':type', $type);
    return $db->execute();
}

function getUnreadNotifications($user_id) {
    $db = getDB();
    $db->query("SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC");
    $db->bind(':user_id', $user_id);
    return $db->resultset();
}

function markNotificationAsRead($notification_id) {
    $db = getDB();
    $db->query("UPDATE notifications SET is_read = 1 WHERE id = :id");
    $db->bind(':id', $notification_id);
    return $db->execute();
}

/**
 * Pagination Functions
 */
function paginate($total_records, $records_per_page = RECORDS_PER_PAGE, $current_page = 1) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'offset' => $offset,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

function generatePaginationHTML($pagination, $base_url) {
    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prev_page = $pagination['current_page'] - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $prev_page . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start_page = max(1, $pagination['current_page'] - 2);
    $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $next_page = $pagination['current_page'] + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $next_page . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Export Functions
 */
function exportToExcel($data, $filename, $headers = []) {
    require_once '../vendor/phpexcel/PHPExcel.php';
    
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $sheet = $objPHPExcel->getActiveSheet();
    
    // Set headers
    if (!empty($headers)) {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    }
    
    // Set data
    $row = 2;
    foreach ($data as $item) {
        $col = 'A';
        foreach ($item as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
}

/**
 * Logging Functions
 */
function logActivity($user_id, $action, $description = '') {
    $db = getDB();
    $db->query("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) VALUES (:user_id, :action, :description, :ip, :user_agent, NOW())");
    $db->bind(':user_id', $user_id);
    $db->bind(':action', $action);
    $db->bind(':description', $description);
    $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
    $db->bind(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    return $db->execute();
}

/**
 * Backup Functions
 */
function backupDatabase() {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = BACKUP_PATH . $backup_file;
    
    // Create backup directory if not exists
    if (!file_exists(BACKUP_PATH)) {
        mkdir(BACKUP_PATH, 0755, true);
    }
    
    $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_path;
    
    system($command, $return_var);
    
    if ($return_var === 0) {
        return [
            'success' => true,
            'message' => 'Database backup created successfully',
            'filename' => $backup_file,
            'path' => $backup_path
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to create database backup'
        ];
    }
}

function restoreDatabase($backup_file) {
    $backup_path = BACKUP_PATH . $backup_file;
    
    if (!file_exists($backup_path)) {
        return [
            'success' => false,
            'message' => 'Backup file not found'
        ];
    }
    
    $command = "mysql --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " < " . $backup_path;
    
    system($command, $return_var);
    
    if ($return_var === 0) {
        return [
            'success' => true,
            'message' => 'Database restored successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to restore database'
        ];
    }
}

/**
 * Email Functions
 */
function sendEmail($to, $subject, $message, $from_name = APP_NAME) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from_name . " <" . SMTP_USERNAME . ">" . "\r\n";
    $headers .= "Reply-To: " . SMTP_USERNAME . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Report Generation Functions
 */
function generateAttendanceReport($siswa_id, $start_date, $end_date) {
    $db = getDB();
    
    // Get student info
    $db->query("SELECT s.*, k.nama_kelas FROM siswa s 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id = :siswa_id");
    $db->bind(':siswa_id', $siswa_id);
    $siswa = $db->single();
    
    if (!$siswa) {
        return ['success' => false, 'message' => 'Siswa tidak ditemukan'];
    }
    
    // Get attendance data
    $db->query("SELECT * FROM presensi 
                WHERE siswa_id = :siswa_id 
                AND DATE(tanggal) BETWEEN :start_date AND :end_date 
                ORDER BY tanggal ASC");
    $db->bind(':siswa_id', $siswa_id);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    $presensi_data = $db->resultset();
    
    // Calculate statistics
    $total_hari = count($presensi_data);
    $hadir = 0;
    $izin = 0;
    $sakit = 0;
    $alpha = 0;
    $terlambat = 0;
    
    foreach ($presensi_data as $presensi) {
        switch ($presensi['status']) {
            case STATUS_HADIR:
                $hadir++;
                break;
            case STATUS_IZIN:
                $izin++;
                break;
            case STATUS_SAKIT:
                $sakit++;
                break;
            case STATUS_ALPHA:
                $alpha++;
                break;
            case STATUS_TERLAMBAT:
                $terlambat++;
                break;
        }
    }
    
    $persentase_kehadiran = calculateAttendancePercentage($hadir + $terlambat, $total_hari);
    
    return [
        'success' => true,
        'siswa' => $siswa,
        'periode' => [
            'start' => $start_date,
            'end' => $end_date
        ],
        'data_presensi' => $presensi_data,
        'statistik' => [
            'total_hari' => $total_hari,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpha' => $alpha,
            'persentase_kehadiran' => $persentase_kehadiran
        ]
    ];
}

function generateClassReport($kelas_id, $start_date, $end_date) {
    $db = getDB();
    
    // Get class info
    $db->query("SELECT * FROM kelas WHERE id = :kelas_id");
    $db->bind(':kelas_id', $kelas_id);
    $kelas = $db->single();
    
    if (!$kelas) {
        return ['success' => false, 'message' => 'Kelas tidak ditemukan'];
    }
    
    // Get students in class
    $db->query("SELECT s.*, 
                COUNT(p.id) as total_presensi,
                SUM(CASE WHEN p.status = 'hadir' OR p.status = 'terlambat' THEN 1 ELSE 0 END) as total_hadir,
                SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                SUM(CASE WHEN p.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha,
                SUM(CASE WHEN p.status = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat
                FROM siswa s
                LEFT JOIN presensi p ON s.id = p.siswa_id 
                    AND DATE(p.tanggal) BETWEEN :start_date AND :end_date
                WHERE s.kelas_id = :kelas_id AND s.status = 'aktif'
                GROUP BY s.id
                ORDER BY s.nama ASC");
    $db->bind(':kelas_id', $kelas_id);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    $siswa_data = $db->resultset();
    
    // Calculate class statistics
    $total_siswa = count($siswa_data);
    $class_stats = [
        'total_siswa' => $total_siswa,
        'avg_kehadiran' => 0,
        'total_hadir' => 0,
        'total_izin' => 0,
        'total_sakit' => 0,
        'total_alpha' => 0,
        'total_terlambat' => 0
    ];
    
    $total_persentase = 0;
    foreach ($siswa_data as &$siswa) {
        $total_hari = $siswa['total_presensi'] ?: 1;
        $siswa['persentase_kehadiran'] = calculateAttendancePercentage($siswa['total_hadir'], $total_hari);
        $total_persentase += $siswa['persentase_kehadiran'];
        
        $class_stats['total_hadir'] += $siswa['total_hadir'];
        $class_stats['total_izin'] += $siswa['total_izin'];
        $class_stats['total_sakit'] += $siswa['total_sakit'];
        $class_stats['total_alpha'] += $siswa['total_alpha'];
        $class_stats['total_terlambat'] += $siswa['total_terlambat'];
    }
    
    $class_stats['avg_kehadiran'] = $total_siswa > 0 ? round($total_persentase / $total_siswa, 2) : 0;
    
    return [
        'success' => true,
        'kelas' => $kelas,
        'periode' => [
            'start' => $start_date,
            'end' => $end_date
        ],
        'siswa_data' => $siswa_data,
        'statistik_kelas' => $class_stats
    ];
}

/**
 * Utility Functions
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function debugLog($message, $data = null) {
    if (APP_VERSION === '1.0.0') { // Only in development
        $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log_message .= ' - Data: ' . print_r($data, true);
        }
        error_log($log_message . PHP_EOL, 3, 'debug.log');
    }
}

/**
 * System Settings Functions
 */
function getSystemSetting($key, $default = null) {
    $db = getDB();
    $db->query("SELECT setting_value FROM system_settings WHERE setting_key = :key");
    $db->bind(':key', $key);
    $result = $db->single();
    
    return $result ? $result['setting_value'] : $default;
}

function setSystemSetting($key, $value) {
    $db = getDB();
    $db->query("INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (:key, :value, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()");
    $db->bind(':key', $key);
    $db->bind(':value', $value);
    return $db->execute();
}

function getAllSystemSettings() {
    $db = getDB();
    $db->query("SELECT * FROM system_settings ORDER BY setting_key");
    $results = $db->resultset();
    
    $settings = [];
    foreach ($results as $result) {
        $settings[$result['setting_key']] = $result['setting_value'];
    }
    
    return $settings;
}
?>