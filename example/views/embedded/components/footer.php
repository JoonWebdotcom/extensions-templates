<?php
// Simple footer for embedded app
?>
<footer style="
    background: #f6f6f7;
    border-top: 1px solid #e1e3e5;
    padding: 20px;
    margin-top: 40px;
    text-align: center;
    color: #6d7175;
    font-size: 14px;
">
    <div class="footer-content">
        <p>
            <strong><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></strong> 
            | Connected to: <?php echo htmlspecialchars($session->getSiteDomain() ?? 'Unknown Store'); ?>
            <?php if (isset($site['site']['name'])): ?>
                | Store: <?php echo htmlspecialchars($site['site']['name']); ?>
            <?php endif; ?>
        </p>
        
  
        
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e1e3e5; font-size: 12px;">
            <p>Powered by JoonWeb App Platform</p>
        </div>
    </div>
</footer>

<?php if (APP_DEBUG): ?>
<!-- Debug Info -->
<div style="
    position: fixed;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    z-index: 9999;
">
    Debug: 
    Session: <?php echo $session->isAuthenticated() ? 'Yes' : 'No'; ?> | 
    Page: <?php echo $_GET['page'] ?? 'dashboard'; ?>
</div>
<?php endif; ?>