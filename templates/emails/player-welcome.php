<?php
/**
 * Email Template: Player Welcome
 *
 * Outputs the inner HTML content for the new player welcome email.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $player    - (object) Player record with full_name, email.
 *   $password  - (string) Temporary plain-text password.
 *   $login_url - (string) URL to the player login page.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Welcome to WFEB</h2>

<p>Dear <?php echo esc_html( $player->full_name ); ?>,</p>

<p>Welcome to WFEB! Your coach has created an account for you. You can now view your certificates and scores online.</p>

<div style="background-color: #f0f4fa; border: 1px solid #d0d8e8; padding: 20px; margin: 20px 0; border-radius: 6px;">
	<p style="margin: 0 0 12px 0; font-weight: 600; color: #1a2a4a; font-size: 15px;">Your Login Credentials</p>
	<table role="presentation" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td style="padding: 6px 0; font-weight: 600; color: #555555; width: 100px;">Email:</td>
			<td style="padding: 6px 0; color: #333333;"><?php echo esc_html( $player->email ); ?></td>
		</tr>
		<tr>
			<td style="padding: 6px 0; font-weight: 600; color: #555555;">Password:</td>
			<td style="padding: 6px 0; color: #333333; font-family: monospace; font-size: 16px; letter-spacing: 1px;"><?php echo esc_html( $password ); ?></td>
		</tr>
	</table>
</div>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $login_url ); ?>" style="display: inline-block; background-color: #10B981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Login to Dashboard</a>
</p>

<p style="color: #e74c3c; font-weight: 600;">Please change your password after first login for security.</p>
