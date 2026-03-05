<?php
/**
 * WFEB Admin Analytics
 *
 * Handles the admin analytics page with chart data.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Admin_Analytics
 *
 * Controller for the Analytics admin page.
 */
class WFEB_Admin_Analytics {

	/**
	 * Render the analytics page.
	 *
	 * Gathers chart data and passes it to the analytics template.
	 *
	 * @return void
	 */
	public function render() {
		$analytics_data = $this->get_analytics_data();

		include WFEB_PLUGIN_DIR . 'templates/admin/analytics.php';
	}

	/**
	 * Get analytics data for charts.
	 *
	 * Queries the database for various aggregate data used in Chart.js visualisations.
	 *
	 * @return array Associative array of chart data sets.
	 */
	public function get_analytics_data() {
		global $wpdb;

		$exams_table        = $wpdb->prefix . 'wfeb_exams';
		$coaches_table      = $wpdb->prefix . 'wfeb_coaches';
		$transactions_table = $wpdb->prefix . 'wfeb_credit_transactions';

		// 1. Exams per month (last 12 months).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exams_per_month = $wpdb->get_results(
			"SELECT DATE_FORMAT(exam_date, '%Y-%m') AS month,
					COUNT(*) AS count
			FROM {$exams_table}
			WHERE exam_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
				AND status = 'completed'
			GROUP BY month
			ORDER BY month ASC"
		);

		// 2. Score distribution (ranges).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$score_distribution = $wpdb->get_results(
			"SELECT
				CASE
					WHEN total_score >= 70 THEN '70-80'
					WHEN total_score >= 60 THEN '60-69'
					WHEN total_score >= 50 THEN '50-59'
					WHEN total_score >= 40 THEN '40-49'
					WHEN total_score >= 30 THEN '30-39'
					WHEN total_score >= 20 THEN '20-29'
					WHEN total_score >= 10 THEN '10-19'
					ELSE '0-9'
				END AS score_range,
				COUNT(*) AS count
			FROM {$exams_table}
			WHERE status = 'completed'
			GROUP BY score_range
			ORDER BY MIN(total_score) ASC"
		);

		// 3. Revenue data (credit purchases per month).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$revenue_data = $wpdb->get_results(
			"SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
					SUM(amount) AS credits_sold
			FROM {$transactions_table}
			WHERE type = 'purchase'
				AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			GROUP BY month
			ORDER BY month ASC"
		);

		// 4. Top coaches by exam count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$top_coaches = $wpdb->get_results(
			"SELECT c.full_name, COUNT(e.id) AS exam_count
			FROM {$exams_table} AS e
			INNER JOIN {$coaches_table} AS c ON e.coach_id = c.id
			WHERE e.status = 'completed'
			GROUP BY e.coach_id
			ORDER BY exam_count DESC
			LIMIT 10"
		);

		// 5. Achievement level distribution.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$level_distribution = $wpdb->get_results(
			"SELECT achievement_level AS level, COUNT(*) AS count
			FROM {$exams_table}
			WHERE status = 'completed' AND achievement_level != ''
			GROUP BY achievement_level
			ORDER BY MIN(total_score) DESC"
		);

		return array(
			'exams_per_month'    => $exams_per_month ? $exams_per_month : array(),
			'score_distribution' => $score_distribution ? $score_distribution : array(),
			'revenue_data'       => $revenue_data ? $revenue_data : array(),
			'top_coaches'        => $top_coaches ? $top_coaches : array(),
			'level_distribution' => $level_distribution ? $level_distribution : array(),
		);
	}
}
