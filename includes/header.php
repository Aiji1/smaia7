<?php
/**
 * Header Template
 * File: includes/header.php
 */

// Include konfigurasi
require_once '../config/config.php';
require_once '../config/session.php';

// Check authentication jika bukan halaman login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'index.php') {
    SessionManager::checkAuth();
}

// Get current user info
$user_info = SessionManager::getSessionInfo();
$page_title = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Sistem Presensi Siswa - Aplikasi untuk mengelola presensi siswa secara digital">
    <meta name="author" content="<?php echo APP_AUTHOR; ?>">
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    
    <title><?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo assets_url('img/favicon.ico'); ?>">
    <link rel="apple-touch-icon" href="<?php echo assets_url('img/logo.png'); ?>">
    
    <!-- CSS -->
    <link href="<?php echo assets_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo assets_url('css/style.css'); ?>" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Additional CSS based on page -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link href="<?php echo assets_url('css/' . $css); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom CSS for specific pages -->
    <?php if (isset($custom_css)): ?>
        <style><?php echo $custom_css; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    <?php if ($current_page !== 'login.php' && SessionManager::isLoggedIn()): ?>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <?php include 'sidebar.php'; ?>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                
                <!-- Flash Messages -->
                <?php if (SessionManager::hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo SessionManager::getFlash('success'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (SessionManager::hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo SessionManager::getFlash('error'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (SessionManager::hasFlash('warning')): ?>
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo SessionManager::getFlash('warning'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (SessionManager::hasFlash('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo SessionManager::getFlash('info'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
    <?php else: ?>
        <!-- For login page -->
        <div class="login-wrapper">
    <?php endif; ?>
    
    <!-- Page specific content starts here -->