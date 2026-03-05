<?php
/**
 * WFEB Admin Players
 *
 * Handles the admin players list page.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Players
 *
 * Controller for the Players admin sub-page.
 */
class WFEB_Admin_Players {

	/**
	 * Render the players list page.
	 *
	 * Retrieves all players with filters from $_GET and passes data
	 * to the players-list template.
	 *
	 * @return void
	 */
	public function render_list() {
		global $wpdb;

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;
		$offset   = ( $paged - 1 ) * $per_page;

		$players_table = $wpdb->prefix . 'wfeb_players';
		$coaches_table = $wpdb->prefix . 'wfeb_coaches';
		$exams_table   = $wpdb->prefix . 'wfeb_exams';

		$where  = array();
		$values = array();

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(p.full_name LIKE %s OR p.email LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		// Build count query.
		$count_sql = "SELECT COUNT(*) FROM {$players_table} AS p {$where_clause}";
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total_items = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total_items = absint( $wpdb->get_var( $count_sql ) );
		}

		$total_pages = ceil( $total_items / $per_page );

		// Build main query with joins for coach name and exam stats.
		$query_values   = $values;
		$query_values[] = $per_page;
		$query_values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT p.*,
					c.full_name AS coach_name,
					COALESCE(es.exam_count, 0) AS exam_count,
					es.best_score,
					es.best_level
				FROM {$players_table} AS p
				LEFT JOIN {$coaches_table} AS c ON p.coach_id = c.id
				LEFT JOIN (
					SELECT player_id,
						COUNT(*) AS exam_count,
						MAX(total_score) AS best_score,
						SUBSTRING_INDEX(GROUP_CONCAT(achievement_level ORDER BY total_score DESC), ',', 1) AS best_level
					FROM {$exams_table}
					WHERE status = 'completed'
					GROUP BY player_id
				) AS es ON p.id = es.player_id
				{$where_clause}
				ORDER BY p.created_at DESC
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$players = $wpdb->get_results( $wpdb->prepare( $sql, $query_values ) );

		if ( ! $players ) {
			$players = array();
		}

		include WFEB_PLUGIN_DIR . 'templates/admin/players-list.php';
	}
}
