<?php
/**
 * WFEB Admin Exam Detail Template
 *
 * Displays a single exam's full score breakdown with category bars,
 * player info, coach info, and certificate link.
 *
 * Variables available from WFEB_Admin_Exams::render_detail():
 * - $exam, $certificate
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the 7 categories with their max scores for bar display.
$categories = array(
	array(
		'label' => __( 'Short Passing', 'wfeb' ),
		'score' => absint( $exam->short_passing_total ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: 1: left score, 2: right score */
			__( 'Left: %1$d | Right: %2$d', 'wfeb' ),
			absint( $exam->short_passing_left ),
			absint( $exam->short_passing_right )
		),
	),
	array(
		'label' => __( 'Long Passing', 'wfeb' ),
		'score' => absint( $exam->long_passing_total ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: 1: left score, 2: right score */
			__( 'Left: %1$d | Right: %2$d', 'wfeb' ),
			absint( $exam->long_passing_left ),
			absint( $exam->long_passing_right )
		),
	),
	array(
		'label' => __( 'Shooting', 'wfeb' ),
		'score' => absint( $exam->shooting_total ),
		'max'   => 20,
		'detail' => sprintf(
			/* translators: 1: TL, 2: TR, 3: BL, 4: BR */
			__( 'TL: %1$d | TR: %2$d | BL: %3$d | BR: %4$d', 'wfeb' ),
			absint( $exam->shooting_tl ),
			absint( $exam->shooting_tr ),
			absint( $exam->shooting_bl ),
			absint( $exam->shooting_br )
		),
	),
	array(
		'label' => __( 'Sprint', 'wfeb' ),
		'score' => absint( $exam->sprint_score ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: %s: sprint time in seconds */
			__( 'Time: %ss', 'wfeb' ),
			esc_html( $exam->sprint_time )
		),
	),
	array(
		'label' => __( 'Dribble', 'wfeb' ),
		'score' => absint( $exam->dribble_score ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: %s: dribble time in seconds */
			__( 'Time: %ss', 'wfeb' ),
			esc_html( $exam->dribble_time )
		),
	),
	array(
		'label' => __( 'Kickups', 'wfeb' ),
		'score' => absint( $exam->kickups_score ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: 1: attempt 1, 2: attempt 2, 3: attempt 3, 4: best */
			__( 'Attempts: %1$d / %2$d / %3$d | Best: %4$d', 'wfeb' ),
			absint( $exam->kickups_attempt1 ),
			absint( $exam->kickups_attempt2 ),
			absint( $exam->kickups_attempt3 ),
			absint( $exam->kickups_best )
		),
	),
	array(
		'label' => __( 'Volley', 'wfeb' ),
		'score' => absint( $exam->volley_total ),
		'max'   => 10,
		'detail' => sprintf(
			/* translators: 1: left score, 2: right score */
			__( 'Left: %1$d | Right: %2$d', 'wfeb' ),
			absint( $exam->volley_left ),
			absint( $exam->volley_right )
		),
	),
);

$level_slug = sanitize_title( $exam->achievement_level );
?>
<div class="wfeb-wrap">

	<!-- Back Link -->
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-exams' ) ); ?>" class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm wfeb-back-link">
		&larr; <?php esc_html_e( 'Back to Exams', 'wfeb' ); ?>
	</a>

	<!-- Page Header -->
	<div class="wfeb-page-header">
		<h1 class="wfeb-page-title">
			<?php
			/* translators: %d: exam ID */
			printf( esc_html__( 'Exam #%d', 'wfeb' ), absint( $exam->id ) );
			?>
		</h1>
		<div class="wfeb-page-actions">
			<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
				<?php echo esc_html( ucfirst( $exam->status ) ); ?>
			</span>
		</div>
	</div>

	<!-- Two-column detail grid: info left, scores right -->
	<div class="wfeb-detail-grid">

		<!-- Left: Exam Information -->
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h2 class="wfeb-card-title"><?php esc_html_e( 'Exam Information', 'wfeb' ); ?></h2>
			</div>
			<div class="wfeb-card-body">

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Exam Date', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></span>
				</div>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Player', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $exam->player_name ); ?></span>
				</div>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Coach', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wfeb-coaches&coach_id=' . absint( $exam->coach_id ) ) ); ?>">
							<?php echo esc_html( $exam->coach_name ); ?>
						</a>
					</span>
				</div>

				<?php if ( ! empty( $exam->assistant_examiner ) ) : ?>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="Second examiner who co-validated this exam session"><?php esc_html_e( 'Assistant Examiner', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $exam->assistant_examiner ); ?></span>
				</div>
				<?php endif; ?>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Total Score', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<strong><?php echo esc_html( $exam->total_score ); ?>/80</strong>
					</span>
				</div>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="Grade based on total score. See Settings for scoring thresholds"><?php esc_html_e( 'Achievement Level', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $level_slug ); ?>">
							<?php echo esc_html( $exam->achievement_level ); ?>
						</span>
					</span>
				</div>

				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label" data-tooltip="Descriptive label for the achievement level"><?php esc_html_e( 'Playing Level', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $exam->playing_level ); ?></span>
				</div>

				<?php if ( $certificate ) : ?>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Certificate', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value">
						<?php echo esc_html( $certificate->certificate_number ); ?>
						<?php if ( ! empty( $certificate->pdf_url ) ) : ?>
							&mdash;
							<a href="<?php echo esc_url( $certificate->pdf_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'View PDF', 'wfeb' ); ?>
							</a>
						<?php endif; ?>
					</span>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $exam->notes ) ) : ?>
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label"><?php esc_html_e( 'Notes', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value"><?php echo esc_html( $exam->notes ); ?></span>
				</div>
				<?php endif; ?>

			</div><!-- .wfeb-card-body -->
		</div><!-- .wfeb-card -->

		<!-- Right: Score Breakdown -->
		<div class="wfeb-card">
			<div class="wfeb-card-header">
				<h2 class="wfeb-card-title"><?php esc_html_e( 'Score Breakdown', 'wfeb' ); ?></h2>
			</div>
			<div class="wfeb-card-body">

				<div class="wfeb-stats-grid">
					<?php foreach ( $categories as $cat ) : ?>
					<div class="wfeb-stat-card">
						<span class="wfeb-stat-label"><?php echo esc_html( $cat['label'] ); ?></span>
						<span class="wfeb-stat-number">
							<?php echo esc_html( $cat['score'] ); ?><span class="wfeb-text-muted"> / <?php echo esc_html( $cat['max'] ); ?></span>
						</span>
						<span class="wfeb-stat-footer"><?php echo esc_html( $cat['detail'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>

				<!-- Total summary row -->
				<div class="wfeb-detail-row">
					<span class="wfeb-detail-label wfeb-font-bold"><?php esc_html_e( 'Total', 'wfeb' ); ?></span>
					<span class="wfeb-detail-value wfeb-font-bold">
						<?php echo esc_html( $exam->total_score ); ?>/80
						&mdash;
						<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $level_slug ); ?>">
							<?php echo esc_html( $exam->achievement_level ); ?>
						</span>
					</span>
				</div>

			</div><!-- .wfeb-card-body -->
		</div><!-- .wfeb-card -->

	</div><!-- .wfeb-detail-grid -->

</div><!-- .wfeb-wrap -->
