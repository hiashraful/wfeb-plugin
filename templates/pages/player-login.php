<?php
/**
 * Template: Player Login
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

// Redirect if already logged in as player.
if ( is_user_logged_in() && wfeb_is_player() ) {
	$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
	if ( $dashboard_page_id ) {
		wp_safe_redirect( get_permalink( $dashboard_page_id ) );
		exit;
	}
}

// Page URLs.
$forgot_password_url = '';

$forgot_password_page_id = get_option( 'wfeb_forgot_password_page_id' );
if ( $forgot_password_page_id ) {
	$forgot_password_url = get_permalink( $forgot_password_page_id );
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
	<title><?php echo esc_html__( 'Player Login - WFEB', 'wfeb' ); ?></title>
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

			<h2 class="wfeb-auth-title"><?php echo esc_html__( 'Player Login', 'wfeb' ); ?></h2>
			<p class="wfeb-auth-subtitle"><?php echo esc_html__( 'View your certificates and scores', 'wfeb' ); ?></p>

			<div id="wfeb-login-notice" class="wfeb-notice" style="display:none;"></div>

			<form id="wfeb-player-login-form" class="wfeb-form" method="post" novalidate>

				<input type="hidden" name="action" value="wfeb_player_login">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-login-email">
						<?php echo esc_html__( 'Email', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="email"
						id="wfeb-login-email"
						name="email"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'you@example.com', 'wfeb' ); ?>"
						required
						autocomplete="email"
					>
				</div>

				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-login-password">
						<?php echo esc_html__( 'Password', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<div class="wfeb-password-wrap">
						<input
							type="password"
							id="wfeb-login-password"
							name="password"
							class="wfeb-form-input"
							placeholder="<?php echo esc_attr__( 'Enter your password', 'wfeb' ); ?>"
							required
							autocomplete="current-password"
						>
						<button
							type="button"
							class="wfeb-password-toggle"
							aria-pressed="false"
							aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'wfeb' ); ?>"
						></button>
					</div>
				</div>

				<?php if ( $forgot_password_url ) : ?>
					<div class="wfeb-form-group wfeb-text-center">
						<a href="<?php echo esc_url( $forgot_password_url ); ?>" style="font-size:14px;color:var(--wfeb-accent);text-decoration:none;">
							<?php echo esc_html__( 'Forgot password?', 'wfeb' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<div class="wfeb-form-group">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--full">
						<?php echo esc_html__( 'Sign In', 'wfeb' ); ?>
					</button>
				</div>

			</form>

			<div class="wfeb-notice wfeb-notice--info wfeb-mt-16">
				<p><?php echo esc_html__( 'Your account was created by your coach. Check your email for login credentials.', 'wfeb' ); ?></p>
			</div>

		</div><!-- .wfeb-auth-card -->

	</div><!-- .wfeb-auth-container -->

	<?php wp_footer(); ?>
</body>
</html>
