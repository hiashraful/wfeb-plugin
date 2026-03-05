<?php
/**
 * Template: Coach Dashboard - Add/Edit Player Section
 *
 * Form for creating a new player or editing an existing one.
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

// Check if editing an existing player.
$player_id = isset( $_GET['player_id'] ) ? absint( $_GET['player_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$player    = null;
$is_edit   = false;

if ( $player_id ) {
	$player = WFEB()->player->get( $player_id );

	// Validate the player belongs to this coach.
	if ( $player && absint( $player->coach_id ) === $coach_id ) {
		$is_edit = true;
	} else {
		$player    = null;
		$player_id = 0;
	}
}

// Form title.
$form_title = $is_edit
	? __( 'Edit Player', 'wfeb' )
	: __( 'Add New Player', 'wfeb' );
?>

<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html( $form_title ); ?></h3>
	</div>
	<div class="wfeb-card-body">

		<form id="wfeb-player-form" class="wfeb-form" method="post" novalidate>

			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="player_id" value="<?php echo esc_attr( $player_id ); ?>">
			<?php endif; ?>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label">
						<?php echo esc_html__( 'Profile Photo', 'wfeb' ); ?>
					</label>
					<?php
					echo WFEB_Media::upload_zone( array(
						'type'          => 'avatar',
						'input_name'    => 'profile_picture',
						'input_id'      => 'wfeb-player-profile-picture',
						'attachment_id' => $is_edit ? absint( $player->profile_picture ?? 0 ) : 0,
						'button_text'   => __( 'Upload Photo', 'wfeb' ),
						'enable_crop'   => true,
						'crop_ratio'    => 1,
					) );
					?>
					<p class="wfeb-form-help"><?php echo esc_html__( 'Square image recommended. Will be cropped to 1:1 ratio.', 'wfeb' ); ?></p>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label" for="wfeb-player-name">
						<?php echo esc_html__( 'Full Name', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-player-name"
						name="full_name"
						class="wfeb-form-input"
						value="<?php echo $is_edit ? esc_attr( $player->full_name ) : ''; ?>"
						placeholder="<?php echo esc_attr__( 'Enter player full name', 'wfeb' ); ?>"
						required
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-player-dob">
						<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="date"
						id="wfeb-player-dob"
						name="dob"
						class="wfeb-form-input"
						value="<?php echo $is_edit ? esc_attr( $player->dob ) : ''; ?>"
						required
					>
				</div>

				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-player-email">
						<?php echo esc_html__( 'Email', 'wfeb' ); ?>
					</label>
					<input
						type="email"
						id="wfeb-player-email"
						name="email"
						class="wfeb-form-input"
						value="<?php echo $is_edit ? esc_attr( $player->email ) : ''; ?>"
						placeholder="<?php echo esc_attr__( 'player@example.com (optional)', 'wfeb' ); ?>"
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-player-phone">
						<?php echo esc_html__( 'Phone', 'wfeb' ); ?>
					</label>
					<input
						type="tel"
						id="wfeb-player-phone"
						name="phone"
						class="wfeb-form-input"
						value="<?php echo $is_edit ? esc_attr( $player->phone ) : ''; ?>"
						placeholder="<?php echo esc_attr__( 'Phone number (optional)', 'wfeb' ); ?>"
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label" for="wfeb-player-address">
						<?php echo esc_html__( 'Address', 'wfeb' ); ?>
					</label>
					<textarea
						id="wfeb-player-address"
						name="address"
						class="wfeb-form-textarea"
						rows="3"
						placeholder="<?php echo esc_attr__( 'Player address (optional)', 'wfeb' ); ?>"
					><?php echo $is_edit ? esc_textarea( $player->address ) : ''; ?></textarea>
				</div>
			</div>

			<?php if ( $is_edit && ! empty( $player->user_id ) ) : ?>
				<div class="wfeb-account-status wfeb-account-status--active">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php echo esc_html__( 'Login Account Active', 'wfeb' ); ?>
				</div>
			<?php elseif ( $is_edit ) : ?>
				<div id="wfeb-create-account-row" class="wfeb-form-row" data-edit-no-account="1">
					<div class="wfeb-form-group wfeb-form-group--full">
						<div class="wfeb-create-account-option">
							<label class="wfeb-form-check">
								<input type="checkbox" name="create_account" id="wfeb-create-account" value="1">
								<span class="wfeb-form-check-label"><?php echo esc_html__( 'Create login account for this player', 'wfeb' ); ?></span>
							</label>
							<p class="wfeb-form-help wfeb-create-account-help" style="display:none;">
								<?php echo esc_html__( 'A login account will be created and credentials will be emailed to the player.', 'wfeb' ); ?>
							</p>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div id="wfeb-create-account-row" class="wfeb-form-row" style="display:none;">
					<div class="wfeb-form-group wfeb-form-group--full">
						<div class="wfeb-create-account-option">
							<label class="wfeb-form-check">
								<input type="checkbox" name="create_account" id="wfeb-create-account" value="1">
								<span class="wfeb-form-check-label"><?php echo esc_html__( 'Create login account for this player', 'wfeb' ); ?></span>
							</label>
							<p class="wfeb-form-help wfeb-create-account-help" style="display:none;">
								<?php echo esc_html__( 'A login account will be created and credentials will be emailed to the player.', 'wfeb' ); ?>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="wfeb-form-actions">
				<button type="submit" class="wfeb-btn wfeb-btn--primary">
					<span class="dashicons dashicons-saved"></span>
					<?php echo $is_edit ? esc_html__( 'Update Player', 'wfeb' ) : esc_html__( 'Save Player', 'wfeb' ); ?>
				</button>
				<a href="<?php echo esc_url( add_query_arg( 'section', 'my-players', $base_url ) ); ?>" class="wfeb-btn wfeb-btn--secondary">
					<?php echo esc_html__( 'Cancel', 'wfeb' ); ?>
				</a>
			</div>

		</form>

	</div>
</div>
