<?php
/**
 * WFEB Certificate Model
 *
 * Handles certificate generation, verification, revocation, and all
 * database operations for the wfeb_certificates table.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Certificate
 *
 * Manages certificate records in the wfeb_certificates table.
 */
class WFEB_Certificate {

	/**
	 * The certificates table name (with prefix).
	 *
	 * @var string
	 */
	private $table;

	/**
	 * The exams table name (with prefix).
	 *
	 * @var string
	 */
	private $exams_table;

	/**
	 * The players table name (with prefix).
	 *
	 * @var string
	 */
	private $players_table;

	/**
	 * The coaches table name (with prefix).
	 *
	 * @var string
	 */
	private $coaches_table;

	/**
	 * Constructor.
	 *
	 * Sets up table name references.
	 */
	public function __construct() {
		global $wpdb;

		$this->table         = $wpdb->prefix . 'wfeb_certificates';
		$this->exams_table   = $wpdb->prefix . 'wfeb_exams';
		$this->players_table = $wpdb->prefix . 'wfeb_players';
		$this->coaches_table = $wpdb->prefix . 'wfeb_coaches';
	}

	/**
	 * Generate a certificate from a completed exam.
	 *
	 * Creates a certificate record with a sequential certificate number,
	 * generates the PDF/HTML file via WFEB_PDF, and stores the result.
	 *
	 * @since 1.0.0
	 *
	 * @param int $exam_id The completed exam ID.
	 * @return object|WP_Error Certificate object on success, WP_Error on failure.
	 */
	public function generate( $exam_id ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		if ( ! $exam_id ) {
			wfeb_log( 'Certificate generation failed - invalid exam_id.' );
			return new WP_Error( 'invalid_exam', __( 'Invalid exam ID.', 'wfeb' ) );
		}

		// Get the exam record.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exam = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->exams_table} WHERE id = %d",
				$exam_id
			)
		);

		if ( ! $exam ) {
			wfeb_log( 'Certificate generation failed - exam not found. ID: ' . $exam_id );
			return new WP_Error( 'exam_not_found', __( 'Exam not found.', 'wfeb' ) );
		}

		if ( 'completed' !== $exam->status ) {
			wfeb_log( 'Certificate generation failed - exam not completed. ID: ' . $exam_id . ', status: ' . $exam->status );
			return new WP_Error( 'exam_not_completed', __( 'Exam must be completed before generating a certificate.', 'wfeb' ) );
		}

		// Check if a certificate already exists for this exam.
		$existing = $this->get_by_exam( $exam_id );

		if ( $existing ) {
			wfeb_log( 'Certificate generation skipped - certificate already exists for exam_id: ' . $exam_id );
			return new WP_Error( 'certificate_exists', __( 'A certificate has already been issued for this exam.', 'wfeb' ) );
		}

		// Generate the sequential certificate number.
		$cert_number = $this->get_next_number();

		if ( is_wp_error( $cert_number ) ) {
			return $cert_number;
		}

		$now = current_time( 'mysql' );

		// Insert the certificate record.
		$inserted = $wpdb->insert(
			$this->table,
			array(
				'exam_id'            => $exam_id,
				'player_id'          => absint( $exam->player_id ),
				'coach_id'           => absint( $exam->coach_id ),
				'certificate_number' => sanitize_text_field( $cert_number ),
				'total_score'        => absint( $exam->total_score ),
				'achievement_level'  => sanitize_text_field( $exam->achievement_level ),
				'playing_level'      => sanitize_text_field( $exam->playing_level ),
				'pdf_url'            => '',
				'pdf_attachment_id'  => null,
				'status'             => 'active',
				'revoke_reason'      => null,
				'issued_at'          => $now,
				'created_at'         => $now,
			),
			array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wfeb_log( 'Certificate generation failed - DB insert error: ' . $wpdb->last_error );
			return new WP_Error( 'db_insert_error', __( 'Failed to create certificate record.', 'wfeb' ) );
		}

		$cert_id = $wpdb->insert_id;

		// Fetch the full certificate with joined data for PDF generation.
		$certificate = $this->get( $cert_id );

		if ( ! $certificate ) {
			wfeb_log( 'Certificate generation failed - could not retrieve certificate after insert. ID: ' . $cert_id );
			return new WP_Error( 'retrieval_error', __( 'Failed to retrieve certificate record after creation.', 'wfeb' ) );
		}

		// Generate the PDF/HTML file.
		$pdf_result = WFEB()->pdf->generate_certificate( $certificate );

		if ( is_wp_error( $pdf_result ) ) {
			wfeb_log( 'Certificate PDF generation failed for cert_id: ' . $cert_id . ' - ' . $pdf_result->get_error_message() );
			// Certificate record exists but PDF failed. Update record with error note.
			return $certificate;
		}

		// Update the certificate record with PDF information.
		$wpdb->update(
			$this->table,
			array(
				'pdf_url'           => esc_url_raw( $pdf_result['url'] ),
				'pdf_attachment_id' => absint( $pdf_result['attachment_id'] ),
			),
			array( 'id' => $cert_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);

		// Refresh the certificate object with PDF data.
		$certificate = $this->get( $cert_id );

		wfeb_log( 'Certificate generated successfully - cert_id: ' . $cert_id . ', number: ' . $cert_number . ', exam_id: ' . $exam_id );

		return $certificate;
	}

	/**
	 * Get a certificate by ID with joined exam, player, and coach data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $cert_id The certificate ID.
	 * @return object|null Certificate object with related data on success, null if not found.
	 */
	public function get( $cert_id ) {
		global $wpdb;

		$cert_id = absint( $cert_id );

		if ( ! $cert_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*,
					p.full_name AS player_name,
					p.dob AS player_dob,
					p.email AS player_email,
					co.full_name AS coach_name,
					co.email AS coach_email,
					e.exam_date,
					e.assistant_examiner,
					e.total_score AS exam_total_score
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->coaches_table} co ON c.coach_id = co.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				WHERE c.id = %d",
				$cert_id
			)
		);

		return $certificate;
	}

	/**
	 * Get a certificate by certificate number.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cert_number The certificate number.
	 * @return object|null Certificate object on success, null if not found.
	 */
	public function get_by_number( $cert_number ) {
		global $wpdb;

		if ( empty( $cert_number ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*,
					p.full_name AS player_name,
					p.dob AS player_dob,
					p.email AS player_email,
					co.full_name AS coach_name,
					co.email AS coach_email,
					e.exam_date,
					e.assistant_examiner
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->coaches_table} co ON c.coach_id = co.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				WHERE c.certificate_number = %s",
				sanitize_text_field( $cert_number )
			)
		);

		return $certificate;
	}

	/**
	 * Get a certificate for a specific exam.
	 *
	 * @since 1.0.0
	 *
	 * @param int $exam_id The exam ID.
	 * @return object|null Certificate object on success, null if not found.
	 */
	public function get_by_exam( $exam_id ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		if ( ! $exam_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*,
					p.full_name AS player_name,
					p.dob AS player_dob,
					co.full_name AS coach_name,
					e.exam_date
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->coaches_table} co ON c.coach_id = co.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				WHERE c.exam_id = %d",
				$exam_id
			)
		);

		return $certificate;
	}

	/**
	 * Verify a certificate for public verification.
	 *
	 * Searches by certificate number, then verifies the player name
	 * (case-insensitive partial match) and date of birth.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name        The player name to verify.
	 * @param string $cert_number The certificate number to look up.
	 * @param string $dob         The player date of birth (Y-m-d).
	 * @return array|WP_Error Verification result array on success, WP_Error if not found or mismatch.
	 */
	public function verify( $name, $cert_number, $dob ) {
		if ( empty( $cert_number ) ) {
			return new WP_Error( 'missing_cert_number', __( 'Certificate number is required.', 'wfeb' ) );
		}

		$certificate = $this->get_by_number( $cert_number );

		if ( ! $certificate ) {
			wfeb_log( 'Certificate verification failed - certificate not found: ' . $cert_number );
			return new WP_Error( 'not_found', __( 'Certificate not found. Please check the certificate number and try again.', 'wfeb' ) );
		}

		if ( 'active' !== $certificate->status ) {
			wfeb_log( 'Certificate verification failed - certificate revoked: ' . $cert_number );
			return new WP_Error( 'revoked', __( 'This certificate has been revoked.', 'wfeb' ) );
		}

		// Verify name: case-insensitive partial match.
		$search_name = strtolower( trim( $name ) );
		$player_name = strtolower( trim( $certificate->player_name ) );

		if ( ! empty( $search_name ) && false === strpos( $player_name, $search_name ) ) {
			wfeb_log( 'Certificate verification failed - name mismatch. Searched: ' . $name . ', Actual: ' . $certificate->player_name );
			return new WP_Error( 'name_mismatch', __( 'The name does not match the certificate records.', 'wfeb' ) );
		}

		// Verify date of birth.
		$search_dob = sanitize_text_field( $dob );
		$player_dob = $certificate->player_dob;

		if ( ! empty( $search_dob ) && $search_dob !== $player_dob ) {
			wfeb_log( 'Certificate verification failed - DOB mismatch for cert: ' . $cert_number );
			return new WP_Error( 'dob_mismatch', __( 'The date of birth does not match the certificate records.', 'wfeb' ) );
		}

		wfeb_log( 'Certificate verified successfully: ' . $cert_number );

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

	/**
	 * Revoke a certificate.
	 *
	 * Sets the certificate status to 'revoked' and stores the revoke reason.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $cert_id The certificate ID.
	 * @param string $reason  Optional reason for revocation.
	 * @return bool True on success, false on failure.
	 */
	public function revoke( $cert_id, $reason = '' ) {
		global $wpdb;

		$cert_id = absint( $cert_id );

		if ( ! $cert_id ) {
			wfeb_log( 'Certificate revocation failed - invalid cert_id.' );
			return false;
		}

		$result = $wpdb->update(
			$this->table,
			array(
				'status'        => 'revoked',
				'revoke_reason' => sanitize_textarea_field( $reason ),
			),
			array( 'id' => $cert_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Certificate revocation failed - DB error for cert_id: ' . $cert_id . ' - ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Certificate revoked - cert_id: ' . $cert_id . ', reason: ' . $reason );

		return true;
	}

	/**
	 * Get certificates for a specific player.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $player_id The player ID.
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $limit   Number of results. Default 20.
	 *     @type int    $offset  Offset for pagination. Default 0.
	 *     @type string $orderby Column to order by. Default 'issued_at'.
	 *     @type string $order   Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 * }
	 * @return array Array of certificate objects.
	 */
	public function get_by_player( $player_id, $args = array() ) {
		global $wpdb;

		$player_id = absint( $player_id );

		if ( ! $player_id ) {
			return array();
		}

		$defaults = array(
			'limit'   => 20,
			'offset'  => 0,
			'orderby' => 'issued_at',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist allowed columns for ordering.
		$allowed_orderby = array( 'id', 'certificate_number', 'total_score', 'achievement_level', 'issued_at', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'issued_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,
					p.full_name AS player_name,
					co.full_name AS coach_name,
					e.exam_date
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->coaches_table} co ON c.coach_id = co.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				WHERE c.player_id = %d
				ORDER BY c.{$orderby} {$order}
				LIMIT %d OFFSET %d",
				$player_id,
				$limit,
				$offset
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get certificates issued by a specific coach.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $coach_id The coach ID.
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $limit   Number of results. Default 20.
	 *     @type int    $offset  Offset for pagination. Default 0.
	 *     @type string $search  Search term to match against player name or certificate number.
	 *     @type string $orderby Column to order by. Default 'issued_at'.
	 *     @type string $order   Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 * }
	 * @return array Array of certificate objects.
	 */
	public function get_by_coach( $coach_id, $args = array() ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			return array();
		}

		$defaults = array(
			'limit'   => 20,
			'offset'  => 0,
			'search'  => '',
			'orderby' => 'issued_at',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist allowed columns for ordering.
		$allowed_orderby = array( 'id', 'certificate_number', 'total_score', 'achievement_level', 'issued_at', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'issued_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		$where  = array( 'c.coach_id = %d' );
		$values = array( $coach_id );

		// Search by player name or certificate number.
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(p.full_name LIKE %s OR c.certificate_number LIKE %s)';
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where );

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT c.*,
					p.full_name AS player_name,
					p.dob AS player_dob,
					e.exam_date
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				{$where_clause}
				ORDER BY c.{$orderby} {$order}
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $values )
		);

		return $results ? $results : array();
	}

	/**
	 * Get all certificates for admin view.
	 *
	 * Supports filters for status, search, date range, limit, and offset.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type string $status    Filter by status (active, revoked). Default empty (all).
	 *     @type string $search    Search term for player name, coach name, or certificate number.
	 *     @type string $date_from Start date filter (Y-m-d). Default empty.
	 *     @type string $date_to   End date filter (Y-m-d). Default empty.
	 *     @type string $orderby   Column to order by. Default 'issued_at'.
	 *     @type string $order     Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 *     @type int    $limit     Number of results. Default 20.
	 *     @type int    $offset    Offset for pagination. Default 0.
	 * }
	 * @return array Array of certificate objects.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'    => '',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'issued_at',
			'order'     => 'DESC',
			'limit'     => 20,
			'offset'    => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist allowed columns for ordering.
		$allowed_orderby = array( 'id', 'certificate_number', 'total_score', 'achievement_level', 'status', 'issued_at', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'issued_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$where  = array();
		$values = array();

		// Filter by status.
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'c.status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		// Search by player name, coach name, or certificate number.
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(p.full_name LIKE %s OR co.full_name LIKE %s OR c.certificate_number LIKE %s)';
			$values[]    = $search_term;
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		// Date range filter on issued_at.
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'c.issued_at >= %s';
			$values[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'c.issued_at <= %s';
			$values[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59';
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT c.*,
					p.full_name AS player_name,
					p.dob AS player_dob,
					co.full_name AS coach_name,
					co.email AS coach_email,
					e.exam_date
				FROM {$this->table} c
				LEFT JOIN {$this->players_table} p ON c.player_id = p.id
				LEFT JOIN {$this->coaches_table} co ON c.coach_id = co.id
				LEFT JOIN {$this->exams_table} e ON c.exam_id = e.id
				{$where_clause}
				ORDER BY c.{$orderby} {$order}
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $values )
		);

		return $results ? $results : array();
	}

	/**
	 * Count certificates, optionally filtered by status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Optional status filter (active, revoked). Empty for all.
	 * @return int Number of certificates.
	 */
	public function get_count( $status = '' ) {
		global $wpdb;

		if ( ! empty( $status ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE status = %s",
					sanitize_text_field( $status )
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		}

		return absint( $count );
	}

	/**
	 * Get the next sequential certificate number.
	 *
	 * Uses the prefix from option 'wfeb_cert_prefix' (default 'WFEB') and
	 * auto-increment starting from option 'wfeb_cert_start' (default 1000).
	 * Gets the next number from max existing certificate number + 1.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @return string|WP_Error The next certificate number string, or WP_Error on failure.
	 */
	private function get_next_number() {
		global $wpdb;

		$prefix     = get_option( 'wfeb_cert_prefix', 'WFEB' );
		$start_from = absint( get_option( 'wfeb_cert_start', 1000 ) );

		// Get the maximum numeric part from existing certificate numbers.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max_number = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(CAST(SUBSTRING(certificate_number, %d) AS UNSIGNED))
				FROM {$this->table}
				WHERE certificate_number LIKE %s",
				strlen( $prefix ) + 2, // +2 for the prefix and the '-' separator.
				$wpdb->esc_like( $prefix ) . '-%'
			)
		);

		if ( $max_number && $max_number >= $start_from ) {
			$next_number = $max_number + 1;
		} else {
			$next_number = $start_from;
		}

		$cert_number = $prefix . '-' . $next_number;

		// Verify uniqueness as a safety check.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE certificate_number = %s",
				$cert_number
			)
		);

		if ( $exists > 0 ) {
			wfeb_log( 'Certificate number collision detected: ' . $cert_number . '. Incrementing.' );
			// Increment until we find a unique number.
			$attempts = 0;
			while ( $exists > 0 && $attempts < 100 ) {
				$next_number++;
				$cert_number = $prefix . '-' . $next_number;

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$this->table} WHERE certificate_number = %s",
						$cert_number
					)
				);

				$attempts++;
			}

			if ( $exists > 0 ) {
				wfeb_log( 'Certificate number generation failed after 100 attempts.' );
				return new WP_Error( 'cert_number_failed', __( 'Failed to generate a unique certificate number.', 'wfeb' ) );
			}
		}

		return $cert_number;
	}
}
