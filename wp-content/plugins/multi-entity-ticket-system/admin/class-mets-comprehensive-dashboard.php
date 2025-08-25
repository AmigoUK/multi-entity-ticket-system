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
     * Helper function to safely translate strings
     */
    private function translate( $text ) {
        return did_action( 'init' ) ? __( $text, METS_TEXT_DOMAIN ) : $text;
    }
    
    /**
     * Helper function to safely echo translated strings
     */
    private function echo_translate( $text ) {
        if ( ! did_action( 'init' ) ) {
            echo esc_html( $text );
            return;
        }
        _e( $text, METS_TEXT_DOMAIN );
    }
    
    /**
     * Display the comprehensive dashboard
     */
    public function display_dashboard() {
        ?>
        <div class="wrap mets-dashboard-wrap">
            <h1 class="wp-heading-inline"><?php echo $this->translate( 'METS Dashboard' ); ?></h1>
            
            <!-- Quick Navigation -->
            <div class="mets-dashboard-navigation">
                <ul class="mets-nav-tabs">
                    <li><a href="#quick-start" class="active"><?php echo $this->translate( 'Quick Start' ); ?></a></li>
                    <li><a href="#system-overview"><?php echo $this->translate( 'System Overview' ); ?></a></li>
                    <li><a href="#user-guide"><?php echo $this->translate( 'User Guide' ); ?></a></li>
                    <li><a href="#shortcodes"><?php echo $this->translate( 'Shortcodes' ); ?></a></li>
                    <li><a href="#developer"><?php echo $this->translate( 'Developer' ); ?></a></li>
                    <li><a href="#help"><?php echo $this->translate( 'Help & Support' ); ?></a></li>
                </ul>
            </div>
            
            <!-- Dashboard Content -->
            <div class="mets-dashboard-content">
                
                <!-- Quick Start Section -->
                <div id="quick-start" class="mets-dashboard-section active">
                    <h2><?php echo $this->translate( 'Welcome to METS - Multi-Entity Ticket System' ); ?></h2>
                    
                    <div class="mets-welcome-panel">
                        <div class="mets-welcome-video">
                            <div class="mets-video-placeholder">
                                {vid1:mets-welcome-intro:Welcome video introducing METS system, showing main features and navigation (3-5 minutes)}
                            </div>
                        </div>
                        
                        <div class="mets-welcome-content">
                            <h3><?php $this->echo_translate( 'Getting Started with METS' ); ?></h3>
                            <p><?php $this->echo_translate( 'METS is a powerful multi-entity ticket management system that helps you manage customer support across multiple departments or businesses.' ); ?></p>
                            
                            <div class="mets-quick-actions">
                                <h4><?php $this->echo_translate( 'Quick Actions' ); ?></h4>
                                <div class="mets-action-cards">
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-add-ticket' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <span><?php $this->echo_translate( 'Create New Ticket' ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-entities' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-building"></span>
                                        <span><?php $this->echo_translate( 'Add New Entity' ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'users.php' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-groups"></span>
                                        <span><?php $this->echo_translate( 'Manage Users & Agents' ); ?></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="mets-action-card">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <span><?php $this->echo_translate( 'System Settings' ); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- First Steps Checklist -->
                    <div class="mets-setup-checklist">
                        <h3><?php $this->echo_translate( 'Initial Setup Checklist' ); ?></h3>
                        <div class="mets-checklist">
                            <?php $this->render_setup_checklist(); ?>
                        </div>
                    </div>
                </div>
                
                <!-- System Overview Section -->
                <div id="system-overview" class="mets-dashboard-section">
                    <h2><?php $this->echo_translate( 'System Overview' ); ?></h2>
                    
                    <!-- KPIs -->
                    <div class="mets-kpi-grid">
                        <?php $this->render_kpis(); ?>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="mets-recent-activity">
                        <h3><?php $this->echo_translate( 'Recent Activity' ); ?></h3>
                        <?php $this->render_recent_activity(); ?>
                    </div>
                </div>
                
                <!-- User Guide Section -->
                <div id="user-guide" class="mets-dashboard-section">
                    <h2><?php $this->echo_translate( 'User Guide' ); ?></h2>
                    
                    <!-- User Roles -->
                    <div class="mets-user-roles">
                        <h3><?php $this->echo_translate( 'User Roles & Permissions' ); ?></h3>
                        
                        <div class="mets-roles-grid">
                            <!-- Customer Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👤</div>
                                <h4><?php $this->echo_translate( 'Customer' ); ?></h4>
                                <p><?php $this->echo_translate( 'Basic customer who can submit tickets and track their status' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'Submit new tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'View own tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Reply to tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Search knowledge base' ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Agent Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👷</div>
                                <h4><?php $this->echo_translate( 'Support Agent' ); ?></h4>
                                <p><?php $this->echo_translate( 'Basic support agent who handles assigned tickets' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'Handle assigned tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Reply to customers' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Access knowledge base' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Log time spent' ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Senior Agent Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">🎖️</div>
                                <h4><?php $this->echo_translate( 'Senior Agent' ); ?></h4>
                                <p><?php $this->echo_translate( 'Experienced agent with advanced permissions' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'All agent capabilities' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Edit any ticket' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Reassign tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Merge & escalate tickets' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Publish KB articles' ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Manager Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👔</div>
                                <h4><?php $this->echo_translate( 'Ticket Manager' ); ?></h4>
                                <p><?php $this->echo_translate( 'Manages ticket workflows and team assignments' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'All senior agent capabilities' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Manage agents & entities' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Configure SLA rules' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Access all reports' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Configure automations' ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Supervisor Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">🌟</div>
                                <h4><?php $this->echo_translate( 'Support Supervisor' ); ?></h4>
                                <p><?php $this->echo_translate( 'System-wide access and oversight capabilities' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'All manager capabilities' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'System configuration' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Email templates' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'Security settings' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'API management' ); ?></li>
                                </ul>
                            </div>
                            
                            <!-- Admin Role -->
                            <div class="mets-role-card">
                                <div class="mets-role-icon">👑</div>
                                <h4><?php $this->echo_translate( 'Ticket Administrator' ); ?></h4>
                                <p><?php $this->echo_translate( 'Full system control and administration' ); ?></p>
                                <ul class="mets-role-capabilities">
                                    <li>✓ <?php $this->echo_translate( 'Complete system control' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'User management' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'System maintenance' ); ?></li>
                                    <li>✓ <?php $this->echo_translate( 'All capabilities' ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mets-role-hierarchy">
                            <h4><?php $this->echo_translate( 'Role Hierarchy' ); ?></h4>
                            <div class="mets-hierarchy-diagram">
                                <div class="mets-video-placeholder">
                                    {vid2:role-hierarchy-explained:Visual explanation of role hierarchy and permission inheritance (2-3 minutes)}
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Management Guide -->
                        <div class="mets-user-management-guide">
                            <h4><?php $this->echo_translate( 'How to Add & Manage Users' ); ?></h4>
                            
                            <div class="mets-user-guide-steps">
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">1</div>
                                    <div class="mets-step-content">
                                        <h5><?php $this->echo_translate( 'Add New Users' ); ?></h5>
                                        <p><?php $this->echo_translate( 'Go to WordPress Users menu to add new team members. You can create new users or invite existing ones.' ); ?></p>
                                        <a href="<?php echo admin_url( 'user-new.php' ); ?>" class="button button-primary">
                                            <?php $this->echo_translate( 'Add New User' ); ?>
                                        </a>
                                        <a href="<?php echo admin_url( 'users.php' ); ?>" class="button">
                                            <?php $this->echo_translate( 'Manage Users' ); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">2</div>
                                    <div class="mets-step-content">
                                        <h5><?php $this->echo_translate( 'Assign METS Roles' ); ?></h5>
                                        <p><?php $this->echo_translate( 'When creating or editing users, select their METS role from the Role dropdown:' ); ?></p>
                                        <ul class="mets-role-list">
                                            <li><strong>Ticket Customer</strong> - <?php $this->echo_translate( 'For end customers' ); ?></li>
                                            <li><strong>Ticket Agent</strong> - <?php $this->echo_translate( 'For basic support staff' ); ?></li>
                                            <li><strong>Senior Agent</strong> - <?php $this->echo_translate( 'For experienced agents' ); ?></li>
                                            <li><strong>Ticket Manager</strong> - <?php $this->echo_translate( 'For team leaders' ); ?></li>
                                            <li><strong>Support Supervisor</strong> - <?php $this->echo_translate( 'For department heads' ); ?></li>
                                            <li><strong>Ticket Administrator</strong> - <?php $this->echo_translate( 'For system admins' ); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mets-guide-step">
                                    <div class="mets-step-number">3</div>
                                    <div class="mets-step-content">
                                        <h5><?php $this->echo_translate( 'Assign to Entities' ); ?></h5>
                                        <p><?php $this->echo_translate( 'After assigning roles, go to METS Agents page to assign users to specific entities/departments.' ); ?></p>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-agents' ); ?>" class="button">
                                            <?php $this->echo_translate( 'Configure Agent Assignments' ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mets-user-tips">
                                <h5><?php $this->echo_translate( 'Pro Tips:' ); ?></h5>
                                <ul>
                                    <li><?php $this->echo_translate( 'You can bulk-edit multiple users at once from the Users page' ); ?></li>
                                    <li><?php $this->echo_translate( 'Use the "Send user notification" option to email login details to new users' ); ?></li>
                                    <li><?php $this->echo_translate( 'Agents can be assigned to multiple entities for cross-department support' ); ?></li>
                                    <li><?php $this->echo_translate( 'Change user roles anytime from Users → Edit User → Role dropdown' ); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Core Features -->
                    <div class="mets-core-features">
                        <h3><?php $this->echo_translate( 'Core Features' ); ?></h3>
                        
                        <div class="mets-feature-accordion">
                            <!-- Ticket Management -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-tickets-alt"></span>
                                    <?php $this->echo_translate( 'Ticket Management' ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php $this->echo_translate( 'Complete ticket lifecycle management from creation to resolution.' ); ?></p>
                                    <ul>
                                        <li><strong><?php $this->echo_translate( 'Create Tickets:' ); ?></strong> <?php $this->echo_translate( 'Submit tickets via forms, email, or admin panel' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Assign & Route:' ); ?></strong> <?php $this->echo_translate( 'Automatically or manually assign to agents' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Track Status:' ); ?></strong> <?php $this->echo_translate( 'Monitor ticket progress through customizable statuses' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Prioritize:' ); ?></strong> <?php $this->echo_translate( 'Set priority levels for urgent issues' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Collaborate:' ); ?></strong> <?php $this->echo_translate( 'Internal notes and team collaboration tools' ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets' ); ?>" class="button"><?php $this->echo_translate( 'View Tickets' ); ?></a>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-add-ticket' ); ?>" class="button"><?php $this->echo_translate( 'Create Ticket' ); ?></a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Entity Management -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php $this->echo_translate( 'Entity Management' ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php $this->echo_translate( 'Manage multiple departments, branches, or businesses from one system.' ); ?></p>
                                    <ul>
                                        <li><strong><?php $this->echo_translate( 'Multiple Entities:' ); ?></strong> <?php $this->echo_translate( 'Create unlimited entities (departments/businesses)' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Entity Settings:' ); ?></strong> <?php $this->echo_translate( 'Custom email, SLA, and business hours per entity' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Agent Assignment:' ); ?></strong> <?php $this->echo_translate( 'Assign agents to specific entities' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Entity Reports:' ); ?></strong> <?php $this->echo_translate( 'Performance metrics per entity' ); ?></li>
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
                                    <?php $this->echo_translate( 'Knowledge Base' ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php $this->echo_translate( 'Self-service knowledge base to reduce ticket volume.' ); ?></p>
                                    <ul>
                                        <li><strong><?php $this->echo_translate( 'Article Management:' ); ?></strong> <?php $this->echo_translate( 'Create, edit, and organize help articles' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Categories & Tags:' ); ?></strong> <?php $this->echo_translate( 'Organize content for easy discovery' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Search Integration:' ); ?></strong> <?php $this->echo_translate( 'Smart search suggestions in ticket forms' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Analytics:' ); ?></strong> <?php $this->echo_translate( 'Track article views and effectiveness' ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-knowledge-base' ); ?>" class="button"><?php $this->echo_translate( 'Manage KB' ); ?></a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Integration -->
                            <div class="mets-accordion-item">
                                <h4 class="mets-accordion-header">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php $this->echo_translate( 'Email Integration' ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php $this->echo_translate( 'Seamless email communication and ticket creation.' ); ?></p>
                                    <ul>
                                        <li><strong><?php $this->echo_translate( 'Email to Ticket:' ); ?></strong> <?php $this->echo_translate( 'Convert incoming emails to tickets automatically' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Email Templates:' ); ?></strong> <?php $this->echo_translate( 'Customizable notification templates' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'SMTP Configuration:' ); ?></strong> <?php $this->echo_translate( 'Use your own email server' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Email Queue:' ); ?></strong> <?php $this->echo_translate( 'Reliable email delivery with retry mechanism' ); ?></li>
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
                                    <?php $this->echo_translate( 'Automation & SLA' ); ?>
                                    <span class="mets-accordion-toggle dashicons dashicons-arrow-down"></span>
                                </h4>
                                <div class="mets-accordion-content">
                                    <p><?php $this->echo_translate( 'Automate workflows and ensure timely responses.' ); ?></p>
                                    <ul>
                                        <li><strong><?php $this->echo_translate( 'SLA Rules:' ); ?></strong> <?php $this->echo_translate( 'Set response and resolution time targets' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Auto-Assignment:' ); ?></strong> <?php $this->echo_translate( 'Route tickets based on rules' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Escalation:' ); ?></strong> <?php $this->echo_translate( 'Automatic escalation for overdue tickets' ); ?></li>
                                        <li><strong><?php $this->echo_translate( 'Business Hours:' ); ?></strong> <?php $this->echo_translate( 'SLA tracking based on working hours' ); ?></li>
                                    </ul>
                                    <div class="mets-feature-links">
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="button"><?php $this->echo_translate( 'Configure SLA' ); ?></a>
                                        <a href="<?php echo admin_url( 'admin.php?page=mets-settings' ); ?>" class="button"><?php $this->echo_translate( 'Automation Rules' ); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shortcodes Section -->
                <div id="shortcodes" class="mets-dashboard-section">
                    <h2><?php $this->echo_translate( 'Shortcodes Reference' ); ?></h2>
                    
                    <div class="mets-shortcodes-intro">
                        <p><?php $this->echo_translate( 'Use these shortcodes to add METS functionality to any page or post. Click the copy button to copy the shortcode to your clipboard.' ); ?></p>
                    </div>
                    
                    <div class="mets-shortcodes-grid">
                        <!-- Ticket Form -->
                        <div class="mets-shortcode-card">
                            <h3><?php $this->echo_translate( 'Ticket Submission Form' ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[ticket_form]</code>
                                <button class="mets-copy-btn" data-copy="[ticket_form]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php $this->echo_translate( 'Displays a public ticket submission form with KB search integration.' ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php $this->echo_translate( 'Parameters:' ); ?></h4>
                                <ul>
                                    <li><code>entity_id="123"</code> - <?php $this->echo_translate( 'Pre-select specific entity' ); ?></li>
                                    <li><code>kb_search="yes"</code> - <?php $this->echo_translate( 'Enable KB search (default: yes)' ); ?></li>
                                    <li><code>category="billing"</code> - <?php $this->echo_translate( 'Pre-select category' ); ?></li>
                                </ul>
                            </div>
                            <div class="mets-shortcode-example">
                                <h4><?php $this->echo_translate( 'Example:' ); ?></h4>
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
                            <h3><?php $this->echo_translate( 'Customer Portal' ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[customer_portal]</code>
                                <button class="mets-copy-btn" data-copy="[customer_portal]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php $this->echo_translate( 'Customer login area to view and manage their tickets.' ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php $this->echo_translate( 'Parameters:' ); ?></h4>
                                <ul>
                                    <li><code>show_closed="yes"</code> - <?php $this->echo_translate( 'Show closed tickets' ); ?></li>
                                    <li><code>tickets_per_page="10"</code> - <?php $this->echo_translate( 'Number of tickets to display' ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- KB Search -->
                        <div class="mets-shortcode-card">
                            <h3><?php $this->echo_translate( 'Knowledge Base Search' ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[kb_search]</code>
                                <button class="mets-copy-btn" data-copy="[kb_search]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php $this->echo_translate( 'Standalone knowledge base search widget.' ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php $this->echo_translate( 'Parameters:' ); ?></h4>
                                <ul>
                                    <li><code>placeholder="Search for help..."</code> - <?php $this->echo_translate( 'Search box placeholder' ); ?></li>
                                    <li><code>show_categories="yes"</code> - <?php $this->echo_translate( 'Display category list' ); ?></li>
                                    <li><code>show_popular="5"</code> - <?php $this->echo_translate( 'Show popular articles' ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Entity List -->
                        <div class="mets-shortcode-card">
                            <h3><?php $this->echo_translate( 'Entity Directory' ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[entity_list]</code>
                                <button class="mets-copy-btn" data-copy="[entity_list]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php $this->echo_translate( 'Display a list of all active entities/departments.' ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php $this->echo_translate( 'Parameters:' ); ?></h4>
                                <ul>
                                    <li><code>show_contact="yes"</code> - <?php $this->echo_translate( 'Show contact info' ); ?></li>
                                    <li><code>columns="3"</code> - <?php $this->echo_translate( 'Number of columns' ); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Ticket Stats -->
                        <div class="mets-shortcode-card">
                            <h3><?php $this->echo_translate( 'Ticket Statistics' ); ?></h3>
                            <div class="mets-shortcode-code">
                                <code>[ticket_stats]</code>
                                <button class="mets-copy-btn" data-copy="[ticket_stats]">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php $this->echo_translate( 'Display ticket statistics and metrics (admin only).' ); ?></p>
                            <div class="mets-shortcode-params">
                                <h4><?php $this->echo_translate( 'Parameters:' ); ?></h4>
                                <ul>
                                    <li><code>type="summary"</code> - <?php $this->echo_translate( 'Stats type: summary, chart, table' ); ?></li>
                                    <li><code>period="week"</code> - <?php $this->echo_translate( 'Time period: day, week, month' ); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mets-shortcode-video">
                        <h3><?php $this->echo_translate( 'Shortcode Usage Tutorial' ); ?></h3>
                        <div class="mets-video-placeholder">
                            {vid5:shortcode-tutorial:How to use METS shortcodes in pages and posts, with live examples (4 minutes)}
                        </div>
                    </div>
                </div>
                
                <!-- Developer Section -->
                <div id="developer" class="mets-dashboard-section">
                    <h2><?php $this->echo_translate( 'Developer Resources' ); ?></h2>
                    
                    <div class="mets-developer-grid">
                        <!-- REST API -->
                        <div class="mets-dev-card">
                            <h3><?php $this->echo_translate( 'REST API Endpoints' ); ?></h3>
                            <p><?php $this->echo_translate( 'Integrate METS with external applications using our REST API.' ); ?></p>
                            
                            <h4><?php $this->echo_translate( 'Available Endpoints:' ); ?></h4>
                            <div class="mets-api-endpoints">
                                <div class="mets-endpoint">
                                    <code>GET /wp-json/mets/v1/tickets</code>
                                    <p><?php $this->echo_translate( 'List all tickets' ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>POST /wp-json/mets/v1/tickets</code>
                                    <p><?php $this->echo_translate( 'Create new ticket' ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>GET /wp-json/mets/v1/tickets/{id}</code>
                                    <p><?php $this->echo_translate( 'Get ticket details' ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>PUT /wp-json/mets/v1/tickets/{id}</code>
                                    <p><?php $this->echo_translate( 'Update ticket' ); ?></p>
                                </div>
                                <div class="mets-endpoint">
                                    <code>POST /wp-json/mets/v1/tickets/{id}/replies</code>
                                    <p><?php $this->echo_translate( 'Add reply to ticket' ); ?></p>
                                </div>
                            </div>
                            
                            <div class="mets-api-auth">
                                <h4><?php $this->echo_translate( 'Authentication:' ); ?></h4>
                                <p><?php $this->echo_translate( 'Use Application Passwords or JWT tokens for authentication.' ); ?></p>
                                <div class="mets-code-example">
                                    <pre>curl -X GET https://yoursite.com/wp-json/mets/v1/tickets \
  -H "Authorization: Bearer YOUR_TOKEN"</pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hooks & Filters -->
                        <div class="mets-dev-card">
                            <h3><?php $this->echo_translate( 'Hooks & Filters' ); ?></h3>
                            <p><?php $this->echo_translate( 'Extend METS functionality using WordPress hooks.' ); ?></p>
                            
                            <h4><?php $this->echo_translate( 'Common Hooks:' ); ?></h4>
                            <div class="mets-hooks-list">
                                <div class="mets-hook">
                                    <code>mets_ticket_created</code>
                                    <p><?php $this->echo_translate( 'Fired after ticket creation' ); ?></p>
                                    <pre>add_action( 'mets_ticket_created', function( $ticket_id ) {
    // Your custom code
}, 10, 1 );</pre>
                                </div>
                                
                                <div class="mets-hook">
                                    <code>mets_ticket_status_changed</code>
                                    <p><?php $this->echo_translate( 'Fired when ticket status changes' ); ?></p>
                                    <pre>add_action( 'mets_ticket_status_changed', function( $ticket_id, $old_status, $new_status ) {
    // Your custom code
}, 10, 3 );</pre>
                                </div>
                                
                                <div class="mets-hook">
                                    <code>mets_ticket_form_fields</code>
                                    <p><?php $this->echo_translate( 'Filter to add custom fields to ticket form' ); ?></p>
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
                            <h3><?php $this->echo_translate( 'Database Schema' ); ?></h3>
                            <p><?php $this->echo_translate( 'METS uses 26 custom database tables for optimal performance.' ); ?></p>
                            
                            <h4><?php $this->echo_translate( 'Main Tables:' ); ?></h4>
                            <ul class="mets-db-tables">
                                <li><code>mets_tickets</code> - <?php $this->echo_translate( 'Main tickets table' ); ?></li>
                                <li><code>mets_ticket_replies</code> - <?php $this->echo_translate( 'Ticket replies and notes' ); ?></li>
                                <li><code>mets_entities</code> - <?php $this->echo_translate( 'Entity/department data' ); ?></li>
                                <li><code>mets_customers</code> - <?php $this->echo_translate( 'Customer information' ); ?></li>
                                <li><code>mets_kb_articles</code> - <?php $this->echo_translate( 'Knowledge base articles' ); ?></li>
                                <li><code>mets_sla_rules</code> - <?php $this->echo_translate( 'SLA configuration' ); ?></li>
                            </ul>
                            
                            <div class="mets-db-diagram">
                                <a href="<?php echo METS_PLUGIN_URL; ?>docs/database-schema.pdf" target="_blank" class="button">
                                    <?php $this->echo_translate( 'View Complete Schema' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mets-developer-video">
                        <h3><?php $this->echo_translate( 'Developer Tutorial' ); ?></h3>
                        <div class="mets-video-placeholder">
                            {vid6:developer-tutorial:Building custom integrations with METS API and hooks (10 minutes)}
                        </div>
                    </div>
                </div>
                
                <!-- Help & Support Section -->
                <div id="help" class="mets-dashboard-section">
                    <h2><?php $this->echo_translate( 'Help & Support' ); ?></h2>
                    
                    <div class="mets-help-grid">
                        <!-- FAQs -->
                        <div class="mets-help-card">
                            <h3><?php $this->echo_translate( 'Frequently Asked Questions' ); ?></h3>
                            
                            <div class="mets-faq-list">
                                <div class="mets-faq-item">
                                    <h4><?php $this->echo_translate( 'How do I create my first entity?' ); ?></h4>
                                    <p><?php $this->echo_translate( 'Go to METS Tickets → Entities → Add New Entity. Fill in the basic information like name, email, and business hours.' ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php $this->echo_translate( 'Can agents work on multiple entities?' ); ?></h4>
                                    <p><?php $this->echo_translate( 'Yes! Agents can be assigned to multiple entities. Go to Agents management to configure entity access.' ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php $this->echo_translate( 'How do I set up email notifications?' ); ?></h4>
                                    <p><?php $this->echo_translate( 'Configure SMTP settings in Settings → Email Configuration. Then customize email templates in Settings → Email Templates.' ); ?></p>
                                </div>
                                
                                <div class="mets-faq-item">
                                    <h4><?php $this->echo_translate( 'What are SLA rules?' ); ?></h4>
                                    <p><?php $this->echo_translate( 'SLA (Service Level Agreement) rules define response and resolution time targets for tickets based on priority and other conditions.' ); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Troubleshooting -->
                        <div class="mets-help-card">
                            <h3><?php $this->echo_translate( 'Troubleshooting' ); ?></h3>
                            
                            <div class="mets-troubleshooting">
                                <div class="mets-trouble-item">
                                    <h4><?php $this->echo_translate( 'Emails not sending' ); ?></h4>
                                    <ul>
                                        <li><?php $this->echo_translate( 'Check SMTP settings' ); ?></li>
                                        <li><?php $this->echo_translate( 'Verify email queue is processing' ); ?></li>
                                        <li><?php $this->echo_translate( 'Check error logs' ); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="mets-trouble-item">
                                    <h4><?php $this->echo_translate( 'Shortcodes not working' ); ?></h4>
                                    <ul>
                                        <li><?php $this->echo_translate( 'Ensure METS plugin is active' ); ?></li>
                                        <li><?php $this->echo_translate( 'Check for theme conflicts' ); ?></li>
                                        <li><?php $this->echo_translate( 'Verify page builder compatibility' ); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="mets-trouble-item">
                                    <h4><?php $this->echo_translate( 'Performance issues' ); ?></h4>
                                    <ul>
                                        <li><?php $this->echo_translate( 'Enable caching' ); ?></li>
                                        <li><?php $this->echo_translate( 'Optimize database' ); ?></li>
                                        <li><?php $this->echo_translate( 'Check server resources' ); ?></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mets-debug-tools">
                                <h4><?php $this->echo_translate( 'Debug Tools' ); ?></h4>
                                <a href="<?php echo admin_url( 'admin.php?page=mets-settings&tab=tools' ); ?>" class="button">
                                    <?php $this->echo_translate( 'System Information' ); ?>
                                </a>
                                <a href="<?php echo admin_url( 'admin.php?page=mets-settings&tab=logs' ); ?>" class="button">
                                    <?php $this->echo_translate( 'View Error Logs' ); ?>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Contact Support -->
                        <div class="mets-help-card">
                            <h3><?php $this->echo_translate( 'Contact Support' ); ?></h3>
                            
                            <div class="mets-support-options">
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-book"></span>
                                    <div class="mets-support-content">
                                        <h4><?php $this->echo_translate( 'Documentation' ); ?></h4>
                                        <p><?php $this->echo_translate( 'Comprehensive guides and tutorials' ); ?></p>
                                    </div>
                                    <div class="mets-support-actions">
                                        <a href="https://docs.mets-tickets.com" target="_blank" class="button">
                                            <?php $this->echo_translate( 'View Docs' ); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <div class="mets-support-content">
                                        <h4><?php $this->echo_translate( 'Community Forum' ); ?></h4>
                                        <p><?php $this->echo_translate( 'Get help from the community' ); ?></p>
                                    </div>
                                    <div class="mets-support-actions">
                                        <a href="https://community.mets-tickets.com" target="_blank" class="button">
                                            <?php $this->echo_translate( 'Visit Forum' ); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mets-support-item">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <div class="mets-support-content">
                                        <h4><?php $this->echo_translate( 'Email Support' ); ?></h4>
                                        <p><?php $this->echo_translate( 'Direct support for license holders' ); ?></p>
                                    </div>
                                    <div class="mets-support-actions">
                                        <a href="mailto:support@mets-tickets.com" class="button button-primary">
                                            <?php $this->echo_translate( 'Email Us' ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Requirements -->
                    <div class="mets-system-requirements">
                        <h3><?php $this->echo_translate( 'System Requirements' ); ?></h3>
                        <div class="mets-requirements-grid">
                            <div class="mets-requirement">
                                <strong><?php $this->echo_translate( 'WordPress:' ); ?></strong> 5.0 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php $this->echo_translate( 'PHP:' ); ?></strong> 7.2 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php $this->echo_translate( 'MySQL:' ); ?></strong> 5.6 or higher
                            </div>
                            <div class="mets-requirement">
                                <strong><?php $this->echo_translate( 'Memory:' ); ?></strong> 128MB minimum
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
            padding: 30px 20px 20px 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        
        .mets-role-icon {
            font-size: 48px;
            text-align: center;
            margin: 0 0 20px 0;
            line-height: 1;
            padding-top: 10px;
        }
        
        .mets-role-card h4 {
            text-align: center;
            margin: 0 0 15px 0;
            color: #23282d;
            font-size: 18px;
            font-weight: 600;
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
        
        /* Hooks & Filters Code Examples */
        .mets-hook {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .mets-hook code {
            display: block;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .mets-hook pre {
            background: #23282d;
            color: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0 0 0;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            line-height: 1.4;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .mets-hook pre::-webkit-scrollbar {
            height: 8px;
        }
        
        .mets-hook pre::-webkit-scrollbar-track {
            background: #1e1e1e;
            border-radius: 4px;
        }
        
        .mets-hook pre::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        .mets-hook pre::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
        
        /* API Code Examples */
        .mets-code-example {
            margin: 15px 0;
        }
        
        .mets-code-example pre {
            background: #23282d;
            color: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            line-height: 1.4;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
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
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .mets-support-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 4px;
            gap: 20px;
        }
        
        .mets-support-item .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #0073aa;
            flex-shrink: 0;
        }
        
        .mets-support-content {
            flex: 1;
        }
        
        .mets-support-item h4 {
            margin: 0 0 8px 0;
            color: #23282d;
        }
        
        .mets-support-item p {
            margin: 0 0 15px 0;
            color: #666;
        }
        
        .mets-support-actions {
            flex-shrink: 0;
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .mets-nav-tabs {
                flex-direction: column;
            }
            
            .mets-nav-tabs li {
                width: 100%;
            }
            
            .mets-welcome-panel {
                flex-direction: column;
                gap: 20px;
            }
            
            .mets-action-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mets-roles-grid {
                grid-template-columns: 1fr;
            }
            
            .mets-shortcodes-grid {
                grid-template-columns: 1fr;
            }
            
            .mets-developer-grid {
                grid-template-columns: 1fr;
            }
            
            .mets-help-grid {
                grid-template-columns: 1fr;
            }
            
            .mets-support-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .mets-support-content {
                text-align: center;
            }
            
            .mets-support-actions {
                align-self: center;
            }
            
            .mets-hook pre {
                font-size: 11px;
                padding: 10px;
            }
            
            .mets-code-example pre {
                font-size: 11px;
                padding: 10px;
            }
            
            .mets-shortcode-code {
                padding: 8px 35px 8px 10px;
            }
            
            .mets-shortcode-code code {
                font-size: 12px;
                word-break: break-all;
            }
        }
        
        @media (max-width: 480px) {
            .mets-action-cards {
                grid-template-columns: 1fr;
            }
            
            .mets-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mets-requirements-grid {
                grid-template-columns: 1fr;
            }
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
                'label' => $this->translate( 'Configure SMTP settings for email delivery' ),
                'complete' => get_option( 'mets_smtp_configured', false ),
                'link' => admin_url( 'admin.php?page=mets-settings' )
            ),
            array(
                'id' => 'first_entity',
                'label' => $this->translate( 'Create your first entity/department' ),
                'complete' => $this->has_entities(),
                'link' => admin_url( 'admin.php?page=mets-entities' )
            ),
            array(
                'id' => 'add_agents',
                'label' => $this->translate( 'Add support agents to your team' ),
                'complete' => $this->has_agents(),
                'link' => admin_url( 'users.php' )
            ),
            array(
                'id' => 'sla_rules',
                'label' => $this->translate( 'Set up SLA rules for response times' ),
                'complete' => $this->has_sla_rules(),
                'link' => admin_url( 'admin.php?page=mets-settings' )
            ),
            array(
                'id' => 'ticket_form',
                'label' => $this->translate( 'Add ticket form to a page' ),
                'complete' => $this->has_ticket_form_page(),
                'link' => admin_url( 'post-new.php?post_type=page' )
            ),
            array(
                'id' => 'kb_articles',
                'label' => $this->translate( 'Create knowledge base articles' ),
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
                            <?php $this->echo_translate( 'Do this now →' ); ?>
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
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$replies_table'" );
        
        if ( $table_exists ) {
            $avg_response_time = $wpdb->get_var(
                "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at))
                FROM {$wpdb->prefix}mets_tickets t
                INNER JOIN {$wpdb->prefix}mets_ticket_replies r ON t.id = r.ticket_id
                WHERE r.user_type = 'agent'
                AND r.id = (
                    SELECT MIN(id) FROM {$wpdb->prefix}mets_ticket_replies 
                    WHERE ticket_id = t.id AND user_type = 'agent'
                )"
            );
        } else {
            $avg_response_time = null;
        }
        
        $kpis = array(
            array(
                'label' => $this->translate( 'Total Tickets' ),
                'value' => number_format( $total_tickets ),
                'icon' => 'dashicons-tickets-alt',
                'color' => '#0073aa'
            ),
            array(
                'label' => $this->translate( 'Open Tickets' ),
                'value' => number_format( $open_tickets ),
                'icon' => 'dashicons-clock',
                'color' => '#f0b849'
            ),
            array(
                'label' => $this->translate( 'Resolved Today' ),
                'value' => number_format( $resolved_today ),
                'icon' => 'dashicons-yes',
                'color' => '#46b450'
            ),
            array(
                'label' => $this->translate( 'Avg Response Time' ),
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
        
        // Get recent tickets (without customer table reference)
        $recent_tickets = $wpdb->get_results(
            "SELECT t.*, e.name as entity_name, t.customer_name, t.customer_email
            FROM {$wpdb->prefix}mets_tickets t
            LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
            ORDER BY t.created_at DESC
            LIMIT 10"
        );
        
        if ( $recent_tickets ) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php $this->echo_translate( 'Ticket' ); ?></th>
                        <th><?php $this->echo_translate( 'Customer' ); ?></th>
                        <th><?php $this->echo_translate( 'Entity' ); ?></th>
                        <th><?php $this->echo_translate( 'Status' ); ?></th>
                        <th><?php $this->echo_translate( 'Created' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_tickets as $ticket ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket->id ); ?>">
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
                        <td><?php echo esc_html( human_time_diff( strtotime( $ticket->created_at ), current_time( 'timestamp' ) ) ) . ' ' . $this->translate( 'ago' ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            ?>
            <p><?php $this->echo_translate( 'No tickets yet. Create your first ticket to see activity here.' ); ?></p>
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