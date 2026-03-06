<?php
/**
 * WFEB AJAX Handler
 *
 * Handles ALL AJAX requests for the WFEB plugin.
 * Registers wp_ajax_ and wp_ajax_nopriv_ actions for public, coach,
 * player, and admin operations.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Ajax
 *
 * Central AJAX controller. Each handler verifies nonce, checks capabilities,
 * sanitizes input, calls the appropriate model method, and returns
 * wp_send_json_success() or wp_send_json_error().
 */
class WFEB_Ajax {

	/**
	 * Constructor.
	 *
	 * Registers all wp_ajax_ and wp_ajax_nopriv_ actions.
	 */
	public function __construct() {

		// -----------------------------------------------------------------
		// Public actions (nopriv + priv).
		// -----------------------------------------------------------------
		$public_actions = array(
			'wfeb_coach_register',
			'wfeb_coach_login',
			'wfeb_player_login',
			'wfeb_forgot_password',
			'wfeb_verify_certificate',
		);

		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
		}

		// -----------------------------------------------------------------
		// Coach actions (priv only).
		// -----------------------------------------------------------------
		$coach_actions = array(
			'wfeb_add_player',
			'wfeb_update_player',
			'wfeb_delete_player',
			'wfeb_search_players',
			'wfeb_save_exam',
			'wfeb_delete_exam',
			'wfeb_get_exam',
			'wfeb_update_coach_settings',
			'wfeb_change_password',
			'wfeb_get_dashboard_stats',
			'wfeb_delete_coach_account',
			'wfeb_upload_media',
			'wfeb_upload_cropped_image',
			'wfeb_setup_credit_cart',
		);

		foreach ( $coach_actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}

		// -----------------------------------------------------------------
		// Player actions (priv only).
		// -----------------------------------------------------------------
		$player_actions = array(
			'wfeb_update_player_profile',
			'wfeb_player_change_password',
			'wfeb_get_player_stats',
			'wfeb_upload_player_avatar',
		);

		foreach ( $player_actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}

		// -----------------------------------------------------------------
		// Admin actions (priv only).
		// -----------------------------------------------------------------
		$admin_actions = array(
			'wfeb_approve_coach',
			'wfeb_reject_coach',
			'wfeb_suspend_coach',
			'wfeb_remove_coach',
			'wfeb_revoke_certificate',
			'wfeb_admin_adjust_credits',
			'wfeb_save_settings',
			'wfeb_preview_certificate',
			'wfeb_get_analytics',
			'wfeb_admin_delete_player',
			'wfeb_send_test_email',
			'wfeb_regenerate_certificates',
		);

		foreach ( $admin_actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}
	}

	// =====================================================================
	// PUBLIC HANDLERS (nopriv + priv)
	// =====================================================================

	/**
	 * 1. Coach Registration.
	 *
	 * Validates fields, handles certificate file upload, creates the coach
	 * record, sends welcome/pending email, and returns a redirect URL.
	 *
	 * @return void
	 */
	public function wfeb_coach_register() {
		check_ajax_referer( 'wfeb_frontend_nonce', 'security' );

		// Prevent duplicate submissions with a short-lived transient lock.
		$email_raw = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$lock_key  = 'wfeb_reg_lock_' . md5( $email_raw );

		if ( get_transient( $lock_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Registration already in progress. Please wait.', 'wfeb' ) ) );
		}
		set_transient( $lock_key, 1, 30 );

		// Required text fields.
		$full_name  = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password   = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$dob        = isset( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : '';
		$address    = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
		$country    = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : 'United Kingdom';
		$ngb_number = isset( $_POST['ngb_number'] ) ? sanitize_text_field( wp_unslash( $_POST['ngb_number'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? wfeb_sanitize_phone( wp_unslash( $_POST['phone'] ) ) : '';

		// Validate required fields.
		if ( empty( $full_name ) || empty( $email ) || empty( $password ) || empty( $dob ) || empty( $address ) || empty( $ngb_number ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'All required fields must be filled in.', 'wfeb' ) ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wfeb' ) ) );
		}

		if ( strlen( $password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters long.', 'wfeb' ) ) );
		}

		// Handle coaching certificate file upload.
		$certificate_path = '';

		if ( ! empty( $_FILES['coaching_certificate'] ) && ! empty( $_FILES['coaching_certificate']['name'] ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$upload_overrides = array(
				'test_form' => false,
				'mimes'     => array(
					'pdf'  => 'application/pdf',
					'jpg'  => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png'  => 'image/png',
				),
			);

			$uploaded = wp_handle_upload( $_FILES['coaching_certificate'], $upload_overrides ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( isset( $uploaded['error'] ) ) {
				wfeb_log( 'Coach registration - certificate upload error: ' . $uploaded['error'] );
				wp_send_json_error( array( 'message' => __( 'Certificate upload failed: ', 'wfeb' ) . $uploaded['error'] ) );
			}

			$certificate_path = $uploaded['url'];
		} else {
			wp_send_json_error( array( 'message' => __( 'Coaching certificate file is required.', 'wfeb' ) ) );
		}

		// Prepare registration data.
		$data = array(
			'full_name'            => $full_name,
			'email'                => $email,
			'password'             => $password,
			'dob'                  => $dob,
			'address'              => $address,
			'country'              => $country,
			'ngb_number'           => $ngb_number,
			'phone'                => $phone,
			'coaching_certificate' => $certificate_path,
		);

		// Call the coach model to register.
		$result = WFEB()->coach->register( $data );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Coach registration AJAX failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Send welcome / pending approval email.
		$approval_mode = get_option( 'wfeb_coach_approval_mode', 'manual' );

		if ( 'auto' === $approval_mode ) {
			if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_coach_welcome' ) ) {
				WFEB()->email->send_coach_welcome( $result, $email );
			}
		} else {
			if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_coach_pending' ) ) {
				WFEB()->email->send_coach_pending( $result, $email );
			}
		}

		$redirect_url = ( 'auto' === $approval_mode )
			? home_url( '/coach-login/' )
			: home_url( '/coach-login/?registered=pending' );

		wfeb_log( 'Coach registered via AJAX - coach_id: ' . $result . ', email: ' . $email );

		$login_page_id = get_option( 'wfeb_coach_login_page_id' );
		$login_url     = $login_page_id ? get_permalink( $login_page_id ) : home_url( '/coach-login/' );

		wp_send_json_success( array(
			'message'      => ( 'auto' === $approval_mode )
				? __( 'Registration successful! You can now log in.', 'wfeb' )
				: __( 'Registration successful! Your account is pending approval. You will receive an email once approved.', 'wfeb' ),
			'redirect_url' => $redirect_url,
			'login_url'    => $login_url,
			'auto_approved' => ( 'auto' === $approval_mode ),
		) );
	}

	/**
	 * 2. Coach Login.
	 *
	 * Validates email and password, uses wp_signon(), checks coach status,
	 * and returns a redirect URL to the coach dashboard.
	 *
	 * @return void
	 */
	public function wfeb_coach_login() {
		wfeb_log( 'Coach login AJAX handler called - email: ' . ( isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : 'not set' ) );

		check_ajax_referer( 'wfeb_frontend_nonce', 'security' );

		wfeb_log( 'Coach login nonce verified' );

		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $email ) || empty( $password ) ) {
			wfeb_log( 'Coach login failed - email or password empty' );
			wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'wfeb' ) ) );
		}

		// WordPress uses user_login for signon - coaches use email as username.
		$creds = array(
			'user_login'    => $email,
			'user_password' => $password,
			'remember'      => true,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			wfeb_log( 'Coach login failed for email: ' . $email . ' - ' . $user->get_error_message() );
			wp_send_json_error( array( 'message' => __( 'Invalid email or password.', 'wfeb' ) ) );
		}

		// Verify the user is a coach.
		if ( ! in_array( 'wfeb_coach', (array) $user->roles, true ) ) {
			wp_logout();
			wp_send_json_error( array( 'message' => __( 'This login is for coaches only. Please use the player login page.', 'wfeb' ) ) );
		}

		// Check coach status.
		$coach = WFEB()->coach->get_by_user_id( $user->ID );

		if ( ! $coach ) {
			wp_logout();
			wfeb_log( 'Coach login failed - no coach record for user_id: ' . $user->ID );
			wp_send_json_error( array( 'message' => __( 'Coach account not found. Please contact support.', 'wfeb' ) ) );
		}

		if ( 'pending' === $coach->status ) {
			wp_logout();
			wp_send_json_error( array( 'message' => __( 'Your account is pending approval. You will receive an email once approved.', 'wfeb' ) ) );
		}

		if ( 'rejected' === $coach->status ) {
			wp_logout();
			$reason_text = ! empty( $coach->rejection_reason ) ? ' Reason: ' . $coach->rejection_reason : '';
			wp_send_json_error( array( 'message' => __( 'Your account has been rejected.', 'wfeb' ) . $reason_text ) );
		}

		if ( 'suspended' === $coach->status ) {
			wp_logout();
			wp_send_json_error( array( 'message' => __( 'Your account has been suspended. Please contact support.', 'wfeb' ) ) );
		}

		wfeb_log( 'Coach logged in - user_id: ' . $user->ID . ', coach_id: ' . $coach->id );

		wp_send_json_success( array(
			'message'      => __( 'Login successful. Redirecting...', 'wfeb' ),
			'redirect_url' => home_url( '/coach-dashboard/' ),
		) );
	}

	/**
	 * 3. Player Login.
	 *
	 * Validates email and password, uses wp_signon(), checks for wfeb_player role,
	 * and returns a redirect URL to the player dashboard.
	 *
	 * @return void
	 */
	public function wfeb_player_login() {
		check_ajax_referer( 'wfeb_frontend_nonce', 'security' );

		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'wfeb' ) ) );
		}

		$creds = array(
			'user_login'    => $email,
			'user_password' => $password,
			'remember'      => true,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			wfeb_log( 'Player login failed for email: ' . $email . ' - ' . $user->get_error_message() );
			wp_send_json_error( array( 'message' => __( 'Invalid email or password.', 'wfeb' ) ) );
		}

		// Verify the user has the player role.
		if ( ! in_array( 'wfeb_player', (array) $user->roles, true ) ) {
			wp_logout();
			wp_send_json_error( array( 'message' => __( 'This login is for players only. Please use the coach login page.', 'wfeb' ) ) );
		}

		wfeb_log( 'Player logged in - user_id: ' . $user->ID );

		wp_send_json_success( array(
			'message'      => __( 'Login successful. Redirecting...', 'wfeb' ),
			'redirect_url' => home_url( '/player-dashboard/' ),
		) );
	}

	/**
	 * 4. Forgot Password.
	 *
	 * Validates that the email exists and triggers the WordPress password reset flow.
	 *
	 * @return void
	 */
	public function wfeb_forgot_password() {
		check_ajax_referer( 'wfeb_frontend_nonce', 'security' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wfeb' ) ) );
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			// Return a generic success message to prevent email enumeration.
			wp_send_json_success( array(
				'message' => __( 'If an account with that email exists, a password reset link has been sent.', 'wfeb' ),
			) );
		}

		$result = retrieve_password( $user->user_login );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Forgot password failed for email: ' . $email . ' - ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => __( 'Unable to send password reset email. Please try again later.', 'wfeb' ) ) );
		}

		wfeb_log( 'Password reset email sent to: ' . $email );

		wp_send_json_success( array(
			'message' => __( 'If an account with that email exists, a password reset link has been sent.', 'wfeb' ),
		) );
	}

	/**
	 * 5. Verify Certificate.
	 *
	 * Validates name, certificate number, and DOB, then calls the certificate
	 * model to verify.
	 *
	 * @return void
	 */
	public function wfeb_verify_certificate() {
		check_ajax_referer( 'wfeb_frontend_nonce', 'security' );

		// QR signature-based verification (auto-verify from QR scan).
		$qr_cert = isset( $_POST['qr_cert'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_cert'] ) ) : '';
		$qr_sig  = isset( $_POST['qr_sig'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_sig'] ) ) : '';

		if ( ! empty( $qr_cert ) && ! empty( $qr_sig ) ) {
			$result = WFEB()->certificate->verify_by_signature( $qr_cert, $qr_sig );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array(
				'message' => __( 'Certificate verified successfully.', 'wfeb' ),
				'data'    => $result,
			) );
		}

		// Standard form-based verification.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$cert_number = isset( $_POST['cert_number'] ) ? sanitize_text_field( wp_unslash( $_POST['cert_number'] ) ) : '';
		$dob         = isset( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : '';

		if ( empty( $name ) || empty( $cert_number ) || empty( $dob ) ) {
			wp_send_json_error( array( 'message' => __( 'All fields are required for certificate verification.', 'wfeb' ) ) );
		}

		if ( ! isset( WFEB()->certificate ) || ! method_exists( WFEB()->certificate, 'verify' ) ) {
			wfeb_log( 'Certificate verification failed - certificate model not available' );
			wp_send_json_error( array( 'message' => __( 'Certificate verification is currently unavailable.', 'wfeb' ) ) );
		}

		$result = WFEB()->certificate->verify( $name, $cert_number, $dob );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( empty( $result ) ) {
			wp_send_json_error( array( 'message' => __( 'No certificate found matching the provided details.', 'wfeb' ) ) );
		}

		wfeb_log( 'Certificate verified - cert_number: ' . $cert_number );

		wp_send_json_success( array(
			'message' => __( 'Certificate verified successfully.', 'wfeb' ),
			'data'    => $result,
		) );
	}

	// =====================================================================
	// COACH HANDLERS (priv only, wfeb_coach role)
	// =====================================================================

	/**
	 * 6. Add Player.
	 *
	 * Creates a new player record for the current coach.
	 *
	 * @return void
	 */
	public function wfeb_add_player() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$dob       = isset( $_POST['dob'] ) ? sanitize_text_field( wp_unslash( $_POST['dob'] ) ) : '';

		if ( empty( $full_name ) || empty( $dob ) ) {
			wp_send_json_error( array( 'message' => __( 'Player name and date of birth are required.', 'wfeb' ) ) );
		}

		$data = array(
			'full_name' => $full_name,
			'dob'       => $dob,
			'email'     => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'     => isset( $_POST['phone'] ) ? wfeb_sanitize_phone( wp_unslash( $_POST['phone'] ) ) : '',
			'address'   => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
		);

		if ( ! empty( $_POST['profile_picture'] ) ) {
			$data['profile_picture'] = absint( $_POST['profile_picture'] );
		}

		$result = WFEB()->player->create( $coach_id, $data );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Add player AJAX failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$player_id = $result;

		// Create WP account if requested and email is provided.
		$account_warning = '';
		if ( isset( $_POST['create_account'] ) && '1' === $_POST['create_account'] && ! empty( $data['email'] ) ) {
			$plain_password    = null;
			$wp_account_result = WFEB()->player->create_wp_account( $player_id, $plain_password );

			if ( ! is_wp_error( $wp_account_result ) && $plain_password ) {
				wfeb_log( 'WP account created for player during add - player_id: ' . $player_id . ', user_id: ' . $wp_account_result );

				if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_player_welcome' ) ) {
					WFEB()->email->send_player_welcome( WFEB()->player->get( $player_id ), $plain_password );
				}
			} else if ( is_wp_error( $wp_account_result ) ) {
				wfeb_log( 'WP account creation failed during add player for player_id: ' . $player_id . ' - ' . $wp_account_result->get_error_message() );
				$account_warning = $wp_account_result->get_error_message();
			}
		}

		// Fetch the newly created player to return.
		$player = WFEB()->player->get( $player_id );

		wfeb_log( 'Player added via AJAX - player_id: ' . $player_id . ', coach_id: ' . $coach_id );

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$redirect_url      = $dashboard_page_id
			? add_query_arg( 'section', 'my-players', get_permalink( $dashboard_page_id ) )
			: '';

		$message = __( 'Player added successfully.', 'wfeb' );
		if ( $account_warning ) {
			$message .= ' ' . sprintf( __( 'However, account creation failed: %s', 'wfeb' ), $account_warning );
		}

		wp_send_json_success( array(
			'message'         => $message,
			'data'            => $player,
			'redirect_url'    => $redirect_url,
			'account_warning' => $account_warning,
		) );
	}

	/**
	 * 7. Update Player.
	 *
	 * Validates that the player belongs to the current coach, then updates.
	 *
	 * @return void
	 */
	public function wfeb_update_player() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id  = wfeb_get_coach_id();
		$player_id = isset( $_POST['player_id'] ) ? absint( $_POST['player_id'] ) : 0;

		if ( ! $coach_id || ! $player_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wfeb' ) ) );
		}

		// Verify the player belongs to this coach.
		$player = WFEB()->player->get( $player_id );

		if ( ! $player || absint( $player->coach_id ) !== $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Player not found or does not belong to your account.', 'wfeb' ) ) );
		}

		$data = array();

		if ( isset( $_POST['full_name'] ) ) {
			$data['full_name'] = sanitize_text_field( wp_unslash( $_POST['full_name'] ) );
		}
		if ( isset( $_POST['dob'] ) ) {
			$data['dob'] = sanitize_text_field( wp_unslash( $_POST['dob'] ) );
		}
		if ( isset( $_POST['email'] ) ) {
			$data['email'] = sanitize_email( wp_unslash( $_POST['email'] ) );
		}
		if ( isset( $_POST['phone'] ) ) {
			$data['phone'] = wfeb_sanitize_phone( wp_unslash( $_POST['phone'] ) );
		}
		if ( isset( $_POST['address'] ) ) {
			$data['address'] = sanitize_textarea_field( wp_unslash( $_POST['address'] ) );
		}
		if ( isset( $_POST['profile_picture'] ) ) {
			$data['profile_picture'] = absint( $_POST['profile_picture'] );
		}

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields to update.', 'wfeb' ) ) );
		}

		$result = WFEB()->player->update( $player_id, $data );

		if ( ! $result ) {
			wfeb_log( 'Update player AJAX failed - player_id: ' . $player_id );
			wp_send_json_error( array( 'message' => __( 'Failed to update player.', 'wfeb' ) ) );
		}

		// Create WP account if requested and player doesn't already have one.
		$account_warning = '';
		if ( isset( $_POST['create_account'] ) && '1' === $_POST['create_account'] && ! WFEB()->player->has_wp_account( $player_id ) ) {
			$updated = WFEB()->player->get( $player_id );

			if ( $updated && ! empty( $updated->email ) ) {
				$plain_password    = null;
				$wp_account_result = WFEB()->player->create_wp_account( $player_id, $plain_password );

				if ( ! is_wp_error( $wp_account_result ) && $plain_password ) {
					wfeb_log( 'WP account created for player during update - player_id: ' . $player_id . ', user_id: ' . $wp_account_result );

					if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_player_welcome' ) ) {
						WFEB()->email->send_player_welcome( WFEB()->player->get( $player_id ), $plain_password );
					}
				} else if ( is_wp_error( $wp_account_result ) ) {
					wfeb_log( 'WP account creation failed during update player for player_id: ' . $player_id . ' - ' . $wp_account_result->get_error_message() );
					$account_warning = $wp_account_result->get_error_message();
				}
			}
		}

		// Fetch the updated player data.
		$updated_player = WFEB()->player->get( $player_id );

		wfeb_log( 'Player updated via AJAX - player_id: ' . $player_id . ', coach_id: ' . $coach_id );

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
		$redirect_url      = $dashboard_page_id
			? add_query_arg( 'section', 'my-players', get_permalink( $dashboard_page_id ) )
			: '';

		$message = __( 'Player updated successfully.', 'wfeb' );
		if ( $account_warning ) {
			$message .= ' ' . sprintf( __( 'However, account creation failed: %s', 'wfeb' ), $account_warning );
		}

		wp_send_json_success( array(
			'message'         => $message,
			'data'            => $updated_player,
			'redirect_url'    => $redirect_url,
			'account_warning' => $account_warning,
		) );
	}

	/**
	 * 8. Delete Player.
	 *
	 * Validates that the player belongs to the current coach and has no completed exams.
	 *
	 * @return void
	 */
	public function wfeb_delete_player() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id  = wfeb_get_coach_id();
		$player_id = isset( $_POST['player_id'] ) ? absint( $_POST['player_id'] ) : 0;

		if ( ! $coach_id || ! $player_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wfeb' ) ) );
		}

		// Verify the player belongs to this coach.
		$player = WFEB()->player->get( $player_id );

		if ( ! $player || absint( $player->coach_id ) !== $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Player not found or does not belong to your account.', 'wfeb' ) ) );
		}

		// Check for completed exams.
		$exam_count = WFEB()->exam->get_count( $coach_id, 'completed' );
		$player_exams = WFEB()->exam->get_by_coach( $coach_id, array(
			'status' => 'completed',
			'limit'  => 1,
		) );

		// Check specifically if this player has completed exams.
		global $wpdb;
		$exams_table      = $wpdb->prefix . 'wfeb_exams';
		$completed_exams  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$exams_table} WHERE player_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$player_id,
				'completed'
			)
		);

		if ( $completed_exams > 0 ) {
			wp_send_json_error( array( 'message' => __( 'Cannot delete a player who has completed exams. Their exam records must be preserved.', 'wfeb' ) ) );
		}

		$result = WFEB()->player->delete( $player_id );

		if ( ! $result ) {
			wfeb_log( 'Delete player AJAX failed - player_id: ' . $player_id );
			wp_send_json_error( array( 'message' => __( 'Failed to delete player.', 'wfeb' ) ) );
		}

		wfeb_log( 'Player deleted via AJAX - player_id: ' . $player_id . ', coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Player deleted successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 9. Search Players.
	 *
	 * Searches the current coach's players by query string.
	 *
	 * @return void
	 */
	public function wfeb_search_players() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => __( 'Search query is required.', 'wfeb' ) ) );
		}

		$results = WFEB()->player->search( $coach_id, $query );

		wp_send_json_success( array(
			'message' => __( 'Search completed.', 'wfeb' ),
			'data'    => $results,
		) );
	}

	/**
	 * 10. Save Exam (create or update).
	 *
	 * Validates all score fields and either creates or updates an exam record.
	 * If status is 'completed': checks credits, deducts credit, completes the exam,
	 * generates certificate, creates player WP account if needed, and sends emails.
	 *
	 * @return void
	 */
	public function wfeb_save_exam() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		// Gather exam data from POST.
		$exam_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'draft';

		$exam_data = array(
			'player_id'           => isset( $_POST['player_id'] ) ? absint( $_POST['player_id'] ) : 0,
			'exam_date'           => isset( $_POST['exam_date'] ) ? sanitize_text_field( wp_unslash( $_POST['exam_date'] ) ) : '',
			'assistant_examiner'  => isset( $_POST['assistant_examiner'] ) ? sanitize_text_field( wp_unslash( $_POST['assistant_examiner'] ) ) : '',
			'short_passing_left'  => isset( $_POST['short_passing_left'] ) ? absint( $_POST['short_passing_left'] ) : 0,
			'short_passing_right' => isset( $_POST['short_passing_right'] ) ? absint( $_POST['short_passing_right'] ) : 0,
			'long_passing_left'   => isset( $_POST['long_passing_left'] ) ? absint( $_POST['long_passing_left'] ) : 0,
			'long_passing_right'  => isset( $_POST['long_passing_right'] ) ? absint( $_POST['long_passing_right'] ) : 0,
			'shooting_tl'         => isset( $_POST['shooting_tl'] ) ? absint( $_POST['shooting_tl'] ) : 0,
			'shooting_tr'         => isset( $_POST['shooting_tr'] ) ? absint( $_POST['shooting_tr'] ) : 0,
			'shooting_bl'         => isset( $_POST['shooting_bl'] ) ? absint( $_POST['shooting_bl'] ) : 0,
			'shooting_br'         => isset( $_POST['shooting_br'] ) ? absint( $_POST['shooting_br'] ) : 0,
			'sprint_time'         => isset( $_POST['sprint_time'] ) ? floatval( $_POST['sprint_time'] ) : 0.00,
			'dribble_time'        => isset( $_POST['dribble_time'] ) ? floatval( $_POST['dribble_time'] ) : 0.00,
			'kickups_attempt1'    => isset( $_POST['kickups_attempt1'] ) ? absint( $_POST['kickups_attempt1'] ) : 0,
			'kickups_attempt2'    => isset( $_POST['kickups_attempt2'] ) ? absint( $_POST['kickups_attempt2'] ) : 0,
			'kickups_attempt3'    => isset( $_POST['kickups_attempt3'] ) ? absint( $_POST['kickups_attempt3'] ) : 0,
			'volley_left'         => isset( $_POST['volley_left'] ) ? absint( $_POST['volley_left'] ) : 0,
			'volley_right'        => isset( $_POST['volley_right'] ) ? absint( $_POST['volley_right'] ) : 0,
			'notes'               => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			'status'              => $status,
		);

		// Verify the player belongs to this coach.
		if ( $exam_data['player_id'] ) {
			$player = WFEB()->player->get( $exam_data['player_id'] );

			if ( ! $player || absint( $player->coach_id ) !== $coach_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid player selected.', 'wfeb' ) ) );
			}
		}

		// Check if this is an edit of an already-completed exam.
		$is_editing_completed = false;
		if ( $exam_id > 0 ) {
			$existing = WFEB()->exam->get( $exam_id );

			if ( ! $existing || absint( $existing->coach_id ) !== $coach_id ) {
				wp_send_json_error( array( 'message' => __( 'Exam not found or does not belong to your account.', 'wfeb' ) ) );
			}

			$is_editing_completed = ( 'completed' === $existing->status );
		}

		// Validate required fields for completed exams.
		if ( 'completed' === $status ) {
			if ( empty( $exam_data['assistant_examiner'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Assistant Examiner is required to complete the exam.', 'wfeb' ) ) );
			}
			if ( empty( $exam_data['player_id'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Please select a player before completing the exam.', 'wfeb' ) ) );
			}
		}

		// If completing a NEW or DRAFT exam, check credits first.
		if ( 'completed' === $status && ! $is_editing_completed ) {
			$credits = WFEB()->coach->get_credits( $coach_id );

			if ( $credits <= 0 ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient certificate credits. Please purchase more credits to complete this exam.', 'wfeb' ) ) );
			}
		}

		// Save exam data as draft first; complete() will handle the status transition.
		$save_data = $exam_data;
		if ( 'completed' === $status && ! $is_editing_completed ) {
			$save_data['status'] = 'draft';
		}

		// Create or update the exam.
		if ( $exam_id > 0 ) {
			$result = WFEB()->exam->update( $exam_id, $save_data );
		} else {
			// Creating a new exam.
			$result = WFEB()->exam->create( $coach_id, $save_data );
		}

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Save exam AJAX failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// For new exams, $result is the exam ID. For updates, it is true.
		$current_exam_id = $exam_id > 0 ? $exam_id : $result;

		$cert_number = '';

		// If completing the exam (not editing an already-completed one), handle credit deduction, certificate generation, and emails.
		if ( 'completed' === $status && ! $is_editing_completed ) {
			// Deduct one credit.
			$deduction = WFEB()->coach->deduct_credit( $coach_id );

			if ( is_wp_error( $deduction ) ) {
				wfeb_log( 'Exam completion - credit deduction failed for coach_id: ' . $coach_id . ' - ' . $deduction->get_error_message() );
				wp_send_json_error( array( 'message' => $deduction->get_error_message() ) );
			}

			// Complete the exam (set status to completed).
			$completion = WFEB()->exam->complete( $current_exam_id );

			if ( is_wp_error( $completion ) ) {
				wfeb_log( 'Exam completion failed for exam_id: ' . $current_exam_id . ' - ' . $completion->get_error_message() );
				wp_send_json_error( array( 'message' => $completion->get_error_message() ) );
			}

			// Generate the certificate.
			if ( isset( WFEB()->certificate ) && method_exists( WFEB()->certificate, 'generate' ) ) {
				$cert_result = WFEB()->certificate->generate( $current_exam_id );

				if ( ! is_wp_error( $cert_result ) ) {
					$cert_number = is_object( $cert_result ) && isset( $cert_result->certificate_number )
						? $cert_result->certificate_number
						: ( is_string( $cert_result ) ? $cert_result : '' );
				} else {
					wfeb_log( 'Certificate generation failed for exam_id: ' . $current_exam_id . ' - ' . $cert_result->get_error_message() );
				}
			}

			// If the player has no WP account, create one and send welcome email.
			$player = WFEB()->player->get( $exam_data['player_id'] );

			if ( $player && empty( $player->user_id ) ) {
				$plain_password    = null;
				$wp_account_result = WFEB()->player->create_wp_account( $exam_data['player_id'], $plain_password );

				if ( ! is_wp_error( $wp_account_result ) && $plain_password ) {
					wfeb_log( 'WP account created for player during exam completion - player_id: ' . $exam_data['player_id'] . ', user_id: ' . $wp_account_result );

					// Send player welcome email.
					if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_player_welcome' ) ) {
						$player_obj = WFEB()->player->get( $exam_data['player_id'] );
						WFEB()->email->send_player_welcome( $player_obj, $plain_password );
					}
				} else if ( is_wp_error( $wp_account_result ) ) {
					wfeb_log( 'WP account creation failed during exam completion for player_id: ' . $exam_data['player_id'] . ' - ' . $wp_account_result->get_error_message() );
				}
			}

			// Send certificate email.
			if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_certificate' ) ) {
				WFEB()->email->send_certificate( $current_exam_id, $exam_data['player_id'] );
			}

			wfeb_log( 'Exam completed and certificate generated - exam_id: ' . $current_exam_id . ', cert_number: ' . $cert_number );
		}

		// Fetch the full exam data to return.
		$exam = WFEB()->exam->get( $current_exam_id );

		$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );

		$response_data = array(
			'message' => ( 'completed' === $status )
				? __( 'Exam completed and certificate generated successfully.', 'wfeb' )
				: __( 'Exam saved as draft.', 'wfeb' ),
			'data'    => $exam,
		);

		if ( 'completed' === $status ) {
			$response_data['certificate_number'] = $cert_number;

			if ( $dashboard_page_id ) {
				$response_data['view_url']     = add_query_arg(
					array( 'section' => 'exam-details', 'exam_id' => $current_exam_id ),
					get_permalink( $dashboard_page_id )
				);
			}
		} elseif ( $dashboard_page_id ) {
			$response_data['redirect_url'] = add_query_arg(
				'section', 'exam-history',
				get_permalink( $dashboard_page_id )
			);
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * 11. Delete Exam.
	 *
	 * Validates that the exam is a draft and belongs to the current coach.
	 *
	 * @return void
	 */
	public function wfeb_delete_exam() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();
		$exam_id  = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;

		if ( ! $coach_id || ! $exam_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wfeb' ) ) );
		}

		// Verify the exam belongs to this coach.
		$exam = WFEB()->exam->get( $exam_id );

		if ( ! $exam || absint( $exam->coach_id ) !== $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Exam not found or does not belong to your account.', 'wfeb' ) ) );
		}

		$result = WFEB()->exam->delete( $exam_id );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Delete exam AJAX failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wfeb_log( 'Exam deleted via AJAX - exam_id: ' . $exam_id . ', coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Exam deleted successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 12. Get Exam.
	 *
	 * Retrieves full exam details. Validates that the exam belongs to the current coach.
	 *
	 * @return void
	 */
	public function wfeb_get_exam() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();
		$exam_id  = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;

		if ( ! $coach_id || ! $exam_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wfeb' ) ) );
		}

		$exam = WFEB()->exam->get( $exam_id );

		if ( ! $exam || absint( $exam->coach_id ) !== $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Exam not found or does not belong to your account.', 'wfeb' ) ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Exam data retrieved.', 'wfeb' ),
			'data'    => $exam,
		) );
	}

	/**
	 * 13. Update Coach Settings.
	 *
	 * Updates coach profile fields and handles certificate file re-upload.
	 *
	 * @return void
	 */
	public function wfeb_update_coach_settings() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		$data = array();

		if ( isset( $_POST['full_name'] ) ) {
			$data['full_name'] = sanitize_text_field( wp_unslash( $_POST['full_name'] ) );
		}
		if ( isset( $_POST['phone'] ) ) {
			$data['phone'] = wfeb_sanitize_phone( wp_unslash( $_POST['phone'] ) );
		}
		if ( isset( $_POST['address'] ) ) {
			$data['address'] = sanitize_textarea_field( wp_unslash( $_POST['address'] ) );
		}
		if ( isset( $_POST['country'] ) ) {
			$data['country'] = sanitize_text_field( wp_unslash( $_POST['country'] ) );
		}
		if ( isset( $_POST['ngb_number'] ) ) {
			$data['ngb_number'] = sanitize_text_field( wp_unslash( $_POST['ngb_number'] ) );
		}
		if ( isset( $_POST['dob'] ) ) {
			$data['dob'] = sanitize_text_field( wp_unslash( $_POST['dob'] ) );
		}
		if ( isset( $_POST['profile_picture'] ) ) {
			$data['profile_picture'] = absint( $_POST['profile_picture'] );
		}

		// Handle certificate file re-upload.
		if ( ! empty( $_FILES['coaching_certificate'] ) && ! empty( $_FILES['coaching_certificate']['name'] ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$upload_overrides = array(
				'test_form' => false,
				'mimes'     => array(
					'pdf'  => 'application/pdf',
					'jpg'  => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png'  => 'image/png',
				),
			);

			$uploaded = wp_handle_upload( $_FILES['coaching_certificate'], $upload_overrides ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( isset( $uploaded['error'] ) ) {
				wfeb_log( 'Coach settings - certificate re-upload error: ' . $uploaded['error'] );
				wp_send_json_error( array( 'message' => __( 'Certificate upload failed: ', 'wfeb' ) . $uploaded['error'] ) );
			}

			$data['coaching_certificate'] = $uploaded['url'];
		}

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields to update.', 'wfeb' ) ) );
		}

		$result = WFEB()->coach->update( $coach_id, $data );

		if ( ! $result ) {
			wfeb_log( 'Update coach settings AJAX failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to update settings.', 'wfeb' ) ) );
		}

		// If email was changed on the coach record, sync with WP user.
		if ( isset( $_POST['email'] ) ) {
			$new_email = sanitize_email( wp_unslash( $_POST['email'] ) );

			if ( ! empty( $new_email ) && is_email( $new_email ) ) {
				$coach = WFEB()->coach->get( $coach_id );

				if ( $coach ) {
					wp_update_user( array(
						'ID'         => $coach->user_id,
						'user_email' => $new_email,
					) );

					WFEB()->coach->update( $coach_id, array( 'email' => $new_email ) );
				}
			}
		}

		wfeb_log( 'Coach settings updated via AJAX - coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Settings updated successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 14. Change Password (Coach).
	 *
	 * Validates the current password, checks new password strength,
	 * and updates via wp_set_password().
	 *
	 * @return void
	 */
	public function wfeb_change_password() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';         // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $current_password ) || empty( $new_password ) || empty( $confirm_password ) ) {
			wp_send_json_error( array( 'message' => __( 'All password fields are required.', 'wfeb' ) ) );
		}

		if ( $new_password !== $confirm_password ) {
			wp_send_json_error( array( 'message' => __( 'New password and confirmation do not match.', 'wfeb' ) ) );
		}

		if ( strlen( $new_password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'New password must be at least 8 characters long.', 'wfeb' ) ) );
		}

		$user = wp_get_current_user();

		// Verify the current password.
		if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Current password is incorrect.', 'wfeb' ) ) );
		}

		// Set the new password.
		wp_set_password( $new_password, $user->ID );

		// Re-login the user so the session is not invalidated.
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		wfeb_log( 'Coach password changed via AJAX - user_id: ' . $user->ID );

		wp_send_json_success( array(
			'message' => __( 'Password changed successfully.', 'wfeb' ),
		) );
	}

	/**
	 * AJAX: Set up WooCommerce cart for credit purchase and return checkout URL.
	 *
	 * Clears the cart, adds the credit product at the requested quantity,
	 * then returns the checkout page URL for JS to redirect to.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wfeb_setup_credit_cart() {
		// Nonce verification.
		check_ajax_referer( 'wfeb_buy_credits_nonce', 'wfeb_buy_credits_nonce' );

		// Must be a logged-in approved coach.
		if ( ! is_user_logged_in() || ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'wfeb' ) ) );
		}

		// WooCommerce must be active.
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not available.', 'wfeb' ) ) );
		}

		$product_id   = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );
		$quantity     = absint( isset( $_POST['quantity'] ) ? $_POST['quantity'] : 1 );
		$checkout_url = isset( $_POST['checkout_url'] ) ? esc_url_raw( wp_unslash( $_POST['checkout_url'] ) ) : '';

		if ( ! $product_id || $quantity < 1 || $quantity > 200 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid quantity or product.', 'wfeb' ) ) );
		}

		// Verify product matches the configured credit product.
		$configured_product_id = absint( get_option( 'wfeb_credit_product_id', 0 ) );
		if ( $configured_product_id && $product_id !== $configured_product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wfeb' ) ) );
		}

		// Clear existing cart and add credit product at requested quantity.
		WC()->cart->empty_cart();
		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( ! $cart_item_key ) {
			wp_send_json_error( array( 'message' => __( 'Could not add product to cart. Please try again.', 'wfeb' ) ) );
		}

		$redirect = $checkout_url ? $checkout_url : wc_get_checkout_url();

		wp_send_json_success( array( 'redirect' => $redirect ) );
	}

	/**
	 * 15. Get Dashboard Stats (Coach).
	 *
	 * Returns credits, total_exams, players_count, and month_exams for
	 * the coach overview dashboard.
	 *
	 * @return void
	 */
	public function wfeb_get_dashboard_stats() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		global $wpdb;

		$credits       = WFEB()->coach->get_credits( $coach_id );
		$total_exams   = WFEB()->exam->get_count( $coach_id );
		$players_count = WFEB()->player->get_count( $coach_id );

		// Exams this month.
		$exams_table = $wpdb->prefix . 'wfeb_exams';
		$month_start = gmdate( 'Y-m-01' );
		$month_end   = gmdate( 'Y-m-t' );

		$month_exams = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$exams_table} WHERE coach_id = %d AND exam_date >= %s AND exam_date <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$coach_id,
				$month_start,
				$month_end
			)
		);

		wp_send_json_success( array(
			'message' => __( 'Dashboard stats retrieved.', 'wfeb' ),
			'data'    => array(
				'credits'       => absint( $credits ),
				'total_exams'   => absint( $total_exams ),
				'players_count' => absint( $players_count ),
				'month_exams'   => absint( $month_exams ),
			),
		) );
	}

	/**
	 * 15b. Delete Coach Account.
	 *
	 * Permanently deletes the coach account and associated WordPress user.
	 * Requires explicit 'DELETE' confirmation string.
	 *
	 * @return void
	 */
	public function wfeb_delete_coach_account() {
		check_ajax_referer( 'wfeb_coach_nonce', 'security' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) : '';

		if ( 'DELETE' !== $confirm ) {
			wp_send_json_error( array( 'message' => __( 'Please type DELETE to confirm account deletion.', 'wfeb' ) ) );
		}

		$user_id  = get_current_user_id();
		$coach_id = wfeb_get_coach_id();

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Coach account not found.', 'wfeb' ) ) );
		}

		// Soft-delete the coach record (mark as deleted).
		global $wpdb;
		$table = $wpdb->prefix . 'wfeb_coaches';
		$wpdb->update(
			$table,
			array( 'status' => 'deleted' ),
			array( 'id' => $coach_id ),
			array( '%s' ),
			array( '%d' )
		);

		wfeb_log( 'Coach account deleted via AJAX - coach_id: ' . $coach_id . ', user_id: ' . $user_id );

		// Log out the user.
		wp_logout();

		wp_send_json_success( array(
			'message'      => __( 'Your account has been deleted.', 'wfeb' ),
			'redirect_url' => home_url(),
		) );
	}

	/**
	 * Upload Media (Coach).
	 *
	 * Handles image file upload via WFEB_Media.
	 *
	 * @return void
	 */
	public function wfeb_upload_media() {
		check_ajax_referer( 'wfeb_coach_nonce', 'nonce' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$result = WFEB_Media::handle_upload( 'file' );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Media upload failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wfeb_log( 'Media uploaded - attachment_id: ' . $result['id'] );

		wp_send_json_success( $result );
	}

	/**
	 * Upload Cropped Image (Coach).
	 *
	 * Handles base64 cropped image upload via WFEB_Media.
	 *
	 * @return void
	 */
	public function wfeb_upload_cropped_image() {
		check_ajax_referer( 'wfeb_coach_nonce', 'nonce' );

		if ( ! wfeb_is_coach() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$image_data = isset( $_POST['image_data'] ) ? $_POST['image_data'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$filename   = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : 'cropped-image.jpg';

		if ( empty( $image_data ) ) {
			wp_send_json_error( array( 'message' => __( 'No image data provided.', 'wfeb' ) ) );
		}

		$result = WFEB_Media::handle_cropped_upload( $image_data, $filename );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Cropped image upload failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wfeb_log( 'Cropped image uploaded - attachment_id: ' . $result['id'] );

		wp_send_json_success( $result );
	}

	// =====================================================================
	// PLAYER HANDLERS (priv only, wfeb_player role)
	// =====================================================================

	/**
	 * 16. Update Player Profile.
	 *
	 * Updates editable player fields (email, phone, address) and handles
	 * profile image upload.
	 *
	 * @return void
	 */
	public function wfeb_update_player_profile() {
		check_ajax_referer( 'wfeb_player_nonce', 'security' );

		if ( ! wfeb_is_player() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$user_id   = get_current_user_id();
		$player    = WFEB()->player->get_by_user_id( $user_id );

		if ( ! $player ) {
			wp_send_json_error( array( 'message' => __( 'Player account not found.', 'wfeb' ) ) );
		}

		$data = array();

		if ( isset( $_POST['email'] ) ) {
			$new_email = sanitize_email( wp_unslash( $_POST['email'] ) );

			if ( ! empty( $new_email ) && is_email( $new_email ) ) {
				$data['email'] = $new_email;

				// Also update the WP user email.
				wp_update_user( array(
					'ID'         => $user_id,
					'user_email' => $new_email,
				) );
			}
		}

		if ( isset( $_POST['phone'] ) ) {
			$data['phone'] = wfeb_sanitize_phone( wp_unslash( $_POST['phone'] ) );
		}

		if ( isset( $_POST['address'] ) ) {
			$data['address'] = sanitize_textarea_field( wp_unslash( $_POST['address'] ) );
		}

		// Handle profile image upload.
		if ( ! empty( $_FILES['profile_image'] ) && ! empty( $_FILES['profile_image']['name'] ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$upload_overrides = array(
				'test_form' => false,
				'mimes'     => array(
					'jpg'  => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png'  => 'image/png',
					'gif'  => 'image/gif',
				),
			);

			$uploaded = wp_handle_upload( $_FILES['profile_image'], $upload_overrides ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( isset( $uploaded['error'] ) ) {
				wfeb_log( 'Player profile image upload error: ' . $uploaded['error'] );
				wp_send_json_error( array( 'message' => __( 'Image upload failed: ', 'wfeb' ) . $uploaded['error'] ) );
			}

			// Store the profile image URL as user meta.
			update_user_meta( $user_id, 'wfeb_profile_image', esc_url_raw( $uploaded['url'] ) );
		}

		if ( ! empty( $data ) ) {
			$result = WFEB()->player->update( $player->id, $data );

			if ( ! $result ) {
				wfeb_log( 'Update player profile AJAX failed - player_id: ' . $player->id );
				wp_send_json_error( array( 'message' => __( 'Failed to update profile.', 'wfeb' ) ) );
			}
		}

		wfeb_log( 'Player profile updated via AJAX - player_id: ' . $player->id . ', user_id: ' . $user_id );

		wp_send_json_success( array(
			'message' => __( 'Profile updated successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 17. Player Change Password.
	 *
	 * Validates current password, checks new password strength,
	 * and updates via wp_set_password().
	 *
	 * @return void
	 */
	public function wfeb_player_change_password() {
		check_ajax_referer( 'wfeb_player_nonce', 'security' );

		if ( ! wfeb_is_player() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';         // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( empty( $current_password ) || empty( $new_password ) || empty( $confirm_password ) ) {
			wp_send_json_error( array( 'message' => __( 'All password fields are required.', 'wfeb' ) ) );
		}

		if ( $new_password !== $confirm_password ) {
			wp_send_json_error( array( 'message' => __( 'New password and confirmation do not match.', 'wfeb' ) ) );
		}

		if ( strlen( $new_password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'New password must be at least 8 characters long.', 'wfeb' ) ) );
		}

		$user = wp_get_current_user();

		// Verify the current password.
		if ( ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Current password is incorrect.', 'wfeb' ) ) );
		}

		// Set the new password.
		wp_set_password( $new_password, $user->ID );

		// Re-login the user so the session is not invalidated.
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		wfeb_log( 'Player password changed via AJAX - user_id: ' . $user->ID );

		wp_send_json_success( array(
			'message' => __( 'Password changed successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 18. Get Player Stats.
	 *
	 * Returns total_certificates, best_score, best_level, and total_exams
	 * for the player dashboard.
	 *
	 * @return void
	 */
	public function wfeb_get_player_stats() {
		check_ajax_referer( 'wfeb_player_nonce', 'security' );

		if ( ! wfeb_is_player() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$user_id = get_current_user_id();
		$player  = WFEB()->player->get_by_user_id( $user_id );

		if ( ! $player ) {
			wp_send_json_error( array( 'message' => __( 'Player account not found.', 'wfeb' ) ) );
		}

		// Get exam stats from the player model.
		$exam_stats = WFEB()->player->get_exam_stats( $player->id );

		// Get total certificates count.
		global $wpdb;
		$certs_table        = $wpdb->prefix . 'wfeb_certificates';
		$total_certificates = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$certs_table} WHERE player_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$player->id,
				'active'
			)
		);

		wp_send_json_success( array(
			'message' => __( 'Player stats retrieved.', 'wfeb' ),
			'data'    => array(
				'total_certificates' => absint( $total_certificates ),
				'best_score'         => $exam_stats->best_score,
				'best_level'         => $exam_stats->best_level,
				'total_exams'        => absint( $exam_stats->total_exams ),
			),
		) );
	}

	/**
	 * 18b. Upload Player Avatar.
	 *
	 * Handles player profile avatar upload via AJAX.
	 * Accepts image files, validates type and size, saves to uploads directory,
	 * and updates the player record.
	 *
	 * @return void
	 */
	public function wfeb_upload_player_avatar() {
		check_ajax_referer( 'wfeb_player_nonce', 'security' );

		if ( ! wfeb_is_player() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$player_id = wfeb_get_player_id();

		if ( ! $player_id ) {
			wp_send_json_error( array( 'message' => __( 'Player account not found.', 'wfeb' ) ) );
		}

		if ( empty( $_FILES['avatar'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wfeb' ) ) );
		}

		$file = $_FILES['avatar']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		// Validate file type.
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.', 'wfeb' ) ) );
		}

		// Validate file size (max 2MB).
		if ( $file['size'] > 2 * 1024 * 1024 ) {
			wp_send_json_error( array( 'message' => __( 'File is too large. Maximum size is 2MB.', 'wfeb' ) ) );
		}

		// Use WordPress media handling.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload['error'] ) );
		}

		// Update the player's avatar URL in user meta.
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'wfeb_avatar_url', esc_url_raw( $upload['url'] ) );

		wfeb_log( 'Player avatar uploaded - player_id: ' . $player_id );

		wp_send_json_success( array(
			'message'    => __( 'Avatar updated successfully.', 'wfeb' ),
			'avatar_url' => esc_url( $upload['url'] ),
		) );
	}

	// =====================================================================
	// ADMIN HANDLERS (priv only, manage_options or wfeb_manage_* caps)
	// =====================================================================

	/**
	 * 19. Approve Coach.
	 *
	 * Sets the coach status to approved and sends an approval email.
	 *
	 * @return void
	 */
	public function wfeb_approve_coach() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0;

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coach ID.', 'wfeb' ) ) );
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wp_send_json_error( array( 'message' => __( 'Coach not found.', 'wfeb' ) ) );
		}

		$result = WFEB()->coach->approve( $coach_id );

		if ( ! $result ) {
			wfeb_log( 'Approve coach AJAX failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to approve coach.', 'wfeb' ) ) );
		}

		// Send approval email.
		if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_coach_approved' ) ) {
			WFEB()->email->send_coach_approved( $coach_id, $coach->email );
		}

		wfeb_log( 'Coach approved via admin AJAX - coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Coach approved successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 20. Reject Coach.
	 *
	 * Validates the rejection reason, sets the coach status to rejected,
	 * and sends a rejection email.
	 *
	 * @return void
	 */
	public function wfeb_reject_coach() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0;
		$reason   = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coach ID.', 'wfeb' ) ) );
		}

		if ( empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'A rejection reason is required.', 'wfeb' ) ) );
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wp_send_json_error( array( 'message' => __( 'Coach not found.', 'wfeb' ) ) );
		}

		$result = WFEB()->coach->reject( $coach_id, $reason );

		if ( ! $result ) {
			wfeb_log( 'Reject coach AJAX failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to reject coach.', 'wfeb' ) ) );
		}

		// Send rejection email.
		if ( isset( WFEB()->email ) && method_exists( WFEB()->email, 'send_coach_rejected' ) ) {
			WFEB()->email->send_coach_rejected( $coach_id, $coach->email, $reason );
		}

		wfeb_log( 'Coach rejected via admin AJAX - coach_id: ' . $coach_id . ', reason: ' . $reason );

		wp_send_json_success( array(
			'message' => __( 'Coach rejected successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 21. Suspend Coach.
	 *
	 * Sets the coach status to suspended.
	 *
	 * @return void
	 */
	public function wfeb_suspend_coach() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0;

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coach ID.', 'wfeb' ) ) );
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wp_send_json_error( array( 'message' => __( 'Coach not found.', 'wfeb' ) ) );
		}

		$result = WFEB()->coach->suspend( $coach_id );

		if ( ! $result ) {
			wfeb_log( 'Suspend coach AJAX failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to suspend coach.', 'wfeb' ) ) );
		}

		wfeb_log( 'Coach suspended via admin AJAX - coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Coach suspended successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 21b. Remove Coach (admin).
	 *
	 * Permanently deletes the coach record and the associated WordPress user.
	 *
	 * @return void
	 */
	public function wfeb_remove_coach() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id = isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0;

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coach ID.', 'wfeb' ) ) );
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wp_send_json_error( array( 'message' => __( 'Coach not found.', 'wfeb' ) ) );
		}

		$result = WFEB()->coach->delete( $coach_id );

		if ( ! $result ) {
			wfeb_log( 'Remove coach AJAX failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to remove coach.', 'wfeb' ) ) );
		}

		wfeb_log( 'Coach removed via admin AJAX - coach_id: ' . $coach_id );

		wp_send_json_success( array(
			'message' => __( 'Coach removed successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 22. Revoke Certificate.
	 *
	 * Validates the revocation reason and calls the certificate model to revoke.
	 *
	 * @return void
	 */
	public function wfeb_revoke_certificate() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_certificates' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$certificate_id = isset( $_POST['certificate_id'] ) ? absint( $_POST['certificate_id'] ) : 0;
		$reason         = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( ! $certificate_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid certificate ID.', 'wfeb' ) ) );
		}

		if ( empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'A revocation reason is required.', 'wfeb' ) ) );
		}

		if ( ! isset( WFEB()->certificate ) || ! method_exists( WFEB()->certificate, 'revoke' ) ) {
			wfeb_log( 'Certificate revocation failed - certificate model not available' );
			wp_send_json_error( array( 'message' => __( 'Certificate management is currently unavailable.', 'wfeb' ) ) );
		}

		$result = WFEB()->certificate->revoke( $certificate_id, $reason );

		if ( is_wp_error( $result ) ) {
			wfeb_log( 'Revoke certificate AJAX failed: ' . $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to revoke certificate.', 'wfeb' ) ) );
		}

		wfeb_log( 'Certificate revoked via admin AJAX - certificate_id: ' . $certificate_id . ', reason: ' . $reason );

		wp_send_json_success( array(
			'message' => __( 'Certificate revoked successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 23. Admin Adjust Credits.
	 *
	 * Adds or removes credits for a coach and records a transaction
	 * with type 'admin_adjust'.
	 *
	 * @return void
	 */
	public function wfeb_admin_adjust_credits() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$coach_id   = isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0;
		$amount     = isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : 0;
		$reason     = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( ! $coach_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid coach ID.', 'wfeb' ) ) );
		}

		if ( 0 === $amount ) {
			wp_send_json_error( array( 'message' => __( 'Credit amount cannot be zero.', 'wfeb' ) ) );
		}

		$coach = WFEB()->coach->get( $coach_id );

		if ( ! $coach ) {
			wp_send_json_error( array( 'message' => __( 'Coach not found.', 'wfeb' ) ) );
		}

		global $wpdb;

		$current_balance = WFEB()->coach->get_credits( $coach_id );
		$new_balance     = $current_balance + $amount;

		// Prevent negative balance.
		if ( $new_balance < 0 ) {
			wp_send_json_error( array( 'message' => __( 'This adjustment would result in a negative credit balance.', 'wfeb' ) ) );
		}

		// Update the coach's credit balance.
		$updated = WFEB()->coach->update( $coach_id, array( 'credits_balance' => $new_balance ) );

		if ( ! $updated ) {
			wfeb_log( 'Admin adjust credits failed - coach_id: ' . $coach_id );
			wp_send_json_error( array( 'message' => __( 'Failed to adjust credits.', 'wfeb' ) ) );
		}

		// Record the transaction.
		$transactions_table = $wpdb->prefix . 'wfeb_credit_transactions';

		$description = ! empty( $reason )
			? sprintf(
				/* translators: 1: credit amount, 2: admin reason */
				__( 'Admin adjustment: %1$d credit(s). Reason: %2$s', 'wfeb' ),
				$amount,
				$reason
			)
			: sprintf(
				/* translators: %d: credit amount */
				__( 'Admin adjustment: %d credit(s)', 'wfeb' ),
				$amount
			);

		$wpdb->insert(
			$transactions_table,
			array(
				'coach_id'    => $coach_id,
				'type'        => 'admin_adjust',
				'amount'      => $amount,
				'balance'     => $new_balance,
				'description' => $description,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s' )
		);

		wfeb_log( 'Admin credits adjusted - coach_id: ' . $coach_id . ', amount: ' . $amount . ', new_balance: ' . $new_balance );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: credit amount, 2: new balance */
				__( 'Credits adjusted by %1$d. New balance: %2$d.', 'wfeb' ),
				$amount,
				$new_balance
			),
			'data'    => array(
				'new_balance' => $new_balance,
			),
		) );
	}

	/**
	 * 24. Save Plugin Settings.
	 *
	 * Saves plugin settings for all 5 admin settings tabs.
	 *
	 * @return void
	 */
	public function wfeb_save_settings() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$tab = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : '';

		if ( empty( $tab ) ) {
			wp_send_json_error( array( 'message' => __( 'Settings tab is required.', 'wfeb' ) ) );
		}

		switch ( $tab ) {
			case 'general':
				if ( isset( $_POST['wfeb_coach_approval_mode'] ) ) {
					$mode = sanitize_text_field( wp_unslash( $_POST['wfeb_coach_approval_mode'] ) );
					if ( in_array( $mode, array( 'manual', 'auto' ), true ) ) {
						update_option( 'wfeb_coach_approval_mode', $mode );
					}
				}
				if ( isset( $_POST['wfeb_cert_prefix'] ) ) {
					update_option( 'wfeb_cert_prefix', sanitize_text_field( wp_unslash( $_POST['wfeb_cert_prefix'] ) ) );
				}
				if ( isset( $_POST['wfeb_cert_start'] ) ) {
					update_option( 'wfeb_cert_start', absint( $_POST['wfeb_cert_start'] ) );
				}
				if ( isset( $_POST['wfeb_logo'] ) ) {
					update_option( 'wfeb_logo_url', esc_url_raw( wp_unslash( $_POST['wfeb_logo'] ) ) );
				}
				break;

			case 'certificate':
				if ( isset( $_POST['wfeb_cert_background'] ) ) {
					update_option( 'wfeb_cert_background', esc_url_raw( wp_unslash( $_POST['wfeb_cert_background'] ) ) );
				}
				if ( isset( $_POST['wfeb_cert_authoriser_name'] ) ) {
					update_option( 'wfeb_cert_authoriser_name', sanitize_text_field( wp_unslash( $_POST['wfeb_cert_authoriser_name'] ) ) );
				}
				break;

			case 'email':
				if ( isset( $_POST['wfeb_email_from_name'] ) ) {
					update_option( 'wfeb_email_from_name', sanitize_text_field( wp_unslash( $_POST['wfeb_email_from_name'] ) ) );
				}
				if ( isset( $_POST['wfeb_email_from_address'] ) ) {
					update_option( 'wfeb_email_from_address', sanitize_email( wp_unslash( $_POST['wfeb_email_from_address'] ) ) );
				}
				break;

			case 'woocommerce':
				if ( isset( $_POST['wfeb_credit_product_id'] ) ) {
					update_option( 'wfeb_credit_product_id', absint( $_POST['wfeb_credit_product_id'] ) );
				}
				if ( isset( $_POST['wfeb_credits_per_purchase'] ) ) {
					update_option( 'wfeb_credits_per_purchase', absint( $_POST['wfeb_credits_per_purchase'] ) );
				}
				if ( isset( $_POST['wfeb_credit_price'] ) ) {
					$raw_price = trim( $_POST['wfeb_credit_price'] );
					$new_price = floatval( $raw_price );
					update_option( 'wfeb_credit_price', $new_price );

					// Only sync to WC product when a positive price is explicitly set.
					if ( $raw_price !== '' && $new_price > 0 ) {
						$product_id = absint( get_option( 'wfeb_credit_product_id', 0 ) );
						if ( $product_id && function_exists( 'wc_get_product' ) ) {
							$product = wc_get_product( $product_id );
							if ( $product ) {
								$product->set_regular_price( (string) $new_price );
								$product->set_price( (string) $new_price );
								$product->save();
							}
						}
					}
				}
				if ( isset( $_POST['wfeb_credit_checkout_page_id'] ) ) {
					update_option( 'wfeb_credit_checkout_page_id', absint( $_POST['wfeb_credit_checkout_page_id'] ) );
				}
				break;

			case 'exam':
				if ( isset( $_POST['thresholds'] ) && is_array( $_POST['thresholds'] ) ) {
					$sanitized = array();
					foreach ( $_POST['thresholds'] as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						$sanitized[] = array(
							'level'         => isset( $row['level'] ) ? sanitize_text_field( wp_unslash( $row['level'] ) ) : '',
							'playing_level' => isset( $row['playing_level'] ) ? sanitize_text_field( wp_unslash( $row['playing_level'] ) ) : '',
							'min'           => isset( $row['min'] ) ? absint( $row['min'] ) : 0,
						);
					}
					update_option( 'wfeb_achievement_thresholds', $sanitized );
				}
				break;

			case 'advanced':
				if ( isset( $_POST['wfeb_delete_data_on_uninstall'] ) ) {
					update_option( 'wfeb_delete_data_on_uninstall', sanitize_text_field( wp_unslash( $_POST['wfeb_delete_data_on_uninstall'] ) ) );
				}
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid settings tab.', 'wfeb' ) ) );
				break;
		}

		wfeb_log( 'Plugin settings saved via admin AJAX - tab: ' . $tab );

		wp_send_json_success( array(
			'message' => __( 'Settings saved successfully.', 'wfeb' ),
		) );
	}

	/**
	 * Preview Certificate.
	 *
	 * Generates a sample certificate PDF with placeholder data.
	 *
	 * @return void
	 */
	public function wfeb_preview_certificate() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wfeb' ) ) );
		}

		// Save the form values first so the PDF generator picks them up.
		if ( isset( $_POST['cert_background'] ) ) {
			$bg = esc_url_raw( wp_unslash( $_POST['cert_background'] ) );
			if ( ! empty( $bg ) ) {
				update_option( 'wfeb_cert_background', $bg );
			}
		}
		if ( isset( $_POST['cert_authoriser_name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['cert_authoriser_name'] ) );
			if ( ! empty( $name ) ) {
				update_option( 'wfeb_cert_authoriser_name', $name );
			}
		}

		// Build a placeholder certificate object.
		$sample                       = new \stdClass();
		$sample->certificate_number   = 'WFEB-PREVIEW';
		$sample->player_name          = 'John Smith';
		$sample->player_dob           = '2010-05-15';
		$sample->coach_name           = 'Jane Doe';
		$sample->exam_date            = gmdate( 'Y-m-d' );
		$sample->total_score          = 62;
		$sample->achievement_level    = 'GOLD';
		$sample->playing_level        = 'Semi-Professional';
		$sample->sprint               = 8;
		$sample->dribble              = 9;
		$sample->kickups              = 7;
		$sample->passing_short        = 10;
		$sample->passing_long         = 9;
		$sample->shooting_distance    = 10;
		$sample->shooting_penalty     = 9;

		$result = WFEB()->pdf->generate_certificate( $sample );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'url' => add_query_arg( 'v', time(), $result['url'] ) ) );
	}

	/**
	 * 25. Admin Delete Player.
	 *
	 * Permanently removes a player record and their WP user account.
	 * Only available to administrators via the admin players list.
	 *
	 * @return void
	 */
	public function wfeb_admin_delete_player() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		$player_id = isset( $_POST['player_id'] ) ? absint( $_POST['player_id'] ) : 0;

		if ( ! $player_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid player ID.', 'wfeb' ) ) );
		}

		$player = WFEB()->player->get( $player_id );

		if ( ! $player ) {
			wp_send_json_error( array( 'message' => __( 'Player not found.', 'wfeb' ) ) );
		}

		$result = WFEB()->player->delete( $player_id );

		if ( ! $result ) {
			wfeb_log( 'Admin delete player AJAX failed - player_id: ' . $player_id );
			wp_send_json_error( array( 'message' => __( 'Failed to delete player.', 'wfeb' ) ) );
		}

		wfeb_log( 'Player deleted via admin AJAX - player_id: ' . $player_id );

		wp_send_json_success( array(
			'message' => __( 'Player deleted successfully.', 'wfeb' ),
		) );
	}

	/**
	 * 26. Get Analytics Data.
	 *
	 * Returns analytics data for admin dashboard charts.
	 *
	 * @return void
	 */
	public function wfeb_get_analytics() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wfeb_manage_coaches' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wfeb' ) ) );
		}

		global $wpdb;

		$coaches_table = $wpdb->prefix . 'wfeb_coaches';
		$players_table = $wpdb->prefix . 'wfeb_players';
		$exams_table   = $wpdb->prefix . 'wfeb_exams';
		$certs_table   = $wpdb->prefix . 'wfeb_certificates';

		// Summary stats.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_coaches           = $wpdb->get_var( "SELECT COUNT(*) FROM {$coaches_table}" );
		$pending_coaches         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$coaches_table} WHERE status = %s", 'pending' ) );
		$total_players           = $wpdb->get_var( "SELECT COUNT(*) FROM {$players_table}" );
		$total_exams             = $wpdb->get_var( "SELECT COUNT(*) FROM {$exams_table}" );
		$completed_exams         = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$exams_table} WHERE status = %s", 'completed' ) );
		$total_certificates      = $wpdb->get_var( "SELECT COUNT(*) FROM {$certs_table}" );
		$active_certificates     = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$certs_table} WHERE status = %s", 'active' ) );

		// Monthly exam trend (last 12 months).
		$monthly_exams = $wpdb->get_results(
			"SELECT DATE_FORMAT(exam_date, '%Y-%m') AS month, COUNT(*) AS count
			FROM {$exams_table}
			WHERE exam_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			GROUP BY DATE_FORMAT(exam_date, '%Y-%m')
			ORDER BY month ASC"
		);

		// Achievement level distribution.
		$level_distribution = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT achievement_level, COUNT(*) AS count
				FROM {$exams_table}
				WHERE status = %s AND achievement_level != ''
				GROUP BY achievement_level
				ORDER BY count DESC",
				'completed'
			)
		);

		// Top coaches by exams conducted.
		$top_coaches = $wpdb->get_results(
			"SELECT c.full_name, COUNT(e.id) AS exam_count
			FROM {$coaches_table} AS c
			LEFT JOIN {$exams_table} AS e ON c.id = e.coach_id
			GROUP BY c.id
			ORDER BY exam_count DESC
			LIMIT 10"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_send_json_success( array(
			'message' => __( 'Analytics data retrieved.', 'wfeb' ),
			'data'    => array(
				'summary'            => array(
					'total_coaches'       => absint( $total_coaches ),
					'pending_coaches'     => absint( $pending_coaches ),
					'total_players'       => absint( $total_players ),
					'total_exams'         => absint( $total_exams ),
					'completed_exams'     => absint( $completed_exams ),
					'total_certificates'  => absint( $total_certificates ),
					'active_certificates' => absint( $active_certificates ),
				),
				'monthly_exams'      => $monthly_exams ? $monthly_exams : array(),
				'level_distribution' => $level_distribution ? $level_distribution : array(),
				'top_coaches'        => $top_coaches ? $top_coaches : array(),
			),
		) );
	}

	/**
	 * 27. Send Test Email.
	 *
	 * Sends a test email to the specified address so admins can verify
	 * their mail configuration is working correctly.
	 *
	 * @return void
	 */
	public function wfeb_send_test_email() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wfeb' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'wfeb' ) ) );
		}

		$sent = wp_mail(
			$email,
			__( 'WFEB Test Email', 'wfeb' ),
			__( 'This is a test email from the WFEB plugin.', 'wfeb' )
		);

		if ( $sent ) {
			wfeb_log( 'Test email sent via admin AJAX to: ' . $email );
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully.', 'wfeb' ) ) );
		} else {
			wfeb_log( 'Test email failed via admin AJAX to: ' . $email );
			wp_send_json_error( array( 'message' => __( 'wp_mail() returned false. Check your mail configuration.', 'wfeb' ) ) );
		}
	}

	/**
	 * Regenerate all certificate files with QR codes and score reports.
	 *
	 * @since 2.4.0
	 */
	public function wfeb_regenerate_certificates() {
		check_ajax_referer( 'wfeb_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wfeb' ) ) );
		}

		$count = WFEB()->certificate->regenerate_all_files();

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of certificates */
				__( 'Successfully regenerated %d certificate(s) with QR codes and score reports.', 'wfeb' ),
				$count
			),
		) );
	}
}
