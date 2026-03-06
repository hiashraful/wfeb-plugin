# FEB Rename + Score Report + QR Verification Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Three features: rename FEB to WFEB on registration page, add downloadable score reports with SVG radar charts, and add HMAC-signed QR code verification to certificates.

**Architecture:** Task 1 is a simple text replacement + file cleanup. Task 2 adds a new `generate_score_report()` method to `WFEB_PDF` that builds a self-contained HTML page with an SVG radar chart and score bars, stored alongside the certificate. Task 3 adds HMAC-signed verification URLs, a pure-PHP QR code SVG generator, QR embedding in certificates, and auto-verify logic on the verify page.

**Tech Stack:** PHP 7.4+, WordPress, pure SVG (no JS libraries for print), HMAC-SHA256 for signatures, pure PHP QR code generator (no external dependencies).

---

## Task 1: FEB -> WFEB Rename + Remove Old Template

**Files:**
- Modify: `templates/pages/coach-registration-v1.php` (lines 5, 44, 63, 68, 92, 104, 148, 301)
- Delete: `templates/pages/coach-registration.php`
- Modify: `wfeb-plugin.php` (lines 352-356) — remove `?v=0` routing, always load v1

### Step 1: Replace FEB with WFEB in coach-registration-v1.php

Replace these strings (use replace_all where safe, individual edits where context matters):

- Line 5: `FEB-branded` -> `WFEB-branded`
- Line 44: `Register as an Examiner - FEB` -> `Register as an Examiner - WFEB`
- Line 63: `FEB Logo` -> `WFEB Logo`
- Line 68: `certified FEB examiner` -> `certified WFEB examiner`
- Line 92: `the FEB database` -> `the WFEB database`
- Line 104: `the FEB tests` -> `the WFEB tests`
- Line 148: `FEB Logo` -> `WFEB Logo`
- Line 301: `consent to FEB storing` -> `consent to WFEB storing`

### Step 2: Remove old registration template

Delete `templates/pages/coach-registration.php`.

### Step 3: Remove ?v=0 routing in wfeb-plugin.php

Change lines 352-356 from:
```php
if ( $page_id == $coach_register_id ) {
    if ( isset( $_GET['v'] ) && '0' === $_GET['v'] ) {
        return WFEB_PLUGIN_DIR . 'templates/pages/coach-registration.php';
    }
    return WFEB_PLUGIN_DIR . 'templates/pages/coach-registration-v1.php';
}
```
To:
```php
if ( $page_id == $coach_register_id ) {
    return WFEB_PLUGIN_DIR . 'templates/pages/coach-registration-v1.php';
}
```

### Step 4: Commit

```
feat: rename FEB to WFEB on registration page, remove old template
```

---

## Task 2: Score Report Download

**Files:**
- Modify: `includes/class-wfeb-pdf.php` — add `generate_score_report()` method
- Modify: `includes/class-wfeb-certificate.php` — add `score_report_url` / `score_report_attachment_id` columns, generate score report during certificate generation
- Modify: `includes/class-wfeb-install.php` — add columns to CREATE TABLE (for fresh installs) + migration
- Modify: `templates/player/certificate-detail.php` — add "Download Score Report" button
- Modify: `templates/coach/exam-details.php` — add "Download Score Report" button

### Step 1: Add DB columns for score report

In `includes/class-wfeb-install.php`, add two columns to the `wfeb_certificates` CREATE TABLE:
```sql
score_report_url varchar(500) NOT NULL DEFAULT '',
score_report_attachment_id bigint(20) unsigned DEFAULT NULL,
```
Add them after `pdf_attachment_id` (line ~240).

Also add a migration method that runs on plugin activation to ALTER TABLE and add the columns if they don't exist (for existing installs). Add this to the `activate()` method.

### Step 2: Add generate_score_report() to WFEB_PDF

In `includes/class-wfeb-pdf.php`, add a new public method `generate_score_report( $certificate, $exam )` that:

1. Accepts the certificate object (with joined data) and the full exam object (with individual category scores)
2. Builds a self-contained HTML page (A4 portrait, print-ready) containing:
   - WFEB logo + header ("Skills Score Report")
   - Player name, exam date, certificate number, achievement level, total score
   - **SVG radar chart** — 7-sided polygon using PHP trigonometry:
     - 7 categories: Short Passing (max 10), Long Passing (max 10), Shooting (max 20), Sprinting (max 10), Dribbling (max 10), Kick Ups (max 10), Volley (max 10)
     - Normalize all to percentage (0-100) for even polygon display
     - Grid rings at 20%, 40%, 60%, 80%, 100%
     - Data polygon: fill `rgba(0, 0, 128, 0.2)` (navy), stroke `rgba(16, 185, 129, 1)` (emerald), 2px border
     - Points: emerald circles at each vertex
     - Labels outside the polygon at each axis
   - **Score breakdown bars** — 7 horizontal bars matching the existing CSS bar style:
     - Color thresholds: >=80% green, >=60% teal, >=40% yellow, >=20% orange, <20% red
     - Show score/max text on each bar
   - Footer with certificate number + date
3. Saves as `score-report-{cert_number}.html` in `uploads/wfeb-certificates/`
4. Returns `array( 'url' => ..., 'attachment_id' => ... )` like `generate_certificate()`

The SVG radar generation helper method `generate_radar_svg( $categories )`:
```php
private function generate_radar_svg( $categories ) {
    $cx = 150; // center x
    $cy = 150; // center y
    $radius = 120; // max radius
    $n = count( $categories ); // 7
    $angle_step = ( 2 * M_PI ) / $n;
    $start_angle = -M_PI / 2; // start from top

    // Build grid rings
    $grid_svg = '';
    foreach ( array( 20, 40, 60, 80, 100 ) as $pct ) {
        $r = $radius * $pct / 100;
        $points = array();
        for ( $i = 0; $i < $n; $i++ ) {
            $angle = $start_angle + $i * $angle_step;
            $points[] = round( $cx + $r * cos( $angle ), 2 ) . ',' . round( $cy + $r * sin( $angle ), 2 );
        }
        $grid_svg .= '<polygon points="' . implode( ' ', $points ) . '" fill="none" stroke="#e2e8f0" stroke-width="1"/>';
    }

    // Axis lines
    $axis_svg = '';
    for ( $i = 0; $i < $n; $i++ ) {
        $angle = $start_angle + $i * $angle_step;
        $x2 = round( $cx + $radius * cos( $angle ), 2 );
        $y2 = round( $cy + $radius * sin( $angle ), 2 );
        $axis_svg .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#e2e8f0" stroke-width="1"/>';
    }

    // Data polygon
    $data_points = array();
    $dot_svg = '';
    foreach ( $categories as $i => $cat ) {
        $pct = $cat['max'] > 0 ? ( $cat['score'] / $cat['max'] ) * 100 : 0;
        $r = $radius * $pct / 100;
        $angle = $start_angle + $i * $angle_step;
        $x = round( $cx + $r * cos( $angle ), 2 );
        $y = round( $cy + $r * sin( $angle ), 2 );
        $data_points[] = $x . ',' . $y;
        $dot_svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="rgba(16,185,129,1)" stroke="#fff" stroke-width="1"/>';
    }
    $data_svg = '<polygon points="' . implode( ' ', $data_points ) . '" fill="rgba(0,0,128,0.2)" stroke="rgba(16,185,129,1)" stroke-width="2"/>';

    // Labels
    $label_svg = '';
    $label_radius = $radius + 20;
    foreach ( $categories as $i => $cat ) {
        $angle = $start_angle + $i * $angle_step;
        $x = round( $cx + $label_radius * cos( $angle ), 2 );
        $y = round( $cy + $label_radius * sin( $angle ), 2 );
        $anchor = 'middle';
        if ( cos( $angle ) < -0.1 ) $anchor = 'end';
        if ( cos( $angle ) > 0.1 ) $anchor = 'start';
        $label_svg .= '<text x="' . $x . '" y="' . $y . '" text-anchor="' . $anchor . '" dominant-baseline="middle" font-size="11" font-weight="600" fill="#334155">' . esc_html( $cat['label'] ) . '</text>';
    }

    return '<svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" style="max-width:300px;margin:0 auto;display:block;">'
        . $grid_svg . $axis_svg . $data_svg . $dot_svg . $label_svg
        . '</svg>';
}
```

### Step 3: Generate score report during certificate creation

In `includes/class-wfeb-certificate.php`, in the `generate()` method, after the PDF is generated and updated (around line 180):

1. Fetch the full exam object: `$exam = WFEB()->exam->get( $exam_id );`
2. Call: `$report_result = WFEB()->pdf->generate_score_report( $certificate, $exam );`
3. If successful, update the certificate record with `score_report_url` and `score_report_attachment_id`

### Step 4: Add score report URL to certificate GET queries

In `class-wfeb-certificate.php`, the `get()`, `get_by_number()`, `get_by_exam()`, `get_by_player()`, and `get_by_coach()` methods already SELECT `c.*` — the new columns will automatically be included since they're in the table. No query changes needed.

### Step 5: Add "Download Score Report" button to player certificate-detail.php

In `templates/player/certificate-detail.php`, after the existing "Download Certificate PDF" button (line 214), add:

```php
<?php if ( ! empty( $certificate->score_report_url ) ) : ?>
    <a href="<?php echo esc_url( $certificate->score_report_url ); ?>" class="wfeb-btn wfeb-btn--download wfeb-btn--lg" target="_blank" rel="noopener noreferrer">
        <span class="dashicons dashicons-chart-area"></span>
        <?php echo esc_html__( 'Download Score Report', 'wfeb' ); ?>
    </a>
<?php endif; ?>
```

### Step 6: Add "Download Score Report" button to coach exam-details.php

In `templates/coach/exam-details.php`, after the "Download Certificate" button (line ~291), add:

```php
<?php if ( ! empty( $certificate->score_report_url ) ) : ?>
    <a href="<?php echo esc_url( $certificate->score_report_url ); ?>" class="wfeb-btn wfeb-btn--sm wfeb-btn--primary" target="_blank" rel="noopener noreferrer">
        <span class="dashicons dashicons-chart-area"></span>
        <?php echo esc_html__( 'Score Report', 'wfeb' ); ?>
    </a>
<?php endif; ?>
```

### Step 7: Commit

```
feat: add downloadable score report with SVG radar chart
```

---

## Task 3: Signed QR Code Verification

**Files:**
- Create: `includes/class-wfeb-qr.php` — pure PHP QR code SVG generator
- Modify: `includes/class-wfeb-certificate.php` — add signature generation + verification methods
- Modify: `includes/class-wfeb-pdf.php` — embed QR code in certificate HTML
- Modify: `templates/pages/verify-certificate.php` — add auto-verify support for `?cert=X&sig=Y`
- Modify: `assets/js/frontend.js` — auto-verify on page load when URL params present
- Modify: `includes/class-wfeb-ajax.php` — add signature-based verification to AJAX handler
- Modify: `wfeb-plugin.php` — require the new QR class

### Step 1: Create the QR code generator class

Create `includes/class-wfeb-qr.php`. This is a self-contained pure PHP QR code generator that outputs inline SVG.

Uses a minimal QR encoder (alphanumeric mode, error correction level M) to generate a QR matrix, then renders it as an SVG with `<rect>` elements.

For simplicity and reliability on the live site (no Composer, no external libs), use a lightweight embedded QR encoder. The class should handle URLs up to ~100 characters (plenty for our verify URLs).

Key method:
```php
public static function svg( $data, $size = 100, $margin = 2 )
```
Returns an SVG string.

**Important:** This must work on PHP 7.4+ with no extensions beyond standard. The QR encoding uses bit manipulation and polynomial math — well-documented algorithm. Use error correction level L (7%) to keep the QR small for short URLs.

### Step 2: Add signature methods to WFEB_Certificate

In `includes/class-wfeb-certificate.php`, add:

```php
/**
 * Get or create the verification secret key.
 */
private function get_verification_secret() {
    $secret = get_option( 'wfeb_verification_secret' );
    if ( empty( $secret ) ) {
        $secret = wp_generate_password( 64, true, true );
        update_option( 'wfeb_verification_secret', $secret, false );
    }
    return $secret;
}

/**
 * Generate a verification signature for a certificate.
 */
public function generate_verification_signature( $cert_number, $player_name, $dob ) {
    $secret = $this->get_verification_secret();
    $data = strtolower( $cert_number . '|' . trim( $player_name ) . '|' . $dob );
    return substr( hash_hmac( 'sha256', $data, $secret ), 0, 16 );
}

/**
 * Get the full verification URL for a certificate.
 */
public function get_verification_url( $certificate ) {
    $sig = $this->generate_verification_signature(
        $certificate->certificate_number,
        $certificate->player_name,
        $certificate->player_dob
    );
    $verify_page_id = get_option( 'wfeb_verify_certificate_page_id' );
    $base_url = $verify_page_id ? get_permalink( $verify_page_id ) : home_url( '/verify-certificate/' );
    return add_query_arg( array(
        'cert' => $certificate->certificate_number,
        'sig'  => $sig,
    ), $base_url );
}

/**
 * Verify a certificate by its signature (QR scan flow).
 */
public function verify_by_signature( $cert_number, $sig ) {
    $certificate = $this->get_by_number( $cert_number );
    if ( ! $certificate ) {
        return new WP_Error( 'not_found', __( 'Certificate not found.', 'wfeb' ) );
    }
    if ( 'active' !== $certificate->status ) {
        return new WP_Error( 'revoked', __( 'This certificate has been revoked.', 'wfeb' ) );
    }
    $expected_sig = $this->generate_verification_signature(
        $certificate->certificate_number,
        $certificate->player_name,
        $certificate->player_dob
    );
    if ( ! hash_equals( $expected_sig, $sig ) ) {
        return new WP_Error( 'invalid_signature', __( 'Invalid verification signature. This certificate link may have been tampered with.', 'wfeb' ) );
    }
    return array(
        'found'       => true,
        'name'        => $certificate->player_name,
        'score'       => $certificate->total_score,
        'level'       => $certificate->achievement_level,
        'date'        => $certificate->exam_date,
        'cert_number' => $certificate->certificate_number,
        'examiner'    => $certificate->coach_name,
    );
}
```

### Step 3: Embed QR code in certificate HTML

In `includes/class-wfeb-pdf.php`, in the `get_certificate_html()` method:

1. Generate the verification URL: `$verify_url = WFEB()->certificate->get_verification_url( $certificate );`
2. Generate QR SVG: `$qr_svg = WFEB_QR::svg( $verify_url, 80 );`
3. Insert between the two footer signature items (around line 485, inside `.cert-footer`):

Add a third footer item between the two existing ones:
```html
<div class="cert-footer-item cert-footer-qr">
    {$qr_svg}
    <p class="cert-footer-label">Scan to Verify</p>
</div>
```

Add CSS for `.cert-footer-qr`:
```css
.cert-footer-qr {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.cert-footer-qr svg {
    width: 25mm;
    height: 25mm;
}
```

### Step 4: Add signature-based verification to AJAX handler

In `includes/class-wfeb-ajax.php`, in the `wfeb_verify_certificate()` method, add a signature-based path at the top (before the existing name/cert_number/dob flow):

```php
// QR signature-based verification (auto-verify from QR scan).
$qr_cert = isset( $_POST['qr_cert'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_cert'] ) ) : '';
$qr_sig  = isset( $_POST['qr_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_sig'] ) ) : '';

if ( ! empty( $qr_cert ) && ! empty( $qr_sig ) ) {
    $result = WFEB()->certificate->verify_by_signature( $qr_cert, $qr_sig );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }
    wp_send_json_success( array(
        'message' => __( 'Certificate verified successfully.', 'wfeb' ),
        'data'    => $result,
    ) );
}
```

### Step 5: Update verify-certificate.php template for auto-verify

In `templates/pages/verify-certificate.php`, after the existing `<script>` that `wp_head()` outputs, add PHP to detect URL params and pass them to JS:

Before `</head>`, add:
```php
<?php
$auto_cert = isset( $_GET['cert'] ) ? sanitize_text_field( wp_unslash( $_GET['cert'] ) ) : '';
$auto_sig  = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
?>
```

Then before `wp_footer()`, add an inline script:
```php
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
```

### Step 6: Add auto-verify logic to frontend.js

In `assets/js/frontend.js`, after the verify form handler (around line 526), add:

```javascript
// Auto-verify from QR code URL params
if (typeof wfebAutoVerify !== 'undefined' && wfebAutoVerify.cert && wfebAutoVerify.sig) {
    (function() {
        console.log('[WFEB] Auto-verifying certificate from QR code');

        // Hide the form, show loading state
        var $form = $('#wfeb-verify-form');
        var $resultsWrap = $('#wfeb-verify-results');
        var $found = $('#wfeb-verify-found');
        var $notFound = $('#wfeb-verify-not-found');

        $form.hide();
        $resultsWrap.show();

        $.ajax({
            url: wfebAutoVerify.ajax_url,
            type: 'POST',
            data: {
                action: 'wfeb_verify_certificate',
                security: wfebAutoVerify.nonce,
                qr_cert: wfebAutoVerify.cert,
                qr_sig: wfebAutoVerify.sig
            },
            success: function(response) {
                if (response.success && response.data && response.data.data) {
                    var certData = response.data.data;
                    $found.show();
                    $notFound.hide();

                    $('#wfeb-result-name').text(certData.player_name || certData.name || '--');
                    $('#wfeb-result-score').text(certData.total_score || certData.score || '--');
                    $('#wfeb-result-date').text(certData.exam_date || certData.date || '--');
                    $('#wfeb-result-cert').text(certData.certificate_number || certData.cert_number || '--');
                    $('#wfeb-result-examiner').text(certData.examiner_name || certData.examiner || '--');

                    var level = certData.achievement_level || certData.level || '';
                    var $badge = $('#wfeb-result-badge');
                    $badge.text(level);
                    $badge.attr('class', 'wfeb-verify-badge wfeb-verify-badge--' + level.toLowerCase().replace(/[^a-z0-9]/g, ''));
                } else {
                    $found.hide();
                    $notFound.show();
                }
            },
            error: function() {
                $found.hide();
                $notFound.show();
            }
        });
    })();
}
```

### Step 7: Require class-wfeb-qr.php in wfeb-plugin.php

In the `includes()` method, add `require_once` for `class-wfeb-qr.php` alongside the other includes.

### Step 8: Commit

```
feat: add HMAC-signed QR code verification to certificates
```

---

## Task 4: Regenerate Existing Certificates (Optional Migration)

For existing certificates that were issued before this update (no QR code, no score report):

Add a WP-CLI command or admin button that loops through all certificates, regenerates their HTML files with the QR code, and generates score reports. This can be a simple method in `WFEB_Certificate`:

```php
public function regenerate_all_files() {
    $all_certs = $this->get_all( array( 'limit' => 9999 ) );
    $count = 0;
    foreach ( $all_certs as $cert ) {
        $full_cert = $this->get( $cert->id );
        $exam = WFEB()->exam->get( $cert->exam_id );
        if ( ! $full_cert || ! $exam ) continue;

        // Regenerate certificate HTML (now with QR)
        $pdf_result = WFEB()->pdf->generate_certificate( $full_cert );
        if ( ! is_wp_error( $pdf_result ) ) {
            // Update PDF URL
            global $wpdb;
            $wpdb->update( $this->table, array(
                'pdf_url' => esc_url_raw( $pdf_result['url'] ),
                'pdf_attachment_id' => absint( $pdf_result['attachment_id'] ),
            ), array( 'id' => $cert->id ) );
        }

        // Generate score report
        $report_result = WFEB()->pdf->generate_score_report( $full_cert, $exam );
        if ( ! is_wp_error( $report_result ) ) {
            global $wpdb;
            $wpdb->update( $this->table, array(
                'score_report_url' => esc_url_raw( $report_result['url'] ),
                'score_report_attachment_id' => absint( $report_result['attachment_id'] ),
            ), array( 'id' => $cert->id ) );
        }
        $count++;
    }
    return $count;
}
```

Add an admin AJAX handler to trigger this. Add a button in the WFEB admin settings page.

### Commit

```
feat: add certificate regeneration for QR codes and score reports
```

---

## Task 5: Version Bump + Final Commit

Bump version in `wfeb-plugin.php` header and `WFEB_VERSION` constant.

```
chore: bump version to 2.4.0
```

---

## Live Site Considerations

- **No Composer on live site** — QR class must be self-contained PHP, no autoloader dependencies
- **PHP 7.4 compatibility** — no named arguments, no match expressions, no union types
- **dbDelta for schema changes** — `dbDelta()` handles ALTER TABLE for existing installs when the CREATE TABLE statement includes new columns
- **Transient cache** — existing certificate HTML files are stored as WP attachments; regenerating overwrites the file at the same path, so URLs stay the same
- **HMAC secret** — auto-generated once, stored as WP option, persists across updates
- **File permissions** — `uploads/wfeb-certificates/` already exists and is writable (certificate HTML files are stored there)
