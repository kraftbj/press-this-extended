<?php
/**
 * Press This Extended
 *
 * @package     BJGK\Press_This_Extended
 * @version     2.0.0
 * @author      Brandon Kraft <public@brandonkraft.com>
 * @copyright   Copyright (c) 2015-2024, Brandon Kraft
 * @link        https://www.brandonkraft.com/press-this-extended/
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Press This Extended
 * Plugin URI:  https://www.brandonkraft.com/press-this-extended/
 * Description: Provides options for extending and modifying the Press This plugin filters. Supports both Press This 1.x and 2.x.
 * Version:     2.0.0
 * Author:      Brandon Kraft
 * Author URI:  https://www.brandonkraft.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: press-this-extended
 * Domain Path: /languages
 * Requires at least: 6.9
 * Requires PHP: 7.4
 */

/*
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PRESS_THIS_EXTENDED_VERSION', '2.0.0' );
define( 'PRESS_THIS_EXTENDED_FILE', __FILE__ );
define( 'PRESS_THIS_EXTENDED_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRESS_THIS_EXTENDED_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * @since 2.0.0
 */
final class Press_This_Extended {

	/**
	 * Plugin instance.
	 *
	 * @var Press_This_Extended|null
	 */
	private static $instance = null;

	/**
	 * Settings handler.
	 *
	 * @var PressThisExtended\Settings
	 */
	private $settings;

	/**
	 * Filters handler.
	 *
	 * @var PressThisExtended\Filters
	 */
	private $filters;

	/**
	 * Get plugin instance.
	 *
	 * @return Press_This_Extended
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once PRESS_THIS_EXTENDED_DIR . 'includes/class-version-detector.php';
		require_once PRESS_THIS_EXTENDED_DIR . 'includes/class-settings.php';
		require_once PRESS_THIS_EXTENDED_DIR . 'includes/class-filters.php';
		require_once PRESS_THIS_EXTENDED_DIR . 'includes/class-legacy-compat.php';
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Load translations
		add_action( 'init', array( $this, 'load_translations' ) );

		// Initialize legacy compatibility (runs early)
		PressThisExtended\Legacy_Compat::init();

		// Initialize settings
		$this->settings = new PressThisExtended\Settings();
		$this->settings->init();

		// Initialize filters
		$this->filters = new PressThisExtended\Filters( $this->settings );
		$this->filters->init();

		// Add plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

		// Add mobile web app meta tags (legacy feature)
		add_action( 'admin_head-press-this.php', array( $this, 'mobile_app_meta' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_translations() {
		$domain = 'press-this-extended';
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );

		load_textdomain(
			$domain,
			trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo'
		);

		load_plugin_textdomain(
			$domain,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=press-this-extended' ),
			__( 'Settings', 'press-this-extended' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add mobile web app meta tags for Press This.
	 *
	 * Makes Press This into a standalone web app on iOS and Chrome for Android.
	 *
	 * @since 1.1.0
	 */
	public function mobile_app_meta() {
		?>
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="mobile-web-app-capable" content="yes">
		<?php
	}

	/**
	 * Get the settings handler.
	 *
	 * @return PressThisExtended\Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get the filters handler.
	 *
	 * @return PressThisExtended\Filters
	 */
	public function get_filters() {
		return $this->filters;
	}
}

/**
 * Get the plugin instance.
 *
 * @return Press_This_Extended
 */
function press_this_extended() {
	return Press_This_Extended::get_instance();
}

// Initialize the plugin
add_action( 'plugins_loaded', 'press_this_extended' );
