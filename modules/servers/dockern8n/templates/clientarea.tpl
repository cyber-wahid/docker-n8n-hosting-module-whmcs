<!-- N8N Docker Manager - Professional Client Area -->
<style>
/* Base Styles - Transparent & Inherit from Parent Theme */
.n8n-manager { 
    font-family: inherit;
    font-size: inherit;
    line-height: 1.5;
    max-width: 100%;
    color: inherit;
    
    /* Only accent color - everything else inherits */
    --primary: #FF6D5A;
    --primary-hover: #E85C4A;
    --primary-2: #FF8A65;
    --primary-3: #FFA726;
    
    /* Transparent defaults */
    --bg-transparent: transparent;
    --border-subtle: rgba(128, 128, 128, 0.2);
    --border-medium: rgba(128, 128, 128, 0.3);
    --shadow-subtle: rgba(0, 0, 0, 0.05);
    --hover-overlay: rgba(128, 128, 128, 0.1);
}
.n8n-manager *,
.n8n-manager *::before,
.n8n-manager *::after {
    box-sizing: border-box;
}

/* Header - Clean */
.n8n-manager .header-bar { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    flex-wrap: wrap; 
    gap: 15px; 
    padding: 15px 5px 15px 5px; 
    margin-bottom: 20px;
    position: relative;
    background: transparent;
    border-bottom: 1px solid var(--border-subtle);
}
.n8n-manager .header-bar h4 { 
    margin: 0; 
    color: inherit; 
    font-size: 22px;
    font-weight: 600;
}
.n8n-manager .header-bar h4 i {
    color: var(--primary);
    margin-right: 10px;
}
.n8n-manager .header-bar a { 
    color: var(--primary); 
    font-size: 14px;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    border: 1px solid var(--primary);
    transition: all 0.2s;
}
.n8n-manager .header-bar a:hover {
    background: var(--primary);
    color: #fff;
}

/* Header Link */

/* Tabs */
.n8n-manager .nav-tabs { 
    list-style: none;
    padding-left: 0;
    border-bottom: 2px solid var(--border-subtle); 
    margin-bottom: 25px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    background: transparent;
}
.n8n-manager .nav-tabs > li {
    float: none;
    margin: 0;
}
.n8n-manager .nav-tabs > li > a { 
    padding: 12px 20px; 
    font-size: 14px;
    font-weight: 500;
    color: inherit;
    opacity: 0.7;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    display: block;
    background: transparent;
}
.n8n-manager .nav-tabs > li > a:hover {
    color: var(--primary);
    background: transparent;
    border-color: transparent;
}
.n8n-manager .nav-tabs > li.active > a,
.n8n-manager .nav-tabs > li.active > a:hover,
.n8n-manager .nav-tabs > li.active > a:focus {
    color: var(--primary);
    background: transparent;
    border: none !important;
    border-bottom: none !important;
    opacity: 1;
}
.n8n-manager .nav-tabs > li > a.active,
.n8n-manager .nav-tabs > li > a.active:hover,
.n8n-manager .nav-tabs > li > a.active:focus,
.n8n-manager .nav-tabs > li > a[aria-selected="true"],
.n8n-manager .nav-tabs > li > a[aria-selected="true"]:hover,
.n8n-manager .nav-tabs > li > a[aria-selected="true"]:focus {
    color: var(--primary);
    background: transparent;
    border: none !important;
    border-bottom: none !important;
}

/* Tab Content */
.n8n-manager .tab-content { 
    padding: 10px 5px;
    text-align: left;
}
.n8n-manager .tab-pane {
    display: none;
}
.n8n-manager .tab-pane.active {
    display: block;
}
.n8n-manager h5 {
    font-size: 18px;
    font-weight: 600;
    color: inherit;
    margin-bottom: 15px;
    text-align: left;
}
.n8n-manager h5 i {
    margin-right: 8px;
    color: var(--primary);
}

/* Info Row */
.n8n-manager .info-row { 
    display: flex; 
    gap: 20px; 
    flex-wrap: wrap; 
    margin-top: 5px;
    margin-bottom: 15px; 
    font-size: 15px; 
    align-items: center;
}
.n8n-manager .info-row > span { 
    background: var(--hover-overlay); 
    padding: 8px 16px; 
    border-radius: 5px;
    color: inherit;
    display: inline-flex;
    align-items: center;
    height: 38px;
    box-sizing: border-box;
    border: 1px solid var(--border-subtle);
}

/* Buttons */
.n8n-manager .btn-row { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 10px; 
    margin-bottom: 20px; 
}
.n8n-manager .btn {
    border-radius: 5px;
    font-weight: 500;
    border: 1px solid transparent;
    transition: background-color 0.2s, border-color 0.2s, color 0.2s, box-shadow 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.n8n-manager .btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(14,80,119,0.18);
}

.n8n-manager .btn-row .btn { 
    padding: 0 18px; 
    height: 42px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.n8n-manager .btn.btn-sm {
    padding: 13px 21px;
    font-size: 13px;
}
.n8n-manager .btn.btn-default {
    background: var(--hover-overlay);
    border-color: var(--border-subtle);
    color: inherit;
}
.n8n-manager .btn.btn-default:hover,
.n8n-manager .btn.btn-default:focus {
    background: #ffffffff;
    border-color: #888686ff;
}
.n8n-manager .btn.btn-primary {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}
.n8n-manager .btn.btn-primary:hover,
.n8n-manager .btn.btn-primary:focus {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
    color: #fff;
}
.n8n-manager .btn.btn-success {
    background: #46A546;
    border-color: #46A546;
    color: #fff;
}
.n8n-manager .btn.btn-success:hover,
.n8n-manager .btn.btn-success:focus {
    background: #46A546;
    border-color: #46A546;
    color: #fff;
}
.n8n-manager .btn.btn-warning {
    background: #f59e0b;
    border-color: #f59e0b;
    color: #111827;
}
.n8n-manager .btn.btn-warning:hover,
.n8n-manager .btn.btn-warning:focus {
    background: #d97706;
    border-color: #d97706;
    color: #111827;
}
.n8n-manager .btn.btn-danger {
    background: #dc2626;
    border-color: #dc2626;
    color: #fff;
}
.n8n-manager .btn.btn-danger:hover,
.n8n-manager .btn.btn-danger:focus {
    background: #b91c1c;
    border-color: #b91c1c;
    color: #fff;
}
.n8n-manager .btn.btn-info {
    background: #0891b2;
    border-color: #0891b2;
    color: #fff;
}
.n8n-manager .btn.btn-info:hover,
.n8n-manager .btn.btn-info:focus {
    background: #0e7490;
    border-color: #0e7490;
    color: #fff;
}
.n8n-manager .btn.btn-secondary {
    background: #64748b;
    border-color: #64748b;
    color: #fff;
}
.n8n-manager .btn.btn-secondary:hover,
.n8n-manager .btn.btn-secondary:focus {
    background: #475569;
    border-color: #475569;
    color: #fff;
}

/* Status Badge */
/* Status Badge - Reset to inherit parent style */
.n8n-manager .status-badge { 
    padding: 0; 
    border-radius: 0; 
    font-size: 14px; 
    font-weight: 500; 
    background: transparent !important; /* Override status colors for background */
    color: inherit;
}
.n8n-manager .status-running { color: #10b981; font-weight: 600; }
.n8n-manager .status-stopped { color: #ef4444; font-weight: 600; }
.n8n-manager .status-restarting { color: #f59e0b; font-weight: 600; }
.n8n-manager .status-unknown { color: inherit; opacity: 0.6; }

/* Tables */
.n8n-manager .resource-table { width: 100%; font-size: 14px; }
.n8n-manager .resource-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
.n8n-manager .resource-table td:first-child { font-weight: 600; width: 120px; color: var(--text-secondary); }

/* Real-time Resource Grid */
.n8n-manager .resource-grid { 
    display: grid; 
    grid-template-columns: repeat(4, 1fr); 
    gap: 15px; 
}
.n8n-manager .resource-card { 
    background: var(--hover-overlay);
    border-radius: 8px; 
    padding: 18px 12px; 
    text-align: center;
    border: 1px solid var(--border-subtle);
    box-shadow: 0 4px 15px var(--shadow-subtle);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 180px;
}
.n8n-manager .resource-card:hover { 
    transform: translateY(0px);
}
.n8n-manager .resource-icon { 
    width: 50px; height: 50px; 
    border-radius: 8px; 
    display: flex; align-items: center; justify-content: center; 
    margin: 0 auto 12px; 
    font-size: 22px;
    background: var(--primary);
    color: white;
}
.n8n-manager .resource-value { 
    font-size: 20px; 
    font-weight: 700; 
    color: inherit;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    line-height: 1.2;
    word-break: break-word;
}
.n8n-manager .resource-value.updating { animation: pulse-value 0.5s ease; }
@keyframes pulse-value { 
    0%, 100% { transform: scale(1); } 
    50% { transform: scale(1.05); color: var(--primary); } 
} 
.n8n-manager .resource-label { 
    font-size: 11px; 
    color: inherit;
    opacity: 0.7;
    text-transform: uppercase; 
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    font-weight: 600;
}
.n8n-manager .resource-bar { 
    height: 10px; 
    background: var(--hover-overlay); 
    border-radius: 5px; 
    overflow: hidden;
}
.n8n-manager .resource-bar-fill { 
    height: 100%; 
    border-radius: 5px; 
    background: var(--primary);
    transition: width 0.5s ease;
}
.n8n-manager .pulse-dot { 
    width: 8px; height: 8px; 
    background: #10b981; 
    border-radius: 50%; 
    animation: pulse-dot 1.5s infinite;
}
@keyframes pulse-dot { 
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16,185,129,0.4); } 
    50% { opacity: 0.8; box-shadow: 0 0 0 8px rgba(16,185,129,0); } 
}

.n8n-manager .backup-table { width: 100%; font-size: 13px; margin-top: 15px; border-collapse: collapse; }
.n8n-manager .backup-table th, .n8n-manager .backup-table td { padding: 12px 15px; border: 1px solid var(--border-subtle); text-align: left; color: inherit; }
.n8n-manager .backup-table th { background: var(--hover-overlay); font-weight: 600; color: inherit; }
.n8n-manager .backup-table tr:hover { background: var(--hover-overlay); }
.n8n-manager .backup-table .btn {
    padding: 6px 12px;
    font-size: 13px;
    height: 32px;
    min-width: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 2px;
}

/* Info Cards */
.n8n-manager .info-card { 
    background: var(--bg-transparent); 
    border: 1px solid var(--border-subtle);
    padding: 20px; 
    border-radius: 8px; 
    margin-bottom: 20px;
    box-shadow: 0 1px 3px var(--shadow-subtle);
    text-align: left;
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
}
.n8n-manager .info-card h6 { 
    margin: 0 0 15px 0; 
    color: inherit;
    font-size: 16px;
    font-weight: 600;
    text-align: left;
}
.n8n-manager .info-card h6 i {
    margin-right: 8px;
    color: var(--primary);
}
.n8n-manager .info-card table { width: 100%; }
.n8n-manager .info-card table { border-collapse: collapse; }
.n8n-manager .info-card table td { padding: 8px 12px; font-size: 14px; text-align: left; word-break: break-word; color: inherit; }
.n8n-manager .info-card p.text-muted { color: inherit; opacity: 0.6; margin-bottom: 15px; text-align: left; font-size: 13px; }

/* Nested Cards (Danger Zone, etc.) */
.n8n-manager .info-card .info-card {
    margin-bottom: 15px;
    padding: 15px;
}
.n8n-manager .info-card .info-card:last-child {
    margin-bottom: 0;
}

/* Form Elements */
.n8n-manager .form-control {
    padding: 12px 16px;
    font-size: 14px;
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    transition: border-color 0.2s, box-shadow 0.2s;
    height: auto;
    line-height: 1.5;
    background: var(--hover-overlay);
    color: inherit;
}
.n8n-manager .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255,109,90,0.1);
    outline: none;
}
.n8n-manager select.form-control {
    height: 46px;
    padding-right: 35px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
}

/* Inline Form Group */
.n8n-manager .inline-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
}
.n8n-manager .inline-form select,
.n8n-manager .inline-form input {
    padding: 12px 16px;
    font-size: 14px;
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    height: 46px;
    background: var(--hover-overlay);
    color: inherit;
}
.n8n-manager .inline-form .btn {
    height: 46px;
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border-radius: 8px; /* Match input radius */
}
.n8n-manager .inline-form select {
    min-width: 300px;
    padding-right: 35px;
}

/* Domain Input */
.n8n-manager .domain-input-group { 
    display: flex; 
    gap: 12px; 
    flex-wrap: wrap; 
    margin-top: 15px; 
}
.n8n-manager .domain-input-group input, 
.n8n-manager .domain-input-group select { 
    padding: 10px 14px; 
    border: 1px solid var(--border-subtle); 
    border-radius: 8px; 
    font-size: 14px; 
    background: var(--hover-overlay);
    color: inherit;
}
.n8n-manager .domain-input-group input { flex: 1; min-width: 200px; }

/* Container Logs Box - Dark Terminal with Colors */
.n8n-manager .logs-box { 
    background: rgba(13, 17, 23, 0.85); 
    color: #c9d1d9; 
    padding: 16px; 
    border-radius: 10px; 
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
    font-size: 13px; 
    height: 350px; 
    overflow: auto; 
    margin: 0; 
    text-align: left;
    line-height: 1.6;
    border: 1px solid rgba(255,255,255,0.1);
    white-space: pre-wrap;
    word-wrap: break-word;
    backdrop-filter: blur(5px);
}
/* Container Log Colors */
/* Container Log Colors - REMOVED */

/* Service Logs Box */
.n8n-manager .service-logs-box { 
    background: var(--hover-overlay); 
    color: inherit; 
    padding: 16px; 
    border-radius: 10px; 
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
    font-size: 13px; 
    height: 300px; 
    overflow: auto; 
    margin: 0; 
    text-align: left;
    line-height: 1.8;
    border: 1px solid var(--border-subtle);
    white-space: pre-wrap;
    word-wrap: break-word;
}
.n8n-manager .service-logs-box .log-action { color: #db2777; font-weight: 600; }
.n8n-manager .service-logs-box .log-user { color: #7c3aed; }
.n8n-manager .service-logs-box .log-ip { color: #059669; }
.n8n-manager .service-logs-box .log-time { color: #6b7280; font-size: 12px; }
.n8n-manager .service-logs-box .log-details { color: #374151; }

/* Confirmation Modal */
.n8n-manager .confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--modal-overlay);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.n8n-manager .confirm-modal.active { opacity: 1; visibility: visible; }
.n8n-manager .confirm-modal-content {
    background: var(--hover-overlay);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    padding: 30px;
    border: 1px solid var(--border-medium);
    border-radius: 16px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    transform: scale(0.9);
    transition: transform 0.3s;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    color: inherit;
}
.n8n-manager .confirm-modal.active .confirm-modal-content { transform: scale(1); }
.n8n-manager .confirm-modal h4 { margin: 0 0 15px; font-size: 18px; color: inherit; }
.n8n-manager .confirm-modal p { margin: 0 0 25px; color: inherit; opacity: 0.8; font-size: 14px; line-height: 1.6; }
.n8n-manager .confirm-modal .btn-group { display: flex; gap: 10px; justify-content: center; }
.n8n-manager .confirm-modal .btn { padding: 12px 24px; border-radius: 8px; font-weight: 500; }

/* Success Animation */
.n8n-manager .success-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10001;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.n8n-manager .success-overlay.active { opacity: 1; visibility: visible; }
.n8n-manager .success-checkmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    animation: successPop 0.5s ease-out;
}
.n8n-manager .success-checkmark i { color: #fff; font-size: 40px; }
.n8n-manager .success-message { font-size: 18px; color: #fff; font-weight: 600; margin-bottom: 5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
.n8n-manager .success-detail { font-size: 14px; color: rgba(255,255,255,0.8); }
@keyframes successPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Button Loading State */
.n8n-manager .btn.loading {
    pointer-events: none;
    opacity: 0.8;
}
.n8n-manager .btn.loading i:first-child {
    animation: spin 1s linear infinite;
}

/* Alerts */
.n8n-manager .alert {
    padding: 14px 18px;
    border-radius: 5px;
    font-size: 13px;
}

/* Status Light Indicator */
.n8n-manager .status-light {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-left: 6px;
    vertical-align: middle;
}
.n8n-manager .status-light.green { background: #22c55e; animation: pulse 2s infinite; }
.n8n-manager .status-light.yellow { background: #eab308; animation: pulse 1s infinite; }
.n8n-manager .status-light.red { background: #ef4444; }
.n8n-manager .status-light.gray { background: #9ca3af; }
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Loading Overlay */
.n8n-manager .loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(128,128,128,0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
.n8n-manager .loading-overlay.active { display: flex; }
.n8n-manager .loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--border-color);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.n8n-manager .loading-text {
    margin-top: 20px;
    font-size: 16px;
    color: inherit;
    font-weight: 500;
}

/* Toast Notification */
.n8n-manager .toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
}
.n8n-manager .toast {
    position: relative;
    overflow: hidden;
    background: var(--hover-overlay);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid var(--border-medium);
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
    min-width: 300px;
    color: inherit;
}
.n8n-manager .toast.success { border-left: 4px solid #22c55e; }
.n8n-manager .toast.error { border-left: 4px solid #ef4444; }
.n8n-manager .toast.warning { border-left: 4px solid #eab308; }
.n8n-manager .toast.info { border-left: 4px solid #3b82f6; }
.n8n-manager .toast i { font-size: 20px; }
.n8n-manager .toast.success i { color: #22c55e; }
.n8n-manager .toast.error i { color: #ef4444; }
.n8n-manager .toast.warning i { color: #eab308; }
.n8n-manager .toast.info i { color: #3b82f6; }
.n8n-manager .toast-message { flex: 1; font-size: 14px; color: inherit; }
.n8n-manager .toast-close { cursor: pointer; color: inherit; opacity: 0.5; font-size: 18px; }
.n8n-manager .toast-close:hover { opacity: 1; }
.n8n-manager .toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: currentColor;
    opacity: 0.3;
    animation: toastCountdown 5s linear forwards;
}
.n8n-manager .toast.success .toast-progress { background: #22c55e; }
.n8n-manager .toast.error .toast-progress { background: #ef4444; }
.n8n-manager .toast.warning .toast-progress { background: #eab308; }
.n8n-manager .toast.info .toast-progress { background: #3b82f6; }
@keyframes toastCountdown {
    from { width: 100%; }
    to { width: 0%; }
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

/* Refresh Button Fix */
.n8n-manager .refresh-btn {
    background: var(--hover-overlay);
    border: 1px solid var(--border-subtle);
    color: inherit;
    cursor: pointer;
    padding: 8px;
    margin-left: 5px;
    border-radius: 5px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.n8n-manager .refresh-btn:hover { color: var(--primary); background: var(--hover-overlay); border-color: var(--primary); }
.n8n-manager .refresh-btn.spinning i { animation: spin 1s linear infinite; }

/* Responsive - Mobile First */
@media (max-width: 768px) {
    /* Header */
    .n8n-manager .header-bar { 
        padding: 12px 10px; 
        margin: 0 0 15px 0;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .n8n-manager .header-bar h4 { font-size: 18px; }
    .n8n-manager .header-bar a { font-size: 12px; padding: 6px 12px; }
    
    /* Tabs - Scrollable */
    .n8n-manager .nav-tabs { 
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }
    .n8n-manager .nav-tabs > li > a { 
        padding: 10px 12px; 
        font-size: 12px;
        white-space: nowrap;
    }
    
    /* Tab Content */
    .n8n-manager .tab-content { padding: 5px 0; }
    .n8n-manager h5 { font-size: 16px; margin-bottom: 12px; }
    
    /* Info Row */
    .n8n-manager .info-row { 
        flex-direction: column; 
        gap: 8px;
    }
    .n8n-manager .info-row > span { 
        width: 100%; 
        justify-content: center;
        height: auto;
        padding: 10px 12px;
    }
    
    /* Buttons - Full Width */
    .n8n-manager .btn-row { 
        flex-direction: column;
        gap: 8px;
    }
    .n8n-manager .btn-row .btn { 
        width: 100%; 
        flex: none;
        padding: 12px 16px;
        font-size: 14px;
    }
    
    /* Info Cards - Full Width, More Padding */
    .n8n-manager .info-card { 
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 10px;
    }
    .n8n-manager .info-card h6 { font-size: 15px; margin-bottom: 10px; }
    .n8n-manager .info-card table { font-size: 13px; }
    .n8n-manager .info-card table td { padding: 6px 8px; }
    
    /* Forms - Full Width Inputs & Buttons */
    .n8n-manager .form-control,
    .n8n-manager select.form-control,
    .n8n-manager input.form-control { 
        width: 100% !important; 
        max-width: 100% !important;
        box-sizing: border-box;
    }
    .n8n-manager .inline-form { 
        flex-direction: column; 
        align-items: stretch;
        gap: 10px;
    }
    .n8n-manager .inline-form select,
    .n8n-manager .inline-form input { 
        width: 100% !important;
        min-width: auto !important;
        max-width: 100% !important;
    }
    .n8n-manager .inline-form .btn {
        width: 100%;
    }
    
    /* Domain Input */
    .n8n-manager .domain-input-group { 
        flex-direction: column;
        gap: 10px;
    }
    .n8n-manager .domain-input-group input, 
    .n8n-manager .domain-input-group select,
    .n8n-manager .domain-input-group .btn { 
        width: 100% !important;
        min-width: auto !important;
    }
    
    /* Tables */
    .n8n-manager .resource-table td { padding: 8px 10px; font-size: 13px; }
    .n8n-manager .backup-table { font-size: 12px; }
    .n8n-manager .backup-table th, 
    .n8n-manager .backup-table td { padding: 8px 6px; }
    
    /* Logs */
    .n8n-manager .logs-box { 
        height: 250px; 
        font-size: 11px;
        padding: 12px;
    }
    
    /* Alerts */
    .n8n-manager .alert { padding: 12px; font-size: 12px; }
    
    /* Toast */
    .n8n-manager .toast-container { 
        left: 10px; 
        right: 10px; 
        top: 10px;
    }
    .n8n-manager .toast { 
        min-width: auto; 
        padding: 12px 16px;
        font-size: 13px;
    }
    
    /* Loading Overlay Text */
    .n8n-manager .loading-text { font-size: 14px; }
    
    /* Resource Grid Responsive */
    .n8n-manager .resource-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .n8n-manager .resource-card { padding: 15px 10px; }
    .n8n-manager .resource-value { font-size: 22px; }
    .n8n-manager .resource-icon { width: 40px; height: 40px; font-size: 18px; }
}
</style>

<div class="n8n-manager">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text" id="loadingText">Processing...</div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-modal-content">
            <h4 id="confirmTitle">Confirm Action</h4>
            <p id="confirmMessage">Are you sure you want to proceed?</p>
            <div class="btn-group">
                <button class="btn btn-default" onclick="hideConfirm()">Cancel</button>
                <button class="btn btn-primary" id="confirmYesBtn" onclick="confirmAction()">Yes, Proceed</button>
            </div>
        </div>
    </div>
    
    <!-- Success Overlay -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-checkmark">
            <i class="fas fa-check"></i>
        </div>
        <div class="success-message" id="successMessage">Success!</div>
        <div class="success-detail" id="successDetail"></div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <div class="header-bar">
        <h4><i class="fas fa-project-diagram"></i> N8N Manager</h4>
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{if substr($domain, 0, 4) == 'http'}{$domain}{else}https://{$domain}{/if}" target="_blank"><i class="fas fa-external-link-alt"></i> {$domain|replace:'https://':''|replace:'http://':''}</a>
        </div>
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#n8n-overview" data-toggle="tab">Overview</a></li>
        <li><a href="#n8n-resources" data-toggle="tab">Resources</a></li>
        {* <li><a href="#n8n-domain" data-toggle="tab">Domain</a></li> *}
        <li><a href="#n8n-workflows" data-toggle="tab">Workflows</a></li>
        <li><a href="#n8n-logs" data-toggle="tab">Logs</a></li>
        <li><a href="#n8n-security" data-toggle="tab">Security</a></li>
        <li><a href="#n8n-settings" data-toggle="tab">Settings</a></li>
    </ul>

    <div class="tab-content">
        <!-- Overview -->
        <div class="tab-pane active" id="n8n-overview">
            <div class="info-row">
                <span><strong>Service ID:</strong> #{$serviceid}</span>
                <span style="display: flex; align-items: center; gap: 8px;">
                    <strong>Status:</strong>
                    <span id="status-badge" class="status-badge status-unknown">Loading...</span>
                    <span class="status-light" id="status-light" style="margin-left: 0;"></span>
                </span>
            </div>
            <div class="btn-row" id="action-buttons">
                <button class="btn btn-success btn-sm" id="btnToggle" disabled><i class="fas fa-spinner fa-spin"></i> Loading...</button>
                <button class="btn btn-primary btn-sm" onclick="runControl('restart')"><i class="fas fa-sync"></i> Restart</button>
                <button class="btn btn-warning btn-sm" onclick="resetPassword()"><i class="fas fa-key"></i> Reset Password</button>
            </div>
            
            <div class="info-card">
                <h6><i class="fas fa-info-circle" style="color: #22c55e;"></i> Service Information</h6>
                <table>
                    <tr><td><strong>Domain:</strong></td><td id="service-domain">{$domain|replace:'https://':''|replace:'http://':''}</td></tr>
                    <tr><td><strong>Version:</strong></td><td id="service-version">{$serviceDetails.version|default:'latest'}</td></tr>
                    <tr><td><strong>CPU Limit:</strong></td><td>{$cpuLimit}</td></tr>
                    <tr><td><strong>Memory:</strong></td><td>{$memoryLimit}</td></tr>
                    <tr><td><strong>Disk Limit:</strong></td><td>{$diskLimit|default:'5G'}</td></tr>
                </table>
            </div>
        </div>

        <!-- Resources -->
        <div class="tab-pane" id="n8n-resources">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h5 style="margin:0;"><i class="fas fa-chart-bar"></i> Real-time Resource Monitor</h5>
                <div id="live-indicator" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #10b981;">
                    <span class="pulse-dot"></span> <span id="update-timer">Updating...</span>
                </div>
            </div>
            
            <!-- Real-time Stats Grid -->
            <div class="resource-grid">
                <!-- CPU Gauge -->
                <div class="resource-card">
                    <div class="resource-icon"><i class="fas fa-microchip"></i></div>
                    <div class="resource-value" id="cpu-value">--%</div>
                    <div class="resource-label">CPU Usage</div>
                    <div class="resource-bar">
                        <div class="resource-bar-fill" id="cpu-bar" style="width: 0%"></div>
                    </div>
                </div>
                
                <!-- Memory Gauge -->
                <div class="resource-card">
                    <div class="resource-icon"><i class="fas fa-memory"></i></div>
                    <div class="resource-value" id="memory-value">--</div>
                    <div class="resource-label">Memory</div>
                    <div class="resource-bar">
                        <div class="resource-bar-fill" id="memory-bar" style="width: 0%"></div>
                    </div>
                </div>
                
                <!-- Network I/O -->
                <div class="resource-card">
                    <div class="resource-icon"><i class="fas fa-network-wired"></i></div>
                    <div class="resource-value" id="network-value">--</div>
                    <div class="resource-label">Network I/O</div>
                    <div class="resource-bar">
                        <div class="resource-bar-fill" id="network-bar" style="width: 0%"></div>
                    </div>
                </div>
                
                <!-- Disk I/O -->
                <div class="resource-card">
                    <div class="resource-icon"><i class="fas fa-hdd"></i></div>
                    <div class="resource-value" id="disk-value">--</div>
                    <div class="resource-label">Disk I/O</div>
                    <div class="resource-bar">
                        <div class="resource-bar-fill" id="disk-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Storage Usage -->
            <div class="info-card" style="margin-top: 20px;">
                <h6><i class="fas fa-database"></i> Storage Usage</h6>
                <div id="storage-stats">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading storage info...</p>
                </div>
            </div>
        </div>

        {* 
        <!-- Domain -->
        <div class="tab-pane" id="n8n-domain">
            <h5><i class="fas fa-globe"></i> Domain Management</h5>
            <p class="text-muted" style="font-size:12px;">Change your N8N instance domain. SSL is automatically configured.</p>
            
            <div class="info-card">
                <h6>Current Domain</h6>
                <p style="margin:0;"><code id="current-domain">{$domain|replace:'https://':''|replace:'http://':''}</code></p>
            </div>
            
            <div class="info-card">
                <h6>Change Domain</h6>
                <p class="text-muted" style="font-size:12px;">Enter your own domain{if $has_subdomain} or use a subdomain under {$subdomain_server} (free){/if}.</p>
                
                <div class="domain-input-group" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:15px;">
                    <select id="domain-type" onchange="toggleDomainInput()" style="padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="custom">Custom Domain</option>
                        {if $has_subdomain}<option value="subdomain">Free Subdomain (.{$subdomain_server})</option>{/if}
                    </select>
                    <input type="text" id="new-domain" placeholder="example.com" style="flex:1; min-width:200px; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                    <button class="btn btn-primary" onclick="changeDomain()"><i class="fas fa-save"></i> Update Domain</button>
                </div>
                
                <div id="dns-info" class="alert alert-info" style="margin-top:15px; font-size:12px;">
                    <strong>DNS Configuration:</strong><br>
                    Point your domain's A record to: <code id="server-ip">{$hostname}</code><br>
                    <small>{if $has_subdomain}For .{$subdomain_server} subdomains, no DNS configuration needed.{/if}</small>
                </div>
            </div>
        </div>
        *}

        <!-- Workflows -->
        <div class="tab-pane" id="n8n-workflows">
            <!-- Export Section -->
            <div class="info-card">
                <h6><i class="fas fa-download"></i> Backup Workflow</h6>
                <p class="text-muted" style="font-size:13px;">Create a JSON backup of all your workflows.</p>
                <div class="btn-row" style="align-items: center;">
                    <label style="margin: 0; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" id="includeCredentials" style="margin-right: 5px;">
                        Include Credentials (API Keys, OAuth tokens, etc.)
                    </label>
                </div>
                <div class="btn-row" style="margin-top: 10px; align-items: center;">
                    <button class="btn btn-success" onclick="exportWorkflows()"><i class="fas fa-download"></i> Backup Workflow</button>
                    <button class="refresh-btn" onclick="loadWorkflowExports()" title="Refresh List"><i class="fas fa-sync"></i></button>
                </div>
                <div class="alert alert-warning" style="font-size:12px; margin-top:10px;" id="credentials-warning" style="display:none;">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> Credentials export contains sensitive data. Keep the backed up file secure!
                </div>
                <div id="workflow-exports-list" style="margin-top:15px;">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading exports...</p>
                </div>
            </div>
            
            <!-- Import Section -->
            <div class="info-card">
                <h6><i class="fas fa-upload"></i> Import / Restore Workflows</h6>
                <p class="text-muted" style="font-size:13px;">Upload a JSON file backed up from N8N to restore or migrate workflows.</p>
                <div id="import-workflows-loading">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
                </div>
                <div id="import-workflows-content" style="display:none;">
                    <div class="inline-form" style="margin-bottom:15px;">
                        <input type="file" id="workflow-import-file" accept=".json" class="form-control" style="max-width:300px;">
                        <button class="btn btn-primary" onclick="importWorkflows()"><i class="fas fa-upload"></i> Import Workflows</button>
                    </div>
                    <div class="alert alert-info" style="font-size:12px; margin-top:10px;">
                        <strong><i class="fas fa-info-circle"></i> Tip:</strong> You can restore from a previously backed up file by clicking "Download" in the export list above, then importing it here.
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="tab-pane" id="n8n-logs">
            <h5><i class="fas fa-terminal"></i> Container Logs</h5>
            <p class="text-muted" style="font-size:13px;">View real-time logs from your N8N container for debugging and monitoring.</p>
            
            <div class="btn-row" style="margin-bottom:15px; align-items: center;">
                <button class="btn btn-primary" onclick="getLogs()"><i class="fas fa-sync"></i> Refresh Logs</button>
                <button class="refresh-btn" onclick="clearLogs()" title="Clear Display"><i class="fas fa-trash"></i></button>
            </div>
            <pre class="logs-box" id="logs-output">Click "Refresh Logs" to load container logs...</pre>
            
            <!-- Service Logs (Activity Log) -->
            <h5 style="margin-top:30px;"><i class="fas fa-history"></i> Service Logs</h5>
            <p class="text-muted" style="font-size:13px;">Recent actions performed on this service by users and the system.</p>
            
            <div class="btn-row" style="margin-bottom:15px; align-items: center;">
                <button class="btn btn-primary" onclick="loadActivityLog()"><i class="fas fa-sync"></i> Refresh Logs</button>
                <button class="refresh-btn" onclick="clearServiceLogs()" title="Clear Display"><i class="fas fa-trash"></i></button>
            </div>
            <div id="service-logs-output" class="service-logs-box">Click "Refresh Logs" to load service activity logs...</div>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane" id="n8n-security">            
            <div class="info-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h6 style="margin: 0;"><i class="fas fa-lock"></i> SSL Certificate Status</h6>
                    <button class="refresh-btn" id="refresh-ssl-btn" onclick="loadSSLStatus()" title="Refresh SSL Status">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div id="ssl-status">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Checking SSL status...</p>
                </div>
            </div>
            
            <!-- IP Whitelist -->
            <div class="info-card">
                <h6><i class="fas fa-filter"></i> IP Whitelist / Access Control</h6>
                <p class="text-muted" style="font-size:13px;">Restrict access to your N8N instance from specific IP addresses only. Leave empty to allow all.</p>
                <div id="ip-whitelist-loading">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading whitelist...</p>
                </div>
                <div id="ip-whitelist-content" style="display:none;">
                    <div style="margin-bottom:15px;">
                        <label style="font-size:14px; cursor: pointer;">
                            <input type="checkbox" id="ip-whitelist-enabled" style="margin-right:8px;"> Enable IP Whitelist
                        </label>
                    </div>
                    <textarea id="ip-whitelist-ips" class="form-control" rows="4" placeholder="Enter IP addresses, one per line (e.g., 192.168.1.1 or CIDR 192.168.1.0/24)" style="margin-bottom:15px;"></textarea>
                    <button class="btn btn-success" onclick="saveIPWhitelist()"><i class="fas fa-save"></i> Save Whitelist</button>
                    <p class="text-muted" style="font-size:12px; margin-top:10px;"><i class="fas fa-info-circle"></i> Your current IP: <code id="current-user-ip">Loading...</code></p>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div class="tab-pane" id="n8n-settings">            
            

            
            <!-- Service Repair -->
            <div class="info-card" style="border-left: 3px solid #0891b2;">
                <h6><i class="fas fa-wrench"></i> Service Repair</h6>
                <p class="text-muted" style="font-size:13px;">If some features are not working, you can regenerate the service configuration file.</p>
                <button class="btn btn-info btn-sm" onclick="generateComposeFile()"><i class="fas fa-sync-alt"></i> Regenerate Config</button>
                <span id="compose-status" style="margin-left: 10px; font-size: 12px;"></span>
            </div>
            
            <!-- Reinstall Options -->
            <div class="info-card" style="border-left: 3px solid #dc3545;">
                <h6><i class="fas fa-exclamation-triangle text-danger"></i> Danger Zone</h6>
                <p class="text-muted" style="font-size:13px;">Choose how you want to reinstall your N8N instance:</p>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                    <!-- Soft Reset -->
                    <div style="flex: 1; min-width: 250px; padding: 15px; background: rgba(245, 124, 0, 0.05); border-radius: 8px; border: 1px solid rgba(245, 124, 0, 0.2);">
                        <h6 style="margin: 0 0 8px 0; color: #f57c00;"><i class="fas fa-user-times"></i> Soft Reset</h6>
                        <p style="font-size: 12px; color: inherit; opacity: 0.8; margin-bottom: 12px;">Delete all <strong>user accounts</strong> but <strong>keep workflows & credentials</strong>. Create a new account after reset.</p>
                        <button class="btn btn-warning btn-sm" onclick="reinstallSoft()"><i class="fas fa-user-times"></i> Soft Reset</button>
                    </div>
                    
                    <!-- Full Reset -->
                    <div style="flex: 1; min-width: 250px; padding: 15px; background: rgba(198, 40, 40, 0.05); border-radius: 8px; border: 1px solid rgba(198, 40, 40, 0.2);">
                        <h6 style="margin: 0 0 8px 0; color: #c62828;"><i class="fas fa-trash-alt"></i> Full Reset</h6>
                        <p style="font-size: 12px; color: inherit; opacity: 0.8; margin-bottom: 12px;"><strong>Delete all data</strong> and create a completely fresh N8N instance.</p>
                        <button class="btn btn-danger btn-sm" onclick="reinstallFull()"><i class="fas fa-trash-alt"></i> Full Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var serviceId = '{$serviceid}';
var apiUrl = '{$custom_api_url}';
var hasSubdomain = {if $has_subdomain}true{else}false{/if};
var subdomainServer = '{if $has_subdomain}{$subdomain_server}{/if}';
{literal}
var containerStatus = 'unknown';
var resourceRefreshInterval = null;
var pendingConfirmCallback = null;

// ========== GLOBAL DATA CACHE (10 minute expiry) ==========
var CACHE_EXPIRY_MS = 10 * 60 * 1000; // 10 minutes in milliseconds
var dataCache = {
    loaded: false,
    loadedAt: null,
    status: null,
    resourceStats: null,
    sslStatus: null,
    sslLoadedAt: null,
    ipWhitelist: null,
    ipWhitelistLoadedAt: null,
    versions: null,
    workflowExports: null,
    workflowExportsLoadedAt: null,
    logs: null // Logs are not cached on initial load (too large)
};

// Check if cache is still valid (within 10 minutes)
function isCacheValid(loadedAt) {
    if (!loadedAt) return false;
    return (Date.now() - loadedAt) < CACHE_EXPIRY_MS;
}

// Load all data at once
function loadAllData() {
    fetch(apiUrl + '?action=getAllData&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                dataCache.loaded = true;
                dataCache.loadedAt = Date.now();
                // Handle both camelCase and lowercase key names from backend
                dataCache.status = d.status;
                dataCache.resourceStats = d.resourcestats || d.resourceStats;
                dataCache.sslStatus = d.sslStatus || d.sslstatus;
                if (dataCache.sslStatus) dataCache.sslLoadedAt = Date.now();
                dataCache.ipWhitelist = d.ipWhitelist || d.ipwhitelist;
                if (dataCache.ipWhitelist) dataCache.ipWhitelistLoadedAt = Date.now();
                dataCache.versions = d.versions;
                dataCache.workflowExports = d.workflowExports || d.workflowexports;
                if (dataCache.workflowExports) dataCache.workflowExportsLoadedAt = Date.now();
                
                // Apply data to UI
                applyStatusData(d.status);
                applyVersionsData(d.versions);
                
                // Apply resource stats immediately if we have them
                if (dataCache.resourceStats) {
                    renderResourceStats(dataCache.resourceStats);
                }
                
                // If there's a reason to believe things changed, refresh exports
                if (dataCache.workflowExports) renderWorkflowExports(dataCache.workflowExports);
            }
        })
        .catch(function(e) {
            console.error('loadAllData error:', e);
        });
}

// Apply cached status data to UI
function applyStatusData(data) {
    if (!data) return;
    containerStatus = data.status || 'unknown';
    updateStatusUI(data.status);
    updateContainerIndicator('n8n', data.n8n_status || data.status);
    updateContainerIndicator('postgres', data.postgres_status || 'unknown');
}

// function applyVersionsData removed


// Load versions from API (fallback if not in getAllData)
// function loadVersions removed


// Apply cached resource stats to UI (or fetch if not cached)
function applyResourceStats() {
    if (dataCache.loaded && dataCache.resourceStats) {
        renderResourceStats(dataCache.resourceStats);
    } else {
        loadResourceStats();
    }
}

// Apply cached workflow exports (or fetch if not cached)
function applyWorkflowExports() {
    if (isCacheValid(dataCache.workflowExportsLoadedAt) && dataCache.workflowExports) {
        renderWorkflowExports(dataCache.workflowExports);
    } else {
        loadWorkflowExports();
    }
}

// Apply cached security data (or fetch if not cached)
function applySecurityData() {
    // SSL Status - use cache if valid
    if (isCacheValid(dataCache.sslLoadedAt) && dataCache.sslStatus) {
        renderSSLStatusFromCache(dataCache.sslStatus);
    } else {
        loadSSLStatus();
    }
    
    // IP Whitelist - use cache if valid
    if (isCacheValid(dataCache.ipWhitelistLoadedAt) && dataCache.ipWhitelist) {
        renderIPWhitelist(dataCache.ipWhitelist);
    } else {
        loadIPWhitelist();
    }
}

function applySettingsData() {
    // Settings tab doesn't need SSL/IP data, just ensure loading states are hidden
    var loadingEl = document.getElementById('ip-whitelist-loading');
    var contentEl = document.getElementById('ip-whitelist-content');
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'block';
}

// Render SSL from cache without showing loading spinner
function renderSSLStatusFromCache(data) {
    var container = document.getElementById('ssl-status');
    if (!container) return;
    
    if (data.success) {
        var statusClass = 'text-success';
        var statusIcon = 'fa-check-circle';
        var statusText = 'Valid';
        
        if (data.ssl_status === 'expired') {
            statusClass = 'text-danger';
            statusIcon = 'fa-times-circle';
            statusText = 'Expired';
        } else if (data.ssl_status === 'expiring_soon') {
            statusClass = 'text-warning';
            statusIcon = 'fa-exclamation-circle';
            statusText = 'Expiring Soon';
        } else if (data.ssl_status === 'pending') {
            statusClass = 'text-muted';
            statusIcon = 'fa-clock';
            statusText = 'Pending';
        }
        
        var html = '<table class="resource-table">' +
            '<tr><td>Status:</td><td><span class="' + statusClass + '"><i class="fas ' + statusIcon + '"></i> ' + statusText + '</span></td></tr>' +
            '<tr><td>Domain:</td><td><code>' + (data.domain || 'N/A') + '</code></td></tr>';
        
        if (data.issuer) html += '<tr><td>Issuer:</td><td>' + data.issuer + '</td></tr>';
        if (data.valid_from) html += '<tr><td>Valid From:</td><td>' + data.valid_from + '</td></tr>';
        if (data.valid_to) html += '<tr><td>Valid To:</td><td>' + data.valid_to + '</td></tr>';
        if (data.expires_in_days !== undefined) html += '<tr><td>Expires In:</td><td>' + data.expires_in_days + ' days</td></tr>';
        if (data.check_method) html += '<tr><td>Verification:</td><td><small class="text-muted">' + (data.check_method === 'local_ssh' ? 'Local Server Check' : 'Network Point Check') + '</small></td></tr>';
        html += '</table>';
        container.innerHTML = html;
    } else {
        container.innerHTML = '<p class="text-muted">' + (data.message || 'Unable to check SSL status') + '</p>';
    }
}

// ========== RENDER HELPERS (use cached data) ==========


function renderSSLStatus(data) {
    var container = document.getElementById('ssl-status');
    if (!container || !data) return;
    if (data.success && data.cert) {
        var cert = data.cert;
        container.innerHTML = '<table style="width:100%">' +
            '<tr><td><strong>Status:</strong></td><td><span style="color:#22c55e;">✓ Valid</span></td></tr>' +
            '<tr><td><strong>Issuer:</strong></td><td>' + (cert.issuer || 'Unknown') + '</td></tr>' +
            '<tr><td><strong>Valid From:</strong></td><td>' + (cert.valid_from || '') + '</td></tr>' +
            '<tr><td><strong>Valid To:</strong></td><td>' + (cert.valid_to || '') + '</td></tr>' +
            '<tr><td><strong>Expires In:</strong></td><td>' + (cert.expires_in || '') + '</td></tr>' +
            '</table>';
    } else {
        container.innerHTML = '<p class="text-muted">' + (data.message || 'Unable to check SSL status') + '</p>';
    }
}

function renderIPWhitelist(data) {
    // Hide loading
    var loadingEl = document.getElementById('ip-whitelist-loading');
    var contentEl = document.getElementById('ip-whitelist-content');
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'block';

    if (!data || !data.whitelist) return;
    var enabledEl = document.getElementById('ip-whitelist-enabled');
    var ipsEl = document.getElementById('ip-whitelist-ips');
    if (enabledEl) enabledEl.checked = data.whitelist.enabled;
    if (ipsEl) ipsEl.value = (data.whitelist.ips || []).join('\n');
}

function renderWorkflowExports(data) {
    // Hide Import loading as well
    var importLoading = document.getElementById('import-workflows-loading');
    var importContent = document.getElementById('import-workflows-content');
    if (importLoading) importLoading.style.display = 'none';
    if (importContent) importContent.style.display = 'block';

    var container = document.getElementById('workflow-exports-list');
    if (!container) return;
    
    if (data && data.success && data.exports && data.exports.length > 0) {
        var html = '<table class="backup-table"><thead><tr><th>Filename</th><th>Size</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
        data.exports.forEach(function(exp) {
            html += '<tr>' +
                '<td>' + exp.filename + '</td>' +
                '<td>' + exp.size + '</td>' +
                '<td>' + exp.date + '</td>' +
                '<td style="white-space: nowrap;">' +
                '<a href="modules/servers/dockern8n/download_export.php?serviceid=' + serviceId + '&filename=' + encodeURIComponent(exp.filename) + '" class="refresh-btn" title="Download" style="color:var(--primary); margin-right:5px;"><i class="fas fa-download"></i></a> ' +
                '<button class="refresh-btn" onclick="restoreWorkflowFromExport(\'' + exp.filename + '\')" title="Restore" style="color:#22c55e; margin-right:5px;"><i class="fas fa-undo"></i></button> ' +
                '<button class="refresh-btn" onclick="deleteWorkflowExport(\'' + exp.filename + '\')" title="Delete" style="color:#ef4444;"><i class="fas fa-trash"></i></button>' +
                '</td></tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } else {
        container.innerHTML = '<p class="text-muted">No workflow exports found. Click "Backup Workflow" to create one.</p>';
    }
}

// ========== LOADING & TOAST HELPERS ==========
function showLoading(text) {
    document.getElementById('loadingText').innerText = text || 'Processing...';
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

// ========== CONFIRMATION MODAL ==========
function showConfirm(title, message, callback, btnClass) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmMessage').innerHTML = message;
    var yesBtn = document.getElementById('confirmYesBtn');
    yesBtn.className = 'btn ' + (btnClass || 'btn-primary');
    pendingConfirmCallback = callback;
    document.getElementById('confirmModal').classList.add('active');
}

function hideConfirm() {
    document.getElementById('confirmModal').classList.remove('active');
    // Reset modal state after animation
    setTimeout(function() {
        document.getElementById('confirmYesBtn').innerText = 'Yes, Proceed';
        var cancelBtn = document.querySelector('#confirmModal .btn-default');
        if (cancelBtn) cancelBtn.style.display = '';
    }, 300);
}

function confirmAction() {
    var callback = pendingConfirmCallback; // Save reference before hiding
    pendingConfirmCallback = null;
    document.getElementById('confirmModal').classList.remove('active');
    
    // Reset modal state
    setTimeout(function() {
        document.getElementById('confirmYesBtn').innerText = 'Yes, Proceed';
        var cancelBtn = document.querySelector('#confirmModal .btn-default');
        if (cancelBtn) cancelBtn.style.display = '';
    }, 300);
    
    // Execute callback
    if (callback) {
        callback();
    }
}

// ========== SUCCESS OVERLAY ==========
function showSuccess(message, detail) {
    document.getElementById('successMessage').innerText = message || 'Success!';
    document.getElementById('successDetail').innerText = detail || '';
    document.getElementById('successOverlay').classList.add('active');
    
    // Auto hide after 2 seconds
    setTimeout(function() {
        hideSuccess();
    }, 2000);
}

function hideSuccess() {
    document.getElementById('successOverlay').classList.remove('active');
}

// ========== BUTTON LOADING STATE ==========
function setBtnLoading(btn, loading, originalHtml) {
    if (loading) {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.classList.add('loading');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    } else {
        btn.classList.remove('loading');
        btn.innerHTML = btn.dataset.originalHtml || originalHtml || btn.innerHTML;
        btn.disabled = false;
    }
}

// ========== COLORIZE CONTAINER LOGS ==========
function colorizeContainerLogs(logText) {
    // Colorization removed as per user request (Black & White only)
    return logText;
}

function showToast(message, type) {
    type = type || 'info';
    var icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-times-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<i class="' + icons[type] + '"></i>' +
        '<span class="toast-message">' + message + '</span>' +
        '<span class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>' +
        '<div class="toast-progress"></div>';
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds (matches CSS countdown animation)
    setTimeout(function() {
        if (toast.parentElement) {
            toast.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(function() { toast.remove(); }, 300);
        }
    }, 5000);
}

function updateStatusLight(status) {
    var light = document.getElementById('status-light');
    if (!light) return;
    
    status = status.toLowerCase();
    light.className = 'status-light';
    
    if (status === 'running' || status === 'up') {
        light.classList.add('green');
    } else if (status === 'stopped' || status === 'exited' || status === 'dead') {
        light.classList.add('red');
    } else if (status === 'restarting' || status === 'starting') {
        light.classList.add('yellow');
    } else {
        light.classList.add('gray');
    }
}

// ========== STATUS ==========
function checkStatus() {
    fetch(apiUrl + '?action=getStatus&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                containerStatus = d.status;
                updateStatusUI(d.status);
                
                // Update individual container status indicators
                updateContainerIndicator('n8n', d.n8n_status || d.status);
                updateContainerIndicator('postgres', d.postgres_status || 'unknown');
            } else {
                document.getElementById('status-badge').innerText = 'Error';
                updateContainerIndicator('n8n', 'error');
                updateContainerIndicator('postgres', 'error');
            }
        })
        .catch(function(e) {
            // Production
        });
}

function updateContainerIndicator(type, status) {
    var el = document.getElementById(type + '-status-indicator');
    if (!el) return;
    
    status = status.toLowerCase();
    var icon = '';
    var color = '';
    var label = status.charAt(0).toUpperCase() + status.slice(1);
    
    if (status === 'running' || status === 'up') {
        icon = 'fa-check-circle';
        color = '#22c55e';
        label = 'Running';
    } else if (status === 'stopped' || status === 'exited' || status === 'created') {
        icon = 'fa-stop-circle';
        color = '#ef4444';
        label = 'Stopped';
    } else if (status === 'deleted') {
        icon = 'fa-times-circle';
        color = '#94a3b8';
        label = 'Not Found';
    } else if (status === 'error') {
        icon = 'fa-exclamation-circle';
        color = '#f59e0b';
        label = 'Error';
    } else {
        icon = 'fa-question-circle';
        color = '#94a3b8';
        label = 'Unknown';
    }
    
    el.innerHTML = '<i class="fas ' + icon + '" style="color: ' + color + '; margin-right: 5px;"></i>' + label;
    el.style.color = color;
}

function updateStatusUI(status) {
    var badge = document.getElementById('status-badge');
    var btn = document.getElementById('btnToggle');
    var suspendBtn = document.getElementById('btnSuspendToggle');
    
    status = status.toLowerCase();
    var isRunning = (status === 'running' || status === 'up');
    var isStopped = (status === 'stopped' || status === 'exited' || status === 'created' || status === 'dead');
    var isSuspended = (status === 'suspended');
    
    // Update status light
    updateStatusLight(status);
    
    badge.className = 'status-badge status-' + (isRunning ? 'running' : (isStopped || isSuspended ? 'stopped' : 'unknown'));
    badge.innerText = status.charAt(0).toUpperCase() + status.slice(1);
    
    if (isRunning) {
        btn.className = 'btn btn-warning btn-sm';
        btn.innerHTML = '<i class="fas fa-stop"></i> Stop';
        btn.onclick = function() { toggleService('stop'); };
        btn.disabled = false;
        
        // Show Suspend button when running
        if (suspendBtn) {
            suspendBtn.style.display = 'inline-block';
            suspendBtn.className = 'btn btn-secondary btn-sm';
            suspendBtn.innerHTML = '<i class="fas fa-pause"></i> Suspend';
            suspendBtn.disabled = false;
        }
    } else if (isStopped || isSuspended) {
        btn.className = 'btn btn-success btn-sm';
        btn.innerHTML = '<i class="fas fa-play"></i> Start';
        btn.onclick = function() { toggleService('start'); };
        btn.disabled = false;
        
        // Show Unsuspend button when stopped/suspended
        if (suspendBtn) {
            suspendBtn.style.display = 'inline-block';
            suspendBtn.className = 'btn btn-info btn-sm';
            suspendBtn.innerHTML = '<i class="fas fa-play-circle"></i> Unsuspend';
            suspendBtn.disabled = false;
        }
    } else {
        btn.className = 'btn btn-success btn-sm';
        btn.innerHTML = '<i class="fas fa-play"></i> Start';
        btn.onclick = function() { toggleService('start'); };
        btn.disabled = false;
        
        // Hide suspend button on unknown status
        if (suspendBtn) {
            suspendBtn.style.display = 'none';
        }
    }
}

function toggleService(action) {
    var actionLabel = action === 'start' ? 'Starting' : 'Stopping';
    
    // Disable all action buttons and show loading on the clicked one
    var allBtns = document.querySelectorAll('#action-buttons .btn');
    allBtns.forEach(function(b) { b.disabled = true; });
    
    var btn = document.getElementById('btnToggle');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + actionLabel + '...';
    
    // Also disable restart button
    var restartBtn = document.querySelector('#action-buttons .btn-primary');
    if (restartBtn) restartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wait...';
    
    fetch(apiUrl + '?action=' + action + '&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                showSuccess(actionLabel + ' Success!', d.message || 'Page will reload shortly.');
                setTimeout(refreshAll, 1000);
            } else {
                showToast('Failed: ' + (d.message || d.error || 'Unknown error'), 'error');
                allBtns.forEach(function(b) { b.disabled = false; });
                if (restartBtn) restartBtn.innerHTML = '<i class="fas fa-sync"></i> Restart';
                fetchStatus();
            }
        })
        .catch(function(e) {
            allBtns.forEach(function(b) { b.disabled = false; });
            showToast('Request Error: ' + e.message, 'error');
        });
}

function runControl(action) {
    var actionLabel = action.charAt(0).toUpperCase() + action.slice(1);
    
    // Disable all action buttons and show loading on each
    var btns = document.querySelectorAll('#action-buttons .btn');
    btns.forEach(function(b) { 
        b.disabled = true;
        b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + actionLabel + 'ing...';
    });
    
    fetch(apiUrl + '?action=' + action + '&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                showSuccess(actionLabel + ' Success!', d.message || 'Page will reload shortly.');
                setTimeout(refreshAll, 1000);
            } else {
                btns.forEach(function(b) { b.disabled = false; });
                showToast('Operation failed: ' + (d.message || d.error), 'error');
                fetchStatus(); // Restore button states
            }
        })
        .catch(function(e) {
            btns.forEach(function(b) { b.disabled = false; });
            showToast('Request Error: ' + e.message, 'error');
            fetchStatus();
        });
}

function toggleSuspend() {
    var suspendBtn = document.getElementById('btnSuspendToggle');
    var isSuspendAction = suspendBtn.innerHTML.includes('Suspend') && !suspendBtn.innerHTML.includes('Unsuspend');
    var action = isSuspendAction ? 'suspend' : 'unsuspend';
    var actionLabel = isSuspendAction ? 'Suspending' : 'Unsuspending';
    
    // Confirm action
    if (!confirm(isSuspendAction ? 
        'Are you sure you want to SUSPEND this service? All containers will be stopped.' : 
        'Are you sure you want to UNSUSPEND this service? All containers will be started.')) {
        return;
    }
    
    // Disable all action buttons
    var btns = document.querySelectorAll('#action-buttons .btn');
    btns.forEach(function(b) { b.disabled = true; });
    
    suspendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + actionLabel + '...';
    
    fetch(apiUrl + '?action=' + action + '&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                showToast(actionLabel + ' service... Page will reload shortly.', 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                btns.forEach(function(b) { b.disabled = false; });
                showToast('Operation failed: ' + (d.message || d.error), 'error');
                fetchStatus();
            }
        })
        .catch(function(e) {
            btns.forEach(function(b) { b.disabled = false; });
            fetchStatus();
        });
}

// ========== RESOURCES ==========
var resourceHistory = { network: [], disk: [] };
var lastUpdateTime = Date.now();

function loadResourceStats() {
    var timerEl = document.getElementById('update-timer');
    if (timerEl) timerEl.textContent = 'Updating...';
    
    fetch(apiUrl + '?action=getStats&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                dataCache.resourceStats = d;
                renderResourceStats(d);
                lastUpdateTime = Date.now();
                updateCountdown();
            } else {
                console.warn('Resource stats failed:', d.message);
                // Don't clear UI immediately on single failure, keep old data if available
                if (!dataCache.resourceStats) {
                    renderResourceStats(d); // Will render N/A
                }
            }
        })
        .catch(function(e) {
            console.error('Resource fetch error:', e);
        })
        .finally(function() {
            // Schedule next update only after this one completes
            // Clear any existing interval to be safe
            if (resourceRefreshInterval) clearInterval(resourceRefreshInterval);
            resourceRefreshInterval = null; // We use timeout now
            
            setTimeout(loadResourceStats, 5000); // Increase to 5 seconds to reduce load
        });
}

function updateCountdown() {
    var timerEl = document.getElementById('update-timer');
    if (!timerEl) return;
    
    var elapsed = Math.floor((Date.now() - lastUpdateTime) / 1000);
    var remaining = Math.max(0, 3 - elapsed);
    timerEl.textContent = remaining > 0 ? 'Next update in ' + remaining + 's' : 'Updating...';
}

function renderResourceStats(d) {
    if (d && d.success) {
        // CPU
        var cpuVal = parseFloat(d.cpu) || 0;
        updateResourceValue('cpu-value', d.cpu);
        updateBar('cpu-bar', Math.min(cpuVal, 100));
        
        // Memory
        updateResourceValue('memory-value', d.memory);
        var memParts = d.memory.split('/');
        if (memParts.length === 2) {
            var used = parseMemory(memParts[0]);
            var total = parseMemory(memParts[1]);
            var memPercent = total > 0 ? (used / total * 100) : 0;
            updateBar('memory-bar', Math.min(memPercent, 100));
        }
        
        // Network - use bar with percentage based on max observed
        updateResourceValue('network-value', d.network);
        var networkVal = parseIO(d.network);
        addToHistory('network', networkVal);
        var networkMax = Math.max.apply(null, resourceHistory.network) || 1;
        var networkPercent = (networkVal / networkMax) * 100;
        updateBar('network-bar', Math.min(networkPercent, 100));
        
        // Disk - use bar with percentage based on max observed
        updateResourceValue('disk-value', d.disk);
        var diskVal = parseIO(d.disk);
        addToHistory('disk', diskVal);
        var diskMax = Math.max.apply(null, resourceHistory.disk) || 1;
        var diskPercent = (diskVal / diskMax) * 100;
        updateBar('disk-bar', Math.min(diskPercent, 100));
        
        // Storage
        renderStorageStats(d.storage);
    } else {
        document.getElementById('cpu-value').textContent = 'N/A';
        document.getElementById('memory-value').textContent = 'N/A';
        document.getElementById('network-value').textContent = 'N/A';
        document.getElementById('disk-value').textContent = 'N/A';
        document.getElementById('storage-stats').innerHTML = '<p class="text-muted">Container not running</p>';
    }
}

function updateResourceValue(id, value) {
    var el = document.getElementById(id);
    if (!el) return;
    var oldValue = el.textContent;
    el.textContent = value || '--';
    if (oldValue !== value) {
        el.classList.add('updating');
        setTimeout(function() { el.classList.remove('updating'); }, 500);
    }
}

function updateBar(id, percent) {
    var bar = document.getElementById(id);
    if (bar) bar.style.width = percent + '%';
}

function parseMemory(str) {
    str = str.trim().toUpperCase();
    var num = parseFloat(str);
    if (str.indexOf('GIB') !== -1 || str.indexOf('GB') !== -1) return num * 1024;
    if (str.indexOf('MIB') !== -1 || str.indexOf('MB') !== -1) return num;
    if (str.indexOf('KIB') !== -1 || str.indexOf('KB') !== -1) return num / 1024;
    return num;
}

function parseIO(str) {
    if (!str) return 0;
    var parts = str.split('/');
    var val = parts[0].trim().toUpperCase();
    var num = parseFloat(val);
    if (val.indexOf('GB') !== -1) return num * 1024;
    if (val.indexOf('MB') !== -1) return num;
    if (val.indexOf('KB') !== -1) return num / 1024;
    if (val.indexOf('B') !== -1) return num / 1024 / 1024;
    return num;
}

function addToHistory(type, value) {
    resourceHistory[type].push(value);
    if (resourceHistory[type].length > 15) resourceHistory[type].shift();
}


function renderStorageStats(storage) {
    var storageEl = document.getElementById('storage-stats');
    if (!storageEl) return;
    
    if (storage) {
        var percent = storage.percent || 0;
        var barColor = percent < 70 ? '#10b981' : (percent < 90 ? '#f59e0b' : '#ef4444');
        
        storageEl.innerHTML = '<div style="margin-bottom: 15px;">' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">' +
            '<span><strong>Used:</strong> ' + (storage.used || '0B') + '</span>' +
            '<span><strong>Limit:</strong> ' + (storage.limit || 'Unknown') + '</span>' +
            '</div>' +
            '<div style="background: #e2e8f0; border-radius: 10px; height: 24px; overflow: hidden;">' +
            '<div style="background: ' + barColor + '; height: 100%; width: ' + percent + '%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">' +
            (percent > 10 ? percent.toFixed(1) + '%' : '') +
            '</div></div></div>' +
            '<table class="resource-table">' +
            '<tr><td>N8N Data:</td><td>' + (storage.n8n_data || '0B') + '</td></tr>' +
            '<tr><td>PostgreSQL:</td><td>' + (storage.postgres_data || '0B') + '</td></tr>' +
            '</table>';
    } else {
        storageEl.innerHTML = '<p class="text-muted">Storage info not available</p>';
    }
}

// ========== DOMAIN ==========
function toggleDomainInput() {
    var type = document.getElementById('domain-type').value;
    var input = document.getElementById('new-domain');
    if (type === 'subdomain') {
        input.placeholder = hasSubdomain ? 'my-app (will become my-app.' + subdomainServer + ')' : 'example.com';
    } else {
        input.placeholder = 'example.com';
    }
}

function changeDomain() {
    var type = document.getElementById('domain-type').value;
    var input = document.getElementById('new-domain').value.trim();
    
    if (!input) { showToast('Please enter a domain', 'warning'); return; }
    
    // Handle subdomain concatenation
    var domain = input;
    if (type === 'subdomain' && hasSubdomain) {
        var separator = subdomainServer.charAt(0) === '.' ? '' : '.';
        domain = input + separator + subdomainServer;
    }
    
    showConfirm('Change Domain', 'Update domain to:<br><br><strong style="color:#0891b2;">' + domain + '</strong><br><br><small class="text-muted">Make sure DNS is configured correctly before proceeding.</small>', function() {
        showLoading('Changing domain... This may take 1-2 minutes');
        fetch(apiUrl + '?action=changeDomain&serviceId=' + serviceId + '&domain=' + encodeURIComponent(domain))
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Domain Updated!', d.message);
                    setTimeout(function() { 
                        showLoading('Refreshing...');
                        window.location.reload(); 
                    }, 3000);
                } else {
                    showToast('Error: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                showToast('Error updating domain', 'error');
            });
    }, 'btn-primary');
}

// ========== PASSWORD ==========
function resetPassword() {
    showConfirm('Reset Password', 'Generate a new random password for your N8N instance?<br><br><small class="text-muted">You will need to use the new password to log in.</small>', function() {
        showLoading('Resetting password...');
        
        fetch(apiUrl + '?action=resetPassword&serviceId=' + serviceId)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Password Reset!', 'New password generated');
                    // Show password in a nice way after success animation
                    setTimeout(function() {
                        showConfirm('New Password', '<div style="background:#f1f5f9; padding:15px; border-radius:7px; margin:10px 0;"><code style="font-size:18px; color:#0891b2;">' + d.password + '</code></div><small>Please save this password securely!</small>', function() {
                            refreshAll();
                        }, 'btn-success');
                        document.getElementById('confirmYesBtn').innerText = 'Got it!';
                        document.querySelector('#confirmModal .btn-default').style.display = 'none';
                    }, 2100);
                } else {
                    showToast('Error: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                // Production
            });
    }, 'btn-warning');
}

// ========== LOGS ==========
function getLogs() {
    var out = document.getElementById('logs-output');
    out.innerHTML = '<span class="log-info">Loading logs...</span>';
    fetch(apiUrl + '?action=getLogs&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) { 
            var logText = d.logs || d.output || 'No logs available';
            out.innerHTML = colorizeContainerLogs(logText);
        })
        .catch(function(e) {
            out.innerHTML = '<span class="log-error">Error loading logs: ' + e.message + '</span>';
        });
}

function clearLogs() {
    document.getElementById('logs-output').innerHTML = '<span class="log-info">Logs cleared. Click "Refresh Logs" to reload.</span>';
}

// ========== WORKFLOWS ==========
function loadWorkflowExports() {
    var list = document.getElementById('workflow-exports-list');
    list.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    // Lock Import section as well
    var importLoading = document.getElementById('import-workflows-loading');
    var importContent = document.getElementById('import-workflows-content');
    if (importLoading) importLoading.style.display = 'block';
    if (importContent) importContent.style.display = 'none';
    
    fetch(apiUrl + '?action=listWorkflowExports&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            // Cache the response for 10 minutes
            dataCache.workflowExports = d;
            dataCache.workflowExportsLoadedAt = Date.now();
            
            // Unlock Import section
            var importLoading = document.getElementById('import-workflows-loading');
            var importContent = document.getElementById('import-workflows-content');
            if (importLoading) importLoading.style.display = 'none';
            if (importContent) importContent.style.display = 'block';

            if (d.success && d.exports && d.exports.length > 0) {
                var html = '<table class="backup-table"><thead><tr><th>Filename</th><th>Size</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
                d.exports.forEach(function(e) {
                    html += '<tr><td>' + e.filename + '</td><td>' + e.size + '</td><td>' + e.date + '</td><td>' +
                        '<a href="modules/servers/dockern8n/download_export.php?serviceId=' + serviceId + '&filename=' + encodeURIComponent(e.filename) + '" class="btn btn-success btn-sm" title="Download"><i class="fas fa-download"></i></a> ' +
                        '<button class="btn btn-primary btn-sm" onclick="restoreWorkflowFromExport(\'' + e.filename + '\')" title="Restore"><i class="fas fa-undo"></i></button> ' +
                        '<button class="btn btn-danger btn-sm" onclick="deleteWorkflowExport(\'' + e.filename + '\')" title="Delete"><i class="fas fa-trash"></i></button>' +
                        '</td></tr>';
                });
                html += '</tbody></table>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p class="text-muted">No workflow exports found.</p>';
            }
        });
}

function restoreWorkflowFromExport(filename) {
    showConfirm('Restore Workflows', 'Import all workflows from <strong>' + filename + '</strong>?<br><br><small class="text-muted">Existing workflows will be updated if they exist.</small>', function() {
        showLoading('Restoring workflows...');
        fetch(apiUrl + '?action=restoreWorkflowExport&serviceId=' + serviceId + '&filename=' + encodeURIComponent(filename))
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Workflows Restored!', d.message);
                    setTimeout(refreshAll, 2000);
                } else {
                    showToast('Restore failed: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                // Production
            });
    }, 'btn-primary');
}


function exportWorkflows() {
    var includeCredentials = document.getElementById('includeCredentials').checked;
    var title = includeCredentials ? 'Export with Credentials' : 'Backup Workflow';
    var message = includeCredentials 
        ? '<strong>⚠️ Warning:</strong> This will include sensitive data like API keys and OAuth tokens!<br><br><div class="alert alert-info" style="font-size:12px; margin:0;"><i class="fas fa-clock"></i> <strong>Estimated time:</strong> 10-30 seconds</div><br>Are you sure you want to proceed?' 
        : 'Create a backup of all your workflows?<br><br><div class="alert alert-info" style="font-size:12px; margin:0;"><i class="fas fa-clock"></i> <strong>Estimated time:</strong> 10-30 seconds</div>';
    
    showConfirm(title, message, function() {
        showLoading('Exporting workflows... This may take 10-30 seconds');
        
        fetch(apiUrl + '?action=exportWorkflows&serviceId=' + serviceId + '&includeCredentials=' + includeCredentials)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Export Complete!', 'Workflow backup created successfully.');
                    loadWorkflowExports();
                } else {
                    showToast('Export failed: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                // Production
            });
    }, includeCredentials ? 'btn-warning' : 'btn-success');
}

function importWorkflows() {
    var fileInput = document.getElementById('workflow-import-file');
    if (!fileInput.files || !fileInput.files[0]) {
        showToast('Please select a JSON file', 'warning');
        return;
    }
    
    showConfirm('Import Workflows', 'Import workflows from <strong>' + fileInput.files[0].name + '</strong>?', function() {
        showLoading('Importing workflows...');
        
        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        
        // Add CSRF token
        var tokenInput = document.querySelector('input[name="token"]');
        if (tokenInput) {
            formData.append('token', tokenInput.value);
        }
        
        fetch(apiUrl + '?action=importWorkflows&serviceId=' + serviceId, {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            hideLoading();
            if (d.success) {
                showSuccess('Import Complete!', d.message);
                fileInput.value = '';
                setTimeout(refreshAll, 2000);
            } else {
                showToast('Import failed: ' + (d.message || d.error), 'error');
            }
        })
        .catch(function(e) {
            hideLoading();
            // Production
        });
    }, 'btn-primary');
}

function deleteWorkflowExport(filename) {
    showConfirm('Delete Export', 'Delete <strong>' + filename + '</strong>?<br><br><small class="text-muted">This action cannot be undone.</small>', function() {
        showLoading('Deleting...');
        fetch(apiUrl + '?action=deleteWorkflowExport&serviceId=' + serviceId + '&filename=' + encodeURIComponent(filename))
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Deleted!', d.message);
                    setTimeout(loadWorkflowExports, 2000);
                } else {
                    showToast('Error: ' + (d.error || d.message), 'error');
                }
            });
    }, 'btn-danger');
}

// Version management logic removed


// ========== SERVICE REPAIR ==========
function generateComposeFile() {
    var statusEl = document.getElementById('compose-status');
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    fetch(apiUrl + '?action=generateComposeFile&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                statusEl.innerHTML = '<span style="color:#10b981;"><i class="fas fa-check"></i> ' + d.message + '</span>';
                showToast('Configuration generated!', 'success');
            } else {
                statusEl.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-times"></i> ' + (d.message || 'Failed') + '</span>';
                showToast('Error: ' + (d.message || 'Failed'), 'error');
            }
        })
        .catch(function(e) {
            statusEl.innerHTML = '<span style="color:#ef4444;"><i class="fas fa-times"></i> Failed</span>';
        });
}

// ========== REINSTALL ==========
// Soft Reset - Delete users only, keep workflows
function reinstallSoft() {
    showConfirm('Soft Reset', '<strong>Delete all user accounts</strong> but <span style="color:#059669;">KEEP all workflows and credentials</span>?<br><br><strong>What happens:</strong><br>• All users will be deleted<br>• You can create a new account<br>• All workflows will be preserved<br>• All credentials will be preserved<br><br><div class="alert alert-warning" style="font-size:12px; margin:10px 0 0 0;"><i class="fas fa-clock"></i> <strong>Please wait for the operation to complete.</strong></div>', function() {
        showLoading('Performing soft reset... Please wait');
        fetch(apiUrl + '?action=softReset&serviceId=' + serviceId)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Soft Reset Complete!', d.message || 'Users removed. You can now create a new account.');
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    showToast('Error: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                showToast('Error performing reset', 'error');
            });
    }, 'btn-warning');
}

// Full Reset - Deletes all data
function reinstallFull() {
    showConfirm('⚠️ FULL RESET', '<div style="background:#fef2f2; padding:15px; border-radius:8px; border:1px solid #fecaca; margin-bottom:15px;"><strong style="color:#dc2626;">⚠️ ALL DATA WILL BE DELETED!</strong><br><br>This will permanently delete:<br>• All workflows<br>• All credentials<br>• All settings<br>• All execution history</div><strong>This action CANNOT be undone!</strong>', function() {
        // Second confirmation
        showConfirm('Last Warning!', '<div style="text-align:center; padding:20px;"><i class="fas fa-exclamation-triangle" style="font-size:48px; color:#dc2626;"></i><br><br><strong>Are you absolutely sure?</strong><br><small class="text-muted">All data will be permanently lost.</small></div>', function() {
            showLoading('Performing full reset... Please wait');
            fetch(apiUrl + '?action=reinstall&serviceId=' + serviceId)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    hideLoading();
                    if (d.success) {
                        showSuccess('Full Reset Complete!', d.message || 'Fresh instance created successfully.');
                        setTimeout(function() { window.location.reload(); }, 2000);
                    } else {
                        showToast('Error: ' + (d.message || d.error), 'error');
                    }
                })
                .catch(function(e) {
                    hideLoading();
                    showToast('Error performing reset', 'error');
                });
        }, 'btn-danger');
    }, 'btn-danger');
}

// ========== INIT ==========
function handleTabShown(target) {
    syncActiveTabUI(target);
    animateHeaderBorder();
    if (target === '#n8n-resources') {
        applyResourceStats();
        // Clear any old interval if exists (migration)
        if (resourceRefreshInterval && typeof resourceRefreshInterval === 'number') {
             clearInterval(resourceRefreshInterval);
             resourceRefreshInterval = null;
        }
        // Start the loop if not already running (we use a flag or check if timeout is set)
        // Since loadResourceStats schedules itself, just calling it once starts the loop.
        // But applyResourceStats calls it if not cached.
        // Let's ensure loop is running.
        if (!resourceRefreshInterval) {
            loadResourceStats();
        }
        if (!window.countdownInterval) {
            window.countdownInterval = setInterval(updateCountdown, 500);
        }
    } else if (target === '#n8n-workflows') {
        applyWorkflowExports();
    } else if (target === '#n8n-logs') {
        getLogs();
    } else if (target === '#n8n-security') {
        applySecurityData();
        loadUserIP();
    } else if (target === '#n8n-settings') {
        applySettingsData();
    }
}

function syncActiveTabUI(target) {
    if (!target || target.charAt(0) !== '#') return;
    var manager = document.querySelector('.n8n-manager');
    if (!manager) return;

    manager.querySelectorAll('a[data-toggle="tab"]').forEach(function(a) {
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) !== '#') return;
        var isActive = href === target;
        a.classList.toggle('active', isActive);
        a.setAttribute('aria-selected', isActive ? 'true' : 'false');
        if (a.parentElement && a.parentElement.tagName && a.parentElement.tagName.toLowerCase() === 'li') {
            a.parentElement.classList.toggle('active', isActive);
        }
    });
}

function activateTabManually(tabEl) {
    var href = tabEl.getAttribute('href');
    if (!href || href.charAt(0) !== '#') return false;
    var manager = tabEl.closest('.n8n-manager');
    if (!manager) return false;

    var tabList = tabEl.closest('ul');
    if (tabList) {
        tabList.querySelectorAll('li').forEach(function(li) { li.classList.remove('active'); });
        tabList.querySelectorAll('a[data-toggle="tab"]').forEach(function(a) {
            a.classList.remove('active');
            a.setAttribute('aria-selected', 'false');
        });
    }

    if (tabEl.parentElement) {
        tabEl.parentElement.classList.add('active');
    }
    tabEl.classList.add('active');
    tabEl.setAttribute('aria-selected', 'true');

    manager.querySelectorAll('.tab-pane').forEach(function(pane) {
        pane.classList.remove('active');
    });

    var targetPane = manager.querySelector(href);
    if (targetPane) {
        targetPane.classList.add('active');
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Load all data at once for better performance
    loadAllData();
    
    // Tab change handlers - use cached data when available
    document.querySelectorAll('.n8n-manager a[data-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(e) {
            var target = e.target.getAttribute('href');
            handleTabShown(target);
        });

        tab.addEventListener('click', function(e) {
            var href = tab.getAttribute('href');
            if (!href || href.charAt(0) !== '#') return;

            if (typeof jQuery !== 'undefined' && jQuery.fn && typeof jQuery.fn.tab === 'function') {
                e.preventDefault();
                jQuery(tab).tab('show');
                return;
            }

            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                e.preventDefault();
                try {
                    bootstrap.Tab.getOrCreateInstance(tab).show();
                    return;
                } catch (err) {}
            }

            e.preventDefault();
            if (activateTabManually(tab)) {
                handleTabShown(href);
            }
        });
    });

    var managerEl = document.querySelector('.n8n-manager');
    var initialTarget = window.location.hash || '#n8n-overview';
    if (managerEl && managerEl.querySelector(initialTarget)) {
        syncActiveTabUI(initialTarget);
    } else {
        syncActiveTabUI('#n8n-overview');
    }
    
    // jQuery fallback for Bootstrap 3
    if (typeof jQuery !== 'undefined') {
        jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = jQuery(e.target).attr('href');
            handleTabShown(target);
        });
    }
});

// Animate header border on tab change
function animateHeaderBorder() {
    var progressBar = document.getElementById('header-progress');
    if (progressBar) {
        // Reset animation
        progressBar.style.animation = 'none';
        progressBar.style.width = '0%';
        progressBar.offsetHeight; // Trigger reflow
        progressBar.style.animation = 'headerBorderFill 0.6s ease-out forwards';
    }
}

// Load user IP for Security tab
function loadUserIP() {
    var ipEl = document.getElementById('current-user-ip');
    if (ipEl) {
        fetch('https://api.ipify.org?format=json')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                ipEl.innerText = d.ip || 'Unknown';
            })
            .catch(function() {
                ipEl.innerText = 'Unable to detect';
            });
    }
}


// ========== SSL STATUS ==========
function loadSSLStatus() {
    var container = document.getElementById('ssl-status');
    var btn = document.getElementById('refresh-ssl-btn');
    
    if (btn) btn.classList.add('spinning');
    if (!container.innerHTML.includes('fa-spinner')) {
        container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Checking SSL status...</p>';
    }

    fetch(apiUrl + '?action=getSSLStatus&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (btn) btn.classList.remove('spinning');
            
            // Cache the response for 10 minutes
            dataCache.sslStatus = d;
            dataCache.sslLoadedAt = Date.now();
            
            if (d.success) {
                var statusClass = 'text-success';
                var statusIcon = 'fa-check-circle';
                var statusText = 'Valid';
                
                if (d.ssl_status === 'expired') {
                    statusClass = 'text-danger';
                    statusIcon = 'fa-times-circle';
                    statusText = 'Expired';
                } else if (d.ssl_status === 'expiring_soon') {
                    statusClass = 'text-warning';
                    statusIcon = 'fa-exclamation-circle';
                    statusText = 'Expiring Soon';
                } else if (d.ssl_status === 'pending') {
                    statusClass = 'text-muted';
                    statusIcon = 'fa-clock';
                    statusText = 'Pending';
                }
                
                var html = '<table class="resource-table">' +
                    '<tr><td>Status:</td><td><span class="' + statusClass + '"><i class="fas ' + statusIcon + '"></i> ' + statusText + '</span></td></tr>' +
                    '<tr><td>Domain:</td><td><code>' + (d.domain || 'N/A') + '</code></td></tr>';
                
                if (d.issuer) {
                    html += '<tr><td>Issuer:</td><td>' + d.issuer + '</td></tr>';
                }
                if (d.valid_from) {
                    html += '<tr><td>Valid From:</td><td>' + d.valid_from + '</td></tr>';
                }
                if (d.valid_to) {
                    html += '<tr><td>Valid To:</td><td>' + d.valid_to + '</td></tr>';
                }
                if (d.expires_in_days !== undefined) {
                    html += '<tr><td>Expires In:</td><td>' + d.expires_in_days + ' days</td></tr>';
                }
                if (d.check_method) {
                    html += '<tr><td>Verification:</td><td><small class="text-muted">' + (d.check_method === 'local_ssh' ? 'Local Server Check' : 'Network Point Check') + '</small></td></tr>';
                }
                html += '</table>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger" style="font-size:12px; margin:0;"><i class="fas fa-exclamation-circle"></i> ' + (d.message || 'Unable to check SSL status') + '</div>';
            }
        })
        .catch(function(e) {
            if (btn) btn.classList.remove('spinning');
            container.innerHTML = '<div class="alert alert-danger" style="font-size:12px; margin:0;"><i class="fas fa-exclamation-circle"></i> Service Unavailable</div>';
        });
}

// ========== IP WHITELIST ==========
function loadIPWhitelist() {
    // Lock IP Whitelist section
    var loadingEl = document.getElementById('ip-whitelist-loading');
    var contentEl = document.getElementById('ip-whitelist-content');
    if (loadingEl) loadingEl.style.display = 'block';
    if (contentEl) contentEl.style.display = 'none';

    fetch(apiUrl + '?action=getIPWhitelist&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            // Cache the response for 10 minutes
            dataCache.ipWhitelist = d;
            dataCache.ipWhitelistLoadedAt = Date.now();
            
            // Unlock IP Whitelist section
            var loadingEl = document.getElementById('ip-whitelist-loading');
            var contentEl = document.getElementById('ip-whitelist-content');
            if (loadingEl) loadingEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'block';

            if (d.success && d.whitelist) {
                document.getElementById('ip-whitelist-enabled').checked = d.whitelist.enabled;
                document.getElementById('ip-whitelist-ips').value = (d.whitelist.ips || []).join('\n');
            }
        });
}

function saveIPWhitelist() {
    var enabled = document.getElementById('ip-whitelist-enabled').checked;
    var ipsText = document.getElementById('ip-whitelist-ips').value;
    var ips = ipsText.split('\n').map(function(ip) { return ip.trim(); }).filter(function(ip) { return ip !== ''; });
    
    showConfirm('Save IP Whitelist', 'Update IP whitelist settings?<br><br><strong>' + (enabled ? '✓ Enabled' : '✗ Disabled') + '</strong> with ' + ips.length + ' IP(s)', function() {
        showLoading('Saving IP whitelist...');
        fetch(apiUrl + '?action=updateIPWhitelist&serviceId=' + serviceId + '&enabled=' + (enabled ? '1' : '0') + '&ips=' + encodeURIComponent(ips.join(',')))
            .then(function(r) { return r.json(); })
            .then(function(d) {
                hideLoading();
                if (d.success) {
                    showSuccess('Saved!', d.message);
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    showToast('Error: ' + (d.message || d.error), 'error');
                }
            })
            .catch(function(e) {
                hideLoading();
                // Production
            });
    }, 'btn-primary');
}

// ========== SERVICE LOGS (ACTIVITY LOG) ==========
function loadActivityLog() {
    var output = document.getElementById('service-logs-output');
    output.innerHTML = '<span class="log-time">Loading service logs...</span>';
    
    fetch(apiUrl + '?action=getActivityLog&serviceId=' + serviceId + '&limit=50')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success && d.logs && d.logs.length > 0) {
                var html = '';
                d.logs.forEach(function(log) {
                    html += '<div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">';
                    html += '<span class="log-time">[' + log.timestamp + ']</span> ';
                    html += '<span class="log-action">' + log.action.toUpperCase() + '</span><br>';
                    html += '<span class="log-user">👤 ' + log.user_name + '</span> ';
                    html += '<span class="log-ip">🌐 ' + log.ip_address + '</span>';
                    if (log.details) {
                        html += '<br><span class="log-details">📝 ' + log.details + '</span>';
                    }
                    html += '</div>';
                });
                output.innerHTML = html;
            } else {
                output.innerHTML = '<span class="log-time">No service logs found.</span>';
            }
        })
        .catch(function(e) {
            output.innerHTML = '<span style="color: #dc2626;">Error loading logs: ' + e.message + '</span>';
        });
}

function clearServiceLogs() {
    document.getElementById('service-logs-output').innerText = 'Logs cleared. Click "Refresh Logs" to reload.';
}

// ========== SETTINGS INIT ==========
// ========== PAGE VISIBILITY HANDLER ==========
// Refresh status when user returns to this tab
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // User returned to this tab - refresh status
        // Production
        fetchStatus();
    }
});

// Safe status fetch (doesn't throw on error)
function fetchStatus() {
    var statusBadge = document.getElementById('status-badge');
    var n8nIndicator = document.getElementById('n8n-status-indicator');
    var postgresIndicator = document.getElementById('postgres-status-indicator');
    
    fetch(apiUrl + '?action=getStatus&serviceId=' + serviceId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                containerStatus = d.status;
                updateStatusUI(d.status);
                updateContainerIndicator('n8n', d.n8n_status || d.status);
                updateContainerIndicator('postgres', d.postgres_status || 'unknown');
            }
        })
        .catch(function(e) {
            // Silently ignore errors when tab was inactive
            // Production:', e);
        });
}

// Helper for page refresh
function refreshAll() {
    window.location.reload();
}

// Initial status fetch on page load
fetchStatus();

// Load versions for version management dropdown
// loadVersions(); removed

{/literal}
</script>
