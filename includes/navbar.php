<?php
/**
 * Navigation Bar Template
 * File: includes/navbar.php
 */

$user_role = SessionManager::getUserRole();
$full_name = SessionManager::getFullName();
$username = SessionManager::getUsername();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo base_url('dashboard/'); ?>">
            <img src="<?php echo assets_url('img/logo.png'); ?>" alt="Logo" width="30" height="30" class="me-2">
            <span class="fw-bold"><?php echo APP_NAME; ?></span>
        </a>
        
        <!-- Mobile menu button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left side navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('dashboard/'); ?>">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                
                <?php if ($user_role === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="masterDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-database me-1"></i> Master Data
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo base_url('master/siswa/'); ?>">
                            <i class="fas fa-users me-2"></i> Data Siswa
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo base_url('master/kelas/'); ?>">
                            <i class="fas fa-chalkboard me-2"></i> Data Kelas
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo base_url('master/waktu/'); ?>">
                            <i class="fas fa-clock me-2"></i> Waktu Presensi
                        </a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="laporanDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar me-1"></i> Laporan
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo base_url('laporan/per_murid/'); ?>">
                            <i class="fas fa-user-graduate me-2"></i> Per Murid
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo base_url('laporan/per_kelas/'); ?>">
                            <i class="fas fa-users me-2"></i> Per Kelas
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if ($user_role === 'admin' || $user_role === 'siswa'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('presensi/scan.php'); ?>">
                        <i class="fas fa-qrcode me-1"></i> Scan Presensi
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo base_url('izin/'); ?>">
                        <i class="fas fa-file-medical me-1"></i> Data Izin
                    </a>
                </li>
            </ul>
            
            <!-- Right side navigation -->
            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-count">
                            3
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <li class="dropdown-header">
                            <strong>Notifikasi</strong>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">Siswa Terlambat</div>
                                        <div class="text-muted small">5 siswa terlambat hari ini</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-info"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">Pengajuan Izin</div>
                                        <div class="text-muted small">2 pengajuan izin baru</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center" href="#">
                                <small>Lihat Semua Notifikasi</small>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- User Profile -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo assets_url('img/avatar/default.png'); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                        <span class="d-none d-md-inline"><?php echo $full_name; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <strong><?php echo $full_name; ?></strong><br>
                            <small class="text-muted"><?php echo ucfirst($user_role); ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo base_url('profile/'); ?>">
                                <i class="fas fa-user me-2"></i> Profil Saya
                            </a>
                        </li>
                        <?php if ($user_role === 'admin'): ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo base_url('pengaturan/sistem.php'); ?>">
                                <i class="fas fa-cog me-2"></i> Pengaturan Sistem
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo base_url('user/'); ?>">
                                <i class="fas fa-users-cog me-2"></i> Manajemen User
                            </a>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin keluar dari sistem?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="<?php echo base_url('auth/logout.php'); ?>" class="btn btn-danger">Ya, Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    var logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
}
</script>