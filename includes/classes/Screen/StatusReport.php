<?php
/**
 * ElasticPress Status Report class
 *
 * @since 4.4.0
 * @package elasticpress
 */

namespace ElasticPress\Screen;

use \ElasticPress\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Status Report class
 *
 * @package ElasticPress
 */
class StatusReport {
	/**
	 * Initialize class
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		if ( 'status-report' !== \ElasticPress\Screen::factory()->get_current_screen() ) {
			return;
		}

		$script_deps = Utils\get_asset_info( 'status-report-script', 'dependencies' );

		wp_enqueue_script(
			'ep_admin_status_report_scripts',
			EP_URL . 'dist/js/status-report-script.js',
			array_merge( $script_deps, [ 'clipboard' ] ),
			Utils\get_asset_info( 'status-report-script', 'version' ),
			true
		);

		wp_enqueue_style(
			'ep_status_report_styles',
			EP_URL . 'dist/css/status-report-styles.css',
			Utils\get_asset_info( 'status-report-styles', 'dependencies' ),
			Utils\get_asset_info( 'status-report-styles', 'version' )
		);
	}

	/**
	 * Return all reports available
	 *
	 * @return array
	 */
	public function get_reports() : array {
		$reports = [];

		$reports['wordpress']    = new \ElasticPress\StatusReport\WordPress();
		$reports['indexable']    = new \ElasticPress\StatusReport\IndexableContent();
		$reports['elasticpress'] = new \ElasticPress\StatusReport\ElasticPress();
		$reports['indices']      = new \ElasticPress\StatusReport\Indices();

		if ( Utils\is_epio() ) {
			$reports['autosuggest'] = new \ElasticPress\StatusReport\ElasticPressIo();
		}

		$reports['last_sync'] = new \ElasticPress\StatusReport\LastSync();
		$reports['features']  = new \ElasticPress\StatusReport\Features();

		/**
		 * Filter the reports executed in the Status Report page.
		 *
		 * @since 4.4.0
		 * @hook ep_status_report_reports
		 * @param {array<Report>} $reports Array of reports
		 * @return {array<Report>} New array of reports
		 */
		$filtered_reports = apply_filters( 'ep_status_report_reports', $reports );

		$skipped_reports = ! empty( $_GET['ep-skip-reports'] ) ? (array) $_GET['ep-skip-reports'] : []; // phpcs:ignore WordPress.Security.NonceVerification
		$skipped_reports = array_map( 'sanitize_text_field', $skipped_reports );

		$filtered_reports = array_filter(
			$filtered_reports,
			function( $report_slug ) use ( $skipped_reports ) {
				return ! in_array( $report_slug, $skipped_reports, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		return $filtered_reports;
	}

	/**
	 * Render all reports (HTML and Copy & Paste button)
	 */
	public function render_reports() {
		$reports = $this->get_reports();

		$html_output       = [];
		$copy_paste_output = [];

		foreach ( $reports as $report ) {
			$title  = $report->get_title();
			$groups = $report->get_groups();

			$html_output[]       = $this->render_html_report( $title, $groups );
			$copy_paste_output[] = $this->render_copy_paste_report( $title, $groups );
		}

		?>
		<p><?php esc_html_e( 'This screen provides a list of information related to ElasticPress and synced content that can be helpful during troubleshooting. This list can also be copy/pasted and shared as needed.', 'elasticpress' ); ?></p>
		<p class="ep-copy-button-wrapper">
			<button class="button" data-clipboard-text="<?php echo esc_attr( implode( "\n\n", $copy_paste_output ) ); ?>" id="ep-copy-report" type="button" >
				<?php esc_html_e( 'Copy status report to clipboard', 'elasticpress' ); ?>
			</button>
			<span class="ep-copy-button-wrapper__success">
				<?php esc_html_e( 'Copied!', 'elasticpress' ); ?>
			</span>
		</p>
		<?php
		echo wp_kses_post( implode( '', $html_output ) );
	}

	/**
	 * Render the HTML of a report
	 *
	 * @param string $title  Report title
	 * @param array  $groups Report groups
	 * @return string
	 */
	public function render_html_report( string $title, array $groups ) : string {
		ob_start();
		?>
		<h2><?php echo esc_html( $title ); ?></h2>
		<table cellpadding="0" cellspacing="0" class="wp-list-table widefat striped">
			<colgroup>
				<col>
				<col>
			</colgroup>
			<?php foreach ( $groups as $group ) : ?>
				<?php if ( isset( $group['title'] ) ) : ?>
					<thead>
						<tr>
							<th colspan="2">
								<?php echo esc_html( $group['title'] ); ?>
							</th>
						</tr>
					</thead>
				<?php endif; ?>
				<tbody>
					<?php
					foreach ( $group['fields'] as $slug => $field ) {
						$label       = $field['label'] ?? $slug;
						$description = $field['description'] ?? '';
						$value       = $field['value'] ?? '';
						?>
						<tr>
							<td>
								<?php echo esc_html( $label ); ?>
								<?php if ( $description ) : ?>
									<small><?php echo esc_html( $description ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo wp_kses_post( $this->render_value( $value ) ); ?>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			<?php endforeach; ?>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the copy & paste report
	 *
	 * @param string $title  Report title
	 * @param array  $groups Report groups
	 * @return string
	 */
	protected function render_copy_paste_report( string $title, array $groups ) : string {
		$output = "## {$title} ##\n\n";

		foreach ( $groups as $group ) {
			$output .= "### {$group['title']} ###\n";
			foreach ( $group['fields'] as $slug => $field ) {
				$value = $field['value'] ?? '';

				$output .= "{$slug}: ";
				$output .= $this->render_value( $value );
				$output .= "\n";
			}
			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Render a value based on its type
	 *
	 * @param mixed $value The value
	 * @return string
	 */
	protected function render_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return var_export( $value, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}
}
