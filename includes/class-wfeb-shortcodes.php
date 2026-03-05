<?php
/**
 * WFEB Shortcodes
 *
 * Registers and renders all WFEB shortcodes for certificate verification,
 * coach login, and player login forms.
 *
 * @package WFEB
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WFEB_Shortcodes
 *
 * Provides [wfeb_verify], [wfeb_coach_login], and [wfeb_player_login] shortcodes.
 */
class WFEB_Shortcodes {

	/**
	 * Constructor.
	 *
	 * Registers all WFEB shortcodes.
	 */
	public function __construct() {
		add_shortcode( 'wfeb_verify', array( $this, 'render_verify' ) );
		add_shortcode( 'wfeb_coach_login', array( $this, 'render_coach_login' ) );
		add_shortcode( 'wfeb_player_login', array( $this, 'render_player_login' ) );
	}

	/**
	 * Enqueue frontend assets for shortcode pages.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		if ( ! wp_style_is( 'wfeb-frontend', 'enqueued' ) ) {
			wp_enqueue_style( 'wfeb-frontend', WFEB_PLUGIN_URL . 'assets/css/frontend.css', array(), WFEB_VERSION );
		}

		if ( ! wp_script_is( 'wfeb-frontend', 'enqueued' ) ) {
			wp_enqueue_script( 'wfeb-frontend', WFEB_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WFEB_VERSION, true );
			wp_localize_script( 'wfeb-frontend', 'wfeb_frontend', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wfeb_frontend_nonce' ),
			) );
		}
	}

	/**
	 * Render the certificate verification shortcode.
	 *
	 * Outputs a search form (Player Name, Certificate Number, Date of Birth)
	 * and a results area. Uses AJAX to perform the certificate lookup.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string Rendered HTML.
	 */
	public function render_verify( $atts ) {
		$this->enqueue_assets();

		// Check if a template file exists, otherwise render inline.
		$template_path = WFEB_PLUGIN_DIR . 'templates/shortcodes/verify-certificate.php';

		ob_start();

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_verify_html();
		}

		return ob_get_clean();
	}

	/**
	 * Render the verify certificate form HTML inline.
	 *
	 * @return void
	 */
	private function render_verify_html() {
		?>
		<div class="wfeb-verify-container">
			<div class="wfeb-verify-form-wrap" style="max-width: 600px; margin: 0 auto;">
				<h2 style="text-align: center; color: #1a2a4a; margin-bottom: 25px;"><?php esc_html_e( 'Verify a WFEB Certificate', 'wfeb' ); ?></h2>
				<p style="text-align: center; color: #666666; margin-bottom: 30px;"><?php esc_html_e( 'Enter the details below to verify the authenticity of a WFEB skills certificate.', 'wfeb' ); ?></p>

				<form id="wfeb-verify-form" class="wfeb-form">
					<?php wp_nonce_field( 'wfeb_frontend_nonce', 'wfeb_verify_nonce' ); ?>

					<div class="wfeb-form-group" style="margin-bottom: 20px;">
						<label for="wfeb-verify-name" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Player Name', 'wfeb' ); ?></label>
						<input type="text" id="wfeb-verify-name" name="player_name" placeholder="<?php esc_attr_e( 'Enter player full name', 'wfeb' ); ?>" required style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
					</div>

					<div class="wfeb-form-group" style="margin-bottom: 20px;">
						<label for="wfeb-verify-cert" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Certificate Number', 'wfeb' ); ?></label>
						<input type="text" id="wfeb-verify-cert" name="certificate_number" placeholder="<?php esc_attr_e( 'e.g. WFEB-2026-00001', 'wfeb' ); ?>" required style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; font-family: monospace; box-sizing: border-box;" />
					</div>

					<div class="wfeb-form-group" style="margin-bottom: 25px;">
						<label for="wfeb-verify-dob" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Date of Birth', 'wfeb' ); ?></label>
						<input type="date" id="wfeb-verify-dob" name="dob" required style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
					</div>

					<div class="wfeb-form-group" style="text-align: center;">
						<button type="submit" class="wfeb-btn wfeb-btn-primary" style="display: inline-block; background-color: #1a2a4a; color: #ffffff; border: none; padding: 12px 40px; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background-color 0.2s;"><?php esc_html_e( 'Verify Certificate', 'wfeb' ); ?></button>
					</div>
				</form>

				<div id="wfeb-verify-loading" style="display: none; text-align: center; margin-top: 20px;">
					<p style="color: #666666;"><?php esc_html_e( 'Searching...', 'wfeb' ); ?></p>
				</div>

				<div id="wfeb-verify-results" style="display: none; margin-top: 30px;"></div>

				<div id="wfeb-verify-error" style="display: none; margin-top: 20px; padding: 15px; background-color: #fff3f3; border-left: 4px solid #e74c3c; border-radius: 0 4px 4px 0;">
					<p style="margin: 0; color: #c0392b;" id="wfeb-verify-error-message"></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the coach login shortcode.
	 *
	 * Outputs a login form for coaches. Redirects to the coach dashboard
	 * if the user is already logged in as a coach.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string Rendered HTML.
	 */
	public function render_coach_login( $atts ) {
		$this->enqueue_assets();

		// If already logged in as a coach, redirect to dashboard.
		if ( is_user_logged_in() && wfeb_is_coach() ) {
			$dashboard_page_id = get_option( 'wfeb_coach_dashboard_page_id' );
			$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/coach-dashboard/' );

			return '<div class="wfeb-login-redirect"><p>' . sprintf(
				/* translators: %s: dashboard URL */
				__( 'You are already logged in. <a href="%s">Go to your Coach Dashboard</a>.', 'wfeb' ),
				esc_url( $dashboard_url )
			) . '</p></div>';
		}

		// Check if a template file exists, otherwise render inline.
		$template_path = WFEB_PLUGIN_DIR . 'templates/shortcodes/coach-login.php';

		ob_start();

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_coach_login_html();
		}

		return ob_get_clean();
	}

	/**
	 * Render the coach login form HTML inline.
	 *
	 * @return void
	 */
	private function render_coach_login_html() {
		$register_page_id    = get_option( 'wfeb_coach_registration_page_id' );
		$register_url        = $register_page_id ? get_permalink( $register_page_id ) : home_url( '/coach-register/' );
		$forgot_password_id  = get_option( 'wfeb_forgot_password_page_id' );
		$forgot_password_url = $forgot_password_id ? get_permalink( $forgot_password_id ) : home_url( '/forgot-password/' );
		?>
		<div class="wfeb-login-container" style="max-width: 450px; margin: 0 auto;">
			<div class="wfeb-login-header" style="text-align: center; margin-bottom: 30px;">
				<h2 style="color: #1a2a4a; margin-bottom: 8px;"><?php esc_html_e( 'Coach Login', 'wfeb' ); ?></h2>
				<p style="color: #666666;"><?php esc_html_e( 'Sign in to your WFEB Coach account.', 'wfeb' ); ?></p>
			</div>

			<div id="wfeb-coach-login-error" style="display: none; padding: 12px 15px; background-color: #fff3f3; border-left: 4px solid #e74c3c; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
				<p style="margin: 0; color: #c0392b;" id="wfeb-coach-login-error-message"></p>
			</div>

			<form id="wfeb-coach-login-form" class="wfeb-form" method="post">
				<?php wp_nonce_field( 'wfeb_frontend_nonce', 'wfeb_login_nonce' ); ?>
				<input type="hidden" name="wfeb_login_role" value="coach" />

				<div class="wfeb-form-group" style="margin-bottom: 20px;">
					<label for="wfeb-coach-email" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Email Address', 'wfeb' ); ?></label>
					<input type="email" id="wfeb-coach-email" name="email" required autocomplete="email" style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
				</div>

				<div class="wfeb-form-group" style="margin-bottom: 15px;">
					<label for="wfeb-coach-password" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Password', 'wfeb' ); ?></label>
					<input type="password" id="wfeb-coach-password" name="password" required autocomplete="current-password" style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
				</div>

				<div class="wfeb-form-group" style="text-align: right; margin-bottom: 25px;">
					<a href="<?php echo esc_url( $forgot_password_url ); ?>" style="font-size: 13px; color: #243b6a; text-decoration: none;"><?php esc_html_e( 'Forgot Password?', 'wfeb' ); ?></a>
				</div>

				<div class="wfeb-form-group" style="text-align: center;">
					<button type="submit" class="wfeb-btn wfeb-btn-primary" style="display: inline-block; width: 100%; background-color: #1a2a4a; color: #ffffff; border: none; padding: 12px 30px; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer;"><?php esc_html_e( 'Sign In', 'wfeb' ); ?></button>
				</div>
			</form>

			<div class="wfeb-login-footer" style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e4e8;">
				<p style="color: #666666; font-size: 14px;">
					<?php esc_html_e( 'Not registered yet?', 'wfeb' ); ?>
					<a href="<?php echo esc_url( $register_url ); ?>" style="color: #243b6a; font-weight: 600; text-decoration: none;"><?php esc_html_e( 'Register as a Coach', 'wfeb' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the player login shortcode.
	 *
	 * Outputs a login form for players. Redirects to the player dashboard
	 * if the user is already logged in as a player.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string Rendered HTML.
	 */
	public function render_player_login( $atts ) {
		$this->enqueue_assets();

		// If already logged in as a player, redirect to dashboard.
		if ( is_user_logged_in() && wfeb_is_player() ) {
			$dashboard_page_id = get_option( 'wfeb_player_dashboard_page_id' );
			$dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/player-dashboard/' );

			return '<div class="wfeb-login-redirect"><p>' . sprintf(
				/* translators: %s: dashboard URL */
				__( 'You are already logged in. <a href="%s">Go to your Player Dashboard</a>.', 'wfeb' ),
				esc_url( $dashboard_url )
			) . '</p></div>';
		}

		// Check if a template file exists, otherwise render inline.
		$template_path = WFEB_PLUGIN_DIR . 'templates/shortcodes/player-login.php';

		ob_start();

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_player_login_html();
		}

		return ob_get_clean();
	}

	/**
	 * Render the player login form HTML inline.
	 *
	 * @return void
	 */
	private function render_player_login_html() {
		$forgot_password_id  = get_option( 'wfeb_forgot_password_page_id' );
		$forgot_password_url = $forgot_password_id ? get_permalink( $forgot_password_id ) : home_url( '/forgot-password/' );
		?>
		<div class="wfeb-login-container" style="max-width: 450px; margin: 0 auto;">
			<div class="wfeb-login-header" style="text-align: center; margin-bottom: 30px;">
				<h2 style="color: #1a2a4a; margin-bottom: 8px;"><?php esc_html_e( 'Player Login', 'wfeb' ); ?></h2>
				<p style="color: #666666;"><?php esc_html_e( 'Sign in to view your exam results and certificates.', 'wfeb' ); ?></p>
			</div>

			<div id="wfeb-player-login-error" style="display: none; padding: 12px 15px; background-color: #fff3f3; border-left: 4px solid #e74c3c; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
				<p style="margin: 0; color: #c0392b;" id="wfeb-player-login-error-message"></p>
			</div>

			<form id="wfeb-player-login-form" class="wfeb-form" method="post">
				<?php wp_nonce_field( 'wfeb_frontend_nonce', 'wfeb_login_nonce' ); ?>
				<input type="hidden" name="wfeb_login_role" value="player" />

				<div class="wfeb-form-group" style="margin-bottom: 20px;">
					<label for="wfeb-player-email" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Email Address', 'wfeb' ); ?></label>
					<input type="email" id="wfeb-player-email" name="email" required autocomplete="email" style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
				</div>

				<div class="wfeb-form-group" style="margin-bottom: 15px;">
					<label for="wfeb-player-password" style="display: block; font-weight: 600; color: #333333; margin-bottom: 6px;"><?php esc_html_e( 'Password', 'wfeb' ); ?></label>
					<input type="password" id="wfeb-player-password" name="password" required autocomplete="current-password" style="width: 100%; padding: 10px 14px; border: 1px solid #d0d4d8; border-radius: 5px; font-size: 15px; box-sizing: border-box;" />
				</div>

				<div class="wfeb-form-group" style="text-align: right; margin-bottom: 25px;">
					<a href="<?php echo esc_url( $forgot_password_url ); ?>" style="font-size: 13px; color: #243b6a; text-decoration: none;"><?php esc_html_e( 'Forgot Password?', 'wfeb' ); ?></a>
				</div>

				<div class="wfeb-form-group" style="text-align: center;">
					<button type="submit" class="wfeb-btn wfeb-btn-primary" style="display: inline-block; width: 100%; background-color: #1a2a4a; color: #ffffff; border: none; padding: 12px 30px; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer;"><?php esc_html_e( 'Sign In', 'wfeb' ); ?></button>
				</div>
			</form>

			<div class="wfeb-login-footer" style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e4e8;">
				<p style="color: #888888; font-size: 13px;"><?php esc_html_e( 'Your player account was created by your coach. If you need login credentials, please contact your coach.', 'wfeb' ); ?></p>
			</div>
		</div>
		<?php
	}
}
