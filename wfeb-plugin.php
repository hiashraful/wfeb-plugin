<?php
/**
 * Plugin Name: WFEB - World Football Examination Board
 * Plugin URI: https://devash.pro/
 * Description: Football skills certification marketplace. Coaches register, purchase certificate credits, conduct 7-category skills exams, and generate certificates for players.
 * Version: 2.4.0
 * Author: Devash
 * Author URI: https://devash.pro
 * Text Domain: wfeb
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WFEB_VERSION', '2.4.0' );
define( 'WFEB_PLUGIN_FILE', __FILE__ );
define( 'WFEB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WFEB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WFEB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main WFEB Plugin Class - Singleton
 */
final class WFEB_Plugin {

    /** @var WFEB_Plugin Single instance */
    private static $instance = null;

    /** @var WFEB_Coach */
    public $coach;

    /** @var WFEB_Player */
    public $player;

    /** @var WFEB_Exam */
    public $exam;

    /** @var WFEB_Certificate */
    public $certificate;

    /** @var WFEB_WooCommerce */
    public $woocommerce;

    /** @var WFEB_Email */
    public $email;

    /** @var WFEB_PDF */
    public $pdf;

    /** @var WFEB_Ajax */
    public $ajax;

    /** @var WFEB_Shortcodes */
    public $shortcodes;

    /** @var WFEB_Roles */
    public $roles;

    /** @var WFEB_Coach_Dashboard */
    public $coach_dashboard;

    /** @var WFEB_Player_Dashboard */
    public $player_dashboard;

    /**
     * Get single instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Include required files
     */
    private function includes() {
        // Helper functions
        require_once WFEB_PLUGIN_DIR . 'includes/wfeb-functions.php';

        // Core classes
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-install.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-roles.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-media.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-coach.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-player.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-exam.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-certificate.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-pdf.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-qr.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-ajax.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-email.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-shortcodes.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-woocommerce.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-coach-dashboard.php';
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-player-dashboard.php';

        // Auto-updater (runs on admin + cron).
        require_once WFEB_PLUGIN_DIR . 'includes/class-wfeb-updater.php';

        // Admin classes
        if ( is_admin() ) {
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-coaches.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-players.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-exams.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-certificates.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-settings.php';
            require_once WFEB_PLUGIN_DIR . 'includes/admin/class-wfeb-admin-analytics.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation / Deactivation
        register_activation_hook( WFEB_PLUGIN_FILE, array( 'WFEB_Install', 'activate' ) );
        register_deactivation_hook( WFEB_PLUGIN_FILE, array( 'WFEB_Install', 'deactivate' ) );

        // Init components after plugins loaded
        add_action( 'plugins_loaded', array( $this, 'init_components' ) );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Dequeue conflicting scripts on dashboard pages (late priority to run after other plugins)
        add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_conflicting_assets' ), 999 );

        // Version check
        add_action( 'admin_init', array( $this, 'check_version' ) );

        // Custom page templates
        add_filter( 'template_include', array( $this, 'load_custom_templates' ) );

        // Tell WooCommerce the credit checkout page IS a checkout page so it
        // initialises payment gateways and the checkout session correctly.
        add_filter( 'woocommerce_is_checkout', array( $this, 'is_wc_checkout' ) );
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        $this->roles            = new WFEB_Roles();
        $this->coach            = new WFEB_Coach();
        $this->player           = new WFEB_Player();
        $this->exam             = new WFEB_Exam();
        $this->certificate      = new WFEB_Certificate();
        $this->pdf              = new WFEB_PDF();
        $this->email            = new WFEB_Email();
        $this->ajax             = new WFEB_Ajax();
        $this->shortcodes       = new WFEB_Shortcodes();
        $this->woocommerce      = new WFEB_WooCommerce();
        $this->coach_dashboard  = new WFEB_Coach_Dashboard();
        $this->player_dashboard = new WFEB_Player_Dashboard();

        if ( is_admin() ) {
            new WFEB_Admin();
            new WFEB_Updater();
        }
    }

    /**
     * Enqueue frontend assets conditionally
     */
    public function enqueue_frontend_assets() {
        // Coach dashboard
        if ( $this->is_coach_dashboard() ) {
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'wfeb-coach-dashboard', WFEB_PLUGIN_URL . 'assets/css/coach-dashboard.css', array( 'dashicons' ), WFEB_VERSION );
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', array(), '4.4.0', true );

            // Cropper.js for image crop modal.
            wp_enqueue_style( 'cropperjs', 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css', array(), '1.6.1' );
            wp_enqueue_script( 'cropperjs', 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js', array(), '1.6.1', true );
            wp_enqueue_script( 'jquery-cropper', 'https://cdn.jsdelivr.net/npm/jquery-cropper@1.0.1/dist/jquery-cropper.min.js', array( 'jquery', 'cropperjs' ), '1.0.1', true );

            // Reusable media upload handler.
            wp_enqueue_script( 'wfeb-media-upload', WFEB_PLUGIN_URL . 'assets/js/wfeb-media-upload.js', array( 'jquery', 'jquery-cropper' ), WFEB_VERSION, true );

            wp_enqueue_script( 'wfeb-coach-dashboard', WFEB_PLUGIN_URL . 'assets/js/coach-dashboard.js', array( 'jquery', 'chart-js' ), WFEB_VERSION, true );
            wp_localize_script( 'wfeb-coach-dashboard', 'wfeb_coach', array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'wfeb_coach_nonce' ),
                'rest_url'        => rest_url( 'wfeb/v1/' ),
                'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£',
            ) );
            return;
        }

        // Player dashboard
        if ( $this->is_player_dashboard() ) {
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'wfeb-player-dashboard', WFEB_PLUGIN_URL . 'assets/css/player-dashboard.css', array( 'dashicons' ), WFEB_VERSION );
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', array(), '4.4.0', true );
            wp_enqueue_script( 'wfeb-player-dashboard', WFEB_PLUGIN_URL . 'assets/js/player-dashboard.js', array( 'jquery', 'chart-js' ), WFEB_VERSION, true );
            wp_localize_script( 'wfeb-player-dashboard', 'wfeb_player', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wfeb_player_nonce' ),
            ) );
            return;
        }

        // Credit checkout page.
        if ( $this->is_credit_checkout() ) {
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style(
                'wfeb-credit-checkout',
                WFEB_PLUGIN_URL . 'assets/css/credit-checkout.css',
                array( 'dashicons' ),
                WFEB_VERSION
            );
            // WooCommerce checkout scripts needed for payment gateways.
            wp_enqueue_script( 'wc-checkout' );
            wp_enqueue_script( 'wc-country-select' );
            do_action( 'woocommerce_checkout_enqueue_scripts' );
            wp_enqueue_script(
                'wfeb-credit-checkout',
                WFEB_PLUGIN_URL . 'assets/js/credit-checkout.js',
                array( 'jquery', 'wc-checkout' ),
                WFEB_VERSION,
                true
            );
            return;
        }

        // Frontend public pages (login, register, verify)
        if ( $this->is_wfeb_public_page() ) {
            wp_enqueue_style( 'wfeb-frontend', WFEB_PLUGIN_URL . 'assets/css/frontend.css', array(), WFEB_VERSION );
            wp_enqueue_script( 'wfeb-frontend', WFEB_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), WFEB_VERSION, true );
            wp_localize_script( 'wfeb-frontend', 'wfeb_frontend', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wfeb_frontend_nonce' ),
            ) );
        }
    }

    /**
     * Isolate standalone dashboards from theme and third-party interference.
     *
     * Since the coach/player dashboards output their own DOCTYPE and do not use
     * the active theme's templates, we dequeue ALL styles and scripts except an
     * explicit allowlist of handles that the dashboards actually need.
     *
     * Runs at priority 999 to execute after all other enqueue hooks.
     */
    public function dequeue_conflicting_assets() {
        if ( ! $this->is_coach_dashboard() && ! $this->is_player_dashboard() && ! $this->is_credit_checkout() ) {
            return;
        }

        // Handles the dashboards are allowed to keep.
        $allowed_styles = array(
            'dashicons',
            'cropperjs',
            'admin-bar',
            'wfeb-credit-checkout',
        );
        $allowed_scripts = array(
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'chart-js',
            'cropperjs',
            'jquery-cropper',
            'admin-bar',
            'wc-checkout',
            'wc-country-select',
            'wc-address-i18n',
        );

        // On the credit checkout page, also allow payment gateway scripts.
        if ( $this->is_credit_checkout() ) {
            $allowed_scripts[] = 'stripe';
            $allowed_scripts[] = 'wc-stripe';
            $allowed_scripts[] = 'wc-stripe-payment-request';
        }

        global $wp_styles;
        if ( $wp_styles ) {
            foreach ( array_values( $wp_styles->queue ) as $handle ) {
                if ( 0 === strpos( $handle, 'wfeb' ) || in_array( $handle, $allowed_styles, true ) ) {
                    continue;
                }
                wp_dequeue_style( $handle );
            }
        }

        global $wp_scripts;
        if ( $wp_scripts ) {
            foreach ( array_values( $wp_scripts->queue ) as $handle ) {
                if ( 0 === strpos( $handle, 'wfeb' ) || in_array( $handle, $allowed_scripts, true ) ) {
                    continue;
                }
                wp_dequeue_script( $handle );
            }
        }
    }

    /**
     * Load custom page templates
     */
    public function load_custom_templates( $template ) {
        if ( is_page() ) {
            $page_id = get_the_ID();
            $coach_dashboard_id  = get_option( 'wfeb_coach_dashboard_page_id' );
            $player_dashboard_id = get_option( 'wfeb_player_dashboard_page_id' );
            $coach_login_id      = get_option( 'wfeb_coach_login_page_id' );
            $coach_register_id   = get_option( 'wfeb_coach_registration_page_id' );
            $player_login_id     = get_option( 'wfeb_player_login_page_id' );
            $forgot_password_id  = get_option( 'wfeb_forgot_password_page_id' );
            $verify_cert_id      = get_option( 'wfeb_verify_certificate_page_id' );
            $credit_checkout_id  = get_option( 'wfeb_credit_checkout_page_id' );

            if ( $page_id == $coach_dashboard_id ) {
                return WFEB_PLUGIN_DIR . 'templates/coach-dashboard-template.php';
            }
            if ( $page_id == $player_dashboard_id ) {
                return WFEB_PLUGIN_DIR . 'templates/player-dashboard-template.php';
            }
            if ( $page_id == $coach_login_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/coach-login.php';
            }
            if ( $page_id == $coach_register_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/coach-registration-v1.php';
            }
            if ( $page_id == $player_login_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/player-login.php';
            }
            if ( $page_id == $forgot_password_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/forgot-password.php';
            }
            if ( $page_id == $verify_cert_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/verify-certificate.php';
            }
            if ( $page_id == $credit_checkout_id ) {
                return WFEB_PLUGIN_DIR . 'templates/pages/credit-checkout.php';
            }
        }
        return $template;
    }

    /**
     * Check if current page is coach dashboard
     */
    public function is_coach_dashboard() {
        return is_page( get_option( 'wfeb_coach_dashboard_page_id' ) );
    }

    /**
     * Check if current page is player dashboard
     */
    public function is_player_dashboard() {
        return is_page( get_option( 'wfeb_player_dashboard_page_id' ) );
    }

    /**
     * Check if current page is a WFEB public page
     */
    public function is_wfeb_public_page() {
        if ( ! is_page() ) {
            return false;
        }
        $page_id = get_the_ID();
        $wfeb_pages = array(
            get_option( 'wfeb_coach_login_page_id' ),
            get_option( 'wfeb_coach_registration_page_id' ),
            get_option( 'wfeb_player_login_page_id' ),
            get_option( 'wfeb_forgot_password_page_id' ),
            get_option( 'wfeb_verify_certificate_page_id' ),
        );
        return in_array( $page_id, $wfeb_pages );
    }

    /**
     * Check if the current page is the credit checkout page.
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_credit_checkout() {
        return is_page( get_option( 'wfeb_credit_checkout_page_id' ) );
    }

    /**
     * Make WooCommerce treat the credit checkout page as a checkout page.
     * This ensures payment gateways and the WC session are initialised.
     *
     * @param  bool $is_checkout
     * @return bool
     */
    public function is_wc_checkout( $is_checkout ) {
        if ( $this->is_credit_checkout() ) {
            return true;
        }
        return $is_checkout;
    }

    /**
     * Check version and run upgrades if needed
     */
    public function check_version() {
        if ( get_option( 'wfeb_version' ) !== WFEB_VERSION ) {
            WFEB_Install::activate();
        }
    }
}

/**
 * Global accessor function
 *
 * @return WFEB_Plugin
 */
function WFEB() {
    return WFEB_Plugin::instance();
}

// Initialize
WFEB();
