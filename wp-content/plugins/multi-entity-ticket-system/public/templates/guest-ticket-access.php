<?php
/**
 * Guest Ticket Access Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This template is no longer used as we've implemented a new guest access system
// The new system uses the guest-ticket-access-form.php template
// This file is kept for backward compatibility but redirects to the new system

wp_redirect( home_url( '/guest-ticket-access/' ) );
exit;