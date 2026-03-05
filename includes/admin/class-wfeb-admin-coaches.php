<?php
/**
 * WFEB Admin Coaches
 *
 * Handles the admin coaches list and detail pages.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Coaches
 *
 * Controller for the Coaches admin sub-pages.
 */
class WFEB_Admin_Coaches {

	/**
	 * Render the coaches list page.
	 *
	 * Reads filters from $_GET (status, search, paged) and passes data
	 * to the coaches-list template.
	 *
	 * @return void
	 */
	public function render_list() {
		$coach_model = WFEB()->coach;

		// Read filter parameters.
		$status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged   = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;
		$offset   = ( $paged - 1 ) * $per_page;

		$args = array(
			'status'  => $status,
			'search'  => $search,
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		);

		$coaches     = $coach_model->get_all( $args );
		$total_items = $coach_model->get_count( $status );
		$total_pages = ceil( $total_items / $per_page );

		// Status counts for tabs.
		$count_all       = $coach_model->get_count();
		$count_pending   = $coach_model->get_count( 'pending' );
		$count_approved  = $coach_model->get_count( 'approved' );
		$count_rejected  = $coach_model->get_count( 'rejected' );
		$count_suspended = $coach_model->get_count( 'suspended' );

		include WFEB_PLUGIN_DIR . 'templates/admin/coaches-list.php';
	}

	/**
	 * Render the coach detail page.
	 *
	 * Reads coach_id from $_GET and loads all related data for the template.
	 *
	 * @return void
	 */
	public function render_detail() {
		$coach_id = isset( $_GET['coach_id'] ) ? absint( $_GET['coach_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $coach_id ) {
			wp_die( esc_html__( 'Invalid coach ID.', 'wfeb' ) );
		}

		$coach_model  = WFEB()->coach;
		$player_model = WFEB()->player;
		$exam_model   = WFEB()->exam;

		$coach = $coach_model->get( $coach_id );

		if ( ! $coach ) {
			wp_die( esc_html__( 'Coach not found.', 'wfeb' ) );
		}

		// Get coach's players.
		$players = $player_model->get_by_coach( $coach_id, array(
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'full_name',
			'order'   => 'ASC',
		) );

		// Get coach's recent exams.
		$recent_exams = $exam_model->get_recent( $coach_id, 10 );

		// Get credit transactions.
		$transactions = $coach_model->get_transactions( $coach_id, array(
			'limit' => 10,
		) );

		include WFEB_PLUGIN_DIR . 'templates/admin/coach-details.php';
	}
}
