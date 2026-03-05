<?php
/**
 * Template: Coach Dashboard - Conduct Exam Section
 *
 * The key template for conducting a 7-category football skills exam.
 * Multi-section card form with stepper inputs and auto-calculated scores.
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

// Check for existing exam (edit mode).
$edit_exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$edit_exam    = null;
$is_edit_mode = false;

if ( $edit_exam_id ) {
	$edit_exam = WFEB()->exam->get( $edit_exam_id );
	// Validate exam belongs to this coach.
	if ( $edit_exam && absint( $edit_exam->coach_id ) === $coach_id ) {
		$is_edit_mode = true;
	} else {
		$edit_exam    = null;
		$edit_exam_id = 0;
	}
}

// Check for pre-selected player.
$preselected_player_id = $is_edit_mode ? absint( $edit_exam->player_id ) : ( isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$preselected_player    = null;

if ( $preselected_player_id ) {
	$preselected_player = WFEB()->player->get( $preselected_player_id );

	// Validate player belongs to this coach.
	if ( $preselected_player && absint( $preselected_player->coach_id ) !== $coach_id ) {
		$preselected_player    = null;
		$preselected_player_id = 0;
	}
}

// Coach credits.
$credits = $coach ? absint( $coach->credits_balance ) : 0;

// Load all players for the select dropdown.
$all_players = $coach_id ? WFEB()->player->get_by_coach(
	$coach_id,
	array(
		'orderby' => 'full_name',
		'order'   => 'ASC',
		'limit'   => 500,
	)
) : array();
?>

<?php if ( $credits < 1 ) : ?>
	<div class="wfeb-alert wfeb-alert--error">
		<span class="dashicons dashicons-warning"></span>
		<div>
			<strong><?php echo esc_html__( 'No Credits Available', 'wfeb' ); ?></strong>
			<p>
				<?php echo esc_html__( 'You need at least 1 credit to complete an exam and generate a certificate. You can still save as a draft.', 'wfeb' ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'credits', $base_url ) ); ?>"><?php echo esc_html__( 'Buy Credits', 'wfeb' ); ?></a>
			</p>
		</div>
	</div>
<?php endif; ?>

<form id="wfeb-exam-form" class="wfeb-form wfeb-exam-form" method="post" novalidate>
	<?php if ( $is_edit_mode ) : ?>
		<input type="hidden" name="exam_id" value="<?php echo esc_attr( $edit_exam_id ); ?>">
	<?php endif; ?>

	<!-- Player Selection -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="dashicons dashicons-admin-users"></span>
				<?php echo esc_html__( 'Player Selection', 'wfeb' ); ?>
			</h3>
		</div>
		<div class="wfeb-card-body">

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label">
						<?php echo esc_html__( 'Select Player', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<div class="wfeb-select" id="wfeb-player-select">
						<input type="hidden" id="wfeb-exam-player-id" name="player_id" value="<?php echo esc_attr( $preselected_player_id ); ?>">

						<!-- Trigger -->
						<div class="wfeb-select-trigger" id="wfeb-player-trigger">
							<?php if ( $preselected_player ) : ?>
								<span class="wfeb-select-chip" data-id="<?php echo esc_attr( $preselected_player_id ); ?>">
									<?php echo esc_html( $preselected_player->full_name ); ?>
									<button type="button" class="wfeb-select-chip-remove" aria-label="<?php echo esc_attr__( 'Remove', 'wfeb' ); ?>">&times;</button>
								</span>
							<?php else : ?>
								<span class="wfeb-select-placeholder"><?php echo esc_html__( 'Choose a player...', 'wfeb' ); ?></span>
							<?php endif; ?>
							<svg class="wfeb-select-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
						</div>

						<!-- Dropdown -->
						<div class="wfeb-select-dropdown" id="wfeb-player-dropdown">
							<div class="wfeb-select-search-wrap">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
								<input type="text" class="wfeb-select-search" id="wfeb-player-search" placeholder="<?php echo esc_attr__( 'Search...', 'wfeb' ); ?>" autocomplete="off">
							</div>
							<div class="wfeb-select-options" id="wfeb-player-options">
								<?php if ( ! empty( $all_players ) ) : ?>
									<?php foreach ( $all_players as $p ) : ?>
										<div class="wfeb-select-option" data-id="<?php echo esc_attr( $p->id ); ?>" data-name="<?php echo esc_attr( $p->full_name ); ?>" data-email="<?php echo esc_attr( $p->email ); ?>">
											<?php echo esc_html( $p->full_name ); ?>
										</div>
									<?php endforeach; ?>
								<?php else : ?>
									<div class="wfeb-select-empty"><?php echo esc_html__( 'No players added yet.', 'wfeb' ); ?></div>
								<?php endif; ?>
							</div>
							<div class="wfeb-select-footer" id="wfeb-player-footer">
								<?php
								printf(
									/* translators: %d: number of players */
									esc_html__( '%d player(s) available', 'wfeb' ),
									count( $all_players )
								);
								?>
							</div>
						</div>
					</div>
					<p class="wfeb-form-help">
						<a href="<?php echo esc_url( add_query_arg( 'section', 'add-player', $base_url ) ); ?>"><?php echo esc_html__( 'Or create new player', 'wfeb' ); ?></a>
					</p>
				</div>
			</div>

			<!-- Inline New Player Form (hidden by default) -->
			<div id="wfeb-new-player-inline" class="wfeb-inline-form" style="display:none;">
				<h4 class="wfeb-inline-title"><?php echo esc_html__( 'Quick Add New Player', 'wfeb' ); ?></h4>
				<div class="wfeb-form-row">
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-new-player-name">
							<?php echo esc_html__( 'Full Name', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input type="text" id="wfeb-new-player-name" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'Player full name', 'wfeb' ); ?>">
					</div>
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-new-player-dob">
							<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input type="date" id="wfeb-new-player-dob" class="wfeb-form-input">
					</div>
				</div>
				<div class="wfeb-form-row">
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-new-player-email">
							<?php echo esc_html__( 'Email (optional)', 'wfeb' ); ?>
						</label>
						<input type="email" id="wfeb-new-player-email" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'player@example.com', 'wfeb' ); ?>">
					</div>
					<div class="wfeb-form-group wfeb-form-group--action">
						<button type="button" id="wfeb-create-inline-player" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm">
							<?php echo esc_html__( 'Create Player', 'wfeb' ); ?>
						</button>
						<button type="button" id="wfeb-cancel-new-player" class="wfeb-btn wfeb-btn--secondary wfeb-btn--sm">
							<?php echo esc_html__( 'Cancel', 'wfeb' ); ?>
						</button>
					</div>
				</div>
			</div>

		</div>
	</div>

	<!-- Exam Metadata -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="dashicons dashicons-calendar-alt"></span>
				<?php echo esc_html__( 'Exam Information', 'wfeb' ); ?>
			</h3>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-exam-date">
						<?php echo esc_html__( 'Exam Date', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="date"
						id="wfeb-exam-date"
						name="exam_date"
						class="wfeb-form-input"
						value="<?php echo esc_attr( $is_edit_mode ? $edit_exam->exam_date : gmdate( 'Y-m-d' ) ); ?>"
						required
					>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-assistant-examiner">
						<?php echo esc_html__( 'Assistant Examiner Name', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-assistant-examiner"
						name="assistant_examiner"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'Enter assistant examiner name', 'wfeb' ); ?>"
						value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->assistant_examiner ) : ''; ?>"
						required
					>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 1: Short Passing (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="short_passing" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">1</span>
				<?php echo esc_html__( 'Short Passing', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-short-passing-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Left Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="short_passing_left" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->short_passing_left ) : '0'; ?>" min="0" max="5" readonly data-calc="short_passing">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Right Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="short_passing_right" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->short_passing_right ) : '0'; ?>" min="0" max="5" readonly data-calc="short_passing">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 2: Long Passing (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="long_passing" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">2</span>
				<?php echo esc_html__( 'Long Passing', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-long-passing-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Left Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="long_passing_left" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->long_passing_left ) : '0'; ?>" min="0" max="5" readonly data-calc="long_passing">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Right Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="long_passing_right" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->long_passing_right ) : '0'; ?>" min="0" max="5" readonly data-calc="long_passing">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 3: Shooting (/20) -->
	<div class="wfeb-card wfeb-exam-category" data-category="shooting" data-max="20">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">3</span>
				<?php echo esc_html__( 'Shooting', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-shooting-badge">0/20</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-shooting-grid">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Top Left', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="shooting_tl" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->shooting_tl ) : '0'; ?>" min="0" max="5" readonly data-calc="shooting">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Top Right', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="shooting_tr" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->shooting_tr ) : '0'; ?>" min="0" max="5" readonly data-calc="shooting">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Bottom Left', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="shooting_bl" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->shooting_bl ) : '0'; ?>" min="0" max="5" readonly data-calc="shooting">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Bottom Right', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="shooting_br" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->shooting_br ) : '0'; ?>" min="0" max="5" readonly data-calc="shooting">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 4: Sprinting (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="sprint" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">4</span>
				<?php echo esc_html__( 'Sprinting', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-sprint-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-sprint-time"><?php echo esc_html__( 'Time (seconds)', 'wfeb' ); ?></label>
					<input
						type="number"
						id="wfeb-sprint-time"
						name="sprint_time"
						class="wfeb-form-input wfeb-time-input"
						value="<?php echo $is_edit_mode && $edit_exam->sprint_time ? esc_attr( $edit_exam->sprint_time ) : ''; ?>"
						min="0"
						step="0.01"
						placeholder="<?php echo esc_attr__( 'seconds', 'wfeb' ); ?>"
						data-calc="sprint"
						data-type="time"
					>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Lower time = higher score. Under 5.5s = 10 points.', 'wfeb' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 5: Dribbling Speed (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="dribble" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">5</span>
				<?php echo esc_html__( 'Dribbling Speed', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-dribble-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-dribble-time"><?php echo esc_html__( 'Time (seconds)', 'wfeb' ); ?></label>
					<input
						type="number"
						id="wfeb-dribble-time"
						name="dribble_time"
						class="wfeb-form-input wfeb-time-input"
						value="<?php echo $is_edit_mode && $edit_exam->dribble_time ? esc_attr( $edit_exam->dribble_time ) : ''; ?>"
						min="0"
						step="0.01"
						placeholder="<?php echo esc_attr__( 'seconds', 'wfeb' ); ?>"
						data-calc="dribble"
						data-type="time"
					>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Lower time = higher score. Under 4.0s = 10 points.', 'wfeb' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Category 6: Kick Up Touch (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="kickups" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">6</span>
				<?php echo esc_html__( 'Kick Up Touch', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-kickups-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row wfeb-form-row--3">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-kickups-1"><?php echo esc_html__( 'Attempt 1', 'wfeb' ); ?></label>
					<input
						type="number"
						id="wfeb-kickups-1"
						name="kickups_attempt1"
						class="wfeb-form-input"
						value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->kickups_attempt1 ) : '0'; ?>"
						min="0"
						step="1"
						data-calc="kickups"
						data-type="kickup"
					>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-kickups-2"><?php echo esc_html__( 'Attempt 2', 'wfeb' ); ?></label>
					<input
						type="number"
						id="wfeb-kickups-2"
						name="kickups_attempt2"
						class="wfeb-form-input"
						value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->kickups_attempt2 ) : '0'; ?>"
						min="0"
						step="1"
						data-calc="kickups"
						data-type="kickup"
					>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-kickups-3"><?php echo esc_html__( 'Attempt 3', 'wfeb' ); ?></label>
					<input
						type="number"
						id="wfeb-kickups-3"
						name="kickups_attempt3"
						class="wfeb-form-input"
						value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->kickups_attempt3 ) : '0'; ?>"
						min="0"
						step="1"
						data-calc="kickups"
						data-type="kickup"
					>
				</div>
			</div>
			<span class="wfeb-form-help"><?php echo esc_html__( 'Best of 3 attempts is used. 100+ kickups = 10 points.', 'wfeb' ); ?></span>
		</div>
	</div>

	<!-- Category 7: Volley Touch (/10) -->
	<div class="wfeb-card wfeb-exam-category" data-category="volley" data-max="10">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="wfeb-category-number">7</span>
				<?php echo esc_html__( 'Volley Touch', 'wfeb' ); ?>
			</h3>
			<span class="wfeb-score-badge" id="wfeb-volley-badge">0/10</span>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Left Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="volley_left" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->volley_left ) : '0'; ?>" min="0" max="5" readonly data-calc="volley">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label"><?php echo esc_html__( 'Right Foot', 'wfeb' ); ?></label>
					<div class="wfeb-stepper" data-min="0" data-max="5">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-minus" aria-label="<?php echo esc_attr__( 'Decrease', 'wfeb' ); ?>">-</button>
						<input type="number" name="volley_right" class="wfeb-stepper-input" value="<?php echo $is_edit_mode ? esc_attr( $edit_exam->volley_right ) : '0'; ?>" min="0" max="5" readonly data-calc="volley">
						<button type="button" class="wfeb-stepper-btn wfeb-stepper-plus" aria-label="<?php echo esc_attr__( 'Increase', 'wfeb' ); ?>">+</button>
					</div>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Score: 0-5', 'wfeb' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Results Summary -->
	<div class="wfeb-card wfeb-exam-summary">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="dashicons dashicons-chart-bar"></span>
				<?php echo esc_html__( 'Results Summary', 'wfeb' ); ?>
			</h3>
		</div>
		<div class="wfeb-card-body">

			<div class="wfeb-summary-total">
				<div class="wfeb-summary-score">
					<span class="wfeb-summary-score-value" id="wfeb-total-score">0</span>
					<span class="wfeb-summary-score-max">/80</span>
				</div>
				<div class="wfeb-summary-level">
					<span class="wfeb-badge wfeb-badge--level wfeb-badge--lg" id="wfeb-award-level"><?php echo esc_html__( 'UNGRADED', 'wfeb' ); ?></span>
					<span class="wfeb-summary-playing-level" id="wfeb-playing-level"><?php echo esc_html__( 'Ungraded', 'wfeb' ); ?></span>
				</div>
			</div>

			<div class="wfeb-summary-breakdown">
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Short Passing', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-short-passing" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-short-passing">0/10</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Long Passing', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-long-passing" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-long-passing">0/10</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Shooting', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-shooting" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-shooting">0/20</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Sprinting', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-sprint" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-sprint">0/10</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Dribbling', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-dribble" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-dribble">0/10</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Kick Up Touch', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-kickups" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-kickups">0/10</span>
				</div>
				<div class="wfeb-breakdown-item">
					<span class="wfeb-breakdown-label"><?php echo esc_html__( 'Volley Touch', 'wfeb' ); ?></span>
					<div class="wfeb-breakdown-bar">
						<div class="wfeb-breakdown-fill" id="wfeb-bar-volley" style="width: 0%;"></div>
					</div>
					<span class="wfeb-breakdown-value" id="wfeb-val-volley">0/10</span>
				</div>
			</div>

		</div>
	</div>

	<!-- Notes -->
	<div class="wfeb-card">
		<div class="wfeb-card-header">
			<h3 class="wfeb-card-title">
				<span class="dashicons dashicons-edit"></span>
				<?php echo esc_html__( 'Notes', 'wfeb' ); ?>
			</h3>
		</div>
		<div class="wfeb-card-body">
			<div class="wfeb-form-group wfeb-form-group--full">
				<textarea
					id="wfeb-exam-notes"
					name="notes"
					class="wfeb-form-textarea"
					rows="4"
					placeholder="<?php echo esc_attr__( 'Optional notes about the exam, conditions, performance observations...', 'wfeb' ); ?>"
				><?php echo $is_edit_mode ? esc_textarea( $edit_exam->notes ) : ''; ?></textarea>
			</div>
		</div>
	</div>

	<!-- Action Buttons -->
	<div class="wfeb-form-actions wfeb-form-actions--sticky">
		<button type="button" id="wfeb-save-draft" class="wfeb-btn wfeb-btn--secondary">
			<span class="dashicons dashicons-media-document"></span>
			<?php echo esc_html__( 'Save as Draft', 'wfeb' ); ?>
		</button>
		<button type="button" id="wfeb-complete-exam" class="wfeb-btn wfeb-btn--primary wfeb-btn--lg" data-credits="<?php echo esc_attr( $credits ); ?>">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php echo esc_html__( 'Complete & Generate Certificate', 'wfeb' ); ?>
		</button>
	</div>

</form>
