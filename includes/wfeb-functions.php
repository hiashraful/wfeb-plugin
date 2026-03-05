<?php
/**
 * WFEB Helper Functions
 *
 * Utility functions used throughout the WFEB plugin for logging,
 * role checking, score calculations, and data formatting.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log a message to the WFEB debug log file.
 *
 * Writes timestamped messages to the WFEB debug log.
 * Creates the log directory if it does not exist.
 *
 * @param string $message The message to log.
 * @return void
 */
function wfeb_log( $message ) {
	$log_dir  = WP_CONTENT_DIR . '/uploads/wfeb-logs';
	$log_file = $log_dir . '/wfeb-debug.log';

	if ( ! file_exists( $log_dir ) ) {
		wp_mkdir_p( $log_dir );
	}

	$timestamp = current_time( 'Y-m-d H:i:s' );
	$entry     = '[' . $timestamp . '] ' . $message . PHP_EOL;

	file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

/**
 * Check if a user has the wfeb_coach role.
 *
 * @param int|null $user_id The user ID to check. Defaults to the current user.
 * @return bool True if the user is a WFEB Coach.
 */
function wfeb_is_coach( $user_id = null ) {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return false;
	}

	return in_array( 'wfeb_coach', (array) $user->roles, true );
}

/**
 * Check if a user has the wfeb_player role.
 *
 * @param int|null $user_id The user ID to check. Defaults to the current user.
 * @return bool True if the user is a WFEB Player.
 */
function wfeb_is_player( $user_id = null ) {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return false;
	}

	return in_array( 'wfeb_player', (array) $user->roles, true );
}

/**
 * Get the coach record ID from the wfeb_coaches table for a given user.
 *
 * @param int|null $user_id The WordPress user ID. Defaults to the current user.
 * @return int|null The coach record ID, or null if not found.
 */
function wfeb_get_coach_id( $user_id = null ) {
	global $wpdb;

	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return null;
	}

	$table_name = $wpdb->prefix . 'wfeb_coaches';

	$coach_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE user_id = %d",
			$user_id
		)
	);

	return $coach_id ? absint( $coach_id ) : null;
}

/**
 * Get the player record ID from the wfeb_players table for a given user.
 *
 * @param int|null $user_id The WordPress user ID. Defaults to the current user.
 * @return int|null The player record ID, or null if not found.
 */
function wfeb_get_player_id( $user_id = null ) {
	global $wpdb;

	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return null;
	}

	$table_name = $wpdb->prefix . 'wfeb_players';

	$player_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE user_id = %d",
			$user_id
		)
	);

	return $player_id ? absint( $player_id ) : null;
}

/**
 * Get the achievement level and playing level based on a total score.
 *
 * Uses a 10-tier system mapping scores to achievement levels.
 *
 * @param int|float $total_score The total exam score.
 * @return array Associative array with 'level' and 'playing_level' keys.
 */
function wfeb_get_achievement_level( $total_score ) {
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
		'level'         => 'UNCLASSIFIED',
		'playing_level' => 'Unclassified',
	);
}

/**
 * Convert a sprint time (seconds) to a score out of 10.
 *
 * Lower times yield higher scores.
 *
 * @param float $time The sprint time in seconds.
 * @return int The score from 0 to 10.
 */
function wfeb_get_sprint_score( $time ) {
	$time = floatval( $time );

	if ( $time < 5.5 ) {
		return 10;
	} elseif ( $time < 6.0 ) {
		return 9;
	} elseif ( $time < 6.5 ) {
		return 8;
	} elseif ( $time < 7.0 ) {
		return 7;
	} elseif ( $time < 7.5 ) {
		return 6;
	} elseif ( $time < 8.0 ) {
		return 5;
	} elseif ( $time < 8.5 ) {
		return 4;
	} elseif ( $time < 9.0 ) {
		return 3;
	} elseif ( $time < 9.5 ) {
		return 2;
	} elseif ( $time < 10.0 ) {
		return 1;
	}

	return 0;
}

/**
 * Convert a dribble time (seconds) to a score out of 10.
 *
 * Lower times yield higher scores.
 *
 * @param float $time The dribble time in seconds.
 * @return int The score from 0 to 10.
 */
function wfeb_get_dribble_score( $time ) {
	$time = floatval( $time );

	if ( $time < 4.0 ) {
		return 10;
	} elseif ( $time < 4.5 ) {
		return 9;
	} elseif ( $time < 5.0 ) {
		return 8;
	} elseif ( $time < 5.5 ) {
		return 7;
	} elseif ( $time < 6.0 ) {
		return 6;
	} elseif ( $time < 6.5 ) {
		return 5;
	} elseif ( $time < 7.0 ) {
		return 4;
	} elseif ( $time < 7.5 ) {
		return 3;
	} elseif ( $time < 8.0 ) {
		return 2;
	} elseif ( $time < 8.5 ) {
		return 1;
	}

	return 0;
}

/**
 * Convert a kickup count to a score out of 10.
 *
 * Higher counts yield higher scores.
 *
 * @param int $count The number of kickups performed.
 * @return int The score from 0 to 10.
 */
function wfeb_get_kickup_score( $count ) {
	$count = absint( $count );

	if ( $count >= 100 ) {
		return 10;
	} elseif ( $count >= 90 ) {
		return 9;
	} elseif ( $count >= 75 ) {
		return 8;
	} elseif ( $count >= 60 ) {
		return 7;
	} elseif ( $count >= 45 ) {
		return 6;
	} elseif ( $count >= 30 ) {
		return 5;
	} elseif ( $count >= 15 ) {
		return 4;
	} elseif ( $count >= 10 ) {
		return 3;
	} elseif ( $count >= 5 ) {
		return 2;
	} elseif ( $count >= 3 ) {
		return 1;
	}

	return 0;
}

/**
 * Format a date string into the specified format.
 *
 * @param string $date   The date string to format.
 * @param string $format The desired date format. Default 'j M Y'.
 * @return string The formatted date string, or empty string on failure.
 */
function wfeb_format_date( $date, $format = 'j M Y' ) {
	if ( empty( $date ) ) {
		return '';
	}

	$timestamp = strtotime( $date );

	if ( false === $timestamp ) {
		return '';
	}

	return date_i18n( $format, $timestamp );
}

/**
 * Get the hex color code for an achievement level.
 *
 * @param string $level The achievement level name.
 * @return string The hex color code.
 */
function wfeb_get_level_color( $level ) {
	$colors = array(
		'MASTERY'      => '#FF0000',
		'DIAMOND'      => '#38BDF8',
		'GOLD'         => '#FFD700',
		'SILVER'       => '#C0C0C0',
		'BRONZE'       => '#CD7F32',
		'MERIT+'       => '#4CAF50',
		'MERIT'        => '#66BB6A',
		'MERIT-'       => '#81C784',
		'PASS+'        => '#2196F3',
		'PASS'         => '#42A5F5',
		'UNCLASSIFIED' => '#9E9E9E',
	);

	$level = strtoupper( $level );

	return isset( $colors[ $level ] ) ? $colors[ $level ] : '#9E9E9E';
}

/**
 * Sanitize a phone number string.
 *
 * Removes all characters except digits, plus sign, hyphens, spaces, and parentheses.
 *
 * @param string $phone The raw phone number input.
 * @return string The sanitized phone number.
 */
function wfeb_sanitize_phone( $phone ) {
	return preg_replace( '/[^0-9+\-\s()]/', '', sanitize_text_field( $phone ) );
}

/**
 * Generate a secure 12-character password.
 *
 * @return string The generated password.
 */
function wfeb_generate_password() {
	return wp_generate_password( 12, true, false );
}

/**
 * Get a list of countries.
 *
 * Used in coach registration and settings forms.
 *
 * @since  2.2.3
 * @return array Associative array of country names (value => label).
 */
function wfeb_get_countries() {
	return array(
		'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola',
		'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria',
		'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados',
		'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan',
		'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei',
		'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia',
		'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile',
		'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica',
		'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Democratic Republic of the Congo',
		'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'East Timor',
		'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea',
		'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland',
		'France', 'Gabon', 'Gambia', 'Georgia', 'Germany',
		'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea',
		'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary',
		'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq',
		'Ireland', 'Israel', 'Italy', 'Ivory Coast', 'Jamaica',
		'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati',
		'Kosovo', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia',
		'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein',
		'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia',
		'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania',
		'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco',
		'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar',
		'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand',
		'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia',
		'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestine',
		'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines',
		'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia',
		'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa',
		'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia',
		'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia',
		'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan',
		'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden',
		'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania',
		'Thailand', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia',
		'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine',
		'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
		'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen',
		'Zambia', 'Zimbabwe',
	);
}
