<?php
/**
 * Template: Player Dashboard - Overview Section
 *
 * Displays the player dashboard overview with stats cards,
 * latest certificate, recent exams table, and score progression chart.
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

// Fetch exam stats.
$exam_stats = WFEB()->player->get_exam_stats( $player_id );

// Certificate count.
$certificates    = WFEB()->certificate->get_by_player( $player_id, array( 'limit' => 1000 ) );
$total_certs     = count( $certificates );
$latest_cert     = ! empty( $certificates ) ? $certificates[0] : null;

// Recent exams (last 5 completed).
$recent_exams = WFEB()->exam->get_by_player(
	$player_id,
	array(
		'status' => 'completed',
		'limit'  => 5,
	)
);

// All completed exams for score chart (ordered ASC for chronological display).
$all_exams = WFEB()->exam->get_by_player(
	$player_id,
	array(
		'status'  => 'completed',
		'orderby' => 'e.exam_date',
		'order'   => 'ASC',
		'limit'   => 100,
	)
);

// Build score data for Chart.js.
$score_chart_data = array();
foreach ( $all_exams as $exam_item ) {
	$score_chart_data[] = array(
		'date'  => wfeb_format_date( $exam_item->exam_date, 'j M Y' ),
		'score' => absint( $exam_item->total_score ),
	);
}

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
?>

<!-- Stats Grid -->
<div class="wfeb-stats-grid">

	<div class="wfeb-stat-card">
		<div class="wfeb-stat-card-icon wfeb-stat-card-icon--accent">
			<span class="dashicons dashicons-awards"></span>
		</div>
		<div class="wfeb-stat-card-info">
			<div class="wfeb-stat-card-label"><?php echo esc_html__( 'Total Certificates', 'wfeb' ); ?></div>
			<div class="wfeb-stat-card-value"><?php echo esc_html( $total_certs ); ?></div>
		</div>
	</div>

	<div class="wfeb-stat-card">
		<div class="wfeb-stat-card-icon wfeb-stat-card-icon--info">
			<span class="dashicons dashicons-star-filled"></span>
		</div>
		<div class="wfeb-stat-card-info">
			<div class="wfeb-stat-card-label"><?php echo esc_html__( 'Best Score', 'wfeb' ); ?></div>
			<div class="wfeb-stat-card-value">
				<?php
				if ( $exam_stats->best_score ) {
					echo esc_html( $exam_stats->best_score . '/80' );
				} else {
					echo esc_html( '--' );
				}
				?>
			</div>
		</div>
	</div>

	<div class="wfeb-stat-card">
		<div class="wfeb-stat-card-icon wfeb-stat-card-icon--gold">
			<span class="dashicons dashicons-shield"></span>
		</div>
		<div class="wfeb-stat-card-info">
			<div class="wfeb-stat-card-label"><?php echo esc_html__( 'Best Level', 'wfeb' ); ?></div>
			<div class="wfeb-stat-card-value">
				<?php if ( $exam_stats->best_level ) : ?>
					<span
						class="wfeb-badge wfeb-badge--lg"
						style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam_stats->best_level ) ); ?>; color: #fff;"
					>
						<?php echo esc_html( $exam_stats->best_level ); ?>
					</span>
				<?php else : ?>
					<?php echo esc_html( '--' ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="wfeb-stat-card">
		<div class="wfeb-stat-card-icon wfeb-stat-card-icon--warning">
			<span class="dashicons dashicons-clipboard"></span>
		</div>
		<div class="wfeb-stat-card-info">
			<div class="wfeb-stat-card-label"><?php echo esc_html__( 'Total Exams', 'wfeb' ); ?></div>
			<div class="wfeb-stat-card-value"><?php echo esc_html( $exam_stats->total_exams ); ?></div>
		</div>
	</div>

</div>

<!-- Latest Certificate -->
<?php if ( $latest_cert ) : ?>
	<div class="wfeb-card wfeb-mb-24">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Latest Certificate', 'wfeb' ); ?></h3>
			<a href="<?php echo esc_url( add_query_arg( 'section', 'certificates', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--secondary">
				<?php echo esc_html__( 'View All', 'wfeb' ); ?>
			</a>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-flex-between" style="flex-wrap: wrap; gap: 16px;">
				<div>
					<span
						class="wfeb-badge wfeb-badge--lg"
						style="background-color: <?php echo esc_attr( wfeb_get_level_color( $latest_cert->achievement_level ) ); ?>; color: #fff;"
					>
						<?php echo esc_html( $latest_cert->achievement_level ); ?>
					</span>
					<div class="wfeb-mt-8">
						<strong><?php echo esc_html( $latest_cert->total_score . '/80' ); ?></strong>
						<span class="wfeb-text-muted"> - <?php echo esc_html( $latest_cert->certificate_number ); ?></span>
					</div>
					<div class="wfeb-text-muted" style="font-size: 13px; margin-top: 4px;">
						<?php echo esc_html( wfeb_format_date( $latest_cert->issued_at ) ); ?>
					</div>
				</div>
				<div class="wfeb-flex wfeb-gap-8">
					<?php if ( ! empty( $latest_cert->pdf_url ) ) : ?>
						<a href="<?php echo esc_url( $latest_cert->pdf_url ); ?>" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-download"></span>
							<?php echo esc_html__( 'Download', 'wfeb' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'section' => 'certificate-detail', 'cert_id' => $latest_cert->id ), $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary wfeb-btn--sm">
						<?php echo esc_html__( 'View Details', 'wfeb' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- Recent Exams -->
<div class="wfeb-card wfeb-mb-24">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Recent Exams', 'wfeb' ); ?></h3>
		<a href="<?php echo esc_url( add_query_arg( 'section', 'scores', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--secondary">
			<?php echo esc_html__( 'View All', 'wfeb' ); ?>
		</a>
	</div>
	<div class="wfeb-card-body">
		<?php if ( ! empty( $recent_exams ) ) : ?>
			<div class="wfeb-table-wrap">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Date', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Coach', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Score', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Level', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_exams as $exam ) : ?>
							<tr>
								<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
								<td><?php echo esc_html( $exam->coach_name ); ?></td>
								<td><?php echo esc_html( $exam->total_score . '/80' ); ?></td>
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
		<?php else : ?>
			<div class="wfeb-empty-state">
				<div class="wfeb-empty-state-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<p class="wfeb-empty-state-text"><?php echo esc_html__( 'No exams yet. Your exam results will appear here once a coach conducts your first exam.', 'wfeb' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Score Progression Chart -->
<?php if ( count( $score_chart_data ) > 1 ) : ?>
	<div class="wfeb-card">
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
