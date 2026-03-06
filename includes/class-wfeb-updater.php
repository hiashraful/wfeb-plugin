<?php
/**
 * WFEB GitHub Auto-Updater
 *
 * Checks GitHub releases for new versions and integrates with the
 * WordPress plugin update system. Shows plugin icon on the updates page.
 *
 * @package WFEB
 * @since   2.2.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WFEB_Updater {

	/**
	 * GitHub repository owner/name.
	 *
	 * @var string
	 */
	private $repo = 'hiashraful/wfeb-plugin';

	/**
	 * GitHub API URL for latest release.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.github.com/repos/hiashraful/wfeb-plugin/releases/latest';

	/**
	 * Plugin slug (directory/file).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Cached GitHub release data.
	 *
	 * @var object|null
	 */
	private $github_data = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_slug = WFEB_PLUGIN_BASENAME;

		// Check for updates.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );

		// Plugin info popup (View Details link).
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );

		// Rename extracted folder to match plugin slug after update.
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

		// Inject plugin icons into the update row.
		add_filter( 'plugin_row_meta', array( $this, 'inject_icon_style' ), 10, 2 );
	}

	/**
	 * Fetch the latest release data from GitHub.
	 *
	 * Caches the result for the duration of the request.
	 *
	 * @return object|false Release data or false on failure.
	 */
	private function get_github_release() {
		if ( null !== $this->github_data ) {
			return $this->github_data;
		}

		// Check transient first (cache for 6 hours).
		$transient = get_transient( 'wfeb_github_release' );
		if ( false !== $transient ) {
			$this->github_data = $transient;
			return $this->github_data;
		}

		$response = wp_remote_get( $this->api_url, array(
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
			),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->github_data = false;
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $data->tag_name ) ) {
			$this->github_data = false;
			return false;
		}

		$this->github_data = $data;
		set_transient( 'wfeb_github_release', $data, 6 * HOUR_IN_SECONDS );

		return $this->github_data;
	}

	/**
	 * Get the version string from the latest GitHub release tag.
	 *
	 * Strips a leading "v" if present (e.g. "v2.3.0" -> "2.3.0").
	 *
	 * @return string|false Version string or false.
	 */
	private function get_remote_version() {
		$release = $this->get_github_release();

		if ( ! $release ) {
			return false;
		}

		return ltrim( $release->tag_name, 'v' );
	}

	/**
	 * Get the download URL for the latest release zip.
	 *
	 * Prefers the first uploaded asset (the release zip). Falls back to
	 * the auto-generated zipball if no asset is uploaded.
	 *
	 * @return string|false Download URL or false.
	 */
	private function get_download_url() {
		$release = $this->get_github_release();

		if ( ! $release ) {
			return false;
		}

		// Prefer uploaded .zip asset.
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->browser_download_url ) && substr( $asset->name, -4 ) === '.zip' ) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fallback to GitHub's auto-generated zipball.
		return $release->zipball_url ?? false;
	}

	/**
	 * Get the plugin icon URL.
	 *
	 * @return string
	 */
	private function get_icon_url() {
		return WFEB_PLUGIN_URL . 'assets/images/icon-128x128.png';
	}

	/**
	 * Check GitHub for a newer version and inject into the update transient.
	 *
	 * @param object $transient The update_plugins transient.
	 * @return object Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_version = $this->get_remote_version();
		$download_url   = $this->get_download_url();

		if ( ! $remote_version || ! $download_url ) {
			return $transient;
		}

		if ( version_compare( WFEB_VERSION, $remote_version, '<' ) ) {
			$plugin_data = (object) array(
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $remote_version,
				'url'         => 'https://github.com/' . $this->repo,
				'package'     => $download_url,
				'icons'       => array(
					'1x'      => $this->get_icon_url(),
					'default' => $this->get_icon_url(),
				),
				'tested'      => get_bloginfo( 'version' ),
				'requires'    => '6.0',
				'requires_php' => '7.4',
			);

			$transient->response[ $this->plugin_slug ] = $plugin_data;
		} else {
			// No update available — add to no_update so WP knows we checked.
			$transient->no_update[ $this->plugin_slug ] = (object) array(
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => WFEB_VERSION,
				'url'         => 'https://github.com/' . $this->repo,
				'icons'       => array(
					'1x'      => $this->get_icon_url(),
					'default' => $this->get_icon_url(),
				),
			);
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the "View Details" popup.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( dirname( $this->plugin_slug ) !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = $this->get_github_release();

		if ( ! $release ) {
			return $result;
		}

		$remote_version = $this->get_remote_version();

		$info                = new stdClass();
		$info->name          = 'WFEB - World Football Examination Board';
		$info->slug          = dirname( $this->plugin_slug );
		$info->version       = $remote_version;
		$info->author        = '<a href="https://devash.pro">Devash</a>';
		$info->homepage      = 'https://github.com/' . $this->repo;
		$info->requires      = '6.0';
		$info->requires_php  = '7.4';
		$info->tested        = get_bloginfo( 'version' );
		$info->download_link = $this->get_download_url();
		$info->trunk         = $this->get_download_url();
		$info->last_updated  = $release->published_at ?? '';

		// Convert GitHub markdown body to HTML for the changelog.
		$info->sections = array(
			'description' => 'Football skills certification marketplace. Coaches register, purchase certificate credits, conduct 7-category skills exams, and generate certificates for players.',
			'changelog'   => ! empty( $release->body ) ? wp_kses_post( nl2br( esc_html( $release->body ) ) ) : 'See the <a href="https://github.com/' . esc_attr( $this->repo ) . '/releases" target="_blank">GitHub releases page</a> for details.',
		);

		$info->icons = array(
			'1x'      => $this->get_icon_url(),
			'default' => $this->get_icon_url(),
		);

		// Banners (optional, using icon as fallback).
		$info->banners = array(
			'low'  => $this->get_icon_url(),
			'high' => $this->get_icon_url(),
		);

		return $info;
	}

	/**
	 * Rename the extracted directory after a GitHub update.
	 *
	 * GitHub zipballs extract to "owner-repo-hash/" which doesn't match
	 * the expected plugin directory name. This renames it.
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra data from the upgrader.
	 * @param array $result     Installation result.
	 * @return array Modified result.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		// Only act on this plugin.
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $result;
		}

		global $wp_filesystem;

		$plugin_dir_name    = dirname( $this->plugin_slug );
		$proper_destination = WP_PLUGIN_DIR . '/' . $plugin_dir_name;

		// If the source is already in the right place, skip the move.
		$source = rtrim( $result['destination'], '/' );
		if ( $source === rtrim( $proper_destination, '/' ) ) {
			return $result;
		}

		// Remove existing plugin directory so move() succeeds.
		if ( $wp_filesystem->exists( $proper_destination ) ) {
			$wp_filesystem->delete( $proper_destination, true );
		}

		$wp_filesystem->move( $source, $proper_destination );
		$result['destination'] = $proper_destination;

		// Re-activate if it was active.
		if ( is_plugin_active( $this->plugin_slug ) ) {
			activate_plugin( $this->plugin_slug );
		}

		return $result;
	}

	/**
	 * Inject icon style — no-op kept for hook registration.
	 *
	 * Icons are provided via the transient 'icons' key which WordPress
	 * renders automatically on the updates page.
	 *
	 * @param array  $plugin_meta Plugin meta links.
	 * @param string $plugin_file Plugin file path.
	 * @return array
	 */
	public function inject_icon_style( $plugin_meta, $plugin_file ) {
		return $plugin_meta;
	}

	/**
	 * Clear the cached GitHub release data.
	 *
	 * Call this when you want to force a fresh check (e.g. after a manual update).
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( 'wfeb_github_release' );
	}
}
