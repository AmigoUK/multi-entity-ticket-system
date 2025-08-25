/**
 * METS User Roles & Permissions Interface
 * 
 * Handles interactive functionality for the user roles and permissions management
 * 
 * @since 1.0.0
 */
(function($) {
    'use strict';

    class METSRolesPermissions {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTooltips();
            this.initFilters();
        }

        bindEvents() {
            // Tab navigation - REMOVED: Single page layout now
            // $(document).on('click', '.nav-tab', this.handleTabClick.bind(this));
            
            // Role details
            $(document).on('click', '.view-role-details', this.handleViewRoleDetails.bind(this));
            
            // User role changes
            $(document).on('click', '.change-user-role', this.handleChangeUserRole.bind(this));
            
            // FAQ accordion
            $(document).on('click', '.faq-question', this.handleFaqToggle.bind(this));
            
            // Role filter
            $(document).on('change', '#role-filter', this.handleRoleFilter.bind(this));
        }

        // REMOVED: Tab navigation no longer needed in single page layout
        /*
        handleTabClick(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const targetTab = $tab.data('tab');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').removeClass('active');
            $('#' + targetTab).addClass('active');
            
            // Store active tab in localStorage
            localStorage.setItem('mets_active_tab', targetTab);
        }
        */

        handleViewRoleDetails(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const roleKey = $button.data('role');
            
            $button.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Loading...');
            
            $.ajax({
                url: metsRolesAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_get_role_details',
                    nonce: metsRolesAjax.nonce,
                    role: roleKey
                },
                success: (response) => {
                    if (response.success) {
                        this.showRoleDetailsModal(roleKey, response.data);
                    } else {
                        this.showNotification(response.data.message || 'Error loading role details', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Network error. Please try again.', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).html('<i class="dashicons dashicons-visibility"></i> View Details');
                }
            });
        }

        showRoleDetailsModal(roleKey, roleData) {
            const modal = $(`
                <div class="mets-modal-overlay">
                    <div class="mets-modal">
                        <div class="mets-modal-header">
                            <h3>${roleData.role.display_name}</h3>
                            <button class="mets-modal-close" aria-label="Close">&times;</button>
                        </div>
                        <div class="mets-modal-content">
                            <div class="role-detail-summary">
                                <p>${roleData.role.description}</p>
                                <div class="role-stats">
                                    <div class="stat">
                                        <span class="stat-number">${roleData.capabilities_count}</span>
                                        <span class="stat-label">Permissions</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-number">${roleData.users_count}</span>
                                        <span class="stat-label">Users</span>
                                    </div>
                                </div>
                            </div>
                            <div class="role-capabilities-list">
                                <h4>Permissions:</h4>
                                <div class="capabilities-grid">
                                    ${Object.keys(roleData.role.capabilities)
                                        .filter(cap => roleData.role.capabilities[cap])
                                        .map(cap => `<div class="capability-item">${this.formatCapabilityName(cap)}</div>`)
                                        .join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(modal);
            modal.fadeIn(200);

            // Close modal events
            modal.on('click', '.mets-modal-close, .mets-modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    modal.fadeOut(200, () => modal.remove());
                }
            });

            // Close on escape key
            $(document).on('keydown.metsModal', (e) => {
                if (e.keyCode === 27) {
                    modal.fadeOut(200, () => modal.remove());
                    $(document).off('keydown.metsModal');
                }
            });
        }

        handleChangeUserRole(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const userId = $button.data('user-id');
            const $row = $button.closest('tr');
            const currentRole = $row.data('user-role');
            
            this.showChangeRoleModal(userId, currentRole, $row);
        }

        showChangeRoleModal(userId, currentRole, $row) {
            // Get available roles (this would come from localized data in a real implementation)
            const availableRoles = [
                { key: 'ticket_agent', name: 'Ticket Agent' },
                { key: 'senior_agent', name: 'Senior Agent' },
                { key: 'ticket_manager', name: 'Ticket Manager' },
                { key: 'support_supervisor', name: 'Support Supervisor' }
            ];

            const modal = $(`
                <div class="mets-modal-overlay">
                    <div class="mets-modal mets-change-role-modal">
                        <div class="mets-modal-header">
                            <h3>Change User Role</h3>
                            <button class="mets-modal-close" aria-label="Close">&times;</button>
                        </div>
                        <div class="mets-modal-content">
                            <form class="change-role-form">
                                <div class="form-field">
                                    <label for="new-role">Select New Role:</label>
                                    <select id="new-role" name="new_role" required>
                                        <option value="">-- Select Role --</option>
                                        ${availableRoles.map(role => 
                                            `<option value="${role.key}" ${role.key === currentRole ? 'selected' : ''}>${role.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="button button-secondary cancel-change">Cancel</button>
                                    <button type="submit" class="button button-primary">Update Role</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(modal);
            modal.fadeIn(200);

            // Handle form submission
            modal.on('submit', '.change-role-form', (e) => {
                e.preventDefault();
                
                const newRole = modal.find('#new-role').val();
                if (!newRole) {
                    this.showNotification('Please select a role', 'error');
                    return;
                }

                if (newRole === currentRole) {
                    modal.fadeOut(200, () => modal.remove());
                    return;
                }

                if (confirm(metsRolesAjax.strings.confirmChange)) {
                    this.updateUserRole(userId, newRole, $row, modal);
                }
            });

            // Close modal events
            modal.on('click', '.mets-modal-close, .cancel-change, .mets-modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    modal.fadeOut(200, () => modal.remove());
                }
            });
        }

        updateUserRole(userId, newRole, $row, modal) {
            const $submitBtn = modal.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Updating...');

            $.ajax({
                url: metsRolesAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_update_user_role',
                    nonce: metsRolesAjax.nonce,
                    user_id: userId,
                    role: newRole
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(metsRolesAjax.strings.updateSuccess, 'success');
                        this.updateUserRowRole($row, newRole);
                        modal.fadeOut(200, () => modal.remove());
                    } else {
                        this.showNotification(response.data.message || metsRolesAjax.strings.updateError, 'error');
                    }
                },
                error: () => {
                    this.showNotification('Network error. Please try again.', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html('Update Role');
                }
            });
        }

        updateUserRowRole($row, newRole) {
            // Update the role badge in the table
            const roleNames = {
                'ticket_agent': 'Ticket Agent',
                'senior_agent': 'Senior Agent', 
                'ticket_manager': 'Ticket Manager',
                'support_supervisor': 'Support Supervisor'
            };

            const roleName = roleNames[newRole] || newRole;
            const $roleCell = $row.find('.user-role');
            
            $roleCell.html(`
                <span class="role-badge role-${newRole}">
                    <i class="dashicons dashicons-admin-users"></i>
                    ${roleName}
                </span>
            `);
            
            // Update row data attribute
            $row.data('user-role', newRole);
        }

        handleFaqToggle(e) {
            const $question = $(e.currentTarget);
            const $faqItem = $question.closest('.faq-item');
            
            $faqItem.toggleClass('open');
        }

        handleRoleFilter(e) {
            const selectedRole = $(e.target).val();
            const $rows = $('.mets-users-table tbody tr');
            
            if (!selectedRole) {
                $rows.show();
                return;
            }
            
            $rows.each(function() {
                const $row = $(this);
                const userRole = $row.data('user-role');
                
                if (userRole === selectedRole) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }

        initTooltips() {
            // Add tooltips to permission indicators
            $('.permission-granted, .permission-denied').each(function() {
                const $el = $(this);
                const title = $el.attr('title');
                
                if (title) {
                    $el.tooltip({
                        position: { my: "center bottom-5", at: "center top" }
                    });
                }
            });
        }

        initFilters() {
            // Restore active tab from localStorage
            const activeTab = localStorage.getItem('mets_active_tab');
            if (activeTab && $('#' + activeTab).length) {
                $('.nav-tab').removeClass('nav-tab-active');
                $('[data-tab="' + activeTab + '"]').addClass('nav-tab-active');
                $('.tab-content').removeClass('active');
                $('#' + activeTab).addClass('active');
            }
        }

        formatCapabilityName(capability) {
            return capability.replace(/_/g, ' ')
                           .replace(/\b\w/g, l => l.toUpperCase());
        }

        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="mets-notification mets-notification-${type}">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                </div>
            `);

            $('body').append(notification);
            
            // Position notification
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 999999
            });

            // Show notification
            notification.slideDown(200);

            // Auto hide after 5 seconds
            setTimeout(() => {
                notification.slideUp(200, () => notification.remove());
            }, 5000);

            // Close button
            notification.on('click', '.notification-close', () => {
                notification.slideUp(200, () => notification.remove());
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new METSRolesPermissions();
    });

})(jQuery);

// Additional CSS for modals and notifications
jQuery(document).ready(function($) {
    if (!$('#mets-roles-dynamic-styles').length) {
        $('head').append(`
            <style id="mets-roles-dynamic-styles">
                /* Modal Styles */
                .mets-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 999999;
                    display: none;
                }
                
                .mets-modal {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                }
                
                .mets-modal-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 20px 25px;
                    border-bottom: 1px solid #ddd;
                    background: #f8f9fa;
                    border-radius: 8px 8px 0 0;
                }
                
                .mets-modal-header h3 {
                    margin: 0;
                    color: #23282d;
                    font-size: 18px;
                }
                
                .mets-modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .mets-modal-close:hover {
                    color: #000;
                }
                
                .mets-modal-content {
                    padding: 25px;
                }
                
                .role-detail-summary p {
                    margin: 0 0 20px 0;
                    color: #666;
                    line-height: 1.5;
                }
                
                .role-stats {
                    display: flex;
                    gap: 30px;
                    margin-bottom: 25px;
                }
                
                .stat {
                    text-align: center;
                }
                
                .stat-number {
                    display: block;
                    font-size: 24px;
                    font-weight: bold;
                    color: #0073aa;
                }
                
                .stat-label {
                    display: block;
                    font-size: 12px;
                    color: #666;
                    margin-top: 4px;
                }
                
                .role-capabilities-list h4 {
                    margin: 0 0 15px 0;
                    color: #23282d;
                }
                
                .capabilities-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 8px;
                }
                
                .capability-item {
                    padding: 8px 12px;
                    background: #e3f2fd;
                    color: #1976d2;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 500;
                }
                
                /* Change Role Modal */
                .mets-change-role-modal .mets-modal {
                    max-width: 400px;
                }
                
                .form-field {
                    margin-bottom: 20px;
                }
                
                .form-field label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #23282d;
                }
                
                .form-field select {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                }
                
                .form-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                    margin-top: 25px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                }
                
                /* Notifications */
                .mets-notification {
                    background: #fff;
                    border-left: 4px solid #0073aa;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    border-radius: 0 4px 4px 0;
                    min-width: 300px;
                    display: none;
                }
                
                .mets-notification-success {
                    border-left-color: #46b450;
                }
                
                .mets-notification-error {
                    border-left-color: #dc3232;
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 12px 16px;
                }
                
                .notification-message {
                    color: #23282d;
                    font-size: 14px;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #666;
                    margin-left: 15px;
                }
                
                .notification-close:hover {
                    color: #000;
                }
                
                /* Spinning animation */
                .dashicons.spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                /* Mobile responsive */
                @media (max-width: 600px) {
                    .mets-modal {
                        width: 95%;
                        margin: 20px;
                    }
                    
                    .capabilities-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .role-stats {
                        justify-content: center;
                    }
                }
            </style>
        `);
    }
});