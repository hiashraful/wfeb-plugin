<?php
/**
 * Template: Coach Registration - V1 (A/B Test Variant)
 *
 * Split-screen layout with WFEB-branded left panel and registration form on the right.
 * Standalone full-page template that bypasses the WordPress theme.
 *
 * @package WWFEB
 * @since   2.2.3
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
$coach_login_url     = '';
$coach_login_page_id = get_option( 'wfeb_coach_login_page_id' );
if ( $coach_login_page_id ) {
	$coach_login_url = get_permalink( $coach_login_page_id );
}

$logo_url  = WFEB_PLUGIN_URL . 'assets/images/LOGO TRANSPARENT.png';
$css_url   = WFEB_PLUGIN_URL . 'assets/css/frontend.css';
$v1_css    = WFEB_PLUGIN_URL . 'assets/css/registration-v1.css';
$js_url    = WFEB_PLUGIN_URL . 'assets/js/frontend.js';
$ajax_url  = admin_url( 'admin-ajax.php' );
$nonce     = wp_create_nonce( 'wfeb_frontend_nonce' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html__( 'Register as an Examiner - WFEB', 'wfeb' ); ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( WFEB_VERSION ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( $v1_css ); ?>?ver=<?php echo esc_attr( WFEB_VERSION ); ?>">
	<?php wp_head(); ?>
</head>
<body class="wfeb-page wfeb-reg-v1-body">

	<div class="wfeb-reg-v1">

		<!-- ============================================================
		     LEFT PANEL - Branded
		     ============================================================ -->
		<aside class="wfeb-reg-v1__panel">

			<div class="wfeb-reg-v1__panel-inner">

				<!-- Logo -->
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wfeb-reg-v1__logo">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB Logo', 'wfeb' ); ?>">
				</a>

				<!-- Headline -->
				<h1 class="wfeb-reg-v1__headline">
					<?php echo esc_html__( 'Become a certified WFEB examiner', 'wfeb' ); ?>
				</h1>
				<p class="wfeb-reg-v1__tagline">
					<?php echo esc_html__( 'Run official football skills exams, issue certificates to players, and build your coaching profile.', 'wfeb' ); ?>
				</p>

				<!-- Benefits -->
				<ul class="wfeb-reg-v1__benefits">
					<li>
						<span class="wfeb-reg-v1__benefit-icon">
							<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						<span><?php echo esc_html__( 'Run the 7-skill exam at your club, school or academy', 'wfeb' ); ?></span>
					</li>
					<li>
						<span class="wfeb-reg-v1__benefit-icon">
							<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						<span><?php echo esc_html__( 'Issue official PDF certificates instantly', 'wfeb' ); ?></span>
					</li>
					<li>
						<span class="wfeb-reg-v1__benefit-icon">
							<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						<span><?php echo esc_html__( 'Results saved on the WFEB database for verification', 'wfeb' ); ?></span>
					</li>
					<li>
						<span class="wfeb-reg-v1__benefit-icon">
							<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
						</span>
						<span><?php echo esc_html__( 'Track player progress over time with repeat exams', 'wfeb' ); ?></span>
					</li>
				</ul>

				<!-- Testimonial -->
				<blockquote class="wfeb-reg-v1__quote">
					<p><?php echo esc_html__( '"I use the WFEB tests to focus my players and give them individual targets."', 'wfeb' ); ?></p>
					<footer>
						<strong><?php echo esc_html__( 'Sarah', 'wfeb' ); ?></strong>
						<span><?php echo esc_html__( 'Club Coach', 'wfeb' ); ?></span>
					</footer>
				</blockquote>

				<!-- Trust badges -->
				<div class="wfeb-reg-v1__trust">
					<div class="wfeb-reg-v1__trust-item">
						<span class="wfeb-reg-v1__trust-number">7</span>
						<span class="wfeb-reg-v1__trust-label"><?php echo esc_html__( 'Skill categories', 'wfeb' ); ?></span>
					</div>
					<div class="wfeb-reg-v1__trust-divider"></div>
					<div class="wfeb-reg-v1__trust-item">
						<span class="wfeb-reg-v1__trust-number">80</span>
						<span class="wfeb-reg-v1__trust-label"><?php echo esc_html__( 'Max score', 'wfeb' ); ?></span>
					</div>
					<div class="wfeb-reg-v1__trust-divider"></div>
					<div class="wfeb-reg-v1__trust-item">
						<span class="wfeb-reg-v1__trust-number">PDF</span>
						<span class="wfeb-reg-v1__trust-label"><?php echo esc_html__( 'Certificates', 'wfeb' ); ?></span>
					</div>
				</div>

			</div><!-- .wfeb-reg-v1__panel-inner -->

			<div class="wfeb-reg-v1__panel-footer">
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php echo esc_html__( 'Privacy Policy', 'wfeb' ); ?></a>
				<span class="wfeb-reg-v1__panel-footer-dot"></span>
				<a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>"><?php echo esc_html__( 'Terms & Conditions', 'wfeb' ); ?></a>
			</div>

		</aside>

		<!-- ============================================================
		     RIGHT SIDE - Registration Form
		     ============================================================ -->
		<main class="wfeb-reg-v1__main">

			<div class="wfeb-reg-v1__form-wrap">

				<!-- Mobile logo (hidden on desktop) -->
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wfeb-reg-v1__mobile-logo">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB Logo', 'wfeb' ); ?>">
				</a>

				<div class="wfeb-reg-v1__form-card">

				<div class="wfeb-reg-v1__form-header">
					<h2 class="wfeb-reg-v1__form-title"><?php echo esc_html__( 'Create your examiner account', 'wfeb' ); ?></h2>
					<p class="wfeb-reg-v1__form-subtitle"><?php echo esc_html__( 'Fill in your details below. Approval is typically within 24 hours.', 'wfeb' ); ?></p>
				</div>

				<div id="wfeb-register-notice" class="wfeb-notice" style="display:none;"></div>

				<form id="wfeb-coach-register-form" class="wfeb-form" method="post" enctype="multipart/form-data" novalidate>

					<input type="hidden" name="action" value="wfeb_coach_register">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

					<!-- Section: Personal Details -->
					<fieldset class="wfeb-reg-v1__fieldset">
						<legend class="wfeb-reg-v1__legend"><?php echo esc_html__( 'Personal details', 'wfeb' ); ?></legend>

						<!-- Full Name -->
						<div class="wfeb-form-group">
							<label class="wfeb-form-label" for="wfeb-reg-fullname">
								<?php echo esc_html__( 'Full Name', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="wfeb-reg-fullname" name="full_name" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'John Smith', 'wfeb' ); ?>" required autocomplete="name">
						</div>

						<!-- DOB & Email -->
						<div class="wfeb-form-row">
							<div class="wfeb-form-group">
								<label class="wfeb-form-label" for="wfeb-reg-dob">
									<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
									<span class="required">*</span>
								</label>
								<input type="date" id="wfeb-reg-dob" name="dob" class="wfeb-form-input" required>
							</div>
							<div class="wfeb-form-group">
								<label class="wfeb-form-label" for="wfeb-reg-email">
									<?php echo esc_html__( 'Email', 'wfeb' ); ?>
									<span class="required">*</span>
								</label>
								<input type="email" id="wfeb-reg-email" name="email" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'you@example.com', 'wfeb' ); ?>" required autocomplete="email">
							</div>
						</div>

						<!-- Phone -->
						<div class="wfeb-form-group">
							<label class="wfeb-form-label" for="wfeb-reg-phone">
								<?php echo esc_html__( 'Phone', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<input type="tel" id="wfeb-reg-phone" name="phone" class="wfeb-form-input" placeholder="<?php echo esc_attr__( '+44 7700 900000', 'wfeb' ); ?>" required autocomplete="tel">
						</div>

						<!-- Address & Country -->
						<div class="wfeb-form-group">
							<label class="wfeb-form-label" for="wfeb-reg-address">
								<?php echo esc_html__( 'Address', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<textarea id="wfeb-reg-address" name="address" class="wfeb-form-input" rows="2" placeholder="<?php echo esc_attr__( 'Full postal address', 'wfeb' ); ?>" required></textarea>
						</div>

						<div class="wfeb-form-group">
							<label class="wfeb-form-label" for="wfeb-reg-country">
								<?php echo esc_html__( 'Country', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<select id="wfeb-reg-country" name="country" class="wfeb-form-input" required>
								<?php foreach ( wfeb_get_countries() as $c ) : ?>
									<option value="<?php echo esc_attr( $c ); ?>"<?php selected( $c, 'United Kingdom' ); ?>>
										<?php echo esc_html( $c ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</fieldset>

					<!-- Section: Professional Details -->
					<fieldset class="wfeb-reg-v1__fieldset">
						<legend class="wfeb-reg-v1__legend"><?php echo esc_html__( 'Professional details', 'wfeb' ); ?></legend>

						<!-- NGB Number -->
						<div class="wfeb-form-group">
							<label class="wfeb-form-label" for="wfeb-reg-ngb">
								<?php echo esc_html__( 'NGB Number', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="wfeb-reg-ngb" name="ngb_number" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'e.g. FA12345678', 'wfeb' ); ?>" required>
							<span class="wfeb-form-hint"><?php echo esc_html__( 'National Governing Body registration number', 'wfeb' ); ?></span>
						</div>

						<!-- Certificate Upload -->
						<div class="wfeb-form-group">
							<label class="wfeb-form-label">
								<?php echo esc_html__( 'Coaching Certificate', 'wfeb' ); ?>
								<span class="required">*</span>
							</label>
							<label class="wfeb-form-file" id="wfeb-cert-dropzone">
								<div class="wfeb-form-file__icon">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 3.75 3.75 0 013.572 5.345A3.75 3.75 0 0118 19.5H6.75z"/>
									</svg>
								</div>
								<div class="wfeb-form-file__text">
									<strong><?php echo esc_html__( 'Click to upload', 'wfeb' ); ?></strong> <?php echo esc_html__( 'or drag and drop', 'wfeb' ); ?>
								</div>
								<div class="wfeb-form-file__hint"><?php echo esc_html__( 'PDF, JPG, JPEG or PNG (max 5MB)', 'wfeb' ); ?></div>
								<input type="file" id="wfeb-reg-certificate" name="coaching_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
								<div class="wfeb-form-file__preview" id="wfeb-cert-preview"></div>
							</label>
						</div>
					</fieldset>

					<!-- Section: Account Security -->
					<fieldset class="wfeb-reg-v1__fieldset">
						<legend class="wfeb-reg-v1__legend"><?php echo esc_html__( 'Account security', 'wfeb' ); ?></legend>

						<div class="wfeb-form-row">
							<div class="wfeb-form-group">
								<label class="wfeb-form-label" for="wfeb-reg-password">
									<?php echo esc_html__( 'Password', 'wfeb' ); ?>
									<span class="required">*</span>
								</label>
								<div class="wfeb-password-wrap">
									<input type="password" id="wfeb-reg-password" name="password" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'Min 8 characters', 'wfeb' ); ?>" required autocomplete="new-password" minlength="8">
									<button type="button" class="wfeb-password-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"></button>
								</div>
								<div id="wfeb-password-strength" class="wfeb-form-hint"></div>
							</div>
							<div class="wfeb-form-group">
								<label class="wfeb-form-label" for="wfeb-reg-password-confirm">
									<?php echo esc_html__( 'Confirm Password', 'wfeb' ); ?>
									<span class="required">*</span>
								</label>
								<div class="wfeb-password-wrap">
									<input type="password" id="wfeb-reg-password-confirm" name="password_confirm" class="wfeb-form-input" placeholder="<?php echo esc_attr__( 'Re-enter password', 'wfeb' ); ?>" required autocomplete="new-password">
									<button type="button" class="wfeb-password-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"></button>
								</div>
							</div>
						</div>
					</fieldset>

					<!-- Agreements -->
					<div class="wfeb-reg-v1__agreements">
						<div class="wfeb-form-group">
							<label class="wfeb-checkbox-group">
								<input type="checkbox" id="wfeb-reg-terms" name="terms" value="1" required>
								<span class="wfeb-checkbox-mark"></span>
								<span class="wfeb-checkbox-label">
									<?php echo esc_html__( 'I agree that my name will appear on certificates permanently and consent to WFEB storing my contact details.', 'wfeb' ); ?>
								</span>
							</label>
						</div>

						<div class="wfeb-form-group">
							<label class="wfeb-checkbox-group">
								<input type="checkbox" id="wfeb-reg-privacy" name="privacy" value="1" required>
								<span class="wfeb-checkbox-mark"></span>
								<span class="wfeb-checkbox-label">
									<?php
									printf(
										/* translators: %1$s: privacy policy link, %2$s: terms link */
										esc_html__( 'I have read and agree to the %1$s and %2$s.', 'wfeb' ),
										'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy Policy', 'wfeb' ) . '</a>',
										'<a href="' . esc_url( home_url( '/terms-and-conditions/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Terms &amp; Conditions', 'wfeb' ) . '</a>'
									);
									?>
								</span>
							</label>
						</div>
					</div>

					<!-- Submit -->
					<div class="wfeb-form-group wfeb-reg-v1__submit-group">
						<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--full wfeb-reg-v1__submit">
							<?php echo esc_html__( 'Create Examiner Account', 'wfeb' ); ?>
						</button>
					</div>

				</form>

				<!-- Login link -->
				<?php if ( $coach_login_url ) : ?>
					<div class="wfeb-auth-links">
						<?php echo esc_html__( 'Already have an account?', 'wfeb' ); ?>
						<a href="<?php echo esc_url( $coach_login_url ); ?>">
							<?php echo esc_html__( 'Sign in', 'wfeb' ); ?>
						</a>
					</div>
				<?php endif; ?>

			</div><!-- .wfeb-reg-v1__form-card -->

			</div><!-- .wfeb-reg-v1__form-wrap -->

		</main>

	</div><!-- .wfeb-reg-v1 -->

	<?php wp_footer(); ?>
</body>
</html>
