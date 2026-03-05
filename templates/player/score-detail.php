<?php
/**
 * Template: Player Dashboard - Score Detail Section
 *
 * Displays a single exam's detailed scores with radar chart,
 * 7-category breakdown with visual bars, total score + achievement
 * level, and comparison with previous exam if available.
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

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

// Get exam ID from query string and validate ownership.
$exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$exam    = $exam_id ? WFEB()->exam->get( $exam_id ) : null;

// Validate the exam belongs to this player.
if ( ! $exam || absint( $exam->player_id ) !== absint( $player_id ) ) :
?>
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<div class="wfeb-empty-state-icon">
					<span class="dashicons dashicons-warning"></span>
				</div>
				<p class="wfeb-empty-state-title"><?php echo esc_html__( 'Exam Not Found', 'wfeb' ); ?></p>
				<p class="wfeb-empty-state-text"><?php echo esc_html__( 'The exam you are looking for does not exist or does not belong to your account.', 'wfeb' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'scores', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
					<?php echo esc_html__( 'Back to Scores', 'wfeb' ); ?>
				</a>
			</div>
		</div>
	</div>
<?php
	return;
endif;

// Score categories for breakdown and radar chart.
$categories = array(
	array(
		'label' => __( 'Short Passing', 'wfeb' ),
		'score' => absint( $exam->short_passing_total ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Long Passing', 'wfeb' ),
		'score' => absint( $exam->long_passing_total ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Shooting', 'wfeb' ),
		'score' => absint( $exam->shooting_total ),
		'max'   => 20,
	),
	array(
		'label' => __( 'Sprinting', 'wfeb' ),
		'score' => absint( $exam->sprint_score ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Dribbling', 'wfeb' ),
		'score' => absint( $exam->dribble_score ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Kick Ups', 'wfeb' ),
		'score' => absint( $exam->kickups_score ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Volley', 'wfeb' ),
		'score' => absint( $exam->volley_total ),
		'max'   => 10,
	),
);

// Build radar chart data (normalize all to percentage for even display).
$radar_data = array(
	'labels' => array(),
	'scores' => array(),
);
foreach ( $categories as $cat ) {
	$radar_data['labels'][] = $cat['label'];
	$radar_data['scores'][] = $cat['max'] > 0 ? round( ( $cat['score'] / $cat['max'] ) * 100 ) : 0;
}

// Find the previous exam for comparison.
$previous_exam = null;
$all_exams     = WFEB()->exam->get_by_player(
	$player_id,
	array(
		'status'  => 'completed',
		'orderby' => 'e.exam_date',
		'order'   => 'DESC',
		'limit'   => 100,
	)
);

// Walk through exams to find the one right before the current exam.
$found_current = false;
foreach ( $all_exams as $past_exam ) {
	if ( $found_current ) {
		$previous_exam = $past_exam;
		break;
	}
	if ( absint( $past_exam->id ) === absint( $exam->id ) ) {
		$found_current = true;
	}
}

$level_lower = sanitize_title( $exam->achievement_level );
?>

<!-- Back Link -->
<div class="wfeb-mb-24">
	<a href="<?php echo esc_url( add_query_arg( 'section', 'scores', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--ghost">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
		<?php echo esc_html__( 'Back to Scores', 'wfeb' ); ?>
	</a>
</div>

<!-- Exam Info -->
<div class="wfeb-card wfeb-mb-24">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Exam Information', 'wfeb' ); ?></h3>
		<span
			class="wfeb-badge wfeb-badge--lg"
			style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>; color: #fff;"
		>
			<?php echo esc_html( $exam->achievement_level ); ?>
		</span>
	</div>
	<div class="wfeb-card-body">
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Exam Date', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 700;"><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></div>
			</div>
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Coach', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 700;"><?php echo esc_html( $exam->coach_name ); ?></div>
			</div>
			<?php if ( ! empty( $exam->assistant_examiner ) ) : ?>
				<div>
					<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
						<?php echo esc_html__( 'Assistant Examiner', 'wfeb' ); ?>
					</div>
					<div style="font-weight: 700;"><?php echo esc_html( $exam->assistant_examiner ); ?></div>
				</div>
			<?php endif; ?>
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Total Score', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 800; font-size: 24px;"><?php echo esc_html( $exam->total_score . '/80' ); ?></div>
			</div>
		</div>
	</div>
</div>

<!-- Radar Chart -->
<div class="wfeb-card wfeb-mb-24">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Skills Radar', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">
		<div class="wfeb-chart-container">
			<canvas id="wfeb-radar-chart"></canvas>
		</div>
	</div>
</div>

<script>
	var wfebRadarData = <?php echo wp_json_encode( $radar_data ); ?>;
</script>

<!-- 7-Category Breakdown -->
<div class="wfeb-card wfeb-mb-24">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Score Breakdown', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">
		<div class="wfeb-score-breakdown">
			<?php foreach ( $categories as $cat ) :
				$percent = $cat['max'] > 0 ? ( $cat['score'] / $cat['max'] ) * 100 : 0;

				// Determine bar color class based on percentage.
				if ( $percent >= 80 ) {
					$fill_class = 'wfeb-score-bar-fill--excellent';
				} elseif ( $percent >= 60 ) {
					$fill_class = 'wfeb-score-bar-fill--great';
				} elseif ( $percent >= 40 ) {
					$fill_class = 'wfeb-score-bar-fill--good';
				} elseif ( $percent >= 20 ) {
					$fill_class = 'wfeb-score-bar-fill--fair';
				} else {
					$fill_class = 'wfeb-score-bar-fill--low';
				}
			?>
				<div class="wfeb-score-bar">
					<span class="wfeb-score-bar-label"><?php echo esc_html( $cat['label'] ); ?></span>
					<div class="wfeb-score-bar-track">
						<div
							class="wfeb-score-bar-fill <?php echo esc_attr( $fill_class ); ?>"
							style="width: <?php echo esc_attr( $percent ); ?>%;"
						></div>
					</div>
					<span class="wfeb-score-bar-value"><?php echo esc_html( $cat['score'] . '/' . $cat['max'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<!-- Score Comparison with Previous Exam -->
<?php if ( $previous_exam ) :
	$score_delta = absint( $exam->total_score ) - absint( $previous_exam->total_score );

	if ( $score_delta > 0 ) {
		$change_class = 'wfeb-score-change--up';
		$change_text  = '+' . $score_delta;
	} elseif ( $score_delta < 0 ) {
		$change_class = 'wfeb-score-change--down';
		$change_text  = (string) $score_delta;
	} else {
		$change_class = 'wfeb-score-change--neutral';
		$change_text  = '0';
	}

	// Category-level deltas.
	$prev_categories = array(
		array(
			'label' => __( 'Short Passing', 'wfeb' ),
			'current' => absint( $exam->short_passing_total ),
			'previous' => absint( $previous_exam->short_passing_total ),
		),
		array(
			'label' => __( 'Long Passing', 'wfeb' ),
			'current' => absint( $exam->long_passing_total ),
			'previous' => absint( $previous_exam->long_passing_total ),
		),
		array(
			'label' => __( 'Shooting', 'wfeb' ),
			'current' => absint( $exam->shooting_total ),
			'previous' => absint( $previous_exam->shooting_total ),
		),
		array(
			'label' => __( 'Sprinting', 'wfeb' ),
			'current' => absint( $exam->sprint_score ),
			'previous' => absint( $previous_exam->sprint_score ),
		),
		array(
			'label' => __( 'Dribbling', 'wfeb' ),
			'current' => absint( $exam->dribble_score ),
			'previous' => absint( $previous_exam->dribble_score ),
		),
		array(
			'label' => __( 'Kick Ups', 'wfeb' ),
			'current' => absint( $exam->kickups_score ),
			'previous' => absint( $previous_exam->kickups_score ),
		),
		array(
			'label' => __( 'Volley', 'wfeb' ),
			'current' => absint( $exam->volley_total ),
			'previous' => absint( $previous_exam->volley_total ),
		),
	);
?>
	<div class="wfeb-card wfeb-mb-24">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Comparison with Previous Exam', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body">

			<!-- Overall Comparison -->
			<div class="wfeb-score-comparison wfeb-mb-24">
				<div class="wfeb-score-comparison-card">
					<div class="wfeb-score-comparison-card-label"><?php echo esc_html__( 'Previous', 'wfeb' ); ?></div>
					<div class="wfeb-score-comparison-card-value"><?php echo esc_html( $previous_exam->total_score ); ?><span style="font-size: 16px; color: var(--wfeb-text-muted);">/80</span></div>
					<div class="wfeb-score-comparison-card-badge">
						<span
							class="wfeb-badge"
							style="background-color: <?php echo esc_attr( wfeb_get_level_color( $previous_exam->achievement_level ) ); ?>; color: #fff;"
						>
							<?php echo esc_html( $previous_exam->achievement_level ); ?>
						</span>
					</div>
					<div class="wfeb-text-muted" style="font-size: 12px; margin-top: 8px;">
						<?php echo esc_html( wfeb_format_date( $previous_exam->exam_date ) ); ?>
					</div>
				</div>
				<div class="wfeb-score-comparison-card">
					<div class="wfeb-score-comparison-card-label"><?php echo esc_html__( 'Current', 'wfeb' ); ?></div>
					<div class="wfeb-score-comparison-card-value"><?php echo esc_html( $exam->total_score ); ?><span style="font-size: 16px; color: var(--wfeb-text-muted);">/80</span></div>
					<div class="wfeb-score-comparison-card-badge">
						<span
							class="wfeb-badge"
							style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>; color: #fff;"
						>
							<?php echo esc_html( $exam->achievement_level ); ?>
						</span>
					</div>
					<div class="<?php echo esc_attr( $change_class ); ?>" style="margin-top: 8px;">
						<?php echo esc_html( $change_text ); ?> <?php echo esc_html__( 'points', 'wfeb' ); ?>
					</div>
				</div>
			</div>

			<!-- Per-Category Comparison -->
			<div class="wfeb-table-wrap">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Category', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Previous', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Current', 'wfeb' ); ?></th>
							<th><?php echo esc_html__( 'Change', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $prev_categories as $pcat ) :
							$cat_delta = $pcat['current'] - $pcat['previous'];

							if ( $cat_delta > 0 ) {
								$cat_change_class = 'wfeb-stat-card-change--up';
								$cat_change_icon  = 'dashicons-arrow-up-alt';
								$cat_change_text  = '+' . $cat_delta;
							} elseif ( $cat_delta < 0 ) {
								$cat_change_class = 'wfeb-stat-card-change--down';
								$cat_change_icon  = 'dashicons-arrow-down-alt';
								$cat_change_text  = (string) $cat_delta;
							} else {
								$cat_change_class = '';
								$cat_change_icon  = 'dashicons-minus';
								$cat_change_text  = '0';
							}
						?>
							<tr>
								<td><strong><?php echo esc_html( $pcat['label'] ); ?></strong></td>
								<td><?php echo esc_html( $pcat['previous'] ); ?></td>
								<td><?php echo esc_html( $pcat['current'] ); ?></td>
								<td>
									<span class="wfeb-stat-card-change <?php echo esc_attr( $cat_change_class ); ?>">
										<span class="dashicons <?php echo esc_attr( $cat_change_icon ); ?>" style="font-size: 14px; width: 14px; height: 14px;"></span>
										<?php echo esc_html( $cat_change_text ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

		</div>
	</div>
<?php endif; ?>

<!-- Back to Scores Link -->
<div class="wfeb-mb-24">
	<a href="<?php echo esc_url( add_query_arg( 'section', 'scores', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--ghost">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
		<?php echo esc_html__( 'Back to Scores', 'wfeb' ); ?>
	</a>
</div>
