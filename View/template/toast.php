<!-- View/template/toast.php -->
<div id="toast-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10000; width: 90%; max-width: 500px;"></div>
<div id="alert-container" style="position: fixed; top: 80px; left: 0; right: 0; z-index: 10000; padding: 20px; pointer-events: none;"></div>
<style>
/* --- Toast Notification Styles --- */
.toast-notification {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    display: flex;
    align-items: flex-start;
    gap: 16px;
    animation: slideInTop 0.3s ease-out;
    border-left: 5px solid #ddd;
}

.toast-notification.success {
    background: #f0f9f4;
    border-left-color: #28a745;
}

.toast-notification.success .toast-icon {
    color: #28a745;
}

.toast-notification.danger {
    background: #fff5f5;
    border-left-color: #dc3545;
}

.toast-notification.danger .toast-icon {
    color: #dc3545;
}

.toast-notification.info {
    background: #f0f8ff;
    border-left-color: #17a2b8;
}

.toast-notification.info .toast-icon {
    color: #17a2b8;
}

.toast-icon {
    font-size: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}

.toast-content {
    flex-grow: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 4px;
}

.toast-message {
    font-size: 14px;
    color: #555;
    line-height: 1.4;
}

.toast-notification.success .toast-title {
    color: #155724;
}

.toast-notification.success .toast-message {
    color: #155724;
}

.toast-notification.danger .toast-title {
    color: #721c24;
}

.toast-notification.danger .toast-message {
    color: #721c24;
}

.toast-notification.info .toast-title {
    color: #0c5460;
}

.toast-notification.info .toast-message {
    color: #0c5460;
}

.toast-close {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 24px;
    padding: 0;
    flex-shrink: 0;
    line-height: 1;
}

.toast-close:hover {
    color: #333;
}

@keyframes slideInTop {
    from {
        transform: translateX(-50%) translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutTop {
    from {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
    to {
        transform: translateX(-50%) translateY(-30px);
        opacity: 0;
    }
}

.toast-notification.removing {
    animation: slideOutTop 0.3s ease-out;
}

/* --- Alert Notification Styles --- */
.alert-notification {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 24px;
    border-radius: 8px;
    margin-bottom: 12px;
    animation: slideInDown 0.3s ease-out;
    max-width: 100%;
    pointer-events: auto;
}

.alert-notification.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-notification.danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-notification.info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.alert-icon {
    font-size: 20px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.alert-content {
    flex-grow: 1;
    font-size: 15px;
    font-weight: 500;
}

.alert-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    font-size: 24px;
    padding: 0;
    flex-shrink: 0;
    line-height: 1;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

.alert-notification.removing {
    animation: slideOutDown 0.3s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutDown {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-30px);
        opacity: 0;
    }
}

/* --- Custom Modal Styles --- */
.custom-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeInBackdrop 0.3s ease-out;
    backdrop-filter: blur(2px);
}

.custom-modal-backdrop.closing {
    animation: fadeOutBackdrop 0.3s ease-out;
}

@keyframes fadeInBackdrop {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOutBackdrop {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

.custom-modal-dialog {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.05);
    width: 90%;
    max-width: 450px;
    animation: slideInModal 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: center;
}

.custom-modal-backdrop.closing .custom-modal-dialog {
    animation: slideOutModal 0.3s ease-in;
}

@keyframes slideInModal {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes slideOutModal {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
}

.custom-modal-content {
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 40px);
}

.custom-modal-header {
    padding: 24px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #c62828 0%, #7f0000 100%);
    border-radius: 16px 16px 0 0;
}

.custom-modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.custom-modal-body {
    padding: 24px;
    color: #2c3e50;
    font-size: 16px;
    line-height: 1.6;
    flex: 1;
    overflow-y: auto;
    max-height: calc(100vh - 220px);
}

.custom-modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
}

.custom-modal-footer .btn {
    padding: 10px 24px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.custom-modal-footer .btn-secondary {
    background: #e9ecef;
    color: #495057;
}

.custom-modal-footer .btn-secondary:hover {
    background: #dee2e6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.custom-modal-footer .btn-danger {
    background: linear-gradient(135deg, #c62828 0%, #7f0000 100%);
    color: white;
}

.custom-modal-footer .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(198, 40, 40, 0.4);
}

.custom-modal-footer .btn-danger:active {
    transform: translateY(0);
}
</style>