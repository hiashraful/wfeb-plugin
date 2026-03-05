<?php
/**
 * Template: Player Dashboard - Score History Section
 *
 * Displays the player's completed exams in a table with all
 * 7 score categories, total, and achievement level. Includes
 * a score progression chart when multiple exams exist.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Player data.
$player    = WFEB()->player_dashboard->get_player_data();
$player_id = wfeb_get_player_id();

// Fetch completed exams.
$exams = WFEB()->exam->get_by_player(
	$player_id,
	array(
		'status'  => 'completed',
		'orderby' => 'e.exam_date',
		'order'   => 'DESC',
		'limit'   => 100,
	)
);

// Build chart data (chronological order for chart).
$chart_exams = array_reverse( $exams );
$score_chart_data = array();
foreach ( $chart_exams as $exam_item ) {
	$score_chart_data[] = array(
		'date'  => wfeb_format_date( $exam_item->exam_date, 'j M Y' ),
		'score' => absint( $exam_item->total_score ),
	);
}

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
?>

<!-- Score Progression Chart -->
<?php if ( count( $score_chart_data ) > 1 ) : ?>
	<div class="wfeb-card wfeb-mb-24">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Score Progression', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-chart-container wfeb-chart-container--wide" style="max-width: 100%;">
				<canvas id="wfeb-score-chart"></canvas>
			</div>
		</div>
	</div>

	<script>
		var wfebScoreData = <?php echo wp_json_encode( $score_chart_data ); ?>;
	</script>
<?php endif; ?>

<!-- Exam History Table -->
<?php if ( ! empty( $exams ) ) : ?>
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<?php echo esc_html__( 'All Exam Results', 'wfeb' ); ?>
				<span class="wfeb-text-muted" style="font-size: 13px; font-weight: 400; margin-left: 8px;">
					(<?php echo esc_html( count( $exams ) ); ?>)
				</span>
			</h3>
		</div>
		<div class="wfeb-card-body" style="padding: 0;">
			<div class="wfeb-table-wrap">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Date', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Coach', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Short Pass', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Long Pass', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Shooting', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Sprint', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Dribble', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Kickups', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Volley', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Total', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Level', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $exams as $exam ) :
							$detail_url = add_query_arg(
								array(
									'section' => 'score-detail',
									'exam_id' => absint( $exam->id ),
								),
								$base_url
							);
						?>
							<tr style="cursor: pointer;" onclick="window.location.href='<?php echo esc_url( $detail_url ); ?>'">
								<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
								<td><?php echo esc_html( $exam->coach_name ); ?></td>
								<td><?php echo esc_html( $exam->short_passing_total . '/10' ); ?></td>
								<td><?php echo esc_html( $exam->long_passing_total . '/10' ); ?></td>
								<td><?php echo esc_html( $exam->shooting_total . '/20' ); ?></td>
								<td><?php echo esc_html( $exam->sprint_score . '/10' ); ?></td>
								<td><?php echo esc_html( $exam->dribble_score . '/10' ); ?></td>
								<td><?php echo esc_html( $exam->kickups_score . '/10' ); ?></td>
								<td><?php echo esc_html( $exam->volley_total . '/10' ); ?></td>
								<td><strong><?php echo esc_html( $exam->total_score . '/80' ); ?></strong></td>
								<td>
									<span
										class="wfeb-badge"
										style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>; color: #fff;"
									>
										<?php echo esc_html( $exam->achievement_level ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php else : ?>

	<!-- Empty State -->
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<div class="wfeb-empty-state-icon">
					<span class="dashicons dashicons-chart-bar"></span>
				</div>
				<p class="wfeb-empty-state-title"><?php echo esc_html__( 'No Exam Results Yet', 'wfeb' ); ?></p>
				<p class="wfeb-empty-state-text"><?php echo esc_html__( 'Your exam scores will appear here once a coach conducts and completes your first exam.', 'wfeb' ); ?></p>
			</div>
		</div>
	</div>

<?php endif; ?>
