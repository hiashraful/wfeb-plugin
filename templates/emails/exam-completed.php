<?php
/**
 * Email Template: Exam Completed
 *
 * Outputs the inner HTML content for the exam completion notification to a coach.
 * Wrapped by WFEB_Email::get_wrapper() before sending.
 *
 * Available variables (via extract):
 *   $coach       - (object) Coach record with full_name, email.
 *   $player      - (object) Player record with full_name.
 *   $exam        - (object) Exam record with total_score, achievement_level, dashboard_url.
 *   $certificate - (object) Certificate record with certificate_number.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$player_name  = isset( $player->full_name ) ? $player->full_name : 'Unknown Player';
$score        = isset( $exam->total_score ) ? $exam->total_score : '0';
$level        = isset( $exam->achievement_level ) ? $exam->achievement_level : 'N/A';
$cert_number  = isset( $certificate->certificate_number ) ? $certificate->certificate_number : 'N/A';
$dashboard_url = isset( $exam->dashboard_url ) ? $exam->dashboard_url : '';
?>

<h2 style="color: #1a2a4a; font-size: 22px; margin: 0 0 20px 0;">Exam Completed</h2>

<p>Dear <?php echo esc_html( $coach->full_name ); ?>,</p>

<p>Exam completed for <?php echo esc_html( $player_name ); ?>. A certificate has been issued. Here is the summary:</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Player</td>
		<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $player_name ); ?></td>
	</tr>
	<tr>
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Score</td>
		<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $score ); ?> / 80</td>
	</tr>
	<tr style="background-color: #f8f9fb;">
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Achievement Level</td>
		<td style="padding: 12px 20px; color: #333333; font-weight: 700; border-bottom: 1px solid #e0e4e8;"><?php echo esc_html( $level ); ?></td>
	</tr>
	<tr>
		<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">Certificate Number</td>
		<td style="padding: 12px 20px; color: #333333; font-family: monospace;"><?php echo esc_html( $cert_number ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $dashboard_url ) ) : ?>
<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url( $dashboard_url ); ?>" style="display: inline-block; background-color: #10B981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">View Details</a>
</p>
<?php endif; ?>
