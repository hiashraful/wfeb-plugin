# Credit Purchase Flow Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Add a polished credit-buying flow: a buy-credits dashboard section with +/- quantity selector, then a custom aclas-style checkout page branded with WFEB colors.

**Architecture:** The coach clicks "Buy More Credits" → lands on a new coach dashboard section (`?section=buy-credits`) with a simple +/- quantity picker and live price display → clicks "Pay Now" which sets the WooCommerce cart via AJAX → redirects to a standalone credit checkout page (custom template in `templates/pages/credit-checkout.php`) that mirrors the aclas checkout UI with WFEB colors → WooCommerce processes payment → existing `process_order()` hook credits the coach automatically.

**Tech Stack:** PHP/WordPress, jQuery, WooCommerce cart/checkout APIs, CSS custom properties (WFEB design system: Sora font, `--wfeb-accent: #0056A7`, `--wfeb-success: #22C55E`)

---

## Colour Mapping (aclas → WFEB)

| aclas value | WFEB replacement | Purpose |
|-------------|-----------------|---------|
| `#6366f1` (indigo) | `#0056A7` (`--wfeb-accent`) | Focus rings, selected state |
| `#00D66E` (green) | `#0056A7` (`--wfeb-accent`) | Pay button, primary action |
| Lavender gradient | `#F8F9FA` solid | Page background |
| Grid overlay | none | Remove |
| Inter font | Sora font | Body typography |

---

## Key Files for Context

Before starting, read these files to understand the codebase:

| File | Why |
|------|-----|
| `templates/coach/credits.php` | Contains the "Buy More Credits" button to update |
| `includes/class-wfeb-coach-dashboard.php` | `$allowed_sections`, `get_section_template()`, `get_page_title()` arrays |
| `wfeb-plugin.php` → `load_custom_templates()` | Where new page template is registered |
| `includes/class-wfeb-ajax.php` | Where new AJAX handler goes (add after `wfeb_get_dashboard_stats`) |
| `includes/admin/class-wfeb-admin-settings.php` → `get_settings()` | Where new option is added |
| `templates/admin/settings.php` (WooCommerce tab, lines 132–190) | Where new page selector field goes |
| `assets/css/coach-dashboard.css` | CSS variables and button classes to reuse |
| `templates/coach-dashboard-template.php` | How the coach JS is localized (`wfeb_coach.ajax_url`, `wfeb_coach.nonce`) |

---

## Task 1: Register `buy-credits` Section in Dashboard Routing

**Files:**
- Modify: `includes/class-wfeb-coach-dashboard.php` lines 29–40, 147–158, 174–187

**Step 1: Add to `$allowed_sections` array (line 38, after `'credits'`)**

```php
'credits',
'buy-credits',  // ADD THIS LINE
'settings',
```

**Step 2: Add to `$template_map` in `get_section_template()` (line 155, after credits entry)**

```php
'credits'        => 'credits.php',
'buy-credits'    => 'buy-credits.php',  // ADD THIS LINE
'settings'       => 'settings.php',
```

**Step 3: Add to `$titles` in `get_page_title()` (line 183, after credits entry)**

```php
'credits'        => __( 'Certificate Credits', 'wfeb' ),
'buy-credits'    => __( 'Buy Certificate Credits', 'wfeb' ),  // ADD THIS LINE
'settings'       => __( 'Settings', 'wfeb' ),
```

**Step 4: Verify routing works**

Visit: `http://your-site.local/coach-dashboard/?section=buy-credits`

Expected: WordPress error "Template file not found" or blank page — confirms the section is registered but template doesn't exist yet.

**Step 5: Commit**

```bash
git add includes/class-wfeb-coach-dashboard.php
git commit -m "feat: register buy-credits dashboard section"
```

---

## Task 2: Update "Buy More Credits" Button in credits.php

**Files:**
- Modify: `templates/coach/credits.php` lines 75–85

Replace the entire `<?php if ( $buy_credits_url ) : ?>` block (lines 75–85) with:

```php
<?php
$buy_section_url = add_query_arg( 'section', 'buy-credits', $base_url );
?>
<a href="<?php echo esc_url( $buy_section_url ); ?>"
   class="wfeb-btn wfeb-btn--primary wfeb-btn--lg">
    <span class="dashicons dashicons-cart"></span>
    <?php echo esc_html__( 'Buy More Credits', 'wfeb' ); ?>
</a>
```

Remove the old PHP variables `$credit_product_id` and `$buy_credits_url` (lines 37–45) since they're no longer needed in this template.

**Step 2: Test**

Click "Buy More Credits" on the credits section → should navigate to `?section=buy-credits`.

**Step 3: Commit**

```bash
git add templates/coach/credits.php
git commit -m "feat: update Buy More Credits button to link to buy-credits section"
```

---

## Task 3: Create Buy Credits Template (`templates/coach/buy-credits.php`)

**Files:**
- Create: `templates/coach/buy-credits.php`

```php
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
$product_id   = absint( get_option( 'wfeb_credit_product_id', 0 ) );
$credit_price = 1.00; // fallback

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
                <span class="wfeb-price-value">£<?php echo esc_html( number_format( $credit_price, 2 ) ); ?></span>
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
                &times; £<?php echo esc_html( number_format( $credit_price, 2 ) ); ?>
            </span>
            <span class="wfeb-buy-credits-total-row">
                <?php esc_html_e( 'Total', 'wfeb' ); ?>
                <strong id="wfeb-credits-total-amount">
                    £<?php echo esc_html( number_format( $credit_price, 2 ) ); ?>
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
```

**Step 2: Verify**

Visit `?section=buy-credits` — should display the card with the quantity selector, price, and Pay Now button.

**Step 3: Commit**

```bash
git add templates/coach/buy-credits.php
git commit -m "feat: create buy-credits template with quantity selector"
```

---

## Task 4: Add Buy Credits CSS to `coach-dashboard.css`

**Files:**
- Modify: `assets/css/coach-dashboard.css` (append to end of file)

Append the following section at the very end of the file:

```css
/* ==========================================================================
   Buy Credits Section
   ========================================================================== */

.wfeb-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--wfeb-text-muted);
    text-decoration: none;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.wfeb-back-link:hover {
    color: var(--wfeb-accent);
}

.wfeb-buy-credits-wrap {
    max-width: 480px;
    margin: 0 auto;
}

.wfeb-buy-credits-card {
    background: var(--wfeb-card-bg);
    border: 1px solid var(--wfeb-border);
    border-radius: var(--wfeb-radius);
    padding: 36px 40px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

/* Header */
.wfeb-buy-credits-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.wfeb-buy-credits-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--wfeb-radius-sm);
    background: var(--wfeb-accent-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.wfeb-buy-credits-icon .dashicons {
    color: var(--wfeb-accent);
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.wfeb-buy-credits-info h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--wfeb-text);
    margin: 0 0 6px;
}

.wfeb-buy-credits-info p {
    font-size: 13px;
    color: var(--wfeb-text-muted);
    margin: 0;
    line-height: 1.5;
}

/* Price Badge */
.wfeb-buy-credits-price-row {
    display: flex;
    justify-content: center;
}

.wfeb-buy-credits-price-badge {
    display: flex;
    align-items: baseline;
    gap: 8px;
    background: var(--wfeb-accent-light);
    border: 1px solid rgba(0, 86, 167, 0.15);
    border-radius: 100px;
    padding: 10px 24px;
}

.wfeb-price-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--wfeb-accent);
    letter-spacing: -0.5px;
}

.wfeb-price-unit {
    font-size: 13px;
    color: var(--wfeb-text-muted);
}

/* Quantity Selector */
.wfeb-qty-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    border: 1px solid var(--wfeb-border);
    border-radius: var(--wfeb-radius-sm);
    overflow: hidden;
    height: 52px;
}

.wfeb-qty-btn {
    width: 52px;
    height: 100%;
    background: var(--wfeb-bg-elevated);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wfeb-text);
    transition: background 0.15s;
    flex-shrink: 0;
}

.wfeb-qty-btn:hover {
    background: var(--wfeb-accent-light);
    color: var(--wfeb-accent);
}

.wfeb-qty-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.wfeb-qty-input {
    flex: 1;
    height: 100%;
    border: none;
    border-left: 1px solid var(--wfeb-border);
    border-right: 1px solid var(--wfeb-border);
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    color: var(--wfeb-text);
    background: var(--wfeb-card-bg);
    font-family: var(--wfeb-font-body);
    outline: none;
    -moz-appearance: textfield;
}

.wfeb-qty-input::-webkit-outer-spin-button,
.wfeb-qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Live Summary */
.wfeb-buy-credits-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--wfeb-bg-elevated);
    border-radius: var(--wfeb-radius-sm);
    font-size: 14px;
    color: var(--wfeb-text-muted);
}

.wfeb-buy-credits-total-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: var(--wfeb-text);
}

.wfeb-buy-credits-total-row strong {
    font-size: 18px;
    font-weight: 700;
    color: var(--wfeb-accent);
}

/* Full-width button modifier */
.wfeb-btn--full {
    width: 100%;
    justify-content: center;
}

/* Responsive */
@media (max-width: 600px) {
    .wfeb-buy-credits-card {
        padding: 24px 20px;
    }
    .wfeb-buy-credits-wrap {
        max-width: 100%;
    }
}
```

**Step 2: Verify**

Reload `?section=buy-credits` — the card should be styled, centered on the page, with the quantity selector looking clean.

**Step 3: Commit**

```bash
git add assets/css/coach-dashboard.css
git commit -m "feat: add buy-credits section styles"
```

---

## Task 5: Add Buy Credits JS to `coach-dashboard.js`

**Files:**
- Modify: `assets/js/coach-dashboard.js` (append near the end, inside the `$(document).ready` wrapper)

Find the closing `}); // end document.ready` at the bottom of admin.js and insert the following block **before** it:

```javascript
// =====================================================================
// Buy Credits: +/- quantity selector + live price + form submit
// =====================================================================
(function initBuyCredits() {
    var $qty     = $('#wfeb-credit-qty');
    if (!$qty.length) return; // Not on the buy-credits section

    var $hiddenQty  = $('#wfeb-buy-credits-qty-hidden');
    var $summary    = $('#wfeb-credits-summary-line');
    var $total      = $('#wfeb-credits-total-amount');
    var $form       = $('#wfeb-buy-credits-form');
    var price       = parseFloat($qty.data('price')) || 1;

    function updatePriceDisplay() {
        var qty = parseInt($qty.val(), 10) || 1;
        if (qty < 1) qty = 1;
        if (qty > 200) qty = 200;
        $qty.val(qty);
        $hiddenQty.val(qty);

        var total  = (qty * price).toFixed(2);
        var word   = qty === 1 ? 'credit' : 'credits';
        $summary.text(qty + ' ' + word + ' \u00d7 \u00a3' + price.toFixed(2));
        $total.text('\u00a3' + total);
    }

    // +/- buttons
    $(document).on('click', '.wfeb-qty-minus', function () {
        var v = parseInt($qty.val(), 10) || 1;
        if (v > 1) { $qty.val(v - 1); updatePriceDisplay(); }
    });

    $(document).on('click', '.wfeb-qty-plus', function () {
        var v = parseInt($qty.val(), 10) || 1;
        if (v < 200) { $qty.val(v + 1); updatePriceDisplay(); }
    });

    $qty.on('input change', function () {
        updatePriceDisplay();
    });

    // Form submit → AJAX → redirect
    $form.on('submit', function (e) {
        e.preventDefault();
        var $btn     = $('#wfeb-pay-now-btn');
        var checkoutUrl = $('input[name="checkout_url"]', $form).val();

        setLoading($btn, true);

        $.post(
            wfeb_coach.ajax_url,
            {
                action:                 'wfeb_setup_credit_cart',
                product_id:             $('input[name="product_id"]', $form).val(),
                quantity:               $hiddenQty.val(),
                checkout_url:           checkoutUrl,
                wfeb_buy_credits_nonce: $('input[name="wfeb_buy_credits_nonce"]', $form).val(),
                _wpnonce:               $('input[name="_wpnonce"]', $form).val(),
            },
            function (response) {
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    setLoading($btn, false);
                    var msg = (response.data && response.data.message)
                        ? response.data.message
                        : 'An error occurred. Please try again.';
                    alert(msg);
                }
            }
        ).fail(function () {
            setLoading($btn, false);
            alert('Request failed. Please try again.');
        });
    });
}());
```

> **Note:** `setLoading()` and `wfeb_coach.ajax_url` are already defined elsewhere in this file. Do NOT redefine them.

**Step 2: Test JS**

1. Visit `?section=buy-credits`
2. Click `+` → quantity increments, price updates
3. Click `−` → quantity decrements (won't go below 1)
4. Type a number in the input → price updates
5. Click "Pay Now" → spinner appears (AJAX will fail until Task 6, which is fine)

**Step 3: Commit**

```bash
git add assets/js/coach-dashboard.js
git commit -m "feat: add buy-credits quantity selector and form submit JS"
```

---

## Task 6: Add AJAX Handler `wfeb_setup_credit_cart`

**Files:**
- Modify: `includes/class-wfeb-ajax.php`

**Step 1: Register the action**

In the constructor of `WFEB_Ajax` (search for `add_action( 'wp_ajax_wfeb_get_dashboard_stats'`), add immediately after it:

```php
add_action( 'wp_ajax_wfeb_setup_credit_cart', array( $this, 'setup_credit_cart' ) );
```

**Step 2: Add the handler method**

Find the `get_dashboard_stats()` method. Add the new method immediately **before** it:

```php
/**
 * AJAX: Set up WooCommerce cart for credit purchase and return checkout URL.
 *
 * Clears cart, adds the credit product at the requested quantity,
 * then returns the checkout page URL for JS to redirect to.
 *
 * @since 1.0.0
 * @return void
 */
public function setup_credit_cart() {
    // Nonce verification.
    check_ajax_referer( 'wfeb_buy_credits_nonce', 'wfeb_buy_credits_nonce' );

    // Must be a logged-in approved coach.
    if ( ! is_user_logged_in() || ! wfeb_is_coach() ) {
        wp_send_json_error( array( 'message' => __( 'Access denied.', 'wfeb' ) ) );
    }

    // WooCommerce must be active.
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        wp_send_json_error( array( 'message' => __( 'WooCommerce is not available.', 'wfeb' ) ) );
    }

    $product_id   = absint( $_POST['product_id'] ?? 0 );
    $quantity     = absint( $_POST['quantity'] ?? 1 );
    $checkout_url = esc_url_raw( $_POST['checkout_url'] ?? '' );

    if ( ! $product_id || $quantity < 1 || $quantity > 200 ) {
        wp_send_json_error( array( 'message' => __( 'Invalid quantity or product.', 'wfeb' ) ) );
    }

    // Verify the product ID matches the configured credit product.
    $configured_product_id = absint( get_option( 'wfeb_credit_product_id', 0 ) );
    if ( $configured_product_id && $product_id !== $configured_product_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wfeb' ) ) );
    }

    // Clear existing cart and add credit product.
    WC()->cart->empty_cart();
    $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );

    if ( ! $cart_item_key ) {
        wp_send_json_error( array( 'message' => __( 'Could not add product to cart. Please try again.', 'wfeb' ) ) );
    }

    // Use the provided checkout URL or fall back to WC checkout.
    $redirect = $checkout_url ?: wc_get_checkout_url();

    wp_send_json_success( array( 'redirect' => $redirect ) );
}
```

**Step 3: Test AJAX**

1. Go to `?section=buy-credits`, select a quantity, click "Pay Now"
2. Open browser DevTools → Network tab → look for the `admin-ajax.php` POST request
3. Expected response: `{"success":true,"data":{"redirect":"http://your-site.local/credit-checkout/"}}`
4. Browser should redirect to the checkout page (404 is fine until Task 8)

**Step 4: Commit**

```bash
git add includes/class-wfeb-ajax.php
git commit -m "feat: add wfeb_setup_credit_cart AJAX handler"
```

---

## Task 7: Add `wfeb_credit_checkout_page_id` Setting

**Files:**
- Modify: `includes/admin/class-wfeb-admin-settings.php`
- Modify: `templates/admin/settings.php`
- Modify: `wfeb-plugin.php`

**Step 1: Add to `get_settings()` in `class-wfeb-admin-settings.php`**

In the `get_settings()` method (around line 57, in the WooCommerce section), add:

```php
// WooCommerce.
'credit_product_id'          => get_option( 'wfeb_credit_product_id', '' ),
'credit_price'               => get_option( 'wfeb_credit_price', '' ),
'credit_checkout_page_id'    => get_option( 'wfeb_credit_checkout_page_id', '' ),  // ADD THIS
```

**Step 2: Add save logic**

Search for the `wfeb_save_settings` AJAX handler in `class-wfeb-admin-settings.php`. Inside the `woocommerce` tab save block, add:

```php
if ( isset( $_POST['wfeb_credit_checkout_page_id'] ) ) {
    update_option( 'wfeb_credit_checkout_page_id', absint( $_POST['wfeb_credit_checkout_page_id'] ) );
}
```

**Step 3: Add field in settings.php WooCommerce tab**

In `templates/admin/settings.php`, after the `wfeb_credit_price` form row (around line 178, before the closing `</div></div>`), add:

```php
<div class="wfeb-form-row">
    <label class="wfeb-form-label" for="wfeb_credit_checkout_page_id"
        data-tooltip="The WordPress page that shows the custom credit checkout form">
        <?php esc_html_e( 'Credit Checkout Page', 'wfeb' ); ?>
    </label>
    <?php
    $pages = get_pages();
    ?>
    <select id="wfeb_credit_checkout_page_id" name="wfeb_credit_checkout_page_id" class="wfeb-form-select">
        <option value=""><?php esc_html_e( '— Select a page —', 'wfeb' ); ?></option>
        <?php foreach ( $pages as $page ) : ?>
            <option value="<?php echo absint( $page->ID ); ?>"
                <?php selected( $settings['credit_checkout_page_id'], $page->ID ); ?>>
                <?php echo esc_html( $page->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="wfeb-form-description">
        <?php esc_html_e( 'Create a blank WordPress page, then select it here. The plugin will load the custom checkout template for that page.', 'wfeb' ); ?>
    </p>
</div>
```

**Step 4: Register template in `wfeb-plugin.php`**

In `load_custom_templates()` (around line 298), add after the `$verify_cert_id` block:

```php
$credit_checkout_id = get_option( 'wfeb_credit_checkout_page_id' );

// ... inside the if ( is_page() ) block, after existing checks:
if ( $page_id == $credit_checkout_id ) {
    return WFEB_PLUGIN_DIR . 'templates/pages/credit-checkout.php';
}
```

**Step 5: Admin setup instructions**

After saving this code, the admin must:
1. Go to WordPress Admin → Pages → Add New
2. Title it "Credit Checkout" (or similar), publish it
3. Go to WFEB → Settings → WooCommerce tab
4. Set "Credit Checkout Page" to the page just created
5. Save settings

**Step 6: Commit**

```bash
git add includes/admin/class-wfeb-admin-settings.php templates/admin/settings.php wfeb-plugin.php
git commit -m "feat: add credit checkout page setting and template routing"
```

---

## Task 8: Create Credit Checkout CSS (`assets/css/credit-checkout.css`)

**Files:**
- Create: `assets/css/credit-checkout.css`

This is a full standalone stylesheet (similar to aclas `checkout.css`) with WFEB colours.

```css
/**
 * WFEB Credit Checkout Page
 * Aclas-style layout adapted with WFEB brand colours.
 */

/* ── Reset ─────────────────────────────────────────────────────────────── */
body.wfeb-credit-checkout-page *,
body.wfeb-credit-checkout-page *::before,
body.wfeb-credit-checkout-page *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body.wfeb-credit-checkout-page {
    font-family: 'Sora', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 16px;
    line-height: 1.5;
    color: #111827;
    background: #F0F2F5;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

/* ── Page Layout ───────────────────────────────────────────────────────── */
.wfeb-cco-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.wfeb-cco-card {
    width: 100%;
    max-width: 900px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
    overflow: hidden;
}

/* ── Two-Column Layout ─────────────────────────────────────────────────── */
.wfeb-cco-columns {
    display: grid;
    grid-template-columns: 42% 58%;
    min-height: 560px;
}

/* LEFT — Order Summary */
.wfeb-cco-left {
    background: #F7F8FA;
    padding: 48px 40px;
    border-right: 1px solid #E5E7EB;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.wfeb-cco-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6B7280;
    text-decoration: none;
    transition: color 0.2s;
}

.wfeb-cco-back-link:hover { color: #0056A7; }

.wfeb-cco-summary-title {
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* Credit Item Row */
.wfeb-cco-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #E5E7EB;
}

.wfeb-cco-item-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(0, 86, 167, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #0056A7;
    font-size: 22px;
}

.wfeb-cco-item-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
    line-height: 1;
}

.wfeb-cco-item-details {
    flex: 1;
}

.wfeb-cco-item-name {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.wfeb-cco-item-desc {
    font-size: 12px;
    color: #6B7280;
    margin-top: 2px;
}

.wfeb-cco-item-price {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
}

/* Totals */
.wfeb-cco-totals {
    margin-top: auto;
    padding-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wfeb-cco-total-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #6B7280;
}

.wfeb-cco-total-row.is-grand {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    padding-top: 12px;
    border-top: 2px solid #E5E7EB;
    margin-top: 4px;
}

.wfeb-cco-total-row.is-grand span:last-child {
    color: #0056A7;
}

/* RIGHT — Billing + Payment */
.wfeb-cco-right {
    padding: 48px 48px 48px 52px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.wfeb-cco-section-title {
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 14px;
}

/* ── Form Elements ─────────────────────────────────────────────────────── */
.wfeb-cco-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.wfeb-cco-form-row.is-full {
    grid-template-columns: 1fr;
}

.wfeb-cco-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.wfeb-cco-label {
    font-size: 12px;
    font-weight: 500;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.wfeb-cco-input,
.wfeb-cco-select {
    height: 42px;
    padding: 0 12px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: #111827;
    background: #fff;
    transition: border-color 0.2s;
    outline: none;
    width: 100%;
}

.wfeb-cco-input:focus,
.wfeb-cco-select:focus {
    border-color: #0056A7;
}

.wfeb-cco-input::placeholder {
    color: #9CA3AF;
}

/* ── WooCommerce Payment Methods Override ──────────────────────────────── */
body.wfeb-credit-checkout-page #payment {
    background: transparent;
    border: none;
    padding: 0;
}

body.wfeb-credit-checkout-page #payment ul.payment_methods {
    border: none;
    padding: 0;
    margin: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

body.wfeb-credit-checkout-page #payment ul.payment_methods li {
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}

body.wfeb-credit-checkout-page #payment ul.payment_methods li:hover {
    border-color: #0056A7;
    box-shadow: 0 2px 8px rgba(0, 86, 167, 0.08);
}

body.wfeb-credit-checkout-page #payment ul.payment_methods li label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #111827;
}

body.wfeb-credit-checkout-page #payment ul.payment_methods li .payment_box {
    padding: 16px;
    background: #F7F8FA;
    border-top: 1px solid #E5E7EB;
}

body.wfeb-credit-checkout-page #payment ul.payment_methods li input[type="radio"] {
    accent-color: #0056A7;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

body.wfeb-credit-checkout-page #payment .place-order {
    display: none; /* we use our own Pay Now button */
}

/* ── Pay Button ────────────────────────────────────────────────────────── */
.wfeb-cco-pay-btn {
    width: 100%;
    height: 52px;
    background: #0056A7;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.wfeb-cco-pay-btn:hover {
    background: #004485;
    transform: translateY(-1px);
}

.wfeb-cco-pay-btn:active {
    transform: translateY(0);
}

.wfeb-cco-pay-btn:disabled {
    background: #D1D5DB;
    cursor: not-allowed;
    transform: none;
}

/* Loading state: hide text, show spinner */
.wfeb-cco-pay-btn.is-loading {
    color: transparent !important;
    pointer-events: none;
}

.wfeb-cco-pay-btn.is-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: wfebCcoSpin 0.7s linear infinite;
}

@keyframes wfebCcoSpin {
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

.wfeb-cco-security-note {
    text-align: center;
    font-size: 12px;
    color: #9CA3AF;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* ── Footer ────────────────────────────────────────────────────────────── */
.wfeb-cco-footer {
    padding: 16px 40px;
    border-top: 1px solid #E5E7EB;
    text-align: center;
    font-size: 12px;
    color: #9CA3AF;
    background: #F7F8FA;
}

/* ── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .wfeb-cco-columns {
        grid-template-columns: 1fr;
    }
    .wfeb-cco-left {
        border-right: none;
        border-bottom: 1px solid #E5E7EB;
        padding: 32px 24px;
    }
    .wfeb-cco-right {
        padding: 32px 24px;
    }
}

@media (max-width: 480px) {
    .wfeb-cco-form-row {
        grid-template-columns: 1fr;
    }
    .wfeb-cco-container {
        padding: 0;
    }
    .wfeb-cco-card {
        border-radius: 0;
        min-height: 100vh;
    }
}
```

**Step 2: Commit**

```bash
git add assets/css/credit-checkout.css
git commit -m "feat: create credit checkout CSS (aclas layout + WFEB colours)"
```

---

## Task 9: Create Credit Checkout Template (`templates/pages/credit-checkout.php`)

**Files:**
- Create: `templates/pages/credit-checkout.php`

This is a full standalone HTML document (does not use the WP theme — same pattern as `coach-login.php`, `coach-registration.php`, etc.).

```php
<?php
/**
 * Template: Credit Checkout Page
 *
 * Standalone full-page checkout in aclas style with WFEB branding.
 * Rendered for the page set at wfeb_credit_checkout_page_id.
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

// Check cart has items.
if ( WC()->cart->is_empty() ) {
    $dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
    $buy_url = add_query_arg( 'section', 'buy-credits', get_permalink( $dashboard_page_id ) );
    wp_safe_redirect( $buy_url );
    exit;
}

// Get cart totals.
WC()->cart->calculate_totals();
$cart_items   = WC()->cart->get_cart();
$cart_total   = WC()->cart->get_total( 'edit' ); // numeric
$cart_total_f = WC()->cart->get_total();          // formatted HTML

// Get the first (only) item — the credit product.
$cart_item     = reset( $cart_items );
$quantity      = $cart_item ? $cart_item['quantity'] : 1;
$product_id    = $cart_item ? $cart_item['product_id'] : 0;
$product       = $product_id ? wc_get_product( $product_id ) : null;
$product_name  = $product ? $product->get_name() : __( 'Certificate Credits', 'wfeb' );
$unit_price    = $product ? (float) $product->get_price() : 1.00;

// Pre-fill billing from coach profile.
$coach        = WFEB()->coach_dashboard->get_coach_data();
$coach_name   = $coach ? explode( ' ', $coach->full_name, 2 ) : array( '', '' );
$first_name   = $coach_name[0] ?? '';
$last_name    = $coach_name[1] ?? '';
$coach_email  = $coach ? $coach->email : '';
$coach_country = $coach ? ( $coach->country ?? '' ) : '';

// Back link.
$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$back_url = add_query_arg( 'section', 'buy-credits', get_permalink( $dashboard_page_id ) );

// WooCommerce checkout object.
$checkout = WC()->checkout();

// Page title (for <title>).
$page_title = __( 'Complete Your Purchase', 'wfeb' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $page_title ); ?> &mdash; <?php bloginfo( 'name' ); ?></title>
<?php
wp_enqueue_style( 'dashicons' );
wp_enqueue_style( 'wfeb-credit-checkout' );
wp_enqueue_script( 'wc-checkout' );
wp_head();
?>
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

                    <!-- Credit item row -->
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
                            £<?php echo esc_html( number_format( $unit_price * $quantity, 2 ) ); ?>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="wfeb-cco-totals">
                        <div class="wfeb-cco-total-row">
                            <span><?php esc_html_e( 'Subtotal', 'wfeb' ); ?></span>
                            <span>£<?php echo esc_html( number_format( $unit_price * $quantity, 2 ) ); ?></span>
                        </div>
                        <div class="wfeb-cco-total-row is-grand">
                            <span><?php esc_html_e( 'Total', 'wfeb' ); ?></span>
                            <span><?php echo wp_kses_post( $cart_total_f ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Billing + Payment -->
                <div class="wfeb-cco-right">

                    <!-- Billing Info -->
                    <div class="wfeb-cco-billing-section">
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

                        <div class="wfeb-cco-form-row is-full">
                            <div class="wfeb-cco-field">
                                <label class="wfeb-cco-label" for="billing_country">
                                    <?php esc_html_e( 'Country', 'wfeb' ); ?>
                                </label>
                                <select id="billing_country" name="billing_country" class="wfeb-cco-select">
                                    <?php foreach ( WC()->countries->get_allowed_countries() as $code => $name ) : ?>
                                        <option value="<?php echo esc_attr( $code ); ?>"
                                            <?php selected( strtoupper( $coach_country ), $code ); ?>>
                                            <?php echo esc_html( $name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Hidden required WC fields (city/address not needed but WC validates them) -->
                        <input type="hidden" name="billing_city" value="N/A" />
                        <input type="hidden" name="billing_address_1" value="N/A" />
                        <input type="hidden" name="billing_postcode" value="N/A" />
                        <input type="hidden" name="billing_phone" value="0000000000" />
                        <input type="hidden" name="ship_to_different_address" value="0" />
                        <input type="hidden" name="order_comments" value="" />
                    </div>

                    <!-- WooCommerce Payment Methods -->
                    <div class="wfeb-cco-payment-section">
                        <p class="wfeb-cco-section-title"><?php esc_html_e( 'Payment Method', 'wfeb' ); ?></p>
                        <div id="order_review">
                            <?php do_action( 'woocommerce_checkout_payment' ); ?>
                        </div>
                    </div>

                    <!-- Pay Button -->
                    <div class="wfeb-cco-submit-section">
                        <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
                        <button type="submit" id="wfeb-cco-pay-btn" class="wfeb-cco-pay-btn">
                            <?php
                            printf(
                                /* translators: %s: formatted total price */
                                esc_html__( 'Pay %s', 'wfeb' ),
                                wp_kses_post( $cart_total_f )
                            );
                            ?>
                        </button>
                        <p class="wfeb-cco-security-note">
                            <span class="dashicons dashicons-lock" style="font-size:14px;width:14px;height:14px;line-height:1"></span>
                            <?php esc_html_e( 'Secure payment powered by WooCommerce', 'wfeb' ); ?>
                        </p>
                    </div>

                </div><!-- /.wfeb-cco-right -->

            </div><!-- /.wfeb-cco-columns -->

        </form>

        <div class="wfeb-cco-footer">
            &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
            <?php bloginfo( 'name' ); ?>
            &mdash;
            <?php esc_html_e( 'All payments are processed securely.', 'wfeb' ); ?>
        </div>

    </div><!-- /.wfeb-cco-card -->
</div><!-- /.wfeb-cco-container -->

<?php wp_footer(); ?>
</body>
</html>
```

**Step 2: Commit**

```bash
git add templates/pages/credit-checkout.php
git commit -m "feat: create credit checkout standalone page template"
```

---

## Task 10: Enqueue Credit Checkout Assets in `wfeb-plugin.php`

**Files:**
- Modify: `wfeb-plugin.php`

**Step 1: Add helper method `is_credit_checkout()`**

After `is_wfeb_public_page()` method (around line 357), add:

```php
/**
 * Check if current page is the credit checkout page.
 */
public function is_credit_checkout() {
    return is_page( get_option( 'wfeb_credit_checkout_page_id' ) );
}
```

**Step 2: Enqueue assets in `enqueue_frontend_assets()`**

Inside `enqueue_frontend_assets()`, before the "Frontend public pages" block (around line 224), add:

```php
// Credit checkout page.
if ( $this->is_credit_checkout() ) {
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style(
        'wfeb-credit-checkout',
        WFEB_PLUGIN_URL . 'assets/css/credit-checkout.css',
        array( 'dashicons' ),
        WFEB_VERSION
    );
    // Enqueue WooCommerce checkout scripts (handles payment gateway JS).
    wp_enqueue_script( 'wc-checkout' );
    return;
}
```

**Step 3: Prevent theme/plugin interference**

The checkout page is standalone (outputs its own DOCTYPE), so in `dequeue_conflicting_assets()`, add the checkout page to the guard:

```php
public function dequeue_conflicting_assets() {
    if (
        ! $this->is_coach_dashboard()
        && ! $this->is_player_dashboard()
        && ! $this->is_credit_checkout()   // ADD THIS
    ) {
        return;
    }
    // ... rest of method unchanged
```

Also add `'wfeb-credit-checkout'` to the `$allowed_styles` array:

```php
$allowed_styles = array(
    'dashicons',
    'cropperjs',
    'admin-bar',
    'wfeb-credit-checkout',  // ADD THIS
);
```

**Step 4: Test end-to-end**

1. Go to coach dashboard → Credits section
2. Click "Buy More Credits" → lands on `?section=buy-credits` ✓
3. Select quantity (e.g., 3) → price updates to £3.00 ✓
4. Click "Pay Now" → spinner appears → cart is set → redirects to the checkout page ✓
5. Checkout page shows: "3 × Certificate Credits" = £3.00 total ✓
6. Billing fields pre-filled with coach name and email ✓
7. Payment methods render in the styled accordion ✓
8. Click "Pay Now" button → WooCommerce processes payment ✓
9. On completion → `process_order()` fires → coach gets 3 credits added ✓

**Step 5: Commit**

```bash
git add wfeb-plugin.php
git commit -m "feat: enqueue credit checkout assets and extend dequeue guard"
```

---

## Admin Setup Checklist (Do Once After Implementation)

- [ ] Create a new WordPress Page titled "Credit Checkout" and publish it
- [ ] Go to WFEB Admin → Settings → WooCommerce tab
- [ ] Set "Credit Checkout Page" to the page just created
- [ ] Set "Credit Product" to the WooCommerce product ID (£1 per unit product)
- [ ] Save settings
- [ ] Test full purchase flow as a coach

---

## Testing Checklist

- [ ] `?section=buy-credits` loads without errors
- [ ] +/- buttons increment/decrement correctly (min 1, max 200)
- [ ] Typing in the quantity input updates price in real time
- [ ] "Pay Now" shows spinner while AJAX runs
- [ ] AJAX sets WC cart and redirects to checkout page
- [ ] Checkout page shows correct quantity and price
- [ ] Billing form is pre-filled with coach name/email
- [ ] WooCommerce payment methods render correctly
- [ ] Checkout form submits and WooCommerce processes payment
- [ ] After successful payment, coach credits are updated (check Credits section)
- [ ] CSS is isolated — theme styles don't bleed into checkout page
- [ ] Back link on checkout returns to buy-credits section with cart preserved
