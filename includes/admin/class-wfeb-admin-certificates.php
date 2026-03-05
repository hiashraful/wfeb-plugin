<?php
/**
 * WFEB Admin Certificates
 *
 * Handles the admin certificates list page.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Certificates
 *
 * Controller for the Certificates admin sub-page.
 */
class WFEB_Admin_Certificates {

	/**
	 * Render the certificates list page.
	 *
	 * Retrieves all certificates with filters from $_GET and passes data
	 * to the certificates-list template.
	 *
	 * @return void
	 */
	public function render_list() {
		$cert_model = WFEB()->certificate;

		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page  = 20;
		$offset    = ( $paged - 1 ) * $per_page;

		$args = array(
			'search'    => $search,
			'status'    => $status,
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'limit'     => $per_page,
			'offset'    => $offset,
			'orderby'   => 'issued_at',
			'order'     => 'DESC',
		);

		$certificates = $cert_model->get_all( $args );
		$total_items  = $cert_model->get_count( $status );
		$total_pages  = ceil( $total_items / $per_page );

		// Status counts for display.
		$count_all     = $cert_model->get_count();
		$count_active  = $cert_model->get_count( 'active' );
		$count_revoked = $cert_model->get_count( 'revoked' );

		include WFEB_PLUGIN_DIR . 'templates/admin/certificates-list.php';
	}
}
