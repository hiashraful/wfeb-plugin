<?php
/**
 * WFEB Admin Settings
 *
 * Handles the admin settings page with tabbed interface.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Settings
 *
 * Controller for the Settings admin page.
 */
class WFEB_Admin_Settings {

	/**
	 * Render the settings page.
	 *
	 * Loads the current settings and passes them to the settings template.
	 *
	 * @return void
	 */
	public function render() {
		$tabs         = $this->get_tabs();
		$settings     = $this->get_settings();
		$active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Ensure valid tab.
		$valid_tabs = array_keys( $tabs );
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'general';
		}

		include WFEB_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Get all current plugin settings as an associative array.
	 *
	 * @return array Settings key-value pairs.
	 */
	public function get_settings() {
		return array(
			// General.
			'cert_prefix'          => get_option( 'wfeb_cert_prefix', 'WFEB' ),
			'cert_start'           => get_option( 'wfeb_cert_start', 1000 ),
			'coach_approval_mode'  => get_option( 'wfeb_coach_approval_mode', 'manual' ),
			'wfeb_logo'            => get_option( 'wfeb_logo', '' ),

			// WooCommerce.
			'credit_product_id'          => get_option( 'wfeb_credit_product_id', '' ),
			'credit_price'               => get_option( 'wfeb_credit_price', '' ),
			'credit_checkout_page_id'    => get_option( 'wfeb_credit_checkout_page_id', '' ),

			// Email.
			'email_from_name'      => get_option( 'wfeb_email_from_name', get_bloginfo( 'name' ) ),
			'email_from_address'   => get_option( 'wfeb_email_from_address', get_option( 'admin_email' ) ),

			// Exam.
			'achievement_thresholds' => get_option( 'wfeb_achievement_thresholds', array() ),

			// Certificate.
			'cert_background'      => get_option( 'wfeb_cert_background', '' ),
			'cert_authoriser_name' => get_option( 'wfeb_cert_authoriser_name', '' ),
		);
	}

	/**
	 * Get tab definitions for the settings page.
	 *
	 * @return array Array of tab slug => tab label pairs.
	 */
	public function get_tabs() {
		return array(
			'general'     => __( 'General', 'wfeb' ),
			'woocommerce' => __( 'WooCommerce', 'wfeb' ),
			'email'       => __( 'Email', 'wfeb' ),
			'exam'        => __( 'Exam', 'wfeb' ),
			'certificate' => __( 'Certificate', 'wfeb' ),
		);
	}
}
