<?php
/**
 * WFEB Player Model
 *
 * Handles all database operations for the wfeb_players table.
 * Players are created by coaches and optionally linked to WP user accounts.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Player
 *
 * Manages player records in the wfeb_players table.
 */
class WFEB_Player {

	/**
	 * The players table name (with prefix).
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 *
	 * Sets the table name using the WordPress database prefix.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'wfeb_players';
	}

	/**
	 * Create a new player record.
	 *
	 * Does NOT create a WordPress user account. That happens on the first exam
	 * via create_wp_account().
	 *
	 * @since 1.0.0
	 *
	 * @param int   $coach_id The coach who owns this player.
	 * @param array $data {
	 *     Player data.
	 *
	 *     @type string $full_name Required. Player full name.
	 *     @type string $dob       Required. Date of birth (Y-m-d).
	 *     @type string $email     Optional. Player email address.
	 *     @type string $phone     Optional. Player phone number.
	 *     @type string $address   Optional. Player address.
	 * }
	 * @return int|WP_Error Player ID on success, WP_Error on failure.
	 */
	public function create( $coach_id, $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['full_name'] ) ) {
			wfeb_log( 'Player creation failed: full_name is required.' );
			return new WP_Error( 'missing_full_name', __( 'Player full name is required.', 'wfeb' ) );
		}

		if ( empty( $data['dob'] ) ) {
			wfeb_log( 'Player creation failed: dob is required.' );
			return new WP_Error( 'missing_dob', __( 'Player date of birth is required.', 'wfeb' ) );
		}

		if ( empty( $coach_id ) ) {
			wfeb_log( 'Player creation failed: coach_id is required.' );
			return new WP_Error( 'missing_coach_id', __( 'Coach ID is required.', 'wfeb' ) );
		}

		$insert_data = array(
			'coach_id'   => absint( $coach_id ),
			'full_name'  => sanitize_text_field( $data['full_name'] ),
			'dob'        => sanitize_text_field( $data['dob'] ),
			'email'      => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
			'phone'      => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'address'    => isset( $data['address'] ) ? sanitize_textarea_field( $data['address'] ) : null,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$formats = array(
			'%d', // coach_id
			'%s', // full_name
			'%s', // dob
			'%s', // email
			'%s', // phone
			'%s', // address
			'%s', // created_at
			'%s', // updated_at
		);

		if ( ! empty( $data['profile_picture'] ) ) {
			$insert_data['profile_picture'] = absint( $data['profile_picture'] );
			$formats[]                      = '%d';
		}

		$result = $wpdb->insert( $this->table, $insert_data, $formats );

		if ( false === $result ) {
			wfeb_log( 'Player creation failed: ' . $wpdb->last_error );
			return new WP_Error( 'db_insert_error', __( 'Failed to create player record.', 'wfeb' ) );
		}

		$player_id = $wpdb->insert_id;

		wfeb_log( 'Player created successfully. ID: ' . $player_id . ', Coach: ' . $coach_id );

		return $player_id;
	}

	/**
	 * Get a player by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $player_id The player ID.
	 * @return object|null Player object on success, null if not found.
	 */
	public function get( $player_id ) {
		global $wpdb;

		if ( empty( $player_id ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$player = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				absint( $player_id )
			)
		);

		return $player;
	}

	/**
	 * Get a player by WordPress user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The WordPress user ID.
	 * @return object|null Player object on success, null if not found.
	 */
	public function get_by_user_id( $user_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$player = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d",
				absint( $user_id )
			)
		);

		return $player;
	}

	/**
	 * Update a player record.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $player_id The player ID to update.
	 * @param array $data      Associative array of fields to update.
	 *                         Allowed keys: full_name, dob, email, phone, address, user_id.
	 * @return bool True on success, false on failure.
	 */
	public function update( $player_id, $data ) {
		global $wpdb;

		if ( empty( $player_id ) || empty( $data ) ) {
			return false;
		}

		$allowed_fields = array( 'full_name', 'dob', 'email', 'phone', 'address', 'user_id', 'profile_picture' );
		$update_data    = array();
		$formats        = array();

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue;
			}

			switch ( $key ) {
				case 'full_name':
					$update_data['full_name'] = sanitize_text_field( $value );
					$formats[]                = '%s';
					break;

				case 'dob':
					$update_data['dob'] = sanitize_text_field( $value );
					$formats[]          = '%s';
					break;

				case 'email':
					$update_data['email'] = sanitize_email( $value );
					$formats[]            = '%s';
					break;

				case 'phone':
					$update_data['phone'] = sanitize_text_field( $value );
					$formats[]            = '%s';
					break;

				case 'address':
					$update_data['address'] = sanitize_textarea_field( $value );
					$formats[]              = '%s';
					break;

				case 'user_id':
					$update_data['user_id'] = absint( $value );
					$formats[]              = '%d';
					break;

				case 'profile_picture':
					$update_data['profile_picture'] = absint( $value );
					$formats[]                      = '%d';
					break;
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		// Always set updated_at timestamp.
		$update_data['updated_at'] = current_time( 'mysql' );
		$formats[]                 = '%s';

		$result = $wpdb->update(
			$this->table,
			$update_data,
			array( 'id' => absint( $player_id ) ),
			$formats,
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Player update failed for ID ' . $player_id . ': ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Player updated successfully. ID: ' . $player_id );

		return true;
	}

	/**
	 * Delete a player record.
	 *
	 * If the player has a linked WordPress user account (user_id), that
	 * WP user is also deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $player_id The player ID to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $player_id ) {
		global $wpdb;

		if ( empty( $player_id ) ) {
			return false;
		}

		$player = $this->get( $player_id );

		if ( ! $player ) {
			wfeb_log( 'Player delete failed: player not found. ID: ' . $player_id );
			return false;
		}

		// If the player has a WP user account, delete it.
		if ( ! empty( $player->user_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			$user_deleted = wp_delete_user( $player->user_id );

			if ( $user_deleted ) {
				wfeb_log( 'WP user deleted for player. User ID: ' . $player->user_id . ', Player ID: ' . $player_id );
			} else {
				wfeb_log( 'Failed to delete WP user for player. User ID: ' . $player->user_id . ', Player ID: ' . $player_id );
			}
		}

		// Delete the player record.
		$result = $wpdb->delete(
			$this->table,
			array( 'id' => absint( $player_id ) ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'Player delete failed for ID ' . $player_id . ': ' . $wpdb->last_error );
			return false;
		}

		wfeb_log( 'Player deleted successfully. ID: ' . $player_id );

		return true;
	}

	/**
	 * Search a coach's players by name.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $coach_id The coach ID whose players to search.
	 * @param string $query    The search term to match against full_name.
	 * @return array Array of player objects.
	 */
	public function search( $coach_id, $query ) {
		global $wpdb;

		if ( empty( $coach_id ) || empty( $query ) ) {
			return array();
		}

		$like = '%' . $wpdb->esc_like( sanitize_text_field( $query ) ) . '%';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE coach_id = %d AND full_name LIKE %s ORDER BY full_name ASC",
				absint( $coach_id ),
				$like
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Create a WordPress user account for a player.
	 *
	 * Generates a random password, creates a WP user with the wfeb_player role,
	 * and links the user_id back to the players table.
	 *
	 * @since 1.0.0
	 *
	 * @param int $player_id The player ID to create a WP account for.
	 * @return int|WP_Error The new WP user ID on success, WP_Error on failure.
	 */
	public function create_wp_account( $player_id, &$plain_password = null ) {
		global $wpdb;

		$player = $this->get( $player_id );

		if ( ! $player ) {
			wfeb_log( 'WP account creation failed: player not found. ID: ' . $player_id );
			return new WP_Error( 'player_not_found', __( 'Player not found.', 'wfeb' ) );
		}

		// Check if the player already has a WP account.
		if ( ! empty( $player->user_id ) ) {
			wfeb_log( 'WP account creation skipped: player already has account. Player ID: ' . $player_id . ', User ID: ' . $player->user_id );
			return new WP_Error( 'account_exists', __( 'Player already has a login account.', 'wfeb' ) );
		}

		// Generate username from email or full_name.
		if ( ! empty( $player->email ) && is_email( $player->email ) ) {
			$username = sanitize_user( $player->email, true );
		} else {
			$username = sanitize_user( strtolower( str_replace( ' ', '_', $player->full_name ) ), true );
		}

		// Ensure the username is unique.
		if ( username_exists( $username ) ) {
			$username = $username . '_' . wp_rand( 100, 999 );
		}

		// If still not unique (edge case), keep appending random numbers.
		while ( username_exists( $username ) ) {
			$username = $username . '_' . wp_rand( 100, 999 );
		}

		// Generate a random password.
		$password      = wp_generate_password( 12 );
		$plain_password = $password;

		// Determine the email to use for the WP account.
		$email = ! empty( $player->email ) && is_email( $player->email )
			? $player->email
			: '';

		// If no email, we cannot create a WP user (email is required by WP).
		if ( empty( $email ) ) {
			wfeb_log( 'WP account creation failed: no valid email for player. ID: ' . $player_id );
			return new WP_Error( 'missing_email', __( 'A valid email address is required to create a login account.', 'wfeb' ) );
		}

		// Check if email already exists.
		if ( email_exists( $email ) ) {
			wfeb_log( 'WP account creation failed: email already in use. Email: ' . $email . ', Player ID: ' . $player_id );
			return new WP_Error( 'email_exists', __( 'This email address is already registered.', 'wfeb' ) );
		}

		// Create the WP user.
		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => $password,
				'user_email'   => $email,
				'display_name' => $player->full_name,
				'first_name'   => $player->full_name,
				'role'         => 'wfeb_player',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			wfeb_log( 'WP account creation failed for player ' . $player_id . ': ' . $user_id->get_error_message() );
			return $user_id;
		}

		// Link the WP user to the player record.
		$updated = $this->update( $player_id, array( 'user_id' => $user_id ) );

		if ( ! $updated ) {
			wfeb_log( 'Failed to link WP user ' . $user_id . ' to player ' . $player_id );
			// User was created but link failed -- attempt cleanup.
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
			return new WP_Error( 'link_failed', __( 'Failed to link login account to player record.', 'wfeb' ) );
		}

		wfeb_log( 'WP account created for player. Player ID: ' . $player_id . ', User ID: ' . $user_id . ', Username: ' . $username );

		return $user_id;
	}

	/**
	 * Get all players for a specific coach.
	 *
	 * Supports search filtering, sorting, and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $coach_id The coach ID.
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $search  Search term to match against full_name. Default empty.
	 *     @type string $orderby Column to order by. Default 'created_at'.
	 *     @type string $order   Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 *     @type int    $limit   Number of records to return. Default 20.
	 *     @type int    $offset  Number of records to skip. Default 0.
	 * }
	 * @return array Array of player objects.
	 */
	public function get_by_coach( $coach_id, $args = array() ) {
		global $wpdb;

		if ( empty( $coach_id ) ) {
			return array();
		}

		$defaults = array(
			'search'  => '',
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 20,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist allowed columns for ordering.
		$allowed_orderby = array( 'id', 'full_name', 'dob', 'email', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit           = absint( $args['limit'] );
		$offset          = absint( $args['offset'] );

		// Build the query.
		$where  = 'WHERE coach_id = %d';
		$params = array( absint( $coach_id ) );

		// Add search condition if provided.
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where   .= ' AND full_name LIKE %s';
			$params[] = $like;
		}

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$params
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Count player records.
	 *
	 * If a coach_id is provided, counts only that coach's players.
	 * Otherwise, counts all players in the system.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $coach_id Optional. The coach ID to filter by.
	 * @return int The number of players.
	 */
	public function get_count( $coach_id = null ) {
		global $wpdb;

		if ( ! empty( $coach_id ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE coach_id = %d",
					absint( $coach_id )
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		}

		return absint( $count );
	}

	/**
	 * Get exam statistics for a player.
	 *
	 * Queries the wfeb_exams table for completed exams and returns
	 * aggregated stats.
	 *
	 * @since 1.0.0
	 *
	 * @param int $player_id The player ID.
	 * @return object {
	 *     @type int         $total_exams    Total number of completed exams.
	 *     @type int|null    $best_score     Highest total_score achieved.
	 *     @type string|null $best_level     Achievement level of the best exam.
	 *     @type string|null $last_exam_date Date of the most recent completed exam.
	 * }
	 */
	public function get_exam_stats( $player_id ) {
		global $wpdb;

		$exams_table = $wpdb->prefix . 'wfeb_exams';

		$defaults = (object) array(
			'total_exams'    => 0,
			'best_score'     => null,
			'best_level'     => null,
			'last_exam_date' => null,
		);

		if ( empty( $player_id ) ) {
			return $defaults;
		}

		// Get total exams and best score in one query.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total_exams,
					MAX(total_score) AS best_score,
					MAX(exam_date) AS last_exam_date
				FROM {$exams_table}
				WHERE player_id = %d AND status = %s",
				absint( $player_id ),
				'completed'
			)
		);

		if ( ! $stats || 0 === (int) $stats->total_exams ) {
			return $defaults;
		}

		// Get the achievement level of the best scoring exam.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$best_level = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT achievement_level
				FROM {$exams_table}
				WHERE player_id = %d AND status = %s
				ORDER BY total_score DESC, exam_date DESC
				LIMIT 1",
				absint( $player_id ),
				'completed'
			)
		);

		return (object) array(
			'total_exams'    => absint( $stats->total_exams ),
			'best_score'     => absint( $stats->best_score ),
			'best_level'     => $best_level ? $best_level : null,
			'last_exam_date' => $stats->last_exam_date,
		);
	}

	/**
	 * Check if a player has a WordPress user account.
	 *
	 * @since 1.0.0
	 *
	 * @param int $player_id The player ID.
	 * @return bool True if the player has a linked WP user, false otherwise.
	 */
	public function has_wp_account( $player_id ) {
		$player = $this->get( $player_id );

		if ( ! $player ) {
			return false;
		}

		return ! empty( $player->user_id );
	}
}
