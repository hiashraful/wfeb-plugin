# WFEB Admin Dashboard Redesign & Bug Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign all 7 WFEB wp-admin pages with a minimalist Google-card UI and fix all broken JS/CSS/AJAX mismatches.

**Architecture:** One shared CSS file (`assets/css/admin.css`) + one shared JS file (`assets/js/admin.js`) serve all 7 admin page templates. Templates are pure PHP that output HTML using a standardized set of CSS class names. The JS communicates with the backend exclusively via AJAX actions in `class-wfeb-ajax.php`. Settings save is AJAX-only (no form POST handler exists).

**Tech Stack:** PHP 7.4+, WordPress admin API, vanilla CSS (no framework), jQuery (WP bundled), Chart.js 4 (analytics only)

---

## Design System Reference (ALL tasks must follow this)

All CSS class names used in templates MUST match this spec exactly.

### CSS Variables
```css
--wfeb-bg:        #f1f5f9;   /* page background */
--wfeb-surface:   #ffffff;   /* card/table background */
--wfeb-border:    #e2e8f0;   /* borders */
--wfeb-primary:   #1e293b;   /* primary text */
--wfeb-secondary: #64748b;   /* secondary text */
--wfeb-accent:    #10B981;   /* green - primary action */
--wfeb-blue:      #3b82f6;   /* info / links */
--wfeb-danger:    #ef4444;   /* delete / reject */
--wfeb-warning:   #f59e0b;   /* pending / suspend */
--wfeb-success:   #22c55e;   /* approved / completed */
--wfeb-radius:    16px;      /* card border radius */
--wfeb-shadow:    0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.04);
```

### Layout Pattern (every page)
```html
<div class="wrap wfeb-wrap">
  <div class="wfeb-page-header">
    <h1 class="wfeb-page-title">Page Title</h1>
    <div class="wfeb-page-actions"><!-- optional buttons --></div>
  </div>
  <!-- stat cards if needed -->
  <!-- filter/tabs bar if needed -->
  <!-- main content card -->
  <!-- pagination if needed -->
</div>
```

### Stat Card HTML Pattern
```html
<div class="wfeb-stats-grid">
  <div class="wfeb-stat-card">
    <span class="wfeb-stat-label">Total Coaches</span>
    <div class="wfeb-stat-number">42</div>
    <div class="wfeb-stat-footer"><!-- optional context --></div>
  </div>
</div>
```

### Table Card HTML Pattern
```html
<div class="wfeb-table-card">
  <div class="wfeb-table-header">
    <div class="wfeb-search-box">...</div>
    <div class="wfeb-table-actions">...</div>
  </div>
  <table class="wfeb-table">
    <thead><tr><th>...</th></tr></thead>
    <tbody><tr><td>...</td></tr></tbody>
  </table>
  <div class="wfeb-pagination">...</div>
</div>
```

### Status Badge HTML Pattern
```html
<!-- Use these exact classes - JS updates them by these names -->
<span class="wfeb-badge wfeb-badge--approved">Approved</span>
<span class="wfeb-badge wfeb-badge--pending">Pending</span>
<span class="wfeb-badge wfeb-badge--rejected">Rejected</span>
<span class="wfeb-badge wfeb-badge--suspended">Suspended</span>
<span class="wfeb-badge wfeb-badge--completed">Completed</span>
<span class="wfeb-badge wfeb-badge--active">Active</span>
<span class="wfeb-badge wfeb-badge--revoked">Revoked</span>
<span class="wfeb-badge wfeb-badge--draft">Draft</span>
```

### Button Classes
```html
<button class="wfeb-btn wfeb-btn--primary">Save</button>
<button class="wfeb-btn wfeb-btn--danger wfeb-btn--sm">Delete</button>
<button class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">View</button>
<a class="wfeb-btn wfeb-btn--primary">+ Add</a>
```

### JS-targeted classes (MUST match between templates and admin.js)
- Coach approve button: `wfeb-approve-coach` with `data-coach-id` and `data-coach-name`
- Coach reject button: `wfeb-reject-coach` with `data-coach-id` and `data-coach-name`
- Coach suspend button: `wfeb-suspend-coach` with `data-coach-id` and `data-coach-name`
- Coach remove button: `wfeb-remove-coach` with `data-coach-id` and `data-coach-name`
- Certificate revoke button: `wfeb-revoke-certificate` with `data-cert-id` and `data-cert-number`
- Settings form: `wfeb-settings-form` (JS intercepts submit)
- Credit adjust form: `wfeb-admin-credit-form`
- Status badge (for JS DOM updates): `wfeb-badge` (JS replaces class to `wfeb-badge wfeb-badge--{status}`)

### Settings AJAX contract
The `wfeb_save_settings` AJAX handler (class-wfeb-ajax.php) reads:
- `$_POST['tab']` — tab slug
- `$_POST['security']` — nonce

Tab slugs and fields handled:
| Tab slug | Fields saved |
|----------|-------------|
| `general` | `wfeb_coach_approval_mode`, `wfeb_cert_prefix`, `wfeb_cert_start`, `wfeb_logo` |
| `woocommerce` | `wfeb_credit_product_id`, `wfeb_credit_price` |
| `email` | `wfeb_email_from_name`, `wfeb_email_from_address` |
| `exam` | `wfeb_achievement_thresholds` (serialized array) |
| `certificate` | `wfeb_cert_background`, `wfeb_cert_authoriser_name` |

The JS must send: `{ action: 'wfeb_save_settings', tab: '<slug>', security: wfeb_admin.nonce, ...fields }`

---

## Task 1: CSS Foundation — Rewrite admin.css

**Files:**
- Modify: `assets/css/admin.css` (full rewrite)

**What to write:** A complete minimalist CSS file following the design system above.

The CSS must include:
1. CSS custom properties (variables above)
2. `.wfeb-wrap` — `max-width: 1280px; margin: 24px 20px 0 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;`
3. `.wfeb-page-header` — flex row, space-between, align-center, `margin-bottom: 24px`
4. `.wfeb-page-title` — `font-size: 22px; font-weight: 700; color: var(--wfeb-primary); margin: 0; padding: 0;`
5. `.wfeb-stats-grid` — `display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;`
6. `.wfeb-stat-card` — `background: var(--wfeb-surface); border-radius: var(--wfeb-radius); box-shadow: var(--wfeb-shadow); padding: 24px; display: flex; flex-direction: column; gap: 8px;`
7. `.wfeb-stat-label` — `font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: var(--wfeb-secondary);`
8. `.wfeb-stat-number` — `font-size: 36px; font-weight: 800; color: var(--wfeb-primary); line-height: 1;`
9. `.wfeb-stat-footer` — `font-size: 12px; color: var(--wfeb-secondary); margin-top: auto;`
10. `.wfeb-table-card` — `background: var(--wfeb-surface); border-radius: var(--wfeb-radius); box-shadow: var(--wfeb-shadow); overflow: hidden;`
11. `.wfeb-table-header` — `display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid var(--wfeb-border);`
12. `.wfeb-search-box` — flex row, gap 8px, input with `border: 1px solid var(--wfeb-border); border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none; width: 220px;` focus: `border-color: var(--wfeb-accent); box-shadow: 0 0 0 3px rgba(16,185,129,.1);`
13. `.wfeb-table` — `width: 100%; border-collapse: collapse;`
14. `.wfeb-table thead th` — `padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--wfeb-secondary); background: #f8fafc; border-bottom: 1px solid var(--wfeb-border); white-space: nowrap;`
15. `.wfeb-table tbody td` — `padding: 14px 16px; font-size: 13px; color: var(--wfeb-primary); border-bottom: 1px solid #f1f5f9; vertical-align: middle;`
16. `.wfeb-table tbody tr:last-child td` — `border-bottom: none;`
17. `.wfeb-table tbody tr:hover td` — `background: #f8fafc;`
18. `.wfeb-badge` — `display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; white-space: nowrap;` — with `::before` pseudo: `content: ''; width: 6px; height: 6px; border-radius: 50%;`
19. Badge color variants: `--approved/--active/--completed`: bg `#dcfce7`, text `#166534`, dot `#22c55e`; `--pending/--draft`: bg `#fef9c3`, text `#854d0e`, dot `#eab308`; `--rejected/--revoked`: bg `#fee2e2`, text `#991b1b`, dot `#ef4444`; `--suspended`: bg `#f1f5f9`, text `#475569`, dot `#94a3b8`
20. `.wfeb-btn` — `display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; font-size: 13px; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: all .15s ease; line-height: 1;`
21. `.wfeb-btn--primary`: bg `var(--wfeb-accent)`, color white; hover: bg `#059669`
22. `.wfeb-btn--danger`: bg `var(--wfeb-danger)`, color white; hover: bg `#dc2626`
23. `.wfeb-btn--ghost`: bg transparent, border `1px solid var(--wfeb-border)`, color `var(--wfeb-secondary)`; hover: bg `#f8fafc`, color `var(--wfeb-primary)`
24. `.wfeb-btn--warning`: bg `var(--wfeb-warning)`, color white
25. `.wfeb-btn--sm`: `padding: 5px 12px; font-size: 12px; border-radius: 6px;`
26. `.wfeb-pagination` — `display: flex; align-items: center; justify-content: center; gap: 6px; padding: 20px;`
27. `.wfeb-page-btn` — `width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--wfeb-border); font-size: 13px; font-weight: 500; color: var(--wfeb-secondary); cursor: pointer; text-decoration: none; background: var(--wfeb-surface); transition: all .15s;`
28. `.wfeb-page-btn.active` — bg `var(--wfeb-accent)`, color white, border-color `var(--wfeb-accent)`
29. `.wfeb-page-btn:hover:not(.active)` — bg `#f8fafc`, color `var(--wfeb-primary)`
30. `.wfeb-filter-bar` — `display: flex; align-items: center; gap: 10px; padding: 16px 24px; border-bottom: 1px solid var(--wfeb-border); flex-wrap: wrap;`
31. `.wfeb-filter-group` — `display: flex; flex-direction: column; gap: 4px;` with label `font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--wfeb-secondary);` and input/select `padding: 7px 10px; border: 1px solid var(--wfeb-border); border-radius: 8px; font-size: 13px; outline: none; min-width: 120px;`
32. `.wfeb-status-tabs` — `display: flex; gap: 0; border-bottom: 1px solid var(--wfeb-border); padding: 0 24px; background: var(--wfeb-surface); border-radius: var(--wfeb-radius) var(--wfeb-radius) 0 0; list-style: none; margin: 0;`
33. `.wfeb-status-tabs li a` — `display: block; padding: 14px 16px; font-size: 13px; font-weight: 500; color: var(--wfeb-secondary); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: all .15s;`
34. `.wfeb-status-tabs li a.current` — `color: var(--wfeb-accent); border-bottom-color: var(--wfeb-accent); font-weight: 600;`
35. `.wfeb-card` — `background: var(--wfeb-surface); border-radius: var(--wfeb-radius); box-shadow: var(--wfeb-shadow); padding: 24px; margin-bottom: 20px;`
36. `.wfeb-card-title` — `font-size: 15px; font-weight: 700; color: var(--wfeb-primary); margin: 0 0 16px; padding-bottom: 14px; border-bottom: 1px solid var(--wfeb-border);`
37. `.wfeb-detail-grid` — `display: grid; grid-template-columns: 2fr 1fr; gap: 20px;`
38. `.wfeb-detail-row` — `display: flex; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px;` with `.wfeb-detail-label` `min-width: 160px; font-weight: 600; color: var(--wfeb-secondary);` and `.wfeb-detail-value` `color: var(--wfeb-primary); flex: 1;`
39. Settings tabs: `.wfeb-nav-tabs` — WP `nav-tab-wrapper` override: `background: var(--wfeb-surface); border-radius: var(--wfeb-radius) var(--wfeb-radius) 0 0; padding: 0 8px; border-bottom: 1px solid var(--wfeb-border); overflow: hidden; margin-bottom: 0;` with `.nav-tab` override: `border: none; border-bottom: 2px solid transparent; border-radius: 0; background: transparent; color: var(--wfeb-secondary); font-size: 13px; padding: 14px 18px; margin: 0; transition: all .15s;` and `.nav-tab-active` override: `color: var(--wfeb-accent); border-bottom-color: var(--wfeb-accent); font-weight: 600; background: transparent; box-shadow: none;`
40. `.wfeb-settings-content` — `background: var(--wfeb-surface); border-radius: 0 0 var(--wfeb-radius) var(--wfeb-radius); box-shadow: var(--wfeb-shadow); padding: 28px; margin-bottom: 20px;`
41. `.wfeb-settings-form .form-table` overrides: `margin: 0;` with `th` `padding: 18px 20px 18px 0; width: 220px; font-size: 13px; font-weight: 600; color: var(--wfeb-primary); vertical-align: top;` and `td` `padding: 14px 0;` — all inputs `border-radius: 8px; border: 1px solid var(--wfeb-border); padding: 8px 12px; font-size: 13px;` on focus `border-color: var(--wfeb-accent); box-shadow: 0 0 0 3px rgba(16,185,129,.1); outline: none;`
42. `.wfeb-charts-grid` — `display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;`
43. `.wfeb-chart-card` — same as `.wfeb-card`, with `canvas { width: 100% !important; max-height: 280px; }`
44. `.wfeb-empty-state` — `text-align: center; padding: 60px 20px; color: var(--wfeb-secondary);` with `p { font-size: 14px; margin: 8px 0 0; }`
45. Modal: `.wfeb-modal-overlay` — `position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 100050; display: none; align-items: center; justify-content: center;` `.active` — `display: flex;` `.wfeb-modal` — `background: #fff; border-radius: var(--wfeb-radius); box-shadow: 0 20px 60px rgba(0,0,0,.2); max-width: 480px; width: 92%; animation: wfebModalIn .18s ease;` `.wfeb-modal-header` — `padding: 20px 24px; border-bottom: 1px solid var(--wfeb-border); display: flex; justify-content: space-between; align-items: center;` `.wfeb-modal-title` — `font-size: 16px; font-weight: 700;` `.wfeb-modal-body` — `padding: 24px;` `.wfeb-modal-footer` — `padding: 16px 24px; border-top: 1px solid var(--wfeb-border); display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-radius: 0 0 var(--wfeb-radius) var(--wfeb-radius);`
46. Responsive: at 1024px stats grid becomes 2-col; at 782px detail-grid becomes 1-col, stats grid 1-col
47. Notice: `.wfeb-notice` — uses WP `.notice` classes but ensure they render in the `.wfeb-wrap` context

**After writing:** No test step needed for CSS — visual verification in browser.

---

## Task 2: JS Fix — Fix admin.js class mismatches + settings save

**Files:**
- Modify: `assets/js/admin.js`

**Bugs to fix:**

### 2a. Fix settings form intercept
Current broken code (line ~359):
```js
$(document).on('submit', '.wfeb-settings-form', function(e) {
```
Template uses class `.wfeb-admin-settings-form`. Change the selector to match template OR change template — we will use `.wfeb-settings-form` in the NEW template (Task 8), so keep this selector but also ensure the NEW settings template uses `.wfeb-settings-form`.

This is ALREADY the correct selector for the NEW templates we will write. No change needed here — the new template in Task 8 will use this class.

### 2b. Fix settings form data — send `tab` field (not `wfeb_settings_tab`)
The AJAX handler reads `$_POST['tab']`. Current JS uses `FormData` which will include the hidden field named `wfeb_settings_tab`. Change the JS to explicitly append `tab` from the hidden field:

Replace the settings save AJAX block with:
```js
$(document).on('submit', '.wfeb-settings-form', function(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]');
    $btn.prop('disabled', true);

    var formData = new FormData(this);
    // The hidden field is named 'wfeb_tab' in new templates
    // AJAX handler reads $_POST['tab'] — explicitly set it
    var tab = $form.find('[name="wfeb_tab"]').val();
    formData.set('tab', tab);
    formData.set('action', 'wfeb_save_settings');
    formData.set('security', wfeb_admin.nonce);

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $btn.prop('disabled', false);
            if (response.success) {
                showAdminNotice('Settings saved.', 'success');
            } else {
                showAdminNotice(response.data.message || 'Failed to save settings.', 'error');
            }
        },
        error: function() {
            $btn.prop('disabled', false);
            showAdminNotice('Error saving settings.', 'error');
        }
    });
});
```

### 2c. Fix DOM updates after coach approve/reject/suspend
After approve, JS does:
```js
$row.find('.wfeb-status-badge').text('Approved').attr('class', 'wfeb-status-badge wfeb-status-badge--approved');
```
But new templates use `.wfeb-badge`. Update ALL status badge DOM updates in admin.js:

Replace all occurrences of `.wfeb-status-badge` with `.wfeb-badge` in the DOM update code. And update the class replacement pattern from `wfeb-status-badge wfeb-status-badge--{status}` to `wfeb-badge wfeb-badge--{status}`.

Specifically fix these locations:
- Line ~63: `$row.find('.wfeb-status-badge')...` → `.wfeb-badge`
- Line ~121: `$row.find('.wfeb-status-badge')...` → `.wfeb-badge`
- Line ~167: `$row.find('.wfeb-status-badge')...` → `.wfeb-badge`
- Line ~269: `$row.find('.wfeb-status-badge')...` → `.wfeb-badge`

Also fix the class names applied:
- `wfeb-status-badge wfeb-status-badge--approved` → `wfeb-badge wfeb-badge--approved`
- `wfeb-status-badge wfeb-status-badge--rejected` → `wfeb-badge wfeb-badge--rejected`
- `wfeb-status-badge wfeb-status-badge--suspended` → `wfeb-badge wfeb-badge--suspended`
- `wfeb-status-badge wfeb-status-badge--revoked` → `wfeb-badge wfeb-badge--revoked`

### 2d. Remove analytics chart duplication
The analytics template has its own inline `<script>` that correctly initializes all charts using the right canvas IDs. The analytics section in admin.js (`renderAnalyticsCharts`) uses DIFFERENT canvas IDs and would conflict.

Remove the entire analytics chart section from admin.js (the `initAnalyticsCharts` IIFE at ~lines 399-444 and `renderAnalyticsCharts` function at ~lines 451-587). The inline script in analytics.php handles charts correctly.

### 2e. Add certificate revoke class alias
The new `certificates-list.php` template will use class `wfeb-revoke-certificate` (already correct in admin.js). No change needed — just ensure the NEW template uses this exact class.

---

## Task 3: Fix settings AJAX backend — wfeb_save_settings

**Files:**
- Modify: `includes/class-wfeb-ajax.php` — function `wfeb_save_settings` (line ~1899)

**Bugs to fix:**

### 3a. Add missing field saves in 'general' case
```php
case 'general':
    if ( isset( $_POST['wfeb_coach_approval_mode'] ) ) {
        $mode = sanitize_text_field( wp_unslash( $_POST['wfeb_coach_approval_mode'] ) );
        if ( in_array( $mode, array( 'manual', 'auto' ), true ) ) {
            update_option( 'wfeb_coach_approval_mode', $mode );
        }
    }
    // ADD THESE:
    if ( isset( $_POST['wfeb_cert_prefix'] ) ) {
        update_option( 'wfeb_cert_prefix', sanitize_text_field( wp_unslash( $_POST['wfeb_cert_prefix'] ) ) );
    }
    if ( isset( $_POST['wfeb_cert_start'] ) ) {
        update_option( 'wfeb_cert_start', absint( $_POST['wfeb_cert_start'] ) );
    }
    if ( isset( $_POST['wfeb_logo'] ) ) {
        update_option( 'wfeb_logo', esc_url_raw( wp_unslash( $_POST['wfeb_logo'] ) ) );
    }
    break;
```

### 3b. Fix 'email' tab name (currently 'emails')
Change `case 'emails':` to `case 'email':` — the template tab slug is `email`.

### 3c. Fix 'certificate' tab name (currently 'certificates')
Change `case 'certificates':` to `case 'certificate':` — BUT this case currently only saves `cert_prefix` and `cert_start` which now belong in 'general'. Replace the entire `case 'certificates':` block with `case 'certificate':` saving `cert_background` and `cert_authoriser_name`:
```php
case 'certificate':
    if ( isset( $_POST['wfeb_cert_background'] ) ) {
        update_option( 'wfeb_cert_background', esc_url_raw( wp_unslash( $_POST['wfeb_cert_background'] ) ) );
    }
    if ( isset( $_POST['wfeb_cert_authoriser_name'] ) ) {
        update_option( 'wfeb_cert_authoriser_name', sanitize_text_field( wp_unslash( $_POST['wfeb_cert_authoriser_name'] ) ) );
    }
    break;
```

### 3d. Add 'exam' case for achievement thresholds
Add a new case BEFORE the `default:`:
```php
case 'exam':
    if ( isset( $_POST['thresholds'] ) && is_array( $_POST['thresholds'] ) ) {
        $thresholds = array();
        foreach ( $_POST['thresholds'] as $item ) {
            if ( ! isset( $item['level'], $item['playing_level'], $item['min'] ) ) {
                continue;
            }
            $thresholds[] = array(
                'level'         => sanitize_text_field( wp_unslash( $item['level'] ) ),
                'playing_level' => sanitize_text_field( wp_unslash( $item['playing_level'] ) ),
                'min'           => absint( $item['min'] ),
            );
        }
        update_option( 'wfeb_achievement_thresholds', $thresholds );
    }
    break;
```

### 3e. Fix WooCommerce case — add credit_price field
Add to `case 'woocommerce':`:
```php
if ( isset( $_POST['wfeb_credit_price'] ) ) {
    update_option( 'wfeb_credit_price', sanitize_text_field( wp_unslash( $_POST['wfeb_credit_price'] ) ) );
}
```

---

## Task 4: Dashboard Template — templates/admin/dashboard.php

**Files:**
- Modify: `templates/admin/dashboard.php`

**What to write:** Full rewrite using the design system.

**Structure:**
1. PHP data fetching stays the same (stats, pending coaches, recent exams)
2. Page header: `<h1>WFEB Dashboard</h1>` in `.wfeb-page-header`
3. Stats grid: 6 stat cards (Total Coaches, Total Players, Total Exams, Total Certificates, Pending Approvals, Credits Sold) using `.wfeb-stats-grid` + `.wfeb-stat-card`
4. If pending coaches exist: a `.wfeb-table-card` with `.wfeb-status-tabs` style header "Pending Coach Approvals", then a `.wfeb-table` with approve/reject buttons using classes `wfeb-approve-coach` and `wfeb-reject-coach` with `data-coach-id` and `data-coach-name`
5. Recent exams: a `.wfeb-table-card` with "Recent Exam Activity" header, `.wfeb-table`, use `.wfeb-badge` for level and status

**Note on stat-footer:** For pending approvals card, if `$pending_count > 0`, add a `.wfeb-stat-footer` with a link to coaches page: `<a href="...wfeb-coaches&status=pending">View pending</a>`.

---

## Task 5: Coaches Templates

**Files:**
- Modify: `templates/admin/coaches-list.php`
- Modify: `templates/admin/coach-details.php`

### coaches-list.php
1. `.wfeb-wrap` with page header "Coaches"
2. `.wfeb-table-card` wrapping everything
3. Status tabs using `.wfeb-status-tabs` `<ul>` with All/Pending/Approved/Rejected/Suspended
4. `.wfeb-table-header` with search form (input `type="search"` name `s`) + Reset link
5. `.wfeb-table` with columns: Name, Email, NGB #, Status, Credits, Registered, Actions
6. Status badges: `<span class="wfeb-badge wfeb-badge--{$coach->status}">`
7. Action buttons per row:
   - Always: `<a href="...coach_id=X" class="wfeb-btn wfeb-btn--ghost wfeb-btn--sm">View</a>`
   - If pending: `<button class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-approve-coach" data-coach-id="X" data-coach-name="Y">Approve</button>` and `<button class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-reject-coach" data-coach-id="X" data-coach-name="Y">Reject</button>`
   - If approved: `<button class="wfeb-btn wfeb-btn--warning wfeb-btn--sm wfeb-suspend-coach" ...>Suspend</button>`
   - Always: `<button class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-remove-coach" ...>Remove</button>`
8. Pagination using `.wfeb-pagination` with numbered page buttons

### coach-details.php
Read the current file first. Keep all PHP data fetching. Redesign the layout:
1. Page header: Coach name + back link (`← Back to Coaches`) + status badge
2. Two-column layout with `.wfeb-detail-grid`
3. Left column: Coach info card (`.wfeb-card`) with `.wfeb-detail-row` rows for each field, then Players table, then Credit Transactions table
4. Right column: Actions card — approve/reject/suspend buttons (use JS-targeted classes), credit adjustment form (`.wfeb-admin-credit-form`), credit balance stat

---

## Task 6: Players Template — templates/admin/players-list.php

**Files:**
- Modify: `templates/admin/players-list.php`

**What to write:**
1. `.wfeb-wrap` with page header "Players"
2. `.wfeb-table-card`
3. `.wfeb-table-header` with search box (name `s`)
4. `.wfeb-table` with columns: Name, DOB, Coach (linked), Exams, Best Score, Best Level, Registered
5. Status badges for best level using `.wfeb-badge` with dynamic level class (use `wfeb_get_level_color()` is legacy — replace with a PHP helper that maps level to CSS class: `wfeb_level_badge_class($level)` — but if the helper doesn't exist, use inline style or just use `wfeb-badge` without color variant for levels)

**Note:** Achievement levels (MASTERY, DIAMOND, etc.) don't map to the standard status classes. Use a separate `.wfeb-level-badge` with inline background from `wfeb_get_level_color()`.
```html
<span class="wfeb-badge" style="background: <?php echo esc_attr(wfeb_get_level_color($player->best_level)); ?>20; color: <?php echo esc_attr(wfeb_get_level_color($player->best_level)); ?>;">
    <?php echo esc_html($player->best_level); ?>
</span>
```

6. Pagination with `.wfeb-pagination`

---

## Task 7: Exams Templates

**Files:**
- Modify: `templates/admin/exams-list.php`
- Modify: `templates/admin/exam-details.php`

### exams-list.php
1. `.wfeb-wrap` with page header "Exams"
2. `.wfeb-table-card`
3. Filter bar (`.wfeb-filter-bar`) with: search, status dropdown (All/Draft/Completed), date from/to, Filter + Reset buttons
4. `.wfeb-table` with columns: Date, Player, Coach (linked), Score, Level, Status, Cert #, Actions
5. Level badge: same inline-style approach as players
6. Status badge: `.wfeb-badge wfeb-badge--{$exam->status}`
7. View action links to `?page=wfeb-exams&exam_id=X`
8. Pagination

### exam-details.php
Read the current file first. Redesign:
1. Page header: "Exam #ID — Player Name" + Back link + status badge
2. Two column layout
3. Left: exam details card with `.wfeb-detail-row` for all fields, scores per category (if available), level badge
4. Right: Certificate card (if certificate exists: cert number, issued date, link to PDF); Player info card with link to coach

---

## Task 8: Certificates Template — templates/admin/certificates-list.php

**Files:**
- Modify: `templates/admin/certificates-list.php`

**What to write:**
1. `.wfeb-wrap` with page header "Certificates"
2. Status tabs: All / Active / Revoked
3. `.wfeb-table-card`
4. Filter bar: search (cert #, player, coach), date from/to, Filter + Reset
5. `.wfeb-table` columns: Cert #, Player, Coach (linked), Score, Level, Date Issued, Status, Actions
6. **CRITICAL:** Revoke button MUST use class `wfeb-revoke-certificate` with `data-cert-id` and `data-cert-number`:
   ```html
   <button class="wfeb-btn wfeb-btn--danger wfeb-btn--sm wfeb-revoke-certificate"
       data-cert-id="<?php echo absint($cert->id); ?>"
       data-cert-number="<?php echo esc_attr($cert->certificate_number); ?>">
       Revoke
   </button>
   ```
7. PDF view link (if `$cert->pdf_url`) and Exam link
8. Pagination

---

## Task 9: Analytics Template — templates/admin/analytics.php

**Files:**
- Modify: `templates/admin/analytics.php`

**What to write:**
1. `.wfeb-wrap` with page header "Analytics"
2. Filter bar with date range + Filter/Reset buttons
3. Stats summary row (4 cards): Total Exams, Total Coaches, Total Certificates, Average Score
4. `.wfeb-charts-grid` (2x2 grid) with 4 `.wfeb-chart-card` cards:
   - "Exams Per Month" — `<canvas id="wfeb-chart-exams-month">`
   - "Score Distribution" — `<canvas id="wfeb-chart-score-dist">`
   - "Credits Revenue" — `<canvas id="wfeb-chart-revenue">`
   - "Achievement Levels" — `<canvas id="wfeb-chart-levels">`
5. Top Coaches table (`.wfeb-table-card`)
6. **IMPORTANT:** Keep the existing inline `<script>` block that initializes Chart.js using these exact canvas IDs. The inline script is correct and self-contained. Do NOT move chart init to admin.js.
7. Pass extra stats to template: add to `WFEB_Admin_Analytics::render()`:
   - `$total_exams = WFEB()->exam->get_count();`
   - `$avg_score` via DB query: `SELECT AVG(total_score) FROM wfeb_exams WHERE status='completed'`

---

## Task 10: Settings Template — templates/admin/settings.php

**Files:**
- Modify: `templates/admin/settings.php`

**What to write — CRITICAL requirements for settings to work:**

1. Each tab form MUST have:
   - `class="wfeb-settings-form"` (JS intercepts this)
   - A hidden field `<input type="hidden" name="wfeb_tab" value="{tab_slug}">` (JS reads this as `tab`)
   - Tab slugs MUST be: `general`, `woocommerce`, `email`, `exam`, `certificate`
   - Nonce field: `<?php wp_nonce_field('wfeb_admin_nonce', 'security'); ?>`

2. Layout:
   - Page title "Settings"
   - WP nav-tabs (`nav-tab-wrapper`) for tab navigation (server-side tab switching via `?tab=`)
   - Tab content wrapped in `.wfeb-settings-content`
   - Each tab is a `<form class="wfeb-settings-form">` using `.form-table`

3. **General tab** fields: cert_prefix, cert_start, coach_approval_mode (select: manual/auto), wfeb_logo (text + Upload button)

4. **WooCommerce tab** fields: credit_product_id, credit_price (text)

5. **Email tab** fields: email_from_name, email_from_address, Test Email button (`id="wfeb-test-email-btn"`, address field `id="wfeb-test-email-address"`)

6. **Exam tab** fields: achievement thresholds table (editable min scores), scoring reference tables (read-only)

7. **Certificate tab** fields: cert_background (text + Upload), cert_authoriser_name, Preview button

8. Add Pages section at BOTTOM of General tab (read-only display of configured page IDs):
   ```php
   // Show configured page links
   $pages = [
       'Coach Login'          => get_option('wfeb_coach_login_page_id'),
       'Coach Registration'   => get_option('wfeb_coach_registration_page_id'),
       'Coach Dashboard'      => get_option('wfeb_coach_dashboard_page_id'),
       'Player Login'         => get_option('wfeb_player_login_page_id'),
       'Player Dashboard'     => get_option('wfeb_player_dashboard_page_id'),
       'Verify Certificate'   => get_option('wfeb_verify_certificate_page_id'),
   ];
   ```
   Display as a table with page name, edit link (to WP page editor), and front-end preview link.

---

## Task 11: Final verification checklist

After all tasks are complete, verify in browser:

1. **Dashboard:** Stats cards show numbers. If pending coaches exist, approve button fires AJAX (check browser console for `[WFEB] Approving coach:`).

2. **Coaches list:** Status tabs work (filter by pending/approved/etc). Approve/Reject/Suspend/Remove buttons fire AJAX. Status badge updates in the row after action.

3. **Players list:** Table loads. Search filters results.

4. **Exams list:** Filter bar works. View link goes to exam detail. Exam detail shows all scores.

5. **Certificates list:** Revoke button fires AJAX modal (check console for `[WFEB] Revoking certificate:`). Status badge updates.

6. **Analytics:** Charts render (4 charts visible). Date filter works.

7. **Settings:**
   - Switching tabs works (URL changes to `?tab=general` etc.)
   - General tab: change `coach_approval_mode` to 'auto', click Save → check console for WFEB notice "Settings saved." → verify `wp_options` row `wfeb_coach_approval_mode` = 'auto'
   - Email tab: click Send Test Email → admin email receives test
   - Certificate tab: upload background image → save → verify option saved

8. **Auto coach approval end-to-end:** With mode set to 'auto', register a new coach → should be auto-approved (status='approved' in DB) and can log in immediately.

---

## Execution Notes

- PHP files: preserve all existing PHP data-fetching logic, only replace HTML output
- Do NOT change controller classes (`class-wfeb-admin-*.php`) except Task 3 (AJAX handler) and Task 9 (Analytics controller for extra stats)
- Keep all existing `esc_html()`, `esc_url()`, `esc_attr()` escaping
- The `wfeb_format_date()` and `wfeb_get_level_color()` helper functions are in `includes/wfeb-functions.php` — keep using them
- No external CSS frameworks or icon libraries — use Unicode characters for icons where needed (e.g., `←` for back, `+` for add)
