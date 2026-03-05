<?php
/**
 * Template: Coach Dashboard - Player Details Section
 *
 * Displays detailed player information, stats, and exam history.
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

// Get player.
$player_id = isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$player    = null;

if ( $player_id ) {
	$player = WFEB()->player->get( $player_id );

	// Validate the player belongs to this coach.
	if ( $player && absint( $player->coach_id ) !== $coach_id ) {
		$player = null;
	}
}

if ( ! $player ) : ?>
	<div class="wfeb-card">
		<div class="wfeb-card-body">
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<p><?php echo esc_html__( 'Player not found or you do not have access to this player.', 'wfeb' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'my-players', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
					<?php echo esc_html__( 'Back to My Players', 'wfeb' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

// Player stats.
$stats = WFEB()->player->get_exam_stats( $player->id );

// Player exam history.
$exams = WFEB()->exam->get_by_coach(
	$coach_id,
	array(
		'search' => $player->full_name,
		'limit'  => 50,
	)
);

// Filter exams to only include this player's exams.
$player_exams = array_filter(
	$exams,
	function ( $exam ) use ( $player ) {
		return absint( $exam->player_id ) === absint( $player->id );
	}
);
?>

<?php
$edit_url    = add_query_arg( array( 'section' => 'add-player', 'player_id' => $player->id ), $base_url );
$exam_url    = add_query_arg( array( 'section' => 'conduct-exam', 'player_id' => $player->id ), $base_url );
$player_email   = ! empty( $player->email ) ? esc_html( $player->email ) : '<span class="wfeb-text-muted">' . esc_html__( 'Not provided', 'wfeb' ) . '</span>';
$player_phone   = ! empty( $player->phone ) ? esc_html( $player->phone ) : '<span class="wfeb-text-muted">' . esc_html__( 'Not provided', 'wfeb' ) . '</span>';
$player_address = ! empty( $player->address ) ? esc_html( $player->address ) : '<span class="wfeb-text-muted">' . esc_html__( 'Not provided', 'wfeb' ) . '</span>';
?>

<!-- Player Info Card -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Player Information', 'wfeb' ); ?></h3>
		<div class="wfeb-card-actions">
			<a href="<?php echo esc_url( $edit_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--secondary">
				<span class="dashicons dashicons-edit"></span>
				<?php echo esc_html__( 'Edit Player', 'wfeb' ); ?>
			</a>
			<a href="<?php echo esc_url( $exam_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary">
				<span class="dashicons dashicons-clipboard"></span>
				<?php echo esc_html__( 'Conduct Exam', 'wfeb' ); ?>
			</a>
		</div>
	</div>
	<div class="wfeb-card-body">
		<div class="wfeb-player-profile-header">
			<div class="wfeb-player-avatar">
				<?php echo WFEB_Media::get_image( $player->profile_picture ?? 0, 'thumbnail', 'avatar' ); ?>
			</div>
			<div class="wfeb-player-profile-name">
				<h2><?php echo esc_html( $player->full_name ); ?></h2>
				<?php if ( ! empty( $player->user_id ) ) : ?>
					<span class="wfeb-account-status wfeb-account-status--active wfeb-account-status--inline">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php echo esc_html__( 'Account Active', 'wfeb' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<div class="wfeb-detail-grid">
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Full Name', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( $player->full_name ); ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $player->dob ) ); ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Email', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo $player_email; ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Phone', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo $player_phone; ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Address', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo $player_address; ?></span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Login Account', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value">
					<?php if ( ! empty( $player->user_id ) ) : ?>
						<span class="wfeb-account-status wfeb-account-status--active wfeb-account-status--inline">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php echo esc_html__( 'Active', 'wfeb' ); ?>
						</span>
					<?php else : ?>
						<span class="wfeb-text-muted"><?php echo esc_html__( 'Not created', 'wfeb' ); ?></span>
					<?php endif; ?>
				</span>
			</div>
			<div class="wfeb-detail-item">
				<span class="wfeb-detail-label"><?php echo esc_html__( 'Registered', 'wfeb' ); ?></span>
				<span class="wfeb-detail-value"><?php echo esc_html( wfeb_format_date( $player->created_at, 'j M Y, g:i A' ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<!-- Stats Row -->
<div class="wfeb-stats-grid wfeb-stats-grid--3">
	<div class="wfeb-stat-card wfeb-stat-card--primary">
		<div class="wfeb-stat-icon">
			<span class="dashicons dashicons-clipboard"></span>
		</div>
		<div class="wfeb-stat-value"><?php echo esc_html( $stats->total_exams ); ?></div>
		<div class="wfeb-stat-label"><?php echo esc_html__( 'Total Exams', 'wfeb' ); ?></div>
	</div>
	<div class="wfeb-stat-card wfeb-stat-card--accent">
		<div class="wfeb-stat-icon">
			<span class="dashicons dashicons-star-filled"></span>
		</div>
		<div class="wfeb-stat-value">
			<?php echo $stats->best_score !== null ? esc_html( $stats->best_score . '/80' ) : '-'; ?>
		</div>
		<div class="wfeb-stat-label"><?php echo esc_html__( 'Best Score', 'wfeb' ); ?></div>
	</div>
	<div class="wfeb-stat-card wfeb-stat-card--gold">
		<div class="wfeb-stat-icon">
			<span class="dashicons dashicons-awards"></span>
		</div>
		<div class="wfeb-stat-value">
			<?php echo $stats->best_level ? esc_html( $stats->best_level ) : '-'; ?>
		</div>
		<div class="wfeb-stat-label"><?php echo esc_html__( 'Best Level', 'wfeb' ); ?></div>
	</div>
</div>

<?php
// Build chart data from completed exams (sorted by date ascending).
$completed_exams = array_filter(
	$player_exams,
	function ( $e ) {
		return 'completed' === $e->status;
	}
);

// Sort by exam_date ascending.
usort(
	$completed_exams,
	function ( $a, $b ) {
		return strtotime( $a->exam_date ) - strtotime( $b->exam_date );
	}
);

$chart_labels = array();
$chart_scores = array();
foreach ( $completed_exams as $e ) {
	$chart_labels[] = date_i18n( 'j M Y', strtotime( $e->exam_date ) );
	$chart_scores[] = (int) $e->total_score;
}
?>

<?php if ( count( $completed_exams ) >= 2 ) : ?>
<!-- Score Progress Chart -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Score Progress', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">
		<div class="wfeb-chart-wrap">
			<canvas
				id="wfeb-player-score-chart"
				data-labels="<?php echo esc_attr( wp_json_encode( $chart_labels ) ); ?>"
				data-scores="<?php echo esc_attr( wp_json_encode( $chart_scores ) ); ?>"
			></canvas>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Exam History -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Exam History', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body wfeb-p-0">
		<?php if ( ! empty( $player_exams ) ) : ?>
			<div class="wfeb-table-responsive">
				<table class="wfeb-table">
					<thead>
						<tr>
							<th class="wfeb-sortable" data-sort="date"><?php echo esc_html__( 'Date', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="score"><?php echo esc_html__( 'Score', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="level"><?php echo esc_html__( 'Level', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="status"><?php echo esc_html__( 'Status', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th><?php echo esc_html__( 'Actions', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $player_exams as $exam ) : ?>
							<tr>
								<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
								<td><?php echo esc_html( $exam->total_score . '/80' ); ?></td>
								<td>
									<span
										class="wfeb-badge wfeb-badge--level"
										style="background-color: <?php echo esc_attr( wfeb_get_level_color( $exam->achievement_level ) ); ?>;"
									>
										<?php echo esc_html( $exam->achievement_level ); ?>
									</span>
								</td>
								<td>
									<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
										<?php echo esc_html( ucfirst( $exam->status ) ); ?>
									</span>
								</td>
								<td>
									<div class="wfeb-actions">
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'exam-details', 'exam_id' => $exam->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--view"
											title="<?php echo esc_attr__( 'View Details', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-visibility"></span>
										</a>
										<?php if ( 'draft' === $exam->status ) : ?>
											<a
												href="<?php echo esc_url( add_query_arg( array( 'section' => 'conduct-exam', 'exam_id' => $exam->id ), $base_url ) ); ?>"
												class="wfeb-action-btn wfeb-action-btn--edit"
												title="<?php echo esc_attr__( 'Edit Exam', 'wfeb' ); ?>"
											>
												<span class="dashicons dashicons-edit"></span>
											</a>
											<button
												type="button"
												class="wfeb-action-btn wfeb-action-btn--delete wfeb-delete-exam"
												data-id="<?php echo esc_attr( $exam->id ); ?>"
												title="<?php echo esc_attr__( 'Delete Exam', 'wfeb' ); ?>"
											>
												<span class="dashicons dashicons-trash"></span>
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-clipboard"></span>
				<p><?php echo esc_html__( 'No exams for this player yet.', 'wfeb' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Back Button -->
<div class="wfeb-form-actions">
	<a href="<?php echo esc_url( add_query_arg( 'section', 'my-players', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary">
		<span class="dashicons dashicons-arrow-left-alt"></span>
		<?php echo esc_html__( 'Back to My Players', 'wfeb' ); ?>
	</a>
</div>
