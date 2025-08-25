<?php
/**
 * Admin Bar Integration
 *
 * Adds METS notifications to the WordPress admin bar
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Admin Bar class
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Admin_Bar {

    /**
     * Single instance of the class
     *
     * @since    1.0.0
     * @access   private
     * @var      METS_Admin_Bar    $instance    Single instance.
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @since    1.0.0
     * @return   METS_Admin_Bar    Single instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Delay hook initialization until after init to prevent early translation loading
        add_action( 'init', array( $this, 'init_delayed_hooks' ), 15 );
    }
    
    /**
     * Initialize delayed hooks after init
     *
     * @since    1.0.0
     */
    public function init_delayed_hooks() {
        // Add admin bar items
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_items' ), 100 );
        
        // Add admin bar styles
        add_action( 'admin_head', array( $this, 'add_admin_bar_styles' ) );
        add_action( 'wp_head', array( $this, 'add_admin_bar_styles' ) );
    }

    /**
     * Add admin bar items
     *
     * @since    1.0.0
     * @param    WP_Admin_Bar    $wp_admin_bar    Admin bar object
     */
    public function add_admin_bar_items( $wp_admin_bar ) {
        // Check if user can view tickets
        if ( ! current_user_can( 'read' ) ) {
            return;
        }

        // Get unread notifications count
        $user_id = get_current_user_id();
        $notifications = get_user_meta( $user_id, 'mets_unread_notifications', true );
        $unread_count = 0;
        
        if ( is_array( $notifications ) ) {
            $unread_count = count( array_filter( $notifications, function( $n ) {
                return ! isset( $n['read'] ) || ! $n['read'];
            } ) );
        }

        // Add main METS menu
        $wp_admin_bar->add_menu( array(
            'id'    => 'mets-menu',
            'title' => sprintf( 
                '<span class="ab-icon"></span><span class="ab-label">%s</span>',
                did_action( 'init' ) ? __( 'Tickets', METS_TEXT_DOMAIN ) : 'Tickets'
            ),
            'href'  => admin_url( 'admin.php?page=mets-tickets' ),
            'meta'  => array(
                'title' => did_action( 'init' ) ? __( 'Multi-Entity Ticket System', METS_TEXT_DOMAIN ) : 'Multi-Entity Ticket System',
            )
        ) );

        // Add notifications submenu
        $wp_admin_bar->add_menu( array(
            'id'     => 'mets-notifications',
            'parent' => 'mets-menu',
            'title'  => sprintf(
                '%s%s',
                did_action( 'init' ) ? __( 'Notifications', METS_TEXT_DOMAIN ) : 'Notifications',
                $unread_count > 0 ? sprintf( ' <span class="mets-notification-badge">%d</span>', $unread_count ) : ''
            ),
            'href'   => '#',
            'meta'   => array(
                'class' => 'mets-notifications-menu',
                'onclick' => 'return false;'
            )
        ) );

        // Add notification items
        if ( $unread_count > 0 ) {
            // Add mark all read link
            $wp_admin_bar->add_menu( array(
                'id'     => 'mets-mark-all-read',
                'parent' => 'mets-notifications',
                'title'  => did_action( 'init' ) ? __( 'Mark All as Read', METS_TEXT_DOMAIN ) : 'Mark All as Read',
                'href'   => '#',
                'meta'   => array(
                    'class' => 'mets-mark-all-read-link'
                )
            ) );
        } else {
            // No notifications message
            $wp_admin_bar->add_menu( array(
                'id'     => 'mets-no-notifications',
                'parent' => 'mets-notifications',
                'title'  => did_action( 'init' ) ? __( 'No new notifications', METS_TEXT_DOMAIN ) : 'No new notifications',
                'href'   => '#',
                'meta'   => array(
                    'class' => 'mets-no-notifications'
                )
            ) );
        }

        // Add quick links
        if ( current_user_can( 'mets_create_tickets' ) ) {
            $wp_admin_bar->add_menu( array(
                'id'     => 'mets-new-ticket',
                'parent' => 'mets-menu',
                'title'  => __( 'New Ticket', METS_TEXT_DOMAIN ),
                'href'   => admin_url( 'admin.php?page=mets-tickets&action=new' )
            ) );
        }

        // Add my tickets link
        $wp_admin_bar->add_menu( array(
            'id'     => 'mets-my-tickets',
            'parent' => 'mets-menu',
            'title'  => __( 'My Tickets', METS_TEXT_DOMAIN ),
            'href'   => admin_url( 'admin.php?page=mets-tickets&filter=my-tickets' )
        ) );

        // Add assigned tickets link for agents
        if ( current_user_can( 'mets_agent' ) ) {
            $wp_admin_bar->add_menu( array(
                'id'     => 'mets-assigned-tickets',
                'parent' => 'mets-menu',
                'title'  => __( 'Assigned to Me', METS_TEXT_DOMAIN ),
                'href'   => admin_url( 'admin.php?page=mets-tickets&filter=assigned-to-me' )
            ) );
        }
    }

    /**
     * Add admin bar styles
     *
     * @since    1.0.0
     */
    public function add_admin_bar_styles() {
        if ( ! is_admin_bar_showing() ) {
            return;
        }
        ?>
        <style type="text/css">
            /* METS Admin Bar Icon */
            #wp-admin-bar-mets-menu .ab-icon:before {
                content: '\f145';
                top: 2px;
            }
            
            /* Notification Badge in Admin Bar */
            #wpadminbar .mets-notification-badge {
                display: inline-block;
                background: #d63638;
                color: #fff;
                font-size: 11px;
                line-height: 1;
                padding: 2px 6px;
                border-radius: 10px;
                margin-left: 4px;
                font-weight: 600;
                vertical-align: top;
                min-width: 18px;
                text-align: center;
            }
            
            /* No Notifications Style */
            #wpadminbar .mets-no-notifications a {
                color: #72777c !important;
                cursor: default !important;
            }
            
            /* Mark All Read Link */
            #wpadminbar .mets-mark-all-read-link a {
                color: #00a0d2 !important;
            }
            
            #wpadminbar .mets-mark-all-read-link a:hover {
                color: #00b9eb !important;
            }
            
            /* Responsive */
            @media screen and (max-width: 782px) {
                #wp-admin-bar-mets-menu .ab-icon {
                    font-size: 32px;
                    width: 52px;
                    height: 46px;
                    line-height: 46px;
                }
                
                #wp-admin-bar-mets-menu .ab-icon:before {
                    top: 0;
                }
            }
        </style>
        <?php
    }
}