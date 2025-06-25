/**
 * Main JavaScript File
 * File: assets/js/app.js
 */

$(document).ready(function() {
    
    // ===== GLOBAL VARIABLES =====
    window.App = {
        baseUrl: window.BASE_URL || '',
        assetsUrl: window.ASSETS_URL || '',
        csrfToken: window.CSRF_TOKEN || '',
        userId: window.USER_ID || null,
        userRole: window.USER_ROLE || '',
        userName: window.USER_NAME || ''
    };
    
    // ===== INITIALIZATION =====
    initializeApp();
    
    function initializeApp() {
        setupAjaxDefaults();
        setupGlobalEventHandlers();
        setupFormValidation();
        setupDataTables();
        setupTooltips();
        setupModals();
        setupNotifications();
        setupSidebar();
        checkSession();
    }
    
    // ===== AJAX SETUP =====
    function setupAjaxDefaults() {
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                    xhr.setRequestHeader("X-CSRFToken", App.csrfToken);
                }
                showLoading();
            },
            complete: function() {
                hideLoading();
            },
            error: function(xhr, status, error) {
                hideLoading();
                if (xhr.status === 401) {
                    showAlert('Session expired. Please login again.', 'error');
                    setTimeout(() => {
                        window.location.href = App.baseUrl + 'auth/login.php';
                    }, 2000);
                } else if (xhr.status === 403) {
                    showAlert('Access denied.', 'error');
                } else if (xhr.status === 500) {
                    showAlert('Server error. Please try again later.', 'error');
                } else {
                    showAlert('An error occurred: ' + error, 'error');
                }
            }
        });
    }
    
    // ===== GLOBAL EVENT HANDLERS =====
    function setupGlobalEventHandlers() {
        
        // Confirm delete buttons
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            const url = $(this).attr('href') || $(this).data('url');
            const message = $(this).data('message') || 'Are you sure you want to delete this item?';
            
            confirmAction(message, function() {
                if ($(this).hasClass('ajax-delete')) {
                    deleteItemAjax(url);
                } else {
                    window.location.href = url;
                }
            }.bind(this));
        });
        
        // Form submit with loading
        $(document).on('submit', '.form-submit', function() {
            showLoading();
            $(this).find('button[type="submit"]').prop('disabled', true);
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Search functionality
        $(document).on('keyup', '.search-input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const targetTable = $(this).data('target') || '.searchable-table';
            
            $(targetTable + ' tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchTerm) > -1);
            });
        });
        
        // Select all checkbox
        $(document).on('change', '.select-all', function() {
            const target = $(this).data('target') || '.item-checkbox';
            $(target).prop('checked', $(this).prop('checked'));
            updateBulkActions();
        });
        
        $(document).on('change', '.item-checkbox', function() {
            updateBulkActions();
        });
        
        // Print functionality
        $(document).on('click', '.btn-print', function() {
            window.print();
        });
        
        // Export functionality
        $(document).on('click', '.btn-export', function() {
            const format = $(this).data('format') || 'excel';
            const url = $(this).data('url');
            
            if (url) {
                window.location.href = url + '?format=' + format;
            }
        });
        
        // Number input formatting
        $(document).on('input', '.number-format', function() {
            const value = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(numberFormat(value));
        });
        
        // Phone number formatting
        $(document).on('input', '.phone-format', function() {
            const value = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(phoneFormat(value));
        });
        
        // Toggle password visibility
        $(document).on('click', '.toggle-password', function() {
            const target = $(this).data('target');
            const type = $(target).attr('type') === 'password' ? 'text' : 'password';
            $(target).attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });
    }
    
    // ===== FORM VALIDATION =====
    function setupFormValidation() {
        $('.needs-validation').each(function() {
            $(this).on('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    showAlert('Please fill in all required fields correctly.', 'warning');
                }
                $(this).addClass('was-validated');
            });
        });
        
        // Real-time validation
        $('.form-control[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Email validation
        $('.email-input').on('blur', function() {
            const email = $(this).val();
            if (email && !isValidEmail(email)) {
                showFieldError($(this), 'Please enter a valid email address');
            } else {
                clearFieldError($(this));
            }
        });
        
        // Password strength
        $('.password-input').on('input', function() {
            const password = $(this).val();
            const strength = getPasswordStrength(password);
            updatePasswordStrength($(this), strength);
        });
    }
    
    // ===== DATATABLES SETUP =====
    function setupDataTables() {
        if ($.fn.DataTable) {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    url: App.assetsUrl + 'js/datatables-id.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        }
    }
    
    // ===== TOOLTIPS AND POPOVERS =====
    function setupTooltips() {
        if (typeof bootstrap !== 'undefined') {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        }
    }
    
    // ===== MODAL SETUP =====
    function setupModals() {
        // Dynamic modal loading
        $(document).on('click', '.modal-trigger', function() {
            const url = $(this).data('url');
            const target = $(this).data('target') || '#dynamicModal';
            
            if (url) {
                loadModalContent(url, target);
            }
        });
        
        // Clear modal on close
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('.modal-body').html('');
            $(this).find('form')[0]?.reset();
        });
    }
    
    // ===== NOTIFICATIONS =====
    function setupNotifications() {
        loadNotifications();
        
        // Mark notification as read
        $(document).on('click', '.notification-item', function() {
            const notifId = $(this).data('id');
            if (notifId) {
                markNotificationAsRead(notifId);
            }
        });
        
        // Refresh notifications every 5 minutes
        setInterval(loadNotifications, 300000);
    }
    
    // ===== SIDEBAR =====
    function setupSidebar() {
        // Mobile sidebar toggle
        $(document).on('click', '.sidebar-toggle', function() {
            $('#sidebar').toggleClass('show');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('#sidebar, .sidebar-toggle').length) {
                    $('#sidebar').removeClass('show');
                }
            }
        });
        
        // Collapse menu items
        $('.sidebar .nav-link[data-bs-toggle="collapse"]').on('click', function() {
            const icon = $(this).find('.fa-chevron-down');
            icon.toggleClass('fa-rotate-180');
        });
    }
    
    // ===== SESSION CHECK =====
    function checkSession() {
        setInterval(function() {
            $.ajax({
                url: App.baseUrl + 'api/check_session.php',
                method: 'GET',
                success: function(response) {
                    if (!response.valid) {
                        showAlert('Session expired. Redirecting to login...', 'warning');
                        setTimeout(() => {
                            window.location.href = App.baseUrl + 'auth/login.php';
                        }, 3000);
                    }
                }
            });
        }, 600000); // Check every 10 minutes
    }
    
    // ===== UTILITY FUNCTIONS =====
    
    function showLoading() {
        $('#loading-spinner').removeClass('d-none');
    }
    
    function hideLoading() {
        $('#loading-spinner').addClass('d-none');
    }
    
    function showAlert(message, type = 'info', duration = 5000) {
        const alertClass = 'alert-' + type;
        const iconClass = getAlertIcon(type);
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const alertContainer = $('.alert-container').length ? $('.alert-container') : $('main');
        alertContainer.prepend(alertHtml);
        
        setTimeout(() => {
            $('.alert').first().fadeOut('slow');
        }, duration);
    }
    
    function getAlertIcon(type) {
        const icons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        return icons[type] || icons['info'];
    }
    
    function confirmAction(message, callback) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        } else {
            if (confirm(message) && callback) {
                callback();
            }
        }
    }
    
    function deleteItemAjax(url) {
        $.ajax({
            url: url,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message, 'error');
                }
            }
        });
    }
    
    function updateBulkActions() {
        const checkedItems = $('.item-checkbox:checked').length;
        const bulkActions = $('.bulk-actions');
        
        if (checkedItems > 0) {
            bulkActions.removeClass('d-none');
            bulkActions.find('.selected-count').text(checkedItems);
        } else {
            bulkActions.addClass('d-none');
        }
    }
    
    function validateField(field) {
        const value = field.val();
        const required = field.prop('required');
        
        if (required && !value) {
            showFieldError(field, 'This field is required');
            return false;
        }
        
        clearFieldError(field);
        return true;
    }
    
    function showFieldError(field, message) {
        field.addClass('is-invalid');
        let feedback = field.siblings('.invalid-feedback');
        if (!feedback.length) {
            feedback = $('<div class="invalid-feedback"></div>');
            field.after(feedback);
        }
        feedback.text(message);
    }
    
    function clearFieldError(field) {
        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').remove();
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function getPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        if (score < 2) return 'weak';
        if (score < 4) return 'medium';
        return 'strong';
    }
    
    function updatePasswordStrength(field, strength) {
        const colors = {
            'weak': 'danger',
            'medium': 'warning',
            'strong': 'success'
        };
        
        let indicator = field.siblings('.password-strength');
        if (!indicator.length) {
            indicator = $('<div class="password-strength progress mt-2"><div class="progress-bar"></div></div>');
            field.after(indicator);
        }
        
        const progressBar = indicator.find('.progress-bar');
        const percentage = strength === 'weak' ? 33 : strength === 'medium' ? 66 : 100;
        
        progressBar
            .removeClass('bg-danger bg-warning bg-success')
            .addClass('bg-' + colors[strength])
            .css('width', percentage + '%')
            .text(strength.charAt(0).toUpperCase() + strength.slice(1));
    }
    
    function numberFormat(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    function phoneFormat(phone) {
        // Format: 08xx-xxxx-xxxx
        if (phone.length > 4 && phone.length <= 8) {
            return phone.substr(0, 4) + '-' + phone.substr(4);
        } else if (phone.length > 8) {
            return phone.substr(0, 4) + '-' + phone.substr(4, 4) + '-' + phone.substr(8);
        }
        return phone;
    }
    
    function loadModalContent(url, target) {
        showLoading();
        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                $(target).find('.modal-body').html(data);
                $(target).modal('show');
            },
            complete: function() {
                hideLoading();
            }
        });
    }
    
    function loadNotifications() {
        $.ajax({
            url: App.baseUrl + 'api/notifications.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateNotificationDropdown(response.notifications);
                    updateNotificationCount(response.unread_count);
                }
            }
        });
    }
    
    function updateNotificationDropdown(notifications) {
        const dropdown = $('.notification-dropdown');
        const notificationsList = dropdown.find('.notifications-list');
        
        if (notifications.length === 0) {
            notificationsList.html('<li class="dropdown-item text-center text-muted">No notifications</li>');
            return;
        }
        
        let html = '';
        notifications.forEach(function(notification) {
            html += `
                <li>
                    <a class="dropdown-item notification-item" href="#" data-id="${notification.id}">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-${getNotificationIcon(notification.type)} text-${notification.type}"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold">${notification.title}</div>
                                <div class="text-muted small">${notification.message}</div>
                                <div class="text-xs text-muted">${formatTimeAgo(notification.created_at)}</div>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        });
        
        notificationsList.html(html);
    }
    
    function updateNotificationCount(count) {
        const badge = $('#notif-count');
        if (count > 0) {
            badge.text(count).removeClass('d-none');
        } else {
            badge.addClass('d-none');
        }
    }
    
    function markNotificationAsRead(notificationId) {
        $.ajax({
            url: App.baseUrl + 'api/notifications.php',
            method: 'POST',
            data: {
                action: 'mark_read',
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    loadNotifications();
                }
            }
        });
    }
    
    function getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'warning': 'exclamation-triangle',
            'error': 'exclamation-circle',
            'success': 'check-circle'
        };
        return icons[type] || 'bell';
    }
    
    function formatTimeAgo(datetime) {
        const now = new Date();
        const time = new Date(datetime);
        const diffInSeconds = Math.floor((now - time) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        return Math.floor(diffInSeconds / 86400) + ' days ago';
    }
    
    // ===== EXPORT GLOBAL FUNCTIONS =====
    window.App.showAlert = showAlert;
    window.App.showLoading = showLoading;
    window.App.hideLoading = hideLoading;
    window.App.confirmAction = confirmAction;
    window.App.validateField = validateField;
    window.App.loadModalContent = loadModalContent;
    
});