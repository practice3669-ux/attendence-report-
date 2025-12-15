            </div> <!-- .content-wrapper -->
        </main> <!-- .main-content -->
    </div> <!-- .app-container -->

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Modal Container -->
    <div id="modalContainer" class="modal-container"></div>

    <?php $assetVersion = filemtime(__DIR__ . '/../assets/js/main.js'); ?>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js?v=<?php echo $assetVersion; ?>"></script>
    <?php 
        $pageJsPath = __DIR__ . '/../assets/js/' . basename($_SERVER['PHP_SELF'], '.php') . '.js';
        if (file_exists($pageJsPath)): 
            $pageJsVersion = filemtime($pageJsPath);
    ?>
        <script src="<?php echo APP_URL; ?>/assets/js/<?php echo basename($_SERVER['PHP_SELF'], '.php'); ?>.js?v=<?php echo $pageJsVersion; ?>"></script>
    <?php endif; ?>
</body>
</html>

