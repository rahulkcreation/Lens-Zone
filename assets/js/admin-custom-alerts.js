/**
 * Custom Alert System for Lens Zone Plugin
 * Replaces browser default alerts with custom styled popups
 */

(function($) {
    'use strict';
    
    // Create custom alert modal HTML
    function createAlertModal() {
        if ($('#lens-zone-custom-alert').length) {
            return;
        }
        
        var modalHTML = `
            <div id="lens-zone-custom-alert" class="lens-zone-modal" style="display: none;">
                <div class="lens-zone-modal-overlay"></div>
                <div class="lens-zone-modal-content">
                    <div class="lens-zone-modal-header">
                        <span class="lens-zone-modal-icon"></span>
                        <h3 class="lens-zone-modal-title"></h3>
                    </div>
                    <div class="lens-zone-modal-body">
                        <p class="lens-zone-modal-message"></p>
                    </div>
                    <div class="lens-zone-modal-footer">
                        <button class="lens-zone-modal-btn lens-zone-modal-btn-primary">OK</button>
                        <button class="lens-zone-modal-btn lens-zone-modal-btn-secondary" style="display: none;">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Add CSS
        var css = `
            <style>
                .lens-zone-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 999999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .lens-zone-modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(2px);
                }
                
                .lens-zone-modal-content {
                    position: relative;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 450px;
                    width: 90%;
                    animation: modalSlideIn 0.3s ease-out;
                }
                
                @keyframes modalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                .lens-zone-modal-header {
                    padding: 24px 24px 16px;
                    text-align: center;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .lens-zone-modal-icon {
                    display: inline-block;
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    margin-bottom: 12px;
                    font-size: 24px;
                    line-height: 48px;
                }
                
                .lens-zone-modal-icon.info {
                    background: #dbeafe;
                    color: #2563eb;
                }
                
                .lens-zone-modal-icon.success {
                    background: #d1fae5;
                    color: #10b981;
                }
                
                .lens-zone-modal-icon.warning {
                    background: #fef3c7;
                    color: #f59e0b;
                }
                
                .lens-zone-modal-icon.error {
                    background: #fee2e2;
                    color: #ef4444;
                }
                
                .lens-zone-modal-icon::before {
                    font-family: dashicons;
                    font-size: 24px;
                }
                
                .lens-zone-modal-icon.info::before {
                    content: "\\f348";
                }
                
                .lens-zone-modal-icon.success::before {
                    content: "\\f147";
                }
                
                .lens-zone-modal-icon.warning::before {
                    content: "\\f534";
                }
                
                .lens-zone-modal-icon.error::before {
                    content: "\\f153";
                }
                
                .lens-zone-modal-title {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: #111827;
                }
                
                .lens-zone-modal-body {
                    padding: 20px 24px;
                }
                
                .lens-zone-modal-message {
                    margin: 0;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #6b7280;
                    text-align: center;
                }
                
                .lens-zone-modal-footer {
                    padding: 16px 24px 24px;
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                }
                
                .lens-zone-modal-btn {
                    padding: 10px 24px;
                    border: none;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .lens-zone-modal-btn-primary {
                    background: #2563eb;
                    color: white;
                }
                
                .lens-zone-modal-btn-primary:hover {
                    background: #1d4ed8;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
                }
                
                .lens-zone-modal-btn-secondary {
                    background: #f3f4f6;
                    color: #374151;
                }
                
                .lens-zone-modal-btn-secondary:hover {
                    background: #e5e7eb;
                }
            </style>
        `;
        
        $('head').append(css);
    }
    
    // Show custom alert
    window.lensZoneAlert = function(message, type, title) {
        type = type || 'info';
        title = title || 'Notification';
        
        createAlertModal();
        
        var $modal = $('#lens-zone-custom-alert');
        var $icon = $modal.find('.lens-zone-modal-icon');
        var $title = $modal.find('.lens-zone-modal-title');
        var $message = $modal.find('.lens-zone-modal-message');
        var $secondaryBtn = $modal.find('.lens-zone-modal-btn-secondary');
        
        // Set icon type
        $icon.removeClass('info success warning error').addClass(type);
        
        // Set title and message
        $title.text(title);
        $message.text(message);
        
        // Hide secondary button for alerts
        $secondaryBtn.hide();
        
        // Show modal
        $modal.fadeIn(200);
        
        // Close on button click
        $modal.find('.lens-zone-modal-btn-primary').off('click').on('click', function() {
            $modal.fadeOut(200);
        });
        
        // Close on overlay click
        $modal.find('.lens-zone-modal-overlay').off('click').on('click', function() {
            $modal.fadeOut(200);
        });
    };
    
    // Show custom confirm
    window.lensZoneConfirm = function(message, callback, title) {
        title = title || 'Confirm';
        
        createAlertModal();
        
        var $modal = $('#lens-zone-custom-alert');
        var $icon = $modal.find('.lens-zone-modal-icon');
        var $title = $modal.find('.lens-zone-modal-title');
        var $message = $modal.find('.lens-zone-modal-message');
        var $primaryBtn = $modal.find('.lens-zone-modal-btn-primary');
        var $secondaryBtn = $modal.find('.lens-zone-modal-btn-secondary');
        
        // Set icon type
        $icon.removeClass('info success warning error').addClass('warning');
        
        // Set title and message
        $title.text(title);
        $message.text(message);
        
        // Show secondary button for confirms
        $secondaryBtn.show().text('Cancel');
        $primaryBtn.text('Confirm');
        
        // Show modal
        $modal.fadeIn(200);
        
        // Handle confirm
        $primaryBtn.off('click').on('click', function() {
            $modal.fadeOut(200);
            if (callback) callback(true);
        });
        
        // Handle cancel
        $secondaryBtn.off('click').on('click', function() {
            $modal.fadeOut(200);
            if (callback) callback(false);
        });
        
        // Close on overlay click (cancel)
        $modal.find('.lens-zone-modal-overlay').off('click').on('click', function() {
            $modal.fadeOut(200);
            if (callback) callback(false);
        });
    };
    
})(jQuery);
