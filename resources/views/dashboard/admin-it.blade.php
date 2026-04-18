
@extends('layouts.dashboard')

@section('title', 'Admin IT Dashboard - System Control Center')

@section('page-title')
Admin IT Dashboard
@endsection

@section('page-subtitle')
System Control Center - Document Upload Management & Team Settings
@endsection

@section('styles')
<style>
:root {
    --it-primary: #6366f1;
    --it-secondary: #4f46e5;
    --it-accent: #8b5cf6;
    --it-light: #a5b4fc;
    --it-bg: #f0f4ff;
    --it-dark: #312e81;
    --it-shadow: 0 8px 32px rgba(99, 102, 241, 0.1);
    --it-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #8b5cf6 100%);
}

.main-container {
    background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
    min-height: 100vh;
    padding: 25px 0;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 35px;
    box-shadow: var(--it-shadow);
    border: 2px solid var(--it-light);
}

.page-title {
    color: var(--it-primary);
    font-weight: 800;
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title i {
    background: var(--it-gradient);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.control-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

.control-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--it-shadow);
    border: 2px solid var(--it-light);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.control-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.2);
}

.control-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--it-gradient);
}

.team-control-card {
    border-left: 6px solid var(--it-primary);
}

.team-control-card.exim::before {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.team-control-card.logistic::before {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.team-control-card.finance::before {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.control-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.control-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--it-primary);
    display: flex;
    align-items: center;
    gap: 12px;
}

.control-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    background: var(--it-gradient);
}

.control-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-active {
    background: #10b981;
}

.status-inactive {
    background: #ef4444;
}

.status-maintenance {
    background: #f59e0b;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.control-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.toggle-section {
    background: rgba(99, 102, 241, 0.05);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--it-light);
}

.toggle-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.toggle-title {
    font-weight: 600;
    color: var(--it-dark);
    font-size: 1rem;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background: var(--it-gradient);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 15px 0 0 0;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 0.9rem;
    color: #64748b;
}

.feature-list li i {
    width: 16px;
    color: var(--it-primary);
}

.btn-control {
    background: var(--it-gradient);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 12px 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}

.btn-control:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
}

.btn-control:disabled {
    background: #e2e8f0;
    color: #a0aec0;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: var(--it-shadow);
    border: 2px solid var(--it-light);
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--it-primary);
    margin-bottom: 5px;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
}

.system-logs {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--it-shadow);
    border: 2px solid var(--it-light);
    margin-bottom: 25px;
}

.logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.logs-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--it-primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.log-entry {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 0.9rem;
    border-left: 4px solid transparent;
}

.log-entry.info {
    background: rgba(59, 130, 246, 0.1);
    border-left-color: #3b82f6;
}

.log-entry.success {
    background: rgba(16, 185, 129, 0.1);
    border-left-color: #10b981;
}

.log-entry.warning {
    background: rgba(245, 158, 11, 0.1);
    border-left-color: #f59e0b;
}

.log-entry.error {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: #ef4444;
}

.log-time {
    font-family: monospace;
    color: #64748b;
    min-width: 80px;
}

.log-message {
    flex: 1;
}

.alert {
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border-left: 4px solid #10b981;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border-left: 4px solid #f59e0b;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-left: 4px solid #ef4444;
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border-left: 4px solid #3b82f6;
}

.maintenance-mode {
    background: rgba(245, 158, 11, 0.1);
    border: 2px solid #f59e0b;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: center;
}

.maintenance-mode h4 {
    color: #d97706;
    margin-bottom: 10px;
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
}

.toast {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 2px solid var(--it-light);
    margin-bottom: 10px;
    overflow: hidden;
    animation: slideInRight 0.3s ease;
    min-width: 300px;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.toast-header {
    background: var(--it-gradient);
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
}

.toast-body {
    padding: 15px;
    font-size: 0.85rem;
}

.refresh-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: var(--it-gradient);
    color: white;
    border: none;
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.refresh-fab:hover {
    transform: scale(1.1) rotate(180deg);
}

@media (max-width: 768px) {
    .control-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .control-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
@endsection

@section('content')
<div class="main-container">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-cogs"></i>
                Admin IT Control Center
            </div>
            <p class="text-muted">System administration and document upload management for EXIM and Logistic teams</p>
        </div>

        <!-- Maintenance Mode Alert -->
        <div id="maintenance-alert" class="maintenance-mode" style="display: none;">
            <h4><i class="fas fa-tools"></i> System Maintenance Mode</h4>
            <p>Document upload forms are currently disabled for maintenance. Users will see a maintenance message.</p>
            <button class="btn-control btn-warning" onclick="disableMaintenanceMode()">
                <i class="fas fa-play"></i>
                Disable Maintenance Mode
            </button>
        </div>

        <!-- System Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-uploads">0</div>
                <div class="stat-label">Total Uploads Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-sessions">0</div>
                <div class="stat-label">Active Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="system-uptime">0h</div>
                <div class="stat-label">System Uptime</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="storage-usage">0%</div>
                <div class="stat-label">Storage Usage</div>
            </div>
        </div>

        <!-- Team Control Cards -->
        <div class="control-grid">
            
            <!-- EXIM Team Control -->
            <div class="control-card team-control-card exim">
                <div class="control-header">
                    <div class="control-title">
                        <div class="control-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="fas fa-file-export"></i>
                        </div>
                        EXIM Team
                    </div>
                    <div class="control-status">
                        <div class="status-indicator status-active" id="exim-status"></div>
                        <span id="exim-status-text">Active</span>
                    </div>
                </div>
                
                <div class="control-actions">
                    <!-- Document Upload Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">Document Upload Form</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="exim-upload-toggle" checked onchange="toggleEximUpload()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Upload PEB, COO, Fumigasi</li>
                            <li><i class="fas fa-check"></i> Real-time validation</li>
                            <li><i class="fas fa-check"></i> Progress tracking</li>
                        </ul>
                    </div>

                    <!-- FTP Integration Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">FTP Auto-Fetch</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="exim-ftp-toggle" checked onchange="toggleEximFTP()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Automatic document retrieval</li>
                            <li><i class="fas fa-check"></i> FTP server monitoring</li>
                        </ul>
                    </div>

                    <!-- Control Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-control btn-success" onclick="restartEximServices()">
                            <i class="fas fa-sync-alt"></i>
                            Restart Services
                        </button>
                        <button class="btn-control btn-warning" onclick="maintenanceModeExim()">
                            <i class="fas fa-tools"></i>
                            Maintenance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logistic Team Control -->
            <div class="control-card team-control-card logistic">
                <div class="control-header">
                    <div class="control-title">
                        <div class="control-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        Logistic Team
                    </div>
                    <div class="control-status">
                        <div class="status-indicator status-active" id="logistic-status"></div>
                        <span id="logistic-status-text">Active</span>
                    </div>
                </div>
                
                <div class="control-actions">
                    <!-- Document Upload Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">Document Upload Form</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="logistic-upload-toggle" checked onchange="toggleLogisticUpload()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Upload BL, AWB, Container Load Plan</li>
                            <li><i class="fas fa-check"></i> Shipping instructions</li>
                            <li><i class="fas fa-check"></i> Freight invoices</li>
                        </ul>
                    </div>

                    <!-- FTP Integration Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">FTP Auto-Fetch</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="logistic-ftp-toggle" checked onchange="toggleLogisticFTP()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Shipping document retrieval</li>
                            <li><i class="fas fa-check"></i> Container tracking integration</li>
                        </ul>
                    </div>

                    <!-- Control Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-control btn-success" onclick="restartLogisticServices()">
                            <i class="fas fa-sync-alt"></i>
                            Restart Services
                        </button>
                        <button class="btn-control btn-warning" onclick="maintenanceModeLogistic()">
                            <i class="fas fa-tools"></i>
                            Maintenance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Finance Team Control -->
            <div class="control-card team-control-card finance">
                <div class="control-header">
                    <div class="control-title">
                        <div class="control-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        Finance Team
                    </div>
                    <div class="control-status">
                        <div class="status-indicator status-active" id="finance-status"></div>
                        <span id="finance-status-text">Active</span>
                    </div>
                </div>
                
                <div class="control-actions">
                    <!-- Document Upload Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">Document Upload Form</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="finance-upload-toggle" checked onchange="toggleFinanceUpload()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Upload Invoice, Packing List</li>
                            <li><i class="fas fa-check"></i> Payment instructions</li>
                            <li><i class="fas fa-check"></i> Container checklists</li>
                        </ul>
                    </div>

                    <!-- Email System Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">Email System</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="finance-email-toggle" checked onchange="toggleFinanceEmail()">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Buyer email notifications</li>
                            <li><i class="fas fa-check"></i> CC system integration</li>
                        </ul>
                    </div>

                    <!-- Control Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-control btn-success" onclick="restartFinanceServices()">
                            <i class="fas fa-sync-alt"></i>
                            Restart Services
                        </button>
                        <button class="btn-control btn-warning" onclick="maintenanceModeFinance()">
                            <i class="fas fa-tools"></i>
                            Maintenance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Global System Control -->
            <div class="control-card">
                <div class="control-header">
                    <div class="control-title">
                        <div class="control-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        Global System
                    </div>
                    <div class="control-status">
                        <div class="status-indicator status-active" id="system-status"></div>
                        <span id="system-status-text">Operational</span>
                    </div>
                </div>
                
                <div class="control-actions">
                    <!-- Database Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">Database Access</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="database-toggle" checked onchange="toggleDatabase()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- SAP Integration Toggle -->
                    <div class="toggle-section">
                        <div class="toggle-header">
                            <div class="toggle-title">SAP Integration</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="sap-toggle" checked onchange="toggleSAP()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Emergency Controls -->
                    <div style="display: flex; gap: 10px; flex-direction: column;">
                        <button class="btn-control btn-danger" onclick="emergencyShutdown()">
                            <i class="fas fa-power-off"></i>
                            Emergency Shutdown
                        </button>
                        <button class="btn-control btn-warning" onclick="globalMaintenanceMode()">
                            <i class="fas fa-exclamation-triangle"></i>
                            Global Maintenance
                        </button>
                        <button class="btn-control btn-success" onclick="systemHealthCheck()">
                            <i class="fas fa-heartbeat"></i>
                            Health Check
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Logs -->
        <div class="system-logs">
            <div class="logs-header">
                <div class="logs-title">
                    <i class="fas fa-list-alt"></i>
                    System Activity Logs
                </div>
                <button class="btn-control" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
            
            <div id="system-logs-container">
                <div class="log-entry info">
                    <div class="log-time">15:30:25</div>
                    <div class="log-message">System initialized successfully</div>
                </div>
                <div class="log-entry success">
                    <div class="log-time">15:30:26</div>
                    <div class="log-message">EXIM upload form enabled</div>
                </div>
                <div class="log-entry success">
                    <div class="log-time">15:30:27</div>
                    <div class="log-message">Logistic upload form enabled</div>
                </div>
                <div class="log-entry info">
                    <div class="log-time">15:30:28</div>
                    <div class="log-message">Finance email system ready</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Refresh FAB -->
<button class="refresh-fab" onclick="refreshDashboard()">
    <i class="fas fa-sync-alt"></i>
</button>
@endsection

@section('scripts')
<script>
// Global state management
let systemState = {
    exim: {
        upload: true,
        ftp: true,
        maintenance: false
    },
    logistic: {
        upload: true,
        ftp: true,
        maintenance: false
    },
    finance: {
        upload: true,
        email: true,
        maintenance: false
    },
    global: {
        database: true,
        sap: true,
        maintenance: false
    }
};

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminIT();
    loadSystemStatus();
    startStatusMonitoring();
    loadSystemStats();
});

function initializeAdminIT() {
    console.log('=== ADMIN IT DASHBOARD INITIALIZED ===');
    addLog('info', 'Admin IT Dashboard initialized');
    
    // Load saved states from localStorage
    const savedState = localStorage.getItem('systemState');
    if (savedState) {
        systemState = JSON.parse(savedState);
        updateToggleStates();
    }
    
    updateSystemStatus();
}

function updateToggleStates() {
    // Update EXIM toggles
    document.getElementById('exim-upload-toggle').checked = systemState.exim.upload;
    document.getElementById('exim-ftp-toggle').checked = systemState.exim.ftp;
    
    // Update Logistic toggles
    document.getElementById('logistic-upload-toggle').checked = systemState.logistic.upload;
    document.getElementById('logistic-ftp-toggle').checked = systemState.logistic.ftp;
    
    // Update Finance toggles
    document.getElementById('finance-upload-toggle').checked = systemState.finance.upload;
    document.getElementById('finance-email-toggle').checked = systemState.finance.email;
    
    // Update Global toggles
    document.getElementById('database-toggle').checked = systemState.global.database;
    document.getElementById('sap-toggle').checked = systemState.global.sap;
}

function saveSystemState() {
    localStorage.setItem('systemState', JSON.stringify(systemState));
    
    // Send to server for persistence
    fetch('/api/admin-it/save-state', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(systemState)
    }).catch(error => {
        console.error('Failed to save system state:', error);
    });
}

// ==========================================
// EXIM TEAM CONTROLS
// ==========================================

function toggleEximUpload() {
    const toggle = document.getElementById('exim-upload-toggle');
    systemState.exim.upload = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `EXIM upload form ${action}`);
    
    // Send command to EXIM dashboard
    sendTeamCommand('exim', 'upload', toggle.checked);
    
    updateStatusIndicator('exim');
    saveSystemState();
    
    showToast('EXIM Upload', `Upload form ${action}`, toggle.checked ? 'success' : 'warning');
}

function toggleEximFTP() {
    const toggle = document.getElementById('exim-ftp-toggle');
    systemState.exim.ftp = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `EXIM FTP auto-fetch ${action}`);
    
    sendTeamCommand('exim', 'ftp', toggle.checked);
    
    updateStatusIndicator('exim');
    saveSystemState();
    
    showToast('EXIM FTP', `Auto-fetch ${action}`, toggle.checked ? 'success' : 'warning');
}

function restartEximServices() {
    showToast('EXIM Services', 'Restarting services...', 'info');
    addLog('info', 'Restarting EXIM services...');
    
    fetch('/api/admin-it/restart-team-services', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ team: 'exim' })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', 'EXIM services restarted successfully');
            showToast('EXIM Services', 'Services restarted successfully', 'success');
        } else {
            addLog('error', 'Failed to restart EXIM services');
            showToast('EXIM Services', 'Failed to restart services', 'error');
        }
    }).catch(error => {
        addLog('error', 'Error restarting EXIM services: ' + error.message);
        showToast('EXIM Services', 'Error restarting services', 'error');
    });
}

function maintenanceModeExim() {
    systemState.exim.maintenance = !systemState.exim.maintenance;
    
    const action = systemState.exim.maintenance ? 'enabled' : 'disabled';
    addLog('warning', `EXIM maintenance mode ${action}`);
    
    sendTeamCommand('exim', 'maintenance', systemState.exim.maintenance);
    
    updateStatusIndicator('exim');
    saveSystemState();
    
    showToast('EXIM Maintenance', `Maintenance mode ${action}`, 'warning');
}

// ==========================================
// LOGISTIC TEAM CONTROLS
// ==========================================

function toggleLogisticUpload() {
    const toggle = document.getElementById('logistic-upload-toggle');
    systemState.logistic.upload = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `Logistic upload form ${action}`);
    
    sendTeamCommand('logistic', 'upload', toggle.checked);
    
    updateStatusIndicator('logistic');
    saveSystemState();
    
    showToast('Logistic Upload', `Upload form ${action}`, toggle.checked ? 'success' : 'warning');
}

function toggleLogisticFTP() {
    const toggle = document.getElementById('logistic-ftp-toggle');
    systemState.logistic.ftp = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `Logistic FTP auto-fetch ${action}`);
    
    sendTeamCommand('logistic', 'ftp', toggle.checked);
    
    updateStatusIndicator('logistic');
    saveSystemState();
    
    showToast('Logistic FTP', `Auto-fetch ${action}`, toggle.checked ? 'success' : 'warning');
}

function restartLogisticServices() {
    showToast('Logistic Services', 'Restarting services...', 'info');
    addLog('info', 'Restarting Logistic services...');
    
    fetch('/api/admin-it/restart-team-services', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ team: 'logistic' })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', 'Logistic services restarted successfully');
            showToast('Logistic Services', 'Services restarted successfully', 'success');
        } else {
            addLog('error', 'Failed to restart Logistic services');
            showToast('Logistic Services', 'Failed to restart services', 'error');
        }
    }).catch(error => {
        addLog('error', 'Error restarting Logistic services: ' + error.message);
        showToast('Logistic Services', 'Error restarting services', 'error');
    });
}

function maintenanceModeLogistic() {
    systemState.logistic.maintenance = !systemState.logistic.maintenance;
    
    const action = systemState.logistic.maintenance ? 'enabled' : 'disabled';
    addLog('warning', `Logistic maintenance mode ${action}`);
    
    sendTeamCommand('logistic', 'maintenance', systemState.logistic.maintenance);
    
    updateStatusIndicator('logistic');
    saveSystemState();
    
    showToast('Logistic Maintenance', `Maintenance mode ${action}`, 'warning');
}

// ==========================================
// FINANCE TEAM CONTROLS
// ==========================================

function toggleFinanceUpload() {
    const toggle = document.getElementById('finance-upload-toggle');
    systemState.finance.upload = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `Finance upload form ${action}`);
    
    sendTeamCommand('finance', 'upload', toggle.checked);
    
    updateStatusIndicator('finance');
    saveSystemState();
    
    showToast('Finance Upload', `Upload form ${action}`, toggle.checked ? 'success' : 'warning');
}

function toggleFinanceEmail() {
    const toggle = document.getElementById('finance-email-toggle');
    systemState.finance.email = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'warning', `Finance email system ${action}`);
    
    sendTeamCommand('finance', 'email', toggle.checked);
    
    updateStatusIndicator('finance');
    saveSystemState();
    
    showToast('Finance Email', `Email system ${action}`, toggle.checked ? 'success' : 'warning');
}

function restartFinanceServices() {
    showToast('Finance Services', 'Restarting services...', 'info');
    addLog('info', 'Restarting Finance services...');
    
    fetch('/api/admin-it/restart-team-services', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ team: 'finance' })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', 'Finance services restarted successfully');
            showToast('Finance Services', 'Services restarted successfully', 'success');
        } else {
            addLog('error', 'Failed to restart Finance services');
            showToast('Finance Services', 'Failed to restart services', 'error');
        }
    }).catch(error => {
        addLog('error', 'Error restarting Finance services: ' + error.message);
        showToast('Finance Services', 'Error restarting services', 'error');
    });
}

function maintenanceModeFinance() {
    systemState.finance.maintenance = !systemState.finance.maintenance;
    
    const action = systemState.finance.maintenance ? 'enabled' : 'disabled';
    addLog('warning', `Finance maintenance mode ${action}`);
    
    sendTeamCommand('finance', 'maintenance', systemState.finance.maintenance);
    
    updateStatusIndicator('finance');
    saveSystemState();
    
    showToast('Finance Maintenance', `Maintenance mode ${action}`, 'warning');
}

// ==========================================
// GLOBAL SYSTEM CONTROLS
// ==========================================

function toggleDatabase() {
    const toggle = document.getElementById('database-toggle');
    systemState.global.database = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'error', `Database access ${action}`);
    
    sendGlobalCommand('database', toggle.checked);
    
    updateStatusIndicator('system');
    saveSystemState();
    
    showToast('Database', `Database access ${action}`, toggle.checked ? 'success' : 'error');
}

function toggleSAP() {
    const toggle = document.getElementById('sap-toggle');
    systemState.global.sap = toggle.checked;
    
    const action = toggle.checked ? 'enabled' : 'disabled';
    addLog(toggle.checked ? 'success' : 'error', `SAP integration ${action}`);
    
    sendGlobalCommand('sap', toggle.checked);
    
    updateStatusIndicator('system');
    saveSystemState();
    
    showToast('SAP Integration', `SAP integration ${action}`, toggle.checked ? 'success' : 'error');
}

function emergencyShutdown() {
    if (!confirm('Are you sure you want to perform an emergency shutdown? This will disable all services.')) {
        return;
    }
    
    addLog('error', 'EMERGENCY SHUTDOWN INITIATED');
    showToast('Emergency', 'Emergency shutdown initiated', 'error');
    
    // Disable all services
    systemState.exim.upload = false;
    systemState.exim.ftp = false;
    systemState.logistic.upload = false;
    systemState.logistic.ftp = false;
    systemState.finance.upload = false;
    systemState.finance.email = false;
    systemState.global.database = false;
    systemState.global.sap = false;
    
    updateToggleStates();
    updateAllStatusIndicators();
    saveSystemState();
    
    sendGlobalCommand('emergency_shutdown', true);
}

function globalMaintenanceMode() {
    const isMaintenanceMode = systemState.global.maintenance;
    systemState.global.maintenance = !isMaintenanceMode;
    
    const action = systemState.global.maintenance ? 'enabled' : 'disabled';
    addLog('warning', `Global maintenance mode ${action}`);
    
    if (systemState.global.maintenance) {
        document.getElementById('maintenance-alert').style.display = 'block';
    } else {
        document.getElementById('maintenance-alert').style.display = 'none';
    }
    
    sendGlobalCommand('global_maintenance', systemState.global.maintenance);
    
    updateAllStatusIndicators();
    saveSystemState();
    
    showToast('Global Maintenance', `Global maintenance mode ${action}`, 'warning');
}

function systemHealthCheck() {
    showToast('Health Check', 'Running system health check...', 'info');
    addLog('info', 'Running system health check...');
    
    fetch('/api/admin-it/health-check', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', 'System health check passed');
            showToast('Health Check', 'System is healthy', 'success');
        } else {
            addLog('warning', 'System health check found issues');
            showToast('Health Check', 'Issues detected: ' + data.issues.join(', '), 'warning');
        }
    }).catch(error => {
        addLog('error', 'Health check failed: ' + error.message);
        showToast('Health Check', 'Health check failed', 'error');
    });
}

// ==========================================
// COMMUNICATION FUNCTIONS
// ==========================================

function sendTeamCommand(team, command, value) {
    const payload = {
        team: team,
        command: command,
        value: value,
        timestamp: new Date().toISOString()
    };
    
    fetch('/api/admin-it/team-command', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', `${team} ${command} command sent successfully`);
        } else {
            addLog('error', `Failed to send ${team} ${command} command`);
        }
    }).catch(error => {
        addLog('error', `Error sending ${team} ${command} command: ` + error.message);
    });
}

function sendGlobalCommand(command, value) {
    const payload = {
        command: command,
        value: value,
        timestamp: new Date().toISOString()
    };
    
    fetch('/api/admin-it/global-command', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog('success', `Global ${command} command sent successfully`);
        } else {
            addLog('error', `Failed to send global ${command} command`);
        }
    }).catch(error => {
        addLog('error', `Error sending global ${command} command: ` + error.message);
    });
}

// ==========================================
// STATUS MANAGEMENT
// ==========================================

function updateStatusIndicator(team) {
    const statusElement = document.getElementById(`${team}-status`);
    const statusTextElement = document.getElementById(`${team}-status-text`);
    
    if (!statusElement || !statusTextElement) return;
    
    let status = 'active';
    let statusText = 'Active';
    
    if (team === 'exim') {
        if (systemState.exim.maintenance) {
            status = 'maintenance';
            statusText = 'Maintenance';
        } else if (!systemState.exim.upload && !systemState.exim.ftp) {
            status = 'inactive';
            statusText = 'Inactive';
        }
    } else if (team === 'logistic') {
        if (systemState.logistic.maintenance) {
            status = 'maintenance';
            statusText = 'Maintenance';
        } else if (!systemState.logistic.upload && !systemState.logistic.ftp) {
            status = 'inactive';
            statusText = 'Inactive';
        }
    } else if (team === 'finance') {
        if (systemState.finance.maintenance) {
            status = 'maintenance';
            statusText = 'Maintenance';
        } else if (!systemState.finance.upload && !systemState.finance.email) {
            status = 'inactive';
            statusText = 'Inactive';
        }
    } else if (team === 'system') {
        if (systemState.global.maintenance) {
            status = 'maintenance';
            statusText = 'Maintenance';
        } else if (!systemState.global.database || !systemState.global.sap) {
            status = 'inactive';
            statusText = 'Degraded';
        }
    }
    
    statusElement.className = `status-indicator status-${status}`;
    statusTextElement.textContent = statusText;
}

function updateAllStatusIndicators() {
    updateStatusIndicator('exim');
    updateStatusIndicator('logistic');
    updateStatusIndicator('finance');
    updateStatusIndicator('system');
}

function updateSystemStatus() {
    updateAllStatusIndicators();
}

// ==========================================
// MONITORING FUNCTIONS
// ==========================================

function loadSystemStatus() {
    fetch('/api/admin-it/system-status')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update system state from server
            if (data.state) {
                systemState = { ...systemState, ...data.state };
                updateToggleStates();
                updateSystemStatus();
            }
        }
    })
    .catch(error => {
        console.error('Failed to load system status:', error);
        addLog('warning', 'Failed to load system status from server');
    });
}

function startStatusMonitoring() {
    // Update system stats every 30 seconds
    setInterval(loadSystemStats, 30000);
    
    // Check system status every 60 seconds
    setInterval(loadSystemStatus, 60000);
    
    // Auto-refresh logs every 2 minutes
    setInterval(refreshLogs, 120000);
}

function loadSystemStats() {
    fetch('/api/admin-it/system-stats')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('total-uploads').textContent = data.stats.total_uploads || 0;
            document.getElementById('active-sessions').textContent = data.stats.active_sessions || 0;
            document.getElementById('system-uptime').textContent = data.stats.uptime || '0h';
            document.getElementById('storage-usage').textContent = (data.stats.storage_usage || 0) + '%';
        }
    })
    .catch(error => {
        console.error('Failed to load system stats:', error);
    });
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

function addLog(type, message) {
    const logContainer = document.getElementById('system-logs-container');
    const now = new Date();
    const timeString = now.toTimeString().split(' ')[0];
    
    const logEntry = document.createElement('div');
    logEntry.className = `log-entry ${type}`;
    logEntry.innerHTML = `
        <div class="log-time">${timeString}</div>
        <div class="log-message">${message}</div>
    `;
    
    logContainer.insertBefore(logEntry, logContainer.firstChild);
    
    // Keep only last 50 log entries
    while (logContainer.children.length > 50) {
        logContainer.removeChild(logContainer.lastChild);
    }
}

function refreshLogs() {
    fetch('/api/admin-it/recent-logs')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.logs) {
            const logContainer = document.getElementById('system-logs-container');
            logContainer.innerHTML = '';
            
            data.logs.forEach(log => {
                const logEntry = document.createElement('div');
                logEntry.className = `log-entry ${log.type}`;
                logEntry.innerHTML = `
                    <div class="log-time">${log.time}</div>
                    <div class="log-message">${log.message}</div>
                `;
                logContainer.appendChild(logEntry);
            });
        }
    })
    .catch(error => {
        console.error('Failed to refresh logs:', error);
        addLog('error', 'Failed to refresh logs from server');
    });
}

function showToast(title, message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    const toastId = 'toast-' + Date.now();
    
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.id = toastId;
    
    const typeColors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    toast.innerHTML = `
        <div class="toast-header" style="background-color: ${typeColors[type]}">
            ${title}
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (document.getElementById(toastId)) {
            toastContainer.removeChild(toast);
        }
    }, 5000);
}

function refreshDashboard() {
    location.reload();
}

function disableMaintenanceMode() {
    systemState.global.maintenance = false;
    document.getElementById('maintenance-alert').style.display = 'none';
    
    addLog('success', 'Global maintenance mode disabled');
    sendGlobalCommand('global_maintenance', false);
    
    updateAllStatusIndicators();
    saveSystemState();
    
    showToast('Maintenance Mode', 'Global maintenance mode disabled', 'success');
}
</script>
@endsection