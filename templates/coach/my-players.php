<?php
/**
 * Template: Coach Dashboard - My Players Section
 *
 * Displays the coach's player list with search, table, and actions.
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

// Fetch players.
$players = WFEB()->player->get_by_coach(
	$coach_id,
	array(
		'limit'   => 100,
		'orderby' => 'full_name',
		'order'   => 'ASC',
	)
);
?>

<!-- Header Row -->
<div class="wfeb-section-header">
	<h2 class="wfeb-section-title"><?php echo esc_html__( 'My Players', 'wfeb' ); ?></h2>
	<a href="<?php echo esc_url( add_query_arg( 'section', 'add-player', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
		<span class="dashicons dashicons-plus-alt"></span>
		<?php echo esc_html__( 'Add New Player', 'wfeb' ); ?>
	</a>
</div>

<!-- Players Table -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<div class="wfeb-search-bar">
			<svg class="wfeb-search-bar-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			<input
				type="text"
				id="wfeb-player-search"
				class="wfeb-search-input"
				placeholder="<?php echo esc_attr__( 'Search players by name...', 'wfeb' ); ?>"
			>
		</div>
	</div>
	<div class="wfeb-card-body wfeb-p-0">
		<?php if ( ! empty( $players ) ) : ?>
			<div class="wfeb-table-responsive">
				<table class="wfeb-table" id="wfeb-players-table">
					<thead>
						<tr>
							<th class="wfeb-sortable" data-sort="name"><?php echo esc_html__( 'Name', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="dob"><?php echo esc_html__( 'DOB', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="exams"><?php echo esc_html__( 'Exams', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="score"><?php echo esc_html__( 'Best Score', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="level"><?php echo esc_html__( 'Best Level', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th class="wfeb-sortable" data-sort="last-exam"><?php echo esc_html__( 'Last Exam', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
							<th><?php echo esc_html__( 'Actions', 'wfeb' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $players as $player ) : ?>
							<?php $stats = WFEB()->player->get_exam_stats( $player->id ); ?>
							<tr data-player-name="<?php echo esc_attr( strtolower( $player->full_name ) ); ?>">
								<td>
									<div class="wfeb-player-name-cell">
										<div class="wfeb-player-list-avatar">
											<?php echo WFEB_Media::get_image( $player->profile_picture ?? 0, 'thumbnail', 'avatar' ); ?>
										</div>
										<strong><?php echo esc_html( $player->full_name ); ?></strong>
									</div>
								</td>
								<td><?php echo esc_html( wfeb_format_date( $player->dob ) ); ?></td>
								<td><?php echo esc_html( $stats->total_exams ); ?></td>
								<td>
									<?php
									if ( $stats->best_score !== null ) {
										echo esc_html( $stats->best_score . '/80' );
									} else {
										echo '<span class="wfeb-text-muted">-</span>';
									}
									?>
								</td>
								<td>
									<?php if ( $stats->best_level ) : ?>
										<span
											class="wfeb-badge wfeb-badge--level"
											style="background-color: <?php echo esc_attr( wfeb_get_level_color( $stats->best_level ) ); ?>;"
										>
											<?php echo esc_html( $stats->best_level ); ?>
										</span>
									<?php else : ?>
										<span class="wfeb-text-muted">-</span>
									<?php endif; ?>
								</td>
								<td>
									<?php
									if ( $stats->last_exam_date ) {
										echo esc_html( wfeb_format_date( $stats->last_exam_date ) );
									} else {
										echo '<span class="wfeb-text-muted">' . esc_html__( 'Never', 'wfeb' ) . '</span>';
									}
									?>
								</td>
								<td>
									<div class="wfeb-actions">
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'player-details', 'player_id' => $player->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--view"
											title="<?php echo esc_attr__( 'View Player', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-visibility"></span>
										</a>
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'add-player', 'player_id' => $player->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--edit"
											title="<?php echo esc_attr__( 'Edit Player', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-edit"></span>
										</a>
										<a
											href="<?php echo esc_url( add_query_arg( array( 'section' => 'conduct-exam', 'player_id' => $player->id ), $base_url ) ); ?>"
											class="wfeb-action-btn wfeb-action-btn--exam"
											title="<?php echo esc_attr__( 'Conduct Exam', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-clipboard"></span>
										</a>
										<button
											type="button"
											class="wfeb-action-btn wfeb-action-btn--delete wfeb-delete-player"
											data-id="<?php echo esc_attr( $player->id ); ?>"
											data-name="<?php echo esc_attr( $player->full_name ); ?>"
											title="<?php echo esc_attr__( 'Delete Player', 'wfeb' ); ?>"
										>
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="wfeb-empty-state">
				<span class="dashicons dashicons-groups"></span>
				<p><?php echo esc_html__( 'No players yet. Add your first player to get started.', 'wfeb' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'add-player', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
					<?php echo esc_html__( 'Add Player', 'wfeb' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>
