<?php
/**
 * WFEB Exam Model
 *
 * Handles all database operations for the wfeb_exams table including
 * creation, retrieval, updating, score calculation, and validation.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Exam
 *
 * Model class for the {$wpdb->prefix}wfeb_exams table.
 */
class WFEB_Exam {

	/**
	 * Table name (set in constructor).
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Players table name (set in constructor).
	 *
	 * @var string
	 */
	private $players_table;

	/**
	 * Coaches table name (set in constructor).
	 *
	 * @var string
	 */
	private $coaches_table;

	/**
	 * Constructor.
	 *
	 * Sets table name references.
	 */
	public function __construct() {
		global $wpdb;

		$this->table         = $wpdb->prefix . 'wfeb_exams';
		$this->players_table = $wpdb->prefix . 'wfeb_players';
		$this->coaches_table = $wpdb->prefix . 'wfeb_coaches';
	}

	/**
	 * Create a new exam record.
	 *
	 * Calculates all totals and derived scores server-side via calculate_scores().
	 * Sets status from $data (default 'draft').
	 *
	 * @since  1.0.0
	 * @param  int   $coach_id The coach record ID.
	 * @param  array $data     Exam data including raw scores and metadata.
	 * @return int|WP_Error    The new exam ID on success, or WP_Error on failure.
	 */
	public function create( $coach_id, $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $coach_id ) ) {
			return new WP_Error( 'missing_coach', __( 'Coach ID is required.', 'wfeb' ) );
		}

		if ( empty( $data['player_id'] ) ) {
			return new WP_Error( 'missing_player', __( 'Player ID is required.', 'wfeb' ) );
		}

		if ( empty( $data['exam_date'] ) ) {
			return new WP_Error( 'missing_exam_date', __( 'Exam date is required.', 'wfeb' ) );
		}

		// Validate score inputs.
		$validation = $this->validate_scores( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Calculate all derived scores.
		$scores = $this->calculate_scores( $data );

		$status = isset( $data['status'] ) && in_array( $data['status'], array( 'draft', 'completed' ), true )
			? $data['status']
			: 'draft';

		$now = current_time( 'mysql' );

		$insert_data = array(
			'coach_id'            => absint( $coach_id ),
			'player_id'           => absint( $data['player_id'] ),
			'exam_date'           => sanitize_text_field( $data['exam_date'] ),
			'assistant_examiner'  => isset( $data['assistant_examiner'] ) ? sanitize_text_field( $data['assistant_examiner'] ) : '',
			'short_passing_left'  => $scores['short_passing_left'],
			'short_passing_right' => $scores['short_passing_right'],
			'short_passing_total' => $scores['short_passing_total'],
			'long_passing_left'   => $scores['long_passing_left'],
			'long_passing_right'  => $scores['long_passing_right'],
			'long_passing_total'  => $scores['long_passing_total'],
			'shooting_tl'         => $scores['shooting_tl'],
			'shooting_tr'         => $scores['shooting_tr'],
			'shooting_bl'         => $scores['shooting_bl'],
			'shooting_br'         => $scores['shooting_br'],
			'shooting_total'      => $scores['shooting_total'],
			'sprint_time'         => $scores['sprint_time'],
			'sprint_score'        => $scores['sprint_score'],
			'dribble_time'        => $scores['dribble_time'],
			'dribble_score'       => $scores['dribble_score'],
			'kickups_attempt1'    => $scores['kickups_attempt1'],
			'kickups_attempt2'    => $scores['kickups_attempt2'],
			'kickups_attempt3'    => $scores['kickups_attempt3'],
			'kickups_best'        => $scores['kickups_best'],
			'kickups_score'       => $scores['kickups_score'],
			'volley_left'         => $scores['volley_left'],
			'volley_right'        => $scores['volley_right'],
			'volley_total'        => $scores['volley_total'],
			'total_score'         => $scores['total_score'],
			'achievement_level'   => $scores['achievement_level'],
			'playing_level'       => $scores['playing_level'],
			'notes'               => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
			'status'              => $status,
			'created_at'          => $now,
			'updated_at'          => $now,
		);

		$format = array(
			'%d', // coach_id
			'%d', // player_id
			'%s', // exam_date
			'%s', // assistant_examiner
			'%d', // short_passing_left
			'%d', // short_passing_right
			'%d', // short_passing_total
			'%d', // long_passing_left
			'%d', // long_passing_right
			'%d', // long_passing_total
			'%d', // shooting_tl
			'%d', // shooting_tr
			'%d', // shooting_bl
			'%d', // shooting_br
			'%d', // shooting_total
			'%f', // sprint_time
			'%d', // sprint_score
			'%f', // dribble_time
			'%d', // dribble_score
			'%d', // kickups_attempt1
			'%d', // kickups_attempt2
			'%d', // kickups_attempt3
			'%d', // kickups_best
			'%d', // kickups_score
			'%d', // volley_left
			'%d', // volley_right
			'%d', // volley_total
			'%d', // total_score
			'%s', // achievement_level
			'%s', // playing_level
			'%s', // notes
			'%s', // status
			'%s', // created_at
			'%s', // updated_at
		);

		$result = $wpdb->insert( $this->table, $insert_data, $format );

		if ( false === $result ) {
			wfeb_log( 'WFEB_Exam::create() - DB insert failed: ' . $wpdb->last_error );
			return new WP_Error( 'db_insert_failed', __( 'Failed to create exam record.', 'wfeb' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get an exam by ID with player name and coach name joined.
	 *
	 * @since  1.0.0
	 * @param  int         $exam_id The exam record ID.
	 * @return object|null The exam row as an object, or null if not found.
	 */
	public function get( $exam_id ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		if ( ! $exam_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT e.*, p.full_name AS player_name, c.full_name AS coach_name
				FROM {$this->table} AS e
				LEFT JOIN {$this->players_table} AS p ON e.player_id = p.id
				LEFT JOIN {$this->coaches_table} AS c ON e.coach_id = c.id
				WHERE e.id = %d",
				$exam_id
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Update an exam record (draft only).
	 *
	 * Recalculates all derived scores from raw inputs.
	 *
	 * @since  1.0.0
	 * @param  int          $exam_id The exam record ID.
	 * @param  array        $data    Updated exam data.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update( $exam_id, $data ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		// Fetch existing exam.
		$existing = $this->get( $exam_id );

		if ( ! $existing ) {
			return new WP_Error( 'exam_not_found', __( 'Exam not found.', 'wfeb' ) );
		}

		// Validate score inputs.
		$validation = $this->validate_scores( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Recalculate all derived scores.
		$scores = $this->calculate_scores( $data );

		$update_data = array(
			'exam_date'           => isset( $data['exam_date'] ) ? sanitize_text_field( $data['exam_date'] ) : $existing->exam_date,
			'assistant_examiner'  => isset( $data['assistant_examiner'] ) ? sanitize_text_field( $data['assistant_examiner'] ) : $existing->assistant_examiner,
			'short_passing_left'  => $scores['short_passing_left'],
			'short_passing_right' => $scores['short_passing_right'],
			'short_passing_total' => $scores['short_passing_total'],
			'long_passing_left'   => $scores['long_passing_left'],
			'long_passing_right'  => $scores['long_passing_right'],
			'long_passing_total'  => $scores['long_passing_total'],
			'shooting_tl'         => $scores['shooting_tl'],
			'shooting_tr'         => $scores['shooting_tr'],
			'shooting_bl'         => $scores['shooting_bl'],
			'shooting_br'         => $scores['shooting_br'],
			'shooting_total'      => $scores['shooting_total'],
			'sprint_time'         => $scores['sprint_time'],
			'sprint_score'        => $scores['sprint_score'],
			'dribble_time'        => $scores['dribble_time'],
			'dribble_score'       => $scores['dribble_score'],
			'kickups_attempt1'    => $scores['kickups_attempt1'],
			'kickups_attempt2'    => $scores['kickups_attempt2'],
			'kickups_attempt3'    => $scores['kickups_attempt3'],
			'kickups_best'        => $scores['kickups_best'],
			'kickups_score'       => $scores['kickups_score'],
			'volley_left'         => $scores['volley_left'],
			'volley_right'        => $scores['volley_right'],
			'volley_total'        => $scores['volley_total'],
			'total_score'         => $scores['total_score'],
			'achievement_level'   => $scores['achievement_level'],
			'playing_level'       => $scores['playing_level'],
			'notes'               => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : $existing->notes,
			'updated_at'          => current_time( 'mysql' ),
		);

		// Allow player_id update on draft.
		if ( isset( $data['player_id'] ) ) {
			$update_data['player_id'] = absint( $data['player_id'] );
		}

		$format = array(
			'%s', // exam_date
			'%s', // assistant_examiner
			'%d', // short_passing_left
			'%d', // short_passing_right
			'%d', // short_passing_total
			'%d', // long_passing_left
			'%d', // long_passing_right
			'%d', // long_passing_total
			'%d', // shooting_tl
			'%d', // shooting_tr
			'%d', // shooting_bl
			'%d', // shooting_br
			'%d', // shooting_total
			'%f', // sprint_time
			'%d', // sprint_score
			'%f', // dribble_time
			'%d', // dribble_score
			'%d', // kickups_attempt1
			'%d', // kickups_attempt2
			'%d', // kickups_attempt3
			'%d', // kickups_best
			'%d', // kickups_score
			'%d', // volley_left
			'%d', // volley_right
			'%d', // volley_total
			'%d', // total_score
			'%s', // achievement_level
			'%s', // playing_level
			'%s', // notes
			'%s', // updated_at
		);

		if ( isset( $data['player_id'] ) ) {
			$format[] = '%d'; // player_id
		}

		$result = $wpdb->update(
			$this->table,
			$update_data,
			array( 'id' => $exam_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'WFEB_Exam::update() - DB update failed for exam ' . $exam_id . ': ' . $wpdb->last_error );
			return new WP_Error( 'db_update_failed', __( 'Failed to update exam record.', 'wfeb' ) );
		}

		return true;
	}

	/**
	 * Finalize an exam by setting status to 'completed'.
	 *
	 * Does NOT deduct credit or generate certificate -- that is handled
	 * by the controller/AJAX layer.
	 *
	 * @since  1.0.0
	 * @param  int          $exam_id The exam record ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function complete( $exam_id ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		$existing = $this->get( $exam_id );

		if ( ! $existing ) {
			return new WP_Error( 'exam_not_found', __( 'Exam not found.', 'wfeb' ) );
		}

		if ( 'completed' === $existing->status ) {
			return new WP_Error( 'exam_already_completed', __( 'This exam is already completed.', 'wfeb' ) );
		}

		$result = $wpdb->update(
			$this->table,
			array(
				'status'     => 'completed',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $exam_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'WFEB_Exam::complete() - DB update failed for exam ' . $exam_id . ': ' . $wpdb->last_error );
			return new WP_Error( 'db_update_failed', __( 'Failed to complete exam.', 'wfeb' ) );
		}

		return true;
	}

	/**
	 * Delete a draft exam.
	 *
	 * Only draft exams can be deleted. Completed exams must remain for records.
	 *
	 * @since  1.0.0
	 * @param  int          $exam_id The exam record ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function delete( $exam_id ) {
		global $wpdb;

		$exam_id = absint( $exam_id );

		$existing = $this->get( $exam_id );

		if ( ! $existing ) {
			return new WP_Error( 'exam_not_found', __( 'Exam not found.', 'wfeb' ) );
		}

		$result = $wpdb->delete(
			$this->table,
			array( 'id' => $exam_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			wfeb_log( 'WFEB_Exam::delete() - DB delete failed for exam ' . $exam_id . ': ' . $wpdb->last_error );
			return new WP_Error( 'db_delete_failed', __( 'Failed to delete exam.', 'wfeb' ) );
		}

		return true;
	}

	/**
	 * Calculate all derived scores from raw input data.
	 *
	 * Calculates totals for each category, converts timed/counted events
	 * to scores, sums the overall total, and determines achievement level.
	 *
	 * @since  1.0.0
	 * @param  array $data Raw exam input scores.
	 * @return array All calculated fields ready for database insertion.
	 */
	public function calculate_scores( $data ) {
		// Raw inputs (sanitized to proper types).
		$short_passing_left  = isset( $data['short_passing_left'] ) ? absint( $data['short_passing_left'] ) : 0;
		$short_passing_right = isset( $data['short_passing_right'] ) ? absint( $data['short_passing_right'] ) : 0;
		$long_passing_left   = isset( $data['long_passing_left'] ) ? absint( $data['long_passing_left'] ) : 0;
		$long_passing_right  = isset( $data['long_passing_right'] ) ? absint( $data['long_passing_right'] ) : 0;
		$shooting_tl         = isset( $data['shooting_tl'] ) ? absint( $data['shooting_tl'] ) : 0;
		$shooting_tr         = isset( $data['shooting_tr'] ) ? absint( $data['shooting_tr'] ) : 0;
		$shooting_bl         = isset( $data['shooting_bl'] ) ? absint( $data['shooting_bl'] ) : 0;
		$shooting_br         = isset( $data['shooting_br'] ) ? absint( $data['shooting_br'] ) : 0;
		$sprint_time         = isset( $data['sprint_time'] ) ? floatval( $data['sprint_time'] ) : 0.00;
		$dribble_time        = isset( $data['dribble_time'] ) ? floatval( $data['dribble_time'] ) : 0.00;
		$kickups_attempt1    = isset( $data['kickups_attempt1'] ) ? absint( $data['kickups_attempt1'] ) : 0;
		$kickups_attempt2    = isset( $data['kickups_attempt2'] ) ? absint( $data['kickups_attempt2'] ) : 0;
		$kickups_attempt3    = isset( $data['kickups_attempt3'] ) ? absint( $data['kickups_attempt3'] ) : 0;
		$volley_left         = isset( $data['volley_left'] ) ? absint( $data['volley_left'] ) : 0;
		$volley_right        = isset( $data['volley_right'] ) ? absint( $data['volley_right'] ) : 0;

		// Category totals.
		$short_passing_total = $short_passing_left + $short_passing_right;
		$long_passing_total  = $long_passing_left + $long_passing_right;
		$shooting_total      = $shooting_tl + $shooting_tr + $shooting_bl + $shooting_br;
		$volley_total        = $volley_left + $volley_right;

		// Timed/counted conversions using helper functions.
		$sprint_score  = wfeb_get_sprint_score( $sprint_time );
		$dribble_score = wfeb_get_dribble_score( $dribble_time );

		// Kickups: best of 3 attempts.
		$kickups_best  = max( $kickups_attempt1, $kickups_attempt2, $kickups_attempt3 );
		$kickups_score = wfeb_get_kickup_score( $kickups_best );

		// Total score: sum of all 7 category scores.
		// 1. Short Passing   /10
		// 2. Long Passing    /10
		// 3. Shooting        /20
		// 4. Sprint          /10
		// 5. Dribble         /10
		// 6. Kickups         /10
		// 7. Volley          /10
		// Total              /80
		$total_score = $short_passing_total
			+ $long_passing_total
			+ $shooting_total
			+ $sprint_score
			+ $dribble_score
			+ $kickups_score
			+ $volley_total;

		// Achievement and playing level.
		$level_data = $this->get_achievement_level( $total_score );

		return array(
			'short_passing_left'  => $short_passing_left,
			'short_passing_right' => $short_passing_right,
			'short_passing_total' => $short_passing_total,
			'long_passing_left'   => $long_passing_left,
			'long_passing_right'  => $long_passing_right,
			'long_passing_total'  => $long_passing_total,
			'shooting_tl'         => $shooting_tl,
			'shooting_tr'         => $shooting_tr,
			'shooting_bl'         => $shooting_bl,
			'shooting_br'         => $shooting_br,
			'shooting_total'      => $shooting_total,
			'sprint_time'         => $sprint_time,
			'sprint_score'        => $sprint_score,
			'dribble_time'        => $dribble_time,
			'dribble_score'       => $dribble_score,
			'kickups_attempt1'    => $kickups_attempt1,
			'kickups_attempt2'    => $kickups_attempt2,
			'kickups_attempt3'    => $kickups_attempt3,
			'kickups_best'        => $kickups_best,
			'kickups_score'       => $kickups_score,
			'volley_left'         => $volley_left,
			'volley_right'        => $volley_right,
			'volley_total'        => $volley_total,
			'total_score'         => $total_score,
			'achievement_level'   => $level_data['level'],
			'playing_level'       => $level_data['playing_level'],
		);
	}

	/**
	 * Map a total score to achievement level and playing level.
	 *
	 * Uses the 10-tier system:
	 *   80 = MASTERY / World Class
	 *   70 = DIAMOND / Professional
	 *   60 = GOLD / Semi-Professional
	 *   50 = SILVER / Advanced Amateur
	 *   40 = BRONZE / Amateur
	 *   30 = MERIT+ / Intermediate
	 *   20 = MERIT / Developing
	 *   15 = MERIT- / Foundation Plus
	 *   10 = PASS+ / Foundation
	 *    5 = PASS / Beginner
	 *   <5 = UNGRADED / Ungraded
	 *
	 * @since  1.0.0
	 * @param  int   $total_score The total exam score (0-80).
	 * @return array Associative array with 'level' and 'playing_level' keys.
	 */
	public function get_achievement_level( $total_score ) {
		$total_score = absint( $total_score );

		$levels = array(
			array(
				'min'           => 80,
				'level'         => 'MASTERY',
				'playing_level' => 'World Class',
			),
			array(
				'min'           => 70,
				'level'         => 'DIAMOND',
				'playing_level' => 'Professional',
			),
			array(
				'min'           => 60,
				'level'         => 'GOLD',
				'playing_level' => 'Semi-Professional',
			),
			array(
				'min'           => 50,
				'level'         => 'SILVER',
				'playing_level' => 'Advanced Amateur',
			),
			array(
				'min'           => 40,
				'level'         => 'BRONZE',
				'playing_level' => 'Amateur',
			),
			array(
				'min'           => 30,
				'level'         => 'MERIT+',
				'playing_level' => 'Intermediate',
			),
			array(
				'min'           => 20,
				'level'         => 'MERIT',
				'playing_level' => 'Developing',
			),
			array(
				'min'           => 15,
				'level'         => 'MERIT-',
				'playing_level' => 'Foundation Plus',
			),
			array(
				'min'           => 10,
				'level'         => 'PASS+',
				'playing_level' => 'Foundation',
			),
			array(
				'min'           => 5,
				'level'         => 'PASS',
				'playing_level' => 'Beginner',
			),
		);

		foreach ( $levels as $tier ) {
			if ( $total_score >= $tier['min'] ) {
				return array(
					'level'         => $tier['level'],
					'playing_level' => $tier['playing_level'],
				);
			}
		}

		return array(
			'level'         => 'UNGRADED',
			'playing_level' => 'Ungraded',
		);
	}

	/**
	 * Get exams by coach ID with filtering, search, and pagination.
	 *
	 * Joins with the players table to include player names.
	 *
	 * @since  1.0.0
	 * @param  int   $coach_id The coach record ID.
	 * @param  array $args     Optional. Query arguments:
	 *   - search    (string) Search player name.
	 *   - date_from (string) Start date (Y-m-d).
	 *   - date_to   (string) End date (Y-m-d).
	 *   - status    (string) Filter by status (draft|completed).
	 *   - orderby   (string) Column to order by. Default 'e.exam_date'.
	 *   - order     (string) ASC or DESC. Default 'DESC'.
	 *   - limit     (int)    Number of records. Default 20.
	 *   - offset    (int)    Offset for pagination. Default 0.
	 * @return array Array of exam row objects.
	 */
	public function get_by_coach( $coach_id, $args = array() ) {
		global $wpdb;

		$coach_id = absint( $coach_id );

		$defaults = array(
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'status'    => '',
			'orderby'   => 'e.exam_date',
			'order'     => 'DESC',
			'limit'     => 20,
			'offset'    => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( 'e.coach_id = %d' );
		$values = array( $coach_id );

		// Search by player name.
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'p.full_name LIKE %s';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
		}

		// Date range filters.
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'e.exam_date >= %s';
			$values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'e.exam_date <= %s';
			$values[] = sanitize_text_field( $args['date_to'] );
		}

		// Status filter.
		if ( ! empty( $args['status'] ) && in_array( $args['status'], array( 'draft', 'completed' ), true ) ) {
			$where[]  = 'e.status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );

		// Whitelist orderby columns.
		$allowed_orderby = array(
			'e.exam_date',
			'e.created_at',
			'e.total_score',
			'e.status',
			'p.full_name',
			'e.id',
		);
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'e.exam_date';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, p.full_name AS player_name
				FROM {$this->table} AS e
				LEFT JOIN {$this->players_table} AS p ON e.player_id = p.id
				WHERE {$where_clause}
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				$values
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get exams by player ID with filtering, search, and pagination.
	 *
	 * Joins with the coaches table to include coach names.
	 *
	 * @since  1.0.0
	 * @param  int   $player_id The player record ID.
	 * @param  array $args      Optional. Query arguments:
	 *   - search    (string) Search coach name.
	 *   - date_from (string) Start date (Y-m-d).
	 *   - date_to   (string) End date (Y-m-d).
	 *   - status    (string) Filter by status (draft|completed).
	 *   - orderby   (string) Column to order by. Default 'e.exam_date'.
	 *   - order     (string) ASC or DESC. Default 'DESC'.
	 *   - limit     (int)    Number of records. Default 20.
	 *   - offset    (int)    Offset for pagination. Default 0.
	 * @return array Array of exam row objects.
	 */
	public function get_by_player( $player_id, $args = array() ) {
		global $wpdb;

		$player_id = absint( $player_id );

		$defaults = array(
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'status'    => '',
			'orderby'   => 'e.exam_date',
			'order'     => 'DESC',
			'limit'     => 20,
			'offset'    => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( 'e.player_id = %d' );
		$values = array( $player_id );

		// Search by coach name.
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'c.full_name LIKE %s';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
		}

		// Date range filters.
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'e.exam_date >= %s';
			$values[] = sanitize_text_field( $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'e.exam_date <= %s';
			$values[] = sanitize_text_field( $args['date_to'] );
		}

		// Status filter.
		if ( ! empty( $args['status'] ) && in_array( $args['status'], array( 'draft', 'completed' ), true ) ) {
			$where[]  = 'e.status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );

		// Whitelist orderby columns.
		$allowed_orderby = array(
			'e.exam_date',
			'e.created_at',
			'e.total_score',
			'e.status',
			'c.full_name',
			'e.id',
		);
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'e.exam_date';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, c.full_name AS coach_name
				FROM {$this->table} AS e
				LEFT JOIN {$this->coaches_table} AS c ON e.coach_id = c.id
				WHERE {$where_clause}
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				$values
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get the count of exams with optional filters.
	 *
	 * @since  1.0.0
	 * @param  int|null $coach_id Optional. Filter by coach ID.
	 * @param  string   $status   Optional. Filter by status (draft|completed).
	 * @return int The number of matching exam records.
	 */
	public function get_count( $coach_id = null, $status = '' ) {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( null !== $coach_id ) {
			$where[]  = 'coach_id = %d';
			$values[] = absint( $coach_id );
		}

		if ( ! empty( $status ) && in_array( $status, array( 'draft', 'completed' ), true ) ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}",
					$values
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}" );
		}

		return absint( $count );
	}

	/**
	 * Get recent exams for a coach, joined with player names.
	 *
	 * @since  1.0.0
	 * @param  int $coach_id The coach record ID.
	 * @param  int $limit    Number of records to return. Default 5.
	 * @return array Array of exam row objects.
	 */
	public function get_recent( $coach_id, $limit = 5 ) {
		global $wpdb;

		$coach_id = absint( $coach_id );
		$limit    = absint( $limit );

		if ( ! $limit ) {
			$limit = 5;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, p.full_name AS player_name
				FROM {$this->table} AS e
				LEFT JOIN {$this->players_table} AS p ON e.player_id = p.id
				WHERE e.coach_id = %d
				ORDER BY e.created_at DESC
				LIMIT %d",
				$coach_id,
				$limit
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Validate all score inputs are within allowed bounds.
	 *
	 * Checks:
	 * - Left/right foot scores: 0-5
	 * - Shooting zone scores: 0-5 each
	 * - Sprint and dribble times: positive numbers
	 * - Kickup counts: non-negative integers
	 * - Volley scores: 0-5 each
	 *
	 * @since  1.0.0
	 * @param  array         $data The raw exam input data.
	 * @return true|WP_Error True if all valid, WP_Error with specific field errors otherwise.
	 */
	public function validate_scores( $data ) {
		$errors = array();

		// Short passing: 0-5 each.
		if ( isset( $data['short_passing_left'] ) ) {
			$val = intval( $data['short_passing_left'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Short passing left must be between 0 and 5.', 'wfeb' );
			}
		}

		if ( isset( $data['short_passing_right'] ) ) {
			$val = intval( $data['short_passing_right'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Short passing right must be between 0 and 5.', 'wfeb' );
			}
		}

		// Long passing: 0-5 each.
		if ( isset( $data['long_passing_left'] ) ) {
			$val = intval( $data['long_passing_left'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Long passing left must be between 0 and 5.', 'wfeb' );
			}
		}

		if ( isset( $data['long_passing_right'] ) ) {
			$val = intval( $data['long_passing_right'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Long passing right must be between 0 and 5.', 'wfeb' );
			}
		}

		// Shooting: 0-5 each zone.
		$shooting_fields = array( 'shooting_tl', 'shooting_tr', 'shooting_bl', 'shooting_br' );
		$shooting_labels = array(
			'shooting_tl' => __( 'Shooting top-left', 'wfeb' ),
			'shooting_tr' => __( 'Shooting top-right', 'wfeb' ),
			'shooting_bl' => __( 'Shooting bottom-left', 'wfeb' ),
			'shooting_br' => __( 'Shooting bottom-right', 'wfeb' ),
		);

		foreach ( $shooting_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$val = intval( $data[ $field ] );
				if ( $val < 0 || $val > 5 ) {
					/* translators: %s: shooting zone label */
					$errors[] = sprintf( __( '%s must be between 0 and 5.', 'wfeb' ), $shooting_labels[ $field ] );
				}
			}
		}

		// Sprint time: must be positive.
		if ( isset( $data['sprint_time'] ) ) {
			$val = floatval( $data['sprint_time'] );
			if ( $val < 0 ) {
				$errors[] = __( 'Sprint time must be a positive number.', 'wfeb' );
			}
		}

		// Dribble time: must be positive.
		if ( isset( $data['dribble_time'] ) ) {
			$val = floatval( $data['dribble_time'] );
			if ( $val < 0 ) {
				$errors[] = __( 'Dribble time must be a positive number.', 'wfeb' );
			}
		}

		// Kickup attempts: non-negative integers.
		$kickup_fields = array( 'kickups_attempt1', 'kickups_attempt2', 'kickups_attempt3' );
		$kickup_labels = array(
			'kickups_attempt1' => __( 'Kickups attempt 1', 'wfeb' ),
			'kickups_attempt2' => __( 'Kickups attempt 2', 'wfeb' ),
			'kickups_attempt3' => __( 'Kickups attempt 3', 'wfeb' ),
		);

		foreach ( $kickup_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$val = intval( $data[ $field ] );
				if ( $val < 0 ) {
					/* translators: %s: kickup attempt label */
					$errors[] = sprintf( __( '%s must be a non-negative number.', 'wfeb' ), $kickup_labels[ $field ] );
				}
			}
		}

		// Volley: 0-5 each.
		if ( isset( $data['volley_left'] ) ) {
			$val = intval( $data['volley_left'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Volley left must be between 0 and 5.', 'wfeb' );
			}
		}

		if ( isset( $data['volley_right'] ) ) {
			$val = intval( $data['volley_right'] );
			if ( $val < 0 || $val > 5 ) {
				$errors[] = __( 'Volley right must be between 0 and 5.', 'wfeb' );
			}
		}

		if ( ! empty( $errors ) ) {
			$error = new WP_Error();
			foreach ( $errors as $index => $message ) {
				$error->add( 'validation_error_' . $index, $message );
			}
			return $error;
		}

		return true;
	}
}
