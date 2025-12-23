<?php
// Get data for dashboard
try {
    $stats = $fun->getDashboardStats($_SESSION['site_domain']);
    $recent_automations = $fun->getAutomations($_SESSION['site_domain'], 1, 5);
    $recent_events = $fun->getRecentEvents($_SESSION['site_domain'], 5);
    $api_status = $fun->checkAPIBySite($_SESSION['site_domain']);
} catch (Exception $e) {
    $stats = [
        'total_automations' => 0,
        'active_automations' => 0,
        'total_events' => 0,
        'triggered_events' => 0,
        'conversion_rate' => 0
    ];
    $recent_automations = ['automations' => []];
    $recent_events = [];
    $api_status = false;
}

$page_title = 'Dashboard';
ob_start();
?>

<style>
    .dashboard {
        padding: 5px 18px;
        width: 100%;
        margin: 0;
    }

    .welcome-banner {
        background: linear-gradient(135deg, #91ba5bff 0%, #337318ff 100%);
        color: white;
        padding: 30px;
        border-radius: 6px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(30deg);
    }

    .welcome-content h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 700;
    }

    .welcome-content p {
        margin: 0 0 20px 0;
        opacity: 0.9;
        font-size: 16px;
    }

    .setup-progress {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .progress-step {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        border:1px solid rgba(255,255,255,0.4);
    }

    .step-complete {
        background: rgba(177, 229, 196, 0.3);
    }

    .step-pending {
        background: rgba(251, 191, 36, 0.3);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 14px;
        border-radius: 6px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }

    .stat-card.featured {
        background: linear-gradient(135deg, #779d44, #1a8325ff);
        color: white;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 15px;
        background: rgba(59, 130, 246, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-card.featured .stat-icon {
        background: rgba(255, 255, 255, 0.2);
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: #1f2937;
        text-align: center;
    }

    .stat-card.featured .stat-number {
        color: white;
    }

    .stat-label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
        text-align: center;
    }

    .stat-card.featured .stat-label {
        color: rgba(255, 255, 255, 0.9);
    }

    .stat-trend {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 8px;
    }

    .trend-up {
        background: #dcfce7;
        color: #166534;
    }

    .trend-down {
        background: #fee2e2;
        color: #dc2626;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 18px;
        font-weight: 600;
    }

    .view-all {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
    }

    .view-all:hover {
        text-decoration: underline;
    }

    .card-content {
        padding: 0;
    }

    .automation-list, .event-list {
        padding: 0;
    }

    .automation-item, .event-item {
        display: flex;
        align-items: center;
        padding: 16px 24px;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }

    .automation-item:hover, .event-item:hover {
        background: #f9fafb;
    }

    .automation-item:last-child, .event-item:last-child {
        border-bottom: none;
    }

    .automation-icon, .event-icon {
        width: 40px;
        height: 40px;
        background: #eff6ff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #3b82f6;
    }

    .event-icon {
        background: #f0fdf4;
        color: #16a34a;
    }

    .automation-info, .event-info {
        flex: 1;
    }

    .automation-name, .event-name {
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 4px 0;
    }

    .automation-meta, .event-meta {
        font-size: 13px;
        color: #6b7280;
    }

    .automation-status, .event-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
    }

    .status-draft {
        background: #fef3c7;
        color: #92400e;
    }

    .status-paused {
        background: #f3f4f6;
        color: #6b7280;
    }

    .status-success {
        background: #dcfce7;
        color: #166534;
    }

    .status-processing {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-failed {
        background: #fee2e2;
        color: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }

    .empty-state svg {
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 30px;
    }

    .action-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        text-align: center;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        color: inherit;
    }

    .action-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 12px;
        background: #eff6ff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #3b82f6;
    }

    .action-title {
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }

    .action-description {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }

    .api-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        margin-left: 10px;
    }

    .api-connected {
        background: #dcfce7;
        color: #166534;
    }

    .api-disconnected {
        background: #fee2e2;
        color: #dc2626;
    }

    .event-time {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }
</style>

<div class="dashboard">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1>Welcome to Aisensy</h1>
            <p>Manage your whatsapp automations and track performance</p>
            
            <div class="setup-progress">
                <div class="progress-step <?php echo $api_status ? 'step-complete' : 'step-pending'; ?>">
                    <span><?php echo $api_status ? '✓' : '⏳'; ?></span>
                    <span>Setup API KEY - <?php echo $api_status ? 'Done' : 'Pending'; ?></span>
                </div>
                <div class="progress-step <?php echo $stats['total_automations'] > 0 ? 'step-complete' : 'step-pending'; ?>">
                    <span><?php echo $stats['total_automations'] > 0 ? '✓' : '⏳'; ?></span>
                    <span>Add Automations - <?php echo $stats['total_automations'] > 0 ? 'Done' : 'Pending'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Stats Grid -->
    <div class="stats-grid">
        <!-- Total Automations -->
        <div class="stat-card featured">
            <div class="stat-number"><?php echo $stats['total_automations']; ?></div>
            <div class="stat-label">Total Automations</div>

        </div>

        <!-- Active Automations -->
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['active_automations']; ?></div>
            <div class="stat-label">Active Automations</div>
          
        </div>

        <!-- Triggered Events -->
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['triggered_events']); ?></div>
            <div class="stat-label">Successful Triggers</div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Recent Automations -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Automations</h3>
                <a href="./?page=automations" class="view-all">View All</a>
            </div>
            <div class="card-content">
                <div class="automation-list">
                    <?php if (!empty($recent_automations['automations'])): ?>
                        <?php foreach ($recent_automations['automations'] as $automation): ?>
                            <div class="automation-item">
                                <div class="automation-icon"><svg  height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5.06152 12C5.55362 8.05369 8.92001 5 12.9996 5C17.4179 5 20.9996 8.58172 20.9996 13C20.9996 17.4183 17.4179 21 12.9996 21H8M13 13V9M11 3H15M3 15H8M5 18H10" stroke="#779d44" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></div>
                                <div class="automation-info">
                                    <div class="automation-name"><?php echo htmlspecialchars($automation['name'] ?? 'Unnamed Automation'); ?></div>
                                    <div class="automation-meta">
                                        <?php echo htmlspecialchars($automation['joonweb_event'] ?? 'No event'); ?> • 
                                        Created <?php echo date('M j, Y', strtotime($automation['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                <div class="automation-status status-<?php echo $automation['status'] ?? 'draft'; ?>">
                                    <?php echo ucfirst($automation['status'] ?? 'draft'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M10.3246 4.31731C10.751 2.5609 13.249 2.5609 13.6754 4.31731C13.9508 5.45193 15.2507 5.99038 16.2478 5.38285C17.7913 4.44239 19.5576 6.2087 18.6172 7.75218C18.0096 8.74925 18.5481 10.0492 19.6827 10.3246C21.4391 10.751 21.4391 13.249 19.6827 13.6754C18.5481 13.9508 18.0096 15.2507 18.6172 16.2478C19.5576 17.7913 17.7913 19.5576 16.2478 18.6172C15.2507 18.0096 13.9508 18.5481 13.6754 19.6827C13.249 21.4391 10.751 21.4391 10.3246 19.6827C10.0492 18.5481 8.74926 18.0096 7.75219 18.6172C6.2087 19.5576 4.44239 17.7913 5.38285 16.2478C5.99038 15.2507 5.45193 13.9508 4.31731 13.6754C2.5609 13.249 2.5609 10.751 4.31731 10.3246C5.45193 10.0492 5.99037 8.74926 5.38285 7.75218C4.44239 6.2087 6.2087 4.44239 7.75219 5.38285C8.74926 5.99037 10.0492 5.45193 10.3246 4.31731Z"/>
                                <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z"/>
                            </svg>
                            <p>No automations yet. Create your first one to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Events</h3>
            </div>
            <div class="card-content">
                <div class="event-list">
                    <?php if (!empty($recent_events)): ?>
                        <?php foreach ($recent_events as $event): ?>
                            <div class="event-item">
                                <div class="event-icon">
                                    <?php 
                                    switch($event['status']) {
                                        case 'success': echo '✅'; break;
                                        case 'processing': echo '⏳'; break;
                                        case 'failed': echo '❌'; break;
                                        default: echo '📝';
                                    }
                                    ?>
                                </div>
                                <div class="event-info">
                                    <div class="event-name"><?php echo htmlspecialchars($event['automation_name'] ?? 'Unknown Automation'); ?></div>
                                    <div class="event-meta"><?php echo htmlspecialchars($event['event_type'] ?? 'Unknown Event'); ?></div>
                                    <div class="event-time"><?php echo date('M j, g:i A', strtotime($event['triggered_at'])); ?></div>
                                </div>
                                <div class="event-status status-<?php echo $event['status'] ?? 'processing'; ?>">
                                    <?php echo ucfirst($event['status'] ?? 'processing'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                            <p>No events triggered yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="./?page=automation&auto=new" class="action-card">
            <div class="action-icon">➕</div>
            <div class="action-title">Create Automation</div>
            <div class="action-description">Build a new automation workflow</div>
        </a>
        
        <a href="./?page=settings" class="action-card">
            <div class="action-icon">⚙️</div>
            <div class="action-title">Settings</div>
            <div class="action-description">Configure your API keys</div>
        </a>
      
    </div>
</div>

<script>
// Real-time stats updates
function updateStats() {
    fetch('./api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stat numbers
                document.querySelectorAll('.stat-number')[0].textContent = data.stats.total_automations;
                document.querySelectorAll('.stat-number')[1].textContent = data.stats.active_automations;
                document.querySelectorAll('.stat-number')[2].textContent = data.stats.triggered_events.toLocaleString();
                
                // Update progress steps
                const automationStep = document.querySelectorAll('.progress-step')[1];
                const eventStep = document.querySelectorAll('.progress-step')[2];
                
                if (data.stats.total_automations > 0) {
                    automationStep.className = 'progress-step step-complete';
                    automationStep.innerHTML = '<span>✅</span><span>Add Automations - Done</span>';
                }
                
                if (data.stats.triggered_events > 0) {
                    eventStep.className = 'progress-step step-complete';
                }
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Update stats every 30 seconds
setInterval(updateStats, 30000);

// Add some animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate stat cards on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>