<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title ?? 'Appx'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- App Bridge loaded once -->
    <script src="https://apps.joonweb.com/app-bridge.js"></script>
    
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<style>
    /* Custom scrollbar styles */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #e0e0e0;
    }
    ::-webkit-scrollbar-thumb {
        background-color: #888;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    /* Firefox scrollbar styles */
    * {
        scrollbar-width: thin;
        scrollbar-color: #c2c2c2ff #e0e0e0;
    }
</style>
<body>    
    <script>
        // App Bridge initialization - runs once
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.JoonWebAppBridge !== 'undefined' && !window.joonwebApp) {
                window.joonwebApp = new JoonWebAppBridge({
                    apiKey: '<?php echo JOONWEB_CLIENT_ID; ?>',
                    host: '<?php echo $_GET['host'] ?? ''; ?>',
                    site: '<?php echo $session->getSiteDomain(); ?>'
                });
                
                console.log('App Bridge initialized');
                
                // Set up default title bar
                if (window.joonwebApp.actions.TitleBar) {
                    window.joonwebApp.actions.TitleBar.create(window.joonwebApp, {
                        title: '<?php echo $page_title ?? APP_NAME; ?>'
                    });
                }
            }
        });
    </script>
    
    <div class="app-container">
            <?php 
                $apidata = $fun->checkAPIBySite($session->getSiteDomain());
                include 'components/header.php';
                // Check if is Authenticated or Not
               
                if (!$apidata) {
                    // Not Authenticated - Show Setup Page
                    include_once 'setup.php';
                    
                }

            ?>
        <?php echo $content; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>

</body>
</html>