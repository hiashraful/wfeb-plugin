<?php
/**
 * Email Template: Coach Rejected
 *
 * Outputs the inner HTML content for the coach rejection email.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $coach  - (object) Coach record with full_name, email.
 *   $reason - (string) Rejection reason (may be empty).
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$admin_email = get_option( 'admin_email' );
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Registration Update</h2>

<p>Dear <?php echo esc_html( $coach->full_name ); ?>,</p>

<p>We regret to inform you that your WFEB coach registration has not been approved.</p>

<?php if ( ! empty( $reason ) ) : ?>
<div style="background-color: #fff3f3; border-left: 4px solid #e74c3c; padding: 15px 20px; margin: 20px 0; border-radius: 0 4px 4px 0;">
	<p style="margin: 0; font-weight: 600; color: #c0392b;">Reason:</p>
	<p style="margin: 8px 0 0 0; color: #333333;"><?php echo esc_html( $reason ); ?></p>
</div>
<?php endif; ?>

<p>If you believe this is an error, please contact us at <a href="mailto:<?php echo esc_attr( $admin_email ); ?>" style="color: #243b6a;"><?php echo esc_html( $admin_email ); ?></a>.</p>

<p>We appreciate your interest in the World Football Examination Board and thank you for your understanding.</p>
