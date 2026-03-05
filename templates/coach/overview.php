<?php
/**
 * Template: Coach Dashboard - Overview Section
 *
 * Professional sports dashboard layout with upcoming exam banner,
 * exam stats donut chart, top player spotlight, recent exams table,
 * and activity sidebar.
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

// Fetch stats.
$credits       = $coach ? absint( $coach->credits_balance ) : 0;
$total_exams   = WFEB()->exam->get_count( $coach_id );
$total_players = WFEB()->player->get_count( $coach_id );

// This month's exams.
$month_start   = gmdate( 'Y-m-01' );
$month_end     = gmdate( 'Y-m-t' );
$monthly_exams = WFEB()->exam->get_by_coach(
	$coach_id,
	array(
		'date_from' => $month_start,
		'date_to'   => $month_end,
		'limit'     => 1000,
	)
);
$monthly_exam_count = count( $monthly_exams );

// Recent exams.
$recent_exams = WFEB()->exam->get_recent( $coach_id, 5 );

// Dashboard base URL.
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$base_url          = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();

// Compute exam stats for donut chart — breakdown by achievement level.
$level_counts    = array();
$completed_count = 0;
$all_exams       = array();
if ( ! empty( $recent_exams ) || $total_exams > 0 ) {
	$all_exams = WFEB()->exam->get_by_coach( $coach_id, array( 'limit' => 10000 ) );
	foreach ( $all_exams as $ex ) {
		if ( 'completed' === $ex->status ) {
			++$completed_count;
			$lvl = strtoupper( $ex->achievement_level );
			if ( ! isset( $level_counts[ $lvl ] ) ) {
				$level_counts[ $lvl ] = 0;
			}
			++$level_counts[ $lvl ];
		}
	}
}

// Build ordered arrays for the donut chart data attributes.
$level_order  = array( 'MASTERY', 'DIAMOND', 'GOLD', 'SILVER', 'BRONZE', 'MERIT+', 'MERIT', 'MERIT-', 'PASS+', 'PASS', 'UNCLASSIFIED' );
$donut_labels = array();
$donut_values = array();
$donut_colors = array();
foreach ( $level_order as $lvl ) {
	if ( ! empty( $level_counts[ $lvl ] ) ) {
		$donut_labels[] = $lvl;
		$donut_values[] = $level_counts[ $lvl ];
		$donut_colors[] = wfeb_get_level_color( $lvl );
	}
}

// Top 3 scorers this month.
$top_players = array();
if ( ! empty( $monthly_exams ) ) {
	// Collect best score per player from completed exams.
	$player_best = array();
	foreach ( $monthly_exams as $mexam ) {
		if ( 'completed' !== $mexam->status ) {
			continue;
		}
		$pid   = absint( $mexam->player_id );
		$score = (int) $mexam->total_score;
		if ( ! isset( $player_best[ $pid ] ) || $score > $player_best[ $pid ]['score'] ) {
			$player_best[ $pid ] = array(
				'player_id' => $pid,
				'name'      => $mexam->player_name,
				'score'     => $score,
				'level'     => $mexam->achievement_level,
			);
		}
	}

	// Sort by score descending and take top 3.
	usort( $player_best, function ( $a, $b ) {
		return $b['score'] - $a['score'];
	} );
	$top_players = array_slice( $player_best, 0, 3 );

	// Fetch profile pictures for top players.
	foreach ( $top_players as &$tp ) {
		$player_obj = WFEB()->player->get( $tp['player_id'] );
		$tp['profile_picture'] = $player_obj ? ( $player_obj->profile_picture ?? 0 ) : 0;
	}
	unset( $tp );
}

// Recent activity from exams.
$activity_exams = WFEB()->exam->get_recent( $coach_id, 4 );
?>

<!-- Skeleton wrapper - hidden when content loads -->
<div id="wfeb-overview-skeleton" class="wfeb-skeleton">

	<!-- Stats Grid -->
	<div class="wfeb-stats-grid">
		<?php for ( $i = 0; $i < 4; $i++ ) : ?>
			<div class="wfeb-skeleton-card">
				<div class="wfeb-skeleton-stat">
					<div class="wfeb-skeleton-bone" style="height: 32px; width: 60px;"></div>
					<div class="wfeb-skeleton-bone" style="height: 14px; width: 100px; margin-top: 8px;"></div>
				</div>
			</div>
		<?php endfor; ?>
	</div>

	<!-- Overview Layout (main + aside) -->
	<div class="wfeb-overview-layout" style="margin-top: 24px;">

		<!-- Main Column -->
		<div class="wfeb-overview-main">

			<!-- Quick Actions Banner -->
			<div class="wfeb-skeleton-card" style="padding: 24px 28px;">
				<div class="wfeb-skeleton-bone" style="height: 12px; width: 100px;"></div>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
					<div>
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 140px;"></div>
						<div class="wfeb-skeleton-bone" style="height: 20px; width: 220px; margin-top: 8px;"></div>
					</div>
					<div style="display: flex; gap: 12px;">
						<div class="wfeb-skeleton-bone" style="height: 38px; width: 120px; border-radius: 8px;"></div>
						<div class="wfeb-skeleton-bone" style="height: 38px; width: 100px; border-radius: 8px;"></div>
						<div class="wfeb-skeleton-bone" style="height: 38px; width: 100px; border-radius: 8px;"></div>
					</div>
				</div>
			</div>

			<!-- Exam Overview + Top Player -->
			<div class="wfeb-overview-row">
				<div class="wfeb-skeleton-card" style="min-height: 240px;">
					<div class="wfeb-skeleton-bone" style="height: 16px; width: 120px; margin-bottom: 24px;"></div>
					<div class="wfeb-skeleton-bone" style="height: 140px; width: 140px; border-radius: 50%; margin: 0 auto;"></div>
				</div>
				<div class="wfeb-skeleton-card" style="min-height: 240px;">
					<div class="wfeb-skeleton-bone" style="height: 16px; width: 100px; margin-bottom: 24px;"></div>
					<div class="wfeb-skeleton-bone" style="height: 64px; width: 64px; border-radius: 50%; margin: 0 auto;"></div>
					<div class="wfeb-skeleton-bone" style="height: 14px; width: 50%; margin: 12px auto 0;"></div>
				</div>
			</div>

			<!-- Recent Exams Table -->
			<div class="wfeb-skeleton-card" style="min-height: 200px;">
				<div class="wfeb-skeleton-bone" style="height: 16px; width: 130px; margin-bottom: 20px;"></div>
				<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<div style="display: flex; gap: 24px; margin-bottom: 16px;">
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 25%;"></div>
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 20%;"></div>
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 15%;"></div>
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 15%;"></div>
						<div class="wfeb-skeleton-bone" style="height: 14px; width: 15%;"></div>
					</div>
				<?php endfor; ?>
			</div>

		</div>

		<!-- Aside Column -->
		<div class="wfeb-overview-aside">
			<div class="wfeb-skeleton-card" style="min-height: 120px;">
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 120px; margin-bottom: 16px;"></div>
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 90%;"></div>
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 70%; margin-top: 8px;"></div>
			</div>
			<div class="wfeb-skeleton-card" style="min-height: 100px;">
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 100px; margin-bottom: 16px;"></div>
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 80%;"></div>
				<div class="wfeb-skeleton-bone" style="height: 14px; width: 60%; margin-top: 8px;"></div>
			</div>
		</div>

	</div>
</div>

<!-- Main content - shown after load -->
<div id="wfeb-overview-content" style="display: none;">

	<!-- Stats Grid -->
	<div class="wfeb-stats-grid">

		<div class="wfeb-stat-card wfeb-stat-card--accent">
			<div class="wfeb-stat-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
			</div>
			<div class="wfeb-stat-value"><?php echo esc_html( $credits ); ?></div>
			<div class="wfeb-stat-label"><?php echo esc_html__( 'Credits Remaining', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--primary">
			<div class="wfeb-stat-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 12h4"/><path d="M10 16h4"/></svg>
			</div>
			<div class="wfeb-stat-value"><?php echo esc_html( $total_exams ); ?></div>
			<div class="wfeb-stat-label"><?php echo esc_html__( 'Total Exams', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--info">
			<div class="wfeb-stat-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
			</div>
			<div class="wfeb-stat-value"><?php echo esc_html( $total_players ); ?></div>
			<div class="wfeb-stat-label"><?php echo esc_html__( 'Players Managed', 'wfeb' ); ?></div>
		</div>

		<div class="wfeb-stat-card wfeb-stat-card--gold">
			<div class="wfeb-stat-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
			</div>
			<div class="wfeb-stat-value"><?php echo esc_html( $monthly_exam_count ); ?></div>
			<div class="wfeb-stat-label"><?php echo esc_html__( "This Month's Exams", 'wfeb' ); ?></div>
		</div>

	</div>

	<?php if ( $credits < 5 ) : ?>
		<!-- Low Credit Warning -->
		<div class="wfeb-alert wfeb-alert--warning">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
			<div class="wfeb-alert-content">
				<div class="wfeb-alert-title"><?php echo esc_html__( 'Low Credit Warning', 'wfeb' ); ?></div>
				<p>
					<?php
					printf(
						/* translators: %d: number of credits remaining */
						esc_html__( 'You have only %d credit(s) remaining. Purchase more credits to continue conducting exams.', 'wfeb' ),
						$credits
					);
					?>
				</p>
			</div>
		</div>
	<?php endif; ?>

	<!-- Main Dashboard Layout -->
	<div class="wfeb-overview-layout">

		<!-- Left / Center Column -->
		<div class="wfeb-overview-main">

			<!-- Quick Actions Hero -->
			<div class="wfeb-qa-hero">
				<div class="wfeb-qa-hero-content">
					<div class="wfeb-qa-hero-greeting">
						<?php
						$hour = (int) gmdate( 'G' );
						if ( $hour < 12 ) {
							$greeting = __( 'Good morning', 'wfeb' );
						} elseif ( $hour < 18 ) {
							$greeting = __( 'Good afternoon', 'wfeb' );
						} else {
							$greeting = __( 'Good evening', 'wfeb' );
						}
						echo esc_html( $greeting . ', ' . $coach->full_name );
						?>
					</div>
					<div class="wfeb-qa-hero-subtitle"><?php echo esc_html( gmdate( 'l, d M Y' ) ); ?></div>
					<div class="wfeb-qa-hero-stats">
						<div class="wfeb-qa-hero-stat">
							<span class="wfeb-qa-hero-stat-value"><?php echo esc_html( $total_exams ); ?></span>
							<span class="wfeb-qa-hero-stat-label"><?php echo esc_html__( 'Exams', 'wfeb' ); ?></span>
						</div>
						<div class="wfeb-qa-hero-stat">
							<span class="wfeb-qa-hero-stat-value"><?php echo esc_html( $total_players ); ?></span>
							<span class="wfeb-qa-hero-stat-label"><?php echo esc_html__( 'Players', 'wfeb' ); ?></span>
						</div>
						<div class="wfeb-qa-hero-stat">
							<span class="wfeb-qa-hero-stat-value"><?php echo esc_html( $credits ); ?></span>
							<span class="wfeb-qa-hero-stat-label"><?php echo esc_html__( 'Credits', 'wfeb' ); ?></span>
						</div>
					</div>
				</div>
				<div class="wfeb-qa-hero-actions">
					<a href="<?php echo esc_url( add_query_arg( 'section', 'conduct-exam', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--lg wfeb-qa-hero-btn">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15l2 2 4-4"/></svg>
						<?php echo esc_html__( 'Conduct Exam', 'wfeb' ); ?>
					</a>
					<div class="wfeb-qa-hero-actions-row">
						<a href="<?php echo esc_url( add_query_arg( 'section', 'credits', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--outline wfeb-qa-hero-btn-secondary">
							<?php echo esc_html__( 'Buy Credits', 'wfeb' ); ?>
						</a>
						<a href="<?php echo esc_url( add_query_arg( 'section', 'add-player', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--outline wfeb-qa-hero-btn-secondary">
							<?php echo esc_html__( 'Add Player', 'wfeb' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Two-column row: Exam Overview + Top Player -->
			<div class="wfeb-overview-row">

				<!-- Exam Overview Donut Chart (by Achievement Level) -->
				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h3 class="wfeb-card-title"><?php echo esc_html__( 'EXAM OVERVIEW', 'wfeb' ); ?></h3>
					</div>
					<div class="wfeb-card-body">
						<?php if ( $completed_count > 0 ) : ?>
							<div class="wfeb-donut-container">
								<canvas id="wfeb-overview-donut"
									data-labels="<?php echo esc_attr( wp_json_encode( $donut_labels ) ); ?>"
									data-values="<?php echo esc_attr( wp_json_encode( $donut_values ) ); ?>"
									data-colors="<?php echo esc_attr( wp_json_encode( $donut_colors ) ); ?>"
									data-total="<?php echo esc_attr( $completed_count ); ?>"
									width="180" height="180"></canvas>
							</div>
							<div class="wfeb-donut-legend wfeb-donut-legend--levels">
								<?php foreach ( $donut_labels as $idx => $label ) : ?>
									<div class="wfeb-donut-legend-item">
										<span class="wfeb-donut-legend-dot" style="background: <?php echo esc_attr( $donut_colors[ $idx ] ); ?>;"></span>
										<?php echo esc_html( $label ); ?>
										<span class="wfeb-donut-legend-value"><?php echo esc_html( $donut_values[ $idx ] ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="wfeb-empty" style="padding: 32px 16px;">
								<p class="wfeb-text-muted"><?php echo esc_html__( 'No completed exams yet', 'wfeb' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Top 3 Players This Month -->
				<div class="wfeb-card">
					<div class="wfeb-card-header">
						<h3 class="wfeb-card-title"><?php echo esc_html__( 'TOP PLAYERS', 'wfeb' ); ?></h3>
						<span class="wfeb-text-muted" style="font-size: 12px;"><?php echo esc_html( gmdate( 'F' ) ); ?></span>
					</div>
					<div class="wfeb-card-body">
						<?php if ( ! empty( $top_players ) ) : ?>
							<?php
							// Reorder for podium: [1] 2nd, [0] 1st, [2] 3rd.
							$podium = array();
							if ( isset( $top_players[1] ) ) {
								$podium[] = array( 'data' => $top_players[1], 'rank' => 2 );
							}
							$podium[] = array( 'data' => $top_players[0], 'rank' => 1 );
							if ( isset( $top_players[2] ) ) {
								$podium[] = array( 'data' => $top_players[2], 'rank' => 3 );
							}
							?>
							<div class="wfeb-podium">
								<?php foreach ( $podium as $p ) :
									$tp   = $p['data'];
									$rank = $p['rank'];
									$cls  = 'wfeb-podium-player wfeb-podium-player--rank-' . $rank;
								?>
									<div class="<?php echo esc_attr( $cls ); ?>">
										<div class="wfeb-podium-avatar-wrap">
											<?php if ( 1 === $rank ) : ?>
												<svg class="wfeb-podium-trophy" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M6 2h12v6a6 6 0 0 1-12 0V2z" fill="#FFD700"/>
													<path d="M2 4h4v2a4 4 0 0 1-4 0V4z" fill="#FFD700" opacity="0.7"/>
													<path d="M18 4h4v2a4 4 0 0 0 4 0V4z" fill="#FFD700" opacity="0.7"/>
													<path d="M18 4h4v2c0 1.1-.9 2-2 2h0c-1.1 0-2-.9-2-2V4z" fill="#FFD700" opacity="0.7"/>
													<rect x="10" y="14" width="4" height="4" rx="1" fill="#FFD700"/>
													<rect x="8" y="18" width="8" height="2" rx="1" fill="#DAA520"/>
												</svg>
											<?php endif; ?>
											<div class="wfeb-podium-avatar">
												<?php echo WFEB_Media::get_image( $tp['profile_picture'], 'thumbnail', 'avatar' ); ?>
											</div>
											<div class="wfeb-podium-rank"><?php echo esc_html( $rank ); ?></div>
										</div>
										<div class="wfeb-podium-info">
											<a class="wfeb-podium-name" href="<?php echo esc_url( add_query_arg( array( 'section' => 'player-details', 'player_id' => $tp['player_id'] ), $base_url ) ); ?>"><?php echo esc_html( $tp['name'] ); ?></a>
											<div class="wfeb-podium-score"><?php echo esc_html( $tp['score'] . '/80' ); ?></div>
											<span
												class="wfeb-badge wfeb-badge--level"
												style="background-color: <?php echo esc_attr( wfeb_get_level_color( $tp['level'] ) ); ?>; font-size: 10px; padding: 2px 8px;"
											>
												<?php echo esc_html( $tp['level'] ); ?>
											</span>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="wfeb-empty" style="padding: 32px 16px;">
								<p class="wfeb-text-muted"><?php echo esc_html__( 'No completed exams this month', 'wfeb' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

			</div>

			<!-- Recent Exams Table -->
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h3 class="wfeb-card-title"><?php echo esc_html__( 'RECENT EXAMS', 'wfeb' ); ?></h3>
					<a href="<?php echo esc_url( add_query_arg( 'section', 'exam-history', $base_url ) ); ?>" class="wfeb-section-link">
						<?php echo esc_html__( 'See all', 'wfeb' ); ?>
					</a>
				</div>
				<div class="wfeb-card-body wfeb-p-0">
					<?php if ( ! empty( $recent_exams ) ) : ?>
						<div class="wfeb-table-wrap">
							<table class="wfeb-table">
								<thead>
									<tr>
										<th class="wfeb-sortable" data-sort="player"><?php echo esc_html__( 'Player', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
										<th class="wfeb-sortable" data-sort="date"><?php echo esc_html__( 'Date', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
										<th class="wfeb-sortable" data-sort="score"><?php echo esc_html__( 'Score', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
										<th class="wfeb-sortable" data-sort="level"><?php echo esc_html__( 'Level', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
										<th class="wfeb-sortable" data-sort="status"><?php echo esc_html__( 'Status', 'wfeb' ); ?><span class="wfeb-sort-icons"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></span></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $recent_exams as $idx => $exam ) : ?>
										<tr style="animation-delay: <?php echo esc_attr( $idx * 0.03 ); ?>s">
											<td>
												<div style="display: flex; align-items: center; gap: 10px;">
													<div class="wfeb-avatar wfeb-avatar--sm">
														<?php echo esc_html( strtoupper( substr( $exam->player_name, 0, 1 ) ) ); ?>
													</div>
													<?php echo esc_html( $exam->player_name ); ?>
												</div>
											</td>
											<td><?php echo esc_html( wfeb_format_date( $exam->exam_date ) ); ?></td>
											<td><strong><?php echo esc_html( $exam->total_score ); ?></strong><span class="wfeb-text-muted">/80</span></td>
											<td>
												<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( strtolower( str_replace( array( '+', '-' ), array( '-plus', '-minus' ), $exam->achievement_level ) ) ); ?>">
													<?php echo esc_html( $exam->achievement_level ); ?>
												</span>
											</td>
											<td>
												<span class="wfeb-badge wfeb-badge--<?php echo esc_attr( $exam->status ); ?>">
													<?php echo esc_html( ucfirst( $exam->status ) ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="wfeb-empty">
							<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--wfeb-text-light); margin-bottom: 12px;"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
							<p class="wfeb-empty-title"><?php echo esc_html__( 'No exams yet', 'wfeb' ); ?></p>
							<p class="wfeb-empty-text"><?php echo esc_html__( 'Conduct your first exam to get started.', 'wfeb' ); ?></p>
							<a href="<?php echo esc_url( add_query_arg( 'section', 'conduct-exam', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--primary">
								<?php echo esc_html__( 'Conduct Exam', 'wfeb' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<!-- Right Sidebar Column -->
		<div class="wfeb-overview-aside">

			<!-- Recent Activity -->
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h3 class="wfeb-card-title"><?php echo esc_html__( 'RECENT ACTIVITY', 'wfeb' ); ?></h3>
				</div>
				<div class="wfeb-card-body">
					<?php if ( ! empty( $activity_exams ) ) : ?>
						<?php foreach ( $activity_exams as $act ) : ?>
							<div class="wfeb-activity-item">
								<div class="wfeb-activity-icon">
									<?php if ( 'completed' === $act->status ) : ?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
									<?php else : ?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
									<?php endif; ?>
								</div>
								<div class="wfeb-activity-content">
									<div class="wfeb-activity-text">
										<?php
										if ( 'completed' === $act->status ) {
											printf(
												/* translators: %s: player name */
												esc_html__( 'Exam completed for %s', 'wfeb' ),
												'<strong>' . esc_html( $act->player_name ) . '</strong>'
											);
										} else {
											printf(
												/* translators: %s: player name */
												esc_html__( 'Draft saved for %s', 'wfeb' ),
												'<strong>' . esc_html( $act->player_name ) . '</strong>'
											);
										}
										?>
									</div>
									<div class="wfeb-activity-time"><?php echo esc_html( wfeb_format_date( $act->exam_date ) ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="wfeb-text-muted" style="text-align: center; padding: 16px 0;"><?php echo esc_html__( 'No recent activity', 'wfeb' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Quick Links -->
			<div class="wfeb-card">
				<div class="wfeb-card-header">
					<h3 class="wfeb-card-title"><?php echo esc_html__( 'QUICK LINKS', 'wfeb' ); ?></h3>
				</div>
				<div class="wfeb-card-body" style="padding: 8px;">
					<a href="<?php echo esc_url( add_query_arg( 'section', 'my-players', $base_url ) ); ?>" class="wfeb-activity-item" style="text-decoration: none; padding: 12px 16px; border-radius: var(--wfeb-radius-sm);">
						<div class="wfeb-activity-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
						</div>
						<div class="wfeb-activity-content">
							<div class="wfeb-activity-text"><?php echo esc_html__( 'My Players', 'wfeb' ); ?></div>
							<div class="wfeb-activity-time"><?php echo esc_html( $total_players . ' players' ); ?></div>
						</div>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'section', 'exam-history', $base_url ) ); ?>" class="wfeb-activity-item" style="text-decoration: none; padding: 12px 16px; border-radius: var(--wfeb-radius-sm);">
						<div class="wfeb-activity-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						</div>
						<div class="wfeb-activity-content">
							<div class="wfeb-activity-text"><?php echo esc_html__( 'Exam History', 'wfeb' ); ?></div>
							<div class="wfeb-activity-time"><?php echo esc_html( $total_exams . ' exams' ); ?></div>
						</div>
					</a>
				</div>
			</div>

		</div>

	</div>

</div>
