<?php
/**
 * Press This Version Detector
 *
 * Detects which version of Press This is installed and returns capabilities.
 *
 * @package BJGK\Press_This_Extended
 * @since 2.0.0
 */

namespace PressThisExtended;

class Version_Detector {

	/**
	 * Cached version info.
	 *
	 * @var array|null
	 */
	private static $version_info = null;

	/**
	 * Get Press This version information.
	 *
	 * @return array {
	 *     @type bool   $installed     Whether Press This plugin is installed.
	 *     @type string $version       The version string (e.g., '1.1.2', '2.0.0').
	 *     @type int    $major_version The major version number (1 or 2).
	 *     @type array  $capabilities  List of available filter capabilities.
	 * }
	 */
	public static function get_version_info() {
		if ( self::$version_info !== null ) {
			return self::$version_info;
		}

		self::$version_info = array(
			'installed'     => false,
			'version'       => '0.0.0',
			'major_version' => 0,
			'capabilities'  => array(),
		);

		// Check if Press This plugin is active
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Try to find Press This plugin
		$press_this_file = self::find_press_this_plugin();

		if ( ! $press_this_file ) {
			return self::$version_info;
		}

		// Check if the plugin is actually active
		$plugin_basename = plugin_basename( $press_this_file );
		if ( ! is_plugin_active( $plugin_basename ) ) {
			return self::$version_info;
		}

		self::$version_info['installed'] = true;

		// Get plugin data
		$plugin_data = get_plugin_data( $press_this_file, false, false );
		$version     = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.0.0';

		self::$version_info['version'] = $version;

		// Parse major version
		$version_parts                      = explode( '.', $version );
		self::$version_info['major_version'] = intval( $version_parts[0] );

		// Determine capabilities based on version
		self::$version_info['capabilities'] = self::get_capabilities_for_version(
			self::$version_info['major_version']
		);

		return self::$version_info;
	}

	/**
	 * Find the Press This plugin file.
	 *
	 * @return string|false The plugin file path or false if not found.
	 */
	private static function find_press_this_plugin() {
		$possible_paths = array(
			WP_PLUGIN_DIR . '/press-this/press-this-plugin.php',
			WP_PLUGIN_DIR . '/press-this/press-this.php',
		);

		foreach ( $possible_paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Get capabilities for a given major version.
	 *
	 * @param int $major_version The major version number.
	 * @return array List of capability keys.
	 */
	private static function get_capabilities_for_version( $major_version ) {
		// Version 1.x capabilities
		$v1_capabilities = array(
			'media_discovery',
			'text_discovery',
			'suggested_html',
			'redirect_in_parent',
			'save_redirect',
		);

		// Version 2.x adds these capabilities
		$v2_capabilities = array(
			'allowed_blocks',
			'post_type',
			'post_format_override',
			'default_post_format',
			'post_format_suggestion',
			'sideload_allowed_types',
			'sideload_max_size',
			'enable_url_proxy',
			'validate_proxy_url',
			'validate_request_ip',
			'press_this_data',
		);

		if ( $major_version >= 2 ) {
			return array_merge( $v1_capabilities, $v2_capabilities );
		}

		return $v1_capabilities;
	}

	/**
	 * Check if a specific capability is available.
	 *
	 * @param string $capability The capability to check.
	 * @return bool Whether the capability is available.
	 */
	public static function has_capability( $capability ) {
		$info = self::get_version_info();
		return in_array( $capability, $info['capabilities'], true );
	}

	/**
	 * Check if Press This is installed.
	 *
	 * @return bool Whether Press This is installed.
	 */
	public static function is_installed() {
		$info = self::get_version_info();
		return $info['installed'];
	}

	/**
	 * Get the major version number.
	 *
	 * @return int The major version number (0 if not installed).
	 */
	public static function get_major_version() {
		$info = self::get_version_info();
		return $info['major_version'];
	}

	/**
	 * Clear the cached version info (useful for testing).
	 */
	public static function clear_cache() {
		self::$version_info = null;
	}
}
