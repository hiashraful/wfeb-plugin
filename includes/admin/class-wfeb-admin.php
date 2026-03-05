<?php
/**
 * WFEB Admin Controller
 *
 * Main admin class that registers wp-admin menu pages, enqueues admin assets,
 * and delegates rendering to sub-page controller classes.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin
 *
 * Handles all wp-admin menu registration, asset loading, and page rendering delegation.
 */
class WFEB_Admin {

	/**
	 * Admin sub-page controllers.
	 *
	 * @var WFEB_Admin_Coaches
	 */
	private $coaches;

	/**
	 * @var WFEB_Admin_Players
	 */
	private $players;

	/**
	 * @var WFEB_Admin_Exams
	 */
	private $exams;

	/**
	 * @var WFEB_Admin_Certificates
	 */
	private $certificates;

	/**
	 * @var WFEB_Admin_Settings
	 */
	private $settings;

	/**
	 * @var WFEB_Admin_Analytics
	 */
	private $analytics;

	/**
	 * Constructor.
	 *
	 * Instantiates sub-page controllers and hooks into admin_menu and admin_enqueue_scripts.
	 */
	public function __construct() {
		$this->coaches      = new WFEB_Admin_Coaches();
		$this->players      = new WFEB_Admin_Players();
		$this->exams        = new WFEB_Admin_Exams();
		$this->certificates = new WFEB_Admin_Certificates();
		$this->settings     = new WFEB_Admin_Settings();
		$this->analytics    = new WFEB_Admin_Analytics();

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the top-level WFEB menu and all sub-menu pages.
	 *
	 * @return void
	 */
	public function register_menus() {
		// Top-level menu.
		add_menu_page(
			__( 'WFEB', 'wfeb' ),
			__( 'WFEB', 'wfeb' ),
			'manage_options',
			'wfeb-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-shield',
			30
		);

		// Dashboard sub-menu (replaces the auto-generated duplicate).
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Dashboard', 'wfeb' ),
			__( 'Dashboard', 'wfeb' ),
			'manage_options',
			'wfeb-dashboard',
			array( $this, 'render_dashboard' )
		);

		// Coaches.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Coaches', 'wfeb' ),
			__( 'Coaches', 'wfeb' ),
			'wfeb_manage_coaches',
			'wfeb-coaches',
			array( $this, 'render_coaches' )
		);

		// Players.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Players', 'wfeb' ),
			__( 'Players', 'wfeb' ),
			'wfeb_manage_players',
			'wfeb-players',
			array( $this, 'render_players' )
		);

		// Exams.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Exams', 'wfeb' ),
			__( 'Exams', 'wfeb' ),
			'wfeb_manage_exams',
			'wfeb-exams',
			array( $this, 'render_exams' )
		);

		// Certificates.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Certificates', 'wfeb' ),
			__( 'Certificates', 'wfeb' ),
			'wfeb_manage_certificates',
			'wfeb-certificates',
			array( $this, 'render_certificates' )
		);

		// Analytics.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Analytics', 'wfeb' ),
			__( 'Analytics', 'wfeb' ),
			'manage_options',
			'wfeb-analytics',
			array( $this, 'render_analytics' )
		);

		// Settings.
		add_submenu_page(
			'wfeb-dashboard',
			__( 'Settings', 'wfeb' ),
			__( 'Settings', 'wfeb' ),
			'wfeb_manage_settings',
			'wfeb-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin CSS and JS only on WFEB admin pages.
	 *
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on WFEB pages.
		if ( false === strpos( $hook, 'wfeb' ) ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'wfeb-admin',
			WFEB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WFEB_VERSION
		);

		// Admin JS.
		wp_enqueue_script(
			'wfeb-admin',
			WFEB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WFEB_VERSION,
			true
		);

		// WordPress media uploader on settings page.
		if ( false !== strpos( $hook, 'wfeb-settings' ) ) {
			wp_enqueue_media();
		}

		// Chart.js on analytics page.
		if ( false !== strpos( $hook, 'wfeb-analytics' ) ) {
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js',
				array(),
				'4.4.0',
				true
			);
		}

		// Localize script data.
		wp_localize_script(
			'wfeb-admin',
			'wfeb_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wfeb_admin_nonce' ),
			)
		);
	}

	/**
	 * Render the admin dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		include WFEB_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Render coaches pages (list or detail based on query params).
	 *
	 * @return void
	 */
	public function render_coaches() {
		if ( ! empty( $_GET['coach_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->coaches->render_detail();
		} else {
			$this->coaches->render_list();
		}
	}

	/**
	 * Render the players list page.
	 *
	 * @return void
	 */
	public function render_players() {
		$this->players->render_list();
	}

	/**
	 * Render exams pages (list or detail based on query params).
	 *
	 * @return void
	 */
	public function render_exams() {
		if ( ! empty( $_GET['exam_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->exams->render_detail();
		} else {
			$this->exams->render_list();
		}
	}

	/**
	 * Render the certificates list page.
	 *
	 * @return void
	 */
	public function render_certificates() {
		$this->certificates->render_list();
	}

	/**
	 * Render the analytics page.
	 *
	 * @return void
	 */
	public function render_analytics() {
		$this->analytics->render();
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		$this->settings->render();
	}
}
