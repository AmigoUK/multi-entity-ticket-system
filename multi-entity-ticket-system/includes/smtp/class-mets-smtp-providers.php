<?php
/**
 * SMTP Providers Configuration
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @since      1.0.0
 */

/**
 * SMTP Providers Configuration class.
 *
 * This class manages pre-configured SMTP provider settings for popular email services.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SMTP_Providers {

	/**
	 * Get all available SMTP providers
	 *
	 * @since    1.0.0
	 * @return   array    Array of provider configurations
	 */
	public static function get_providers() {
		$providers = array(
			'wordpress' => array(
				'name' => __( 'WordPress Default Mail', METS_TEXT_DOMAIN ),
				'description' => __( 'Use the default WordPress mail functionality (recommended for beginners)', METS_TEXT_DOMAIN ),
				'requires_auth' => false,
				'icon' => 'dashicons-wordpress',
			),
			'gmail' => array(
				'name' => __( 'Gmail / Google Workspace', METS_TEXT_DOMAIN ),
				'description' => __( 'Send emails through Gmail or Google Workspace accounts', METS_TEXT_DOMAIN ),
				'host' => 'smtp.gmail.com',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-google',
				'help_url' => 'https://support.google.com/mail/answer/185833',
				'note' => __( 'Requires an App Password for authentication. Enable 2-factor authentication and generate an app-specific password.', METS_TEXT_DOMAIN ),
			),
			'outlook' => array(
				'name' => __( 'Outlook / Office 365', METS_TEXT_DOMAIN ),
				'description' => __( 'Send emails through Outlook.com or Office 365 accounts', METS_TEXT_DOMAIN ),
				'host' => 'smtp.office365.com',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-email',
				'alternative_host' => 'smtp-mail.outlook.com',
			),
			'yahoo' => array(
				'name' => __( 'Yahoo Mail', METS_TEXT_DOMAIN ),
				'description' => __( 'Send emails through Yahoo Mail accounts', METS_TEXT_DOMAIN ),
				'host' => 'smtp.mail.yahoo.com',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-email-alt',
				'note' => __( 'Requires an App Password for authentication. Enable 2-step verification and generate an app password.', METS_TEXT_DOMAIN ),
			),
			'sendgrid' => array(
				'name' => __( 'SendGrid', METS_TEXT_DOMAIN ),
				'description' => __( 'Professional email delivery service by SendGrid', METS_TEXT_DOMAIN ),
				'host' => 'smtp.sendgrid.net',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-email-alt2',
				'username_note' => __( 'Use "apikey" as username', METS_TEXT_DOMAIN ),
				'password_note' => __( 'Use your SendGrid API key as password', METS_TEXT_DOMAIN ),
			),
			'mailgun' => array(
				'name' => __( 'Mailgun', METS_TEXT_DOMAIN ),
				'description' => __( 'Developer-friendly transactional email service', METS_TEXT_DOMAIN ),
				'host' => 'smtp.mailgun.org',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-email-alt2',
				'host_note' => __( 'For EU region, use smtp.eu.mailgun.org', METS_TEXT_DOMAIN ),
			),
			'amazon_ses' => array(
				'name' => __( 'Amazon SES', METS_TEXT_DOMAIN ),
				'description' => __( 'Amazon Simple Email Service for reliable email delivery', METS_TEXT_DOMAIN ),
				'host' => 'email-smtp.us-east-1.amazonaws.com',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-cloud',
				'regions' => array(
					'us-east-1' => 'email-smtp.us-east-1.amazonaws.com',
					'us-west-2' => 'email-smtp.us-west-2.amazonaws.com',
					'eu-west-1' => 'email-smtp.eu-west-1.amazonaws.com',
				),
				'note' => __( 'Requires SMTP credentials from AWS SES console', METS_TEXT_DOMAIN ),
			),
			'zoho' => array(
				'name' => __( 'Zoho Mail', METS_TEXT_DOMAIN ),
				'description' => __( 'Business email hosting by Zoho', METS_TEXT_DOMAIN ),
				'host' => 'smtp.zoho.com',
				'port' => 587,
				'encryption' => 'tls',
				'requires_auth' => true,
				'icon' => 'dashicons-email',
				'regions' => array(
					'com' => 'smtp.zoho.com',
					'eu' => 'smtp.zoho.eu',
					'in' => 'smtp.zoho.in',
					'com.au' => 'smtp.zoho.com.au',
				),
			),
			'custom' => array(
				'name' => __( 'Custom SMTP Server', METS_TEXT_DOMAIN ),
				'description' => __( 'Configure your own SMTP server settings', METS_TEXT_DOMAIN ),
				'requires_auth' => true,
				'icon' => 'dashicons-admin-generic',
				'fields_required' => array( 'host', 'port', 'encryption' ),
			),
		);

		return apply_filters( 'mets_smtp_providers', $providers );
	}

	/**
	 * Get provider configuration
	 *
	 * @since    1.0.0
	 * @param    string    $provider    Provider key
	 * @return   array|false            Provider configuration or false if not found
	 */
	public static function get_provider_config( $provider ) {
		$providers = self::get_providers();
		
		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		}
		
		return false;
	}

	/**
	 * Get encryption types
	 *
	 * @since    1.0.0
	 * @return   array    Array of encryption types
	 */
	public static function get_encryption_types() {
		return array(
			'none' => __( 'None', METS_TEXT_DOMAIN ),
			'ssl' => __( 'SSL', METS_TEXT_DOMAIN ),
			'tls' => __( 'TLS', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Get common SMTP ports
	 *
	 * @since    1.0.0
	 * @return   array    Array of common ports
	 */
	public static function get_common_ports() {
		return array(
			25 => __( '25 - Default SMTP', METS_TEXT_DOMAIN ),
			465 => __( '465 - SMTP over SSL', METS_TEXT_DOMAIN ),
			587 => __( '587 - SMTP over TLS (Recommended)', METS_TEXT_DOMAIN ),
			2525 => __( '2525 - Alternative SMTP', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Validate provider credentials
	 *
	 * @since    1.0.0
	 * @param    string    $provider       Provider key
	 * @param    array     $credentials    Credentials array
	 * @return   array                     Validation result
	 */
	public static function validate_provider_credentials( $provider, $credentials ) {
		$result = array(
			'valid' => true,
			'errors' => array(),
		);

		$config = self::get_provider_config( $provider );
		
		if ( ! $config ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Invalid provider selected', METS_TEXT_DOMAIN );
			return $result;
		}

		// Skip validation for WordPress default mail
		if ( $provider === 'wordpress' ) {
			return $result;
		}

		// Validate required fields
		if ( $config['requires_auth'] ) {
			if ( empty( $credentials['username'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Username is required', METS_TEXT_DOMAIN );
			}
			
			if ( empty( $credentials['password'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Password is required', METS_TEXT_DOMAIN );
			}
		}

		// Validate custom SMTP fields
		if ( $provider === 'custom' ) {
			if ( empty( $credentials['host'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'SMTP host is required', METS_TEXT_DOMAIN );
			}
			
			if ( empty( $credentials['port'] ) || ! is_numeric( $credentials['port'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Valid SMTP port is required', METS_TEXT_DOMAIN );
			}
			
			if ( empty( $credentials['encryption'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Encryption type is required', METS_TEXT_DOMAIN );
			}
		}

		// Validate email addresses
		if ( ! empty( $credentials['from_email'] ) && ! is_email( $credentials['from_email'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Invalid From Email address', METS_TEXT_DOMAIN );
		}
		
		if ( ! empty( $credentials['reply_to'] ) && ! is_email( $credentials['reply_to'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Invalid Reply-To Email address', METS_TEXT_DOMAIN );
		}

		return $result;
	}

	/**
	 * Get provider setup instructions
	 *
	 * @since    1.0.0
	 * @param    string    $provider    Provider key
	 * @return   array                  Setup instructions
	 */
	public static function get_provider_instructions( $provider ) {
		$instructions = array();

		switch ( $provider ) {
			case 'gmail':
				$instructions = array(
					__( 'Enable 2-factor authentication in your Google account', METS_TEXT_DOMAIN ),
					__( 'Generate an app-specific password from Google Account settings', METS_TEXT_DOMAIN ),
					__( 'Use your Gmail address as username', METS_TEXT_DOMAIN ),
					__( 'Use the app password (not your regular password) for authentication', METS_TEXT_DOMAIN ),
				);
				break;
				
			case 'outlook':
				$instructions = array(
					__( 'For personal accounts, enable 2-factor authentication', METS_TEXT_DOMAIN ),
					__( 'Generate an app password from account security settings', METS_TEXT_DOMAIN ),
					__( 'For Office 365, you may need to enable SMTP AUTH for your account', METS_TEXT_DOMAIN ),
				);
				break;
				
			case 'sendgrid':
				$instructions = array(
					__( 'Create a SendGrid account and verify your sender identity', METS_TEXT_DOMAIN ),
					__( 'Generate an API key with Mail Send permissions', METS_TEXT_DOMAIN ),
					__( 'Use "apikey" as the username', METS_TEXT_DOMAIN ),
					__( 'Use your API key as the password', METS_TEXT_DOMAIN ),
				);
				break;
				
			case 'amazon_ses':
				$instructions = array(
					__( 'Verify your domain or email address in AWS SES', METS_TEXT_DOMAIN ),
					__( 'Create SMTP credentials in the SES console', METS_TEXT_DOMAIN ),
					__( 'Select the appropriate region for your SMTP endpoint', METS_TEXT_DOMAIN ),
					__( 'Move out of sandbox mode for production use', METS_TEXT_DOMAIN ),
				);
				break;
		}

		return $instructions;
	}

	/**
	 * Get provider-specific field labels
	 *
	 * @since    1.0.0
	 * @param    string    $provider    Provider key
	 * @return   array                  Field labels
	 */
	public static function get_provider_field_labels( $provider ) {
		$labels = array(
			'username' => __( 'Username', METS_TEXT_DOMAIN ),
			'password' => __( 'Password', METS_TEXT_DOMAIN ),
		);

		switch ( $provider ) {
			case 'gmail':
			case 'outlook':
			case 'yahoo':
				$labels['username'] = __( 'Email Address', METS_TEXT_DOMAIN );
				$labels['password'] = __( 'App Password', METS_TEXT_DOMAIN );
				break;
				
			case 'sendgrid':
				$labels['username'] = __( 'Username (use "apikey")', METS_TEXT_DOMAIN );
				$labels['password'] = __( 'API Key', METS_TEXT_DOMAIN );
				break;
				
			case 'amazon_ses':
				$labels['username'] = __( 'SMTP Username', METS_TEXT_DOMAIN );
				$labels['password'] = __( 'SMTP Password', METS_TEXT_DOMAIN );
				break;
		}

		return $labels;
	}
}