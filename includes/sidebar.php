<?php
/**
 * Sidebar Navigation Template
 * File: includes/sidebar.php
 */

$user_role = SessionManager::getUserRole();
$current_page = $_SERVER['REQUEST_URI'];

function isActive($path) {
    global $current_page;
    return strpos($current_page, $path) !== false ? 'active' : '';
}
?>

<div class="position-sticky pt-3">
    <!-- User Info Panel -->
    <div class="user-panel d-flex align-items-center p-3 mb-3 bg-light rounded">
        <div class="image">
            <img src="<?php echo assets_url('img/avatar/default.png'); ?>" class="img-circle elevation-2" alt="User Image" width="40" height="40">
        </div>
        <div class="info ms-3">
            <div class="fw-bold"><?php echo SessionManager::getFullName(); ?></div>
            <small class="text-muted"><?php echo ucfirst($user_role); ?></small>
        </div>
    </div>

    <!-- Navigation Menu -->
    <ul class="nav nav-pills flex-column mb-auto">
        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?php echo base_url('dashboard/'); ?>" class="nav-link <?php echo isActive('/dashboard/'); ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>

        <!-- Presensi Menu -->
        <?php if ($user_role === 'admin' || $user_role === 'siswa'): ?>
        <li class="nav-item">
            <a href="<?php echo base_url('presensi/scan.php'); ?>" class="nav-link <?php echo isActive('/presensi/'); ?>">
                <i class="fas fa-qrcode me-2"></i>
                Scan Presensi
            </a>
        </li>
        <?php endif; ?>

        <!-- Data Izin -->
        <li class="nav-item">
            <a href="<?php echo base_url('izin/'); ?>" class="nav-link <?php echo isActive('/izin/'); ?>">
                <i class="fas fa-file-medical me-2"></i>
                Data Izin
                <?php if ($user_role === 'admin'): ?>
                <span class="badge bg-warning ms-2">2</span>
                <?php endif; ?>
            </a>
        </li>

        <!-- Master Data (Admin Only) -->
        <?php if ($user_role === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo (isActive('/master/')) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#masterDataCollapse" role="button">
                <i class="fas fa-database me-2"></i>
                Master Data
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse <?php echo (isActive('/master/')) ? 'show' : ''; ?>" id="masterDataCollapse">
                <ul class="nav nav-pills flex-column ms-3">
                    <li class="nav-item">
                        <a href="<?php echo base_url('master/siswa/'); ?>" class="nav-link <?php echo isActive('/master/siswa/'); ?>">
                            <i class="fas fa-users me-2"></i>
                            Data Siswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('master/kelas/'); ?>" class="nav-link <?php echo isActive('/master/kelas/'); ?>">
                            <i class="fas fa-chalkboard me-2"></i>
                            Data Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('master/waktu/'); ?>" class="nav-link <?php echo isActive('/master/waktu/'); ?>">
                            <i class="fas fa-clock me-2"></i>
                            Waktu Presensi
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <!-- Laporan -->
        <?php if ($user_role === 'admin' || $user_role === 'wali'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo (isActive('/laporan/')) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#laporanCollapse" role="button">
                <i class="fas fa-chart-bar me-2"></i>
                Laporan Presensi
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse <?php echo (isActive('/laporan/')) ? 'show' : ''; ?>" id="laporanCollapse">
                <ul class="nav nav-pills flex-column ms-3">
                    <li class="nav-item">
                        <a href="<?php echo base_url('laporan/per_murid/'); ?>" class="nav-link <?php echo isActive('/laporan/per_murid/'); ?>">
                            <i class="fas fa-user-graduate me-2"></i>
                            Laporan Per Murid
                        </a>
                    </li>
                    <?php if ($user_role === 'admin'): ?>
                    <li class="nav-item">
                        <a href="<?php echo base_url('laporan/per_kelas/'); ?>" class="nav-link <?php echo isActive('/laporan/per_kelas/'); ?>">
                            <i class="fas fa-users me-2"></i>
                            Laporan Per Kelas
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <hr class="my-3">

        <!-- Admin Only Menus -->
        <?php if ($user_role === 'admin'): ?>
        <!-- Manajemen User -->
        <li class="nav-item">
            <a href="<?php echo base_url('user/'); ?>" class="nav-link <?php echo isActive('/user/'); ?>">
                <i class="fas fa-users-cog me-2"></i>
                Manajemen User
            </a>
        </li>

        <!-- Pengaturan Sistem -->
        <li class="nav-item">
            <a href="<?php echo base_url('pengaturan/sistem.php'); ?>" class="nav-link <?php echo isActive('/pengaturan/'); ?>">
                <i class="fas fa-cog me-2"></i>
                Pengaturan Sistem
            </a>
        </li>
        <?php endif; ?>

        <!-- Profile -->
        <li class="nav-item">
            <a href="<?php echo base_url('profile/'); ?>" class="nav-link <?php echo isActive('/profile/'); ?>">
                <i class="fas fa-user me-2"></i>
                Profil Saya
            </a>
        </li>
    </ul>

    <hr>

    <!-- Quick Stats (untuk admin) -->
    <?php if ($user_role === 'admin'): ?>
    <div class="quick-stats">
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Statistik Hari Ini</span>
        </h6>
        <div class="px-3">
            <div class="d-flex justify-content-between mb-2">
                <small>Hadir:</small>
                <span class="badge bg-success" id="hadir-count">--</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <small>Terlambat:</small>
                <span class="badge bg-warning" id="terlambat-count">--</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <small>Izin:</small>
                <span class="badge bg-info" id="izin-count">--</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <small>Alpha:</small>
                <span class="badge bg-danger" id="alpha-count">--</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Clock Widget -->
    <div class="clock-widget p-3 mt-3 bg-primary text-white rounded">
        <div class="text-center">
            <div id="current-time" class="h5 mb-0"></div>
            <div id="current-date" class="small"></div>
        </div>
    </div>
</div>

<script>
// Update clock
function updateClock() {
    const now = new Date();
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false
    };
    const dateOptions = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    
    document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', timeOptions);
    document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', dateOptions);
}

// Update clock every second
setInterval(updateClock, 1000);
updateClock(); // Initial call

<?php if ($user_role === 'admin'): ?>
// Load quick stats
function loadQuickStats() {
    $.ajax({
        url: '<?php echo base_url("api/dashboard_stats.php"); ?>',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#hadir-count').text(data.hadir || 0);
            $('#terlambat-count').text(data.terlambat || 0);
            $('#izin-count').text(data.izin || 0);
            $('#alpha-count').text(data.alpha || 0);
        },
        error: function() {
            console.log('Error loading quick stats');
        }
    });
}

// Load stats on page load
$(document).ready(function() {
    loadQuickStats();
    // Refresh stats every 5 minutes
    setInterval(loadQuickStats, 300000);
});
<?php endif; ?>
</script>