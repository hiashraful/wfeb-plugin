<?php
/**
 * WFEB Roles and Capabilities
 *
 * Handles custom role creation, login redirects, and wp-admin restrictions
 * for WFEB Coach and Player roles.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Roles
 *
 * Manages custom WordPress roles and capabilities for the WFEB plugin.
 */
class WFEB_Roles {

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress init, admin_init, login_redirect, and admin bar filter.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'restrict_wp_login' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'block_wp_admin' ) );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );
	}

	/**
	 * Create custom WFEB roles and add capabilities to administrator.
	 *
	 * @return void
	 */
	public static function create_roles() {
		// Create Coach role.
		add_role(
			'wfeb_coach',
			__( 'WFEB Coach', 'wfeb' ),
			array(
				'read'                    => true,
				'upload_files'            => true,
				'wfeb_conduct_exam'       => true,
				'wfeb_manage_own_players' => true,
				'wfeb_view_own_exams'     => true,
			)
		);

		// Create Player role.
		add_role(
			'wfeb_player',
			__( 'WFEB Player', 'wfeb' ),
			array(
				'read'                       => true,
				'wfeb_view_own_certificates' => true,
				'wfeb_view_own_scores'       => true,
			)
		);

		// Add WFEB management capabilities to administrator.
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			$admin_role->add_cap( 'wfeb_manage_coaches' );
			$admin_role->add_cap( 'wfeb_manage_players' );
			$admin_role->add_cap( 'wfeb_manage_exams' );
			$admin_role->add_cap( 'wfeb_manage_certificates' );
			$admin_role->add_cap( 'wfeb_manage_settings' );
		}
	}

	/**
	 * Remove custom WFEB roles and administrator capabilities.
	 *
	 * @return void
	 */
	public static function remove_roles() {
		// Remove custom roles.
		remove_role( 'wfeb_coach' );
		remove_role( 'wfeb_player' );

		// Remove WFEB capabilities from administrator.
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			$admin_role->remove_cap( 'wfeb_manage_coaches' );
			$admin_role->remove_cap( 'wfeb_manage_players' );
			$admin_role->remove_cap( 'wfeb_manage_exams' );
			$admin_role->remove_cap( 'wfeb_manage_certificates' );
			$admin_role->remove_cap( 'wfeb_manage_settings' );
		}
	}

	/**
	 * Block wp-admin access for WFEB Coach and Player roles.
	 *
	 * Redirects coaches to /coach-dashboard/ and players to /player-dashboard/.
	 * Allows AJAX requests to pass through.
	 *
	 * @return void
	 */
	public function block_wp_admin() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( in_array( 'wfeb_coach', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/coach-dashboard/' ) );
			exit;
		}

		if ( in_array( 'wfeb_player', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/player-dashboard/' ) );
			exit;
		}
	}

	/**
	 * Hide the WordPress admin bar for WFEB Coach and Player roles.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public function hide_admin_bar( $show ) {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return $show;
		}

		if ( in_array( 'wfeb_coach', (array) $user->roles, true ) ) {
			return false;
		}

		if ( in_array( 'wfeb_player', (array) $user->roles, true ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Redirect WFEB users to their respective dashboards after login.
	 *
	 * @param string           $redirect_to The redirect destination URL.
	 * @param string           $request     The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user        WP_User object if login was successful, WP_Error object otherwise.
	 * @return string
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
		if ( ! isset( $user->roles ) || ! is_array( $user->roles ) ) {
			return $redirect_to;
		}

		if ( in_array( 'wfeb_coach', $user->roles, true ) ) {
			return home_url( '/coach-dashboard/' );
		}

		if ( in_array( 'wfeb_player', $user->roles, true ) ) {
			return home_url( '/player-dashboard/' );
		}

		return $redirect_to;
	}

	/**
	 * Restrict access to the default WordPress registration page.
	 *
	 * Redirects wp-login.php?action=register to the coach registration page.
	 *
	 * @return void
	 */
	public function restrict_wp_login() {
		global $pagenow;

		if ( 'wp-login.php' === $pagenow && isset( $_GET['action'] ) && 'register' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( home_url( '/coach-registration/' ) );
			exit;
		}
	}
}
