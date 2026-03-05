<?php
/**
 * Email Template: Credit Purchase
 *
 * Outputs the inner HTML content for the credit purchase confirmation email.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $coach       - (object) Coach record with full_name, email.
 *   $quantity    - (int)    Number of credits purchased.
 *   $new_balance - (int)    Updated credit balance.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/coach-dashboard/' );
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Credit Purchase Confirmed</h2>

<p>Dear <?php echo esc_html( $coach->full_name ); ?>,</p>

<p>Your credit purchase is confirmed. Here are the details:</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Credits Purchased</td>
		<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $quantity ); ?></td>
	</tr>
	<tr>
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">New Balance</td>
		<td style="padding: 12px 20px; color: #1a2a4a; font-weight: 700; font-size: 18px;"><?php echo esc_html( $new_balance ); ?> credits</td>
	</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $dashboard_url ); ?>" style="display: inline-block; background-color: #10B981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Go to Dashboard</a>
</p>

<p>Thank you for your purchase.</p>
