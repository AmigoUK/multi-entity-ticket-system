<?php
/**
 * Comprehensive Dashboard for METS
 *
 * @package    METS
 * @subpackage METS/admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class METS_Comprehensive_Dashboard {
    
    /**
     * Display the comprehensive dashboard
     */
    public function display_dashboard() {
        ?>
        <div class="wrap mets-dashboard-wrap">
            <h1 class="wp-heading-inline"><?php _e( 'METS Dashboard', METS_TEXT_DOMAIN ); ?></h1>
            
            <!-- Quick Navigation -->
            <div class="mets-dashboard-navigation">
                <ul class="mets-nav-tabs">
                    <li><a href="#quick-start" class="active"><?php _e( 'Quick Start', METS_TEXT_DOMAIN ); ?></a></li>
                    <li><a href="#system-overview"><?php _e( 'System Overview', METS_TEXT_DOMAIN ); ?></a></li>
                    <li><a href="#user-guide"><?php _e( 'User Guide', METS_TEXT_DOMAIN ); ?></a></li>
                    <li><a href="#shortcodes"><?php _e( 'Shortcodes', METS_TEXT_DOMAIN ); ?></a></li>
                    <li><a href="#developer"><?php _e( 'Developer', METS_TEXT_DOMAIN ); ?></a></li>
                    <li><a href="#help"><?php _e( 'Help & Support', METS_TEXT_DOMAIN ); ?></a></li>
                </ul>
            </div>
            
            <!-- Dashboard Content -->
            <div class="mets-dashboard-content">
                
                <!-- Quick Start Section -->
                <div id="quick-start" class="mets-dashboard-section active">
                    <h2><?php _e( 'Welcome to METS - Multi-Entity Ticket System', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <div class="mets-welcome-panel">
                        <div class="mets-welcome-video">
                            <div class="mets-video-placeholder">
                                {vid1:mets-welcome-intro:Welcome video introducing METS system, showing main features and navigation (3-5 minutes)}
                            </div>
                        </div>
                        
                        <div class="mets-welcome-content">
                            <h3><?php _e( 'Getting Started with METS', METS_TEXT_DOMAIN ); ?></h3>
                            <p><?php _e( 'METS is a powerful multi-entity ticket management system that helps you manage customer support across multiple departments or businesses.', METS_TEXT_DOMAIN ); ?></p>
                            
                            <div class="mets-quick-actions">
                                <h4><?php _e( 'Quick Actions', METS_TEXT_DOMAIN ); ?></h4>
                                <div class="mets-action-cards">
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-add-ticket' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <span><?php _e( 'Create New Ticket', METS_TEXT_DOMAIN ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-entities' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-building"></span>
                                        <span><?php _e( 'Add New Entity', METS_TEXT_DOMAIN ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'users.php' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-groups"></span>
                                        <span><?php _e( 'Manage Users & Agents', METS_TEXT_DOMAIN ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <span><?php _e( 'System Settings', METS_TEXT_DOMAIN ); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- First Steps Checklist -->
                    <div class="mets-setup-checklist">
                        <h3><?php _e( 'Initial Setup Checklist', METS_TEXT_DOMAIN ); ?></h3>
                        <div class="mets-checklist">
                            <?php $this->render_setup_checklist(); ?>
                        </div>
                    </div>
                </div>
                
                <!-- System Overview Section -->
                <div id="system-overview" class="mets-dashboard-section">
                    <h2><?php _e( 'System Overview', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <!-- KPIs -->
                    <div class="mets-kpi-grid">
                        <?php $this->render_kpis(); ?>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="mets-recent-activity">
                        <h3><?php _e( 'Recent Activity', METS_TEXT_DOMAIN ); ?></h3>
                        <?php $this->render_recent_activity(); ?>
                    </div>
                </div>
                
                <!-- User Guide Section -->
                <div id="user-guide" class="mets-dashboard-section">
                    <h2><?php _e( 'User Guide', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <!-- User Roles -->
                    <div class="mets-user-roles">
                        <h3><?php _e( 'User Roles & Permissions', METS_TEXT_DOMAIN ); ?></h3>
                        
                        <div class="mets-roles-grid">
                            <!-- Customer Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👤</div>
                                <h4><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Basic customer who can submit tickets and track their status', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'Submit new tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'View own tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Reply to tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Search knowledge base', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Agent Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👷</div>
                                <h4><?php _e( 'Support Agent', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Basic support agent who handles assigned tickets', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'Handle assigned tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Reply to customers', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Access knowledge base', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Log time spent', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Senior Agent Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">🎖️</div>
                                <h4><?php _e( 'Senior Agent', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Experienced agent with advanced permissions', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'All agent capabilities', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Edit any ticket', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Reassign tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Merge & escalate tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Publish KB articles', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Manager Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👔</div>
                                <h4><?php _e( 'Ticket Manager', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Manages ticket workflows and team assignments', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'All senior agent capabilities', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Manage agents & entities', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Configure SLA rules', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Access all reports', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Configure automations', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Supervisor Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">🌟</div>
                                <h4><?php _e( 'Support Supervisor', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'System-wide access and oversight capabilities', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'All manager capabilities', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'System configuration', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Email templates', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'Security settings', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'API management', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Admin Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👑</div>
                                <h4><?php _e( 'Ticket Administrator', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Full system control and administration', METS_TEXT_DOMAIN ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php _e( 'Complete system control', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'User management', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'System maintenance', METS_TEXT_DOMAIN ); ?></li>
                                    <li>✓ <?php _e( 'All capabilities', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mets-role-hierarchy">
                            <h4><?php _e( 'Role Hierarchy', METS_TEXT_DOMAIN ); ?></h4>
                            <div class="mets-hierarchy-diagram">
                                <div class="mets-video-placeholder">
                                    {vid2:role-hierarchy-explained:Visual explanation of role hierarchy and permission inheritance (2-3 minutes)}
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Management Guide -->
                        <div class="mets-user-management-guide">
                            <h4><?php _e( 'How to Add & Manage Users', METS_TEXT_DOMAIN ); ?></h4>
                            
                            <div class="mets-user-guide-steps">
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">1</div>
                                    <div class="mets-step-content">
                                        <h5><?php _e( 'Add New Users', METS_TEXT_DOMAIN ); ?></h5>
                                        <p><?php _e( 'Go to WordPress Users menu to add new team members. You can create new users or invite existing ones.', METS_TEXT_DOMAIN ); ?></p>
                                        <a href="<?php echo admin_url( 'user-new.php' ); ?>" class="button button-primary">
                                            <?php _e( 'Add New User', METS_TEXT_DOMAIN ); ?>
                                        </a>
                                        <a href="<?php echo admin_url( 'users.php' ); ?>" class="button">
                                            <?php _e( 'Manage Users', METS_TEXT_DOMAIN ); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">2</div>
                                    <div class="mets-step-content">
                                        <h5><?php _e( 'Assign METS Roles', METS_TEXT_DOMAIN ); ?></h5>
                                        <p><?php _e( 'When creating or editing users, select their METS role from the Role dropdown:', METS_TEXT_DOMAIN ); ?></p>
                                        <ul class="mets-role-list">
                                            <li><strong>Ticket Customer</strong> - <?php _e( 'For end customers', METS_TEXT_DOMAIN ); ?></li>
                                            <li><strong>Ticket Agent</strong> - <?php _e( 'For basic support staff', METS_TEXT_DOMAIN ); ?></li>
                                            <li><strong>Senior Agent</strong> - <?php _e( 'For experienced agents', METS_TEXT_DOMAIN ); ?></li>
                                            <li><strong>Ticket Manager</strong> - <?php _e( 'For team leaders', METS_TEXT_DOMAIN ); ?></li>
                                            <li><strong>Support Supervisor</strong> - <?php _e( 'For department heads', METS_TEXT_DOMAIN ); ?></li>
                                            <li><strong>Ticket Administrator</strong> - <?php _e( 'For system admins', METS_TEXT_DOMAIN ); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">3</div>
                                    <div class="mets-step-content">
                                        <h5><?php _e( 'Assign to Entities', METS_TEXT_DOMAIN ); ?></h5>
                                        <p><?php _e( 'After assigning roles, go to METS Agents page to assign users to specific entities/departments.', METS_TEXT_DOMAIN ); ?></p>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-agents' ); ?>" class="button">
                                            <?php _e( 'Configure Agent Assignments', METS_TEXT_DOMAIN ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mets-user-tips">
                                <h5><?php _e( 'Pro Tips:', METS_TEXT_DOMAIN ); ?></h5>
                                <ul>
                                    <li><?php _e( 'You can bulk-edit multiple users at once from the Users page', METS_TEXT_DOMAIN ); ?></li>
                                    <li><?php _e( 'Use the "Send user notification" option to email login details to new users', METS_TEXT_DOMAIN ); ?></li>
                                    <li><?php _e( 'Agents can be assigned to multiple entities for cross-department support', METS_TEXT_DOMAIN ); ?></li>
                                    <li><?php _e( 'Change user roles anytime from Users → Edit User → Role dropdown', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Core Features -->
                    <div class="mets-core-features">
                        <h3><?php _e( 'Core Features', METS_TEXT_DOMAIN ); ?></h3>
                        
                        <div class="mets-feature-accordion">
                            <!-- Ticket Management -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-tickets-alt"></span>
                                    <?php _e( 'Ticket Management', METS_TEXT_DOMAIN ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php _e( 'Complete ticket lifecycle management from creation to resolution.', METS_TEXT_DOMAIN ); ?></p>
                                    <ul>
                                        <li><strong><?php _e( 'Create Tickets:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Submit tickets via forms, email, or admin panel', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Assign & Route:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Automatically or manually assign to agents', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Track Status:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Monitor ticket progress through customizable statuses', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Prioritize:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Set priority levels for urgent issues', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Collaborate:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Internal notes and team collaboration tools', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets' ); ?>" class="button"><?php _e( 'View Tickets', METS_TEXT_DOMAIN ); ?></a>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-add-ticket' ); ?>" class="button"><?php _e( 'Create Ticket', METS_TEXT_DOMAIN ); ?></a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Entity Management -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php _e( 'Entity Management', METS_TEXT_DOMAIN ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php _e( 'Manage multiple departments, branches, or businesses from one system.', METS_TEXT_DOMAIN ); ?></p>
                                    <ul>
                                        <li><strong><?php _e( 'Multiple Entities:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Create unlimited entities (departments/businesses)', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Entity Settings:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Custom email, SLA, and business hours per entity', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Agent Assignment:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Assign agents to specific entities', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Entity Reports:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Performance metrics per entity', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                    <div class="mets-video-placeholder">
                                        {vid3:entity-management-guide:How to create and manage entities, assign agents, and configure entity-specific settings (5 minutes)}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Knowledge Base -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-book"></span>
                                    <?php _e( 'Knowledge Base', METS_TEXT_DOMAIN ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php _e( 'Self-service knowledge base to reduce ticket volume.', METS_TEXT_DOMAIN ); ?></p>
                                    <ul>
                                        <li><strong><?php _e( 'Article Management:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Create, edit, and organize help articles', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Categories & Tags:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Organize content for easy discovery', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Search Integration:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Smart search suggestions in ticket forms', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Analytics:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Track article views and effectiveness', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-knowledge-base' ); ?>" class="button"><?php _e( 'Manage KB', METS_TEXT_DOMAIN ); ?></a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Integration -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php _e( 'Email Integration', METS_TEXT_DOMAIN ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php _e( 'Seamless email communication and ticket creation.', METS_TEXT_DOMAIN ); ?></p>
                                    <ul>
                                        <li><strong><?php _e( 'Email to Ticket:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Convert incoming emails to tickets automatically', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Email Templates:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Customizable notification templates', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'SMTP Configuration:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Use your own email server', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Email Queue:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Reliable email delivery with retry mechanism', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                    <div class="mets-video-placeholder">
                                        {vid4:email-setup-tutorial:Complete email integration setup including SMTP configuration and email templates (7 minutes)}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Automation & SLA -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                    <?php _e( 'Automation & SLA', METS_TEXT_DOMAIN ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php _e( 'Automate workflows and ensure timely responses.', METS_TEXT_DOMAIN ); ?></p>
                                    <ul>
                                        <li><strong><?php _e( 'SLA Rules:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Set response and resolution time targets', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Auto-Assignment:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Route tickets based on rules', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Escalation:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Automatic escalation for overdue tickets', METS_TEXT_DOMAIN ); ?></li>
                                        <li><strong><?php _e( 'Business Hours:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'SLA tracking based on working hours', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="button"><?php _e( 'Configure SLA', METS_TEXT_DOMAIN ); ?></a>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="button"><?php _e( 'Automation Rules', METS_TEXT_DOMAIN ); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shortcodes Section -->
                <div id="shortcodes" class="mets-dashboard-section">
                    <h2><?php _e( 'Shortcodes Reference', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <div class="mets-shortcodes-intro">
                        <p><?php _e( 'Use these shortcodes to add METS functionality to any page or post. Click the copy button to copy the shortcode to your clipboard.', METS_TEXT_DOMAIN ); ?></p>
                    </div>
                    
                    <div class="mets-shortcodes-grid">
                        <!-- Ticket Form -->
                        <div class="mets-shortcode-card">
                            <h3><?php _e( 'Ticket Submission Form', METS_TEXT_DOMAIN ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[ticket_form]</code>
                                <button class="mets-copy-btn" data-copy="[ticket_form]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e( 'Displays a public ticket submission form with KB search integration.', METS_TEXT_DOMAIN ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php _e( 'Parameters:', METS_TEXT_DOMAIN ); ?></h4>
                                <ul>
                                    <li><code>entity_id="123"</code> - <?php _e( 'Pre-select specific entity', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>kb_search="yes"</code> - <?php _e( 'Enable KB search (default: yes)', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>category="billing"</code> - <?php _e( 'Pre-select category', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                            <div class="mets-shortcode-example">
                                <h4><?php _e( 'Example:', METS_TEXT_DOMAIN ); ?></h4>
                                <div class="mets-shortcode-code">
                                    <code>[ticket_form entity_id="4" category="support"]</code>
                                    <button class="mets-copy-btn" data-copy='[ticket_form entity_id="4" category="support"]'>
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Portal -->
                        <div class="mets-shortcode-card">
                            <h3><?php _e( 'Customer Portal', METS_TEXT_DOMAIN ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[customer_portal]</code>
                                <button class="mets-copy-btn" data-copy="[customer_portal]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e( 'Customer login area to view and manage their tickets.', METS_TEXT_DOMAIN ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php _e( 'Parameters:', METS_TEXT_DOMAIN ); ?></h4>
                                <ul>
                                    <li><code>show_closed="yes"</code> - <?php _e( 'Show closed tickets', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>tickets_per_page="10"</code> - <?php _e( 'Number of tickets to display', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- KB Search -->
                        <div class="mets-shortcode-card">
                            <h3><?php _e( 'Knowledge Base Search', METS_TEXT_DOMAIN ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[kb_search]</code>
                                <button class="mets-copy-btn" data-copy="[kb_search]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e( 'Standalone knowledge base search widget.', METS_TEXT_DOMAIN ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php _e( 'Parameters:', METS_TEXT_DOMAIN ); ?></h4>
                                <ul>
                                    <li><code>placeholder="Search for help..."</code> - <?php _e( 'Search box placeholder', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>show_categories="yes"</code> - <?php _e( 'Display category list', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>show_popular="5"</code> - <?php _e( 'Show popular articles', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Entity List -->
                        <div class="mets-shortcode-card">
                            <h3><?php _e( 'Entity Directory', METS_TEXT_DOMAIN ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[entity_list]</code>
                                <button class="mets-copy-btn" data-copy="[entity_list]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e( 'Display a list of all active entities/departments.', METS_TEXT_DOMAIN ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php _e( 'Parameters:', METS_TEXT_DOMAIN ); ?></h4>
                                <ul>
                                    <li><code>show_contact="yes"</code> - <?php _e( 'Show contact info', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>columns="3"</code> - <?php _e( 'Number of columns', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Ticket Stats -->
                        <div class="mets-shortcode-card">
                            <h3><?php _e( 'Ticket Statistics', METS_TEXT_DOMAIN ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[ticket_stats]</code>
                                <button class="mets-copy-btn" data-copy="[ticket_stats]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e( 'Display ticket statistics and metrics (admin only).', METS_TEXT_DOMAIN ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php _e( 'Parameters:', METS_TEXT_DOMAIN ); ?></h4>
                                <ul>
                                    <li><code>type="summary"</code> - <?php _e( 'Stats type: summary, chart, table', METS_TEXT_DOMAIN ); ?></li>
                                    <li><code>period="week"</code> - <?php _e( 'Time period: day, week, month', METS_TEXT_DOMAIN ); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mets-shortcode-video">
                        <h3><?php _e( 'Shortcode Usage Tutorial', METS_TEXT_DOMAIN ); ?></h3>
                        <div class="mets-video-placeholder">
                            {vid5:shortcode-tutorial:How to use METS shortcodes in pages and posts, with live examples (4 minutes)}
                        </div>
                    </div>
                </div>
                
                <!-- Developer Section -->
                <div id="developer" class="mets-dashboard-section">
                    <h2><?php _e( 'Developer Resources', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <div class="mets-developer-grid">
                        <!-- REST API -->
                        <div class="mets-dev-card">
                            <h3><?php _e( 'REST API Endpoints', METS_TEXT_DOMAIN ); ?></h3>
                            <p><?php _e( 'Integrate METS with external applications using our REST API.', METS_TEXT_DOMAIN ); ?></p>
                            
                            <h4><?php _e( 'Available Endpoints:', METS_TEXT_DOMAIN ); ?></h4>
                            <div class="mets-api-endpoints">
                                <div class="mets-endpoint">
                                    <code>GET /wp-json/mets/v1/tickets</code>
                                    <p><?php _e( 'List all tickets', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>POST /wp-json/mets/v1/tickets</code>
                                    <p><?php _e( 'Create new ticket', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>GET /wp-json/mets/v1/tickets/{id}</code>
                                    <p><?php _e( 'Get ticket details', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>PUT /wp-json/mets/v1/tickets/{id}</code>
                                    <p><?php _e( 'Update ticket', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>POST /wp-json/mets/v1/tickets/{id}/replies</code>
                                    <p><?php _e( 'Add reply to ticket', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                            </div>
                            
                            <div class="mets-api-auth">
                                <h4><?php _e( 'Authentication:', METS_TEXT_DOMAIN ); ?></h4>
                                <p><?php _e( 'Use Application Passwords or JWT tokens for authentication.', METS_TEXT_DOMAIN ); ?></p>
                                <div class="mets-code-example">
                                    <pre>curl -X GET https://yoursite.com/wp-json/mets/v1/tickets \
  -H "Authorization: Bearer YOUR_TOKEN"</pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hooks & Filters -->
                        <div class="mets-dev-card">
                            <h3><?php _e( 'Hooks & Filters', METS_TEXT_DOMAIN ); ?></h3>
                            <p><?php _e( 'Extend METS functionality using WordPress hooks.', METS_TEXT_DOMAIN ); ?></p>
                            
                            <h4><?php _e( 'Common Hooks:', METS_TEXT_DOMAIN ); ?></h4>
                            <div class="mets-hooks-list">
                                <div class="mets-hook">
                                    <code>mets_ticket_created</code>
                                    <p><?php _e( 'Fired after ticket creation', METS_TEXT_DOMAIN ); ?></p>
                                    <pre>add_action( 'mets_ticket_created', function( $ticket_id ) {
    // Your custom code
}, 10, 1 );</pre>
                                </div>
                                
                                <div class="mets-hook">
                                    <code>mets_ticket_status_changed</code>
                                    <p><?php _e( 'Fired when ticket status changes', METS_TEXT_DOMAIN ); ?></p>
                                    <pre>add_action( 'mets_ticket_status_changed', function( $ticket_id, $old_status, $new_status ) {
    // Your custom code
}, 10, 3 );</pre>
                                </div>
                                
                                <div class="mets-hook">
                                    <code>mets_ticket_form_fields</code>
                                    <p><?php _e( 'Filter to add custom fields to ticket form', METS_TEXT_DOMAIN ); ?></p>
                                    <pre>add_filter( 'mets_ticket_form_fields', function( $fields ) {
    $fields['custom_field'] = array(
        'label' => 'Custom Field',
        'type'  => 'text',
        'required' => false
    );
    return $fields;
}, 10, 1 );</pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Database Schema -->
                        <div class="mets-dev-card">
                            <h3><?php _e( 'Database Schema', METS_TEXT_DOMAIN ); ?></h3>
                            <p><?php _e( 'METS uses 26 custom database tables for optimal performance.', METS_TEXT_DOMAIN ); ?></p>
                            
                            <h4><?php _e( 'Main Tables:', METS_TEXT_DOMAIN ); ?></h4>
                            <ul class="mets-db-tables">
                                <li><code>mets_tickets</code> - <?php _e( 'Main tickets table', METS_TEXT_DOMAIN ); ?></li>
                                <li><code>mets_replies</code> - <?php _e( 'Ticket replies and notes', METS_TEXT_DOMAIN ); ?></li>
                                <li><code>mets_entities</code> - <?php _e( 'Entity/department data', METS_TEXT_DOMAIN ); ?></li>
                                <li><code>mets_customers</code> - <?php _e( 'Customer information', METS_TEXT_DOMAIN ); ?></li>
                                <li><code>mets_kb_articles</code> - <?php _e( 'Knowledge base articles', METS_TEXT_DOMAIN ); ?></li>
                                <li><code>mets_sla_rules</code> - <?php _e( 'SLA configuration', METS_TEXT_DOMAIN ); ?></li>
                            </ul>
                            
                            <div class="mets-db-diagram">
                                <a href="<?php echo METS_PLUGIN_URL; ?>docs/database-schema.pdf" target="_blank" class="button">
                                    <?php _e( 'View Complete Schema', METS_TEXT_DOMAIN ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mets-developer-video">
                        <h3><?php _e( 'Developer Tutorial', METS_TEXT_DOMAIN ); ?></h3>
                        <div class="mets-video-placeholder">
                            {vid6:developer-tutorial:Building custom integrations with METS API and hooks (10 minutes)}
                        </div>
                    </div>
                </div>
                
                <!-- Help & Support Section -->
                <div id="help" class="mets-dashboard-section">
                    <h2><?php _e( 'Help & Support', METS_TEXT_DOMAIN ); ?></h2>
                    
                    <div class="mets-help-grid">
                        <!-- FAQs -->
                        <div class="mets-help-card">
                            <h3><?php _e( 'Frequently Asked Questions', METS_TEXT_DOMAIN ); ?></h3>
                            
                            <div class="mets-faq-list">
                                <div class="mets-faq-item">
                                    <h4><?php _e( 'How do I create my first entity?', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Go to METS Tickets → Entities → Add New Entity. Fill in the basic information like name, email, and business hours.', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php _e( 'Can agents work on multiple entities?', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Yes! Agents can be assigned to multiple entities. Go to Agents management to configure entity access.', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php _e( 'How do I set up email notifications?', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Configure SMTP settings in Settings → Email Configuration. Then customize email templates in Settings → Email Templates.', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php _e( 'What are SLA rules?', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'SLA (Service Level Agreement) rules define response and resolution time targets for tickets based on priority and other conditions.', METS_TEXT_DOMAIN ); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Troubleshooting -->
                        <div class="mets-help-card">
                            <h3><?php _e( 'Troubleshooting', METS_TEXT_DOMAIN ); ?></h3>
                            
                            <div class="mets-troubleshooting">
                                <div class="mets-trouble-item">
                                    <h4><?php _e( 'Emails not sending', METS_TEXT_DOMAIN ); ?></h4>
                                    <ul>
                                        <li><?php _e( 'Check SMTP settings', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Verify email queue is processing', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Check error logs', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="mets-trouble-item">
                                    <h4><?php _e( 'Shortcodes not working', METS_TEXT_DOMAIN ); ?></h4>
                                    <ul>
                                        <li><?php _e( 'Ensure METS plugin is active', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Check for theme conflicts', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Verify page builder compatibility', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="mets-trouble-item">
                                    <h4><?php _e( 'Performance issues', METS_TEXT_DOMAIN ); ?></h4>
                                    <ul>
                                        <li><?php _e( 'Enable caching', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Optimize database', METS_TEXT_DOMAIN ); ?></li>
                                        <li><?php _e( 'Check server resources', METS_TEXT_DOMAIN ); ?></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mets-debug-tools">
                                <h4><?php _e( 'Debug Tools', METS_TEXT_DOMAIN ); ?></h4>
                                <a href="<?php echo admin_url( 'admin.php?page=mets-settings&tab=tools' ); ?>" class="button">
                                    <?php _e( 'System Information', METS_TEXT_DOMAIN ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=mets-settings&tab=logs' ); ?>" class="button">
                                    <?php _e( 'View Error Logs', METS_TEXT_DOMAIN ); ?>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Contact Support -->
                        <div class="mets-help-card">
                            <h3><?php _e( 'Contact Support', METS_TEXT_DOMAIN ); ?></h3>
                            
                            <div class="mets-support-options">
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-book"></span>
                                    <h4><?php _e( 'Documentation', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Comprehensive guides and tutorials', METS_TEXT_DOMAIN ); ?></p>
                                    <a href="https://docs.mets-tickets.com" target="_blank" class="button">
                                        <?php _e( 'View Docs', METS_TEXT_DOMAIN ); ?>
                                    </a>
                                </div>
                                
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <h4><?php _e( 'Community Forum', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Get help from the community', METS_TEXT_DOMAIN ); ?></p>
                                    <a href="https://community.mets-tickets.com" target="_blank" class="button">
                                        <?php _e( 'Visit Forum', METS_TEXT_DOMAIN ); ?>
                                    </a>
                                </div>
                                
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <h4><?php _e( 'Email Support', METS_TEXT_DOMAIN ); ?></h4>
                                    <p><?php _e( 'Direct support for license holders', METS_TEXT_DOMAIN ); ?></p>
                                    <a href="mailto:support@mets-tickets.com" class="button button-primary">
                                        <?php _e( 'Email Us', METS_TEXT_DOMAIN ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Requirements -->
                    <div class="mets-system-requirements">
                        <h3><?php _e( 'System Requirements', METS_TEXT_DOMAIN ); ?></h3>
                        <div class="mets-requirements-grid">
                            <div class="mets-requirement">
                                <strong><?php _e( 'WordPress:', METS_TEXT_DOMAIN ); ?></strong> 5.0 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php _e( 'PHP:', METS_TEXT_DOMAIN ); ?></strong> 7.2 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php _e( 'MySQL:', METS_TEXT_DOMAIN ); ?></strong> 5.6 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php _e( 'Memory:', METS_TEXT_DOMAIN ); ?></strong> 128MB minimum
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
        /* Dashboard Styles */
        .mets-dashboard-wrap {
            margin-top: 20px;
            max-width: 1200px;
        }
        
        .mets-dashboard-navigation {
            background: #fff;
            border: 1px solid #ccd0d4;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mets-nav-tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 1px solid #ccd0d4;
        }
        
        .mets-nav-tabs li {
            margin: 0;
        }
        
        .mets-nav-tabs a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #23282d;
            border-bottom: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .mets-nav-tabs a:hover {
            background: #f1f1f1;
        }
        
        .mets-nav-tabs a.active {
            border-bottom-color: #0073aa;
            font-weight: 600;
        }
        
        .mets-dashboard-section {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .mets-dashboard-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Welcome Panel */
        .mets-welcome-panel {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 30px;
        }
        
        .mets-video-placeholder {
            background: #f1f1f1;
            border: 2px dashed #ccd0d4;
            padding: 40px;
            text-align: center;
            color: #666;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }
        
        .mets-welcome-video {
            flex: 1;
        }
        
        .mets-welcome-content {
            flex: 1;
        }
        
        /* Action Cards */
        .mets-action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .mets-action-card {
            background: #f1f1f1;
            border: 1px solid #ccd0d4;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #23282d;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .mets-action-card:hover {
            background: #e5e5e5;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,.1);
        }
        
        .mets-action-card .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: #0073aa;
        }
        
        /* Role Cards */
        .mets-roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mets-role-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        
        .mets-role-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .mets-role-card h4 {
            text-align: center;
            margin: 10px 0;
            color: #23282d;
        }
        
        .mets-role-capabilities {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        
        .mets-role-capabilities li {
            padding: 5px 0;
            color: #666;
        }
        
        /* Accordion */
        .mets-accordion-header {
            background: #f1f1f1;
            border: 1px solid #ccd0d4;
            padding: 15px;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.3s;
        }
        
        .mets-accordion-header:hover {
            background: #e5e5e5;
        }
        
        .mets-accordion-header.active {
            background: #0073aa;
            color: #fff;
        }
        
        .mets-accordion-content {
            display: none;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: 0;
            padding: 20px;
        }
        
        .mets-accordion-content.active {
            display: block;
        }
        
        /* Shortcode Cards */
        .mets-shortcodes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mets-shortcode-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            border-radius: 4px;
        }
        
        .mets-shortcode-code {
            background: #f1f1f1;
            padding: 10px 40px 10px 15px;
            border-radius: 4px;
            position: relative;
            margin: 10px 0;
            font-family: monospace;
        }
        
        .mets-copy-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .mets-copy-btn:hover {
            background: #005a87;
        }
        
        .mets-copy-btn.copied {
            background: #46b450;
        }
        
        /* Developer Cards */
        .mets-developer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mets-dev-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            border-radius: 4px;
        }
        
        .mets-endpoint {
            background: #f8f8f8;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #0073aa;
        }
        
        .mets-endpoint code {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* KPI Grid */
        .mets-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .mets-kpi-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
        }
        
        .mets-kpi-card .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 10px;
        }
        
        .mets-kpi-value {
            font-size: 36px;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        
        .mets-kpi-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Help Cards */
        .mets-help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mets-help-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            border-radius: 4px;
        }
        
        .mets-faq-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .mets-faq-item:last-child {
            border-bottom: none;
        }
        
        .mets-faq-item h4 {
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        /* Support Options */
        .mets-support-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .mets-support-item {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 4px;
        }
        
        .mets-support-item .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        /* Checklist */
        .mets-checklist {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            border-radius: 4px;
        }
        
        .mets-checklist-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .mets-checklist-item:last-child {
            border-bottom: none;
        }
        
        .mets-checklist-status {
            margin-right: 15px;
            font-size: 20px;
        }
        
        .mets-checklist-status.complete {
            color: #46b450;
        }
        
        .mets-checklist-status.incomplete {
            color: #ccc;
        }
        
        /* User Management Guide */
        .mets-user-management-guide {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .mets-user-guide-steps {
            margin: 20px 0;
        }
        
        .mets-guide-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .mets-guide-step:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .mets-step-number {
            background: #0073aa;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .mets-step-content {
            flex: 1;
        }
        
        .mets-step-content h5 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 16px;
        }
        
        .mets-role-list {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .mets-role-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .mets-role-list li:last-child {
            border-bottom: none;
        }
        
        .mets-user-tips {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .mets-user-tips h5 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .mets-user-tips ul {
            margin: 0;
            color: #856404;
        }
        
        .mets-user-tips li {
            margin-bottom: 5px;
        }
        
        /* System Requirements */
        .mets-requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            background: #f8f8f8;
            padding: 20px;
            border-radius: 4px;
        }
        
        .mets-requirement {
            padding: 10px;
            background: #fff;
            border-radius: 3px;
            text-align: center;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab Navigation
            $('.mets-nav-tabs a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                // Update active states
                $('.mets-nav-tabs a').removeClass('active');
                $(this).addClass('active');
                
                $('.mets-dashboard-section').removeClass('active');
                $(target).addClass('active');
                
                // Smooth scroll to top
                $('html, body').animate({ scrollTop: $('.mets-dashboard-wrap').offset().top - 32 }, 300);
            });
            
            // Accordion
            $('.mets-accordion-header').on('click', function() {
                $(this).toggleClass('active');
                $(this).next('.mets-accordion-content').toggleClass('active');
                $(this).find('.mets-accordion-toggle')
                    .toggleClass('dashicons-arrow-down dashicons-arrow-up');
            });
            
            // Copy to Clipboard
            $('.mets-copy-btn').on('click', function() {
                var $btn = $(this);
                var textToCopy = $btn.data('copy');
                
                // Create temporary input
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(textToCopy).select();
                document.execCommand('copy');
                $temp.remove();
                
                // Show success
                $btn.addClass('copied');
                $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                
                setTimeout(function() {
                    $btn.removeClass('copied');
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            });
            
            // Initialize first accordion item as open
            $('.mets-accordion-item:first .mets-accordion-header').click();
        });
        </script>
        <?php
    }
    
    /**
     * Render setup checklist
     */
    private function render_setup_checklist() {
        $checklist_items = array(
            array(
                'id' => 'smtp_configured',
                'label' => __( 'Configure SMTP settings for email delivery', METS_TEXT_DOMAIN ),
                'complete' => get_option( 'mets_smtp_configured', false ),
                'link' => admin_url( 'admin.php?page=mets-settings' )
            ),
            array(
                'id' => 'first_entity',
                'label' => __( 'Create your first entity/department', METS_TEXT_DOMAIN ),
                'complete' => $this->has_entities(),
                'link' => admin_url( 'admin.php?page=mets-entities' )
            ),
            array(
                'id' => 'add_agents',
                'label' => __( 'Add support agents to your team', METS_TEXT_DOMAIN ),
                'complete' => $this->has_agents(),
                'link' => admin_url( 'users.php' )
            ),
            array(
                'id' => 'sla_rules',
                'label' => __( 'Set up SLA rules for response times', METS_TEXT_DOMAIN ),
                'complete' => $this->has_sla_rules(),
                'link' => admin_url( 'admin.php?page=mets-settings' )
            ),
            array(
                'id' => 'ticket_form',
                'label' => __( 'Add ticket form to a page', METS_TEXT_DOMAIN ),
                'complete' => $this->has_ticket_form_page(),
                'link' => admin_url( 'post-new.php?post_type=page' )
            ),
            array(
                'id' => 'kb_articles',
                'label' => __( 'Create knowledge base articles', METS_TEXT_DOMAIN ),
                'complete' => $this->has_kb_articles(),
                'link' => admin_url( 'admin.php?page=mets-knowledge-base' )
            ),
        );
        
        foreach ( $checklist_items as $item ) {
            $status_icon = $item['complete'] ? '✓' : '○';
            $status_class = $item['complete'] ? 'complete' : 'incomplete';
            ?>
            <div class="mets-checklist-item">
                <span class="mets-checklist-status <?php echo esc_attr( $status_class ); ?>">
                    <?php echo $status_icon; ?>
                </span>
                <span class="mets-checklist-label">
                    <?php echo esc_html( $item['label'] ); ?>
                    <?php if ( ! $item['complete'] && ! empty( $item['link'] ) ) : ?>
                        <a href="<?php echo esc_url( $item['link'] ); ?>" class="mets-checklist-action">
                            <?php _e( 'Do this now →', METS_TEXT_DOMAIN ); ?>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
            <?php
        }
    }
    
    /**
     * Render KPIs
     */
    private function render_kpis() {
        global $wpdb;
        
        // Get ticket statistics - using correct table name
        $total_tickets = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets" );
        $open_tickets = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE status IN ('new', 'open', 'pending', 'in_progress')" );
        $resolved_today = $wpdb->get_var( 
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
                WHERE status IN ('resolved', 'closed') 
                AND DATE(updated_at) = %s",
                current_time( 'Y-m-d' )
            )
        );
        
        // Get average response time (in hours) - check if replies table exists
        $replies_table = $wpdb->prefix . 'mets_replies';
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$replies_table'" );
        
        if ( $table_exists ) {
            $avg_response_time = $wpdb->get_var(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at))
                FROM {$wpdb->prefix}mets_tickets t
                INNER JOIN {$wpdb->prefix}mets_replies r ON t.id = r.ticket_id
                WHERE r.user_type = 'agent'
                AND r.id = (
                    SELECT MIN(id) FROM {$wpdb->prefix}mets_replies 
                    WHERE ticket_id = t.id AND user_type = 'agent'
                )"
            );
        } else {
            $avg_response_time = null;
        }
        
        $kpis = array(
            array(
                'label' => __( 'Total Tickets', METS_TEXT_DOMAIN ),
                'value' => number_format( $total_tickets ),
                'icon' => 'dashicons-tickets-alt',
                'color' => '#0073aa'
            ),
            array(
                'label' => __( 'Open Tickets', METS_TEXT_DOMAIN ),
                'value' => number_format( $open_tickets ),
                'icon' => 'dashicons-clock',
                'color' => '#f0b849'
            ),
            array(
                'label' => __( 'Resolved Today', METS_TEXT_DOMAIN ),
                'value' => number_format( $resolved_today ),
                'icon' => 'dashicons-yes',
                'color' => '#46b450'
            ),
            array(
                'label' => __( 'Avg Response Time', METS_TEXT_DOMAIN ),
                'value' => $avg_response_time ? round( $avg_response_time, 1 ) . 'h' : 'N/A',
                'icon' => 'dashicons-backup',
                'color' => '#826eb4'
            ),
        );
        
        foreach ( $kpis as $kpi ) {
            ?>
            <div class="mets-kpi-card">
                <span class="dashicons <?php echo esc_attr( $kpi['icon'] ); ?>" 
                      style="color: <?php echo esc_attr( $kpi['color'] ); ?>;"></span>
                <div class="mets-kpi-value" style="color: <?php echo esc_attr( $kpi['color'] ); ?>;">
                    <?php echo esc_html( $kpi['value'] ); ?>
                </div>
                <div class="mets-kpi-label">
                    <?php echo esc_html( $kpi['label'] ); ?>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;
        
        // Get recent tickets
        $recent_tickets = $wpdb->get_results(
            "SELECT t.*, c.name as customer_name, e.name as entity_name
            FROM {$wpdb->prefix}mets_tickets t
            LEFT JOIN {$wpdb->prefix}mets_customers c ON t.customer_id = c.id
            LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
            ORDER BY t.created_at DESC
            LIMIT 10"
        );
        
        if ( $recent_tickets ) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Ticket', METS_TEXT_DOMAIN ); ?></th>
                        <th><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></th>
                        <th><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
                        <th><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
                        <th><?php _e( 'Created', METS_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_tickets as $ticket ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=mets-tickets&action=edit&ticket_id=' . $ticket->id ); ?>">
                                #<?php echo esc_html( $ticket->id ); ?> - <?php echo esc_html( $ticket->subject ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( $ticket->customer_name ); ?></td>
                        <td><?php echo esc_html( $ticket->entity_name ); ?></td>
                        <td>
                            <span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>">
                                <?php echo esc_html( ucfirst( $ticket->status ) ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( human_time_diff( strtotime( $ticket->created_at ), current_time( 'timestamp' ) ) ) . ' ' . __( 'ago', METS_TEXT_DOMAIN ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            ?>
            <p><?php _e( 'No tickets yet. Create your first ticket to see activity here.', METS_TEXT_DOMAIN ); ?></p>
            <?php
        }
    }
    
    /**
     * Helper methods for checklist
     */
    private function has_entities() {
        global $wpdb;
        return (bool) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_entities WHERE status = 'active'" );
    }
    
    private function has_agents() {
        $agents = get_users( array(
            'role__in' => array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor', 'ticket_admin' )
        ) );
        return count( $agents ) > 0;
    }
    
    private function has_sla_rules() {
        global $wpdb;
        return (bool) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_sla_rules WHERE is_active = 1" );
    }
    
    private function has_ticket_form_page() {
        global $wpdb;
        return (bool) $wpdb->get_var( 
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type IN ('page', 'post') 
            AND post_status = 'publish' 
            AND post_content LIKE '%[ticket_form%'"
        );
    }
    
    private function has_kb_articles() {
        global $wpdb;
        return (bool) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_articles WHERE status = 'published'" );
    }
}