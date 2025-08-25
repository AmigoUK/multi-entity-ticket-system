/**
 * METS Polling Client
 * 
 * Handles periodic polling for updates as a replacement for WebSocket functionality
 * 
 * @since 1.0.0
 */
(function($) {
    'use strict';

    /**
     * METS Polling Client Class
     */
    class METSPollingClient {
        constructor() {
            this.pollInterval = metsPolling.pollInterval || 30000; // Default 30 seconds
            this.ajaxUrl = metsPolling.ajaxUrl;
            this.nonce = metsPolling.nonce;
            this.lastCheck = Math.floor(Date.now() / 1000);
            this.pollingTimer = null;
            this.isPolling = false;
            this.notificationContainer = null;
            
            // Initialize if polling is enabled
            if (metsPolling.enablePolling) {
                this.init();
            }
        }

        /**
         * Initialize the polling client
         */
        init() {
            // Create notification container
            this.createNotificationContainer();
            
            // Start polling
            this.start();
            
            // Check for notifications on page load
            this.checkNotifications();
            
            // Handle notification clicks
            this.bindNotificationEvents();
            
            // Handle visibility change
            this.handleVisibilityChange();
        }

        /**
         * Create notification container
         */
        createNotificationContainer() {
            if ($('#mets-notification-container').length === 0) {
                const container = $('<div id="mets-notification-container" class="mets-notification-container"></div>');
                $('body').append(container);
                this.notificationContainer = container;
            } else {
                this.notificationContainer = $('#mets-notification-container');
            }
        }

        /**
         * Start polling
         */
        start() {
            // Clear any existing timer
            if (this.pollingTimer) {
                clearInterval(this.pollingTimer);
            }
            
            // Set up polling interval
            this.pollingTimer = setInterval(() => {
                if (!this.isPolling && !document.hidden) {
                    this.checkForUpdates();
                }
            }, this.pollInterval);
        }

        /**
         * Stop polling
         */
        stop() {
            if (this.pollingTimer) {
                clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }
        }

        /**
         * Check for updates
         */
        checkForUpdates() {
            if (this.isPolling) {
                return;
            }
            
            this.isPolling = true;
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_check_updates',
                    nonce: this.nonce,
                    lastCheck: this.lastCheck
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.processUpdates(response.data);
                        this.lastCheck = response.data.timestamp || Math.floor(Date.now() / 1000);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('METS Polling Error:', error);
                },
                complete: () => {
                    this.isPolling = false;
                }
            });
        }

        /**
         * Process updates from server
         */
        processUpdates(data) {
            // Process notifications
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    this.showNotification(notification);
                });
                
                // Update notification badge
                this.updateNotificationBadge(data.notifications.length);
            }
            
            // Trigger custom events for different update types
            if (data.tickets && data.tickets.length > 0) {
                $(document).trigger('mets:new-tickets', [data.tickets]);
            }
            
            if (data.replies && data.replies.length > 0) {
                $(document).trigger('mets:new-replies', [data.replies]);
            }
            
            if (data.assignments && data.assignments.length > 0) {
                $(document).trigger('mets:new-assignments', [data.assignments]);
            }
            
            if (data.status_changes && data.status_changes.length > 0) {
                $(document).trigger('mets:status-changes', [data.status_changes]);
            }
        }

        /**
         * Show notification
         */
        showNotification(notification) {
            const notificationEl = $(`
                <div class="mets-notification" data-notification-id="${notification.id}" data-ticket-id="${notification.ticket_id}">
                    <div class="mets-notification-content">
                        <div class="mets-notification-icon">
                            ${this.getNotificationIcon(notification.type)}
                        </div>
                        <div class="mets-notification-text">
                            <div class="mets-notification-message">${notification.message}</div>
                            <div class="mets-notification-time">${this.getRelativeTime(notification.timestamp)}</div>
                        </div>
                        <button class="mets-notification-close" aria-label="Close">&times;</button>
                    </div>
                </div>
            `);
            
            // Add to container
            this.notificationContainer.prepend(notificationEl);
            
            // Animate in
            setTimeout(() => {
                notificationEl.addClass('show');
            }, 10);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                this.hideNotification(notificationEl);
            }, 10000);
            
            // Play notification sound if enabled
            this.playNotificationSound();
            
            // Show browser notification if permitted
            this.showBrowserNotification(notification);
        }

        /**
         * Hide notification
         */
        hideNotification(notificationEl) {
            notificationEl.removeClass('show');
            setTimeout(() => {
                notificationEl.remove();
            }, 300);
        }

        /**
         * Get notification icon based on type
         */
        getNotificationIcon(type) {
            const icons = {
                'new_ticket': '<span class="dashicons dashicons-tickets-alt"></span>',
                'ticket_assigned': '<span class="dashicons dashicons-admin-users"></span>',
                'new_reply': '<span class="dashicons dashicons-format-chat"></span>',
                'status_changed': '<span class="dashicons dashicons-flag"></span>'
            };
            
            return icons[type] || '<span class="dashicons dashicons-info"></span>';
        }

        /**
         * Get relative time string
         */
        getRelativeTime(timestamp) {
            const now = Math.floor(Date.now() / 1000);
            const diff = now - timestamp;
            
            if (diff < 60) {
                return 'Just now';
            } else if (diff < 3600) {
                const minutes = Math.floor(diff / 60);
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            } else if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            } else {
                const days = Math.floor(diff / 86400);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
        }

        /**
         * Update notification badge
         */
        updateNotificationBadge(count) {
            // Update admin bar badge
            let badge = $('#wp-admin-bar-mets-notifications .mets-notification-badge');
            if (badge.length === 0) {
                $('#wp-admin-bar-mets-notifications a').append('<span class="mets-notification-badge">0</span>');
                badge = $('#wp-admin-bar-mets-notifications .mets-notification-badge');
            }
            
            const currentCount = parseInt(badge.text()) || 0;
            const newCount = currentCount + count;
            badge.text(newCount).toggle(newCount > 0);
            
            // Update page title if notifications
            if (newCount > 0) {
                document.title = `(${newCount}) ${document.title.replace(/^\(\d+\)\s*/, '')}`;
            }
        }

        /**
         * Check for existing notifications
         */
        checkNotifications() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_get_notifications',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.updateNotificationBadge(response.data.count);
                    }
                }
            });
        }

        /**
         * Bind notification events
         */
        bindNotificationEvents() {
            // Close notification
            $(document).on('click', '.mets-notification-close', (e) => {
                e.stopPropagation();
                const notification = $(e.target).closest('.mets-notification');
                const notificationId = notification.data('notification-id');
                
                this.markNotificationRead(notificationId);
                this.hideNotification(notification);
            });
            
            // Click on notification
            $(document).on('click', '.mets-notification', (e) => {
                const ticketId = $(e.currentTarget).data('ticket-id');
                if (ticketId) {
                    window.location.href = `${window.location.origin}/wp-admin/admin.php?page=mets-tickets&action=view&ticket_id=${ticketId}`;
                }
            });
            
            // Mark all as read
            $(document).on('click', '#mets-mark-all-read', (e) => {
                e.preventDefault();
                $('.mets-notification').each((index, el) => {
                    const notificationId = $(el).data('notification-id');
                    this.markNotificationRead(notificationId);
                });
                $('.mets-notification').remove();
                this.updateNotificationBadge(-1000); // Reset badge
            });
        }

        /**
         * Mark notification as read
         */
        markNotificationRead(notificationId) {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_mark_notification_read',
                    nonce: this.nonce,
                    notificationId: notificationId
                }
            });
            
            // Update badge count
            const badge = $('#wp-admin-bar-mets-notifications .mets-notification-badge');
            if (badge.length) {
                const count = Math.max(0, parseInt(badge.text()) - 1);
                badge.text(count).toggle(count > 0);
                
                // Update page title
                if (count === 0) {
                    document.title = document.title.replace(/^\(\d+\)\s*/, '');
                } else {
                    document.title = `(${count}) ${document.title.replace(/^\(\d+\)\s*/, '')}`;
                }
            }
        }

        /**
         * Play notification sound
         */
        playNotificationSound() {
            // Only play if user has interacted with page
            if (this.userHasInteracted) {
                const audio = new Audio(metsPolling.notificationSound || '');
                audio.volume = 0.3;
                audio.play().catch(() => {
                    // Ignore errors - user may have disabled sounds
                });
            }
        }

        /**
         * Show browser notification
         */
        showBrowserNotification(notification) {
            // Check if notifications are supported and permitted
            if ('Notification' in window && Notification.permission === 'granted') {
                const browserNotification = new Notification('METS Ticket System', {
                    body: notification.message,
                    icon: metsPolling.notificationIcon || '',
                    tag: notification.id,
                    requireInteraction: false
                });
                
                // Click handler
                browserNotification.onclick = () => {
                    window.focus();
                    if (notification.ticket_id) {
                        window.location.href = `${window.location.origin}/wp-admin/admin.php?page=mets-tickets&action=view&ticket_id=${notification.ticket_id}`;
                    }
                    browserNotification.close();
                };
                
                // Auto close after 5 seconds
                setTimeout(() => {
                    browserNotification.close();
                }, 5000);
            }
        }

        /**
         * Handle visibility change
         */
        handleVisibilityChange() {
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    // Stop polling when page is hidden
                    this.stop();
                } else {
                    // Resume polling when page is visible
                    this.start();
                    // Check for updates immediately
                    this.checkForUpdates();
                }
            });
        }

        /**
         * Request notification permission
         */
        static requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    }

    // Initialize on document ready
    $(document).ready(() => {
        // Create global instance
        window.metsPollingClient = new METSPollingClient();
        
        // Request notification permission on first user interaction
        $(document).one('click', () => {
            METSPollingClient.requestNotificationPermission();
            window.metsPollingClient.userHasInteracted = true;
        });
    });

})(jQuery);