<?php
/**
 * Email Template: Coach Approved
 *
 * Outputs the inner HTML content for the coach approval email.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $coach         - (object) Coach record with full_name, email.
 *   $dashboard_url - (string) URL to the coach dashboard.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Account Approved</h2>

<p>Dear <?php echo esc_html( $coach->full_name ); ?>,</p>

<p>Congratulations <?php echo esc_html( $coach->full_name ); ?>! Your WFEB coach account has been approved. You can now log in and start conducting examinations.</p>

<p>As an approved WFEB coach, you have access to:</p>
<ul style="color: #333333; line-height: 1.8; padding-left: 20px;">
	<li>Register and manage players</li>
	<li>Conduct football ability examinations</li>
	<li>Issue official WFEB certificates</li>
	<li>Track player progress and scores</li>
</ul>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $dashboard_url ); ?>" style="display: inline-block; background-color: #10B981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Go to Dashboard</a>
</p>

<p>Welcome to the World Football Examination Board. We look forward to supporting your coaching journey.</p>
