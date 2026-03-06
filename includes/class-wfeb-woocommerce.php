<?php
/**
 * WFEB WooCommerce Integration
 *
 * Handles certificate credit purchases via WooCommerce including
 * product creation, order processing, refunds, and cart URLs.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_WooCommerce
 *
 * Integrates WFEB credit purchases with WooCommerce order flow.
 */
class WFEB_WooCommerce {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @var bool
	 */
	private $wc_active = false;

	/**
	 * Constructor.
	 *
	 * Checks if WooCommerce is active and registers order hooks.
	 */
	public function __construct() {
		$this->wc_active = $this->check_woocommerce_active();

		if ( ! $this->wc_active ) {
			return;
		}

		add_action( 'woocommerce_order_status_completed', array( $this, 'process_order' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'process_refund' ) );
		add_action( 'woocommerce_before_add_to_cart_quantity', array( $this, 'customize_product_page' ) );

		// Simplify checkout fields for credit purchases.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'simplify_credit_checkout_fields' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'fill_credit_checkout_defaults' ) );

		// Skip postcode validation for credit purchases (virtual product, no shipping).
		add_filter( 'woocommerce_validate_postcode', array( $this, 'skip_postcode_validation' ), 10, 3 );

		// Disable shipping requirement for credit purchases.
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'credit_cart_needs_shipping' ) );

		// Redirect to coach dashboard after successful credit purchase.
		add_filter( 'woocommerce_get_return_url', array( $this, 'filter_credit_return_url' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'redirect_order_received_for_credits' ) );

		// Auto-complete credit orders immediately (COD, Stripe, any gateway).
		add_action( 'woocommerce_payment_complete', array( $this, 'auto_complete_credit_order' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'auto_complete_credit_order' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'auto_complete_credit_order' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'auto_complete_credit_order' ) );

		// Disable ALL WooCommerce emails for credit orders.
		add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_cancelled_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_failed_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_on_hold_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_refunded_order', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_invoice', array( $this, 'disable_credit_order_email' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_note', array( $this, 'disable_credit_order_email' ), 10, 2 );
	}

	/**
	 * Check whether WooCommerce plugin is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private function check_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Return whether WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	public function is_active() {
		return $this->wc_active;
	}

	/**
	 * Get the WooCommerce product ID for WFEB credits.
	 *
	 * @return int The product ID, or 0 if not set.
	 */
	public function get_credit_product_id() {
		$product_id = get_option( 'wfeb_credit_product_id', 0 );
		return absint( $product_id );
	}

	/**
	 * Create the WFEB Certificate Credit product in WooCommerce.
	 *
	 * Creates a simple, virtual product with a price of 10.00 GBP.
	 * Stores the product ID in the wfeb_credit_product_id option.
	 *
	 * @return int The newly created product ID, or 0 on failure.
	 */
	public function create_credit_product() {
		if ( ! $this->wc_active ) {
			wfeb_log( 'WFEB_WooCommerce::create_credit_product() - WooCommerce is not active.' );
			return 0;
		}

		// Check if product already exists.
		$existing_id = $this->get_credit_product_id();
		if ( $existing_id && get_post_status( $existing_id ) ) {
			wfeb_log( 'WFEB_WooCommerce::create_credit_product() - Product already exists: ' . $existing_id );
			return $existing_id;
		}

		$product = new WC_Product_Simple();

		$product->set_name( 'WFEB Certificate Credit' );
		$product->set_description( 'Purchase certificate credits for the World Football Examination Board. Each credit allows you to issue one skills certificate.' );
		$product->set_short_description( 'WFEB Certificate Credit - one credit per certificate.' );
		$product->set_regular_price( '10.00' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( false );
		$product->set_status( 'publish' );
		$product->set_sku( 'wfeb-certificate-credit' );

		$product_id = $product->save();

		if ( ! $product_id ) {
			wfeb_log( 'WFEB_WooCommerce::create_credit_product() - Failed to create product.' );
			return 0;
		}

		// Store the product ID in options.
		update_option( 'wfeb_credit_product_id', $product_id );

		wfeb_log( 'WFEB_WooCommerce::create_credit_product() - Product created: ' . $product_id );

		return $product_id;
	}

	/**
	 * Get the URL to add the credit product to the cart with a specified quantity.
	 *
	 * @param int $quantity Number of credits to add. Default 1.
	 * @return string The add-to-cart URL, or empty string if product not found.
	 */
	public function get_buy_credits_url( $quantity = 1 ) {
		if ( ! $this->wc_active ) {
			return '';
		}

		$product_id = $this->get_credit_product_id();

		if ( ! $product_id ) {
			return '';
		}

		$quantity = absint( $quantity );
		if ( $quantity < 1 ) {
			$quantity = 1;
		}

		return add_query_arg(
			array(
				'add-to-cart' => $product_id,
				'quantity'    => $quantity,
			),
			wc_get_cart_url()
		);
	}

	/**
	 * Process a completed WooCommerce order for WFEB credits.
	 *
	 * Finds WFEB credit product line items, determines the coach from the
	 * order customer, adds credits, and sends confirmation email.
	 *
	 * @param int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public function process_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wfeb_log( 'WFEB_WooCommerce::process_order() - Order not found: ' . $order_id );
			return;
		}

		// Prevent double processing.
		if ( $order->get_meta( '_wfeb_credits_processed' ) === 'yes' ) {
			wfeb_log( 'WFEB_WooCommerce::process_order() - Order already processed: ' . $order_id );
			return;
		}

		$credit_product_id = $this->get_credit_product_id();

		if ( ! $credit_product_id ) {
			return;
		}

		$total_credits = 0;

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();

			if ( $this->is_credit_product( $product_id ) ) {
				$total_credits += absint( $item->get_quantity() );
			}
		}

		if ( $total_credits <= 0 ) {
			return;
		}

		// Get the coach from the order customer.
		$user_id = $order->get_customer_id();

		if ( ! $user_id ) {
			wfeb_log( 'WFEB_WooCommerce::process_order() - No customer user_id for order: ' . $order_id );
			return;
		}

		$coach = WFEB()->coach->get_by_user_id( $user_id );

		if ( ! $coach ) {
			wfeb_log( 'WFEB_WooCommerce::process_order() - Coach not found for user_id: ' . $user_id . ', order: ' . $order_id );
			return;
		}

		// Add credits to the coach.
		$added = WFEB()->coach->add_credits( $coach->id, $total_credits, $order_id );

		if ( ! $added ) {
			wfeb_log( 'WFEB_WooCommerce::process_order() - Failed to add credits for coach_id: ' . $coach->id . ', order: ' . $order_id );
			return;
		}

		// Mark order as processed for WFEB.
		$order->update_meta_data( '_wfeb_credits_processed', 'yes' );
		$order->update_meta_data( '_wfeb_credits_quantity', $total_credits );
		$order->update_meta_data( '_wfeb_coach_id', $coach->id );
		$order->save();

		// Refresh coach data to get new balance.
		$coach       = WFEB()->coach->get( $coach->id );
		$new_balance = absint( $coach->credits_balance );

		// Send confirmation email.
		WFEB()->email->send_credit_purchase( $coach, $total_credits, $new_balance );

		wfeb_log( 'WFEB_WooCommerce::process_order() - Credits added: ' . $total_credits . ' for coach_id: ' . $coach->id . ', order: ' . $order_id . ', new_balance: ' . $new_balance );
	}

	/**
	 * Process a refunded WooCommerce order for WFEB credits.
	 *
	 * Reverses previously added credits if the order contained WFEB credit items.
	 * Records a refund transaction.
	 *
	 * @param int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public function process_refund( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wfeb_log( 'WFEB_WooCommerce::process_refund() - Order not found: ' . $order_id );
			return;
		}

		// Only process if credits were previously added.
		if ( $order->get_meta( '_wfeb_credits_processed' ) !== 'yes' ) {
			return;
		}

		// Prevent double refund processing.
		if ( $order->get_meta( '_wfeb_credits_refunded' ) === 'yes' ) {
			wfeb_log( 'WFEB_WooCommerce::process_refund() - Refund already processed: ' . $order_id );
			return;
		}

		$coach_id       = absint( $order->get_meta( '_wfeb_coach_id' ) );
		$credits_to_rev = absint( $order->get_meta( '_wfeb_credits_quantity' ) );

		if ( ! $coach_id || ! $credits_to_rev ) {
			wfeb_log( 'WFEB_WooCommerce::process_refund() - Missing coach_id or credits quantity for order: ' . $order_id );
			return;
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wfeb_log( 'WFEB_WooCommerce::process_refund() - Coach not found: ' . $coach_id . ', order: ' . $order_id );
			return;
		}

		// Calculate new balance (do not go below zero).
		$current_balance = absint( $coach->credits_balance );
		$new_balance     = max( 0, $current_balance - $credits_to_rev );

		// Update the coach's credit balance.
		$updated = WFEB()->coach->update( $coach_id, array(
			'credits_balance' => $new_balance,
		) );

		if ( ! $updated ) {
			wfeb_log( 'WFEB_WooCommerce::process_refund() - Failed to deduct credits for coach_id: ' . $coach_id . ', order: ' . $order_id );
			return;
		}

		// Record the refund transaction.
		$transactions_table = $wpdb->prefix . 'wfeb_credit_transactions';

		$wpdb->insert(
			$transactions_table,
			array(
				'coach_id'    => $coach_id,
				'type'        => 'refund',
				'amount'      => -1 * $credits_to_rev,
				'balance'     => $new_balance,
				'description' => sprintf(
					/* translators: 1: credit count, 2: order ID */
					__( 'Refund of %1$d credit(s) for order #%2$d', 'wfeb' ),
					$credits_to_rev,
					$order_id
				),
				'order_id'    => $order_id,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%d', '%s' )
		);

		// Mark order as refunded for WFEB.
		$order->update_meta_data( '_wfeb_credits_refunded', 'yes' );
		$order->save();

		wfeb_log( 'WFEB_WooCommerce::process_refund() - Credits refunded: ' . $credits_to_rev . ' for coach_id: ' . $coach_id . ', order: ' . $order_id . ', new_balance: ' . $new_balance );
	}

	/**
	 * Check if a product ID matches the WFEB credit product.
	 *
	 * @param int $product_id The WooCommerce product ID to check.
	 * @return bool True if the product is the WFEB credit product.
	 */
	public function is_credit_product( $product_id ) {
		$credit_product_id = $this->get_credit_product_id();

		if ( ! $credit_product_id ) {
			return false;
		}

		return absint( $product_id ) === $credit_product_id;
	}

	/**
	 * Check whether the current cart contains a WFEB credit product.
	 *
	 * @return bool
	 */
	private function is_credit_checkout() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( $this->is_credit_product( $item['product_id'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Simplify WooCommerce checkout fields for credit purchases.
	 *
	 * Only first name, last name, email, and country are required.
	 * All other billing fields are made optional and shipping/order fields removed.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function simplify_credit_checkout_fields( $fields ) {
		if ( ! $this->is_credit_checkout() ) {
			return $fields;
		}

		$keep_required = array(
			'billing_first_name',
			'billing_last_name',
			'billing_email',
			'billing_country',
		);

		if ( isset( $fields['billing'] ) ) {
			foreach ( $fields['billing'] as $key => &$field ) {
				if ( ! in_array( $key, $keep_required, true ) ) {
					$field['required'] = false;
				}
			}
		}

		// Remove shipping and additional fields entirely.
		$fields['shipping'] = array();
		$fields['order']    = array();

		return $fields;
	}

	/**
	 * Fill default values for billing fields not collected in the credit checkout UI.
	 *
	 * Ensures WooCommerce validation passes even when optional fields are blank.
	 *
	 * @param array $data Posted checkout data.
	 * @return array Modified data.
	 */
	public function fill_credit_checkout_defaults( $data ) {
		if ( ! $this->is_credit_checkout() ) {
			return $data;
		}

		$defaults = array(
			'billing_address_1' => 'N/A',
			'billing_city'      => 'N/A',
			'billing_postcode'  => '00000',
			'billing_phone'     => '0000000000',
			'billing_state'     => '',
		);

		foreach ( $defaults as $key => $value ) {
			if ( empty( $data[ $key ] ) ) {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Auto-complete a WooCommerce order if it contains the WFEB credit product.
	 *
	 * Works with any payment gateway (COD, Stripe, PayPal, etc.).
	 * Sets order status to "completed" which triggers process_order() to add credits.
	 *
	 * @param int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public function auto_complete_credit_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Already completed — nothing to do.
		if ( $order->get_status() === 'completed' ) {
			return;
		}

		$has_credits = false;
		foreach ( $order->get_items() as $item ) {
			if ( $this->is_credit_product( $item->get_product_id() ) ) {
				$has_credits = true;
				break;
			}
		}

		if ( ! $has_credits ) {
			return;
		}

		$order->set_status( 'completed', __( 'WFEB credit order auto-completed.', 'wfeb' ) );
		$order->save();
	}

	/**
	 * Disable all WooCommerce emails for orders containing the WFEB credit product.
	 *
	 * @param bool     $enabled Whether the email is enabled.
	 * @param WC_Order $order   The order object (may be false on some filters).
	 * @return bool
	 */
	public function disable_credit_order_email( $enabled, $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return $enabled;
		}

		foreach ( $order->get_items() as $item ) {
			if ( $this->is_credit_product( $item->get_product_id() ) ) {
				return false;
			}
		}

		return $enabled;
	}

	/**
	 * Filter the WooCommerce return URL for credit orders.
	 *
	 * Changes the post-payment redirect destination so the payment gateway
	 * sends the customer directly to the coach dashboard instead of the
	 * order-received page. This fires before any output, so it works
	 * reliably on all hosting environments.
	 *
	 * @param string   $return_url The default return URL.
	 * @param WC_Order $order      The order object.
	 * @return string Modified return URL for credit orders.
	 */
	public function filter_credit_return_url( $return_url, $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return $return_url;
		}

		if ( ! $this->order_has_credits( $order ) ) {
			return $return_url;
		}

		$dashboard_url = $this->get_credits_dashboard_url();

		return $dashboard_url ? $dashboard_url : $return_url;
	}

	/**
	 * Redirect away from order-received page for credit orders.
	 *
	 * Safety net: if a customer somehow lands on the order-received endpoint
	 * for a credit order (e.g. browser back, bookmark, gateway that ignores
	 * return URL), redirect them before any output.
	 *
	 * @return void
	 */
	public function redirect_order_received_for_credits() {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Get order ID from the endpoint.
		global $wp;
		$order_id = absint( $wp->query_vars['order-received'] ?? 0 );

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( ! $this->order_has_credits( $order ) ) {
			return;
		}

		$dashboard_url = $this->get_credits_dashboard_url();

		if ( ! $dashboard_url ) {
			return;
		}

		wp_safe_redirect( $dashboard_url );
		exit;
	}

	/**
	 * Check whether a WooCommerce order contains the WFEB credit product.
	 *
	 * @param WC_Order $order The order to check.
	 * @return bool
	 */
	private function order_has_credits( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( $this->is_credit_product( $item->get_product_id() ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the coach dashboard credits section URL.
	 *
	 * @return string|false The URL, or false if the dashboard page is not configured.
	 */
	private function get_credits_dashboard_url() {
		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );

		if ( ! $dashboard_page_id ) {
			return false;
		}

		return add_query_arg( 'section', 'buy-credits', get_permalink( $dashboard_page_id ) );
	}

	/**
	 * Skip postcode format validation for credit checkout.
	 *
	 * @param bool   $valid    Whether the postcode is valid.
	 * @param string $postcode The postcode value.
	 * @param string $country  The country code.
	 * @return bool
	 */
	public function skip_postcode_validation( $valid, $postcode, $country ) {
		if ( $this->is_credit_checkout() ) {
			return true;
		}
		return $valid;
	}

	/**
	 * Disable shipping requirement when the cart only contains credit products.
	 *
	 * @param bool $needs_shipping Whether the cart needs shipping.
	 * @return bool
	 */
	public function credit_cart_needs_shipping( $needs_shipping ) {
		if ( $this->is_credit_checkout() ) {
			return false;
		}
		return $needs_shipping;
	}

	/**
	 * Customize the product page for the WFEB credit product.
	 *
	 * Adds informational text before the add-to-cart quantity field
	 * explaining what credits are used for.
	 *
	 * @return void
	 */
	public function customize_product_page() {
		global $product;

		if ( ! $product || ! $this->is_credit_product( $product->get_id() ) ) {
			return;
		}

		echo '<div class="wfeb-credit-info" style="margin-bottom: 15px; padding: 15px; background-color: #f0f4fa; border: 1px solid #d0d8e8; border-radius: 6px;">';
		echo '<p style="margin: 0 0 8px 0; font-weight: 600; color: #1a2a4a;">' . esc_html__( 'WFEB Certificate Credits', 'wfeb' ) . '</p>';
		echo '<p style="margin: 0; font-size: 14px; color: #555555;">' . esc_html__( 'Each credit allows you to issue one skills certificate after completing an exam. Select the number of credits you wish to purchase.', 'wfeb' ) . '</p>';
		echo '</div>';
	}
}
