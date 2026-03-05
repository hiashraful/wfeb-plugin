<?php
/**
 * Template: Player Dashboard - Certificate Detail Section
 *
 * Displays a single certificate with large achievement badge,
 * score display, full 7-category breakdown with visual bars,
 * and a prominent download PDF button.
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

// Get certificate ID from query string and validate ownership.
$cert_id     = isset( $_GET['cert_id'] ) ? absint( $_GET['cert_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$certificate = $cert_id ? WFEB()->certificate->get( $cert_id ) : null;

// Validate the certificate belongs to this player.
if ( ! $certificate || absint( $certificate->player_id ) !== absint( $player_id ) ) :
?>
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<div class="wfeb-empty-state-icon">
					<span class="dashicons dashicons-warning"></span>
				</div>
				<p class="wfeb-empty-state-title"><?php echo esc_html__( 'Certificate Not Found', 'wfeb' ); ?></p>
				<p class="wfeb-empty-state-text"><?php echo esc_html__( 'The certificate you are looking for does not exist or does not belong to your account.', 'wfeb' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'certificates', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
					<?php echo esc_html__( 'Back to Certificates', 'wfeb' ); ?>
				</a>
			</div>
		</div>
	</div>
<?php
	return;
endif;

// Get the linked exam for score breakdown.
$exam = $certificate->exam_id ? WFEB()->exam->get( $certificate->exam_id ) : null;

// Score categories with max values.
$categories = array(
	array(
		'label' => __( 'Short Passing', 'wfeb' ),
		'score' => $exam ? absint( $exam->short_passing_total ) : 0,
		'max'   => 10,
	),
	array(
		'label' => __( 'Long Passing', 'wfeb' ),
		'score' => $exam ? absint( $exam->long_passing_total ) : 0,
		'max'   => 10,
	),
	array(
		'label' => __( 'Shooting', 'wfeb' ),
		'score' => $exam ? absint( $exam->shooting_total ) : 0,
		'max'   => 20,
	),
	array(
		'label' => __( 'Sprinting', 'wfeb' ),
		'score' => $exam ? absint( $exam->sprint_score ) : 0,
		'max'   => 10,
	),
	array(
		'label' => __( 'Dribbling', 'wfeb' ),
		'score' => $exam ? absint( $exam->dribble_score ) : 0,
		'max'   => 10,
	),
	array(
		'label' => __( 'Kick Ups', 'wfeb' ),
		'score' => $exam ? absint( $exam->kickups_score ) : 0,
		'max'   => 10,
	),
	array(
		'label' => __( 'Volley', 'wfeb' ),
		'score' => $exam ? absint( $exam->volley_total ) : 0,
		'max'   => 10,
	),
);

$level_lower = sanitize_title( $certificate->achievement_level );
?>

<!-- Back Link -->
<div class="wfeb-mb-24">
	<a href="<?php echo esc_url( add_query_arg( 'section', 'certificates', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--ghost">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
		<?php echo esc_html__( 'Back to Certificates', 'wfeb' ); ?>
	</a>
</div>

<div class="wfeb-cert-detail">

	<!-- Header with Level Badge -->
	<div class="wfeb-cert-detail-header">
		<div class="wfeb-cert-detail-badge">
			<span
				class="wfeb-badge wfeb-badge--lg"
				style="background-color: <?php echo esc_attr( wfeb_get_level_color( $certificate->achievement_level ) ); ?>; color: #fff; font-size: 18px; padding: 10px 24px;"
			>
				<?php echo esc_html( $certificate->achievement_level ); ?>
			</span>
		</div>
		<div class="wfeb-cert-detail-title"><?php echo esc_html__( 'Achievement Certificate', 'wfeb' ); ?></div>
		<div class="wfeb-cert-detail-number"><?php echo esc_html( $certificate->certificate_number ); ?></div>
	</div>

	<!-- Certificate Info -->
	<div style="background-color: var(--wfeb-card-bg); border-left: 1px solid var(--wfeb-border); border-right: 1px solid var(--wfeb-border); padding: 24px;">
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; text-align: center;">
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Certificate Number', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 700;"><?php echo esc_html( $certificate->certificate_number ); ?></div>
			</div>
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Date Issued', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 700;"><?php echo esc_html( wfeb_format_date( $certificate->issued_at ) ); ?></div>
			</div>
			<div>
				<div class="wfeb-text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php echo esc_html__( 'Examiner', 'wfeb' ); ?>
				</div>
				<div style="font-weight: 700;"><?php echo esc_html( $certificate->coach_name ); ?></div>
			</div>
		</div>
	</div>

	<!-- Score Display -->
	<div class="wfeb-cert-detail-score">
		<div class="wfeb-score-circle">
			<?php
			$score_percent = $certificate->total_score > 0
				? ( absint( $certificate->total_score ) / 80 ) * 100
				: 0;
			// SVG circle: circumference = 2 * PI * 65 = ~408.4
			$circumference = 408.4;
			$dash_offset   = $circumference - ( $circumference * $score_percent / 100 );
			?>
			<svg viewBox="0 0 160 160">
				<circle class="wfeb-score-circle-bg" cx="80" cy="80" r="65"></circle>
				<circle
					class="wfeb-score-circle-fill wfeb-score-circle-fill--<?php echo esc_attr( $level_lower ); ?>"
					cx="80" cy="80" r="65"
					stroke-dasharray="<?php echo esc_attr( $circumference ); ?>"
					stroke-dashoffset="<?php echo esc_attr( $dash_offset ); ?>"
				></circle>
			</svg>
			<div class="wfeb-score-circle-text">
				<span class="wfeb-score-circle-value"><?php echo esc_html( $certificate->total_score ); ?></span>
				<span class="wfeb-score-circle-label"><?php echo esc_html__( 'out of 80', 'wfeb' ); ?></span>
			</div>
		</div>
	</div>

	<!-- 7-Category Breakdown -->
	<?php if ( $exam ) : ?>
		<div class="wfeb-cert-detail-breakdown" style="padding: 24px;">
			<h4 style="margin-bottom: 20px; font-size: 15px; font-weight: 700; color: var(--wfeb-text);">
				<?php echo esc_html__( 'Score Breakdown', 'wfeb' ); ?>
			</h4>
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
	<?php endif; ?>

	<!-- Download PDF Button -->
	<div class="wfeb-cert-detail-actions">
		<?php if ( ! empty( $certificate->pdf_url ) ) : ?>
			<a href="<?php echo esc_url( $certificate->pdf_url ); ?>" class="wfeb-btn wfeb-btn--download wfeb-btn--lg" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-download"></span>
				<?php echo esc_html__( 'Download Certificate PDF', 'wfeb' ); ?>
			</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( add_query_arg( 'section', 'certificates', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary">
			<?php echo esc_html__( 'Back to Certificates', 'wfeb' ); ?>
		</a>
	</div>

</div>
