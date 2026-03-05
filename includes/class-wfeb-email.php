<?php
/**
 * WFEB Email Notifications
 *
 * Handles all email notifications for the WFEB plugin using wp_mail()
 * with HTML content type and branded email templates.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Email
 *
 * Sends branded HTML email notifications for coach registration,
 * approval, player accounts, certificates, credit purchases, and more.
 */
class WFEB_Email {

	/**
	 * Constructor.
	 *
	 * Sets the wp_mail content type to HTML.
	 */
	public function __construct() {
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * Set the email content type to HTML.
	 *
	 * @return string HTML content type.
	 */
	public function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * Load an email template from the templates/emails/ directory.
	 *
	 * Extracts the $data array as variables available to the template file.
	 * Returns the rendered HTML string.
	 *
	 * @param string $template_name Template filename (without path).
	 * @param array  $data          Associative array of data to pass to the template.
	 * @return string Rendered HTML string, or empty string if template not found.
	 */
	public function get_template( $template_name, $data = array() ) {
		$template_path = WFEB_PLUGIN_DIR . 'templates/emails/' . $template_name;

		if ( ! file_exists( $template_path ) ) {
			wfeb_log( 'WFEB_Email::get_template() - Template not found: ' . $template_path );
			return '';
		}

		if ( ! empty( $data ) ) {
			extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Wrap email content in a branded HTML email template.
	 *
	 * Uses inline CSS for email-safe rendering with a professional
	 * navy/white color scheme, WFEB logo header, content area, and footer.
	 *
	 * @param string $content The inner HTML content of the email.
	 * @param string $subject The email subject line (used in the header area).
	 * @return string Full HTML email document.
	 */
	public function get_wrapper( $content, $subject ) {
		$logo_url = WFEB_PLUGIN_URL . 'assets/images/wfeb-logo.png';
		$year     = date( 'Y' );

		$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . esc_html( $subject ) . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f8;">
<tr>
<td align="center" style="padding: 30px 15px;">
<table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

<!-- Header -->
<tr>
<td align="center" style="background-color: #1a2a4a; padding: 30px 40px;">
<img src="' . esc_url( $logo_url ) . '" alt="WFEB" width="180" style="display: block; max-width: 180px; height: auto;" />
<p style="color: #ffffff; font-size: 14px; margin: 10px 0 0 0; letter-spacing: 1px; text-transform: uppercase;">World Football Examination Board</p>
</td>
</tr>

<!-- Subject Bar -->
<tr>
<td style="background-color: #243b6a; padding: 15px 40px;">
<h1 style="color: #ffffff; font-size: 18px; margin: 0; font-weight: 600;">' . esc_html( $subject ) . '</h1>
</td>
</tr>

<!-- Content -->
<tr>
<td style="padding: 35px 40px; color: #333333; font-size: 15px; line-height: 1.6;">
' . $content . '
</td>
</tr>

<!-- Footer -->
<tr>
<td style="background-color: #1a2a4a; padding: 25px 40px; text-align: center;">
<p style="color: #8a9bc0; font-size: 13px; margin: 0 0 5px 0;">World Football Examination Board</p>
<p style="color: #5a6b8a; font-size: 12px; margin: 0;">&copy; ' . esc_html( $year ) . ' WFEB. All rights reserved.</p>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>';

		return $html;
	}

	/**
	 * Send new coach registration notification to the site admin.
	 *
	 * @param object $coach The coach record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_coach_registration_to_admin( $coach ) {
		$admin_email = get_option( 'admin_email' );
		$subject     = 'New Coach Registration - ' . $coach->full_name;

		$admin_url = admin_url( 'admin.php?page=wfeb-coaches&action=view&id=' . $coach->id );

		$content = '<p>A new coach has registered and is awaiting approval.</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 140px; border-bottom: 1px solid #e0e4e8;">Name</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $coach->full_name ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Email</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $coach->email ) . '</td>
</tr>
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">NGB Number</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $coach->ngb_number ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">Phone</td>
<td style="padding: 12px 20px; color: #333333;">' . esc_html( $coach->phone ) . '</td>
</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $admin_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Review &amp; Approve</a>
</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $admin_email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_coach_registration_to_admin() - Failed to send email for coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send approval notification to a coach.
	 *
	 * @param object $coach The coach record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_coach_approved( $coach ) {
		$subject = 'Your WFEB Coach Account is Approved';

		$login_page_id = get_option( 'wfeb_coach_login_page_id' );
		$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url( '/coach-login/' );

		$content = '<p>Dear ' . esc_html( $coach->full_name ) . ',</p>

<p>Congratulations! Your WFEB Coach account has been reviewed and approved. You now have full access to the Coach Dashboard where you can manage players, conduct exams, and issue certificates.</p>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $login_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Login to Coach Dashboard</a>
</p>

<p>Welcome to the World Football Examination Board. We look forward to supporting your coaching journey.</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $coach->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_coach_approved() - Failed to send email to coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send rejection notification to a coach.
	 *
	 * @param object $coach The coach record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_coach_rejected( $coach ) {
		$subject     = 'WFEB Coach Registration Update';
		$admin_email = get_option( 'admin_email' );

		$reason = ! empty( $coach->rejection_reason )
			? esc_html( $coach->rejection_reason )
			: 'No specific reason was provided.';

		$content = '<p>Dear ' . esc_html( $coach->full_name ) . ',</p>

<p>Thank you for your interest in becoming a WFEB Coach. After reviewing your registration, we are unable to approve your account at this time.</p>

<div style="background-color: #fff3f3; border-left: 4px solid #e74c3c; padding: 15px 20px; margin: 20px 0; border-radius: 0 4px 4px 0;">
<p style="margin: 0; font-weight: 600; color: #c0392b;">Reason:</p>
<p style="margin: 8px 0 0 0; color: #333333;">' . $reason . '</p>
</div>

<p>If you believe this decision was made in error or would like to provide additional information, please contact us at <a href="mailto:' . esc_attr( $admin_email ) . '" style="color: #243b6a;">' . esc_html( $admin_email ) . '</a>.</p>

<p>We appreciate your understanding.</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $coach->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_coach_rejected() - Failed to send email to coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send welcome email to a new player with login credentials.
	 *
	 * @param object $player   The player record object.
	 * @param string $password The temporary plain-text password.
	 * @return bool True on success, false on failure.
	 */
	public function send_player_welcome( $player, $password ) {
		$subject = 'Welcome to WFEB - Your Player Account';

		$login_page_id = get_option( 'wfeb_player_login_page_id' );
		$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url( '/player-login/' );

		$content = '<p>Dear ' . esc_html( $player->full_name ) . ',</p>

<p>A player account has been created for you on the World Football Examination Board platform. You can use this account to view your exam results, certificates, and progress.</p>

<div style="background-color: #f0f4fa; border: 1px solid #d0d8e8; padding: 20px; margin: 20px 0; border-radius: 6px;">
<p style="margin: 0 0 10px 0; font-weight: 600; color: #1a2a4a;">Your Login Credentials</p>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td style="padding: 5px 0; font-weight: 600; color: #555555; width: 100px;">Email:</td>
<td style="padding: 5px 0; color: #333333;">' . esc_html( $player->email ) . '</td>
</tr>
<tr>
<td style="padding: 5px 0; font-weight: 600; color: #555555;">Password:</td>
<td style="padding: 5px 0; color: #333333; font-family: monospace; font-size: 16px; letter-spacing: 1px;">' . esc_html( $password ) . '</td>
</tr>
</table>
</div>

<p style="color: #e74c3c; font-weight: 600;">Please change your password after your first login for security.</p>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $login_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Login to Player Dashboard</a>
</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $player->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_player_welcome() - Failed to send email to player_id: ' . $player->id );
		}

		return $result;
	}

	/**
	 * Send certificate issued notification to a player.
	 *
	 * @param object $certificate The certificate record object.
	 * @param object $player      The player record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_certificate_issued( $certificate, $player ) {
		$level   = isset( $certificate->achievement_level ) ? $certificate->achievement_level : 'N/A';
		$subject = 'Your WFEB Certificate - ' . $level;

		$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
		$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/player-dashboard/' );

		$level_color = function_exists( 'wfeb_get_level_color' ) ? wfeb_get_level_color( $level ) : '#1a2a4a';

		$score     = isset( $certificate->total_score ) ? $certificate->total_score : '0';
		$cert_no   = isset( $certificate->certificate_number ) ? $certificate->certificate_number : 'N/A';
		$pdf_url   = isset( $certificate->pdf_url ) ? $certificate->pdf_url : '';

		$content = '<p>Dear ' . esc_html( $player->full_name ) . ',</p>

<p>Congratulations! You have been awarded a WFEB Skills Certificate. Here are the details:</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Achievement Level</td>
<td style="padding: 12px 20px; color: ' . esc_attr( $level_color ) . '; font-weight: 700; border-bottom: 1px solid #e0e4e8;">' . esc_html( $level ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Total Score</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $score ) . ' / 80</td>
</tr>
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">Certificate Number</td>
<td style="padding: 12px 20px; color: #333333; font-family: monospace;">' . esc_html( $cert_no ) . '</td>
</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $dashboard_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">View Certificate</a>';

		if ( ! empty( $pdf_url ) ) {
			$content .= '
<br><br>
<a href="' . esc_url( $pdf_url ) . '" style="display: inline-block; background-color: #243b6a; color: #ffffff; text-decoration: none; padding: 10px 25px; border-radius: 5px; font-size: 13px;">Download PDF</a>';
		}

		$content .= '
</p>

<p>Well done on your achievement. Keep up the excellent work!</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $player->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_certificate_issued() - Failed to send email to player_id: ' . $player->id );
		}

		return $result;
	}

	/**
	 * Send exam completed notification to the coach.
	 *
	 * @param object $exam        The exam record object.
	 * @param object $coach       The coach record object.
	 * @param object $certificate The certificate record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_exam_completed( $exam, $coach, $certificate ) {
		$player_name = isset( $exam->player_name ) ? $exam->player_name : 'Unknown Player';
		$subject     = 'Exam Completed - ' . $player_name;

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/coach-dashboard/' );

		$cert_number = isset( $certificate->certificate_number ) ? $certificate->certificate_number : 'N/A';
		$level       = isset( $exam->achievement_level ) ? $exam->achievement_level : 'N/A';
		$score       = isset( $exam->total_score ) ? $exam->total_score : '0';

		$content = '<p>Dear ' . esc_html( $coach->full_name ) . ',</p>

<p>An exam has been completed and a certificate has been issued. Here is a summary:</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Player</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $player_name ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Score</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $score ) . ' / 80</td>
</tr>
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; border-bottom: 1px solid #e0e4e8;">Achievement Level</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $level ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">Certificate #</td>
<td style="padding: 12px 20px; color: #333333; font-family: monospace;">' . esc_html( $cert_number ) . '</td>
</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $dashboard_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">View in Dashboard</a>
</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $coach->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_exam_completed() - Failed to send email to coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send credit purchase confirmation to a coach.
	 *
	 * @param object $coach       The coach record object.
	 * @param int    $quantity    Number of credits purchased.
	 * @param int    $new_balance The coach's updated credit balance.
	 * @return bool True on success, false on failure.
	 */
	public function send_credit_purchase( $coach, $quantity, $new_balance ) {
		$subject = 'WFEB Credits Purchased';

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/coach-dashboard/' );

		$content = '<p>Dear ' . esc_html( $coach->full_name ) . ',</p>

<p>Your certificate credit purchase has been confirmed. Here are the details:</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0; border: 1px solid #e0e4e8; border-radius: 6px; overflow: hidden;">
<tr style="background-color: #f8f9fb;">
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a; width: 160px; border-bottom: 1px solid #e0e4e8;">Credits Purchased</td>
<td style="padding: 12px 20px; color: #333333; border-bottom: 1px solid #e0e4e8;">' . esc_html( $quantity ) . '</td>
</tr>
<tr>
<td style="padding: 12px 20px; font-weight: 600; color: #1a2a4a;">New Balance</td>
<td style="padding: 12px 20px; color: #1a2a4a; font-weight: 700; font-size: 18px;">' . esc_html( $new_balance ) . ' credits</td>
</tr>
</table>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $dashboard_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Go to Dashboard</a>
</p>

<p>Thank you for your purchase.</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $coach->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_credit_purchase() - Failed to send email to coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send low credits warning to a coach.
	 *
	 * @param object $coach The coach record object.
	 * @return bool True on success, false on failure.
	 */
	public function send_low_credits_warning( $coach ) {
		$subject = 'Low Certificate Credits Warning';

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/coach-dashboard/' );

		$current_balance = isset( $coach->credits_balance ) ? absint( $coach->credits_balance ) : 0;

		$content = '<p>Dear ' . esc_html( $coach->full_name ) . ',</p>

<div style="background-color: #fff8e1; border-left: 4px solid #f39c12; padding: 15px 20px; margin: 20px 0; border-radius: 0 4px 4px 0;">
<p style="margin: 0; font-weight: 600; color: #e67e22;">Low Credits Alert</p>
<p style="margin: 8px 0 0 0; color: #333333;">You currently have <strong>' . esc_html( $current_balance ) . '</strong> certificate credit(s) remaining. You will need credits to issue certificates for completed exams.</p>
</div>

<p>To avoid interruptions when issuing certificates, please purchase additional credits.</p>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $dashboard_url ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Buy More Credits</a>
</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $coach->email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_low_credits_warning() - Failed to send email to coach_id: ' . $coach->id );
		}

		return $result;
	}

	/**
	 * Send password reset email to a user.
	 *
	 * @param object $user       The WordPress user object or user data object with email and display_name.
	 * @param string $reset_link The password reset URL.
	 * @return bool True on success, false on failure.
	 */
	public function send_password_reset( $user, $reset_link ) {
		$subject = 'WFEB Password Reset';

		$display_name = '';
		$email        = '';

		if ( $user instanceof WP_User ) {
			$display_name = $user->display_name;
			$email        = $user->user_email;
		} elseif ( is_object( $user ) ) {
			$display_name = isset( $user->display_name ) ? $user->display_name : ( isset( $user->full_name ) ? $user->full_name : '' );
			$email        = isset( $user->user_email ) ? $user->user_email : ( isset( $user->email ) ? $user->email : '' );
		}

		if ( empty( $email ) ) {
			wfeb_log( 'WFEB_Email::send_password_reset() - No email address provided.' );
			return false;
		}

		$content = '<p>Dear ' . esc_html( $display_name ) . ',</p>

<p>We received a request to reset the password for your WFEB account. Click the button below to set a new password:</p>

<p style="text-align: center; margin: 30px 0;">
<a href="' . esc_url( $reset_link ) . '" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; font-size: 14px;">Reset Password</a>
</p>

<p style="color: #888888; font-size: 13px;">This link will expire in 24 hours. If you did not request a password reset, you can safely ignore this email.</p>

<p style="color: #888888; font-size: 13px;">If the button above does not work, copy and paste the following URL into your browser:</p>
<p style="word-break: break-all; color: #243b6a; font-size: 13px;">' . esc_url( $reset_link ) . '</p>';

		$html   = $this->get_wrapper( $content, $subject );
		$result = wp_mail( $email, $subject, $html );

		if ( ! $result ) {
			wfeb_log( 'WFEB_Email::send_password_reset() - Failed to send email to: ' . $email );
		}

		return $result;
	}
}
