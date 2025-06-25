<?php
/**
 * Dashboard Page
 * File: dashboard/index.php
 */

$page_title = 'Dashboard';
$additional_css = ['dashboard.css'];
$additional_js = ['chart.js', 'dashboard.js'];

require_once '../includes/header.php';

// Check authentication
SessionManager::checkAuth();

$user_role = SessionManager::getUserRole();
$user_id = SessionManager::getUserId();

// Get database connection
$db = getDB();

// Initialize variables
$stats = [];
$recent_activities = [];
$attendance_chart_data = [];
$class_attendance = [];

try {
    if ($user_role === 'admin') {
        // Admin Dashboard Statistics
        
        // Today's statistics
        $today = date('Y-m-d');
        
        // Total students
        $db->query("SELECT COUNT(*) as total FROM siswa WHERE status = 'aktif'");
        $stats['total_siswa'] = $db->single()['total'];
        
        // Total classes
        $db->query("SELECT COUNT(*) as total FROM kelas WHERE status = 'aktif'");
        $stats['total_kelas'] = $db->single()['total'];
        
        // Today's attendance summary
        $db->query("SELECT 
                    COUNT(*) as total_presensi,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
                    FROM presensi WHERE DATE(tanggal) = :today");
        $db->bind(':today', $today);
        $today_stats = $db->single();
        
        $stats = array_merge($stats, $today_stats);
        
        // Calculate attendance percentage
        $stats['persentase_kehadiran'] = $stats['total_presensi'] > 0 ? 
            round((($stats['hadir'] + $stats['terlambat']) / $stats['total_presensi']) * 100, 1) : 0;
        
        // Weekly attendance chart data
        $db->query("SELECT 
                    DATE(tanggal) as tanggal,
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir
                    FROM presensi 
                    WHERE DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(tanggal)
                    ORDER BY tanggal ASC");
        $attendance_chart_data = $db->resultset();
        
        // Class attendance today
        $db->query("SELECT 
                    k.nama_kelas,
                    COUNT(s.id) as total_siswa,
                    COUNT(p.id) as total_presensi,
                    SUM(CASE WHEN p.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir
                    FROM kelas k
                    LEFT JOIN siswa s ON k.id = s.kelas_id AND s.status = 'aktif'
                    LEFT JOIN presensi p ON s.id = p.siswa_id AND DATE(p.tanggal) = :today
                    WHERE k.status = 'aktif'
                    GROUP BY k.id, k.nama_kelas
                    ORDER BY k.nama_kelas");
        $db->bind(':today', $today);
        $class_attendance = $db->resultset();
        
        // Recent activities
        $db->query("SELECT 
                    al.action,
                    al.description,
                    al.created_at,
                    u.full_name as user_name
                    FROM activity_logs al
                    JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT 10");
        $recent_activities = $db->resultset();
        
    } elseif ($user_role === 'siswa') {
        // Student Dashboard
        $siswa_id = SessionManager::get('siswa_id');
        
        // Get student info
        $db->query("SELECT s.*, k.nama_kelas FROM siswa s 
                    JOIN kelas k ON s.kelas_id = k.id 
                    WHERE s.id = :siswa_id");
        $db->bind(':siswa_id', $siswa_id);
        $student_info = $db->single();
        
        // Student attendance statistics (current month)
        $current_month = date('Y-m');
        $db->query("SELECT 
                    COUNT(*) as total_hari,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
                    FROM presensi 
                    WHERE siswa_id = :siswa_id 
                    AND DATE_FORMAT(tanggal, '%Y-%m') = :current_month");
        $db->bind(':siswa_id', $siswa_id);
        $db->bind(':current_month', $current_month);
        $stats = $db->single();
        
        $stats['persentase_kehadiran'] = $stats['total_hari'] > 0 ? 
            round((($stats['hadir'] + $stats['terlambat']) / $stats['total_hari']) * 100, 1) : 0;
        
        // Recent attendance
        $db->query("SELECT * FROM presensi 
                    WHERE siswa_id = :siswa_id 
                    ORDER BY tanggal DESC 
                    LIMIT 10");
        $db->bind(':siswa_id', $siswa_id);
        $recent_attendance = $db->resultset();
        
    } elseif ($user_role === 'wali') {
        // Parent Dashboard
        $wali_id = SessionManager::get('wali_id');
        
        // Get children
        $db->query("SELECT s.*, k.nama_kelas FROM siswa s 
                    JOIN kelas k ON s.kelas_id = k.id 
                    WHERE s.wali_id = :wali_id AND s.status = 'aktif'");
        $db->bind(':wali_id', $wali_id);
        $children = $db->resultset();
        
        $stats['total_anak'] = count($children);
    }
    
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    SessionManager::setFlash('error', 'Terjadi kesalahan saat memuat data dashboard.');
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary" id="exportReport">
                <i class="fas fa-download me-1"></i>Export Laporan
            </button>
        </div>
    </div>
</div>

<?php if ($user_role === 'admin'): ?>
<!-- Admin Dashboard -->
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Siswa</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_siswa']); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Hadir Hari Ini</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['hadir'] + $stats['terlambat']); ?></h2>
                        <small><?php echo $stats['persentase_kehadiran']; ?>%</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Terlambat</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['terlambat']); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Tidak Hadir</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['alpha'] + $stats['izin'] + $stats['sakit']); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Attendance Chart -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Grafik Kehadiran (7 Hari Terakhir)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo base_url('presensi/scan.php'); ?>" class="btn btn-primary">
                        <i class="fas fa-qrcode me-2"></i>Scan Presensi
                    </a>
                    <a href="<?php echo base_url('master/siswa/tambah.php'); ?>" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i>Tambah Siswa
                    </a>
                    <a href="<?php echo base_url('laporan/per_kelas/'); ?>" class="btn btn-info">
                        <i class="fas fa-file-alt me-2"></i>Lihat Laporan
                    </a>
                    <a href="<?php echo base_url('pengaturan/backup.php'); ?>" class="btn btn-secondary">
                        <i class="fas fa-database me-2"></i>Backup Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Class Attendance Today -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Kehadiran Per Kelas Hari Ini
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Hadir</th>
                                <th>Total</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($class_attendance as $class): ?>
                            <?php 
                                $percentage = $class['total_siswa'] > 0 ? 
                                    round(($class['hadir'] / $class['total_siswa']) * 100, 1) : 0;
                                $badge_class = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['nama_kelas']); ?></td>
                                <td><?php echo $class['hadir']; ?></td>
                                <td><?php echo $class['total_siswa']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Aktivitas Terbaru
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item d-flex mb-3">
                        <div class="activity-icon me-3">
                            <i class="fas fa-circle text-primary"></i>
                        </div>
                        <div class="activity-content">
                            <div class="fw-medium"><?php echo htmlspecialchars($activity['user_name']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></div>
                            <div class="text-xs text-muted"><?php echo formatDateTime($activity['created_at']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($user_role === 'siswa'): ?>
<!-- Student Dashboard -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4>Selamat datang, <?php echo htmlspecialchars($student_info['nama']); ?>!</h4>
                        <p class="mb-2">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo htmlspecialchars($student_info['nama_kelas']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-id-badge me-2"></i>
                            NIS: <?php echo htmlspecialchars($student_info['nis']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="h2 mb-0"><?php echo $stats['persentase_kehadiran']; ?>%</div>
                        <div>Tingkat Kehadiran Bulan Ini</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Attendance Statistics -->
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-success h2 mb-2"><?php echo $stats['hadir']; ?></div>
                <div class="fw-medium">Hadir</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-warning h2 mb-2"><?php echo $stats['terlambat']; ?></div>
                <div class="fw-medium">Terlambat</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-info h2 mb-2"><?php echo $stats['izin']; ?></div>
                <div class="fw-medium">Izin</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-danger h2 mb-2"><?php echo $stats['alpha']; ?></div>
                <div class="fw-medium">Alpha</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions for Student -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo base_url('presensi/scan.php'); ?>" class="btn btn-primary">
                        <i class="fas fa-qrcode me-2"></i>Scan Presensi
                    </a>
                    <a href="<?php echo base_url('izin/tambah.php'); ?>" class="btn btn-warning">
                        <i class="fas fa-file-medical me-2"></i>Ajukan Izin
                    </a>
                    <a href="<?php echo base_url('laporan/per_murid/'); ?>" class="btn btn-info">
                        <i class="fas fa-chart-bar me-2"></i>Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Attendance -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Riwayat Presensi Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $attendance): ?>
                            <tr>
                                <td><?php echo formatDate($attendance['tanggal']); ?></td>
                                <td><?php echo formatTime($attendance['jam_masuk']); ?></td>
                                <td><?php echo formatTime($attendance['jam_keluar']); ?></td>
                                <td>
                                    <span class="status-<?php echo $attendance['status']; ?>">
                                        <?php echo ucfirst($attendance['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($user_role === 'wali'): ?>
<!-- Parent Dashboard -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>Data Anak
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($children as $child): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($child['nama']); ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($child['nama_kelas']); ?> | 
                                        NIS: <?php echo htmlspecialchars($child['nis']); ?>
                                    </small>
                                </p>
                                <a href="<?php echo base_url('laporan/per_murid/?siswa=' . $child['id']); ?>" class="btn btn-sm btn-primary">
                                    Lihat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Hidden data for JavaScript -->
<script type="text/javascript">
    window.dashboardData = {
        attendanceChart: <?php echo json_encode($attendance_chart_data); ?>,
        userRole: '<?php echo $user_role; ?>',
        stats: <?php echo json_encode($stats); ?>
    };
</script>

<?php require_once '../includes/footer.php'; ?>