/**
 * Dashboard JavaScript
 * File: assets/js/dashboard.js
 */

$(document).ready(function() {
    
    // ===== INITIALIZE DASHBOARD =====
    initializeDashboard();
    
    function initializeDashboard() {
        initializeCharts();
        initializeCounters();
        initializeRealTimeUpdates();
        setupEventHandlers();
        loadQuickStats();
        startAutoRefresh();
    }
    
    // ===== CHART INITIALIZATION =====
    function initializeCharts() {
        if (typeof Chart === 'undefined' || !window.dashboardData) {
            return;
        }
        
        const chartData = window.dashboardData.attendanceChart;
        const userRole = window.dashboardData.userRole;
        
        if (userRole === 'admin' && chartData) {
            createAttendanceChart(chartData);
            createAttendanceDonut();
        }
        
        if (userRole === 'siswa') {
            createStudentProgressChart();
        }
    }
    
    function createAttendanceChart(data) {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;
        
        const labels = data.map(item => {
            const date = new Date(item.tanggal);
            return date.toLocaleDateString('id-ID', { 
                weekday: 'short', 
                day: 'numeric', 
                month: 'short' 
            });
        });
        
        const attendanceData = data.map(item => item.hadir);
        const totalData = data.map(item => item.total);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hadir',
                    data: attendanceData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }, {
                    label: 'Total Siswa',
                    data: totalData,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: {
                                family: 'Segoe UI',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#007bff',
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Segoe UI',
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            font: {
                                family: 'Segoe UI',
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    
    function createAttendanceDonut() {
        const ctx = document.getElementById('attendanceDonut');
        if (!ctx) return;
        
        const stats = window.dashboardData.stats;
        if (!stats) return;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    data: [
                        stats.hadir || 0,
                        stats.terlambat || 0,
                        stats.izin || 0,
                        stats.sakit || 0,
                        stats.alpha || 0
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#fd7e14',
                        '#dc3545'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'Segoe UI',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
    
    function createStudentProgressChart() {
        const ctx = document.getElementById('studentProgressChart');
        if (!ctx) return;
        
        // Create a simple progress chart for student
        const stats = window.dashboardData.stats;
        const percentage = stats ? stats.persentase_kehadiran : 0;
        
        createProgressCircle('#studentProgress', percentage);
    }
    
    // ===== ANIMATED COUNTERS =====
    function initializeCounters() {
        $('.stats-number, .summary-number').each(function() {
            animateCounter($(this));
        });
    }
    
    function animateCounter(element) {
        const target = parseInt(element.text().replace(/,/g, ''));
        if (isNaN(target)) return;
        
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.text(numberFormat(Math.floor(current)));
        }, 16);
    }
    
    // ===== REAL-TIME UPDATES =====
    function initializeRealTimeUpdates() {
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);
        
        // Update notifications
        setInterval(updateNotifications, 300000); // 5 minutes
    }
    
    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const dateString = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        $('#current-time').text(timeString);
        $('#current-date').text(dateString);
    }
    
    function updateNotifications() {
        $.ajax({
            url: App.baseUrl + 'api/notifications.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateNotificationBadge(response.unread_count);
                }
            }
        });
    }
    
    function updateNotificationBadge(count) {
        const badge = $('#notif-count');
        if (count > 0) {
            badge.text(count).removeClass('d-none');
        } else {
            badge.addClass('d-none');
        }
    }
    
    // ===== EVENT HANDLERS =====
    function setupEventHandlers() {
        // Export report button
        $('#exportReport').on('click', function() {
            showExportModal();
        });
        
        // Refresh button
        $('.btn-refresh').on('click', function() {
            refreshDashboard();
        });
        
        // Quick action buttons
        $('.quick-action').on('click', function() {
            const action = $(this).data('action');
            handleQuickAction(action);
        });
        
        // Card hover effects
        $('.card').hover(
            function() {
                $(this).addClass('shadow-lg');
            },
            function() {
                $(this).removeClass('shadow-lg');
            }
        );
    }
    
    function showExportModal() {
        const modalHtml = `
            <div class="modal fade" id="exportModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Export Laporan Dashboard</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="exportForm">
                                <div class="mb-3">
                                    <label class="form-label">Format Export</label>
                                    <select class="form-select" name="format" required>
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Periode</label>
                                    <select class="form-select" name="period" required>
                                        <option value="today">Hari Ini</option>
                                        <option value="week">Minggu Ini</option>
                                        <option value="month">Bulan Ini</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                <div id="customPeriod" class="d-none">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control" name="start_date">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Selesai</label>
                                            <input type="date" class="form-control" name="end_date">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-primary" id="exportBtn">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('exportModal'));
        modal.show();
        
        // Handle custom period toggle
        $('select[name="period"]').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#customPeriod').removeClass('d-none');
            } else {
                $('#customPeriod').addClass('d-none');
            }
        });
        
        // Handle export
        $('#exportBtn').on('click', function() {
            const formData = $('#exportForm').serialize();
            window.open(App.baseUrl + 'api/export_dashboard.php?' + formData, '_blank');
            modal.hide();
        });
        
        // Clean up modal on hide
        $('#exportModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
    
    function refreshDashboard() {
        App.showLoading();
        location.reload();
    }
    
    function handleQuickAction(action) {
        switch (action) {
            case 'scan':
                window.location.href = App.baseUrl + 'presensi/scan.php';
                break;
            case 'add-student':
                window.location.href = App.baseUrl + 'master/siswa/tambah.php';
                break;
            case 'view-reports':
                window.location.href = App.baseUrl + 'laporan/';
                break;
            case 'backup':
                window.location.href = App.baseUrl + 'pengaturan/backup.php';
                break;
            default:
                console.log('Unknown action:', action);
        }
    }
    
    // ===== LOAD QUICK STATS =====
    function loadQuickStats() {
        if (window.dashboardData.userRole !== 'admin') return;
        
        $.ajax({
            url: App.baseUrl + 'api/dashboard_stats.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateQuickStats(response.data);
                }
            },
            error: function() {
                console.log('Failed to load quick stats');
            }
        });
    }
    
    function updateQuickStats(data) {
        $('#hadir-count').text(data.hadir || 0);
        $('#terlambat-count').text(data.terlambat || 0);
        $('#izin-count').text(data.izin || 0);
        $('#alpha-count').text(data.alpha || 0);
        
        // Update sidebar badges
        $('.sidebar .badge').each(function() {
            const type = $(this).closest('.nav-link').data('stat-type');
            if (type && data[type]) {
                $(this).text(data[type]);
            }
        });
    }
    
    // ===== AUTO REFRESH =====
    function startAutoRefresh() {
        // Refresh dashboard data every 5 minutes
        setInterval(() => {
            loadQuickStats();
            updateNotifications();
        }, 300000);
    }
    
    // ===== UTILITY FUNCTIONS =====
    function createProgressCircle(selector, percentage) {
        const element = $(selector);
        if (!element.length) return;
        
        const radius = 45;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - (percentage / 100) * circumference;
        
        const svg = `
            <svg width="120" height="120" class="progress-circle">
                <circle cx="60" cy="60" r="${radius}" class="progress-circle-bg"></circle>
                <circle cx="60" cy="60" r="${radius}" class="progress-circle-fg"
                        style="stroke-dasharray: ${circumference}; stroke-dashoffset: ${offset}"></circle>
            </svg>
            <div class="progress-text">${percentage}%</div>
        `;
        
        element.html(svg);
    }
    
    function numberFormat(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    function showToast(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        const toastContainer = $('.toast-container').length ? 
            $('.toast-container') : $('<div class="toast-container position-fixed top-0 end-0 p-3"></div>').appendTo('body');
        
        const toastElement = $(toastHtml).appendTo(toastContainer);
        const toast = new bootstrap.Toast(toastElement[0]);
        toast.show();
        
        toastElement.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
    
    // ===== RESPONSIVE CHART RESIZE =====
    $(window).on('resize', function() {
        Chart.helpers.each(Chart.instances, function(instance) {
            instance.resize();
        });
    });
    
    // ===== KEYBOARD SHORTCUTS =====
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R for refresh
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            e.preventDefault();
            refreshDashboard();
        }
        
        // Ctrl/Cmd + E for export
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 69) {
            e.preventDefault();
            showExportModal();
        }
    });
    
    // ===== LAZY LOADING FOR CHARTS =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const chartObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const chartId = entry.target.id;
                if (chartId && !entry.target.classList.contains('chart-loaded')) {
                    // Load chart based on ID
                    entry.target.classList.add('chart-loaded');
                    chartObserver.unobserve(entry.target);
                }
            }
        });
    }, observerOptions);
    
    // Observe all chart elements
    document.querySelectorAll('canvas[id*="Chart"]').forEach(chart => {
        chartObserver.observe(chart);
    });
    
});