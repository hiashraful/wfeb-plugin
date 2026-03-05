<?php
/**
 * Template: Coach Dashboard - Exam Details Section
 *
 * Displays full exam details with score breakdown bars,
 * radar chart canvas, certificate info, and notes.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Coach data.
$coach    = WFEB()->coach_dashboard->get_coach_data();
$coach_id = wfeb_get_coach_id();

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

// Get exam.
$exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$exam    = null;

if ( $exam_id ) {
	$exam = WFEB()->exam->get( $exam_id );

	// Validate the exam belongs to this coach.
	if ( $exam && absint( $exam->coach_id ) !== $coach_id ) {
		$exam = null;
	}
}

if ( ! $exam ) : ?>
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<p><?php echo esc_html__( 'Exam not found or you do not have access to this exam.', 'wfeb' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'exam-history', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
					<?php echo esc_html__( 'Back to Exam History', 'wfeb' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

// Check for certificate.
$certificate = WFEB()->certificate->get_by_exam( $exam->id );

// Category scores for breakdown bars.
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
		'label' => __( 'Kick Up Touch', 'wfeb' ),
		'score' => absint( $exam->kickups_score ),
		'max'   => 10,
	),
	array(
		'label' => __( 'Volley Touch', 'wfeb' ),
		'score' => absint( $exam->volley_total ),
		'max'   => 10,
	),
);

// Radar chart data (for JS).
$radar_labels = array();
$radar_scores = array();
$radar_max    = array();

foreach ( $categories as $cat ) {
	$radar_labels[] = $cat['label'];
	$radar_scores[] = $cat['score'];
	$radar_max[]    = $cat['max'];
}
?>

<!-- Exam Info Card -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Exam Information', 'wfeb' ); ?></h3>
		<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?> wfeb-badge--lg">
			<?php echo esc_html( ucfirst( $exam->status ) ); ?>
		</span>
	</div>
	<div class="wfeb-card-body">
		<div class="wfeb-detail-grid">
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Exam Date', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Player', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( $exam->player_name ); ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Assistant Examiner', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value">
					<?php echo ! empty( $exam->assistant_examiner ) ? esc_html( $exam->assistant_examiner ) : '<span class="wfeb-text-muted">-</span>'; ?>
				</span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Created', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $exam->created_at, 'j M Y, g:i A' ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<!-- Total Score & Achievement Level -->
<div class="wfeb-card wfeb-exam-result-card">
	<div class="wfeb-card-body">
		<div class="wfeb-exam-result">
			<div class="wfeb-exam-result-score">
				<span class="wfeb-exam-result-value"><?php echo esc_html( $exam->total_score ); ?></span>
				<span class="wfeb-exam-result-max">/80</span>
			</div>
			<div class="wfeb-exam-result-level">
				<span
					class="wfeb-badge wfeb-badge--level wfeb-badge--xl"
					style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>;"
				>
					<?php echo esc_html( $exam->achievement_level ); ?>
				</span>
				<span class="wfeb-exam-result-playing"><?php echo esc_html( $exam->playing_level ); ?></span>
			</div>
		</div>
	</div>
</div>

<!-- Score Breakdown -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Score Breakdown', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">

		<div class="wfeb-exam-breakdown-grid">

			<!-- Bars -->
			<div class="wfeb-breakdown-bars">
				<?php foreach ( $categories as $cat ) : ?>
					<?php $percentage = $cat['max'] > 0 ? round( ( $cat['score'] / $cat['max'] ) * 100 ) : 0; ?>
					<div class="wfeb-breakdown-item">
						<span class="wfeb-breakdown-label"><?php echo esc_html( $cat['label'] ); ?></span>
						<div class="wfeb-breakdown-bar">
							<div class="wfeb-breakdown-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
						</div>
						<span class="wfeb-breakdown-value"><?php echo esc_html( $cat['score'] . '/' . $cat['max'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Radar Chart -->
			<div class="wfeb-radar-chart-wrap">
				<canvas
					id="wfeb-radar-chart"
					data-labels="<?php echo esc_attr( wp_json_encode( $radar_labels ) ); ?>"
					data-scores="<?php echo esc_attr( wp_json_encode( $radar_scores ) ); ?>"
					data-max="<?php echo esc_attr( wp_json_encode( $radar_max ) ); ?>"
				></canvas>
			</div>

		</div>

		<!-- Detailed Scores -->
		<div class="wfeb-detail-scores">
			<h4><?php echo esc_html__( 'Detailed Scores', 'wfeb' ); ?></h4>
			<div class="wfeb-table-responsive">
				<table class="wfeb-table wfeb-table--compact">
					<tbody>
						<tr>
							<td><strong><?php echo esc_html__( 'Short Passing', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left:', 'wfeb' ); ?> <?php echo esc_html( $exam->short_passing_left ); ?>/5</td>
							<td><?php echo esc_html__( 'Right:', 'wfeb' ); ?> <?php echo esc_html( $exam->short_passing_right ); ?>/5</td>
							<td><strong><?php echo esc_html__( 'Total:', 'wfeb' ); ?> <?php echo esc_html( $exam->short_passing_total ); ?>/10</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Long Passing', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left:', 'wfeb' ); ?> <?php echo esc_html( $exam->long_passing_left ); ?>/5</td>
							<td><?php echo esc_html__( 'Right:', 'wfeb' ); ?> <?php echo esc_html( $exam->long_passing_right ); ?>/5</td>
							<td><strong><?php echo esc_html__( 'Total:', 'wfeb' ); ?> <?php echo esc_html( $exam->long_passing_total ); ?>/10</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Shooting', 'wfeb' ); ?></strong></td>
							<td colspan="2">
								<?php echo esc_html__( 'TL:', 'wfeb' ); ?> <?php echo esc_html( $exam->shooting_tl ); ?>,
								<?php echo esc_html__( 'TR:', 'wfeb' ); ?> <?php echo esc_html( $exam->shooting_tr ); ?>,
								<?php echo esc_html__( 'BL:', 'wfeb' ); ?> <?php echo esc_html( $exam->shooting_bl ); ?>,
								<?php echo esc_html__( 'BR:', 'wfeb' ); ?> <?php echo esc_html( $exam->shooting_br ); ?>
							</td>
							<td><strong><?php echo esc_html__( 'Total:', 'wfeb' ); ?> <?php echo esc_html( $exam->shooting_total ); ?>/20</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Sprinting', 'wfeb' ); ?></strong></td>
							<td colspan="2"><?php echo esc_html__( 'Time:', 'wfeb' ); ?> <?php echo esc_html( $exam->sprint_time ); ?>s</td>
							<td><strong><?php echo esc_html__( 'Score:', 'wfeb' ); ?> <?php echo esc_html( $exam->sprint_score ); ?>/10</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Dribbling', 'wfeb' ); ?></strong></td>
							<td colspan="2"><?php echo esc_html__( 'Time:', 'wfeb' ); ?> <?php echo esc_html( $exam->dribble_time ); ?>s</td>
							<td><strong><?php echo esc_html__( 'Score:', 'wfeb' ); ?> <?php echo esc_html( $exam->dribble_score ); ?>/10</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Kick Up Touch', 'wfeb' ); ?></strong></td>
							<td colspan="2">
								<?php echo esc_html__( 'Attempts:', 'wfeb' ); ?>
								<?php echo esc_html( $exam->kickups_attempt1 ); ?>,
								<?php echo esc_html( $exam->kickups_attempt2 ); ?>,
								<?php echo esc_html( $exam->kickups_attempt3 ); ?>
								(<?php echo esc_html__( 'Best:', 'wfeb' ); ?> <?php echo esc_html( $exam->kickups_best ); ?>)
							</td>
							<td><strong><?php echo esc_html__( 'Score:', 'wfeb' ); ?> <?php echo esc_html( $exam->kickups_score ); ?>/10</strong></td>
						</tr>
						<tr>
							<td><strong><?php echo esc_html__( 'Volley Touch', 'wfeb' ); ?></strong></td>
							<td><?php echo esc_html__( 'Left:', 'wfeb' ); ?> <?php echo esc_html( $exam->volley_left ); ?>/5</td>
							<td><?php echo esc_html__( 'Right:', 'wfeb' ); ?> <?php echo esc_html( $exam->volley_right ); ?>/5</td>
							<td><strong><?php echo esc_html__( 'Total:', 'wfeb' ); ?> <?php echo esc_html( $exam->volley_total ); ?>/10</strong></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php if ( $certificate && 'completed' === $exam->status ) : ?>
	<!-- Certificate Info -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="dashicons dashicons-awards"></span>
				<?php echo esc_html__( 'Certificate', 'wfeb' ); ?>
			</h3>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-detail-grid">
				<div class="wfeb-detail-item">
					<span class="wfeb-detail-label"><?php echo esc_html__( 'Certificate Number', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><strong><?php echo esc_html( $certificate->certificate_number ); ?></strong></span>
				</div>
				<div class="wfeb-detail-item">
					<span class="wfeb-detail-label"><?php echo esc_html__( 'Status', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $certificate->status ); ?>">
							<?php echo esc_html( ucfirst( $certificate->status ) ); ?>
						</span>
					</span>
				</div>
				<div class="wfeb-detail-item">
					<span class="wfeb-detail-label"><?php echo esc_html__( 'Issued', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $certificate->issued_at, 'j M Y, g:i A' ) ); ?></span>
				</div>
				<?php if ( ! empty( $certificate->pdf_url ) ) : ?>
					<div class="wfeb-detail-item">
						<span class="wfeb-detail-label"><?php echo esc_html__( 'Download', 'wfeb' ); ?></span>
						<span class="wfeb-detail-value">
							<a href="<?php echo esc_url( $certificate->pdf_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-download"></span>
								<?php echo esc_html__( 'Download Certificate', 'wfeb' ); ?>
							</a>
						</span>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $exam->notes ) ) : ?>
	<!-- Notes -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title"><?php echo esc_html__( 'Notes', 'wfeb' ); ?></h3>
		</div>
		<div class="wfeb-card-body">
			<p><?php echo esc_html( $exam->notes ); ?></p>
		</div>
	</div>
<?php endif; ?>

<!-- Back Button -->
<div class="wfeb-form-actions">
	<a href="<?php echo esc_url( add_query_arg( 'section', 'exam-history', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary">
		<span class="dashicons dashicons-arrow-left-alt"></span>
		<?php echo esc_html__( 'Back to Exam History', 'wfeb' ); ?>
	</a>
</div>
