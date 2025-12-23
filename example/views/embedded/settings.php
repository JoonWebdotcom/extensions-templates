<?php
$page_title = 'Settings';
$apidata = $fun->checkAPIBySite($session->getSiteDomain());
ob_start();
?>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    table tr {
        border-bottom: 1px solid #e5e7eb;
    }

    table th, table td {
        text-align: left;
        padding: 12px 8px;
        font-size: 15px;
    }

    table th {
        background: #f3f4f6;
        color: #374151;
        font-weight: 600;
    }

    table td {
        color: #1f2937;
    }

    tr:hover td {
        background: #f9fafb;
    }

    .empty-state {
        text-align: center;
        padding: 20px;
        color: #6b7280;
    }
    .main-content{
        padding: 5px 18px;
        width: 100%;
        margin: 0;
        text-align: left;
    }
    .save-button{
        background-color: #46b660;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    .save-button:hover{
        background-color: #3ea153;
    }
</style>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <h3 style="text-align:left;">Settings</h3>
            <div class="automation-section" style="text-align:left;">
                <div class="form-group">
             <p class="authkey-label">Aisensy API KEY</p>
                <div class="authkey-input-group">
                    <input type="text" value="<?=$apidata;?>" placeholder="Enter API Key" class="authkey-input apikey">
                </div>
                    <p style="color: red;font-size:10px;">Note: Changing the API key will stop the current automations from working, you need to delete and make again.</p>
                </div>
                <div class="form-group"  style="margin-bottom: 15px;">
                    <p class="authkey-label">Working Whatsapp Number</p>
                     <div class="authkey-input-group">
                        <input type="tel"value="<?=$whatsappNumber ?? '';?>" placeholder="Enter Your Mobile Number" class="authkey-input whatsappnumber">
                    </div>
                </div>
                <div class="form-group" >
                    <button class="save-button">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
            document.querySelector('.save-button').addEventListener('click', function() {
                const authKey = document.querySelector('.apikey').value.trim();
                const saveButton = this;
                
                if (!authKey) {
                    alert('Please enter a valid API key.');
                    return;
                }

                // Disable button and show loading state
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';

                // Send AJAX request to save the API key
                fetch('/ajax/core.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        site_domain: '<?php echo $session->getSiteDomain(); ?>',
                        api_key: authKey,
                        Whatsapp_number: document.querySelector('.whatsappnumber').value.trim(),
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
                        alert('API key saved successfully!');
                        // Update CRF token if provided
                        if (data.newCrfToken) {
                            // You might want to update a global variable or hidden field
                            console.log('New CRF token received');
                        }

                        location.reload(); // Reload to reflect changes
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving API key: ' + error.message);
                })
                .finally(() => {
                    // Re-enable button
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                });
            });
        </script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>