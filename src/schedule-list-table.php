<?php
/**
 * List table for cron schedules.
 *
 * @package WP Crontrol
 */

namespace Crontrol;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Cron schedule list table class.
 */
class Schedule_List_Table extends \WP_List_Table {

	/**
	 * Array of cron event schedules that are added by WordPress core.
	 *
	 * @var string[] Array of schedule names.
	 */
	protected static $core_schedules;

	/**
	 * Array of cron event schedule names that are in use by events.
	 *
	 * @var string[] Array of schedule names.
	 */
	protected static $used_schedules;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'crontrol-schedule',
			'plural'   => 'crontrol-schedules',
			'ajax'     => false,
			'screen'   => 'crontrol-schedules',
		) );
	}

	/**
	 * Prepares the list table items and arguments.
	 */
	public function prepare_items() {
		$schedules = Schedule\get();
		$count     = count( $schedules );

		self::$core_schedules = get_core_schedules();
		self::$used_schedules = array_unique( wp_list_pluck( Event\get(), 'schedule' ) );

		$this->items = $schedules;

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => -1,
			'total_pages' => 1,
		) );
	}

	/**
	 * Returns an array of column names for the table.
	 *
	 * @return string[] Array of column names keyed by their ID.
	 */
	public function get_columns() {
		return array(
			'crontrol_name'     => __( 'Name', 'wp-crontrol' ),
			'crontrol_interval' => __( 'Interval', 'wp-crontrol' ),
			'crontrol_display'  => __( 'Display Name', 'wp-crontrol' ),
		);
	}

	/**
	 * Returns an array of CSS class names for the table.
	 *
	 * @return string[] Array of class names.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * Generates and displays row action links for the table.
	 *
	 * @param array  $schedule    The schedule for the current row.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML.
	 */
	protected function handle_row_actions( $schedule, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$links = array();

		if ( ! in_array( $schedule['name'], self::$core_schedules, true ) ) {
			if ( in_array( $schedule['name'], self::$used_schedules, true ) ) {
				$links[] = "<span class='in-use'>" . esc_html__( 'This custom schedule is in use and cannot be deleted', 'wp-crontrol' ) . '</span>';
			} else {
				$link = add_query_arg( array(
					'page'   => 'crontrol_admin_options_page',
					'action' => 'delete-sched',
					'id'     => rawurlencode( $schedule['name'] ),
				), admin_url( 'options-general.php' ) );
				$link = wp_nonce_url( $link, 'delete-sched_' . $schedule['name'] );

				$links[] = "<span class='delete'><a href='" . esc_url( $link ) . "'>" . esc_html__( 'Delete', 'wp-crontrol' ) . '</a></span>';
			}
		}

		return $this->row_actions( $links );
	}

	/**
	 * Returns the output for the schdule name cell of a table row.
	 *
	 * @param array $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_name( array $schedule ) {
		$return = esc_html( $schedule['name'] );

		if ( in_array( $schedule['name'], get_core_schedules(), true ) ) {
			$return .= sprintf(
				'<br><em>(%s)</em>',
				esc_html__( 'WordPress core schedule', 'wp-crontrol' )
			);
		}

		return $return;
	}

	/**
	 * Returns the output for the interval cell of a table row.
	 *
	 * @param array $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_interval( array $schedule ) {
		if ( $schedule['interval'] < 600 ) {
			return sprintf(
				'%s (%s)<br><span style="color:#c00"><span class="dashicons dashicons-warning" aria-hidden="true"></span>%s</span>',
				esc_html( $schedule['interval'] ),
				esc_html( interval( $schedule['interval'] ) ),
				esc_html__( 'An interval of less than 10 minutes may be unreliable.', 'wp-crontrol' )
			);
		} else {
			return sprintf(
				'%s (%s)',
				esc_html( $schedule['interval'] ),
				esc_html( interval( $schedule['interval'] ) )
			);
		}
	}

	/**
	 * Returns the output for the display name cell of a table row.
	 *
	 * @param array $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_display( array $schedule ) {
		return esc_html( $schedule['display'] );
	}

	/**
	 * Outputs a message when there are no items to show in the table.
	 */
	public function no_items() {
		esc_html_e( 'There are no schedules.', 'wp-crontrol' );
	}

}