<?php
/**
 * Template: Coach Dashboard - Settings Section
 *
 * Coach account settings: personal info, professional details,
 * change password, and account deletion (danger zone).
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

// Current user for email.
$current_user = wp_get_current_user();

// Current profile picture attachment ID.
$profile_picture_id = ( $coach && ! empty( $coach->profile_picture ) ) ? absint( $coach->profile_picture ) : 0;
?>

<!-- Profile Picture -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title">
			<span class="dashicons dashicons-format-image"></span>
			<?php echo esc_html__( 'Profile Picture', 'wfeb' ); ?>
		</h3>
	</div>
	<div class="wfeb-card-body">
		<form id="wfeb-settings-avatar" class="wfeb-form" novalidate>
			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<?php
					echo WFEB_Media::upload_zone( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'type'          => 'avatar',
						'input_name'    => 'profile_picture',
						'input_id'      => 'wfeb-profile-picture',
						'attachment_id' => $profile_picture_id,
						'button_text'   => __( 'Upload Photo', 'wfeb' ),
						'remove_text'   => __( 'Remove Photo', 'wfeb' ),
						'enable_crop'   => true,
						'crop_ratio'    => 1,
					) );
					?>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Square image recommended. Will be cropped to 1:1 ratio.', 'wfeb' ); ?></span>
				</div>
			</div>

			<div class="wfeb-form-actions">
				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-settings-save" data-form="avatar">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html__( 'Save Profile Picture', 'wfeb' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Personal Information -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title">
			<span class="dashicons dashicons-admin-users"></span>
			<?php echo esc_html__( 'Personal Information', 'wfeb' ); ?>
		</h3>
	</div>
	<div class="wfeb-card-body">
		<form id="wfeb-settings-personal" class="wfeb-form" novalidate>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-name">
						<?php echo esc_html__( 'Full Name', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-settings-name"
						name="full_name"
						class="wfeb-form-input"
						value="<?php echo $coach ? esc_attr( $coach->full_name ) : ''; ?>"
						required
					>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-dob">
						<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
					</label>
					<input
						type="date"
						id="wfeb-settings-dob"
						name="dob"
						class="wfeb-form-input"
						value="<?php echo $coach ? esc_attr( $coach->dob ) : ''; ?>"
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-email">
						<?php echo esc_html__( 'Email', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="email"
						id="wfeb-settings-email"
						name="email"
						class="wfeb-form-input"
						value="<?php echo $coach ? esc_attr( $coach->email ) : esc_attr( $current_user->user_email ); ?>"
						required
					>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-phone">
						<?php echo esc_html__( 'Phone', 'wfeb' ); ?>
					</label>
					<input
						type="tel"
						id="wfeb-settings-phone"
						name="phone"
						class="wfeb-form-input"
						value="<?php echo $coach ? esc_attr( $coach->phone ) : ''; ?>"
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label" for="wfeb-settings-address">
						<?php echo esc_html__( 'Address', 'wfeb' ); ?>
					</label>
					<textarea
						id="wfeb-settings-address"
						name="address"
						class="wfeb-form-textarea"
						rows="3"
					><?php echo $coach ? esc_textarea( $coach->address ) : ''; ?></textarea>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-country">
						<?php echo esc_html__( 'Country', 'wfeb' ); ?>
					</label>
					<?php $current_country = ( $coach && ! empty( $coach->country ) ) ? $coach->country : 'United Kingdom'; ?>
					<select
						id="wfeb-settings-country"
						name="country"
						class="wfeb-form-input"
					>
						<?php foreach ( wfeb_get_countries() as $c ) : ?>
							<option value="<?php echo esc_attr( $c ); ?>"<?php selected( $c, $current_country ); ?>>
								<?php echo esc_html( $c ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="wfeb-form-actions">
				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-settings-save" data-form="personal">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html__( 'Save Personal Info', 'wfeb' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>

<!-- Professional Details -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title">
			<span class="dashicons dashicons-id-alt"></span>
			<?php echo esc_html__( 'Professional Details', 'wfeb' ); ?>
		</h3>
	</div>
	<div class="wfeb-card-body">
		<form id="wfeb-settings-professional" class="wfeb-form" novalidate enctype="multipart/form-data">

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-ngb">
						<?php echo esc_html__( 'NGB Number', 'wfeb' ); ?>
					</label>
					<input
						type="text"
						id="wfeb-settings-ngb"
						name="ngb_number"
						class="wfeb-form-input"
						value="<?php echo $coach ? esc_attr( $coach->ngb_number ) : ''; ?>"
					>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group wfeb-form-group--full">
					<label class="wfeb-form-label" for="wfeb-settings-certificate">
						<?php echo esc_html__( 'Coaching Certificate', 'wfeb' ); ?>
					</label>

					<?php if ( $coach && ! empty( $coach->coaching_certificate ) ) : ?>
						<div class="wfeb-current-file">
							<span class="dashicons dashicons-media-default"></span>
							<span class="wfeb-current-file-name"><?php echo esc_html( basename( $coach->coaching_certificate ) ); ?></span>
							<a href="<?php echo esc_url( $coach->coaching_certificate ); ?>" target="_blank" rel="noopener noreferrer" class="wfeb-btn wfeb-btn--sm wfeb-btn--outline">
								<?php echo esc_html__( 'View', 'wfeb' ); ?>
							</a>
						</div>
					<?php endif; ?>

					<input
						type="file"
						id="wfeb-settings-certificate"
						name="coaching_certificate"
						class="wfeb-form-input"
						accept=".pdf,.jpg,.jpeg,.png"
					>
					<span class="wfeb-form-help"><?php echo esc_html__( 'Upload a new certificate to replace the existing one. Accepted: PDF, JPG, PNG.', 'wfeb' ); ?></span>
				</div>
			</div>

			<div class="wfeb-form-actions">
				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-settings-save" data-form="professional">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html__( 'Save Professional Details', 'wfeb' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>

<!-- Change Password -->
<div class="wfeb-card">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title">
			<span class="dashicons dashicons-lock"></span>
			<?php echo esc_html__( 'Change Password', 'wfeb' ); ?>
		</h3>
	</div>
	<div class="wfeb-card-body">
		<form id="wfeb-settings-password" class="wfeb-form" novalidate>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-current-password">
						<?php echo esc_html__( 'Current Password', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<div class="wfeb-password-wrap">
						<input
							type="password"
							id="wfeb-settings-current-password"
							name="current_password"
							class="wfeb-form-input"
							required
							autocomplete="current-password"
						>
						<button type="button" class="wfeb-password-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"></button>
					</div>
				</div>
			</div>

			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-new-password">
						<?php echo esc_html__( 'New Password', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<div class="wfeb-password-wrap">
						<input
							type="password"
							id="wfeb-settings-new-password"
							name="new_password"
							class="wfeb-form-input"
							required
							autocomplete="new-password"
						>
						<button type="button" class="wfeb-password-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"></button>
					</div>
					<div class="wfeb-password-strength" id="wfeb-password-strength">
						<div class="wfeb-password-strength-bar">
							<div class="wfeb-password-strength-fill" id="wfeb-password-strength-fill"></div>
						</div>
						<span class="wfeb-password-strength-text" id="wfeb-password-strength-text"></span>
					</div>
				</div>
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-settings-confirm-password">
						<?php echo esc_html__( 'Confirm New Password', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<div class="wfeb-password-wrap">
						<input
							type="password"
							id="wfeb-settings-confirm-password"
							name="confirm_password"
							class="wfeb-form-input"
							required
							autocomplete="new-password"
						>
						<button type="button" class="wfeb-password-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"></button>
					</div>
				</div>
			</div>

			<div class="wfeb-form-actions">
				<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-settings-save" data-form="password">
					<span class="dashicons dashicons-lock"></span>
					<?php echo esc_html__( 'Update Password', 'wfeb' ); ?>
				</button>
			</div>

		</form>
	</div>
</div>

<!-- Danger Zone -->
<div class="wfeb-card wfeb-card--danger">
	<div class="wfeb-card-header">
		<h3 class="wfeb-card-title wfeb-text-danger">
			<span class="dashicons dashicons-warning"></span>
			<?php echo esc_html__( 'Danger Zone', 'wfeb' ); ?>
		</h3>
	</div>
	<div class="wfeb-card-body">
		<p class="wfeb-danger-description">
			<?php echo esc_html__( 'Once you delete your account, there is no going back. All your data including players, exams, and certificates will be permanently removed. Please be certain.', 'wfeb' ); ?>
		</p>

		<div class="wfeb-danger-confirm">
			<label class="wfeb-form-label" for="wfeb-delete-confirm">
				<?php echo esc_html__( 'Type "DELETE" to confirm account deletion:', 'wfeb' ); ?>
			</label>
			<div class="wfeb-form-row">
				<div class="wfeb-form-group">
					<input
						type="text"
						id="wfeb-delete-confirm"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'Type DELETE here', 'wfeb' ); ?>"
						autocomplete="off"
					>
				</div>
				<div class="wfeb-form-group wfeb-form-group--action">
					<button type="button" id="wfeb-delete-account" class="wfeb-btn wfeb-btn--danger" disabled>
						<span class="dashicons dashicons-trash"></span>
						<?php echo esc_html__( 'Delete My Account', 'wfeb' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
