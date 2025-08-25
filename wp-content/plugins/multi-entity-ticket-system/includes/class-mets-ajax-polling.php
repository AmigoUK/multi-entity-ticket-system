<?php
/**
 * AJAX Polling functionality
 *
 * Provides periodic update checks as a replacement for WebSocket real-time features
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * AJAX Polling class
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Ajax_Polling {

    /**
     * Single instance of the class
     *
     * @since    1.0.0
     * @access   private
     * @var      METS_Ajax_Polling    $instance    Single instance.
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @since    1.0.0
     * @return   METS_Ajax_Polling    Single instance
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
        // Register AJAX handlers
        add_action( 'wp_ajax_mets_check_updates', array( $this, 'check_updates' ) );
        add_action( 'wp_ajax_mets_get_notifications', array( $this, 'get_notifications' ) );
        add_action( 'wp_ajax_mets_mark_notification_read', array( $this, 'mark_notification_read' ) );
        
        // Enqueue polling scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_polling_scripts' ) );
    }

    /**
     * Enqueue polling scripts
     *
     * @since    1.0.0
     * @param    string    $hook    The current admin page hook
     */
    public function enqueue_polling_scripts( $hook ) {
        // Only load on METS admin pages
        if ( strpos( $hook, 'mets' ) === false && strpos( $hook, 'multi-entity-ticket' ) === false ) {
            return;
        }

        wp_enqueue_script(
            'mets-polling-client',
            METS_PLUGIN_URL . 'assets/js/mets-polling-client.js',
            array( 'jquery' ),
            METS_VERSION,
            true
        );

        // Prepare localization strings - avoid early translation loading
        $strings = array();
        if ( did_action( 'init' ) ) {
            $strings = array(
                'newTicket' => did_action( 'init' ) ? __( 'New ticket created', METS_TEXT_DOMAIN ) : 'New ticket created',
                'ticketUpdated' => did_action( 'init' ) ? __( 'Ticket updated', METS_TEXT_DOMAIN ) : 'Ticket updated',
                'ticketAssigned' => did_action( 'init' ) ? __( 'Ticket assigned to you', METS_TEXT_DOMAIN ) : 'Ticket assigned to you',
                'newReply' => did_action( 'init' ) ? __( 'New reply on ticket', METS_TEXT_DOMAIN ) : 'New reply on ticket',
                'statusChanged' => did_action( 'init' ) ? __( 'Ticket status changed', METS_TEXT_DOMAIN ) : 'Ticket status changed',
            );
        } else {
            $strings = array(
                'newTicket' => 'New ticket created',
                'ticketUpdated' => 'Ticket updated',
                'ticketAssigned' => 'Ticket assigned to you',
                'newReply' => 'New reply on ticket',
                'statusChanged' => 'Ticket status changed',
            );
        }

        // Localize script with AJAX data
        wp_localize_script( 'mets-polling-client', 'metsPolling', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mets-polling-nonce' ),
            'pollInterval' => apply_filters( 'mets_poll_interval', 30000 ), // 30 seconds default
            'enablePolling' => apply_filters( 'mets_enable_polling', true ),
            'currentUserId' => get_current_user_id(),
            'isAdmin' => current_user_can( 'manage_options' ),
            'strings' => $strings
        ) );
    }

    /**
     * Check for updates since last check
     *
     * @since    1.0.0
     */
    public function check_updates() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mets-polling-nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $current_user_id = get_current_user_id();
        $last_check = isset( $_POST['lastCheck'] ) ? intval( $_POST['lastCheck'] ) : 0;
        
        // Get updates since last check
        $updates = $this->get_updates_since( $last_check, $current_user_id );
        
        // Store unread notifications
        if ( ! empty( $updates['notifications'] ) ) {
            $this->store_notifications( $updates['notifications'], $current_user_id );
        }
        
        wp_send_json_success( $updates );
    }

    /**
     * Get updates since timestamp
     *
     * @since    1.0.0
     * @param    int    $timestamp    Last check timestamp
     * @param    int    $user_id      User ID
     * @return   array                Updates array
     */
    private function get_updates_since( $timestamp, $user_id ) {
        global $wpdb;
        
        $updates = array(
            'tickets' => array(),
            'replies' => array(),
            'assignments' => array(),
            'status_changes' => array(),
            'notifications' => array(),
            'timestamp' => time()
        );
        
        // Convert timestamp to MySQL datetime
        $since_date = date( 'Y-m-d H:i:s', $timestamp );
        
        // Get new tickets (for admins and agents)
        if ( current_user_can( 'mets_view_all_tickets' ) ) {
            $new_tickets = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, subject, created_at, created_by 
                 FROM {$wpdb->prefix}mets_tickets 
                 WHERE created_at > %s 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                $since_date
            ) );
            
            if ( $new_tickets ) {
                foreach ( $new_tickets as $ticket ) {
                    $creator = get_userdata( $ticket->created_by );
                    $updates['tickets'][] = array(
                        'id' => $ticket->id,
                        'subject' => $ticket->subject,
                        'created_at' => $ticket->created_at,
                        'created_by' => $creator ? $creator->display_name : __( 'Unknown', METS_TEXT_DOMAIN )
                    );
                    
                    // Add notification
                    $updates['notifications'][] = array(
                        'type' => 'new_ticket',
                        'ticket_id' => $ticket->id,
                        'message' => sprintf( 
                            __( 'New ticket: %s', METS_TEXT_DOMAIN ), 
                            $ticket->subject 
                        ),
                        'timestamp' => strtotime( $ticket->created_at )
                    );
                }
            }
        }
        
        // Get tickets assigned to current user
        $assigned_tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.id, t.subject, t.assigned_to_changed_at 
             FROM {$wpdb->prefix}mets_tickets t
             WHERE t.assigned_to = %d 
             AND t.assigned_to_changed_at > %s 
             ORDER BY t.assigned_to_changed_at DESC 
             LIMIT 10",
            $user_id,
            $since_date
        ) );
        
        if ( $assigned_tickets ) {
            foreach ( $assigned_tickets as $ticket ) {
                $updates['assignments'][] = array(
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'assigned_at' => $ticket->assigned_to_changed_at
                );
                
                // Add notification
                $updates['notifications'][] = array(
                    'type' => 'ticket_assigned',
                    'ticket_id' => $ticket->id,
                    'message' => sprintf( 
                        __( 'Ticket assigned to you: %s', METS_TEXT_DOMAIN ), 
                        $ticket->subject 
                    ),
                    'timestamp' => strtotime( $ticket->assigned_to_changed_at )
                );
            }
        }
        
        // Get new replies on user's tickets or assigned tickets
        $replies_query = "SELECT r.id, r.ticket_id, r.created_at, r.user_id, t.subject 
                         FROM {$wpdb->prefix}mets_ticket_replies r
                         JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                         WHERE r.created_at > %s 
                         AND r.user_id != %d
                         AND (t.created_by = %d OR t.assigned_to = %d)
                         ORDER BY r.created_at DESC 
                         LIMIT 10";
                         
        $new_replies = $wpdb->get_results( $wpdb->prepare(
            $replies_query,
            $since_date,
            $user_id,
            $user_id,
            $user_id
        ) );
        
        if ( $new_replies ) {
            foreach ( $new_replies as $reply ) {
                $replier = get_userdata( $reply->user_id );
                $updates['replies'][] = array(
                    'id' => $reply->id,
                    'ticket_id' => $reply->ticket_id,
                    'subject' => $reply->subject,
                    'created_at' => $reply->created_at,
                    'created_by' => $replier ? $replier->display_name : __( 'Unknown', METS_TEXT_DOMAIN )
                );
                
                // Add notification
                $updates['notifications'][] = array(
                    'type' => 'new_reply',
                    'ticket_id' => $reply->ticket_id,
                    'message' => sprintf( 
                        __( 'New reply on: %s', METS_TEXT_DOMAIN ), 
                        $reply->subject 
                    ),
                    'timestamp' => strtotime( $reply->created_at )
                );
            }
        }
        
        // Get status changes on user's tickets
        $status_changes = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, subject, status, updated_at 
             FROM {$wpdb->prefix}mets_tickets 
             WHERE updated_at > %s 
             AND status_changed_at > %s
             AND (created_by = %d OR assigned_to = %d)
             ORDER BY updated_at DESC 
             LIMIT 10",
            $since_date,
            $since_date,
            $user_id,
            $user_id
        ) );
        
        if ( $status_changes ) {
            foreach ( $status_changes as $ticket ) {
                $updates['status_changes'][] = array(
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'updated_at' => $ticket->updated_at
                );
                
                // Add notification
                $updates['notifications'][] = array(
                    'type' => 'status_changed',
                    'ticket_id' => $ticket->id,
                    'message' => sprintf( 
                        __( 'Status changed to %s: %s', METS_TEXT_DOMAIN ), 
                        ucfirst( $ticket->status ),
                        $ticket->subject 
                    ),
                    'timestamp' => strtotime( $ticket->updated_at )
                );
            }
        }
        
        return $updates;
    }

    /**
     * Store notifications for user
     *
     * @since    1.0.0
     * @param    array    $notifications    Notifications array
     * @param    int      $user_id          User ID
     */
    private function store_notifications( $notifications, $user_id ) {
        $stored_notifications = get_user_meta( $user_id, 'mets_unread_notifications', true );
        if ( ! is_array( $stored_notifications ) ) {
            $stored_notifications = array();
        }
        
        // Add new notifications
        foreach ( $notifications as $notification ) {
            $notification['id'] = uniqid();
            $notification['read'] = false;
            $stored_notifications[] = $notification;
        }
        
        // Keep only last 50 notifications
        $stored_notifications = array_slice( $stored_notifications, -50 );
        
        update_user_meta( $user_id, 'mets_unread_notifications', $stored_notifications );
    }

    /**
     * Get user notifications
     *
     * @since    1.0.0
     */
    public function get_notifications() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mets-polling-nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = get_current_user_id();
        $notifications = get_user_meta( $user_id, 'mets_unread_notifications', true );
        
        if ( ! is_array( $notifications ) ) {
            $notifications = array();
        }
        
        // Get only unread notifications
        $unread_notifications = array_filter( $notifications, function( $notification ) {
            return ! isset( $notification['read'] ) || ! $notification['read'];
        } );
        
        wp_send_json_success( array(
            'notifications' => array_values( $unread_notifications ),
            'count' => count( $unread_notifications )
        ) );
    }

    /**
     * Mark notification as read
     *
     * @since    1.0.0
     */
    public function mark_notification_read() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mets-polling-nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = get_current_user_id();
        $notification_id = isset( $_POST['notificationId'] ) ? sanitize_text_field( $_POST['notificationId'] ) : '';
        
        if ( empty( $notification_id ) ) {
            wp_send_json_error( 'Invalid notification ID' );
        }
        
        $notifications = get_user_meta( $user_id, 'mets_unread_notifications', true );
        
        if ( ! is_array( $notifications ) ) {
            wp_send_json_success();
            return;
        }
        
        // Mark notification as read
        foreach ( $notifications as &$notification ) {
            if ( isset( $notification['id'] ) && $notification['id'] === $notification_id ) {
                $notification['read'] = true;
                break;
            }
        }
        
        update_user_meta( $user_id, 'mets_unread_notifications', $notifications );
        
        wp_send_json_success();
    }
}