<?php
/**
 * WFEB PDF / Certificate HTML Generator
 *
 * Generates certificate HTML files with print-ready CSS that produce
 * A4 landscape output. Uses pure HTML/CSS approach with @media print
 * and @page rules -- no external PDF libraries required.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_PDF
 *
 * Generates and manages certificate HTML files stored as WordPress attachments.
 */
class WFEB_PDF {

	/**
	 * The upload subdirectory for certificate files.
	 *
	 * @var string
	 */
	private $upload_dir = 'wfeb-certificates';

	/**
	 * Generate a certificate HTML file for a given certificate object.
	 *
	 * Creates a self-contained HTML page with A4 landscape layout and
	 * print-ready CSS. Stores the file as a WordPress attachment in
	 * uploads/wfeb-certificates/.
	 *
	 * @since 1.0.0
	 *
	 * @param object $certificate The certificate object with joined player, coach, and exam data.
	 * @return array|WP_Error Array with 'url' and 'attachment_id' on success, WP_Error on failure.
	 */
	public function generate_certificate( $certificate ) {
		if ( empty( $certificate ) || empty( $certificate->certificate_number ) ) {
			wfeb_log( 'PDF generation failed - invalid certificate object.' );
			return new WP_Error( 'invalid_certificate', __( 'Invalid certificate data.', 'wfeb' ) );
		}

		// Generate the HTML content.
		$html = $this->get_certificate_html( $certificate );

		if ( empty( $html ) ) {
			wfeb_log( 'PDF generation failed - HTML generation returned empty for cert: ' . $certificate->certificate_number );
			return new WP_Error( 'html_generation_failed', __( 'Failed to generate certificate HTML.', 'wfeb' ) );
		}

		// Ensure the upload directory exists.
		$upload_path = $this->get_upload_path();

		if ( is_wp_error( $upload_path ) ) {
			return $upload_path;
		}

		// Build the filename.
		$filename = sanitize_file_name( 'certificate-' . $certificate->certificate_number . '.html' );
		$filepath = trailingslashit( $upload_path ) . $filename;

		// Write the HTML file.
		$written = file_put_contents( $filepath, $html ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $written ) {
			wfeb_log( 'PDF generation failed - could not write file: ' . $filepath );
			return new WP_Error( 'file_write_failed', __( 'Failed to write certificate file.', 'wfeb' ) );
		}

		// Get the file URL.
		$upload_dir = wp_upload_dir();
		$file_url   = trailingslashit( $upload_dir['baseurl'] ) . $this->upload_dir . '/' . $filename;

		// Create a WordPress attachment for the file.
		$attachment_id = $this->create_attachment( $filepath, $file_url, $certificate );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wfeb_log( 'Certificate file generated - cert: ' . $certificate->certificate_number . ', attachment_id: ' . $attachment_id );

		return array(
			'url'           => $file_url,
			'attachment_id' => $attachment_id,
		);
	}

	/**
	 * Generate the full HTML content for a certificate.
	 *
	 * Creates a self-contained A4 landscape HTML page with embedded CSS
	 * and all certificate details.
	 *
	 * @since 1.0.0
	 *
	 * @param object $certificate The certificate object with related data.
	 * @return string The complete HTML document.
	 */
	public function get_certificate_html( $certificate ) {
		$player_name     = isset( $certificate->player_name ) ? esc_html( $certificate->player_name ) : '';
		$total_score     = isset( $certificate->total_score ) ? absint( $certificate->total_score ) : 0;
		$achievement     = isset( $certificate->achievement_level ) ? esc_html( $certificate->achievement_level ) : '';
		$playing_level   = isset( $certificate->playing_level ) ? esc_html( $certificate->playing_level ) : '';
		$cert_number     = isset( $certificate->certificate_number ) ? esc_html( $certificate->certificate_number ) : '';
		$exam_date       = isset( $certificate->exam_date ) ? esc_html( wfeb_format_date( $certificate->exam_date, 'j F Y' ) ) : '';
		$coach_name      = isset( $certificate->coach_name ) ? esc_html( $certificate->coach_name ) : '';
		$issued_at       = isset( $certificate->issued_at ) ? esc_html( wfeb_format_date( $certificate->issued_at, 'j F Y' ) ) : '';
		$authoriser_name = esc_html( get_option( 'wfeb_cert_authoriser_name', get_option( 'wfeb_authoriser_name', 'WFEB Board' ) ) );

		// Get level-specific styles.
		$level_styles = $this->get_level_styles( $achievement );

		// Asset URLs.
		$logo_url       = WFEB_PLUGIN_URL . 'assets/images/LOGO TRANSPARENT.png';
		$custom_bg      = get_option( 'wfeb_cert_background', '' );
		$background_url = ! empty( $custom_bg ) ? $custom_bg : WFEB_PLUGIN_URL . 'assets/images/certificate of ability.png';

		// Generate QR code for verification.
		$qr_svg = '';
		if ( isset( $certificate->player_name, $certificate->player_dob ) && class_exists( 'WFEB_QR' ) ) {
			$verify_url = WFEB()->certificate->get_verification_url( $certificate );
			$qr_svg     = WFEB_QR::svg( $verify_url, 80 );
		}

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Certificate - <?php echo $cert_number; ?></title>
	<style>
		/* Reset */
		*, *::before, *::after {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		/* Print-ready page settings */
		@page {
			size: A4 landscape;
			margin: 0;
		}

		@media print {
			html, body {
				width: 297mm;
				height: 210mm;
				margin: 0;
				padding: 0;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}

			.certificate-page {
				page-break-after: avoid;
				page-break-inside: avoid;
			}

			.no-print {
				display: none !important;
			}
		}

		body {
			font-family: 'Georgia', 'Times New Roman', serif;
			background: #f5f5f5;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			padding: 20px;
		}

		@media print {
			body {
				background: #ffffff;
				padding: 0;
			}
		}

		.certificate-page {
			width: 297mm;
			height: 210mm;
			position: relative;
			background: #ffffff;
			overflow: hidden;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		}

		@media print {
			.certificate-page {
				box-shadow: none;
			}
		}

		/* Background image */
		.certificate-bg {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			object-fit: cover;
			z-index: 0;
			opacity: 0.12;
		}

		/* Content overlay */
		.certificate-content {
			position: relative;
			z-index: 1;
			width: 100%;
			height: 100%;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: space-between;
			padding: 18mm 22mm;
		}

		/* Decorative border */
		.certificate-border {
			position: absolute;
			top: 6mm;
			left: 6mm;
			right: 6mm;
			bottom: 6mm;
			border: 2px solid <?php echo $level_styles['border_color']; ?>;
			z-index: 1;
			pointer-events: none;
		}

		.certificate-border::before {
			content: '';
			position: absolute;
			top: 2mm;
			left: 2mm;
			right: 2mm;
			bottom: 2mm;
			border: 1px solid <?php echo $level_styles['border_color']; ?>;
			opacity: 0.5;
		}

		/* Header section */
		.cert-header {
			text-align: center;
			width: 100%;
		}

		.cert-logo {
			height: 50px;
			width: auto;
			margin-bottom: 6px;
		}

		.cert-title {
			font-size: 26px;
			font-weight: 700;
			letter-spacing: 6px;
			text-transform: uppercase;
			color: <?php echo $level_styles['primary_color']; ?>;
			margin-bottom: 2px;
			font-family: 'Georgia', serif;
		}

		.cert-subtitle {
			font-size: 11px;
			letter-spacing: 4px;
			text-transform: uppercase;
			color: #666666;
			font-weight: 400;
		}

		/* Player section */
		.cert-player-section {
			text-align: center;
			width: 100%;
		}

		.cert-awarded-to {
			font-size: 11px;
			letter-spacing: 3px;
			text-transform: uppercase;
			color: #888888;
			margin-bottom: 6px;
		}

		.cert-player-name {
			font-size: 36px;
			font-weight: 700;
			color: #1a1a1a;
			font-family: 'Georgia', serif;
			letter-spacing: 2px;
			padding-bottom: 4px;
			border-bottom: 2px solid <?php echo $level_styles['primary_color']; ?>;
			display: inline-block;
		}

		/* Achievement section */
		.cert-achievement-section {
			text-align: center;
			width: 100%;
		}

		.cert-level-badge {
			display: inline-block;
			padding: 6px 28px;
			font-size: 18px;
			font-weight: 700;
			letter-spacing: 4px;
			text-transform: uppercase;
			color: <?php echo $level_styles['badge_text']; ?>;
			background: <?php echo $level_styles['badge_bg']; ?>;
			border-radius: 4px;
			border: 1px solid <?php echo $level_styles['badge_border']; ?>;
			margin-bottom: 6px;
		}

		.cert-playing-level {
			font-size: 14px;
			color: #555555;
			font-style: italic;
			margin-bottom: 4px;
		}

		.cert-score {
			font-size: 16px;
			font-weight: 600;
			color: <?php echo $level_styles['primary_color']; ?>;
		}

		.cert-score span {
			font-size: 22px;
		}

		/* Details grid */
		.cert-details {
			display: flex;
			justify-content: space-between;
			width: 100%;
			max-width: 680px;
			gap: 20px;
		}

		.cert-detail-item {
			text-align: center;
			flex: 1;
		}

		.cert-detail-label {
			font-size: 9px;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: #999999;
			margin-bottom: 3px;
		}

		.cert-detail-value {
			font-size: 12px;
			font-weight: 600;
			color: #333333;
		}

		/* Footer section */
		.cert-footer {
			display: flex;
			justify-content: space-between;
			width: 100%;
			align-items: flex-end;
		}

		.cert-footer-item {
			text-align: center;
			min-width: 140px;
		}

		.cert-signature-line {
			width: 140px;
			height: 1px;
			background: #999999;
			margin: 0 auto 4px;
		}

		.cert-footer-label {
			font-size: 9px;
			letter-spacing: 1px;
			text-transform: uppercase;
			color: #999999;
		}

		.cert-footer-value {
			font-size: 11px;
			font-weight: 600;
			color: #333333;
			margin-bottom: 2px;
		}

		.cert-footer-qr {
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		.cert-footer-qr svg {
			width: 22mm;
			height: 22mm;
		}

		/* Print button (hidden in print) */
		.print-controls {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 1000;
		}

		.btn-print {
			display: inline-block;
			padding: 10px 24px;
			background: <?php echo $level_styles['primary_color']; ?>;
			color: #ffffff;
			border: none;
			border-radius: 4px;
			font-size: 14px;
			cursor: pointer;
			font-family: Arial, sans-serif;
			text-decoration: none;
		}

		.btn-print:hover {
			opacity: 0.9;
		}
	</style>
</head>
<body>
	<!-- Print button -->
	<div class="print-controls no-print">
		<button class="btn-print" onclick="window.print();">Print / Save as PDF</button>
	</div>

	<div class="certificate-page">
		<!-- Background image -->
		<img class="certificate-bg" src="<?php echo esc_url( $background_url ); ?>" alt="">

		<!-- Decorative border -->
		<div class="certificate-border"></div>

		<!-- Certificate content -->
		<div class="certificate-content">
			<!-- Header -->
			<div class="cert-header">
				<img class="cert-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="WFEB">
				<h1 class="cert-title">Certificate of Football Ability</h1>
				<p class="cert-subtitle">World Football Examination Board</p>
			</div>

			<!-- Player name -->
			<div class="cert-player-section">
				<p class="cert-awarded-to">This is to certify that</p>
				<h2 class="cert-player-name"><?php echo $player_name; ?></h2>
			</div>

			<!-- Achievement -->
			<div class="cert-achievement-section">
				<div class="cert-level-badge"><?php echo $achievement; ?></div>
				<p class="cert-playing-level">Playing Level: <?php echo $playing_level; ?></p>
				<p class="cert-score">Score: <span><?php echo $total_score; ?></span>/80</p>
			</div>

			<!-- Details -->
			<div class="cert-details">
				<div class="cert-detail-item">
					<p class="cert-detail-label">Exam Date</p>
					<p class="cert-detail-value"><?php echo $exam_date; ?></p>
				</div>
				<div class="cert-detail-item">
					<p class="cert-detail-label">Certificate No.</p>
					<p class="cert-detail-value"><?php echo $cert_number; ?></p>
				</div>
				<div class="cert-detail-item">
					<p class="cert-detail-label">Date of Issue</p>
					<p class="cert-detail-value"><?php echo $issued_at; ?></p>
				</div>
			</div>

			<!-- Footer -->
			<div class="cert-footer">
				<div class="cert-footer-item">
					<p class="cert-footer-value"><?php echo $coach_name; ?></p>
					<div class="cert-signature-line"></div>
					<p class="cert-footer-label">Examiner</p>
				</div>
				<?php if ( ! empty( $qr_svg ) ) : ?>
				<div class="cert-footer-item cert-footer-qr">
					<?php echo $qr_svg; ?>
					<p class="cert-footer-label">Scan to Verify</p>
				</div>
				<?php endif; ?>
				<div class="cert-footer-item">
					<p class="cert-footer-value"><?php echo $authoriser_name; ?></p>
					<div class="cert-signature-line"></div>
					<p class="cert-footer-label">Authorized By</p>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get CSS styles based on the achievement level.
	 *
	 * Returns an array of color values for the given level tier.
	 * Uses gold tones for mastery/diamond/gold, silver for silver/bronze,
	 * green for merit levels, and blue for pass levels.
	 *
	 * @since 1.0.0
	 *
	 * @param string $level The achievement level string.
	 * @return array Associative array of CSS color values.
	 */
	public function get_level_styles( $level ) {
		$level = strtoupper( trim( $level ) );

		$styles = array(
			// Gold tier: Mastery, Diamond, Gold.
			'MASTERY' => array(
				'primary_color' => '#B8860B',
				'border_color'  => '#DAA520',
				'badge_bg'      => 'linear-gradient(135deg, #FFD700, #B8860B)',
				'badge_text'    => '#ffffff',
				'badge_border'  => '#B8860B',
			),
			'DIAMOND' => array(
				'primary_color' => '#8B7500',
				'border_color'  => '#DAA520',
				'badge_bg'      => 'linear-gradient(135deg, #B9F2FF, #87CEEB)',
				'badge_text'    => '#1a3a4a',
				'badge_border'  => '#87CEEB',
			),
			'GOLD' => array(
				'primary_color' => '#B8860B',
				'border_color'  => '#DAA520',
				'badge_bg'      => 'linear-gradient(135deg, #FFD700, #FFA500)',
				'badge_text'    => '#5a3e00',
				'badge_border'  => '#DAA520',
			),
			// Silver tier: Silver, Bronze.
			'SILVER' => array(
				'primary_color' => '#708090',
				'border_color'  => '#A9A9A9',
				'badge_bg'      => 'linear-gradient(135deg, #E8E8E8, #C0C0C0)',
				'badge_text'    => '#333333',
				'badge_border'  => '#A9A9A9',
			),
			'BRONZE' => array(
				'primary_color' => '#8B4513',
				'border_color'  => '#A9A9A9',
				'badge_bg'      => 'linear-gradient(135deg, #DEB887, #CD7F32)',
				'badge_text'    => '#3e1a00',
				'badge_border'  => '#CD7F32',
			),
			// Green tier: Merit+, Merit, Merit-.
			'MERIT+' => array(
				'primary_color' => '#2E7D32',
				'border_color'  => '#4CAF50',
				'badge_bg'      => 'linear-gradient(135deg, #66BB6A, #4CAF50)',
				'badge_text'    => '#ffffff',
				'badge_border'  => '#388E3C',
			),
			'MERIT' => array(
				'primary_color' => '#388E3C',
				'border_color'  => '#66BB6A',
				'badge_bg'      => 'linear-gradient(135deg, #81C784, #66BB6A)',
				'badge_text'    => '#1b5e20',
				'badge_border'  => '#4CAF50',
			),
			'MERIT-' => array(
				'primary_color' => '#43A047',
				'border_color'  => '#81C784',
				'badge_bg'      => 'linear-gradient(135deg, #A5D6A7, #81C784)',
				'badge_text'    => '#1b5e20',
				'badge_border'  => '#66BB6A',
			),
			// Blue tier: Pass+, Pass.
			'PASS+' => array(
				'primary_color' => '#1565C0',
				'border_color'  => '#2196F3',
				'badge_bg'      => 'linear-gradient(135deg, #64B5F6, #2196F3)',
				'badge_text'    => '#ffffff',
				'badge_border'  => '#1976D2',
			),
			'PASS' => array(
				'primary_color' => '#1976D2',
				'border_color'  => '#42A5F5',
				'badge_bg'      => 'linear-gradient(135deg, #90CAF9, #42A5F5)',
				'badge_text'    => '#0d47a1',
				'badge_border'  => '#2196F3',
			),
		);

		if ( isset( $styles[ $level ] ) ) {
			return $styles[ $level ];
		}

		// Default / Unclassified.
		return array(
			'primary_color' => '#616161',
			'border_color'  => '#9E9E9E',
			'badge_bg'      => 'linear-gradient(135deg, #E0E0E0, #9E9E9E)',
			'badge_text'    => '#333333',
			'badge_border'  => '#757575',
		);
	}

	/**
	 * Delete a certificate file and its WordPress attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attachment_id The WordPress attachment ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_certificate_file( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			wfeb_log( 'Certificate file deletion failed - invalid attachment_id.' );
			return false;
		}

		// Get the file path before deleting the attachment.
		$file_path = get_attached_file( $attachment_id );

		// Delete the WordPress attachment (also deletes the file).
		$deleted = wp_delete_attachment( $attachment_id, true );

		if ( ! $deleted ) {
			wfeb_log( 'Certificate file deletion failed - wp_delete_attachment failed for attachment_id: ' . $attachment_id );

			// Try manual file deletion as fallback.
			if ( $file_path && file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
				wfeb_log( 'Certificate file deleted manually: ' . $file_path );
			}

			return false;
		}

		wfeb_log( 'Certificate file deleted - attachment_id: ' . $attachment_id );

		return true;
	}

	/**
	 * Get or create the upload directory path for certificate files.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return string|WP_Error The absolute path to the upload directory, or WP_Error on failure.
	 */
	private function get_upload_path() {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			wfeb_log( 'Upload directory error: ' . $upload_dir['error'] );
			return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}

		$path = trailingslashit( $upload_dir['basedir'] ) . $this->upload_dir;

		if ( ! file_exists( $path ) ) {
			$created = wp_mkdir_p( $path );

			if ( ! $created ) {
				wfeb_log( 'Failed to create certificate upload directory: ' . $path );
				return new WP_Error( 'mkdir_failed', __( 'Failed to create certificate upload directory.', 'wfeb' ) );
			}

			// Add an index.php for security.
			$index_file = trailingslashit( $path ) . 'index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		return $path;
	}

	/**
	 * Create a WordPress attachment record for a certificate file.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param string $filepath    The absolute path to the file.
	 * @param string $file_url    The public URL to the file.
	 * @param object $certificate The certificate object.
	 * @return int|WP_Error The attachment ID on success, WP_Error on failure.
	 */
	private function create_attachment( $filepath, $file_url, $certificate ) {
		$filetype = wp_check_filetype( basename( $filepath ), array( 'html' => 'text/html' ) );

		$attachment_data = array(
			'guid'           => $file_url,
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'text/html',
			'post_title'     => sprintf(
				/* translators: %s: certificate number */
				__( 'Certificate %s', 'wfeb' ),
				$certificate->certificate_number
			),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment_data, $filepath );

		if ( is_wp_error( $attachment_id ) ) {
			wfeb_log( 'Failed to create attachment for cert: ' . $certificate->certificate_number . ' - ' . $attachment_id->get_error_message() );
			return $attachment_id;
		}

		if ( ! $attachment_id ) {
			wfeb_log( 'Failed to create attachment for cert: ' . $certificate->certificate_number );
			return new WP_Error( 'attachment_failed', __( 'Failed to create file attachment.', 'wfeb' ) );
		}

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}

	/**
	 * Generate a score report HTML file for a certificate.
	 *
	 * Creates a self-contained A4 portrait HTML page with an SVG radar
	 * chart and 7-category score breakdown bars. Print-ready with
	 * @media print CSS.
	 *
	 * @since 2.4.0
	 *
	 * @param object $certificate The certificate object with joined data.
	 * @param object $exam        The full exam object with individual category scores.
	 * @return array|WP_Error Array with 'url' and 'attachment_id' on success, WP_Error on failure.
	 */
	public function generate_score_report( $certificate, $exam ) {
		if ( empty( $certificate ) || empty( $exam ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid certificate or exam data.', 'wfeb' ) );
		}

		$html = $this->get_score_report_html( $certificate, $exam );

		if ( empty( $html ) ) {
			return new WP_Error( 'html_generation_failed', __( 'Failed to generate score report HTML.', 'wfeb' ) );
		}

		$upload_path = $this->get_upload_path();

		if ( is_wp_error( $upload_path ) ) {
			return $upload_path;
		}

		$filename = sanitize_file_name( 'score-report-' . $certificate->certificate_number . '.html' );
		$filepath = trailingslashit( $upload_path ) . $filename;

		$written = file_put_contents( $filepath, $html ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $written ) {
			return new WP_Error( 'file_write_failed', __( 'Failed to write score report file.', 'wfeb' ) );
		}

		$upload_dir = wp_upload_dir();
		$file_url   = trailingslashit( $upload_dir['baseurl'] ) . $this->upload_dir . '/' . $filename;

		$attachment_id = $this->create_attachment( $filepath, $file_url, $certificate );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wfeb_log( 'Score report generated - cert: ' . $certificate->certificate_number );

		return array(
			'url'           => $file_url,
			'attachment_id' => $attachment_id,
		);
	}

	/**
	 * Generate the HTML content for a score report.
	 *
	 * @since  2.4.0
	 * @access private
	 *
	 * @param object $certificate The certificate object.
	 * @param object $exam        The exam object with category scores.
	 * @return string The complete HTML document.
	 */
	private function get_score_report_html( $certificate, $exam ) {
		$player_name   = isset( $certificate->player_name ) ? esc_html( $certificate->player_name ) : '';
		$cert_number   = isset( $certificate->certificate_number ) ? esc_html( $certificate->certificate_number ) : '';
		$achievement   = isset( $certificate->achievement_level ) ? esc_html( $certificate->achievement_level ) : '';
		$total_score   = isset( $certificate->total_score ) ? absint( $certificate->total_score ) : 0;
		$exam_date     = isset( $certificate->exam_date ) ? esc_html( wfeb_format_date( $certificate->exam_date, 'j F Y' ) ) : '';
		$coach_name    = isset( $certificate->coach_name ) ? esc_html( $certificate->coach_name ) : '';
		$level_styles  = $this->get_level_styles( $achievement );
		$logo_url      = WFEB_PLUGIN_URL . 'assets/images/LOGO TRANSPARENT.png';

		$categories = array(
			array( 'label' => 'Short Passing', 'score' => absint( $exam->short_passing_total ), 'max' => 10 ),
			array( 'label' => 'Long Passing',  'score' => absint( $exam->long_passing_total ),  'max' => 10 ),
			array( 'label' => 'Shooting',      'score' => absint( $exam->shooting_total ),      'max' => 20 ),
			array( 'label' => 'Sprinting',     'score' => absint( $exam->sprint_score ),        'max' => 10 ),
			array( 'label' => 'Dribbling',     'score' => absint( $exam->dribble_score ),       'max' => 10 ),
			array( 'label' => 'Kick Ups',      'score' => absint( $exam->kickups_score ),       'max' => 10 ),
			array( 'label' => 'Volley',        'score' => absint( $exam->volley_total ),        'max' => 10 ),
		);

		$radar_svg = $this->generate_radar_svg( $categories );

		// Build score bars HTML.
		$bars_html = '';
		foreach ( $categories as $cat ) {
			$pct = $cat['max'] > 0 ? ( $cat['score'] / $cat['max'] ) * 100 : 0;

			if ( $pct >= 80 ) {
				$bar_color = '#10b981';
			} elseif ( $pct >= 60 ) {
				$bar_color = '#14b8a6';
			} elseif ( $pct >= 40 ) {
				$bar_color = '#f59e0b';
			} elseif ( $pct >= 20 ) {
				$bar_color = '#f97316';
			} else {
				$bar_color = '#ef4444';
			}

			$bars_html .= '<div class="sr-bar">'
				. '<span class="sr-bar-label">' . esc_html( $cat['label'] ) . '</span>'
				. '<div class="sr-bar-track">'
				. '<div class="sr-bar-fill" style="width:' . round( $pct ) . '%;background:' . $bar_color . ';"></div>'
				. '</div>'
				. '<span class="sr-bar-value">' . $cat['score'] . '/' . $cat['max'] . '</span>'
				. '</div>';
		}

		$score_pct = $total_score > 0 ? round( ( $total_score / 80 ) * 100 ) : 0;

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Score Report - <?php echo $cert_number; ?></title>
	<style>
		*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

		@page { size: A4 portrait; margin: 0; }

		@media print {
			html, body { width: 210mm; height: 297mm; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.sr-page { page-break-after: avoid; page-break-inside: avoid; box-shadow: none !important; }
			.no-print { display: none !important; }
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: #f5f5f5;
			display: flex;
			justify-content: center;
			align-items: flex-start;
			min-height: 100vh;
			padding: 20px;
		}

		@media print { body { background: #fff; padding: 0; } }

		.sr-page {
			width: 210mm;
			min-height: 297mm;
			background: #fff;
			position: relative;
			overflow: hidden;
			box-shadow: 0 4px 20px rgba(0,0,0,0.15);
			padding: 15mm 18mm;
		}

		.sr-header {
			text-align: center;
			margin-bottom: 8mm;
			padding-bottom: 6mm;
			border-bottom: 2px solid <?php echo $level_styles['border_color']; ?>;
		}

		.sr-logo { height: 40px; margin-bottom: 6px; }

		.sr-title {
			font-size: 22px;
			font-weight: 700;
			letter-spacing: 3px;
			text-transform: uppercase;
			color: <?php echo $level_styles['primary_color']; ?>;
			margin-bottom: 2px;
		}

		.sr-subtitle {
			font-size: 11px;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: #666;
		}

		.sr-info-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 12px;
			margin-bottom: 8mm;
			text-align: center;
		}

		.sr-info-item-label {
			font-size: 9px;
			letter-spacing: 1.5px;
			text-transform: uppercase;
			color: #999;
			margin-bottom: 3px;
		}

		.sr-info-item-value {
			font-size: 13px;
			font-weight: 700;
			color: #1a1a1a;
		}

		.sr-level-badge {
			display: inline-block;
			padding: 3px 14px;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: <?php echo $level_styles['badge_text']; ?>;
			background: <?php echo $level_styles['badge_bg']; ?>;
			border-radius: 4px;
			border: 1px solid <?php echo $level_styles['badge_border']; ?>;
		}

		.sr-two-col {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8mm;
			margin-bottom: 8mm;
		}

		.sr-section-title {
			font-size: 14px;
			font-weight: 700;
			color: #1a1a1a;
			margin-bottom: 12px;
			padding-bottom: 6px;
			border-bottom: 1px solid #e2e8f0;
		}

		.sr-radar-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.sr-bar {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 10px;
		}

		.sr-bar-label {
			width: 90px;
			font-size: 11px;
			font-weight: 600;
			color: #334155;
			flex-shrink: 0;
		}

		.sr-bar-track {
			flex: 1;
			height: 14px;
			background: #f1f5f9;
			border-radius: 7px;
			overflow: hidden;
		}

		.sr-bar-fill {
			height: 100%;
			border-radius: 7px;
			transition: none;
		}

		.sr-bar-value {
			width: 40px;
			font-size: 11px;
			font-weight: 700;
			color: #334155;
			text-align: right;
			flex-shrink: 0;
		}

		.sr-score-summary {
			text-align: center;
			padding: 6mm 0;
			border-top: 1px solid #e2e8f0;
		}

		.sr-score-big {
			font-size: 48px;
			font-weight: 800;
			color: <?php echo $level_styles['primary_color']; ?>;
			line-height: 1;
		}

		.sr-score-big span {
			font-size: 20px;
			font-weight: 400;
			color: #999;
		}

		.sr-score-pct {
			font-size: 14px;
			color: #666;
			margin-top: 4px;
		}

		.sr-footer {
			text-align: center;
			font-size: 9px;
			color: #999;
			letter-spacing: 1px;
			text-transform: uppercase;
			margin-top: 6mm;
			padding-top: 4mm;
			border-top: 1px solid #e2e8f0;
		}

		.print-controls {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 1000;
		}

		.btn-print {
			display: inline-block;
			padding: 10px 24px;
			background: <?php echo $level_styles['primary_color']; ?>;
			color: #fff;
			border: none;
			border-radius: 4px;
			font-size: 14px;
			cursor: pointer;
			font-family: inherit;
		}

		.btn-print:hover { opacity: 0.9; }
	</style>
</head>
<body>
	<div class="print-controls no-print">
		<button class="btn-print" onclick="window.print();">Print / Save as PDF</button>
	</div>

	<div class="sr-page">
		<div class="sr-header">
			<img class="sr-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="WFEB">
			<h1 class="sr-title">Skills Score Report</h1>
			<p class="sr-subtitle">World Football Examination Board</p>
		</div>

		<div class="sr-info-grid">
			<div>
				<div class="sr-info-item-label">Player</div>
				<div class="sr-info-item-value"><?php echo $player_name; ?></div>
			</div>
			<div>
				<div class="sr-info-item-label">Exam Date</div>
				<div class="sr-info-item-value"><?php echo $exam_date; ?></div>
			</div>
			<div>
				<div class="sr-info-item-label">Certificate</div>
				<div class="sr-info-item-value"><?php echo $cert_number; ?></div>
			</div>
			<div>
				<div class="sr-info-item-label">Achievement</div>
				<div class="sr-info-item-value"><span class="sr-level-badge"><?php echo $achievement; ?></span></div>
			</div>
		</div>

		<div class="sr-two-col">
			<div>
				<div class="sr-section-title">Skills Radar</div>
				<div class="sr-radar-wrap">
					<?php echo $radar_svg; ?>
				</div>
			</div>
			<div>
				<div class="sr-section-title">Score Breakdown</div>
				<?php echo $bars_html; ?>
			</div>
		</div>

		<div class="sr-score-summary">
			<div class="sr-score-big"><?php echo $total_score; ?><span>/80</span></div>
			<div class="sr-score-pct"><?php echo $score_pct; ?>% Overall Score</div>
		</div>

		<div class="sr-footer">
			<?php echo $cert_number; ?> &bull; Examiner: <?php echo $coach_name; ?> &bull; World Football Examination Board
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate an SVG radar chart for score categories.
	 *
	 * Produces a pure SVG heptagon radar chart with grid rings, axis lines,
	 * data polygon, vertex dots, and labels. No JS required.
	 *
	 * @since  2.4.0
	 * @access private
	 *
	 * @param array $categories Array of arrays with 'label', 'score', 'max' keys.
	 * @return string The SVG markup.
	 */
	private function generate_radar_svg( $categories ) {
		$cx         = 160;
		$cy         = 160;
		$radius     = 120;
		$n          = count( $categories );
		$angle_step = ( 2 * M_PI ) / $n;
		$start      = -M_PI / 2;

		// Grid rings.
		$grid = '';
		foreach ( array( 20, 40, 60, 80, 100 ) as $pct ) {
			$r   = $radius * $pct / 100;
			$pts = array();
			for ( $i = 0; $i < $n; $i++ ) {
				$a     = $start + $i * $angle_step;
				$pts[] = round( $cx + $r * cos( $a ), 2 ) . ',' . round( $cy + $r * sin( $a ), 2 );
			}
			$grid .= '<polygon points="' . implode( ' ', $pts ) . '" fill="none" stroke="#e2e8f0" stroke-width="1"/>';
		}

		// Axis lines.
		$axes = '';
		for ( $i = 0; $i < $n; $i++ ) {
			$a  = $start + $i * $angle_step;
			$x2 = round( $cx + $radius * cos( $a ), 2 );
			$y2 = round( $cy + $radius * sin( $a ), 2 );
			$axes .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#e2e8f0" stroke-width="1"/>';
		}

		// Data polygon + dots.
		$data_pts = array();
		$dots     = '';
		foreach ( $categories as $i => $cat ) {
			$pct = $cat['max'] > 0 ? ( $cat['score'] / $cat['max'] ) * 100 : 0;
			$r   = $radius * $pct / 100;
			$a   = $start + $i * $angle_step;
			$x   = round( $cx + $r * cos( $a ), 2 );
			$y   = round( $cy + $r * sin( $a ), 2 );
			$data_pts[] = $x . ',' . $y;
			$dots .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="rgba(16,185,129,1)" stroke="#fff" stroke-width="1.5"/>';
		}
		$data = '<polygon points="' . implode( ' ', $data_pts ) . '" fill="rgba(0,0,128,0.2)" stroke="rgba(16,185,129,1)" stroke-width="2"/>';

		// Labels.
		$labels       = '';
		$label_radius = $radius + 24;
		foreach ( $categories as $i => $cat ) {
			$a      = $start + $i * $angle_step;
			$x      = round( $cx + $label_radius * cos( $a ), 2 );
			$y      = round( $cy + $label_radius * sin( $a ), 2 );
			$cos_a  = cos( $a );
			$anchor = 'middle';
			if ( $cos_a < -0.1 ) {
				$anchor = 'end';
			} elseif ( $cos_a > 0.1 ) {
				$anchor = 'start';
			}
			$labels .= '<text x="' . $x . '" y="' . $y . '" text-anchor="' . $anchor . '" dominant-baseline="middle" font-family="-apple-system,BlinkMacSystemFont,sans-serif" font-size="11" font-weight="600" fill="#334155">' . esc_html( $cat['label'] ) . '</text>';
		}

		return '<svg viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg" style="max-width:280px;width:100%;height:auto;">'
			. $grid . $axes . $data . $dots . $labels
			. '</svg>';
	}
}
