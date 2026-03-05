<?php
/**
 * Email Template: Player Certificate
 *
 * Outputs the inner HTML content for the certificate issued email to a player.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $player      - (object) Player record with full_name, email.
 *   $certificate - (object) Certificate record with certificate_number, total_score,
 *                           achievement_level, playing_level, pdf_url.
 *   $exam_date   - (string) Date the exam was conducted.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cert_number      = isset( $certificate->certificate_number ) ? $certificate->certificate_number : 'N/A';
$total_score      = isset( $certificate->total_score ) ? $certificate->total_score : '0';
$achievement_level = isset( $certificate->achievement_level ) ? $certificate->achievement_level : 'N/A';
$playing_level    = isset( $certificate->playing_level ) ? $certificate->playing_level : 'N/A';
$pdf_url          = isset( $certificate->pdf_url ) ? $certificate->pdf_url : '';
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Certificate of Football Ability</h2>

<p>Dear <?php echo esc_html( $player->full_name ); ?>,</p>

<p>Congratulations <?php echo esc_html( $player->full_name ); ?>! You have been awarded a WFEB Certificate of Football Ability.</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Certificate Number</td>
		<td style="padding: 12px 20px; color: #333333; font-family: monospace; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $cert_number ); ?></td>
	</tr>
	<tr>
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Score</td>
		<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $total_score ); ?> / 80</td>
	</tr>
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Achievement Level</td>
		<td style="padding: 12px 20px; color: #333333; font-weight: 700; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $achievement_level ); ?></td>
	</tr>
	<tr>
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Playing Level</td>
		<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $playing_level ); ?></td>
	</tr>
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">Date</td>
		<td style="padding: 12px 20px; color: #333333;"><?php echo esc_html( $exam_date ); ?></td>
	</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $pdf_url ); ?>" style="display: inline-block; background-color: #10B981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">View Certificate</a>
<?php if ( ! empty( $pdf_url ) ) : ?>
	<br><br>
	<a href="<?php echo esc_url( $pdf_url ); ?>" style="display: inline-block; background-color: #243b6a; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 13px;">Download PDF</a>
<?php endif; ?>
</p>

<p>Well done on your achievement. Keep up the excellent work!</p>
