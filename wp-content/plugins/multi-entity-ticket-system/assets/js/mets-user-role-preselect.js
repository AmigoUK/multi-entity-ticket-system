/**
 * METS User Role Pre-selection
 * 
 * Automatically pre-selects the role dropdown when creating a new user
 * based on the mets_role URL parameter
 * 
 * @since 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if we're on the user-new.php page
        if (window.location.pathname.indexOf('user-new.php') === -1) {
            return;
        }

        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const metsRole = urlParams.get('mets_role');
        
        if (!metsRole) {
            return;
        }

        // Find the role dropdown
        const $roleSelect = $('#role');
        
        if ($roleSelect.length === 0) {
            return;
        }

        // Pre-select the role if it exists in the dropdown
        if ($roleSelect.find('option[value="' + metsRole + '"]').length > 0) {
            $roleSelect.val(metsRole);
            
            // Add a visual indicator that the role was pre-selected
            const roleLabels = {
                'ticket_agent': 'Ticket Agent',
                'senior_agent': 'Senior Agent',
                'ticket_manager': 'Ticket Manager',
                'support_supervisor': 'Support Supervisor'
            };
            
            const roleName = roleLabels[metsRole] || metsRole;
            
            // Add a notice above the form
            const noticeHtml = '<div class="notice notice-info mets-role-notice" style="margin-top: 20px;">' +
                '<p><strong>METS Team Management:</strong> Creating a new user with <strong>' + roleName + '</strong> privileges. ' +
                'The role has been pre-selected for you.</p></div>';
            
            $('.wrap h1').after(noticeHtml);
            
            // Highlight the role dropdown briefly
            $roleSelect.css({
                'background-color': '#e8f5e9',
                'border-color': '#4caf50',
                'transition': 'all 0.3s ease'
            });
            
            // Remove highlight after 3 seconds
            setTimeout(function() {
                $roleSelect.css({
                    'background-color': '',
                    'border-color': ''
                });
            }, 3000);
        }
    });

})(jQuery);