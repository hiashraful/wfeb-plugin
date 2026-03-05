<?php
/**
 * Template: Coach Registration
 *
 * Standalone full-page template that bypasses the WordPress theme.
 * Outputs its own DOCTYPE, html, head, and body tags.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect if already logged in as coach.
if ( is_user_logged_in() && wfeb_is_coach() ) {
	$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
	if ( $dashboard_page_id ) {
		wp_safe_redirect( get_permalink( $dashboard_page_id ) );
		exit;
	}
}

// Page URLs.
$coach_login_url = '';

$coach_login_page_id = get_option( 'wfeb_coach_login_page_id' );
if ( $coach_login_page_id ) {
	$coach_login_url = get_permalink( $coach_login_page_id );
}

$logo_url    = WFEB_PLUGIN_URL . 'assets/images/LOGO TRANSPARENT.png';
$css_url     = WFEB_PLUGIN_URL . 'assets/css/frontend.css';
$js_url      = WFEB_PLUGIN_URL . 'assets/js/frontend.js';
$ajax_url    = admin_url( 'admin-ajax.php' );
$nonce       = wp_create_nonce( 'wfeb_frontend_nonce' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html__( 'Coach Registration - WFEB', 'wfeb' ); ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( WFEB_VERSION ); ?>">
	<?php wp_head(); ?>
</head>
<body class="wfeb-page">

	<div class="wfeb-auth-container">

		<div class="wfeb-auth-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB Logo', 'wfeb' ); ?>">
		</div>

		<div class="wfeb-auth-card wfeb-auth-card--wide">

			<h2 class="wfeb-auth-title"><?php echo esc_html__( 'Coach Registration', 'wfeb' ); ?></h2>
			<p class="wfeb-auth-subtitle"><?php echo esc_html__( 'Register as a WFEB certified examiner', 'wfeb' ); ?></p>

			<div id="wfeb-register-notice" class="wfeb-notice" style="display:none;"></div>

			<form id="wfeb-coach-register-form" class="wfeb-form" method="post" enctype="multipart/form-data" novalidate>

				<input type="hidden" name="action" value="wfeb_coach_register">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<!-- Full Name -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-reg-fullname">
						<?php echo esc_html__( 'Full Name', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-reg-fullname"
						name="full_name"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'John Smith', 'wfeb' ); ?>"
						required
						autocomplete="name"
					>
				</div>

				<!-- Date of Birth & Email row -->
				<div class="wfeb-form-row">
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-reg-dob">
							<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="date"
							id="wfeb-reg-dob"
							name="dob"
							class="wfeb-form-input"
							required
						>
					</div>
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-reg-email">
							<?php echo esc_html__( 'Email', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="email"
							id="wfeb-reg-email"
							name="email"
							class="wfeb-form-input"
							placeholder="<?php echo esc_attr__( 'you@example.com', 'wfeb' ); ?>"
							required
							autocomplete="email"
						>
					</div>
				</div>

				<!-- Phone -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-reg-phone">
						<?php echo esc_html__( 'Phone', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="tel"
						id="wfeb-reg-phone"
						name="phone"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( '+44 7700 900000', 'wfeb' ); ?>"
						required
						autocomplete="tel"
					>
				</div>

				<!-- Address -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-reg-address">
						<?php echo esc_html__( 'Address', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<textarea
						id="wfeb-reg-address"
						name="address"
						class="wfeb-form-input"
						rows="3"
						placeholder="<?php echo esc_attr__( 'Full postal address', 'wfeb' ); ?>"
						required
					></textarea>
				</div>

				<!-- Country -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-reg-country">
						<?php echo esc_html__( 'Country', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<select
						id="wfeb-reg-country"
						name="country"
						class="wfeb-form-input"
						required
					>
						<?php foreach ( wfeb_get_countries() as $c ) : ?>
							<option value="<?php echo esc_attr( $c ); ?>"<?php selected( $c, 'United Kingdom' ); ?>>
								<?php echo esc_html( $c ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- NGB Number -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-reg-ngb">
						<?php echo esc_html__( 'NGB Number', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-reg-ngb"
						name="ngb_number"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'e.g. FA12345678', 'wfeb' ); ?>"
						required
					>
					<span class="wfeb-form-hint"><?php echo esc_html__( 'National Governing Body registration number', 'wfeb' ); ?></span>
				</div>

				<!-- Coaching Certificate Upload -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label">
						<?php echo esc_html__( 'Coaching Certificate', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<label class="wfeb-form-file" id="wfeb-cert-dropzone">
						<div class="wfeb-form-file__icon">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 3.75 3.75 0 013.572 5.345A3.75 3.75 0 0118 19.5H6.75z" />
							</svg>
						</div>
						<div class="wfeb-form-file__text">
							<strong><?php echo esc_html__( 'Click to upload', 'wfeb' ); ?></strong> <?php echo esc_html__( 'or drag and drop', 'wfeb' ); ?>
						</div>
						<div class="wfeb-form-file__hint"><?php echo esc_html__( 'PDF, JPG, JPEG or PNG (max 5MB)', 'wfeb' ); ?></div>
						<input
							type="file"
							id="wfeb-reg-certificate"
							name="coaching_certificate"
							accept=".pdf,.jpg,.jpeg,.png"
							required
						>
						<div class="wfeb-form-file__preview" id="wfeb-cert-preview"></div>
					</label>
				</div>

				<!-- Password -->
				<div class="wfeb-form-row">
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-reg-password">
							<?php echo esc_html__( 'Password', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<div class="wfeb-password-wrap">
							<input
								type="password"
								id="wfeb-reg-password"
								name="password"
								class="wfeb-form-input"
								placeholder="<?php echo esc_attr__( 'Min 8 characters', 'wfeb' ); ?>"
								required
								autocomplete="new-password"
								minlength="8"
							>
							<button
								type="button"
								class="wfeb-password-toggle"
								aria-pressed="false"
								aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
							></button>
						</div>
						<div id="wfeb-password-strength" class="wfeb-form-hint"></div>
					</div>
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-reg-password-confirm">
							<?php echo esc_html__( 'Confirm Password', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<div class="wfeb-password-wrap">
							<input
								type="password"
								id="wfeb-reg-password-confirm"
								name="password_confirm"
								class="wfeb-form-input"
								placeholder="<?php echo esc_attr__( 'Re-enter password', 'wfeb' ); ?>"
								required
								autocomplete="new-password"
							>
							<button
								type="button"
								class="wfeb-password-toggle"
								aria-pressed="false"
								aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
							></button>
						</div>
					</div>
				</div>

				<!-- Terms Agreement -->
				<div class="wfeb-form-group">
					<label class="wfeb-checkbox-group">
						<input type="checkbox" id="wfeb-reg-terms" name="terms" value="1" required>
						<span class="wfeb-checkbox-mark"></span>
						<span class="wfeb-checkbox-label">
							<?php echo esc_html__( 'I agree that my name will appear on certificates permanently and consent to WFEB storing my contact details.', 'wfeb' ); ?>
						</span>
					</label>
				</div>

				<!-- Submit -->
				<div class="wfeb-form-group">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--full">
						<?php echo esc_html__( 'Register as Coach', 'wfeb' ); ?>
					</button>
				</div>

			</form>

		</div><!-- .wfeb-auth-card -->

		<?php if ( $coach_login_url ) : ?>
			<div class="wfeb-auth-links">
				<?php echo esc_html__( 'Already have an account?', 'wfeb' ); ?>
				<a href="<?php echo esc_url( $coach_login_url ); ?>">
					<?php echo esc_html__( 'Sign in', 'wfeb' ); ?>
				</a>
			</div>
		<?php endif; ?>

	</div><!-- .wfeb-auth-container -->

	<?php wp_footer(); ?>
</body>
</html>
