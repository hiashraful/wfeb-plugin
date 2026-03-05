<?php
/**
 * WFEB Player Dashboard
 *
 * Handles player frontend dashboard routing, access control,
 * section management, and sidebar navigation.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Player_Dashboard
 *
 * Manages the player-facing dashboard: access checks, section routing,
 * template resolution, and sidebar menu construction.
 */
class WFEB_Player_Dashboard {

	/**
	 * Allowed dashboard sections.
	 *
	 * @var array
	 */
	private $allowed_sections = array(
		'overview',
		'certificates',
		'certificate-detail',
		'scores',
		'score-detail',
		'profile',
		'password',
	);

	/**
	 * Cached player data object.
	 *
	 * @var object|null
	 */
	private $player_data = null;

	/**
	 * Constructor.
	 *
	 * Hooks into template_redirect to enforce access control.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'check_access' ) );
	}

	/**
	 * Check access to the player dashboard page.
	 *
	 * Runs on template_redirect. If the current page is the player dashboard:
	 * - Not logged in: redirect to player login page.
	 * - Logged in but not a wfeb_player: redirect to home.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_access() {
		$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );

		if ( ! $dashboard_page_id || ! is_page( absint( $dashboard_page_id ) ) ) {
			return;
		}

		// Not logged in: redirect to player login.
		if ( ! is_user_logged_in() ) {
			$login_page_id = get_option( 'wfeb_player_login_page_id' );
			$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url();

			wfeb_log( 'Player dashboard access denied - user not logged in. Redirecting to login.' );

			wp_safe_redirect( $login_url );
			exit;
		}

		// Logged in but not a player: redirect to home.
		if ( ! wfeb_is_player() ) {
			wfeb_log( 'Player dashboard access denied - user ID ' . get_current_user_id() . ' does not have wfeb_player role.' );

			wp_safe_redirect( home_url() );
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
	 * Maps section slugs to template files within the templates/player/ directory.
	 *
	 * @since 1.0.0
	 * @return string Absolute path to the section template file.
	 */
	public function get_section_template() {
		$section = $this->get_current_section();

		$template_map = array(
			'overview'           => 'overview.php',
			'certificates'       => 'certificates.php',
			'certificate-detail' => 'certificate-detail.php',
			'scores'             => 'scores.php',
			'score-detail'       => 'score-detail.php',
			'profile'            => 'profile.php',
			'password'           => 'password.php',
		);

		$template_file = isset( $template_map[ $section ] ) ? $template_map[ $section ] : 'overview.php';

		return WFEB_PLUGIN_DIR . 'templates/player/' . $template_file;
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
			'overview'           => __( 'Overview', 'wfeb' ),
			'certificates'       => __( 'My Certificates', 'wfeb' ),
			'certificate-detail' => __( 'Certificate Details', 'wfeb' ),
			'scores'             => __( 'Score History', 'wfeb' ),
			'score-detail'       => __( 'Score Details', 'wfeb' ),
			'profile'            => __( 'Profile', 'wfeb' ),
			'password'           => __( 'Change Password', 'wfeb' ),
		);

		return isset( $titles[ $section ] ) ? $titles[ $section ] : __( 'Overview', 'wfeb' );
	}

	/**
	 * Get the sidebar navigation items for the player dashboard.
	 *
	 * Each item includes a slug, label, dashicon name, and full URL
	 * to the dashboard page with the appropriate section parameter.
	 *
	 * @since 1.0.0
	 * @return array Array of sidebar menu items.
	 */
	public function get_sidebar_items() {
		$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
		$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

		$items = array(
			array(
				'slug'  => 'overview',
				'label' => __( 'Overview', 'wfeb' ),
				'icon'  => 'dashicons-dashboard',
				'url'   => add_query_arg( 'section', 'overview', $base_url ),
			),
			array(
				'slug'  => 'certificates',
				'label' => __( 'My Certificates', 'wfeb' ),
				'icon'  => 'dashicons-awards',
				'url'   => add_query_arg( 'section', 'certificates', $base_url ),
			),
			array(
				'slug'  => 'scores',
				'label' => __( 'Score History', 'wfeb' ),
				'icon'  => 'dashicons-chart-bar',
				'url'   => add_query_arg( 'section', 'scores', $base_url ),
			),
			array(
				'slug'  => 'profile',
				'label' => __( 'Profile', 'wfeb' ),
				'icon'  => 'dashicons-admin-users',
				'url'   => add_query_arg( 'section', 'profile', $base_url ),
			),
			array(
				'slug'  => 'password',
				'label' => __( 'Change Password', 'wfeb' ),
				'icon'  => 'dashicons-lock',
				'url'   => add_query_arg( 'section', 'password', $base_url ),
			),
		);

		return $items;
	}

	/**
	 * Get the current player's record from the database.
	 *
	 * Uses the logged-in user's ID to look up the corresponding player row.
	 * The result is cached in a class property so repeated calls within
	 * the same request do not trigger additional database queries.
	 *
	 * @since 1.0.0
	 * @return object|null Player object on success, null if not found.
	 */
	public function get_player_data() {
		if ( null !== $this->player_data ) {
			return $this->player_data;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wfeb_log( 'get_player_data failed - no current user.' );
			return null;
		}

		$player_model = new WFEB_Player();
		$this->player_data = $player_model->get_by_user_id( $user_id );

		if ( ! $this->player_data ) {
			wfeb_log( 'get_player_data failed - no player record found for user ID ' . $user_id . '.' );
		}

		return $this->player_data;
	}
}
