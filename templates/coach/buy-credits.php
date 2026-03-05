<?php
/**
 * Template: Coach Dashboard - Buy Credits Section
 *
 * Simple quantity selector for purchasing certificate credits.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get credit product and price.
$product_id      = absint( get_option( 'wfeb_credit_product_id', 0 ) );
$credit_price    = 1.00; // fallback
$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';

if ( $product_id && function_exists( 'wc_get_product' ) ) {
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$credit_price = (float) $product->get_price();
	}
}

// Dashboard base URL (for back link).
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$credits_url       = add_query_arg( 'section', 'credits', get_permalink( $dashboard_page_id ) );

// Checkout page URL.
$checkout_page_id = absint( get_option( 'wfeb_credit_checkout_page_id', 0 ) );
$checkout_url     = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
?>

<div class="wfeb-buy-credits-wrap">

	<!-- Back Link -->
	<a href="<?php echo esc_url( $credits_url ); ?>" class="wfeb-back-link">
		<span class="dashicons dashicons-arrow-left-alt"></span>
		<?php esc_html_e( 'Back to Credits', 'wfeb' ); ?>
	</a>

	<!-- Buyer Card -->
	<div class="wfeb-buy-credits-card">

		<!-- Header -->
		<div class="wfeb-buy-credits-header">
			<div class="wfeb-buy-credits-icon">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="wfeb-buy-credits-info">
				<h2><?php esc_html_e( 'Certificate Credits', 'wfeb' ); ?></h2>
				<p><?php esc_html_e( 'Each credit allows you to conduct one player exam and generate a certificate.', 'wfeb' ); ?></p>
			</div>
		</div>

		<!-- Price Badge -->
		<div class="wfeb-buy-credits-price-row">
			<div class="wfeb-buy-credits-price-badge">
				<span class="wfeb-price-value"><?php echo esc_html( $currency_symbol . number_format( $credit_price, 2 ) ); ?></span>
				<span class="wfeb-price-unit"><?php esc_html_e( 'per credit', 'wfeb' ); ?></span>
			</div>
		</div>

		<!-- Quantity Selector -->
		<div class="wfeb-qty-selector">
			<button type="button"
				class="wfeb-qty-btn wfeb-qty-minus"
				aria-label="<?php esc_attr_e( 'Decrease quantity', 'wfeb' ); ?>">
				<span class="dashicons dashicons-minus"></span>
			</button>
			<input
				type="number"
				id="wfeb-credit-qty"
				class="wfeb-qty-input"
				value="1"
				min="1"
				max="200"
				data-price="<?php echo esc_attr( $credit_price ); ?>"
				aria-label="<?php esc_attr_e( 'Number of credits', 'wfeb' ); ?>"
			/>
			<button type="button"
				class="wfeb-qty-btn wfeb-qty-plus"
				aria-label="<?php esc_attr_e( 'Increase quantity', 'wfeb' ); ?>">
				<span class="dashicons dashicons-plus-alt2"></span>
			</button>
		</div>

		<!-- Live Summary -->
		<div class="wfeb-buy-credits-summary">
			<span id="wfeb-credits-summary-line">
				1 <?php esc_html_e( 'credit', 'wfeb' ); ?>
				&times; <?php echo esc_html( $currency_symbol . number_format( $credit_price, 2 ) ); ?>
			</span>
			<span class="wfeb-buy-credits-total-row">
				<?php esc_html_e( 'Total', 'wfeb' ); ?>
				<strong id="wfeb-credits-total-amount">
					<?php echo esc_html( $currency_symbol . number_format( $credit_price, 2 ) ); ?>
				</strong>
			</span>
		</div>

		<!-- Pay Now Form -->
		<?php if ( ! $checkout_url ) : ?>
			<div class="wfeb-alert wfeb-alert--warning">
				<span class="dashicons dashicons-warning"></span>
				<p><?php esc_html_e( 'Checkout page not configured. Please contact the administrator.', 'wfeb' ); ?></p>
			</div>
		<?php else : ?>
			<form id="wfeb-buy-credits-form">
				<?php wp_nonce_field( 'wfeb_buy_credits_nonce', 'wfeb_buy_credits_nonce' ); ?>
				<input type="hidden" name="action" value="wfeb_setup_credit_cart" />
				<input type="hidden" name="product_id" value="<?php echo absint( $product_id ); ?>" />
				<input type="hidden" id="wfeb-buy-credits-qty-hidden" name="quantity" value="1" />
				<input type="hidden" name="checkout_url" value="<?php echo esc_url( $checkout_url ); ?>" />
				<button type="submit" id="wfeb-pay-now-btn" class="wfeb-btn wfeb-btn--primary wfeb-btn--lg wfeb-btn--full">
					<?php esc_html_e( 'Pay Now', 'wfeb' ); ?>
				</button>
			</form>
		<?php endif; ?>

	</div><!-- /.wfeb-buy-credits-card -->

</div><!-- /.wfeb-buy-credits-wrap -->
