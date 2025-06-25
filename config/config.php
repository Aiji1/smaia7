<?php
/**
 * Konfigurasi Utama Aplikasi
 * File: config/config.php
 */

// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Aplikasi
define('APP_NAME', 'Sistem Presensi Siswa');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Your Name');

// URL dan Path
define('BASE_URL', 'http://localhost/presensi-app/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/presensi-app/assets/uploads/');
define('BACKUP_PATH', $_SERVER['DOCUMENT_ROOT'] . '/presensi-app/assets/uploads/backup/');

// Konfigurasi Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Konfigurasi Presensi
define('PRESENSI_MASUK_START', '06:00:00');
define('PRESENSI_MASUK_END', '08:00:00');
define('PRESENSI_PULANG_START', '14:00:00');
define('PRESENSI_PULANG_END', '17:00:00');

// Status Presensi
define('STATUS_HADIR', 'hadir');
define('STATUS_IZIN', 'izin');
define('STATUS_SAKIT', 'sakit');
define('STATUS_ALPHA', 'alpha');
define('STATUS_TERLAMBAT', 'terlambat');

// Role User
define('ROLE_ADMIN', 'admin');
define('ROLE_SISWA', 'siswa');
define('ROLE_WALI', 'wali');

// Konfigurasi Pagination
define('RECORDS_PER_PAGE', 10);

// Konfigurasi Email (jika diperlukan)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Error Reporting
if (APP_VERSION == '1.0.0') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoload classes
spl_autoload_register(function ($class) {
    $file = $_SERVER['DOCUMENT_ROOT'] . '/presensi-app/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include database connection
require_once 'database.php';

// Include functions
require_once '../includes/functions.php';

// Helper Functions
function base_url($path = '') {
    return BASE_URL . $path;
}

function assets_url($path = '') {
    return ASSETS_URL . $path;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_user_role() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function format_time($time, $format = 'H:i') {
    return date($format, strtotime($time));
}

function get_indonesian_day($date) {
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $day = date('l', strtotime($date));
    return $days[$day];
}

function get_indonesian_month($date) {
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    $month = date('m', strtotime($date));
    return $months[$month];
}

// Generate CSRF Token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>