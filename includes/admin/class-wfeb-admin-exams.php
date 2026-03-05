<?php
/**
 * WFEB Admin Exams
 *
 * Handles the admin exams list and detail pages.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Exams
 *
 * Controller for the Exams admin sub-pages.
 */
class WFEB_Admin_Exams {

	/**
	 * Render the exams list page.
	 *
	 * Retrieves all exams with filters from $_GET and passes data
	 * to the exams-list template.
	 *
	 * @return void
	 */
	public function render_list() {
		global $wpdb;

		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page  = 20;
		$offset    = ( $paged - 1 ) * $per_page;

		$exams_table   = $wpdb->prefix . 'wfeb_exams';
		$players_table = $wpdb->prefix . 'wfeb_players';
		$coaches_table = $wpdb->prefix . 'wfeb_coaches';
		$certs_table   = $wpdb->prefix . 'wfeb_certificates';

		$where  = array();
		$values = array();

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(p.full_name LIKE %s OR c.full_name LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $status ) && in_array( $status, array( 'draft', 'completed' ), true ) ) {
			$where[]  = 'e.status = %s';
			$values[] = $status;
		}

		if ( ! empty( $date_from ) ) {
			$where[]  = 'e.exam_date >= %s';
			$values[] = $date_from;
		}

		if ( ! empty( $date_to ) ) {
			$where[]  = 'e.exam_date <= %s';
			$values[] = $date_to;
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		// Count query.
		$count_sql = "SELECT COUNT(*) FROM {$exams_table} AS e
						LEFT JOIN {$players_table} AS p ON e.player_id = p.id
						LEFT JOIN {$coaches_table} AS c ON e.coach_id = c.id
						{$where_clause}";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total_items = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total_items = absint( $wpdb->get_var( $count_sql ) );
		}

		$total_pages = ceil( $total_items / $per_page );

		// Main query.
		$query_values   = $values;
		$query_values[] = $per_page;
		$query_values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT e.*,
					p.full_name AS player_name,
					c.full_name AS coach_name,
					cert.certificate_number
				FROM {$exams_table} AS e
				LEFT JOIN {$players_table} AS p ON e.player_id = p.id
				LEFT JOIN {$coaches_table} AS c ON e.coach_id = c.id
				LEFT JOIN {$certs_table} AS cert ON e.id = cert.exam_id
				{$where_clause}
				ORDER BY e.exam_date DESC, e.id DESC
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$exams = $wpdb->get_results( $wpdb->prepare( $sql, $query_values ) );

		if ( ! $exams ) {
			$exams = array();
		}

		include WFEB_PLUGIN_DIR . 'templates/admin/exams-list.php';
	}

	/**
	 * Render the exam detail page.
	 *
	 * Reads exam_id from $_GET and loads all related data for the template.
	 *
	 * @return void
	 */
	public function render_detail() {
		$exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $exam_id ) {
			wp_die( esc_html__( 'Invalid exam ID.', 'wfeb' ) );
		}

		$exam_model = WFEB()->exam;
		$exam       = $exam_model->get( $exam_id );

		if ( ! $exam ) {
			wp_die( esc_html__( 'Exam not found.', 'wfeb' ) );
		}

		// Get certificate if exists.
		$certificate = WFEB()->certificate->get_by_exam( $exam_id );

		include WFEB_PLUGIN_DIR . 'templates/admin/exam-details.php';
	}
}
