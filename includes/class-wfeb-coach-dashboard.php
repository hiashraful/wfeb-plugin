<?php
/**
 * WFEB Coach Dashboard
 *
 * Handles coach frontend dashboard routing, access control,
 * section management, and sidebar navigation.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Coach_Dashboard
 *
 * Manages the coach-facing dashboard: access checks, section routing,
 * template resolution, and sidebar menu construction.
 */
class WFEB_Coach_Dashboard {

	/**
	 * Allowed dashboard sections.
	 *
	 * @var array
	 */
	private $allowed_sections = array(
		'overview',
		'my-players',
		'add-player',
		'player-details',
		'conduct-exam',
		'exam-history',
		'exam-details',
		'credits',
		'buy-credits',
		'settings',
		'documentation',
	);

	/**
	 * Cached coach data object.
	 *
	 * @var object|null
	 */
	private $coach_data = null;

	/**
	 * Constructor.
	 *
	 * Hooks into template_redirect to enforce access control.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'check_access' ) );
	}

	/**
	 * Check access to the coach dashboard page.
	 *
	 * Runs on template_redirect. If the current page is the coach dashboard:
	 * - Not logged in: redirect to coach login page.
	 * - Logged in but not a wfeb_coach: redirect to home.
	 * - Coach status is not 'approved': redirect to coach login with error param.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_access() {
		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );

		if ( ! $dashboard_page_id || ! is_page( absint( $dashboard_page_id ) ) ) {
			return;
		}

		// Not logged in: redirect to coach login.
		if ( ! is_user_logged_in() ) {
			$login_page_id = get_option( 'wfeb_coach_login_page_id' );
			$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url();

			wfeb_log( 'Coach dashboard access denied - user not logged in. Redirecting to login.' );

			wp_safe_redirect( $login_url );
			exit;
		}

		// Logged in but not a coach: redirect to home.
		if ( ! wfeb_is_coach() ) {
			wfeb_log( 'Coach dashboard access denied - user ID ' . get_current_user_id() . ' does not have wfeb_coach role.' );

			wp_safe_redirect( home_url() );
			exit;
		}

		// Coach exists but is not approved: redirect to login with error.
		$coach = $this->get_coach_data();

		if ( $coach && 'approved' !== $coach->status ) {
			$login_page_id = get_option( 'wfeb_coach_login_page_id' );
			$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url();

			$redirect_url = add_query_arg(
				array(
					'wfeb_error' => 'not_approved',
					'status'     => sanitize_key( $coach->status ),
				),
				$login_url
			);

			wfeb_log( 'Coach dashboard access denied - coach status is "' . $coach->status . '" for user ID ' . get_current_user_id() . '.' );

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Get the current dashboard section from the query string.
	 *
	 * Reads $_GET['section'], sanitizes it, and validates against the
	 * allowed sections list. Defaults to 'overview'.
	 *
	 * @since 1.0.0
	 * @return string The current section slug.
	 */
	public function get_current_section() {
		$section = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $section, $this->allowed_sections, true ) ) {
			$section = 'overview';
		}

		return $section;
	}

	/**
	 * Get the template file path for the current section.
	 *
	 * Maps section slugs to template files within the templates/coach/ directory.
	 *
	 * @since 1.0.0
	 * @return string Absolute path to the section template file.
	 */
	public function get_section_template() {
		$section = $this->get_current_section();

		$template_map = array(
			'overview'       => 'overview.php',
			'my-players'     => 'my-players.php',
			'add-player'     => 'add-player.php',
			'player-details' => 'player-details.php',
			'conduct-exam'   => 'conduct-exam.php',
			'exam-history'   => 'exam-history.php',
			'exam-details'   => 'exam-details.php',
			'credits'        => 'credits.php',
			'buy-credits'    => 'buy-credits.php',
			'settings'       => 'settings.php',
			'documentation'  => 'documentation.php',
		);

		$template_file = isset( $template_map[ $section ] ) ? $template_map[ $section ] : 'overview.php';

		return WFEB_PLUGIN_DIR . 'templates/coach/' . $template_file;
	}

	/**
	 * Get the page title for the current section.
	 *
	 * @since 1.0.0
	 * @return string The human-readable page title.
	 */
	public function get_page_title() {
		$section = $this->get_current_section();

		$titles = array(
			'overview'       => __( 'Overview', 'wfeb' ),
			'my-players'     => __( 'My Players', 'wfeb' ),
			'add-player'     => __( 'Add Player', 'wfeb' ),
			'player-details' => __( 'Player Details', 'wfeb' ),
			'conduct-exam'   => __( 'Conduct Exam', 'wfeb' ),
			'exam-history'   => __( 'Exam History', 'wfeb' ),
			'exam-details'   => __( 'Exam Details', 'wfeb' ),
			'credits'        => __( 'Certificate Credits', 'wfeb' ),
			'buy-credits'    => __( 'Buy Certificate Credits', 'wfeb' ),
			'settings'       => __( 'Settings', 'wfeb' ),
			'documentation'  => __( 'Documentation', 'wfeb' ),
		);

		return isset( $titles[ $section ] ) ? $titles[ $section ] : __( 'Overview', 'wfeb' );
	}

	/**
	 * Get the sidebar navigation items for the coach dashboard.
	 *
	 * Each item includes a slug, label, dashicon name, and full URL
	 * to the dashboard page with the appropriate section parameter.
	 *
	 * @since 1.0.0
	 * @return array Array of sidebar menu items.
	 */
	public function get_sidebar_items() {
		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

		$items = array(
			array(
				'slug'  => 'overview',
				'label' => __( 'Overview', 'wfeb' ),
				'icon'  => 'dashicons-dashboard',
				'url'   => add_query_arg( 'section', 'overview', $base_url ),
			),
			array(
				'slug'  => 'my-players',
				'label' => __( 'My Players', 'wfeb' ),
				'icon'  => 'dashicons-groups',
				'url'   => add_query_arg( 'section', 'my-players', $base_url ),
			),
			array(
				'slug'  => 'conduct-exam',
				'label' => __( 'Conduct Exam', 'wfeb' ),
				'icon'  => 'dashicons-clipboard',
				'url'   => add_query_arg( 'section', 'conduct-exam', $base_url ),
			),
			array(
				'slug'  => 'exam-history',
				'label' => __( 'Exam History', 'wfeb' ),
				'icon'  => 'dashicons-list-view',
				'url'   => add_query_arg( 'section', 'exam-history', $base_url ),
			),
			array(
				'slug'  => 'credits',
				'label' => __( 'Certificate Credits', 'wfeb' ),
				'icon'  => 'dashicons-cart',
				'url'   => add_query_arg( 'section', 'credits', $base_url ),
			),
			array(
				'slug'  => 'settings',
				'label' => __( 'Settings', 'wfeb' ),
				'icon'  => 'dashicons-admin-generic',
				'url'   => add_query_arg( 'section', 'settings', $base_url ),
			),
		);

		return $items;
	}

	/**
	 * Get the current coach's record from the database.
	 *
	 * Uses the logged-in user's ID to look up the corresponding coach row.
	 * The result is cached in a class property so repeated calls within
	 * the same request do not trigger additional database queries.
	 *
	 * @since 1.0.0
	 * @return object|null Coach object on success, null if not found.
	 */
	public function get_coach_data() {
		if ( null !== $this->coach_data ) {
			return $this->coach_data;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wfeb_log( 'get_coach_data failed - no current user.' );
			return null;
		}

		$coach_model = new WFEB_Coach();
		$this->coach_data = $coach_model->get_by_user_id( $user_id );

		if ( ! $this->coach_data ) {
			wfeb_log( 'get_coach_data failed - no coach record found for user ID ' . $user_id . '.' );
		}

		return $this->coach_data;
	}
}
