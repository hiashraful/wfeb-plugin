<?php
/**
 * WFEB Coach Model
 *
 * Handles coach registration, approval workflow, credit management,
 * and all database operations for the wfeb_coaches table.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Coach
 *
 * Model class for coach CRUD operations, status management, and credit transactions.
 */
class WFEB_Coach {

	/**
	 * The coaches database table name (with prefix).
	 *
	 * @var string
	 */
	private $table;

	/**
	 * The credit transactions database table name (with prefix).
	 *
	 * @var string
	 */
	private $transactions_table;

	/**
	 * Constructor.
	 *
	 * Sets up table name references.
	 */
	public function __construct() {
		global $wpdb;

		$this->table              = $wpdb->prefix . 'wfeb_coaches';
		$this->transactions_table = $wpdb->prefix . 'wfeb_credit_transactions';
	}

	/**
	 * Register a new coach.
	 *
	 * Creates a WordPress user with the wfeb_coach role and inserts a
	 * corresponding record in the coaches table. Status is set to 'pending'
	 * unless the wfeb_coach_approval_mode option is 'auto', in which case
	 * the coach is approved immediately.
	 *
	 * @param array $data {
	 *     Registration data.
	 *
	 *     @type string $full_name            Coach full name.
	 *     @type string $dob                  Date of birth (Y-m-d).
	 *     @type string $address              Postal address.
	 *     @type string $ngb_number           National governing body number.
	 *     @type string $coaching_certificate  File path to uploaded certificate.
	 *     @type string $email                Email address.
	 *     @type string $phone                Phone number.
	 *     @type string $password             Plain-text password.
	 * }
	 * @return int|WP_Error Coach ID on success, WP_Error on failure.
	 */
	public function register( $data ) {
		global $wpdb;

		// Validate required fields.
		$required = array( 'full_name', 'email', 'password', 'dob', 'ngb_number' );

		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				wfeb_log( 'Coach registration failed - missing required field: ' . $field );
				return new WP_Error(
					'missing_field',
					/* translators: %s: field name */
					sprintf( __( 'The %s field is required.', 'wfeb' ), $field )
				);
			}
		}

		// Check if email already exists as a WP user.
		if ( email_exists( sanitize_email( $data['email'] ) ) ) {
			wfeb_log( 'Coach registration failed - email already exists: ' . $data['email'] );
			return new WP_Error(
				'email_exists',
				__( 'An account with this email address already exists.', 'wfeb' )
			);
		}

		// Check if a coach record with this email already exists in coaches table.
		$existing_coach = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table} WHERE email = %s",
			sanitize_email( $data['email'] )
		) );

		if ( $existing_coach ) {
			wfeb_log( 'Coach registration failed - coach record already exists for email: ' . $data['email'] . ', coach_id: ' . $existing_coach );
			return new WP_Error(
				'email_exists',
				__( 'An account with this email address already exists.', 'wfeb' )
			);
		}

		// Create the WordPress user.
		$user_id = wp_create_user(
			sanitize_email( $data['email'] ),
			$data['password'],
			sanitize_email( $data['email'] )
		);

		if ( is_wp_error( $user_id ) ) {
			wfeb_log( 'Coach registration failed - WP user creation error: ' . $user_id->get_error_message() );
			return $user_id;
		}

		// Set the user role to wfeb_coach.
		$user = new WP_User( $user_id );
		$user->set_role( 'wfeb_coach' );

		// Update the user display name.
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => sanitize_text_field( $data['full_name'] ),
			'first_name'   => sanitize_text_field( $data['full_name'] ),
		) );

		// Determine initial status.
		$approval_mode = get_option( 'wfeb_coach_approval_mode', 'manual' );
		$status        = ( 'auto' === $approval_mode ) ? 'approved' : 'pending';

		$now = current_time( 'mysql' );

		// Check if a coach record already exists for this user_id (prevent duplicates).
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table} WHERE user_id = %d",
			$user_id
		) );

		if ( $existing ) {
			wfeb_log( 'Coach registration skipped - coach record already exists for user_id: ' . $user_id . ', coach_id: ' . $existing );
			return (int) $existing;
		}

		// Insert the coach record.
		$inserted = $wpdb->insert(
			$this->table,
			array(
				'user_id'              => $user_id,
				'full_name'            => sanitize_text_field( $data['full_name'] ),
				'dob'                  => sanitize_text_field( $data['dob'] ),
				'address'              => sanitize_textarea_field( isset( $data['address'] ) ? $data['address'] : '' ),
				'country'              => sanitize_text_field( isset( $data['country'] ) ? $data['country'] : 'United Kingdom' ),
				'ngb_number'           => sanitize_text_field( $data['ngb_number'] ),
				'coaching_certificate' => sanitize_text_field( isset( $data['coaching_certificate'] ) ? $data['coaching_certificate'] : '' ),
				'email'                => sanitize_email( $data['email'] ),
				'phone'                => sanitize_text_field( isset( $data['phone'] ) ? $data['phone'] : '' ),
				'status'               => $status,
				'credits_balance'      => 0,
				'rejection_reason'     => '',
				'created_at'           => $now,
				'updated_at'           => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wfeb_log( 'Coach registration failed - DB insert error: ' . $wpdb->last_error );

			// Roll back: delete the WP user we just created.
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );

			return new WP_Error(
				'db_insert_failed',
				__( 'Failed to create coach record. Please try again.', 'wfeb' )
			);
		}

		$coach_id = $wpdb->insert_id;

		wfeb_log( 'Coach registered successfully - coach_id: ' . $coach_id . ', user_id: ' . $user_id . ', status: ' . $status );

		return $coach_id;
	}

	/**
	 * Get a coach record by coach ID.
	 *
	 * @param int $coach_id The coach ID.
	 * @return object|null Coach object on success, null if not found.
	 */
	public function get( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$coach = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$coach_id
			)
		);

		return $coach;
	}

	/**
	 * Get a coach record by WordPress user ID.
	 *
	 * @param int $user_id The WordPress user ID.
	 * @return object|null Coach object on success, null if not found.
	 */
	public function get_by_user_id( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$coach = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d",
				$user_id
			)
		);

		return $coach;
	}

	/**
	 * Update a coach record.
	 *
	 * Accepts an associative array of fields to update. Only whitelisted
	 * columns are permitted. The updated_at timestamp is set automatically.
	 *
	 * @param int   $coach_id The coach ID.
	 * @param array $data     Associative array of column => value pairs.
	 * @return bool True on success, false on failure.
	 */
	public function update( $coach_id, $data ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			wfeb_log( 'Coach update failed - invalid coach_id' );
			return false;
		}

		// Whitelist of updatable columns.
		$allowed_fields = array(
			'full_name',
			'dob',
			'address',
			'country',
			'ngb_number',
			'coaching_certificate',
			'profile_picture',
			'email',
			'phone',
			'status',
			'credits_balance',
			'rejection_reason',
		);

		$update_data    = array();
		$update_formats = array();

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue;
			}

			switch ( $key ) {
				case 'credits_balance':
				case 'profile_picture':
					$update_data[ $key ] = absint( $value );
					$update_formats[]    = '%d';
					break;

				case 'email':
					$update_data[ $key ] = sanitize_email( $value );
					$update_formats[]    = '%s';
					break;

				case 'address':
				case 'rejection_reason':
					$update_data[ $key ] = sanitize_textarea_field( $value );
					$update_formats[]    = '%s';
					break;

				default:
					$update_data[ $key ] = sanitize_text_field( $value );
					$update_formats[]    = '%s';
					break;
			}
		}

		if ( empty( $update_data ) ) {
			wfeb_log( 'Coach update failed - no valid fields to update for coach_id: ' . $coach_id );
			return false;
		}

		// Always set updated_at.
		$update_data['updated_at'] = current_time( 'mysql' );
		$update_formats[]          = '%s';

		$result = $wpdb->update(
			$this->table,
			$update_data,
			array( 'id' => $coach_id ),
			$update_formats,
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Coach update failed - DB error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Coach updated successfully - coach_id: ' . $coach_id );

		return true;
	}

	/**
	 * Approve a coach.
	 *
	 * Sets the coach status to 'approved' and updates the timestamp.
	 *
	 * @param int $coach_id The coach ID.
	 * @return bool True on success, false on failure.
	 */
	public function approve( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			wfeb_log( 'Coach approval failed - invalid coach_id' );
			return false;
		}

		$result = $wpdb->update(
			$this->table,
			array(
				'status'     => 'approved',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $coach_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Coach approval failed - DB error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Coach approved - coach_id: ' . $coach_id );

		return true;
	}

	/**
	 * Reject a coach.
	 *
	 * Sets the coach status to 'rejected', stores the rejection reason,
	 * and updates the timestamp.
	 *
	 * @param int    $coach_id The coach ID.
	 * @param string $reason   Optional rejection reason.
	 * @return bool True on success, false on failure.
	 */
	public function reject( $coach_id, $reason = '' ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			wfeb_log( 'Coach rejection failed - invalid coach_id' );
			return false;
		}

		$result = $wpdb->update(
			$this->table,
			array(
				'status'           => 'rejected',
				'rejection_reason' => sanitize_textarea_field( $reason ),
				'updated_at'       => current_time( 'mysql' ),
			),
			array( 'id' => $coach_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Coach rejection failed - DB error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Coach rejected - coach_id: ' . $coach_id . ', reason: ' . $reason );

		return true;
	}

	/**
	 * Suspend a coach.
	 *
	 * Sets the coach status to 'suspended' and updates the timestamp.
	 *
	 * @param int $coach_id The coach ID.
	 * @return bool True on success, false on failure.
	 */
	public function suspend( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			wfeb_log( 'Coach suspension failed - invalid coach_id' );
			return false;
		}

		$result = $wpdb->update(
			$this->table,
			array(
				'status'     => 'suspended',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $coach_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Coach suspension failed - DB error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Coach suspended - coach_id: ' . $coach_id );

		return true;
	}

	/**
	 * Delete a coach record and the associated WordPress user.
	 *
	 * @param int $coach_id The coach ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			wfeb_log( 'Coach deletion failed - invalid coach_id' );
			return false;
		}

		// Fetch the coach to get the user_id before deleting.
		$coach = $this->get( $coach_id );

		if ( ! $coach ) {
			wfeb_log( 'Coach deletion failed - coach not found for coach_id: ' . $coach_id );
			return false;
		}

		// Delete the coach record from the database.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->delete(
			$this->table,
			array( 'id' => $coach_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			wfeb_log( 'Coach deletion failed - DB error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		// Delete the associated WordPress user.
		if ( ! empty( $coach->user_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $coach->user_id );
		}

		wfeb_log( 'Coach deleted - coach_id: ' . $coach_id . ', user_id: ' . $coach->user_id );

		return true;
	}

	/**
	 * Remove duplicate coach records, keeping the one with the lowest ID.
	 * Checks duplicates by email (since race conditions can create separate WP users).
	 *
	 * @return int Number of duplicates removed.
	 */
	public function cleanup_duplicates() {
		global $wpdb;

		// Find emails that have more than one coach record.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$duplicates = $wpdb->get_results(
			"SELECT email, MIN(id) AS keep_id, COUNT(*) AS cnt
			 FROM {$this->table}
			 GROUP BY email
			 HAVING cnt > 1"
		);

		if ( empty( $duplicates ) ) {
			return 0;
		}

		$removed = 0;

		foreach ( $duplicates as $dup ) {
			// Get the duplicate rows (all except the one we're keeping).
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$dup_rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, user_id FROM {$this->table} WHERE email = %s AND id != %d",
				$dup->email,
				$dup->keep_id
			) );

			foreach ( $dup_rows as $dup_row ) {
				// Delete the duplicate coach record.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->delete( $this->table, array( 'id' => $dup_row->id ), array( '%d' ) );

				// Also delete the orphaned WP user created by the race condition.
				if ( ! empty( $dup_row->user_id ) ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';
					wp_delete_user( $dup_row->user_id );
					wfeb_log( 'Deleted orphaned WP user_id: ' . $dup_row->user_id . ' from duplicate coach_id: ' . $dup_row->id );
				}

				$removed++;
			}

			wfeb_log( 'Cleaned up duplicate coach record(s) for email: ' . $dup->email . ', kept coach_id: ' . $dup->keep_id );
		}

		return $removed;
	}

	/**
	 * Get the credit balance for a coach.
	 *
	 * @param int $coach_id The coach ID.
	 * @return int Credit balance, or 0 if coach not found.
	 */
	public function get_credits( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT credits_balance FROM {$this->table} WHERE id = %d",
				$coach_id
			)
		);

		return absint( $balance );
	}

	/**
	 * Deduct one credit from a coach's balance.
	 *
	 * Checks that the coach has a positive balance before deducting.
	 * Records a transaction of type 'usage' with amount -1 in the
	 * credit transactions table.
	 *
	 * @param int $coach_id The coach ID.
	 * @return true|WP_Error True on success, WP_Error if insufficient credits or failure.
	 */
	public function deduct_credit( $coach_id ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			return new WP_Error(
				'invalid_coach',
				__( 'Invalid coach ID.', 'wfeb' )
			);
		}

		$current_balance = $this->get_credits( $coach_id );

		if ( $current_balance <= 0 ) {
			wfeb_log( 'Credit deduction failed - insufficient balance for coach_id: ' . $coach_id . ', balance: ' . $current_balance );
			return new WP_Error(
				'insufficient_credits',
				__( 'Insufficient certificate credits. Please purchase more credits to continue.', 'wfeb' )
			);
		}

		$new_balance = $current_balance - 1;

		// Update the coach's credit balance.
		$updated = $wpdb->update(
			$this->table,
			array(
				'credits_balance' => $new_balance,
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $coach_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wfeb_log( 'Credit deduction failed - DB update error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return new WP_Error(
				'deduction_failed',
				__( 'Failed to deduct credit. Please try again.', 'wfeb' )
			);
		}

		// Record the transaction.
		$wpdb->insert(
			$this->transactions_table,
			array(
				'coach_id'    => $coach_id,
				'type'        => 'usage',
				'amount'      => -1,
				'balance'     => $new_balance,
				'description' => __( 'Certificate credit used', 'wfeb' ),
				'order_id'    => null,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%d', '%s' )
		);

		wfeb_log( 'Credit deducted - coach_id: ' . $coach_id . ', new_balance: ' . $new_balance );

		return true;
	}

	/**
	 * Add credits to a coach's balance.
	 *
	 * Records a transaction of type 'purchase' in the credit transactions table.
	 *
	 * @param int      $coach_id The coach ID.
	 * @param int      $amount   Number of credits to add (must be positive).
	 * @param int|null $order_id Optional WooCommerce order ID.
	 * @return bool True on success, false on failure.
	 */
	public function add_credits( $coach_id, $amount, $order_id = null ) {
		global $wpdb;

		$coach_id = absint( $coach_id );
		$amount   = absint( $amount );

		if ( ! $coach_id || ! $amount ) {
			wfeb_log( 'Add credits failed - invalid coach_id or amount: coach_id=' . $coach_id . ', amount=' . $amount );
			return false;
		}

		$current_balance = $this->get_credits( $coach_id );
		$new_balance     = $current_balance + $amount;

		// Update the coach's credit balance.
		$updated = $wpdb->update(
			$this->table,
			array(
				'credits_balance' => $new_balance,
				'updated_at'      => current_time( 'mysql' ),
			),
			array( 'id' => $coach_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wfeb_log( 'Add credits failed - DB update error for coach_id: ' . $coach_id . ' - ' . $wpdb->last_error );
			return false;
		}

		// Build the transaction description.
		$description = sprintf(
			/* translators: %d: number of credits purchased */
			__( 'Purchased %d certificate credit(s)', 'wfeb' ),
			$amount
		);

		// Record the transaction.
		$transaction_data = array(
			'coach_id'    => $coach_id,
			'type'        => 'purchase',
			'amount'      => $amount,
			'balance'     => $new_balance,
			'description' => $description,
			'created_at'  => current_time( 'mysql' ),
		);

		$transaction_formats = array( '%d', '%s', '%d', '%d', '%s', '%s' );

		if ( ! is_null( $order_id ) ) {
			$transaction_data['order_id'] = absint( $order_id );
			$transaction_formats[]        = '%d';
		}

		$wpdb->insert(
			$this->transactions_table,
			$transaction_data,
			$transaction_formats
		);

		wfeb_log( 'Credits added - coach_id: ' . $coach_id . ', amount: ' . $amount . ', new_balance: ' . $new_balance . ( $order_id ? ', order_id: ' . $order_id : '' ) );

		return true;
	}

	/**
	 * Get all coaches with optional filtering, searching, and pagination.
	 *
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type string $status  Filter by status (pending, approved, rejected, suspended).
	 *     @type string $search  Search term to match against full_name, email, or ngb_number.
	 *     @type string $orderby Column to order by. Default 'created_at'.
	 *     @type string $order   Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 *     @type int    $limit   Number of results. Default 20.
	 *     @type int    $offset  Offset for pagination. Default 0.
	 * }
	 * @return array Array of coach objects.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => '',
			'search'  => '',
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 20,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist orderable columns.
		$allowed_orderby = array( 'id', 'full_name', 'email', 'status', 'credits_balance', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';

		$where   = array();
		$values  = array();

		// Filter by status.
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		// Search by name, email, or NGB number.
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(full_name LIKE %s OR email LIKE %s OR ngb_number LIKE %s)';
			$values[]    = $search_term;
			$values[]    = $search_term;
			$values[]    = $search_term;
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		// Build the query. Order by and limit/offset are appended safely.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$this->table} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, $values )
		);

		return $results ? $results : array();
	}

	/**
	 * Get the count of coaches, optionally filtered by status.
	 *
	 * @param string $status Optional status filter. Empty string for all coaches.
	 * @return int Number of coaches matching the criteria.
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
	 * Get credit transactions for a coach.
	 *
	 * @param int   $coach_id The coach ID.
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type int    $limit  Number of results. Default 20.
	 *     @type int    $offset Offset for pagination. Default 0.
	 *     @type string $type   Filter by transaction type (purchase, usage, refund, adjustment).
	 * }
	 * @return array Array of transaction objects.
	 */
	public function get_transactions( $coach_id, $args = array() ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		if ( ! $coach_id ) {
			return array();
		}

		$defaults = array(
			'limit'  => 20,
			'offset' => 0,
			'type'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( 'coach_id = %d' );
		$values = array( $coach_id );

		// Filter by transaction type.
		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'type = %s';
			$values[] = sanitize_text_field( $args['type'] );
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where );

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->transactions_table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$values
			)
		);

		return $results ? $results : array();
	}
}
