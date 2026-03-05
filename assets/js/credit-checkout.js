/**
 * WFEB Credit Checkout
 *
 * Handles pay-button spinner and WooCommerce error toasts.
 *
 * @package WFEB
 * @since   2.2.3
 */
(function ($) {
	'use strict';

	var $btn   = $( '#wfeb-cco-pay-btn' );
	var $form  = $( '#wfeb-cco-form' );
	var toastTimer = null;

	/* ── Pay button spinner ─────────────────────────────────────────────── */

	// WooCommerce fires this right before submitting checkout via AJAX.
	$form.on( 'checkout_place_order', function () {
		$btn.addClass( 'is-loading' ).prop( 'disabled', true );
		return true; // Allow WC to continue.
	});

	/* ── Error handling — toast ──────────────────────────────────────────── */

	// WooCommerce triggers 'checkout_error' after validation fails.
	$( document.body ).on( 'checkout_error', function () {
		$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
		showErrorToasts();
	});

	// Also catch the WC update_checkout events for edge cases.
	$( document.body ).on( 'updated_checkout', function () {
		$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
	});

	/**
	 * Find WooCommerce error notices and show them as toasts.
	 */
	function showErrorToasts() {
		// WC injects .woocommerce-error or .woocommerce-NoticeGroup with <li> items.
		var $errors = $( '.woocommerce-error li, .woocommerce-NoticeGroup-checkout li' );

		if ( ! $errors.length ) {
			// Fallback: look for text inside the error list itself.
			$errors = $( '.woocommerce-error, .woocommerce-NoticeGroup-checkout' );
		}

		if ( ! $errors.length ) {
			return;
		}

		// Hide the default WC error block (we show toasts instead).
		$( '.woocommerce-error, .woocommerce-NoticeGroup-checkout' ).hide();

		$errors.each( function ( i ) {
			var msg = $( this ).text().trim();
			if ( msg ) {
				showToast( msg, i * 120 );
			}
		});
	}

	/**
	 * Show a single toast notification.
	 *
	 * @param {string} message The error message.
	 * @param {number} delay   Stagger delay in ms.
	 */
	function showToast( message, delay ) {
		var $toast = $( '<div class="wfeb-cco-toast">' +
			'<span class="wfeb-cco-toast__icon dashicons dashicons-warning"></span>' +
			'<span class="wfeb-cco-toast__msg"></span>' +
			'<button type="button" class="wfeb-cco-toast__close">&times;</button>' +
			'</div>' );

		$toast.find( '.wfeb-cco-toast__msg' ).text( message );

		// Ensure container exists.
		if ( ! $( '#wfeb-cco-toast-wrap' ).length ) {
			$( 'body' ).append( '<div id="wfeb-cco-toast-wrap"></div>' );
		}

		setTimeout( function () {
			$( '#wfeb-cco-toast-wrap' ).append( $toast );

			// Trigger reflow then animate in.
			$toast[0].offsetHeight; // eslint-disable-line no-unused-expressions
			$toast.addClass( 'is-visible' );

			// Auto-dismiss after 6s.
			var timer = setTimeout( function () {
				dismissToast( $toast );
			}, 6000 );

			$toast.data( 'timer', timer );
		}, delay );

		// Close button.
		$toast.on( 'click', '.wfeb-cco-toast__close', function () {
			clearTimeout( $toast.data( 'timer' ) );
			dismissToast( $toast );
		});
	}

	/**
	 * Dismiss a toast with exit animation.
	 */
	function dismissToast( $toast ) {
		$toast.removeClass( 'is-visible' ).addClass( 'is-leaving' );
		setTimeout( function () {
			$toast.remove();
		}, 300 );
	}

})(jQuery);
