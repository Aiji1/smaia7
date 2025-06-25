<?php
/**
 * Footer Template
 * File: includes/footer.php
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>

    <!-- Page specific content ends here -->
    
    <?php if ($current_page !== 'login.php' && SessionManager::isLoggedIn()): ?>
            </main>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
                    </span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Dibuat oleh <?php echo APP_AUTHOR; ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>
    
    <?php else: ?>
        </div> <!-- End login-wrapper -->
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="<?php echo assets_url('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo assets_url('js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo assets_url('js/app.js'); ?>"></script>
    
    <!-- Additional JavaScript based on page -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo assets_url('js/' . $js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page specific JavaScript -->
    <?php if (isset($custom_js)): ?>
        <script>
            <?php echo $custom_js; ?>
        </script>
    <?php endif; ?>
    
    <!-- Global JavaScript Variables -->
    <script>
        // Set global variables
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        window.ASSETS_URL = '<?php echo ASSETS_URL; ?>';
        window.CSRF_TOKEN = '<?php echo generate_csrf_token(); ?>';
        
        <?php if (SessionManager::isLoggedIn()): ?>
        window.USER_ID = <?php echo SessionManager::getUserId(); ?>;
        window.USER_ROLE = '<?php echo SessionManager::getUserRole(); ?>';
        window.USER_NAME = '<?php echo SessionManager::getFullName(); ?>';
        <?php endif; ?>
        
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // CSRF token for AJAX requests
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                    xhr.setRequestHeader("X-CSRFToken", window.CSRF_TOKEN);
                }
            }
        });
    </script>
    
    <!-- Analytics or tracking codes here -->
    <?php if (isset($tracking_code)): ?>
        <?php echo $tracking_code; ?>
    <?php endif; ?>
    
</body>
</html>