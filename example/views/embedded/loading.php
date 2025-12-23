<?php
$site_domain = $_SESSION['site_domain'] ?? $_GET['site'] ?? '';
$is_embedded = isset($_GET['embedded']) || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loading App - <?php echo APP_NAME; ?></title>
    <!-- NO App Bridge script here -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f6f6f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .loading-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007cba;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        h2 {
            color: #202223;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        p {
            color: #6d7175;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .shop-info {
            background: #f1f2f3;
            padding: 12px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .error-message {
            background: #fde8e8;
            color: #c0341d;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            display: none;
        }
        
        .retry-button {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .retry-button:hover {
            background: #005a87;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        
        <h2>Loading <?php echo APP_NAME; ?></h2>
        <p>Please wait while we connect to your store...</p>
        
        <?php if (!empty($site_domain)): ?>
            <div class="shop-info">
                <strong>Store:</strong> <?php echo htmlspecialchars($site_domain); ?>
            </div>
        <?php endif; ?>
        
        <div id="errorMessage" class="error-message">
            <strong>Connection Issue</strong>
            <p id="errorText"></p>
        </div>
        
        <button id="retryButton" class="retry-button" style="display: none;" onclick="retryConnection()">
            Retry Connection
        </button>
        
        <div id="redirectInfo" style="margin-top: 15px; font-size: 12px; color: #8c9196;">
            <?php if (!$is_embedded): ?>
                <p>If loading takes too long, <a href="/auth/install.php?site=<?php echo urlencode($site_domain); ?>">click here to reinstall</a></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let retryCount = 0;
        const maxRetries = 2; // Reduced retries
        
        function startLoading() {
            console.log('Loading screen started');
            
            // Simple redirect after short delay
            setTimeout(() => {
                redirectToApp();
            }, 2000);
            
            // Fallback timeout
            setTimeout(() => {
                if (retryCount < maxRetries) {
                    retryCount++;
                    console.log(`Fallback retry ${retryCount}/${maxRetries}`);
                    redirectToApp();
                }
            }, 5000);
        }
        
        function redirectToApp() {
            const urlParams = new URLSearchParams(window.location.search);
            console.log('Redirecting to main app...');
            // Use replace to avoid history stack
            window.location.replace('/embedded.php?' + urlParams.toString());
        }
        
        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            const retryButton = document.getElementById('retryButton');
            
            errorText.textContent = message;
            errorMessage.style.display = 'block';
            retryButton.style.display = 'inline-block';
        }
        
        function retryConnection() {
            console.log('Manual retry requested');
            redirectToApp();
        }
        
        // Start loading process
        document.addEventListener('DOMContentLoaded', startLoading);
    </script>
</body>
</html>