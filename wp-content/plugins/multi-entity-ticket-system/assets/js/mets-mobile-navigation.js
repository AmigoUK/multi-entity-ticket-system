/**
 * METS Mobile Navigation JavaScript
 *
 * @package MultiEntityTicketSystem
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Mobile Navigation Controller
     */
    var METSMobileNav = {
        
        /**
         * Initialize mobile navigation
         */
        init: function() {
            this.createNavigationElements();
            this.bindEvents();
            this.updateBreadcrumb();
            this.initStatusBar();
            this.handleResize();
        },

        /**
         * Create navigation elements
         */
        createNavigationElements: function() {
            // Create mobile toggle button
            if (!$('.mets-mobile-nav-toggle').length) {
                var $toggle = $('<button class="mets-mobile-nav-toggle" aria-label="Toggle Navigation">' +
                    '<span class="dashicons dashicons-menu-alt3"></span>' +
                    '</button>');
                $('body').append($toggle);
            }

            // Create mobile navigation panel
            if (!$('.mets-mobile-nav-panel').length) {
                this.createNavigationPanel();
            }

            // Create FAB
            if (!$('.mets-fab').length) {
                this.createFloatingActionButton();
            }

            // Create breadcrumb
            if (!$('.mets-mobile-breadcrumb').length) {
                this.createBreadcrumb();
            }

            // Create status bar
            if (!$('.mets-mobile-status-bar').length) {
                this.createStatusBar();
            }
        },

        /**
         * Create navigation panel
         */
        createNavigationPanel: function() {
            var menuItems = this.getMenuItems();
            var quickActions = this.getQuickActions();

            var panelHTML = '<div class="mets-mobile-nav-overlay"></div>' +
                '<div class="mets-mobile-nav-panel">' +
                    '<div class="mets-mobile-nav-header">' +
                        '<h3>METS Dashboard</h3>' +
                        '<button class="mets-mobile-nav-close" aria-label="Close Navigation">&times;</button>' +
                    '</div>' +
                    '<ul class="mets-mobile-nav-menu">' + menuItems + '</ul>' +
                    '<div class="mets-mobile-quick-actions">' +
                        '<h4>Quick Actions</h4>' +
                        '<div class="mets-quick-action-grid">' + quickActions + '</div>' +
                    '</div>' +
                '</div>';

            $('body').append(panelHTML);
        },

        /**
         * Get menu items based on current WordPress admin menu
         */
        getMenuItems: function() {
            var items = '';
            var currentPage = this.getCurrentPage();

            // Main METS menu items
            var menuItems = [
                { icon: 'dashboard', text: 'Dashboard', url: 'admin.php?page=mets-dashboard', page: 'mets-dashboard' },
                { icon: 'tickets-alt', text: 'Tickets', url: 'admin.php?page=mets-all-tickets', page: 'mets-all-tickets' },
                { icon: 'building', text: 'Entities', url: 'admin.php?page=mets-entities', page: 'mets-entities' },
                { icon: 'groups', text: 'Customers', url: 'admin.php?page=mets-customers', page: 'mets-customers' },
                { icon: 'chart-bar', text: 'Reports', url: 'admin.php?page=mets-reports', page: 'mets-reports' },
                { icon: 'admin-tools', text: 'Automation', url: 'admin.php?page=mets-automation', page: 'mets-automation' },
                { icon: 'admin-settings', text: 'Settings', url: 'admin.php?page=mets-settings', page: 'mets-settings' }
            ];

            menuItems.forEach(function(item) {
                var isActive = currentPage === item.page ? ' current' : '';
                items += '<li>' +
                    '<a href="' + item.url + '" class="' + isActive + '">' +
                        '<span class="dashicons dashicons-' + item.icon + '"></span>' +
                        item.text +
                    '</a>' +
                '</li>';
            });

            return items;
        },

        /**
         * Get quick actions
         */
        getQuickActions: function() {
            var actions = [
                { icon: 'plus-alt', text: 'New Ticket', url: 'admin.php?page=mets-all-tickets&action=add' },
                { icon: 'search', text: 'Search', action: 'search' },
                { icon: 'email-alt', text: 'Messages', url: 'admin.php?page=mets-messages' },
                { icon: 'sos', text: 'Urgent', url: 'admin.php?page=mets-all-tickets&priority=critical' }
            ];

            var actionsHTML = '';
            actions.forEach(function(action) {
                var clickAction = action.url ? 'href="' + action.url + '"' : 'data-action="' + action.action + '"';
                actionsHTML += '<a ' + clickAction + ' class="mets-quick-action-btn">' +
                    '<span class="dashicons dashicons-' + action.icon + '"></span>' +
                    action.text +
                '</a>';
            });

            return actionsHTML;
        },

        /**
         * Create floating action button
         */
        createFloatingActionButton: function() {
            var fabHTML = '<button class="mets-fab" aria-label="Quick Actions">' +
                '<span class="dashicons dashicons-plus-alt2"></span>' +
            '</button>' +
            '<div class="mets-fab-menu">' +
                '<div class="mets-fab-item">' +
                    '<span class="mets-fab-item-label">New Ticket</span>' +
                    '<a href="admin.php?page=mets-all-tickets&action=add" class="mets-fab-item-btn">' +
                        '<span class="dashicons dashicons-tickets-alt"></span>' +
                    '</a>' +
                '</div>' +
                '<div class="mets-fab-item">' +
                    '<span class="mets-fab-item-label">Search</span>' +
                    '<button class="mets-fab-item-btn" data-action="search">' +
                        '<span class="dashicons dashicons-search"></span>' +
                    '</button>' +
                '</div>' +
                '<div class="mets-fab-item">' +
                    '<span class="mets-fab-item-label">Refresh</span>' +
                    '<button class="mets-fab-item-btn" data-action="refresh">' +
                        '<span class="dashicons dashicons-update"></span>' +
                    '</button>' +
                '</div>' +
            '</div>';

            $('body').append(fabHTML);
        },

        /**
         * Create breadcrumb navigation
         */
        createBreadcrumb: function() {
            var breadcrumbHTML = '<div class="mets-mobile-breadcrumb">' +
                '<a href="admin.php?page=mets-dashboard">Dashboard</a>' +
                '<span class="separator">›</span>' +
                '<span class="current-page"></span>' +
            '</div>';

            $('#wpbody-content').prepend(breadcrumbHTML);
        },

        /**
         * Create status bar
         */
        createStatusBar: function() {
            var statusHTML = '<div class="mets-mobile-status-bar">' +
                // WebSocket status removed - features deleted
                '<div class="mets-status-indicator">' +
                    '<span class="mets-status-dot" id="database-status"></span>' +
                    '<span>Database</span>' +
                '</div>' +
                '<div class="mets-status-indicator">' +
                    '<span class="mets-status-dot" id="email-status"></span>' +
                    '<span>Email</span>' +
                '</div>' +
            '</div>';

            $('#wpbody-content').prepend(statusHTML);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Toggle navigation
            $(document).on('click', '.mets-mobile-nav-toggle', function() {
                self.toggleNavigation();
            });

            // Close navigation
            $(document).on('click', '.mets-mobile-nav-close, .mets-mobile-nav-overlay', function() {
                self.closeNavigation();
            });

            // FAB toggle
            $(document).on('click', '.mets-fab', function() {
                self.toggleFABMenu();
            });

            // Quick action handlers
            $(document).on('click', '[data-action="search"]', function() {
                self.triggerSearch();
            });

            $(document).on('click', '[data-action="refresh"]', function() {
                self.refreshPage();
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Esc to close navigation
                if (e.keyCode === 27) {
                    self.closeNavigation();
                    self.closeFABMenu();
                }

                // Ctrl/Cmd + K for search
                if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                    e.preventDefault();
                    self.triggerSearch();
                }
            });

            // Handle window resize
            $(window).on('resize', function() {
                self.handleResize();
            });

            // Close FAB menu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.mets-fab, .mets-fab-menu').length) {
                    self.closeFABMenu();
                }
            });
        },

        /**
         * Toggle navigation panel
         */
        toggleNavigation: function() {
            var $panel = $('.mets-mobile-nav-panel');
            var $overlay = $('.mets-mobile-nav-overlay');
            var $toggle = $('.mets-mobile-nav-toggle');

            if ($panel.hasClass('active')) {
                this.closeNavigation();
            } else {
                $panel.addClass('active');
                $overlay.show();
                $toggle.addClass('active');
                $('body').addClass('mets-nav-open');
                
                // Focus management for accessibility
                $panel.find('.mets-mobile-nav-close').focus();
            }
        },

        /**
         * Close navigation panel
         */
        closeNavigation: function() {
            $('.mets-mobile-nav-panel').removeClass('active');
            $('.mets-mobile-nav-overlay').hide();
            $('.mets-mobile-nav-toggle').removeClass('active');
            $('body').removeClass('mets-nav-open');
        },

        /**
         * Toggle FAB menu
         */
        toggleFABMenu: function() {
            var $menu = $('.mets-fab-menu');
            var $fab = $('.mets-fab');

            if ($menu.hasClass('active')) {
                this.closeFABMenu();
            } else {
                $menu.addClass('active');
                $fab.addClass('active');
            }
        },

        /**
         * Close FAB menu
         */
        closeFABMenu: function() {
            $('.mets-fab-menu').removeClass('active');
            $('.mets-fab').removeClass('active');
        },

        /**
         * Update breadcrumb based on current page
         */
        updateBreadcrumb: function() {
            var currentPage = this.getCurrentPageTitle();
            $('.mets-mobile-breadcrumb .current-page').text(currentPage);
        },

        /**
         * Initialize status bar with real status
         */
        initStatusBar: function() {
            // this.updateWebSocketStatus(); // Removed - WebSocket features deleted
            this.updateDatabaseStatus();
            this.updateEmailStatus();

            // Update status every 30 seconds
            setInterval(() => {
                // this.updateWebSocketStatus(); // Removed - WebSocket features deleted
                this.updateDatabaseStatus();
                this.updateEmailStatus();
            }, 30000);
        },

        // Removed - WebSocket features deleted
        /*
         * Update WebSocket status
         *
        updateWebSocketStatus: function() {
            // Check if WebSocket is available from METS globals
            var isConnected = typeof window.metsWebSocket !== 'undefined' && window.metsWebSocket.connected;
            var $status = $('#websocket-status');
            
            $status.removeClass('warning error offline').addClass(isConnected ? '' : 'offline');
        },*/

        /**
         * Update database status
         */
        updateDatabaseStatus: function() {
            // Assume database is OK unless we get an error
            var $status = $('#database-status');
            $status.removeClass('warning error offline');
        },

        /**
         * Update email status
         */
        updateEmailStatus: function() {
            // Check email configuration
            var $status = $('#email-status');
            
            // You could make an AJAX call here to check email status
            // For now, assume it's configured
            $status.removeClass('warning error offline');
        },

        /**
         * Trigger search functionality
         */
        triggerSearch: function() {
            var $searchInput = $('.search-box input[name="s"]');
            
            if ($searchInput.length) {
                $searchInput.focus();
            } else {
                // Create a temporary search overlay
                this.createSearchOverlay();
            }
            
            this.closeFABMenu();
        },

        /**
         * Create search overlay
         */
        createSearchOverlay: function() {
            if ($('.mets-search-overlay').length) {
                return;
            }

            var searchHTML = '<div class="mets-search-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; display: flex; align-items: flex-start; justify-content: center; padding-top: 100px;">' +
                '<div class="mets-search-container" style="background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px;">' +
                    '<input type="text" placeholder="Search tickets, customers, entities..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">' +
                    '<button type="button" class="mets-search-close" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>' +
                '</div>' +
            '</div>';

            $('body').append(searchHTML);

            // Focus on search input
            $('.mets-search-overlay input').focus();

            // Close on escape or click outside
            $('.mets-search-overlay').on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('mets-search-close')) {
                    $(this).remove();
                }
            });

            // Handle search on enter
            $('.mets-search-overlay input').on('keydown', function(e) {
                if (e.keyCode === 13) { // Enter
                    var searchTerm = $(this).val();
                    if (searchTerm) {
                        window.location.href = 'admin.php?page=mets-all-tickets&s=' + encodeURIComponent(searchTerm);
                    }
                }
            });
        },

        /**
         * Refresh current page
         */
        refreshPage: function() {
            window.location.reload();
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            var width = $(window).width();
            
            // Close navigation on desktop
            if (width > 1024) {
                this.closeNavigation();
                this.closeFABMenu();
            }
        },

        /**
         * Get current page from URL
         */
        getCurrentPage: function() {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'dashboard';
        },

        /**
         * Get current page title
         */
        getCurrentPageTitle: function() {
            var page = this.getCurrentPage();
            var titles = {
                'mets-dashboard': 'Dashboard',
                'mets-tickets': 'Tickets',
                'mets-entities': 'Entities', 
                'mets-customers': 'Customers',
                'mets-reports': 'Reports',
                'mets-automation': 'Automation',
                'mets-settings': 'Settings'
            };

            return titles[page] || 'Dashboard';
        },

        /**
         * Add notification badge
         */
        addNotificationBadge: function(count, element) {
            if (count > 0) {
                var badge = '<span class="mets-notification-badge" style="background: #d63638; color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; position: absolute; top: -5px; right: -5px;">' + count + '</span>';
                $(element).css('position', 'relative').append(badge);
            }
        },

        /**
         * Update notification badges
         */
        updateNotificationBadges: function() {
            // This would be called from AJAX to update real-time notifications
            // Example: this.addNotificationBadge(5, '.mets-fab');
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize on METS admin pages
        if (window.location.href.indexOf('page=mets-') > -1 || 
            $('#adminmenu a[href*="page=mets-"]').length > 0) {
            METSMobileNav.init();
        }
    });

    // Export for global access
    window.METSMobileNav = METSMobileNav;

})(jQuery);