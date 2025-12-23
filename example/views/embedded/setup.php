<?php
$page_title = 'Get Started';
// Set CRF Token:
$_SESSION['crfToken'] = bin2hex(random_bytes(32));
ob_start();
?>
<style>
    /* Modern Get Started Page Styles */
    .main-content {
        padding: 0;
        margin: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
    }
    .automation-section li a{
        color: #fff !important;
    }

    .automation-section {
        background: white;
        border-radius: 6px;
        padding: 25px;
        width:95%;
        border: 1px solid #e2e8f0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

  

    .automation-section h2 {
        color: #42b764;
        font-size: 32px;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: 26px;
        letter-spacing: -0.5px;
        background: #42b764;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .automation-section h2 .highlight {
        background: #42b764;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
    }

    .automation-section h2 .highlight::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #42b764, #91cca2ff);
        border-radius: 2px;
    }

    .steps-container {
        background: #fefffdff;
        border-radius: 20px;
        padding: 18px 25px;
        margin-bottom: 20px;
        border: 1px dashed #405446ff;
        position: relative;
    }

    .steps-container::before {
        content: 'Quick Start Guide';
        position: absolute;
        top: -12px;
        left: 24px;
        background: white;
        padding: 4px 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        color: #5b615dff;
        border: 2px solid #5b615dff;
        letter-spacing: 0.5px;
    }

    .steps-list {
        list-style: none;
        padding: 0;
        margin: 0;
        text-align: left;
    }

    .steps-list li {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
    }

    .steps-list li:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }


    .step-content {
        flex: 1;
    }

    .step-content h3 {
        color: #3f3f3fff;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .step-content p {
        color: #727869ff;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .step-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background:  rgba(42, 159, 83, 1);
        text-decoration: none;
        padding: 8px 20px;
        border-radius: 12px;
        font-weight: 500;
        font-size: 14px;
        
        transition: all 0.3s ease;
    }

    .step-link:hover {
        background: rgba(44, 139, 77, 1);
    }

    .step-link svg {
        width: 16px;
        height: 16px;
    }

    .api-section {
        margin-top: 32px;
    }

    .authkey-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #4a4a4aff;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        text-align: left;
    }

 

    .authkey-input-group {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        position: relative;
    }

    .authkey-input {
        flex: 1;
        padding: 16px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 15px;
        color: #1e293b;
        background: #f8fafc;
        transition: all 0.3s ease;
    }

    .authkey-input:focus {
        outline: none;
        border-color: #42b764;
        background: white;
    }

    .authkey-input::placeholder {
        color: #94a3b8;
    }


    .input-icon {
        position: absolute;
        left: 20px;
        top: 55%;
        transform: translateY(-55%);
        color: #939b9f;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .authkey-input {
        padding-left: 48px;
    }

    .save-button {
        background: #42b764;
        color: white;
        border: none;
        padding: 16px 32px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .save-button:hover:not(:disabled) {
        background: #779d44
        transform: translateY(-2px);
    }

    .save-button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .save-button .spinner {
        display: none;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .save-button.saving .spinner {
        display: block;
    }

    .save-button.saving .button-text {
        display: none;
    }

    .help-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #779d44;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        margin-top: 16px;
        padding: 10px 16px;
        border-radius: 10px;
        background: #ecfdf5;
        border: 1px solid #a8c681ff;
        transition: all 0.3s ease;
    }

    .help-link:hover {
        background: #d1fae5;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    }

    .help-link svg {
        width: 16px;
        height: 16px;
    }

    .api-info-box {
        background: linear-gradient(135deg, #f0f9ff 0%, #ecfdf5 100%);
        border-radius: 16px;
        padding: 20px;
        margin-top: 24px;
        border-left: 4px solid #779d44;
        text-align: left;
    }

    .api-info-box h4 {
        color: #779d44;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .api-info-box p {
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
        margin: 0;
    }

    .success-message {
        display: none;
        background: linear-gradient(135deg, #779d44 0%, #9bc06aff 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 16px;
        margin-top: 24px;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-message svg {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
    }

    /* Responsive Design */
    @media (max-width: 640px) {
        .automation-section {
            padding: 32px 24px;
            width: 95%;
        }

        .automation-section h2 {
            font-size: 24px;
        }

        .steps-container {
            padding: 24px 20px;
        }

        .authkey-input-group {
            flex-direction: column;
        }

        .save-button {
            width: 100%;
            justify-content: center;
        }
    }

    /* Feature Highlights */
    .feature-highlights {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 40px;
        padding-top: 40px;
        border-top: 1px solid #e2e8f0;
    }

    .feature-card {
        background: linear-gradient(135deg, #f8fafc 0%, #cfe1b6ff 100%);
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.1);
        border-color: #a7f3d0;
    }

    .feature-card svg {
        width: 40px;
        height: 40px;
        color: #779d44;
        margin-bottom: 16px;
    }

    .feature-card h4 {
        color: #779d44;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .feature-card p {
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
        margin: 0;
    }
</style>

<div class="main-content">
    <div class="automation-section">
        <h2>Aisensy <span class="highlight">Whatsapp</span> Automations</h2>
        
        <div class="steps-container">
            <ol class="steps-list">
                <li>
                    <div class="step-content">
                        <h3>Create Your Aisensy Account</h3>
                        <p>Sign up for free and unlock whatsapp automation capabilities for your business & Get your API Key</p>
                        <a href="#" class="step-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                            Sign Up for Free
                        </a>
                    </div>
                </li>

            </ol>
        </div>

        <div class="api-section">
            <div class="authkey-label">
                Enter Your Aisensy API Key
            </div>
            
            <div class="authkey-input-group">
                <div class="input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <input type="text" 
                       placeholder="Paste your API key here (e.g., eyxxxxxxxxxxxx)" 
                       class="authkey-input" 
                       id="apiKeyInput">
                <button class="save-button" id="saveApiKeyBtn">
                    <div class="spinner"></div>
                    <span class="button-text">Connect Account</span>
                </button>
            </div>
            <div class="api-info-box">
                <h4>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12" y2="8"/>
                    </svg>
                    API Key Security
                </h4>
                <p>Your API key is stored securely and encrypted. Never share it publicly. You can regenerate it anytime from your Aisensy dashboard if needed.</p>
            </div>

            <div class="success-message" id="successMessage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>Successfully connected! You can now create automations.</span>
            </div>
        </div>

      
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveButton = document.getElementById('saveApiKeyBtn');
    const apiKeyInput = document.getElementById('apiKeyInput');
    const successMessage = document.getElementById('successMessage');

    // Focus animation for input


    // Auto-detect paste and trim
    apiKeyInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            this.value = this.value.trim();
            validateApiKey(this.value);
        }, 10);
    });

    // Real-time validation
    apiKeyInput.addEventListener('input', function() {
        validateApiKey(this.value);
    });

    // Save API key
    saveButton.addEventListener('click', saveApiKey);

    // Also allow Enter key to save
    apiKeyInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            saveApiKey();
        }
    });

    function validateApiKey(key) {
        const trimmedKey = key.trim();
        if (trimmedKey.length > 10 && trimmedKey.startsWith('ais_')) {
            apiKeyInput.style.borderColor = '#42b764';
            apiKeyInput.style.boxShadow = '0 0 0 3px rgba(66, 183, 100, 0.1)';
        } else if (trimmedKey.length > 0) {
            apiKeyInput.style.borderColor = '#f59e0b';
            apiKeyInput.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.1)';
        } else {
            apiKeyInput.style.borderColor = '#e2e8f0';
            apiKeyInput.style.boxShadow = 'none';
        }
    }

    function saveApiKey() {
        const authKey = apiKeyInput.value.trim();
        
        if (!authKey) {
            showError('Please enter a valid API key.');
            return;
        }

        // Basic validation
        if (authKey.length < 20 || !authKey.startsWith('ey')) {
            showError('Please enter a valid Aisensy API key. It should start with "ey"');
            return;
        }

        // Disable button and show loading state
        saveButton.disabled = true;
        saveButton.classList.add('saving');

        // Send AJAX request to save the API key
        fetch('/ajax/core.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                site_domain: '<?php echo $session->getSiteDomain(); ?>',
                api_key: authKey,
                action: 'save_api_key',
                crfToken: '<?php echo $_SESSION['crfToken']; ?>'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                successMessage.style.display = 'flex';
                
                // Show success notification
                showNotification('API key saved successfully! Your account is now connected.', 'success');
                
                // Update CRF token if provided
                if (data.newCrfToken) {
                    console.log('New CRF token received:', data.newCrfToken);
                }

                // Redirect to automations page after 2 seconds
                setTimeout(() => {
                    window.location.href = '?page=dashboard';
                }, 2000);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error saving API key: ' + error.message);
        })
        .finally(() => {
            // Re-enable button
            saveButton.disabled = false;
            saveButton.classList.remove('saving');
        });
    }

    function showError(message) {
        // Create error notification
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            border: 1px solid #dc2626;
            max-width: 300px;
            display: flex;
            align-items: center;
            gap: 12px;
        `;
        
        errorDiv.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12" y2="16"/>
            </svg>
            <span>${message}</span>
        `;
        
        document.body.appendChild(errorDiv);
        
        // Animate in
        setTimeout(() => {
            errorDiv.style.transform = 'translateX(0)';
        }, 10);
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.style.transform = 'translateX(120%)';
            setTimeout(() => errorDiv.remove(), 300);
        }, 5000);
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        const colors = {
            success: { bg: '#10b981', border: '#059669' },
            error: { bg: '#ef4444', border: '#dc2626' }
        };
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, ${colors[type].bg} 0%, ${colors[type].border} 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            border: 1px solid ${colors[type].border};
            max-width: 300px;
            display: flex;
            align-items: center;
            gap: 12px;
        `;
        
        notification.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>' : '<path d="M18 6L6 18M6 6l12 12"/>'}
            </svg>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(120%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Add some interactive effects
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
?>