<?php
/**
 * WFEB Plugin Installation
 *
 * Handles plugin activation, deactivation, and version upgrades.
 * Creates database tables, roles, pages, and default options.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WFEB_Install
 *
 * Manages all installation routines for the WFEB plugin.
 */
class WFEB_Install {

    /**
     * Plugin activation callback.
     *
     * Creates database tables, user roles, pages, and sets default options.
     *
     * @since 1.0.0
     * @return void
     */
    public static function activate() {
        self::create_tables();
        self::create_roles();
        self::create_pages();
        self::set_default_options();

        // Update stored version.
        update_option( 'wfeb_version', WFEB_VERSION );

        // Flush rewrite rules so custom page slugs work immediately.
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback.
     *
     * Flushes rewrite rules to clean up.
     *
     * @since 1.0.0
     * @return void
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Compare stored version with current version and re-run activation if mismatched.
     *
     * This handles database migrations and structural updates on plugin upgrades.
     *
     * @since 1.0.0
     * @return void
     */
    public static function check_version() {
        $stored_version = get_option( 'wfeb_version' );

        if ( $stored_version !== WFEB_VERSION ) {
            self::migrate( $stored_version );
            self::activate();
        }
    }

    /**
     * Run version-specific migrations.
     *
     * @since  2.2.0
     * @access private
     * @param  string|false $from_version The version being upgraded from.
     * @return void
     */
    private static function migrate( $from_version ) {
        global $wpdb;

        // 2.2.0: Rename balance_after -> balance in credit_transactions table.
        if ( $from_version && version_compare( $from_version, '2.2.0', '<' ) ) {
            $table = $wpdb->prefix . 'wfeb_credit_transactions';
            $col   = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'balance_after'" );
            if ( ! empty( $col ) ) {
                $wpdb->query( "ALTER TABLE {$table} CHANGE `balance_after` `balance` int(11) NOT NULL DEFAULT 0" );
            }
        }

        // 2.2.3: Add country column to coaches table.
        if ( $from_version && version_compare( $from_version, '2.2.3', '<' ) ) {
            $table = $wpdb->prefix . 'wfeb_coaches';
            $col   = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'country'" );
            if ( empty( $col ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN country varchar(100) NOT NULL DEFAULT 'United Kingdom' AFTER address" );
            }
        }
    }

    /**
     * Create all required database tables using dbDelta.
     *
     * Tables created:
     * - wfeb_coaches          Coach profiles and approval status.
     * - wfeb_players          Players managed by coaches.
     * - wfeb_exams            7-category skills examination records.
     * - wfeb_certificates     Issued certificates with PDF references.
     * - wfeb_credit_transactions  Credit purchase/usage/refund ledger.
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ---------------------------------------------------------------
        // 1. Coaches table
        // ---------------------------------------------------------------
        $table_coaches = $wpdb->prefix . 'wfeb_coaches';
        $sql_coaches   = "CREATE TABLE {$table_coaches} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            full_name varchar(255) NOT NULL,
            dob date NOT NULL,
            address text NOT NULL,
            country varchar(100) NOT NULL DEFAULT 'United Kingdom',
            ngb_number varchar(100) NOT NULL,
            coaching_certificate varchar(500) NOT NULL DEFAULT '',
            profile_picture bigint(20) unsigned DEFAULT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            credits_balance int(11) NOT NULL DEFAULT 0,
            rejection_reason text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_coaches );

        // ---------------------------------------------------------------
        // 2. Players table
        // ---------------------------------------------------------------
        $table_players = $wpdb->prefix . 'wfeb_players';
        $sql_players   = "CREATE TABLE {$table_players} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            coach_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            profile_picture bigint(20) unsigned DEFAULT NULL,
            full_name varchar(255) NOT NULL,
            dob date NOT NULL,
            email varchar(255) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            address text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY coach_id (coach_id),
            KEY user_id (user_id),
            KEY email (email)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_players );

        // ---------------------------------------------------------------
        // 3. Exams table
        // ---------------------------------------------------------------
        $table_exams = $wpdb->prefix . 'wfeb_exams';
        $sql_exams   = "CREATE TABLE {$table_exams} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            coach_id bigint(20) unsigned NOT NULL,
            player_id bigint(20) unsigned NOT NULL,
            exam_date date NOT NULL,
            assistant_examiner varchar(255) NOT NULL DEFAULT '',
            short_passing_left tinyint(3) unsigned NOT NULL DEFAULT 0,
            short_passing_right tinyint(3) unsigned NOT NULL DEFAULT 0,
            short_passing_total tinyint(3) unsigned NOT NULL DEFAULT 0,
            long_passing_left tinyint(3) unsigned NOT NULL DEFAULT 0,
            long_passing_right tinyint(3) unsigned NOT NULL DEFAULT 0,
            long_passing_total tinyint(3) unsigned NOT NULL DEFAULT 0,
            shooting_tl tinyint(3) unsigned NOT NULL DEFAULT 0,
            shooting_tr tinyint(3) unsigned NOT NULL DEFAULT 0,
            shooting_bl tinyint(3) unsigned NOT NULL DEFAULT 0,
            shooting_br tinyint(3) unsigned NOT NULL DEFAULT 0,
            shooting_total tinyint(3) unsigned NOT NULL DEFAULT 0,
            sprint_time decimal(5,2) unsigned DEFAULT NULL,
            sprint_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            dribble_time decimal(5,2) unsigned DEFAULT NULL,
            dribble_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            kickups_attempt1 int(11) unsigned NOT NULL DEFAULT 0,
            kickups_attempt2 int(11) unsigned NOT NULL DEFAULT 0,
            kickups_attempt3 int(11) unsigned NOT NULL DEFAULT 0,
            kickups_best int(11) unsigned NOT NULL DEFAULT 0,
            kickups_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            volley_left tinyint(3) unsigned NOT NULL DEFAULT 0,
            volley_right tinyint(3) unsigned NOT NULL DEFAULT 0,
            volley_total tinyint(3) unsigned NOT NULL DEFAULT 0,
            total_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            achievement_level varchar(20) NOT NULL DEFAULT '',
            playing_level varchar(50) NOT NULL DEFAULT '',
            notes text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY coach_id (coach_id),
            KEY player_id (player_id),
            KEY status (status),
            KEY exam_date (exam_date)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_exams );

        // ---------------------------------------------------------------
        // 4. Certificates table
        // ---------------------------------------------------------------
        $table_certificates = $wpdb->prefix . 'wfeb_certificates';
        $sql_certificates   = "CREATE TABLE {$table_certificates} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exam_id bigint(20) unsigned NOT NULL,
            player_id bigint(20) unsigned NOT NULL,
            coach_id bigint(20) unsigned NOT NULL,
            certificate_number varchar(50) NOT NULL,
            total_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            achievement_level varchar(20) NOT NULL DEFAULT '',
            playing_level varchar(50) NOT NULL DEFAULT '',
            pdf_url varchar(500) NOT NULL DEFAULT '',
            pdf_attachment_id bigint(20) unsigned DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            revoke_reason text DEFAULT NULL,
            issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY certificate_number (certificate_number),
            KEY exam_id (exam_id),
            KEY player_id (player_id),
            KEY coach_id (coach_id),
            KEY status (status)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_certificates );

        // ---------------------------------------------------------------
        // 5. Credit transactions table
        // ---------------------------------------------------------------
        $table_transactions = $wpdb->prefix . 'wfeb_credit_transactions';
        $sql_transactions   = "CREATE TABLE {$table_transactions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            coach_id bigint(20) unsigned NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'purchase',
            amount int(11) NOT NULL DEFAULT 0,
            balance int(11) NOT NULL DEFAULT 0,
            description varchar(500) NOT NULL DEFAULT '',
            order_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY coach_id (coach_id),
            KEY type (type),
            KEY order_id (order_id)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_transactions );
    }

    /**
     * Create custom user roles via the WFEB_Roles class.
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private static function create_roles() {
        if ( class_exists( 'WFEB_Roles' ) ) {
            WFEB_Roles::create_roles();
        }
    }

    /**
     * Create required front-end pages and store their IDs in options.
     *
     * Pages are only created if the stored option does not already reference
     * a published page. This prevents duplicate pages on re-activation.
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private static function create_pages() {
        $pages = array(
            'wfeb_coach_dashboard_page_id' => array(
                'title'   => 'Coach Dashboard',
                'slug'    => 'coach-dashboard',
                'content' => '<!-- WFEB Coach Dashboard - content rendered via template -->',
            ),
            'wfeb_player_dashboard_page_id' => array(
                'title'   => 'Player Dashboard',
                'slug'    => 'player-dashboard',
                'content' => '<!-- WFEB Player Dashboard - content rendered via template -->',
            ),
            'wfeb_coach_login_page_id' => array(
                'title'   => 'Coach Login',
                'slug'    => 'coach-login',
                'content' => '<!-- WFEB Coach Login - content rendered via template -->',
            ),
            'wfeb_coach_registration_page_id' => array(
                'title'   => 'Coach Registration',
                'slug'    => 'coach-registration',
                'content' => '<!-- WFEB Coach Registration - content rendered via template -->',
            ),
            'wfeb_player_login_page_id' => array(
                'title'   => 'Player Login',
                'slug'    => 'player-login',
                'content' => '<!-- WFEB Player Login - content rendered via template -->',
            ),
            'wfeb_forgot_password_page_id' => array(
                'title'   => 'Forgot Password',
                'slug'    => 'forgot-password',
                'content' => '<!-- WFEB Forgot Password - content rendered via template -->',
            ),
            'wfeb_verify_certificate_page_id' => array(
                'title'   => 'Certificate Verification',
                'slug'    => 'verify-certificate',
                'content' => '<!-- WFEB Certificate Verification - content rendered via template -->',
            ),
            'wfeb_credit_checkout_page_id' => array(
                'title'   => 'Credit Checkout',
                'slug'    => 'credit-checkout',
                'content' => '<!-- WFEB Credit Checkout - content rendered via template -->',
            ),
        );

        foreach ( $pages as $option_key => $page_data ) {
            $existing_page_id = get_option( $option_key );

            // Check if the stored page still exists and is published.
            if ( $existing_page_id && get_post_status( $existing_page_id ) === 'publish' ) {
                continue;
            }

            $page_id = wp_insert_post(
                array(
                    'post_title'     => $page_data['title'],
                    'post_name'      => $page_data['slug'],
                    'post_content'   => $page_data['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                    'ping_status'    => 'closed',
                ),
                true
            );

            if ( ! is_wp_error( $page_id ) ) {
                update_option( $option_key, $page_id );
            }
        }
    }

    /**
     * Set default plugin options.
     *
     * Uses add_option so existing values are never overwritten on re-activation.
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private static function set_default_options() {
        add_option( 'wfeb_version', WFEB_VERSION );
        add_option( 'wfeb_cert_prefix', 'WFEB' );
        add_option( 'wfeb_cert_start', 1000 );
        add_option( 'wfeb_coach_approval_mode', 'manual' );
    }
}
