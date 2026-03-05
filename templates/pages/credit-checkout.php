<?php
/**
 * Template: Credit Checkout Page
 *
 * Standalone full-page checkout styled in two-column layout.
 * Loaded for the page set at wfeb_credit_checkout_page_id.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require WooCommerce.
if ( ! function_exists( 'WC' ) ) {
	wp_die( esc_html__( 'WooCommerce is required for this page.', 'wfeb' ) );
}

// Require logged-in approved coach.
if ( ! is_user_logged_in() || ! wfeb_is_coach() ) {
	$login_page_id = get_option( 'wfeb_coach_login_page_id' );
	$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url();
	wp_safe_redirect( $login_url );
	exit;
}

// Redirect back to buy-credits if cart is empty.
if ( WC()->cart->is_empty() ) {
	$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
	$buy_url           = add_query_arg( 'section', 'buy-credits', get_permalink( $dashboard_page_id ) );
	wp_safe_redirect( $buy_url );
	exit;
}

// Calculate cart totals.
WC()->cart->calculate_totals();

// Get the single credit cart item.
$cart_items   = WC()->cart->get_cart();
$cart_item    = reset( $cart_items );
$quantity     = $cart_item ? $cart_item['quantity'] : 1;
$product_id   = $cart_item ? $cart_item['product_id'] : 0;
$product      = $product_id ? wc_get_product( $product_id ) : null;
$product_name = $product ? $product->get_name() : __( 'Certificate Credits', 'wfeb' );
$unit_price      = $product ? (float) $product->get_price() : 1.00;
$line_total      = $unit_price * $quantity;
$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';

// Formatted total (includes currency symbol and tax).
$cart_total_f = WC()->cart->get_total();

// Pre-fill billing from coach profile.
$coach        = WFEB()->coach_dashboard->get_coach_data();
$full_name    = $coach ? $coach->full_name : '';
$name_parts   = explode( ' ', $full_name, 2 );
$first_name   = isset( $name_parts[0] ) ? $name_parts[0] : '';
$last_name    = isset( $name_parts[1] ) ? $name_parts[1] : '';
$coach_email  = $coach ? $coach->email : '';
// Country defaults to GB (sent as hidden field).
$coach_country = 'GB';

// Back URL: returns to buy-credits section.
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$back_url = add_query_arg( 'section', 'buy-credits', get_permalink( $dashboard_page_id ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Complete Your Purchase', 'wfeb' ); ?> &mdash; <?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="wfeb-credit-checkout-page">

<div class="wfeb-cco-container">
	<div class="wfeb-cco-card">

		<form name="checkout" id="wfeb-cco-form" method="post"
			action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
			class="checkout woocommerce-checkout">

			<div class="wfeb-cco-columns">

				<!-- LEFT: Order Summary -->
				<div class="wfeb-cco-left">

					<a href="<?php echo esc_url( $back_url ); ?>" class="wfeb-cco-back-link">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php esc_html_e( 'Change quantity', 'wfeb' ); ?>
					</a>

					<p class="wfeb-cco-summary-title"><?php esc_html_e( 'Order Summary', 'wfeb' ); ?></p>

					<!-- Credit item -->
					<div class="wfeb-cco-item">
						<div class="wfeb-cco-item-icon">
							<span class="dashicons dashicons-awards"></span>
						</div>
						<div class="wfeb-cco-item-details">
							<div class="wfeb-cco-item-name">
								<?php echo esc_html( $quantity ); ?>
								&times;
								<?php echo esc_html( $product_name ); ?>
							</div>
							<div class="wfeb-cco-item-desc">
								<?php esc_html_e( '1 credit = 1 player exam & certificate', 'wfeb' ); ?>
							</div>
						</div>
						<div class="wfeb-cco-item-price">
							<?php echo esc_html( $currency_symbol . number_format( $line_total, 2 ) ); ?>
						</div>
					</div>

					<!-- Totals -->
					<div class="wfeb-cco-totals">
						<div class="wfeb-cco-total-row">
							<span><?php esc_html_e( 'Subtotal', 'wfeb' ); ?></span>
							<span><?php echo esc_html( $currency_symbol . number_format( $line_total, 2 ) ); ?></span>
						</div>
						<div class="wfeb-cco-total-row is-grand">
							<span><?php esc_html_e( 'Total', 'wfeb' ); ?></span>
							<span><?php echo wp_kses_post( $cart_total_f ); ?></span>
						</div>
					</div>

				</div><!-- /.wfeb-cco-left -->

				<!-- RIGHT: Billing + Payment -->
				<div class="wfeb-cco-right">

					<!-- Billing Details -->
					<div>
						<p class="wfeb-cco-section-title"><?php esc_html_e( 'Billing Details', 'wfeb' ); ?></p>

						<div class="wfeb-cco-form-row">
							<div class="wfeb-cco-field">
								<label class="wfeb-cco-label" for="billing_first_name">
									<?php esc_html_e( 'First Name', 'wfeb' ); ?>
								</label>
								<input type="text" id="billing_first_name" name="billing_first_name"
									class="wfeb-cco-input"
									value="<?php echo esc_attr( $first_name ); ?>"
									required />
							</div>
							<div class="wfeb-cco-field">
								<label class="wfeb-cco-label" for="billing_last_name">
									<?php esc_html_e( 'Last Name', 'wfeb' ); ?>
								</label>
								<input type="text" id="billing_last_name" name="billing_last_name"
									class="wfeb-cco-input"
									value="<?php echo esc_attr( $last_name ); ?>"
									required />
							</div>
						</div>

						<div class="wfeb-cco-form-row is-full">
							<div class="wfeb-cco-field">
								<label class="wfeb-cco-label" for="billing_email">
									<?php esc_html_e( 'Email Address', 'wfeb' ); ?>
								</label>
								<input type="email" id="billing_email" name="billing_email"
									class="wfeb-cco-input"
									value="<?php echo esc_attr( $coach_email ); ?>"
									required />
							</div>
						</div>

						<!-- Hidden required WooCommerce billing fields (not collected in this UI). -->
						<input type="hidden" name="billing_country" value="<?php echo esc_attr( $coach_country ); ?>" />
						<input type="hidden" name="billing_address_1" value="N/A" />
						<input type="hidden" name="billing_address_2" value="" />
						<input type="hidden" name="billing_city" value="N/A" />
						<input type="hidden" name="billing_state" value="" />
						<input type="hidden" name="billing_postcode" value="00000" />
						<input type="hidden" name="billing_phone" value="0000000000" />
						<input type="hidden" name="shipping_method[0]" value="" />
						<input type="hidden" name="ship_to_different_address" value="0" />
						<input type="hidden" name="order_comments" value="" />
					</div>

					<!-- WooCommerce Payment Methods -->
					<div>
						<p class="wfeb-cco-section-title"><?php esc_html_e( 'Payment Method', 'wfeb' ); ?></p>
						<div id="order_review">
							<?php
							// WC_Checkout must be instantiated to register its hooks before
							// the payment template is rendered. do_action() alone outputs
							// nothing because WC_Checkout attaches its listener in its constructor.
							$wc_checkout = WC()->checkout();
							wc_get_template(
								'checkout/payment.php',
								array( 'checkout' => $wc_checkout )
							);
							?>
						</div>
					</div>

					<!-- Submit -->
					<!-- Note: woocommerce-process-checkout-nonce is output by checkout/payment.php above -->
					<div>
						<button type="submit" id="wfeb-cco-pay-btn" class="wfeb-cco-pay-btn">
							<span class="wfeb-cco-pay-btn__text"><?php
							printf(
								/* translators: %s: formatted total price */
								esc_html__( 'Pay %s', 'wfeb' ),
								wp_kses_post( $cart_total_f )
							);
							?></span>
							<span class="wfeb-cco-spinner"><?php for ( $i = 0; $i < 12; $i++ ) : ?><span class="wfeb-blade"></span><?php endfor; ?></span>
						</button>
						<p class="wfeb-cco-security-note" style="margin-top: 12px;">
							<span class="dashicons dashicons-lock" style="font-size:14px;width:14px;height:14px;line-height:1.1"></span>
							<?php esc_html_e( 'Secure payment powered by WooCommerce', 'wfeb' ); ?>
						</p>
					</div>

				</div><!-- /.wfeb-cco-right -->

			</div><!-- /.wfeb-cco-columns -->

		</form>

		<div class="wfeb-cco-footer">
			<p class="wfeb-cco-footer-copy">
				&copy; 2026 &mdash; <?php esc_html_e( 'World Football Examination Board', 'wfeb' ); ?>
			</p>
			<nav class="wfeb-cco-footer-links">
				<?php
				$privacy_id = get_option( 'wp_page_for_privacy_policy' );
				$privacy_url = $privacy_id ? get_permalink( $privacy_id ) : '#';
				?>
				<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy Policy', 'wfeb' ); ?></a>
				<span class="wfeb-cco-footer-sep">&middot;</span>
				<a href="#"><?php esc_html_e( 'Terms &amp; Conditions', 'wfeb' ); ?></a>
				<span class="wfeb-cco-footer-sep">&middot;</span>
				<a href="#"><?php esc_html_e( 'Refund Policy', 'wfeb' ); ?></a>
			</nav>
		</div>

	</div><!-- /.wfeb-cco-card -->
</div><!-- /.wfeb-cco-container -->

<?php wp_footer(); ?>
</body>
</html>
