<?php
/**
 * Add Student Form
 * File: master/siswa/tambah.php
 */

$page_title = 'Tambah Data Siswa';
$additional_css = ['form.css'];
$additional_js = ['form-validation.js', 'siswa.js'];

require_once '../../includes/header.php';

// Check authentication and role
SessionManager::checkAuth();
if (!SessionManager::checkRole(['admin'])) {
    SessionManager::setFlash('error', 'Akses ditolak. Anda tidak memiliki permission untuk halaman ini.');
    redirect(base_url('dashboard/'));
}

// Get database connection
$db = getDB();

// Get list of classes for dropdown
$db->query("SELECT * FROM kelas WHERE status = 'aktif' ORDER BY nama_kelas ASC");
$kelas_list = $db->resultset();

// Get list of wali for dropdown
$db->query("SELECT u.id, u.full_name, u.email FROM users u WHERE u.role = 'wali' ORDER BY u.full_name ASC");
$wali_list = $db->resultset();

$errors = [];
$form_data = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Token validation
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        // Validate and sanitize input
        $form_data = [
            'nis' => validateInput($_POST['nis'] ?? '', 'alphanumeric'),
            'nama' => validateInput($_POST['nama'] ?? '', 'string'),
            'email' => validateInput($_POST['email'] ?? '', 'email'),
            'jenis_kelamin' => validateInput($_POST['jenis_kelamin'] ?? '', 'string'),
            'tanggal_lahir' => validateInput($_POST['tanggal_lahir'] ?? '', 'string'),
            'alamat' => validateInput($_POST['alamat'] ?? '', 'string'),
            'no_telepon' => validateInput($_POST['no_telepon'] ?? '', 'phone'),
            'kelas_id' => validateInput($_POST['kelas_id'] ?? '', 'int'),
            'wali_id' => validateInput($_POST['wali_id'] ?? '', 'int'),
            'status' => 'aktif'
        ];
        
        // Validation
        if (empty($form_data['nis'])) {
            $errors['nis'] = 'NIS wajib diisi';
        }
        
        if (empty($form_data['nama'])) {
            $errors['nama'] = 'Nama wajib diisi';
        }
        
        if (empty($form_data['email']) || !$form_data['email']) {
            $errors['email'] = 'Email tidak valid';
        }
        
        if (!in_array($form_data['jenis_kelamin'], ['L', 'P'])) {
            $errors['jenis_kelamin'] = 'Jenis kelamin tidak valid';
        }
        
        if (empty($form_data['tanggal_lahir'])) {
            $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi';
        }
        
        if (empty($form_data['alamat'])) {
            $errors['alamat'] = 'Alamat wajib diisi';
        }
        
        if (empty($form_data['no_telepon'])) {
            $errors['no_telepon'] = 'Nomor telepon wajib diisi';
        }
        
        if (empty($form_data['kelas_id'])) {
            $errors['kelas_id'] = 'Kelas wajib dipilih';
        }
        
        // Check if NIS already exists
        if (empty($errors['nis'])) {
            $db->query("SELECT id FROM siswa WHERE nis = :nis");
            $db->bind(':nis', $form_data['nis']);
            if ($db->single()) {
                $errors['nis'] = 'NIS sudah digunakan';
            }
        }
        
        // Check if email already exists
        if (empty($errors['email'])) {
            $db->query("SELECT id FROM siswa WHERE email = :email");
            $db->bind(':email', $form_data['email']);
            if ($db->single()) {
                $errors['email'] = 'Email sudah digunakan';
            }
        }
        
        // Handle photo upload
        $photo_filename = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['foto'], 'avatar/', ['jpg', 'jpeg', 'png']);
            if ($upload_result['success']) {
                $photo_filename = $upload_result['filename'];
            } else {
                $errors['foto'] = $upload_result['message'];
            }
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            $db->beginTransaction();
            
            try {
                // Insert student data
                $db->query("INSERT INTO siswa (nis, nama, email, jenis_kelamin, tanggal_lahir, alamat, no_telepon, kelas_id, wali_id, foto, status, created_at) 
                           VALUES (:nis, :nama, :email, :jenis_kelamin, :tanggal_lahir, :alamat, :no_telepon, :kelas_id, :wali_id, :foto, :status, NOW())");
                
                $db->bind(':nis', $form_data['nis']);
                $db->bind(':nama', $form_data['nama']);
                $db->bind(':email', $form_data['email']);
                $db->bind(':jenis_kelamin', $form_data['jenis_kelamin']);
                $db->bind(':tanggal_lahir', $form_data['tanggal_lahir']);
                $db->bind(':alamat', $form_data['alamat']);
                $db->bind(':no_telepon', $form_data['no_telepon']);
                $db->bind(':kelas_id', $form_data['kelas_id']);
                $db->bind(':wali_id', $form_data['wali_id'] ?: null);
                $db->bind(':foto', $photo_filename);
                $db->bind(':status', $form_data['status']);
                
                $db->execute();
                $siswa_id = $db->lastInsertId();
                
                // Create user account for student
                $username = strtolower($form_data['nis']);
                $password = generateRandomPassword(8);
                $hashed_password = hashPassword($password);
                
                $db->query("INSERT INTO users (username, password, email, full_name, role, siswa_id, status, created_at) 
                           VALUES (:username, :password, :email, :full_name, 'siswa', :siswa_id, 'aktif', NOW())");
                
                $db->bind(':username', $username);
                $db->bind(':password', $hashed_password);
                $db->bind(':email', $form_data['email']);
                $db->bind(':full_name', $form_data['nama']);
                $db->bind(':siswa_id', $siswa_id);
                
                $db->execute();
                
                // Generate QR Code for student
                $qr_data = json_encode([
                    'siswa_id' => $siswa_id,
                    'nis' => $form_data['nis'],
                    'nama' => $form_data['nama']
                ]);
                
                $qr_filename = 'qr_' . $form_data['nis'] . '.png';
                generateQRCode($qr_data, $qr_filename);
                
                // Update student with QR code filename
                $db->query("UPDATE siswa SET qr_code = :qr_code WHERE id = :id");
                $db->bind(':qr_code', $qr_filename);
                $db->bind(':id', $siswa_id);
                $db->execute();
                
                $db->endTransaction();
                
                // Log activity
                logActivity(SessionManager::getUserId(), 'CREATE_STUDENT', 
                           'Menambah data siswa: ' . $form_data['nama'] . ' (NIS: ' . $form_data['nis'] . ')');
                
                // Send notification or email with login credentials
                if (function_exists('sendEmail')) {
                    $email_subject = 'Akun Siswa - ' . APP_NAME;
                    $email_message = "
                        <h3>Selamat datang di " . APP_NAME . "</h3>
                        <p>Halo {$form_data['nama']},</p>
                        <p>Akun Anda telah dibuat dengan detail berikut:</p>
                        <ul>
                            <li><strong>Username:</strong> {$username}</li>
                            <li><strong>Password:</strong> {$password}</li>
                            <li><strong>NIS:</strong> {$form_data['nis']}</li>
                        </ul>
                        <p>Silakan login menggunakan kredensial di atas dan ubah password Anda setelah login pertama.</p>
                        <p>Terima kasih.</p>
                    ";
                    
                    sendEmail($form_data['email'], $email_subject, $email_message);
                }
                
                SessionManager::setFlash('success', 'Data siswa berhasil ditambahkan! Username: ' . $username . ', Password: ' . $password);
                redirect(base_url('master/siswa/'));
                
            } catch (Exception $e) {
                $db->cancelTransaction();
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        error_log("Add Student Error: " . $e->getMessage());
        SessionManager::setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-plus me-2"></i>Tambah Data Siswa
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?php echo base_url('master/siswa/'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>Form Data Siswa
                </h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <!-- NIS -->
                        <div class="col-md-6 mb-3">
                            <label for="nis" class="form-label">NIS <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['nis']) ? 'is-invalid' : ''; ?>" 
                                   id="nis" name="nis" value="<?php echo htmlspecialchars($form_data['nis'] ?? ''); ?>" 
                                   required maxlength="20">
                            <?php if (isset($errors['nis'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['nis']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Nomor Induk Siswa (maksimal 20 karakter)</div>
                        </div>
                        
                        <!-- Nama -->
                        <div class="col-md-6 mb-3">
                            <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" 
                                   id="nama" name="nama" value="<?php echo htmlspecialchars($form_data['nama'] ?? ''); ?>" 
                                   required maxlength="100">
                            <?php if (isset($errors['nama'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['nama']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control email-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Email akan digunakan untuk login dan notifikasi</div>
                        </div>
                        
                        <!-- Jenis Kelamin -->
                        <div class="col-md-6 mb-3">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['jenis_kelamin']) ? 'is-invalid' : ''; ?>" 
                                    id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo ($form_data['jenis_kelamin'] ?? '') === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($form_data['jenis_kelamin'] ?? '') === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                            <?php if (isset($errors['jenis_kelamin'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['jenis_kelamin']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Tanggal Lahir -->
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?php echo isset($errors['tanggal_lahir']) ? 'is-invalid' : ''; ?>" 
                                   id="tanggal_lahir" name="tanggal_lahir" 
                                   value="<?php echo htmlspecialchars($form_data['tanggal_lahir'] ?? ''); ?>" 
                                   max="<?php echo date('Y-m-d'); ?>" required>
                            <?php if (isset($errors['tanggal_lahir'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['tanggal_lahir']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- No Telepon -->
                        <div class="col-md-6 mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control phone-format <?php echo isset($errors['no_telepon']) ? 'is-invalid' : ''; ?>" 
                                   id="no_telepon" name="no_telepon" 
                                   value="<?php echo htmlspecialchars($form_data['no_telepon'] ?? ''); ?>" 
                                   required pattern="[0-9\-\+\s\(\)]+">
                            <?php if (isset($errors['no_telepon'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['no_telepon']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Format: 08xx-xxxx-xxxx</div>
                        </div>
                    </div>
                    
                    <!-- Alamat -->
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control <?php echo isset($errors['alamat']) ? 'is-invalid' : ''; ?>" 
                                  id="alamat" name="alamat" rows="3" required maxlength="500"><?php echo htmlspecialchars($form_data['alamat'] ?? ''); ?></textarea>
                        <?php if (isset($errors['alamat'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['alamat']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Alamat lengkap tempat tinggal (maksimal 500 karakter)</div>
                    </div>
                    
                    <div class="row">
                        <!-- Kelas -->
                        <div class="col-md-6 mb-3">
                            <label for="kelas_id" class="form-label">Kelas <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['kelas_id']) ? 'is-invalid' : ''; ?>" 
                                    id="kelas_id" name="kelas_id" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?php echo $kelas['id']; ?>" 
                                            <?php echo ($form_data['kelas_id'] ?? '') == $kelas['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['kelas_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['kelas_id']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Wali -->
                        <div class="col-md-6 mb-3">
                            <label for="wali_id" class="form-label">Wali Murid</label>
                            <select class="form-select" id="wali_id" name="wali_id">
                                <option value="">Pilih Wali Murid (Opsional)</option>
                                <?php foreach ($wali_list as $wali): ?>
                                    <option value="<?php echo $wali['id']; ?>" 
                                            <?php echo ($form_data['wali_id'] ?? '') == $wali['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($wali['full_name'] . ' (' . $wali['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Kosongkan jika belum ada wali murid yang terdaftar</div>
                        </div>
                    </div>
                    
                    <!-- Foto -->
                    <div class="mb-4">
                        <label for="foto" class="form-label">Foto Siswa</label>
                        <input type="file" class="form-control <?php echo isset($errors['foto']) ? 'is-invalid' : ''; ?>" 
                               id="foto" name="foto" accept="image/jpeg,image/jpg,image/png">
                        <?php if (isset($errors['foto'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['foto']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">File gambar (JPG, JPEG, PNG) maksimal 5MB. Opsional.</div>
                        
                        <!-- Preview area -->
                        <div id="imagePreview" class="mt-3 d-none">
                            <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo base_url('master/siswa/'); ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan Data Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Info Panel -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <small>
                        <strong>Catatan:</strong><br>
                        • Field dengan tanda (*) wajib diisi<br>
                        • NIS harus unik dan tidak boleh sama<br>
                        • Email akan digunakan untuk akun login siswa<br>
                        • Username otomatis dibuat dari NIS<br>
                        • Password acak akan digenerate otomatis<br>
                        • QR Code akan dibuat otomatis untuk presensi
                    </small>
                </div>
                
                <div class="alert alert-warning">
                    <small>
                        <strong>Setelah menyimpan:</strong><br>
                        • Akun login siswa akan dibuat otomatis<br>
                        • Email berisi kredensial login akan dikirim<br>
                        • QR Code untuk presensi akan digenerate<br>
                        • Siswa dapat langsung melakukan presensi
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo base_url('master/siswa/'); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list me-1"></i>Daftar Siswa
                    </a>
                    <a href="<?php echo base_url('master/kelas/tambah.php'); ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-plus me-1"></i>Tambah Kelas
                    </a>
                    <a href="<?php echo base_url('user/'); ?>?role=wali" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-user-friends me-1"></i>Kelola Wali Murid
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').classList.add('d-none');
    }
});

// Auto-generate username preview
document.getElementById('nis').addEventListener('input', function() {
    const nis = this.value.toLowerCase();
    // You can show a preview of what the username will be
    console.log('Username will be:', nis);
});

// Validate email availability
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value;
    if (email) {
        // AJAX check if email exists
        $.ajax({
            url: '<?php echo base_url("api/check_email.php"); ?>',
            method: 'POST',
            data: { email: email },
            success: function(response) {
                if (response.exists) {
                    $('#email').addClass('is-invalid');
                    $('#email').siblings('.invalid-feedback').text('Email sudah digunakan');
                } else {
                    $('#email').removeClass('is-invalid');
                }
            }
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>