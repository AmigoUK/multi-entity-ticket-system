<?php
/**
 * Performance Monitoring Dashboard
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

class METS_Performance_Dashboard {

	/**
	 * Performance optimizer instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Performance_Optimizer    $performance_optimizer
	 */
	private $performance_optimizer;

	/**
	 * Database optimizer instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Database_Optimizer    $database_optimizer
	 */
	private $database_optimizer;

	/**
	 * Cache manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Cache_Manager    $cache_manager
	 */
	private $cache_manager;

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->performance_optimizer = METS_Performance_Optimizer::get_instance();
		$this->database_optimizer = METS_Database_Optimizer::get_instance();
		$this->cache_manager = METS_Cache_Manager::get_instance();
	}

	/**
	 * Display the performance dashboard
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		$current_performance = $this->performance_optimizer->get_current_performance();
		$database_stats = $this->database_optimizer->get_table_statistics();
		$cache_stats = $this->cache_manager->get_stats();
		$optimization_report = $this->database_optimizer->generate_optimization_report();

		?>
		<div class="wrap">
			<h1><?php _e( 'Performance Dashboard', METS_TEXT_DOMAIN ); ?></h1>
			
			<div class="performance-dashboard">
				<!-- Current Performance Overview -->
				<div class="performance-section">
					<h2><?php _e( 'Current Performance', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="performance-metrics">
						<div class="metric-card">
							<h3><?php _e( 'Memory Usage', METS_TEXT_DOMAIN ); ?></h3>
							<div class="metric-value">
								<?php echo $this->format_bytes( $current_performance['memory_usage'] ); ?>
								<span class="metric-limit">/ <?php echo $current_performance['memory_limit']; ?></span>
							</div>
							<div class="metric-bar">
								<?php 
								$memory_percent = ( $current_performance['memory_usage'] / $this->convert_to_bytes( $current_performance['memory_limit'] ) ) * 100;
								?>
								<div class="metric-progress" style="width: <?php echo min( $memory_percent, 100 ); ?>%"></div>
							</div>
						</div>

						<div class="metric-card">
							<h3><?php _e( 'Peak Memory', METS_TEXT_DOMAIN ); ?></h3>
							<div class="metric-value">
								<?php echo $this->format_bytes( $current_performance['memory_peak'] ); ?>
							</div>
						</div>

						<div class="metric-card">
							<h3><?php _e( 'Execution Time', METS_TEXT_DOMAIN ); ?></h3>
							<div class="metric-value">
								<?php echo number_format( $current_performance['execution_time'], 3 ); ?>s
							</div>
						</div>

						<div class="metric-card">
							<h3><?php _e( 'Database Queries', METS_TEXT_DOMAIN ); ?></h3>
							<div class="metric-value">
								<?php echo $current_performance['queries_count']; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Database Statistics -->
				<div class="performance-section">
					<h2><?php _e( 'Database Statistics', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="database-stats">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e( 'Table', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Rows', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Size (MB)', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Free Space (MB)', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $database_stats as $key => $stats ) : ?>
									<tr>
										<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></strong></td>
										<td><?php echo number_format( $stats['rows'] ); ?></td>
										<td><?php echo $stats['size_mb']; ?></td>
										<td><?php echo $stats['free_mb']; ?></td>
										<td>
											<button class="button button-small optimize-table" data-table="<?php echo esc_attr( $stats['table'] ); ?>">
												<?php _e( 'Optimize', METS_TEXT_DOMAIN ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div class="database-actions">
							<button class="button button-primary" id="optimize-all-tables">
								<?php _e( 'Optimize All Tables', METS_TEXT_DOMAIN ); ?>
							</button>
							<button class="button" id="create-indexes">
								<?php _e( 'Create Indexes', METS_TEXT_DOMAIN ); ?>
							</button>
							<button class="button" id="cleanup-old-data">
								<?php _e( 'Cleanup Old Data', METS_TEXT_DOMAIN ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Cache Statistics -->
				<div class="performance-section">
					<h2><?php _e( 'Cache Statistics', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="cache-stats">
						<div class="cache-overview">
							<div class="cache-metric">
								<h4><?php _e( 'Total Cache Keys', METS_TEXT_DOMAIN ); ?></h4>
								<span class="cache-value"><?php echo number_format( $cache_stats['total_keys'] ); ?></span>
							</div>
							<div class="cache-metric">
								<h4><?php _e( 'Memory Usage', METS_TEXT_DOMAIN ); ?></h4>
								<span class="cache-value"><?php echo $this->format_bytes( $cache_stats['memory_usage'] ); ?></span>
							</div>
						</div>

						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e( 'Cache Group', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Keys', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Memory', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Expiration', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $cache_stats['groups'] as $group_name => $group_stats ) : ?>
									<tr>
										<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $group_name ) ) ); ?></strong></td>
										<td><?php echo number_format( $group_stats['keys'] ); ?></td>
										<td><?php echo $this->format_bytes( $group_stats['memory'] ); ?></td>
										<td><?php echo $this->format_duration( $group_stats['expiration'] ); ?></td>
										<td>
											<button class="button button-small flush-cache-group" data-group="<?php echo esc_attr( $group_name ); ?>">
												<?php _e( 'Flush', METS_TEXT_DOMAIN ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div class="cache-actions">
							<button class="button button-primary" id="warm-cache">
								<?php _e( 'Warm Up Cache', METS_TEXT_DOMAIN ); ?>
							</button>
							<button class="button" id="flush-all-cache">
								<?php _e( 'Flush All Cache', METS_TEXT_DOMAIN ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Optimization Recommendations -->
				<div class="performance-section">
					<h2><?php _e( 'Optimization Recommendations', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="recommendations">
						<?php if ( ! empty( $optimization_report['recommendations'] ) ) : ?>
							<?php foreach ( $optimization_report['recommendations'] as $recommendation ) : ?>
								<div class="recommendation recommendation-<?php echo esc_attr( $recommendation['type'] ); ?>">
									<div class="recommendation-icon">
										<?php if ( $recommendation['type'] === 'error' ) : ?>
											<span class="dashicons dashicons-warning"></span>
										<?php elseif ( $recommendation['type'] === 'warning' ) : ?>
											<span class="dashicons dashicons-info"></span>
										<?php else : ?>
											<span class="dashicons dashicons-lightbulb"></span>
										<?php endif; ?>
									</div>
									<div class="recommendation-content">
										<p><?php echo esc_html( $recommendation['message'] ); ?></p>
										<?php if ( ! empty( $recommendation['action'] ) ) : ?>
											<button class="button button-small recommendation-action" data-action="<?php echo esc_attr( $recommendation['action'] ); ?>">
												<?php _e( 'Apply Fix', METS_TEXT_DOMAIN ); ?>
											</button>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="recommendation recommendation-success">
								<div class="recommendation-icon">
									<span class="dashicons dashicons-yes-alt"></span>
								</div>
								<div class="recommendation-content">
									<p><?php _e( 'No optimization issues detected. Your system is performing well!', METS_TEXT_DOMAIN ); ?></p>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Slow Queries -->
				<?php 
				$slow_queries = $this->database_optimizer->get_slow_queries();
				if ( ! empty( $slow_queries ) ) :
				?>
				<div class="performance-section">
					<h2><?php _e( 'Slow Queries', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="slow-queries">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php _e( 'Query', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Execution Time', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Timestamp', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_slice( $slow_queries, -10 ) as $query ) : ?>
									<tr>
										<td>
											<code><?php echo esc_html( substr( $query['query'], 0, 100 ) . '...' ); ?></code>
										</td>
										<td><?php echo number_format( $query['execution_time'], 3 ); ?>s</td>
										<td><?php echo date( 'Y-m-d H:i:s', $query['timestamp'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

				<!-- System Information -->
				<div class="performance-section">
					<h2><?php _e( 'System Information', METS_TEXT_DOMAIN ); ?></h2>
					
					<div class="system-info">
						<div class="info-grid">
							<div class="info-item">
								<strong><?php _e( 'PHP Version:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo $current_performance['php_version']; ?></span>
							</div>
							<div class="info-item">
								<strong><?php _e( 'MySQL Version:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo $current_performance['mysql_version']; ?></span>
							</div>
							<div class="info-item">
								<strong><?php _e( 'WordPress Version:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo get_bloginfo( 'version' ); ?></span>
							</div>
							<div class="info-item">
								<strong><?php _e( 'Memory Limit:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo $current_performance['memory_limit']; ?></span>
							</div>
							<div class="info-item">
								<strong><?php _e( 'Max Execution Time:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo ini_get( 'max_execution_time' ); ?>s</span>
							</div>
							<div class="info-item">
								<strong><?php _e( 'Object Cache:', METS_TEXT_DOMAIN ); ?></strong>
								<span><?php echo wp_using_ext_object_cache() ? __( 'Enabled', METS_TEXT_DOMAIN ) : __( 'Disabled', METS_TEXT_DOMAIN ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
		.performance-dashboard {
			max-width: 1200px;
		}

		.performance-section {
			background: #fff;
			margin: 20px 0;
			padding: 20px;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}

		.performance-section h2 {
			margin-top: 0;
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}

		.performance-metrics {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}

		.metric-card {
			background: #f9f9f9;
			padding: 15px;
			border-radius: 4px;
			border: 1px solid #ddd;
		}

		.metric-card h3 {
			margin: 0 0 10px 0;
			font-size: 14px;
			color: #666;
		}

		.metric-value {
			font-size: 24px;
			font-weight: bold;
			color: #2271b1;
		}

		.metric-limit {
			font-size: 14px;
			color: #666;
			font-weight: normal;
		}

		.metric-bar {
			width: 100%;
			height: 4px;
			background: #ddd;
			border-radius: 2px;
			margin-top: 10px;
			overflow: hidden;
		}

		.metric-progress {
			height: 100%;
			background: #2271b1;
			transition: width 0.3s ease;
		}

		.database-actions,
		.cache-actions {
			margin-top: 20px;
			padding-top: 15px;
			border-top: 1px solid #eee;
		}

		.cache-overview {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin-bottom: 20px;
		}

		.cache-metric {
			text-align: center;
			padding: 15px;
			background: #f0f0f0;
			border-radius: 4px;
		}

		.cache-metric h4 {
			margin: 0 0 10px 0;
			font-size: 14px;
			color: #666;
		}

		.cache-value {
			font-size: 20px;
			font-weight: bold;
			color: #2271b1;
		}

		.recommendations {
			margin: 20px 0;
		}

		.recommendation {
			display: flex;
			align-items: flex-start;
			padding: 15px;
			margin-bottom: 10px;
			border-radius: 4px;
			border-left: 4px solid;
		}

		.recommendation-error {
			background: #fef7f1;
			border-left-color: #d63638;
		}

		.recommendation-warning {
			background: #fcf9e8;
			border-left-color: #dba617;
		}

		.recommendation-info {
			background: #f0f6fc;
			border-left-color: #2271b1;
		}

		.recommendation-success {
			background: #f0f6fc;
			border-left-color: #00a32a;
		}

		.recommendation-icon {
			margin-right: 15px;
			font-size: 18px;
		}

		.recommendation-content {
			flex: 1;
		}

		.recommendation-content p {
			margin: 0 0 10px 0;
		}

		.slow-queries table {
			margin-top: 15px;
		}

		.slow-queries code {
			font-size: 12px;
			background: #f1f1f1;
			padding: 2px 4px;
			border-radius: 2px;
		}

		.info-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 15px;
		}

		.info-item {
			display: flex;
			justify-content: space-between;
			padding: 10px;
			background: #f9f9f9;
			border-radius: 4px;
		}

		@media (max-width: 768px) {
			.performance-metrics {
				grid-template-columns: 1fr;
			}
			
			.cache-overview {
				grid-template-columns: 1fr;
			}
			
			.info-grid {
				grid-template-columns: 1fr;
			}
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Optimize all tables
			$('#optimize-all-tables').on('click', function() {
				var button = $(this);
				button.prop('disabled', true).text('<?php _e( 'Optimizing...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_optimize_all_tables',
					nonce: '<?php echo wp_create_nonce( 'mets_performance_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'All tables optimized successfully!', METS_TEXT_DOMAIN ); ?>');
						location.reload();
					} else {
						alert('<?php _e( 'Error: ', METS_TEXT_DOMAIN ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php _e( 'Optimize All Tables', METS_TEXT_DOMAIN ); ?>');
				});
			});

			// Create indexes
			$('#create-indexes').on('click', function() {
				var button = $(this);
				button.prop('disabled', true).text('<?php _e( 'Creating...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_create_indexes',
					nonce: '<?php echo wp_create_nonce( 'mets_performance_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'Database indexes created successfully!', METS_TEXT_DOMAIN ); ?>');
						location.reload();
					} else {
						alert('<?php _e( 'Error: ', METS_TEXT_DOMAIN ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php _e( 'Create Indexes', METS_TEXT_DOMAIN ); ?>');
				});
			});

			// Warm cache
			$('#warm-cache').on('click', function() {
				var button = $(this);
				button.prop('disabled', true).text('<?php _e( 'Warming...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_warm_cache',
					nonce: '<?php echo wp_create_nonce( 'mets_performance_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'Cache warmed up successfully!', METS_TEXT_DOMAIN ); ?>');
						location.reload();
					} else {
						alert('<?php _e( 'Error: ', METS_TEXT_DOMAIN ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php _e( 'Warm Up Cache', METS_TEXT_DOMAIN ); ?>');
				});
			});

			// Flush all cache
			$('#flush-all-cache').on('click', function() {
				if (!confirm('<?php _e( 'Are you sure you want to flush all cache?', METS_TEXT_DOMAIN ); ?>')) {
					return;
				}
				
				var button = $(this);
				button.prop('disabled', true).text('<?php _e( 'Flushing...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_flush_all_cache',
					nonce: '<?php echo wp_create_nonce( 'mets_performance_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'All cache flushed successfully!', METS_TEXT_DOMAIN ); ?>');
						location.reload();
					} else {
						alert('<?php _e( 'Error: ', METS_TEXT_DOMAIN ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php _e( 'Flush All Cache', METS_TEXT_DOMAIN ); ?>');
				});
			});

			// Individual cache group flush
			$('.flush-cache-group').on('click', function() {
				var button = $(this);
				var group = button.data('group');
				
				button.prop('disabled', true).text('<?php _e( 'Flushing...', METS_TEXT_DOMAIN ); ?>');
				
				$.post(ajaxurl, {
					action: 'mets_flush_cache_group',
					group: group,
					nonce: '<?php echo wp_create_nonce( 'mets_performance_action' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('<?php _e( 'Cache group flushed successfully!', METS_TEXT_DOMAIN ); ?>');
						location.reload();
					} else {
						alert('<?php _e( 'Error: ', METS_TEXT_DOMAIN ); ?>' + response.data);
					}
				}).always(function() {
					button.prop('disabled', false).text('<?php _e( 'Flush', METS_TEXT_DOMAIN ); ?>');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Format bytes to human readable format
	 *
	 * @since    1.0.0
	 * @param    int    $bytes    Bytes to format
	 * @return   string           Formatted string
	 */
	private function format_bytes( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
			$bytes /= 1024;
		}
		
		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Format duration to human readable format
	 *
	 * @since    1.0.0
	 * @param    int    $seconds    Duration in seconds
	 * @return   string             Formatted string
	 */
	private function format_duration( $seconds ) {
		if ( $seconds < 60 ) {
			return $seconds . 's';
		} elseif ( $seconds < 3600 ) {
			return round( $seconds / 60 ) . 'm';
		} else {
			return round( $seconds / 3600 ) . 'h';
		}
	}

	/**
	 * Convert memory limit string to bytes
	 *
	 * @since    1.0.0
	 * @param    string    $limit    Memory limit string
	 * @return   int                 Memory limit in bytes
	 */
	private function convert_to_bytes( $limit ) {
		$limit = strtolower( $limit );
		$bytes = intval( $limit );
		
		if ( strpos( $limit, 'k' ) !== false ) {
			$bytes *= 1024;
		} elseif ( strpos( $limit, 'm' ) !== false ) {
			$bytes *= 1024 * 1024;
		} elseif ( strpos( $limit, 'g' ) !== false ) {
			$bytes *= 1024 * 1024 * 1024;
		}
		
		return $bytes;
	}
}