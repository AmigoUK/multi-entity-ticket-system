<?php
/**
 * Test Data Manager — one-click import/remove of curated test data.
 *
 * Visible only when WP_DEBUG === true or METS_ALLOW_TEST_DATA is defined.
 *
 * @package METS
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class METS_Test_Data_Manager {

	/**
	 * Whether the test-data tool is enabled.
	 */
	public static function is_enabled() {
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || defined( 'METS_ALLOW_TEST_DATA' );
	}

	/* ------------------------------------------------------------------
	 * Data Definitions (deterministic)
	 * ----------------------------------------------------------------*/

	private function get_entities() {
		return array(
			array(
				'name'            => 'Acme Tech Support',
				'slug'            => 'acme-tech',
				'description'     => 'Technical support division of Acme Corporation.',
				'primary_color'   => '#2563EB',
				'secondary_color' => '#1E40AF',
				'status'          => 'active',
			),
			array(
				'name'            => 'Globex Billing',
				'slug'            => 'globex-billing',
				'description'     => 'Billing and payment department of Globex Inc.',
				'primary_color'   => '#059669',
				'secondary_color' => '#047857',
				'status'          => 'active',
			),
			array(
				'name'            => 'Initech Customer Care',
				'slug'            => 'initech-care',
				'description'     => 'Customer care team at Initech Solutions.',
				'primary_color'   => '#DC2626',
				'secondary_color' => '#B91C1C',
				'status'          => 'active',
			),
		);
	}

	private function get_agents() {
		return array(
			array(
				'login'    => 'agent_sarah',
				'email'    => 'sarah.agent@test.local',
				'first'    => 'Sarah',
				'last'     => 'Johnson',
				'entities' => array( 'acme-tech', 'globex-billing' ),
			),
			array(
				'login'    => 'agent_mike',
				'email'    => 'mike.agent@test.local',
				'first'    => 'Mike',
				'last'     => 'Chen',
				'entities' => array( 'acme-tech' ),
			),
			array(
				'login'    => 'agent_emma',
				'email'    => 'emma.agent@test.local',
				'first'    => 'Emma',
				'last'     => 'Williams',
				'entities' => array( 'globex-billing' ),
			),
			array(
				'login'    => 'agent_david',
				'email'    => 'david.agent@test.local',
				'first'    => 'David',
				'last'     => 'Patel',
				'entities' => array( 'initech-care' ),
			),
		);
	}

	/**
	 * 30 tickets: 10 per entity, spread across statuses/priorities/categories.
	 * Returns array of ticket data keyed by entity slug.
	 */
	private function get_tickets() {
		$statuses   = array( 'new', 'open', 'pending', 'on_hold', 'resolved', 'closed' );
		$priorities = array( 'urgent', 'high', 'normal', 'low' );
		$categories = array( 'technical', 'billing', 'general', 'feature_request' );

		$customers = array(
			array( 'name' => 'Alice Thompson', 'email' => 'alice.thompson@test.local' ),
			array( 'name' => 'Bob Martinez',   'email' => 'bob.martinez@test.local' ),
			array( 'name' => 'Carol White',    'email' => 'carol.white@test.local' ),
			array( 'name' => 'Daniel Kim',     'email' => 'daniel.kim@test.local' ),
			array( 'name' => 'Elena Rossi',    'email' => 'elena.rossi@test.local' ),
			array( 'name' => 'Frank Nguyen',   'email' => 'frank.nguyen@test.local' ),
			array( 'name' => 'Grace Lee',      'email' => 'grace.lee@test.local' ),
			array( 'name' => 'Henry Clark',    'email' => 'henry.clark@test.local' ),
			array( 'name' => 'Irene Foster',   'email' => 'irene.foster@test.local' ),
			array( 'name' => 'James Baker',    'email' => 'james.baker@test.local' ),
		);

		$entity_tickets = array(
			'acme-tech' => array(
				array( 'subject' => 'Cannot connect to VPN',                'desc' => 'I have been unable to connect to the company VPN since yesterday morning. Error message says "Connection timed out". I have tried restarting my router and computer.' ),
				array( 'subject' => 'Software license expired',             'desc' => 'My Adobe Creative Suite license has expired and I cannot open any files. I need this for a client deadline tomorrow. License key: ACME-2024-CC-XXXX.' ),
				array( 'subject' => 'Printer not responding',               'desc' => 'The third floor printer (HP LaserJet Pro) is showing offline status. Multiple team members are affected. We have already tried power cycling it.' ),
				array( 'subject' => 'Email sync issues on mobile',          'desc' => 'Emails are not syncing on my iPhone since the last iOS update. Desktop Outlook works fine. I have removed and re-added the account twice.' ),
				array( 'subject' => 'Request for additional monitor',       'desc' => 'I would like to request a second monitor for my workstation. My role in data analysis requires viewing multiple dashboards simultaneously.' ),
				array( 'subject' => 'Slow network speed in Building B',     'desc' => 'Download speeds in Building B have dropped to under 5 Mbps. Speed tests confirm the issue. Building A has normal speeds (~100 Mbps).' ),
				array( 'subject' => 'Password reset not working',           'desc' => 'The self-service password reset page keeps returning an error. I have verified my security questions are correct. Ticket is urgent as I am locked out.' ),
				array( 'subject' => 'New employee laptop setup',            'desc' => 'We have a new hire starting Monday (Jan 15). Need a standard developer laptop configured with our dev tools stack and VPN access.' ),
				array( 'subject' => 'Data backup verification',             'desc' => 'Please verify that the Q4 2025 financial data backup completed successfully. We need confirmation for the upcoming audit.' ),
				array( 'subject' => 'Conference room AV not working',       'desc' => 'The projector in Conference Room A is not detecting any input. We have an important client meeting at 2 PM today. HDMI and wireless casting both fail.' ),
			),
			'globex-billing' => array(
				array( 'subject' => 'Invoice discrepancy',                   'desc' => 'Invoice #GLX-2025-4472 shows a charge of $1,200 but our agreed rate is $950/month. Please review and issue a corrected invoice.' ),
				array( 'subject' => 'Payment method update',                 'desc' => 'I need to update our company credit card on file. The current card ending in 4242 expires next month. New card details attached securely.' ),
				array( 'subject' => 'Refund request for duplicate charge',   'desc' => 'We were charged twice for the December service fee. Transaction IDs: TXN-889901 and TXN-889902. Both for $450.00 on Dec 15.' ),
				array( 'subject' => 'Subscription upgrade inquiry',          'desc' => 'We are considering upgrading from the Business plan to Enterprise. Can you provide a prorated quote for mid-cycle upgrade for 50 seats?' ),
				array( 'subject' => 'Tax exemption certificate',             'desc' => 'Attaching our updated 501(c)(3) tax exemption certificate. Please apply this to our account so future invoices exclude sales tax.' ),
				array( 'subject' => 'Late payment fee waiver request',       'desc' => 'We received a $75 late payment fee on our January invoice. The payment was one day late due to a bank processing delay. Requesting a one-time waiver.' ),
				array( 'subject' => 'Annual billing switch',                 'desc' => 'We would like to switch from monthly to annual billing to take advantage of the 15% discount. Current plan: Business, 25 seats.' ),
				array( 'subject' => 'Custom invoice format needed',          'desc' => 'Our accounting department requires purchase order numbers on all invoices. PO format: GLX-PO-YYYY-NNNN. Can this be added to our account?' ),
				array( 'subject' => 'Credit note not applied',               'desc' => 'Credit note CN-2025-0089 for $320 was issued on Jan 5 but has not been applied to our February invoice. Please investigate.' ),
				array( 'subject' => 'Multi-currency billing question',       'desc' => 'Our EU subsidiary needs invoices in EUR instead of USD. Is it possible to set up a separate billing profile with EUR currency?' ),
			),
			'initech-care' => array(
				array( 'subject' => 'Account login issues',                  'desc' => 'I cannot log into my account despite using the correct credentials. The "Forgot Password" link sends a reset email but the link in the email is expired.' ),
				array( 'subject' => 'Feature request: dark mode',            'desc' => 'Would love to see a dark mode option in the dashboard. Working late hours and the bright interface causes eye strain. Many users on the forum agree.' ),
				array( 'subject' => 'Data export not complete',              'desc' => 'Exported my account data via Settings > Export but the CSV file is missing records from September and October 2025. Total should be ~5000 rows, got 3200.' ),
				array( 'subject' => 'Two-factor authentication setup help',  'desc' => 'I enabled 2FA but my authenticator app is not generating the correct codes. I have tried both Google Authenticator and Authy. Time sync is correct.' ),
				array( 'subject' => 'API rate limit too restrictive',        'desc' => 'Our integration is hitting the 100 req/min API rate limit. We process ~200 orders/min during peak hours. Can our limit be increased to 500 req/min?' ),
				array( 'subject' => 'Notification preferences not saving',   'desc' => 'Every time I uncheck email notifications and click Save, the preferences revert to default on page reload. Tested in Chrome 120 and Firefox 121.' ),
				array( 'subject' => 'Mobile app crashing on Android',        'desc' => 'The app crashes immediately after splash screen on Samsung Galaxy S24 running Android 15. Cleared cache and reinstalled. Other apps work fine.' ),
				array( 'subject' => 'Cancel subscription request',           'desc' => 'I would like to cancel my Premium subscription effective at the end of the current billing cycle (Feb 28). Please confirm no further charges.' ),
				array( 'subject' => 'Incorrect timezone in reports',         'desc' => 'All report timestamps show UTC instead of our configured timezone (America/New_York). This started after the January platform update.' ),
				array( 'subject' => 'Bulk import failing',                   'desc' => 'CSV bulk import fails at row 847 with error "Invalid date format". The date column uses YYYY-MM-DD format as documented. File is UTF-8 encoded.' ),
			),
		);

		$tickets = array();
		$base_date = strtotime( '-30 days' );
		$i = 0;

		foreach ( $entity_tickets as $entity_slug => $items ) {
			foreach ( $items as $idx => $item ) {
				$day_offset  = (int) ( ( $idx / 10.0 ) * 30 );
				$hour_offset = ( $idx % 8 ) + 9; // 09:00–16:00
				$created     = gmdate( 'Y-m-d H:i:s', $base_date + $day_offset * 86400 + $hour_offset * 3600 );

				$tickets[] = array(
					'entity_slug'    => $entity_slug,
					'ticket_number'  => 'T' . strtoupper( str_replace( '-', '', $entity_slug ) ) . '-' . gmdate( 'Ym', $base_date ) . '-' . str_pad( $idx + 1, 4, '0', STR_PAD_LEFT ),
					'subject'        => $item['subject'],
					'description'    => $item['desc'],
					'status'         => $statuses[ $idx % count( $statuses ) ],
					'priority'       => $priorities[ $idx % count( $priorities ) ],
					'category'       => $categories[ $idx % count( $categories ) ],
					'customer_name'  => $customers[ $idx ]['name'],
					'customer_email' => $customers[ $idx ]['email'],
					'created_at'     => $created,
					'updated_at'     => $created,
				);
				$i++;
			}
		}

		return $tickets;
	}

	/**
	 * Build 2–3 replies per ticket.
	 */
	private function get_replies_for_ticket( $ticket_idx, $ticket_created_at ) {
		$reply_templates = array(
			// Customer replies
			array( 'type' => 'customer', 'internal' => 0, 'messages' => array(
				'Thank you for looking into this. Any updates?',
				'I tried the steps you suggested but the issue persists. Here is the new error message I am seeing.',
				'This is becoming critical for our team. Could you please escalate this?',
			) ),
			// Agent replies
			array( 'type' => 'agent', 'internal' => 0, 'messages' => array(
				'Thank you for reporting this issue. I have reviewed your account and identified the root cause. Let me walk you through the next steps.',
				'I have applied a fix on our end. Could you please clear your browser cache and try again? Let me know if the issue persists.',
				'I have escalated this to our engineering team. You should expect a resolution within 24 hours. I will keep you updated.',
			) ),
			// Internal notes
			array( 'type' => 'agent', 'internal' => 1, 'messages' => array(
				'Checked the server logs — this appears to be related to the recent deployment. Flagging for the next standup.',
				'Customer is a premium account holder. Prioritizing per SLA agreement.',
				'Similar issue reported by two other customers this week. May indicate a systemic problem.',
			) ),
		);

		$base = strtotime( $ticket_created_at );
		$count = ( $ticket_idx % 2 === 0 ) ? 3 : 2;
		$replies = array();

		for ( $r = 0; $r < $count; $r++ ) {
			$group     = $reply_templates[ $r % count( $reply_templates ) ];
			$msg_idx   = $ticket_idx % count( $group['messages'] );
			$hours_gap = ( $r + 1 ) * 2; // 2h, 4h, 6h after ticket creation

			$replies[] = array(
				'user_type'        => $group['type'],
				'is_internal_note' => $group['internal'],
				'content'          => $group['messages'][ $msg_idx ],
				'created_at'       => gmdate( 'Y-m-d H:i:s', $base + $hours_gap * 3600 ),
			);
		}

		return $replies;
	}

	private function get_sla_rules() {
		return array(
			array( 'priority' => 'urgent', 'response' => 1,  'resolution' => 8,  'escalation' => 4 ),
			array( 'priority' => 'high',   'response' => 4,  'resolution' => 24, 'escalation' => 12 ),
			array( 'priority' => 'normal', 'response' => 8,  'resolution' => 48, 'escalation' => 24 ),
			array( 'priority' => 'low',    'response' => 24, 'resolution' => 72, 'escalation' => 48 ),
		);
	}

	private function get_kb_categories_map() {
		return array(
			'acme-tech'       => array(
				array( 'name' => 'Getting Started',  'slug' => 'acme-getting-started',  'icon' => 'dashicons-welcome-learn-more' ),
				array( 'name' => 'Troubleshooting',  'slug' => 'acme-troubleshooting',  'icon' => 'dashicons-sos' ),
				array( 'name' => 'API Reference',    'slug' => 'acme-api-reference',    'icon' => 'dashicons-rest-api' ),
			),
			'globex-billing'  => array(
				array( 'name' => 'Billing FAQ',       'slug' => 'globex-billing-faq',       'icon' => 'dashicons-money-alt' ),
				array( 'name' => 'Payment Methods',   'slug' => 'globex-payment-methods',   'icon' => 'dashicons-cart' ),
				array( 'name' => 'Subscription Plans', 'slug' => 'globex-subscription-plans', 'icon' => 'dashicons-clipboard' ),
			),
			'initech-care'    => array(
				array( 'name' => 'Account Management', 'slug' => 'initech-account-mgmt',   'icon' => 'dashicons-admin-users' ),
				array( 'name' => 'Security',           'slug' => 'initech-security',        'icon' => 'dashicons-shield' ),
				array( 'name' => 'Contact Info',       'slug' => 'initech-contact-info',    'icon' => 'dashicons-phone' ),
			),
		);
	}

	private function get_kb_tags() {
		return array(
			array( 'name' => 'how-to',          'slug' => 'how-to' ),
			array( 'name' => 'faq',             'slug' => 'faq' ),
			array( 'name' => 'troubleshooting', 'slug' => 'troubleshooting' ),
			array( 'name' => 'billing',         'slug' => 'billing' ),
			array( 'name' => 'security',        'slug' => 'security' ),
		);
	}

	private function get_kb_articles() {
		return array(
			// Acme articles (4)
			array(
				'entity_slug'    => 'acme-tech',
				'title'          => 'How to Connect to the Company VPN',
				'slug'           => 'acme-how-to-connect-vpn',
				'category_slugs' => array( 'acme-getting-started' ),
				'tag_slugs'      => array( 'how-to' ),
				'content'        => "<h2>Prerequisites</h2>\n<p>Before connecting, ensure you have the latest VPN client installed (v3.2 or higher).</p>\n<h2>Steps</h2>\n<ol>\n<li>Open the VPN client application.</li>\n<li>Enter the server address: <code>vpn.acme-corp.test</code></li>\n<li>Enter your corporate credentials.</li>\n<li>Select the appropriate gateway for your region.</li>\n<li>Click Connect.</li>\n</ol>\n<h2>Troubleshooting</h2>\n<p>If the connection times out, try switching to TCP mode in Settings > Protocol.</p>",
				'excerpt'        => 'Step-by-step guide for connecting to the Acme corporate VPN.',
				'view_count'     => 342,
				'helpful_count'  => 89,
			),
			array(
				'entity_slug'    => 'acme-tech',
				'title'          => 'Troubleshooting Printer Connectivity Issues',
				'slug'           => 'acme-troubleshooting-printers',
				'category_slugs' => array( 'acme-troubleshooting' ),
				'tag_slugs'      => array( 'troubleshooting' ),
				'content'        => "<h2>Common Printer Issues</h2>\n<p>Most printer problems can be resolved with these steps:</p>\n<ol>\n<li>Check that the printer is powered on and shows a ready status.</li>\n<li>Verify the network cable is securely connected (for wired printers).</li>\n<li>Restart the print spooler service on your computer.</li>\n<li>Try removing and re-adding the printer in system settings.</li>\n</ol>\n<h2>Still Not Working?</h2>\n<p>Submit a ticket with the printer model, location, and error message.</p>",
				'excerpt'        => 'Common fixes for printer connectivity problems.',
				'view_count'     => 215,
				'helpful_count'  => 67,
			),
			array(
				'entity_slug'    => 'acme-tech',
				'title'          => 'REST API Authentication Guide',
				'slug'           => 'acme-api-authentication',
				'category_slugs' => array( 'acme-api-reference' ),
				'tag_slugs'      => array( 'how-to', 'security' ),
				'content'        => "<h2>Authentication Methods</h2>\n<p>The Acme API supports two authentication methods:</p>\n<h3>API Key</h3>\n<p>Include your API key in the <code>X-API-Key</code> header with every request.</p>\n<h3>OAuth 2.0</h3>\n<p>For server-to-server integrations, use the OAuth 2.0 client credentials flow.</p>\n<h2>Rate Limits</h2>\n<p>Authenticated requests are limited to 1000 requests per minute.</p>",
				'excerpt'        => 'How to authenticate with the Acme REST API.',
				'view_count'     => 178,
				'helpful_count'  => 52,
			),
			array(
				'entity_slug'    => 'acme-tech',
				'title'          => 'Setting Up Your Developer Workstation',
				'slug'           => 'acme-dev-workstation-setup',
				'category_slugs' => array( 'acme-getting-started' ),
				'tag_slugs'      => array( 'how-to' ),
				'content'        => "<h2>Required Software</h2>\n<ul>\n<li>Git 2.40+</li>\n<li>Node.js 20 LTS</li>\n<li>Docker Desktop</li>\n<li>VS Code with the Acme Extension Pack</li>\n</ul>\n<h2>Configuration</h2>\n<p>Clone the starter repo and run <code>./setup.sh</code> to configure your environment automatically.</p>",
				'excerpt'        => 'Everything you need to set up a new developer workstation.',
				'view_count'     => 410,
				'helpful_count'  => 124,
			),
			// Globex articles (4)
			array(
				'entity_slug'    => 'globex-billing',
				'title'          => 'Understanding Your Monthly Invoice',
				'slug'           => 'globex-understanding-invoice',
				'category_slugs' => array( 'globex-billing-faq' ),
				'tag_slugs'      => array( 'faq', 'billing' ),
				'content'        => "<h2>Invoice Sections</h2>\n<p>Your Globex invoice includes the following sections:</p>\n<ol>\n<li><strong>Account Summary</strong> — overview of charges and credits.</li>\n<li><strong>Service Charges</strong> — itemized list of services used.</li>\n<li><strong>Taxes &amp; Fees</strong> — applicable taxes and regulatory fees.</li>\n<li><strong>Payment Due</strong> — total amount and due date.</li>\n</ol>\n<h2>Payment Terms</h2>\n<p>Invoices are due within 30 days of issue. Late payments incur a 1.5% monthly fee.</p>",
				'excerpt'        => 'A guide to reading and understanding your Globex monthly invoice.',
				'view_count'     => 523,
				'helpful_count'  => 198,
			),
			array(
				'entity_slug'    => 'globex-billing',
				'title'          => 'Accepted Payment Methods',
				'slug'           => 'globex-accepted-payments',
				'category_slugs' => array( 'globex-payment-methods' ),
				'tag_slugs'      => array( 'faq', 'billing' ),
				'content'        => "<h2>We Accept</h2>\n<ul>\n<li>Visa, Mastercard, American Express</li>\n<li>ACH / Direct Debit (US bank accounts)</li>\n<li>Wire Transfer (for invoices over $5,000)</li>\n<li>PayPal Business</li>\n</ul>\n<h2>Updating Payment Method</h2>\n<p>Go to Account Settings > Billing > Payment Method to update your card or bank details.</p>",
				'excerpt'        => 'All payment methods accepted by Globex Billing.',
				'view_count'     => 287,
				'helpful_count'  => 93,
			),
			array(
				'entity_slug'    => 'globex-billing',
				'title'          => 'Plan Comparison: Business vs Enterprise',
				'slug'           => 'globex-plan-comparison',
				'category_slugs' => array( 'globex-subscription-plans' ),
				'tag_slugs'      => array( 'faq' ),
				'content'        => "<h2>Feature Comparison</h2>\n<table>\n<tr><th>Feature</th><th>Business</th><th>Enterprise</th></tr>\n<tr><td>Users</td><td>Up to 50</td><td>Unlimited</td></tr>\n<tr><td>Storage</td><td>100 GB</td><td>1 TB</td></tr>\n<tr><td>Support</td><td>Email</td><td>24/7 Phone + Email</td></tr>\n<tr><td>SLA</td><td>99.9%</td><td>99.99%</td></tr>\n<tr><td>SSO</td><td>No</td><td>Yes</td></tr>\n</table>\n<h2>Upgrade</h2>\n<p>Contact your account manager or submit a billing ticket to upgrade mid-cycle.</p>",
				'excerpt'        => 'Side-by-side comparison of Business and Enterprise plans.',
				'view_count'     => 612,
				'helpful_count'  => 156,
			),
			array(
				'entity_slug'    => 'globex-billing',
				'title'          => 'How to Request a Refund',
				'slug'           => 'globex-refund-process',
				'category_slugs' => array( 'globex-billing-faq' ),
				'tag_slugs'      => array( 'how-to', 'billing' ),
				'content'        => "<h2>Refund Policy</h2>\n<p>Refunds are available within 30 days of a charge for:</p>\n<ul>\n<li>Duplicate charges</li>\n<li>Service outages exceeding SLA thresholds</li>\n<li>Billing errors</li>\n</ul>\n<h2>How to Request</h2>\n<p>Submit a billing ticket with the transaction ID and reason. Refunds are processed within 5–10 business days.</p>",
				'excerpt'        => 'Step-by-step guide to requesting a refund from Globex.',
				'view_count'     => 189,
				'helpful_count'  => 74,
			),
			// Initech articles (4)
			array(
				'entity_slug'    => 'initech-care',
				'title'          => 'Managing Your Account Settings',
				'slug'           => 'initech-account-settings',
				'category_slugs' => array( 'initech-account-mgmt' ),
				'tag_slugs'      => array( 'how-to' ),
				'content'        => "<h2>Account Settings</h2>\n<p>Access your account settings from the top-right menu > My Account.</p>\n<h3>Profile</h3>\n<p>Update your name, email, timezone, and profile picture.</p>\n<h3>Notifications</h3>\n<p>Choose which email and in-app notifications you receive.</p>\n<h3>Connected Apps</h3>\n<p>Manage third-party app connections and revoke access as needed.</p>",
				'excerpt'        => 'How to manage your Initech account settings and preferences.',
				'view_count'     => 445,
				'helpful_count'  => 112,
			),
			array(
				'entity_slug'    => 'initech-care',
				'title'          => 'Enabling Two-Factor Authentication',
				'slug'           => 'initech-enable-2fa',
				'category_slugs' => array( 'initech-security' ),
				'tag_slugs'      => array( 'how-to', 'security' ),
				'content'        => "<h2>Why Enable 2FA?</h2>\n<p>Two-factor authentication adds an extra layer of security to your account.</p>\n<h2>Setup Steps</h2>\n<ol>\n<li>Go to Account Settings > Security.</li>\n<li>Click Enable Two-Factor Authentication.</li>\n<li>Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.).</li>\n<li>Enter the 6-digit code to verify.</li>\n<li>Save your backup codes in a secure location.</li>\n</ol>",
				'excerpt'        => 'Secure your account with two-factor authentication.',
				'view_count'     => 367,
				'helpful_count'  => 145,
			),
			array(
				'entity_slug'    => 'initech-care',
				'title'          => 'Contact Support Channels',
				'slug'           => 'initech-contact-support',
				'category_slugs' => array( 'initech-contact-info' ),
				'tag_slugs'      => array( 'faq' ),
				'content'        => "<h2>Support Hours</h2>\n<p>Monday–Friday, 9:00 AM – 5:00 PM (Eastern Time).</p>\n<h2>Channels</h2>\n<ul>\n<li><strong>Email:</strong> support@initech.test</li>\n<li><strong>Phone:</strong> +1 (555) 0199</li>\n<li><strong>Live Chat:</strong> Available on our website during business hours.</li>\n<li><strong>Ticket System:</strong> Submit a ticket through your dashboard.</li>\n</ul>",
				'excerpt'        => 'All the ways to reach Initech customer support.',
				'view_count'     => 289,
				'helpful_count'  => 78,
			),
			array(
				'entity_slug'    => 'initech-care',
				'title'          => 'Password Security Best Practices',
				'slug'           => 'initech-password-security',
				'category_slugs' => array( 'initech-security' ),
				'tag_slugs'      => array( 'security' ),
				'content'        => "<h2>Strong Password Guidelines</h2>\n<ul>\n<li>Use at least 12 characters.</li>\n<li>Include uppercase, lowercase, numbers, and symbols.</li>\n<li>Never reuse passwords across services.</li>\n<li>Use a password manager.</li>\n</ul>\n<h2>Password Rotation</h2>\n<p>We recommend changing your password every 90 days. You will receive a reminder 7 days before the rotation period.</p>",
				'excerpt'        => 'Tips for keeping your Initech account password secure.',
				'view_count'     => 198,
				'helpful_count'  => 86,
			),
		);
	}

	/* ------------------------------------------------------------------
	 * Admin Page Rendering
	 * ----------------------------------------------------------------*/

	public function display_test_data_page() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'multi-entity-ticket-system' ) );
		}

		$stored = get_option( 'mets_test_data_ids', array() );
		$has_data = ! empty( $stored );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Test Data Manager', 'multi-entity-ticket-system' ); ?></h1>

			<div class="notice notice-warning" style="padding:12px;">
				<strong><?php esc_html_e( 'Dev/staging tool only.', 'multi-entity-ticket-system' ); ?></strong>
				<?php esc_html_e( 'This page is visible because WP_DEBUG is enabled or METS_ALLOW_TEST_DATA is defined.', 'multi-entity-ticket-system' ); ?>
			</div>

			<div class="card" style="max-width:600px;margin-top:16px;padding:20px;">
				<h2 style="margin-top:0;">
					<?php esc_html_e( 'Status:', 'multi-entity-ticket-system' ); ?>
					<?php if ( $has_data ) : ?>
						<span style="color:#059669;">&#9679; <?php esc_html_e( 'Test data loaded', 'multi-entity-ticket-system' ); ?></span>
					<?php else : ?>
						<span style="color:#6B7280;">&#9679; <?php esc_html_e( 'No test data', 'multi-entity-ticket-system' ); ?></span>
					<?php endif; ?>
				</h2>

				<div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:6px;padding:12px 16px;margin:16px 0;">
					<strong><?php esc_html_e( 'What gets imported:', 'multi-entity-ticket-system' ); ?></strong>
					<ul style="margin:8px 0 0 20px;">
						<li><?php esc_html_e( '3 entities, 4 agent users, 30 tickets', 'multi-entity-ticket-system' ); ?></li>
						<li><?php esc_html_e( '60-90 ticket replies, 12 SLA rules, 15 business hours', 'multi-entity-ticket-system' ); ?></li>
						<li><?php esc_html_e( '9 KB categories, 5 KB tags, 12 KB articles', 'multi-entity-ticket-system' ); ?></li>
					</ul>
				</div>

				<div style="display:flex;gap:12px;margin-top:20px;">
					<?php if ( ! $has_data ) : ?>
						<button id="mets-import-test-data" class="button button-primary button-hero">
							<?php esc_html_e( 'Import Test Data', 'multi-entity-ticket-system' ); ?>
						</button>
					<?php else : ?>
						<button id="mets-import-test-data" class="button button-primary button-hero" disabled>
							<?php esc_html_e( 'Import Test Data', 'multi-entity-ticket-system' ); ?>
						</button>
						<button id="mets-remove-test-data" class="button button-hero" style="background:#DC2626;border-color:#DC2626;color:#fff;">
							<?php esc_html_e( 'Remove All Test Data', 'multi-entity-ticket-system' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<div id="mets-test-data-status" style="margin-top:16px;"></div>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * AJAX: Import
	 * ----------------------------------------------------------------*/

	public function ajax_import_test_data() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		if ( ! self::is_enabled() ) {
			wp_send_json_error( array( 'message' => 'Test data tool is not enabled.' ) );
		}

		$existing = get_option( 'mets_test_data_ids', array() );
		if ( ! empty( $existing ) ) {
			wp_send_json_error( array( 'message' => 'Test data already exists. Remove it first.' ) );
		}

		global $wpdb;
		$ids = array();

		try {
			// 1. Entities
			$ids['entities'] = $this->create_entities( $wpdb );

			// 2. Agent WP users
			$ids['users'] = $this->create_agent_users();

			// 3. User–entity assignments
			$ids['user_entities'] = $this->create_user_entities( $wpdb, $ids['entities'], $ids['users'] );

			// 4. SLA rules
			$ids['sla_rules'] = $this->create_sla_rules( $wpdb, $ids['entities'] );

			// 5. Business hours
			$ids['business_hours'] = $this->create_business_hours( $wpdb, $ids['entities'] );

			// 6. Tickets
			$ids['tickets'] = $this->create_tickets( $wpdb, $ids['entities'], $ids['users'] );

			// 7. Ticket replies
			$ids['ticket_replies'] = $this->create_ticket_replies( $wpdb, $ids['tickets'], $ids['users'] );

			// 8–10. KB data (only if KB tables exist)
			$kb_tables_exist = $this->table_exists( $wpdb, 'mets_kb_categories' );
			if ( $kb_tables_exist ) {
				$ids['kb_categories'] = $this->create_kb_categories( $wpdb, $ids['entities'] );
				$ids['kb_tags'] = $this->create_kb_tags( $wpdb );

				$kb_result = $this->create_kb_articles( $wpdb, $ids['entities'], $ids['kb_categories'], $ids['kb_tags'] );
				$ids['kb_articles']            = $kb_result['article_ids'];
				$ids['kb_article_categories']  = $kb_result['category_links'];
				$ids['kb_article_tags']        = $kb_result['tag_links'];
			} else {
				$ids['kb_categories'] = array();
				$ids['kb_tags']       = array();
				$ids['kb_articles']   = array();
			}

			update_option( 'mets_test_data_ids', $ids, false );

			wp_send_json_success( array(
				'message' => sprintf(
					'Imported: %d entities, %d agents, %d tickets, %d replies, %d SLA rules, %d business hours, %d KB categories, %d KB tags, %d KB articles.',
					count( $ids['entities'] ),
					count( $ids['users'] ),
					count( $ids['tickets'] ),
					count( $ids['ticket_replies'] ),
					count( $ids['sla_rules'] ),
					count( $ids['business_hours'] ),
					count( $ids['kb_categories'] ),
					count( $ids['kb_tags'] ),
					count( $ids['kb_articles'] )
				),
			) );
		} catch ( \Exception $e ) {
			// Save partial IDs so remove can clean up
			update_option( 'mets_test_data_ids', $ids, false );
			wp_send_json_error( array( 'message' => 'Import failed: ' . $e->getMessage() ) );
		}
	}

	/* ------------------------------------------------------------------
	 * AJAX: Remove
	 * ----------------------------------------------------------------*/

	public function ajax_remove_test_data() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$ids = get_option( 'mets_test_data_ids', array() );
		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No test data to remove.' ) );
		}

		global $wpdb;

		// Remove in reverse FK order

		// 1. KB article–tag links
		if ( ! empty( $ids['kb_article_tags'] ) && $this->table_exists( $wpdb, 'mets_kb_article_tags' ) ) {
			foreach ( $ids['kb_article_tags'] as $link ) {
				$wpdb->delete( $wpdb->prefix . 'mets_kb_article_tags', array(
					'article_id' => $link['article_id'],
					'tag_id'     => $link['tag_id'],
				), array( '%d', '%d' ) );
			}
		}

		// 2. KB article–category links
		if ( ! empty( $ids['kb_article_categories'] ) && $this->table_exists( $wpdb, 'mets_kb_article_categories' ) ) {
			foreach ( $ids['kb_article_categories'] as $link ) {
				$wpdb->delete( $wpdb->prefix . 'mets_kb_article_categories', array(
					'article_id'  => $link['article_id'],
					'category_id' => $link['category_id'],
				), array( '%d', '%d' ) );
			}
		}

		// 3. KB articles
		if ( ! empty( $ids['kb_articles'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_kb_articles', $ids['kb_articles'] );
		}

		// 4. KB tags
		if ( ! empty( $ids['kb_tags'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_kb_tags', $ids['kb_tags'] );
		}

		// 5. KB categories
		if ( ! empty( $ids['kb_categories'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_kb_categories', $ids['kb_categories'] );
		}

		// 6. Ticket replies
		if ( ! empty( $ids['ticket_replies'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_ticket_replies', $ids['ticket_replies'] );
		}

		// 7. Tickets
		if ( ! empty( $ids['tickets'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_tickets', $ids['tickets'] );
		}

		// 8. Business hours
		if ( ! empty( $ids['business_hours'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_business_hours', $ids['business_hours'] );
		}

		// 9. SLA rules
		if ( ! empty( $ids['sla_rules'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_sla_rules', $ids['sla_rules'] );
		}

		// 10. User–entity assignments
		if ( ! empty( $ids['user_entities'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_user_entities', $ids['user_entities'] );
		}

		// 11. WP users
		if ( ! empty( $ids['users'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			foreach ( $ids['users'] as $user_id ) {
				wp_delete_user( $user_id );
			}
		}

		// 12. Entities
		if ( ! empty( $ids['entities'] ) ) {
			$this->delete_by_ids( $wpdb, 'mets_entities', array_values( $ids['entities'] ) );
		}

		// 13. Clean up option
		delete_option( 'mets_test_data_ids' );

		wp_send_json_success( array( 'message' => 'All test data removed successfully.' ) );
	}

	/* ------------------------------------------------------------------
	 * Private Helpers: Creation
	 * ----------------------------------------------------------------*/

	private function create_entities( $wpdb ) {
		$table = $wpdb->prefix . 'mets_entities';
		$map = array(); // slug => id

		foreach ( $this->get_entities() as $entity ) {
			$slug = $entity['slug'];

			// Collision check
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE slug = %s", $slug
			) );
			if ( $existing ) {
				$slug .= '-test';
				$entity['slug'] = $slug;
			}

			$now = current_time( 'mysql', true );
			$wpdb->insert( $table, array(
				'name'            => $entity['name'],
				'slug'            => $slug,
				'description'     => $entity['description'],
				'primary_color'   => $entity['primary_color'],
				'secondary_color' => $entity['secondary_color'],
				'status'          => $entity['status'],
				'created_at'      => $now,
				'updated_at'      => $now,
			), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );

			$map[ $entity['slug'] ] = $wpdb->insert_id;
		}

		return $map; // slug => id
	}

	private function create_agent_users() {
		$user_ids = array();

		foreach ( $this->get_agents() as $agent ) {
			$login = $agent['login'];

			// Collision check
			if ( username_exists( $login ) ) {
				$suffix = 2;
				while ( username_exists( $login . $suffix ) ) {
					$suffix++;
				}
				$login = $login . $suffix;
			}

			$user_id = wp_insert_user( array(
				'user_login' => $login,
				'user_pass'  => 'TestAgent123!',
				'user_email' => $agent['email'],
				'first_name' => $agent['first'],
				'last_name'  => $agent['last'],
				'role'       => 'ticket_agent',
			) );

			if ( is_wp_error( $user_id ) ) {
				throw new \Exception( 'Failed to create user ' . $login . ': ' . $user_id->get_error_message() );
			}

			$user_ids[ $agent['login'] ] = $user_id;
		}

		return $user_ids; // original_login => id
	}

	private function create_user_entities( $wpdb, $entity_map, $user_map ) {
		$table = $wpdb->prefix . 'mets_user_entities';
		$ids = array();
		$now = current_time( 'mysql', true );

		foreach ( $this->get_agents() as $agent ) {
			$user_id = isset( $user_map[ $agent['login'] ] ) ? $user_map[ $agent['login'] ] : 0;
			if ( ! $user_id ) {
				continue;
			}

			foreach ( $agent['entities'] as $entity_slug ) {
				$entity_id = isset( $entity_map[ $entity_slug ] ) ? $entity_map[ $entity_slug ] : 0;
				if ( ! $entity_id ) {
					continue;
				}

				$wpdb->insert( $table, array(
					'user_id'    => $user_id,
					'entity_id'  => $entity_id,
					'role'       => 'agent',
					'created_at' => $now,
				), array( '%d', '%d', '%s', '%s' ) );

				$ids[] = $wpdb->insert_id;
			}
		}

		return $ids;
	}

	private function create_sla_rules( $wpdb, $entity_map ) {
		$table = $wpdb->prefix . 'mets_sla_rules';
		$ids = array();
		$now = current_time( 'mysql', true );
		$rules = $this->get_sla_rules();

		foreach ( $entity_map as $slug => $entity_id ) {
			foreach ( $rules as $rule ) {
				$name = ucfirst( $rule['priority'] ) . ' Priority SLA';
				$wpdb->insert( $table, array(
					'entity_id'             => $entity_id,
					'name'                  => $name,
					'priority'              => $rule['priority'],
					'response_time_hours'   => $rule['response'],
					'resolution_time_hours' => $rule['resolution'],
					'escalation_time_hours' => $rule['escalation'],
					'business_hours_only'   => 1,
					'is_active'             => 1,
					'created_at'            => $now,
					'updated_at'            => $now,
				), array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ) );

				$ids[] = $wpdb->insert_id;
			}
		}

		return $ids;
	}

	private function create_business_hours( $wpdb, $entity_map ) {
		$table = $wpdb->prefix . 'mets_business_hours';
		$ids = array();

		foreach ( $entity_map as $slug => $entity_id ) {
			// Mon(1) through Fri(5)
			for ( $day = 1; $day <= 5; $day++ ) {
				$wpdb->insert( $table, array(
					'entity_id'  => $entity_id,
					'day_of_week' => $day,
					'start_time' => '09:00:00',
					'end_time'   => '17:00:00',
					'is_active'  => 1,
				), array( '%d', '%d', '%s', '%s', '%d' ) );

				$ids[] = $wpdb->insert_id;
			}
		}

		return $ids;
	}

	private function create_tickets( $wpdb, $entity_map, $user_map ) {
		$table = $wpdb->prefix . 'mets_tickets';
		$ids = array();
		$tickets = $this->get_tickets();

		// Build a simple round-robin of agent IDs for assignment
		$agent_ids = array_values( $user_map );

		foreach ( $tickets as $idx => $ticket ) {
			$entity_slug = $ticket['entity_slug'];
			$entity_id = isset( $entity_map[ $entity_slug ] ) ? $entity_map[ $entity_slug ] : 0;
			if ( ! $entity_id ) {
				continue;
			}

			$assigned_to = null;
			if ( $ticket['status'] !== 'new' && ! empty( $agent_ids ) ) {
				$assigned_to = $agent_ids[ $idx % count( $agent_ids ) ];
			}

			$wpdb->insert( $table, array(
				'entity_id'      => $entity_id,
				'ticket_number'  => $ticket['ticket_number'],
				'subject'        => $ticket['subject'],
				'description'    => $ticket['description'],
				'status'         => $ticket['status'],
				'priority'       => $ticket['priority'],
				'category'       => $ticket['category'],
				'customer_name'  => $ticket['customer_name'],
				'customer_email' => $ticket['customer_email'],
				'assigned_to'    => $assigned_to,
				'created_at'     => $ticket['created_at'],
				'updated_at'     => $ticket['updated_at'],
			), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );

			$ids[] = $wpdb->insert_id;
		}

		return $ids;
	}

	private function create_ticket_replies( $wpdb, $ticket_ids, $user_map ) {
		$table = $wpdb->prefix . 'mets_ticket_replies';
		$ids = array();
		$agent_ids = array_values( $user_map );
		$tickets = $this->get_tickets();

		foreach ( $ticket_ids as $idx => $ticket_id ) {
			if ( ! isset( $tickets[ $idx ] ) ) {
				continue;
			}

			$replies = $this->get_replies_for_ticket( $idx, $tickets[ $idx ]['created_at'] );

			foreach ( $replies as $r_idx => $reply ) {
				$user_id = null;
				if ( $reply['user_type'] === 'agent' && ! empty( $agent_ids ) ) {
					$user_id = $agent_ids[ ( $idx + $r_idx ) % count( $agent_ids ) ];
				}

				$wpdb->insert( $table, array(
					'ticket_id'        => $ticket_id,
					'user_id'          => $user_id,
					'user_type'        => $reply['user_type'],
					'content'          => $reply['content'],
					'is_internal_note' => $reply['is_internal_note'],
					'created_at'       => $reply['created_at'],
				), array( '%d', '%d', '%s', '%s', '%d', '%s' ) );

				$ids[] = $wpdb->insert_id;
			}
		}

		return $ids;
	}

	private function create_kb_categories( $wpdb, $entity_map ) {
		$table = $wpdb->prefix . 'mets_kb_categories';
		$map = array(); // slug => id
		$now = current_time( 'mysql', true );
		$categories_map = $this->get_kb_categories_map();

		foreach ( $categories_map as $entity_slug => $categories ) {
			$entity_id = isset( $entity_map[ $entity_slug ] ) ? $entity_map[ $entity_slug ] : 0;
			if ( ! $entity_id ) {
				continue;
			}

			foreach ( $categories as $order => $cat ) {
				$wpdb->insert( $table, array(
					'entity_id'  => $entity_id,
					'name'       => $cat['name'],
					'slug'       => $cat['slug'],
					'icon'       => $cat['icon'],
					'sort_order' => $order,
					'created_at' => $now,
					'updated_at' => $now,
				), array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' ) );

				$map[ $cat['slug'] ] = $wpdb->insert_id;
			}
		}

		return $map; // slug => id
	}

	private function create_kb_tags( $wpdb ) {
		$table = $wpdb->prefix . 'mets_kb_tags';
		$map = array(); // slug => id
		$now = current_time( 'mysql', true );

		foreach ( $this->get_kb_tags() as $tag ) {
			// Check for existing slug
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE slug = %s", $tag['slug']
			) );
			if ( $existing ) {
				$map[ $tag['slug'] ] = (int) $existing;
				continue;
			}

			$wpdb->insert( $table, array(
				'name'       => $tag['name'],
				'slug'       => $tag['slug'],
				'created_at' => $now,
			), array( '%s', '%s', '%s' ) );

			$map[ $tag['slug'] ] = $wpdb->insert_id;
		}

		return $map; // slug => id
	}

	private function create_kb_articles( $wpdb, $entity_map, $category_map, $tag_map ) {
		$articles_table    = $wpdb->prefix . 'mets_kb_articles';
		$cat_link_table    = $wpdb->prefix . 'mets_kb_article_categories';
		$tag_link_table    = $wpdb->prefix . 'mets_kb_article_tags';
		$article_ids       = array();
		$cat_links         = array();
		$tag_links         = array();
		$now               = current_time( 'mysql', true );
		$admin_id          = get_current_user_id();

		foreach ( $this->get_kb_articles() as $article ) {
			$entity_id = isset( $entity_map[ $article['entity_slug'] ] ) ? $entity_map[ $article['entity_slug'] ] : 0;
			if ( ! $entity_id ) {
				continue;
			}

			$wpdb->insert( $articles_table, array(
				'entity_id'     => $entity_id,
				'title'         => $article['title'],
				'slug'          => $article['slug'],
				'content'       => $article['content'],
				'excerpt'       => $article['excerpt'],
				'author_id'     => $admin_id,
				'status'        => 'published',
				'visibility'    => 'customer',
				'view_count'    => $article['view_count'],
				'helpful_count' => $article['helpful_count'],
				'created_at'    => $now,
				'updated_at'    => $now,
				'published_at'  => $now,
			), array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ) );

			$art_id = $wpdb->insert_id;
			$article_ids[] = $art_id;

			// Category links
			foreach ( $article['category_slugs'] as $i => $cat_slug ) {
				$cat_id = isset( $category_map[ $cat_slug ] ) ? $category_map[ $cat_slug ] : 0;
				if ( $cat_id ) {
					$wpdb->insert( $cat_link_table, array(
						'article_id'  => $art_id,
						'category_id' => $cat_id,
						'is_primary'  => ( $i === 0 ) ? 1 : 0,
						'created_at'  => $now,
					), array( '%d', '%d', '%d', '%s' ) );

					$cat_links[] = array( 'article_id' => $art_id, 'category_id' => $cat_id );
				}
			}

			// Tag links
			foreach ( $article['tag_slugs'] as $tag_slug ) {
				$tag_id = isset( $tag_map[ $tag_slug ] ) ? $tag_map[ $tag_slug ] : 0;
				if ( $tag_id ) {
					$wpdb->insert( $tag_link_table, array(
						'article_id' => $art_id,
						'tag_id'     => $tag_id,
						'created_at' => $now,
					), array( '%d', '%d', '%s' ) );

					$tag_links[] = array( 'article_id' => $art_id, 'tag_id' => $tag_id );
				}
			}
		}

		return array(
			'article_ids'       => $article_ids,
			'category_links'    => $cat_links,
			'tag_links'         => $tag_links,
		);
	}

	/* ------------------------------------------------------------------
	 * Private Helpers: Deletion
	 * ----------------------------------------------------------------*/

	private function table_exists( $wpdb, $table_suffix ) {
		$table = $wpdb->prefix . $table_suffix;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	private function delete_by_ids( $wpdb, $table_suffix, $ids ) {
		if ( ! $this->table_exists( $wpdb, $table_suffix ) ) {
			return;
		}
		$table = $wpdb->prefix . $table_suffix;
		foreach ( $ids as $id ) {
			if ( $id ) {
				$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
			}
		}
	}
}
