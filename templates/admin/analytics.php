<?php
/**
 * WFEB Admin Analytics Template
 *
 * Displays analytics charts using Chart.js.
 *
 * Variables available from WFEB_Admin_Analytics::render():
 * - $analytics_data (array with: exams_per_month, score_distribution,
 *   revenue_data, top_coaches, level_distribution)
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Derive summary stats from $analytics_data for the stat cards row.
// ---------------------------------------------------------------------------

// Exams this month: last entry in exams_per_month where month == current month.
$current_month   = gmdate( 'Y-m' );
$exams_this_month = 0;
if ( ! empty( $analytics_data['exams_per_month'] ) ) {
	foreach ( $analytics_data['exams_per_month'] as $epm ) {
		if ( isset( $epm->month ) && $epm->month === $current_month ) {
			$exams_this_month = (int) $epm->count;
			break;
		}
	}
}

// Total completed exams across the 12-month window (sum of exams_per_month).
$total_exams_window = 0;
if ( ! empty( $analytics_data['exams_per_month'] ) ) {
	foreach ( $analytics_data['exams_per_month'] as $epm ) {
		$total_exams_window += (int) $epm->count;
	}
}

// Average score approximation: mid-point weighted average from score_distribution.
$score_range_midpoints = array(
	'70-80' => 75,
	'60-69' => 64,
	'50-59' => 54,
	'40-49' => 44,
	'30-39' => 34,
	'20-29' => 24,
	'10-19' => 14,
	'0-9'   => 4,
);
$avg_score           = 0;
$score_total_weight  = 0;
$score_weighted_sum  = 0;
if ( ! empty( $analytics_data['score_distribution'] ) ) {
	foreach ( $analytics_data['score_distribution'] as $sd ) {
		$midpoint           = isset( $score_range_midpoints[ $sd->score_range ] ) ? $score_range_midpoints[ $sd->score_range ] : 40;
		$count              = (int) $sd->count;
		$score_weighted_sum += $midpoint * $count;
		$score_total_weight += $count;
	}
	if ( $score_total_weight > 0 ) {
		$avg_score = round( $score_weighted_sum / $score_total_weight, 1 );
	}
}

// Total credits sold this 12-month window (sum of revenue_data).
$credits_this_period = 0;
if ( ! empty( $analytics_data['revenue_data'] ) ) {
	foreach ( $analytics_data['revenue_data'] as $rd ) {
		$credits_this_period += (int) $rd->credits_sold;
	}
}

// Active coaches in the data (distinct coaches in top_coaches list).
$active_coaches_count = ! empty( $analytics_data['top_coaches'] ) ? count( $analytics_data['top_coaches'] ) : 0;
?>
<div class="wfeb-wrap">

	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title"><?php esc_html_e( 'Analytics', 'wfeb' ); ?></h1>
	</div>

	<!-- Summary Stat Cards -->
	<div class="wfeb-stats-grid">

		<div class="wfeb-stat-card">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Exams This Month', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $exams_this_month ); ?></div>
			<div class="wfeb-stat-footer"><?php echo esc_html( $total_exams_window ); ?> <?php esc_html_e( 'in last 12 months', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card" data-tooltip="Approximate average across all exams, estimated from score range midpoints">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Avg Score', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $avg_score ); ?></div>
			<div class="wfeb-stat-footer"><?php esc_html_e( 'weighted midpoint estimate', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card" data-tooltip="Total exam credits purchased by coaches via WooCommerce">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Credits Issued', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $credits_this_period ); ?></div>
			<div class="wfeb-stat-footer"><?php esc_html_e( 'last 12 months', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card">
			<div class="wfeb-stat-label"><?php esc_html_e( 'Active Coaches', 'wfeb' ); ?></div>
			<div class="wfeb-stat-number"><?php echo esc_html( $active_coaches_count ); ?></div>
			<div class="wfeb-stat-footer"><?php esc_html_e( 'with completed exams', 'wfeb' ); ?></div>
		</div>

	</div>

	<!-- Date Range Filter -->
	<div class="wfeb-table-card">
		<form method="get">
			<input type="hidden" name="page" value="wfeb-analytics" />
			<div class="wfeb-filter-bar">
				<div class="wfeb-filter-group">
					<label for="wfeb-analytics-from"><?php esc_html_e( 'From', 'wfeb' ); ?></label>
					<input type="date" id="wfeb-analytics-from" name="date_from"
						value="<?php echo esc_attr( isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '' ); ?>" />
				</div>
				<div class="wfeb-filter-group">
					<label for="wfeb-analytics-to"><?php esc_html_e( 'To', 'wfeb' ); ?></label>
					<input type="date" id="wfeb-analytics-to" name="date_to"
						value="<?php echo esc_attr( isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '' ); ?>" />
				</div>
				<div class="wfeb-filter-actions">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm">
						<?php esc_html_e( 'Filter', 'wfeb' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-analytics' ) ); ?>"
						class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">
						<?php esc_html_e( 'Reset', 'wfeb' ); ?>
					</a>
				</div>
			</div>
		</form>
	</div>

	<!-- Charts Grid -->
	<div class="wfeb-charts-grid">

		<!-- Exams Per Month (Bar Chart) -->
		<div class="wfeb-chart-card">
			<h3 class="wfeb-chart-card-title"><?php esc_html_e( 'Exams Per Month', 'wfeb' ); ?></h3>
			<canvas id="wfeb-chart-exams-month"></canvas>
		</div>

		<!-- Score Distribution (Pie Chart) -->
		<div class="wfeb-chart-card">
			<h3 class="wfeb-chart-card-title"><?php esc_html_e( 'Score Distribution', 'wfeb' ); ?></h3>
			<canvas id="wfeb-chart-score-dist"></canvas>
		</div>

		<!-- Credits Revenue (Line Chart) -->
		<div class="wfeb-chart-card">
			<h3 class="wfeb-chart-card-title"><?php esc_html_e( 'Credits Revenue', 'wfeb' ); ?></h3>
			<canvas id="wfeb-chart-revenue"></canvas>
		</div>

		<!-- Achievement Levels (Doughnut Chart) -->
		<div class="wfeb-chart-card">
			<h3 class="wfeb-chart-card-title"><?php esc_html_e( 'Achievement Levels', 'wfeb' ); ?></h3>
			<canvas id="wfeb-chart-levels"></canvas>
		</div>

	</div>

	<!-- Top Coaches Table -->
	<?php if ( ! empty( $analytics_data['top_coaches'] ) ) : ?>
	<div class="wfeb-table-card">
		<div class="wfeb-table-header">
			<h2 class="wfeb-table-title"><?php esc_html_e( 'Top Coaches by Exams', 'wfeb' ); ?></h2>
		</div>
		<table class="wfeb-table">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Coach Name', 'wfeb' ); ?></th>
					<th><?php esc_html_e( 'Exams Conducted', 'wfeb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $analytics_data['top_coaches'] as $index => $tc ) : ?>
				<tr>
					<td><?php echo esc_html( $index + 1 ); ?></td>
					<td><?php echo esc_html( $tc->full_name ); ?></td>
					<td><?php echo esc_html( $tc->exam_count ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>

<!-- Inline JSON data for Chart.js -->
<script type="text/javascript">
(function() {
	'use strict';

	var analyticsData = <?php echo wp_json_encode( $analytics_data ); ?>;

	function initCharts() {
		if ( typeof Chart === 'undefined' ) {
			return;
		}

		// Exams Per Month - Bar Chart
		var examsCtx = document.getElementById('wfeb-chart-exams-month');
		if ( examsCtx ) {
			var examsLabels = [];
			var examsCounts = [];
			if ( analyticsData.exams_per_month ) {
				analyticsData.exams_per_month.forEach(function(item) {
					examsLabels.push(item.month);
					examsCounts.push(parseInt(item.count, 10));
				});
			}
			new Chart(examsCtx, {
				type: 'bar',
				data: {
					labels: examsLabels,
					datasets: [{
						label: '<?php echo esc_js( __( 'Exams', 'wfeb' ) ); ?>',
						data: examsCounts,
						backgroundColor: 'rgba(54, 162, 235, 0.6)',
						borderColor: 'rgba(54, 162, 235, 1)',
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
				}
			});
		}

		// Score Distribution - Pie Chart
		var scoreCtx = document.getElementById('wfeb-chart-score-dist');
		if ( scoreCtx ) {
			var scoreLabels = [];
			var scoreCounts = [];
			var scoreColors = [
				'#FF6384', '#FF9F40', '#FFCD56', '#4BC0C0',
				'#36A2EB', '#9966FF', '#C9CBCF', '#FF6384'
			];
			if ( analyticsData.score_distribution ) {
				analyticsData.score_distribution.forEach(function(item) {
					scoreLabels.push(item.score_range);
					scoreCounts.push(parseInt(item.count, 10));
				});
			}
			new Chart(scoreCtx, {
				type: 'pie',
				data: {
					labels: scoreLabels,
					datasets: [{
						data: scoreCounts,
						backgroundColor: scoreColors.slice(0, scoreLabels.length)
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false
				}
			});
		}

		// Credits Revenue - Line Chart
		var revenueCtx = document.getElementById('wfeb-chart-revenue');
		if ( revenueCtx ) {
			var revenueLabels = [];
			var revenueValues = [];
			if ( analyticsData.revenue_data ) {
				analyticsData.revenue_data.forEach(function(item) {
					revenueLabels.push(item.month);
					revenueValues.push(parseInt(item.credits_sold, 10));
				});
			}
			new Chart(revenueCtx, {
				type: 'line',
				data: {
					labels: revenueLabels,
					datasets: [{
						label: '<?php echo esc_js( __( 'Credits Sold', 'wfeb' ) ); ?>',
						data: revenueValues,
						borderColor: 'rgba(75, 192, 192, 1)',
						backgroundColor: 'rgba(75, 192, 192, 0.2)',
						tension: 0.3,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: { y: { beginAtZero: true } }
				}
			});
		}

		// Achievement Levels - Doughnut Chart
		var levelsCtx = document.getElementById('wfeb-chart-levels');
		if ( levelsCtx ) {
			var levelLabels = [];
			var levelCounts = [];
			var levelColors = [];
			var colorMap = {
				'MASTERY': '#FF0000',
				'DIAMOND': '#B9F2FF',
				'GOLD': '#FFD700',
				'SILVER': '#C0C0C0',
				'BRONZE': '#CD7F32',
				'MERIT+': '#4CAF50',
				'MERIT': '#66BB6A',
				'MERIT-': '#81C784',
				'PASS+': '#2196F3',
				'PASS': '#42A5F5',
				'UNGRADED': '#9E9E9E',
				'UNCLASSIFIED': '#9E9E9E'
			};
			if ( analyticsData.level_distribution ) {
				analyticsData.level_distribution.forEach(function(item) {
					levelLabels.push(item.level);
					levelCounts.push(parseInt(item.count, 10));
					levelColors.push(colorMap[item.level] || '#9E9E9E');
				});
			}
			new Chart(levelsCtx, {
				type: 'doughnut',
				data: {
					labels: levelLabels,
					datasets: [{
						data: levelCounts,
						backgroundColor: levelColors
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false
				}
			});
		}
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'complete' || document.readyState === 'interactive' ) {
		setTimeout(initCharts, 100);
	} else {
		document.addEventListener('DOMContentLoaded', initCharts);
	}
})();
</script>
