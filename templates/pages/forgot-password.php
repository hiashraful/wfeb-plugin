<?php
/**
 * Template: Forgot Password
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

// Page URLs.
$coach_login_url  = '';
$player_login_url = '';

$coach_login_page_id = get_option( 'wfeb_coach_login_page_id' );
if ( $coach_login_page_id ) {
	$coach_login_url = get_permalink( $coach_login_page_id );
}

$player_login_page_id = get_option( 'wfeb_player_login_page_id' );
if ( $player_login_page_id ) {
	$player_login_url = get_permalink( $player_login_page_id );
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
	<title><?php echo esc_html__( 'Reset Password - WFEB', 'wfeb' ); ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( WFEB_VERSION ); ?>">
	<?php wp_head(); ?>
</head>
<body class="wfeb-page">

	<div class="wfeb-auth-container">

		<div class="wfeb-auth-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB Logo', 'wfeb' ); ?>">
		</div>

		<div class="wfeb-auth-card">

			<h2 class="wfeb-auth-title"><?php echo esc_html__( 'Reset Password', 'wfeb' ); ?></h2>
			<p class="wfeb-auth-subtitle"><?php echo esc_html__( "Enter your email address and we'll send you a reset link", 'wfeb' ); ?></p>

			<div id="wfeb-forgot-notice" class="wfeb-notice" style="display:none;"></div>

			<form id="wfeb-forgot-password-form" class="wfeb-form" method="post" novalidate>

				<input type="hidden" name="action" value="wfeb_forgot_password">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-forgot-email">
						<?php echo esc_html__( 'Email', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="email"
						id="wfeb-forgot-email"
						name="email"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'you@example.com', 'wfeb' ); ?>"
						required
						autocomplete="email"
					>
				</div>

				<div class="wfeb-form-group">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--full">
						<?php echo esc_html__( 'Send Reset Link', 'wfeb' ); ?>
					</button>
				</div>

			</form>

			<!-- Success state (shown by JS after successful submission) -->
			<div id="wfeb-reset-success" class="wfeb-notice wfeb-notice--success" style="display:none;">
				<p><?php echo esc_html__( 'Check your email for a password reset link.', 'wfeb' ); ?></p>
			</div>

		</div><!-- .wfeb-auth-card -->

		<div class="wfeb-auth-links">
			<?php if ( $coach_login_url ) : ?>
				<a href="<?php echo esc_url( $coach_login_url ); ?>">
					<?php echo esc_html__( 'Back to Coach Login', 'wfeb' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $coach_login_url && $player_login_url ) : ?>
				<span class="wfeb-auth-links__divider"></span>
			<?php endif; ?>

			<?php if ( $player_login_url ) : ?>
				<a href="<?php echo esc_url( $player_login_url ); ?>">
					<?php echo esc_html__( 'Back to Player Login', 'wfeb' ); ?>
				</a>
			<?php endif; ?>
		</div>

	</div><!-- .wfeb-auth-container -->

	<?php wp_footer(); ?>
</body>
</html>
