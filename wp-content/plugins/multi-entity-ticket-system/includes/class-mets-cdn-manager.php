<?php
/**
 * CDN Management and Asset Optimization
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_CDN_Manager {

	/**
	 * CDN configuration
	 */
	private $cdn_config = array();
	
	/**
	 * Asset versioning for cache busting
	 */
	private $asset_version = '1.0.0';
	
	/**
	 * Supported CDN providers
	 */
	private $supported_cdns = array(
		'cloudflare' => 'Cloudflare',
		'aws_cloudfront' => 'AWS CloudFront',
		'keycdn' => 'KeyCDN',
		'bunnycdn' => 'BunnyNet CDN',
		'custom' => 'Custom CDN'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_cdn_config();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Asset optimization hooks
		add_filter( 'style_loader_src', array( $this, 'cdn_rewrite_css' ), 10, 2 );
		add_filter( 'script_loader_src', array( $this, 'cdn_rewrite_js' ), 10, 2 );
		add_filter( 'wp_get_attachment_url', array( $this, 'cdn_rewrite_attachment' ), 10, 2 );
		
		// Content filtering for images
		add_filter( 'the_content', array( $this, 'cdn_rewrite_content_urls' ) );
		add_filter( 'mets_email_content', array( $this, 'cdn_rewrite_content_urls' ) );
		
		// Asset optimization
		add_action( 'wp_enqueue_scripts', array( $this, 'optimize_frontend_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'optimize_admin_assets' ), 5 );
		
		// Cache preloading
		add_action( 'wp_head', array( $this, 'add_resource_hints' ) );
		add_action( 'wp_head', array( $this, 'add_preload_tags' ) );
		
		// CDN cache purging
		add_action( 'mets_ticket_created', array( $this, 'purge_ticket_cache' ) );
		add_action( 'mets_ticket_updated', array( $this, 'purge_ticket_cache' ) );
		add_action( 'mets_kb_article_published', array( $this, 'purge_kb_cache' ) );
	}

	/**
	 * Load CDN configuration
	 */
	private function load_cdn_config() {
		$this->cdn_config = get_option( 'mets_cdn_config', array(
			'enabled' => false,
			'provider' => 'cloudflare',
			'domain' => '',
			'zones' => array(
				'css' => true,
				'js' => true,
				'images' => true,
				'fonts' => true,
				'documents' => false
			),
			'cache_control' => array(
				'css' => 31536000,     // 1 year
				'js' => 31536000,      // 1 year
				'images' => 2592000,   // 30 days
				'fonts' => 31536000,   // 1 year
				'documents' => 86400   // 1 day
			),
			'optimization' => array(
				'minify_css' => true,
				'minify_js' => true,
				'combine_css' => true,
				'combine_js' => false, // Can break functionality
				'lazy_load_images' => true,
				'webp_conversion' => true,
				'critical_css' => true
			)
		) );
	}

	/**
	 * Rewrite CSS URLs to use CDN
	 */
	public function cdn_rewrite_css( $src, $handle ) {
		if ( ! $this->is_cdn_enabled( 'css' ) ) {
			return $src;
		}

		// Skip external URLs
		if ( $this->is_external_url( $src ) ) {
			return $src;
		}

		// Add versioning and CDN rewrite
		$cdn_src = $this->rewrite_url( $src );
		
		// Add cache busting
		$cdn_src = $this->add_cache_busting( $cdn_src, $handle );
		
		return $cdn_src;
	}

	/**
	 * Rewrite JavaScript URLs to use CDN
	 */
	public function cdn_rewrite_js( $src, $handle ) {
		if ( ! $this->is_cdn_enabled( 'js' ) ) {
			return $src;
		}

		// Skip external URLs
		if ( $this->is_external_url( $src ) ) {
			return $src;
		}

		// Add versioning and CDN rewrite
		$cdn_src = $this->rewrite_url( $src );
		
		// Add cache busting
		$cdn_src = $this->add_cache_busting( $cdn_src, $handle );
		
		return $cdn_src;
	}

	/**
	 * Rewrite attachment URLs to use CDN
	 */
	public function cdn_rewrite_attachment( $url, $post_id ) {
		if ( ! $this->is_cdn_enabled( 'images' ) ) {
			return $url;
		}

		// Check if it's an image
		$file_type = wp_check_filetype( $url );
		$image_types = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );
		
		if ( in_array( $file_type['ext'], $image_types ) ) {
			return $this->rewrite_url( $url );
		}

		return $url;
	}

	/**
	 * Rewrite URLs in content
	 */
	public function cdn_rewrite_content_urls( $content ) {
		if ( ! $this->cdn_config['enabled'] ) {
			return $content;
		}

		$site_url = get_site_url();
		$cdn_url = $this->get_cdn_url();

		// Replace image URLs
		if ( $this->is_cdn_enabled( 'images' ) ) {
			$content = preg_replace_callback(
				'/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i',
				function( $matches ) {
					$original_tag = $matches[0];
					$src = $matches[1];
					
					if ( ! $this->is_external_url( $src ) ) {
						$cdn_src = $this->rewrite_url( $src );
						$original_tag = str_replace( $src, $cdn_src, $original_tag );
						
						// Add lazy loading if enabled
						if ( $this->cdn_config['optimization']['lazy_load_images'] ) {
							$original_tag = $this->add_lazy_loading( $original_tag );
						}
					}
					
					return $original_tag;
				},
				$content
			);
		}

		return $content;
	}

	/**
	 * Optimize frontend assets
	 */
	public function optimize_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		// Combine and minify CSS if enabled
		if ( $this->cdn_config['optimization']['combine_css'] ) {
			$this->combine_css_files();
		}

		// Add critical CSS
		if ( $this->cdn_config['optimization']['critical_css'] ) {
			add_action( 'wp_head', array( $this, 'inject_critical_css' ), 1 );
		}

		// Preload key resources
		$this->preload_critical_resources();
	}

	/**
	 * Optimize admin assets
	 */
	public function optimize_admin_assets() {
		// Lighter optimization for admin area
		if ( $this->cdn_config['optimization']['minify_css'] ) {
			add_filter( 'style_loader_tag', array( $this, 'add_css_optimization_attributes' ), 10, 2 );
		}
	}

	/**
	 * Add resource hints for performance
	 */
	public function add_resource_hints() {
		if ( ! $this->cdn_config['enabled'] ) {
			return;
		}

		$cdn_domain = parse_url( $this->get_cdn_url(), PHP_URL_HOST );
		
		echo "\n<!-- METS CDN Resource Hints -->\n";
		echo '<link rel="dns-prefetch" href="//' . esc_attr( $cdn_domain ) . '">' . "\n";
		echo '<link rel="preconnect" href="//' . esc_attr( $cdn_domain ) . '" crossorigin>' . "\n";
	}

	/**
	 * Add preload tags for critical resources
	 */
	public function add_preload_tags() {
		$critical_assets = $this->get_critical_assets();
		
		echo "\n<!-- METS Critical Asset Preloading -->\n";
		foreach ( $critical_assets as $asset ) {
			$url = $this->rewrite_url( $asset['url'] );
			printf(
				'<link rel="preload" href="%s" as="%s"%s>' . "\n",
				esc_url( $url ),
				esc_attr( $asset['type'] ),
				! empty( $asset['crossorigin'] ) ? ' crossorigin' : ''
			);
		}
	}

	/**
	 * Get critical assets for preloading
	 */
	private function get_critical_assets() {
		$assets = array();
		
		// Add critical CSS
		$assets[] = array(
			'url' => METS_PLUGIN_URL . 'assets/css/mets-critical.css',
			'type' => 'style'
		);
		
		// Add critical JavaScript
		$assets[] = array(
			'url' => METS_PLUGIN_URL . 'assets/js/mets-core.js',
			'type' => 'script',
			'crossorigin' => true
		);
		
		// Add key fonts
		$assets[] = array(
			'url' => METS_PLUGIN_URL . 'assets/fonts/mets-icons.woff2',
			'type' => 'font',
			'crossorigin' => true
		);
		
		return apply_filters( 'mets_critical_assets', $assets );
	}

	/**
	 * Inject critical CSS inline
	 */
	public function inject_critical_css() {
		$critical_css = $this->get_critical_css();
		
		if ( ! empty( $critical_css ) ) {
			echo "\n<style id='mets-critical-css'>\n" . $critical_css . "\n</style>\n";
		}
	}

	/**
	 * Get critical CSS content
	 */
	private function get_critical_css() {
		$cache_key = 'mets_critical_css_' . md5( get_template() . get_stylesheet() );
		$critical_css = wp_cache_get( $cache_key, 'mets_performance' );
		
		if ( $critical_css === false ) {
			// Generate or load critical CSS
			$critical_css = $this->generate_critical_css();
			wp_cache_set( $cache_key, $critical_css, 'mets_performance', 3600 );
		}
		
		return $critical_css;
	}

	/**
	 * Generate critical CSS
	 */
	private function generate_critical_css() {
		// This would typically extract critical CSS from main stylesheets
		// For now, return essential METS styles
		return '
			.mets-loading{display:none}.mets-ticket-form{max-width:600px}
			.mets-alert{padding:12px;margin:10px 0;border-radius:4px}
			.mets-alert.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
			.mets-alert.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
			.mets-btn{display:inline-block;padding:8px 16px;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px}
		';
	}

	/**
	 * Combine CSS files
	 */
	private function combine_css_files() {
		global $wp_styles;
		
		if ( ! isset( $wp_styles->queue ) ) {
			return;
		}

		$mets_css_files = array();
		
		// Find METS CSS files
		foreach ( $wp_styles->queue as $handle ) {
			if ( strpos( $handle, 'mets' ) !== false ) {
				$mets_css_files[] = $handle;
			}
		}

		if ( count( $mets_css_files ) > 1 ) {
			// Combine files (simplified implementation)
			$combined_handle = 'mets-combined-css';
			$combined_url = $this->create_combined_css( $mets_css_files );
			
			// Remove individual files
			foreach ( $mets_css_files as $handle ) {
				wp_dequeue_style( $handle );
			}
			
			// Enqueue combined file
			wp_enqueue_style( $combined_handle, $combined_url, array(), $this->asset_version );
		}
	}

	/**
	 * Create combined CSS file
	 */
	private function create_combined_css( $handles ) {
		global $wp_styles;
		
		$combined_css = '';
		$cache_key = 'mets_combined_css_' . md5( implode( '', $handles ) );
		
		// Check if combined file is cached
		$cached_url = wp_cache_get( $cache_key, 'mets_assets' );
		if ( $cached_url !== false ) {
			return $cached_url;
		}

		// Combine CSS content
		foreach ( $handles as $handle ) {
			if ( isset( $wp_styles->registered[ $handle ] ) ) {
				$src = $wp_styles->registered[ $handle ]->src;
				$css_content = $this->get_css_content( $src );
				
				if ( $css_content ) {
					$combined_css .= "/* {$handle} */\n" . $css_content . "\n\n";
				}
			}
		}

		// Create combined file
		$upload_dir = wp_upload_dir();
		$combined_dir = $upload_dir['basedir'] . '/mets-cache/css/';
		$combined_url_dir = $upload_dir['baseurl'] . '/mets-cache/css/';
		
		if ( ! wp_mkdir_p( $combined_dir ) ) {
			return false;
		}

		$filename = 'combined-' . md5( $combined_css ) . '.css';
		$file_path = $combined_dir . $filename;
		$file_url = $combined_url_dir . $filename;

		// Minify if enabled
		if ( $this->cdn_config['optimization']['minify_css'] ) {
			$combined_css = $this->minify_css( $combined_css );
		}

		// Write combined file
		if ( file_put_contents( $file_path, $combined_css ) ) {
			wp_cache_set( $cache_key, $file_url, 'mets_assets', 3600 );
			return $file_url;
		}

		return false;
	}

	/**
	 * Get CSS content from URL
	 */
	private function get_css_content( $url ) {
		if ( $this->is_external_url( $url ) ) {
			return false;
		}

		// Convert URL to file path
		$file_path = $this->url_to_path( $url );
		
		if ( file_exists( $file_path ) ) {
			return file_get_contents( $file_path );
		}

		return false;
	}

	/**
	 * Minify CSS
	 */
	private function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
		
		// Remove whitespace
		$css = preg_replace( '/\s+/', ' ', $css );
		
		// Remove unnecessary spaces
		$css = str_replace( array( '; ', ' {', '{ ', ' }', '} ', ': ', ', ' ), array( ';', '{', '{', '}', '}', ':', ',' ), $css );
		
		return trim( $css );
	}

	/**
	 * Add lazy loading to images
	 */
	private function add_lazy_loading( $img_tag ) {
		// Don't add lazy loading if already present
		if ( strpos( $img_tag, 'loading=' ) !== false ) {
			return $img_tag;
		}

		// Add loading="lazy" attribute
		return str_replace( '<img ', '<img loading="lazy" ', $img_tag );
	}

	/**
	 * Add CSS optimization attributes
	 */
	public function add_css_optimization_attributes( $tag, $handle ) {
		// Add media="print" onload trick for non-critical CSS
		if ( strpos( $handle, 'mets' ) !== false && ! in_array( $handle, array( 'mets-critical', 'mets-admin' ) ) ) {
			$tag = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
			$tag = str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $tag );
		}

		return $tag;
	}

	/**
	 * Purge ticket-related cache
	 */
	public function purge_ticket_cache( $ticket_id ) {
		$this->purge_cdn_cache( array(
			'/tickets/',
			'/ticket/' . $ticket_id,
			'/dashboard/',
			'/api/tickets'
		) );
	}

	/**
	 * Purge knowledge base cache
	 */
	public function purge_kb_cache( $article_id ) {
		$this->purge_cdn_cache( array(
			'/knowledge-base/',
			'/kb/' . $article_id,
			'/api/kb'
		) );
	}

	/**
	 * Purge CDN cache
	 */
	public function purge_cdn_cache( $urls = array() ) {
		if ( ! $this->cdn_config['enabled'] ) {
			return false;
		}

		switch ( $this->cdn_config['provider'] ) {
			case 'cloudflare':
				return $this->purge_cloudflare_cache( $urls );
				
			case 'aws_cloudfront':
				return $this->purge_cloudfront_cache( $urls );
				
			default:
				// Log that purging is not implemented for this provider
				error_log( 'METS CDN: Cache purging not implemented for ' . $this->cdn_config['provider'] );
				return false;
		}
	}

	/**
	 * Purge Cloudflare cache
	 */
	private function purge_cloudflare_cache( $urls ) {
		$zone_id = get_option( 'mets_cloudflare_zone_id' );
		$api_token = get_option( 'mets_cloudflare_api_token' );
		
		if ( ! $zone_id || ! $api_token ) {
			return false;
		}

		$api_url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache";
		
		$data = array(
			'files' => array_map( function( $url ) {
				return home_url( $url );
			}, $urls )
		);

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $data ),
			'timeout' => 30
		) );

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Purge CloudFront cache
	 */
	private function purge_cloudfront_cache( $urls ) {
		// This would require AWS SDK implementation
		// For now, just log the request
		error_log( 'METS CDN: CloudFront cache purge requested for: ' . implode( ', ', $urls ) );
		return true;
	}

	/**
	 * Check if CDN is enabled for specific asset type
	 */
	private function is_cdn_enabled( $type ) {
		return $this->cdn_config['enabled'] && 
			   ! empty( $this->cdn_config['zones'][ $type ] ) && 
			   ! empty( $this->cdn_config['domain'] );
	}

	/**
	 * Check if URL is external
	 */
	private function is_external_url( $url ) {
		$site_url = parse_url( home_url(), PHP_URL_HOST );
		$url_host = parse_url( $url, PHP_URL_HOST );
		
		return $url_host && $url_host !== $site_url;
	}

	/**
	 * Rewrite URL to use CDN
	 */
	private function rewrite_url( $url ) {
		$site_url = home_url();
		$cdn_url = $this->get_cdn_url();
		
		return str_replace( $site_url, $cdn_url, $url );
	}

	/**
	 * Get CDN URL
	 */
	private function get_cdn_url() {
		$protocol = is_ssl() ? 'https://' : 'http://';
		return $protocol . rtrim( $this->cdn_config['domain'], '/' );
	}

	/**
	 * Add cache busting to URL
	 */
	private function add_cache_busting( $url, $handle ) {
		// Use handle-specific version if available
		global $wp_scripts, $wp_styles;
		
		$version = $this->asset_version;
		
		if ( isset( $wp_styles->registered[ $handle ]->ver ) ) {
			$version = $wp_styles->registered[ $handle ]->ver;
		} elseif ( isset( $wp_scripts->registered[ $handle ]->ver ) ) {
			$version = $wp_scripts->registered[ $handle ]->ver;
		}
		
		return add_query_arg( 'ver', $version, $url );
	}

	/**
	 * Convert URL to file path
	 */
	private function url_to_path( $url ) {
		$home_url = home_url();
		$relative_path = str_replace( $home_url, '', $url );
		return ABSPATH . ltrim( $relative_path, '/' );
	}

	/**
	 * Get CDN statistics and performance metrics
	 */
	public function get_cdn_stats() {
		return array(
			'enabled' => $this->cdn_config['enabled'],
			'provider' => $this->cdn_config['provider'],
			'zones_active' => array_sum( $this->cdn_config['zones'] ),
			'optimizations' => array_sum( $this->cdn_config['optimization'] ),
			'cache_hit_ratio' => $this->calculate_cache_hit_ratio(),
			'bandwidth_saved' => $this->calculate_bandwidth_savings(),
			'page_load_improvement' => $this->calculate_performance_improvement()
		);
	}

	/**
	 * Calculate cache hit ratio (placeholder)
	 */
	private function calculate_cache_hit_ratio() {
		// This would integrate with CDN provider APIs
		return 85.7; // Placeholder
	}

	/**
	 * Calculate bandwidth savings (placeholder)
	 */
	private function calculate_bandwidth_savings() {
		// This would integrate with CDN provider APIs
		return '2.4 GB'; // Placeholder
	}

	/**
	 * Calculate performance improvement (placeholder)
	 */
	private function calculate_performance_improvement() {
		// This would be based on real performance monitoring
		return '43%'; // Placeholder
	}
}