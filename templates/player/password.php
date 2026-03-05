<?php
/**
 * Template: Player Dashboard - Change Password Section
 *
 * Displays a form for the player to change their password with
 * current password verification, new password strength meter,
 * and confirmation field.
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
?>

<div class="wfeb-card" style="max-width: 560px;">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title"><?php echo esc_html__( 'Change Password', 'wfeb' ); ?></h3>
	</div>
	<div class="wfeb-card-body">

		<!-- Notice area for success/error messages -->
		<div id="wfeb-password-notice" style="display: none;"></div>

		<form id="wfeb-password-form" class="wfeb-form">

			<!-- Current Password -->
			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-current-password">
					<?php echo esc_html__( 'Current Password', 'wfeb' ); ?>
					<span class="required">*</span>
				</label>
				<div style="position: relative;">
					<input
						type="password"
						id="wfeb-current-password"
						name="current_password"
						class="wfeb-form-input"
						required
						autocomplete="current-password"
						placeholder="<?php echo esc_attr__( 'Enter your current password', 'wfeb' ); ?>"
						style="padding-right: 44px;"
					>
					<button
						type="button"
						class="wfeb-password-toggle"
						data-target="wfeb-current-password"
						aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
						style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--wfeb-text-muted); padding: 4px;"
					>
						<span class="dashicons dashicons-visibility"></span>
					</button>
				</div>
			</div>

			<!-- New Password -->
			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-new-password">
					<?php echo esc_html__( 'New Password', 'wfeb' ); ?>
					<span class="required">*</span>
				</label>
				<div style="position: relative;">
					<input
						type="password"
						id="wfeb-new-password"
						name="new_password"
						class="wfeb-form-input"
						required
						autocomplete="new-password"
						placeholder="<?php echo esc_attr__( 'Enter your new password', 'wfeb' ); ?>"
						style="padding-right: 44px;"
					>
					<button
						type="button"
						class="wfeb-password-toggle"
						data-target="wfeb-new-password"
						aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
						style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--wfeb-text-muted); padding: 4px;"
					>
						<span class="dashicons dashicons-visibility"></span>
					</button>
				</div>

				<!-- Password Strength Meter -->
				<div class="wfeb-password-strength" id="wfeb-password-strength" data-strength="">
					<div class="wfeb-password-strength-bars">
						<div class="wfeb-password-strength-bar"></div>
						<div class="wfeb-password-strength-bar"></div>
						<div class="wfeb-password-strength-bar"></div>
						<div class="wfeb-password-strength-bar"></div>
					</div>
					<span class="wfeb-password-label" id="wfeb-password-strength-label"></span>
				</div>
			</div>

			<!-- Confirm New Password -->
			<div class="wfeb-form-group">
				<label class="wfeb-form-label" for="wfeb-confirm-password">
					<?php echo esc_html__( 'Confirm New Password', 'wfeb' ); ?>
					<span class="required">*</span>
				</label>
				<div style="position: relative;">
					<input
						type="password"
						id="wfeb-confirm-password"
						name="confirm_password"
						class="wfeb-form-input"
						required
						autocomplete="new-password"
						placeholder="<?php echo esc_attr__( 'Confirm your new password', 'wfeb' ); ?>"
						style="padding-right: 44px;"
					>
					<button
						type="button"
						class="wfeb-password-toggle"
						data-target="wfeb-confirm-password"
						aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
						style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--wfeb-text-muted); padding: 4px;"
					>
						<span class="dashicons dashicons-visibility"></span>
					</button>
				</div>
			</div>

			<!-- Submit -->
			<div class="wfeb-mt-8">
				<button type="submit" class="wfeb-btn wfeb-btn--primary" id="wfeb-password-submit">
					<span class="dashicons dashicons-lock"></span>
					<?php echo esc_html__( 'Update Password', 'wfeb' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>
