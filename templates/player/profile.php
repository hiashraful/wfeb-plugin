<?php
/**
 * Template: Player Dashboard - Profile Section
 *
 * Displays the player's profile card with avatar, read-only name/DOB,
 * and an editable form for email, phone, and address.
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

// Player details.
$player_name    = $player ? esc_html( $player->full_name ) : '';
$player_dob     = $player ? esc_html( wfeb_format_date( $player->dob ) ) : '';
$player_email   = $player ? esc_attr( $player->email ) : '';
$player_phone   = $player ? esc_attr( $player->phone ) : '';
$player_address = $player ? esc_textarea( $player->address ) : '';

// Avatar: check for user meta or fall back to placeholder.
$user_id    = get_current_user_id();
$avatar_url = get_user_meta( $user_id, 'wfeb_avatar_url', true );

if ( empty( $avatar_url ) ) {
	$avatar_url = WFEB_PLUGIN_URL . 'assets/images/placeholder-avatar.svg';
}

// First letter for fallback display.
$first_letter = $player_name ? strtoupper( mb_substr( $player_name, 0, 1 ) ) : '?';
?>

<!-- Profile Card -->
<div class="wfeb-profile-card wfeb-mb-24">
	<div class="wfeb-profile-card-header">
		<!-- Avatar -->
		<div class="wfeb-profile-avatar" id="wfeb-avatar-trigger">
			<?php if ( ! empty( $avatar_url ) ) : ?>
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $player_name ); ?>" id="wfeb-avatar-preview">
			<?php else : ?>
				<span id="wfeb-avatar-letter"><?php echo esc_html( $first_letter ); ?></span>
			<?php endif; ?>
			<div class="wfeb-profile-avatar-overlay">
				<span class="dashicons dashicons-camera"></span>
			</div>
		</div>
		<!-- Hidden file input for avatar upload -->
		<input
			type="file"
			id="wfeb-avatar-input"
			accept="image/*"
			style="display: none;"
		>
	</div>

	<div class="wfeb-profile-card-body">
		<!-- Player Info (read-only, set by coach) -->
		<div class="wfeb-profile-info">
			<h2 class="wfeb-profile-name"><?php echo esc_html( $player_name ); ?></h2>
			<?php if ( $player_dob ) : ?>
				<div class="wfeb-text-muted" style="font-size: 14px;">
					<span class="dashicons dashicons-calendar-alt" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"></span>
					<?php echo esc_html( $player_dob ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Editable Profile Form -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Contact Information', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">
		<form id="wfeb-profile-form" class="wfeb-form">
			<!-- Notice area -->
			<div id="wfeb-profile-notice" style="display: none;"></div>

			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-profile-email">
					<?php echo esc_html__( 'Email', 'wfeb' ); ?>
				</label>
				<input
					type="email"
					id="wfeb-profile-email"
					name="email"
					class="wfeb-form-input"
					value="<?php echo esc_attr( $player_email ); ?>"
					placeholder="<?php echo esc_attr__( 'Enter your email address', 'wfeb' ); ?>"
				>
			</div>

			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-profile-phone">
					<?php echo esc_html__( 'Phone', 'wfeb' ); ?>
				</label>
				<input
					type="tel"
					id="wfeb-profile-phone"
					name="phone"
					class="wfeb-form-input"
					value="<?php echo esc_attr( $player_phone ); ?>"
					placeholder="<?php echo esc_attr__( 'Enter your phone number', 'wfeb' ); ?>"
				>
			</div>

			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-profile-address">
					<?php echo esc_html__( 'Address', 'wfeb' ); ?>
				</label>
				<textarea
					id="wfeb-profile-address"
					name="address"
					class="wfeb-form-input"
					rows="3"
					placeholder="<?php echo esc_attr__( 'Enter your address', 'wfeb' ); ?>"
				><?php echo $player_address; // Already escaped with esc_textarea above. ?></textarea>
			</div>

			<div>
				<button type="submit" class="wfeb-btn wfeb-btn--primary" id="wfeb-profile-save">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html__( 'Save Changes', 'wfeb' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
