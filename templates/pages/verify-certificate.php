<?php
/**
 * Template: Verify Certificate
 *
 * Standalone full-page template that bypasses the WordPress theme.
 * Public certificate verification page.
 * Outputs its own DOCTYPE, html, head, and body tags.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url    = WFEB_PLUGIN_URL . 'assets/images/LOGO TRANSPARENT.png';
$css_url     = WFEB_PLUGIN_URL . 'assets/css/frontend.css';
$js_url      = WFEB_PLUGIN_URL . 'assets/js/frontend.js';
$ajax_url    = admin_url( 'admin-ajax.php' );
$nonce       = wp_create_nonce( 'wfeb_frontend_nonce' );
$home_url    = home_url( '/' );

// QR auto-verify params.
$auto_cert = isset( $_GET['cert'] ) ? sanitize_text_field( wp_unslash( $_GET['cert'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$auto_sig  = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html__( 'Verify Certificate - WFEB', 'wfeb' ); ?></title>
	<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( WFEB_VERSION ); ?>">
	<?php wp_head(); ?>
</head>
<body class="wfeb-page">

	<div class="wfeb-verify-container">

		<div class="wfeb-auth-logo">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'WFEB Logo', 'wfeb' ); ?>">
		</div>

		<div class="wfeb-auth-card wfeb-auth-card--wide">

			<h2 class="wfeb-auth-title"><?php echo esc_html__( 'Verify Certificate', 'wfeb' ); ?></h2>
			<p class="wfeb-auth-subtitle"><?php echo esc_html__( 'Enter the certificate details to verify its authenticity', 'wfeb' ); ?></p>

			<div id="wfeb-verify-notice" class="wfeb-notice" style="display:none;"></div>

			<form id="wfeb-verify-form" class="wfeb-form" method="post" novalidate>

				<input type="hidden" name="action" value="wfeb_verify_certificate">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<!-- Player Name -->
				<div class="wfeb-form-group">
					<label class="wfeb-form-label" for="wfeb-verify-name">
						<?php echo esc_html__( 'Player Name', 'wfeb' ); ?>
						<span class="required">*</span>
					</label>
					<input
						type="text"
						id="wfeb-verify-name"
						name="player_name"
						class="wfeb-form-input"
						placeholder="<?php echo esc_attr__( 'Full name as shown on certificate', 'wfeb' ); ?>"
						required
					>
				</div>

				<!-- Certificate Number & Date of Birth row -->
				<div class="wfeb-form-row">
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-verify-cert-number">
							<?php echo esc_html__( 'Certificate Number', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="text"
							id="wfeb-verify-cert-number"
							name="certificate_number"
							class="wfeb-form-input"
							placeholder="<?php echo esc_attr__( 'e.g. WFEB-1001', 'wfeb' ); ?>"
							required
						>
					</div>
					<div class="wfeb-form-group">
						<label class="wfeb-form-label" for="wfeb-verify-dob">
							<?php echo esc_html__( 'Date of Birth', 'wfeb' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="date"
							id="wfeb-verify-dob"
							name="dob"
							class="wfeb-form-input"
							required
						>
					</div>
				</div>

				<!-- Submit -->
				<div class="wfeb-form-group">
					<button type="submit" class="wfeb-btn wfeb-btn--primary wfeb-btn--full">
						<?php echo esc_html__( 'Verify Certificate', 'wfeb' ); ?>
					</button>
				</div>

			</form>

		</div><!-- .wfeb-auth-card -->

		<!-- Results Area (hidden by default, shown via JS) -->
		<div id="wfeb-verify-results" style="display:none;width:100%;">

			<!-- Success Result -->
			<div id="wfeb-verify-found" class="wfeb-verify-result wfeb-verify-result--found" style="display:none;">
				<h3 class="wfeb-verify-result__title"><?php echo esc_html__( 'Certificate Verified', 'wfeb' ); ?></h3>

				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'NAME', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value" id="wfeb-result-name">--</span>
				</div>
				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'SCORE', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value" id="wfeb-result-score">--</span>
				</div>
				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'DATE', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value" id="wfeb-result-date">--</span>
				</div>
				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'CERT#', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value" id="wfeb-result-cert">--</span>
				</div>
				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'EXAMINER', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value" id="wfeb-result-examiner">--</span>
				</div>
				<div class="wfeb-verify-field">
					<span class="wfeb-verify-field__label"><?php echo esc_html__( 'ACHIEVEMENT', 'wfeb' ); ?></span>
					<span class="wfeb-verify-field__value">
						<span class="wfeb-verify-badge" id="wfeb-result-badge">--</span>
					</span>
				</div>
			</div>

			<!-- Error Result -->
			<div id="wfeb-verify-not-found" class="wfeb-verify-result wfeb-verify-result--not-found" style="display:none;">
				<h3 class="wfeb-verify-result__title"><?php echo esc_html__( 'Certificate Not Found', 'wfeb' ); ?></h3>
				<p class="wfeb-text-center wfeb-text-muted">
					<?php echo esc_html__( 'The details provided do not match any certificate in our records.', 'wfeb' ); ?>
				</p>
			</div>

		</div><!-- #wfeb-verify-results -->

		<div class="wfeb-auth-links">
			<a href="<?php echo esc_url( $home_url ); ?>">
				<?php echo esc_html__( 'Back to Home', 'wfeb' ); ?>
			</a>
		</div>

	</div><!-- .wfeb-verify-container -->

	<?php if ( ! empty( $auto_cert ) && ! empty( $auto_sig ) ) : ?>
	<script>
	var wfebAutoVerify = {
		cert: <?php echo wp_json_encode( $auto_cert ); ?>,
		sig: <?php echo wp_json_encode( $auto_sig ); ?>,
		ajax_url: <?php echo wp_json_encode( $ajax_url ); ?>,
		nonce: <?php echo wp_json_encode( $nonce ); ?>
	};
	</script>
	<?php endif; ?>

	<?php wp_footer(); ?>
</body>
</html>
