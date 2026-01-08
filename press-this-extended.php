<?php
/**
 * Press This Extended
 *
 * @package     BJGK\Press_this_extended
 * @version     2.0.0
 * @author      Brandon Kraft <public@brandonkraft.com>
 * @copyright   Copyright (c) 2015-2026, Brandon Kraft
 * @link        https://kraft.blog/press-this-extended/
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Press This Extended
 * Plugin URI:  https://kraft.blog/press-this-extended/
 * Description: Provides options for extending and modifying the Press This canonical plugin.
 * Version:     2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Brandon Kraft
 * Author URI:  https://kraft.blog
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: press-this-extended
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 * @since 2.0.0 Modernized with PHP 7.4+ features and React settings page.
 */
class Press_This_Extended {

	private const OPTION_PREFIX = 'press-this-extended-';
	private const TEXT_DOMAIN   = 'press-this-extended';
	private const REST_NAMESPACE = 'press-this-extended/v1';

	/**
	 * Default settings values.
	 */
	private array $defaults = [
		'media'        => true,
		'text'         => true,
		'blockquote'   => '<blockquote>%1$s</blockquote>',
		'citation'     => '',
		'parent'       => false,
		'save_publish' => 'permalink',
		'save_draft'   => 'pt',
	];

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Reorganized for REST API and React settings.
	 */
	public function __construct() {
		// Set the default citation with translation.
		$this->defaults['citation'] = '<p>' . _x( 'Source:', 'Used in Press This to indicate where the content comes from.' ) . ' <em><a href="%1$s">%2$s</a></em></p>';

		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'admin_init', [ $this, 'execute_filters' ] );
		add_action( 'admin_init', [ $this, 'legacy_conversion' ] );
		add_action( 'admin_head-press-this.php', [ $this, 'mobile_app_meta' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'action_links' ] );

		// AJAX context hooks.
		if ( wp_doing_ajax() ) {
			add_action( 'admin_init', [ $this, 'execute_ajax_filters' ] );
		}
	}

	/**
	 * Add admin menu page for settings.
	 *
	 * @since 2.0.0
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'Press This Extended', self::TEXT_DOMAIN ),
			__( 'Press This', self::TEXT_DOMAIN ),
			'manage_options',
			'press-this-extended',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Render the settings page container for React.
	 *
	 * @since 2.0.0
	 */
	public function render_settings_page(): void {
		echo '<div id="press-this-extended-settings" class="wrap"></div>';
	}

	/**
	 * Enqueue admin assets for the settings page.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_press-this-extended' !== $hook ) {
			return;
		}

		$asset_file = plugin_dir_path( __FILE__ ) . 'build/scripts/settings/index.min.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'pte-settings',
			plugins_url( 'build/scripts/settings/index.min.js', __FILE__ ),
			$asset['dependencies'] ?? [],
			$asset['version'] ?? '2.0.0',
			true
		);

		wp_enqueue_style(
			'pte-settings',
			plugins_url( 'build/styles/settings/style.css', __FILE__ ),
			[ 'wp-components' ],
			$asset['version'] ?? '2.0.0'
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.0.0
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => fn() => current_user_can( 'manage_options' ),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => fn() => current_user_can( 'manage_options' ),
					'args'                => $this->get_settings_args(),
				],
			]
		);
	}

	/**
	 * Get settings schema for REST API validation.
	 *
	 * @since 2.0.0
	 */
	private function get_settings_args(): array {
		return [
			'media'        => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'text'         => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'blockquote'   => [
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			],
			'citation'     => [
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			],
			'parent'       => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'save_publish' => [
				'type'              => 'string',
				'enum'              => [ 'permalink', 'editor' ],
				'sanitize_callback' => [ $this, 'sanitize_redirect_option' ],
			],
			'save_draft'   => [
				'type'              => 'string',
				'enum'              => [ 'pt', 'editor' ],
				'sanitize_callback' => [ $this, 'sanitize_redirect_option' ],
			],
		];
	}

	/**
	 * Get all settings via REST API.
	 *
	 * @since 2.0.0
	 */
	public function get_settings(): \WP_REST_Response {
		$settings = [];

		foreach ( array_keys( $this->defaults ) as $key ) {
			$option_name = self::OPTION_PREFIX . str_replace( '_', '-', $key );
			$default     = $this->defaults[ $key ];
			$value       = get_option( $option_name, $default );

			// Handle boolean conversion for checkboxes stored as 0/1.
			if ( is_bool( $default ) ) {
				$value = (bool) $value;
			}

			$settings[ $key ] = $value;
		}

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update settings via REST API.
	 *
	 * @since 2.0.0
	 */
	public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_params();

		foreach ( $params as $key => $value ) {
			if ( ! array_key_exists( $key, $this->defaults ) ) {
				continue;
			}

			$option_name = self::OPTION_PREFIX . str_replace( '_', '-', $key );

			// Convert boolean to int for storage consistency.
			if ( is_bool( $this->defaults[ $key ] ) ) {
				$value = $value ? 1 : 0;
			}

			update_option( $option_name, $value );
		}

		return $this->get_settings();
	}

	/**
	 * Sanitize redirect option values.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Updated with type hints.
	 */
	public function sanitize_redirect_option( string $value ): string {
		$valid = [ 'permalink', 'pt', 'editor' ];
		return in_array( $value, $valid, true ) ? $value : 'editor';
	}

	/**
	 * Handle legacy settings migration.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added editor setting deletion.
	 * @since 2.0.0 Updated with strict comparisons.
	 */
	public function legacy_conversion(): void {
		$legacy = get_option( self::OPTION_PREFIX . 'legacy', 'nothing' );

		if ( $legacy === 1 || $legacy === '1' ) {
			update_option( self::OPTION_PREFIX . 'media', 0 );
			update_option( self::OPTION_PREFIX . 'text', 0 );
			update_option( self::OPTION_PREFIX . 'citation', '<p>via <a href="%1$s">%2$s</a></p>' );
			delete_option( self::OPTION_PREFIX . 'legacy' );
		} elseif ( $legacy === false || $legacy === 0 || $legacy === '0' ) {
			delete_option( self::OPTION_PREFIX . 'legacy' );
		}

		if ( get_option( self::OPTION_PREFIX . 'editor' ) ) {
			delete_option( self::OPTION_PREFIX . 'editor' );
		}
	}

	/**
	 * Execute Press This filters based on settings.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Renamed from execute().
	 */
	public function execute_filters(): void {
		$media_discovery = (bool) get_option( self::OPTION_PREFIX . 'media', $this->defaults['media'] );
		$redirect_parent = (bool) get_option( self::OPTION_PREFIX . 'parent', $this->defaults['parent'] );

		if ( ! $media_discovery ) {
			add_filter( 'enable_press_this_media_discovery', '__return_false' );
		}

		add_filter( 'press_this_suggested_html', [ $this, 'filter_suggested_html' ], 10, 2 );

		if ( $redirect_parent ) {
			add_filter( 'press_this_redirect_in_parent', '__return_true' );
		}
	}

	/**
	 * Execute AJAX-specific filters.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Renamed from execute_ajax().
	 */
	public function execute_ajax_filters(): void {
		add_filter( 'press_this_save_redirect', [ $this, 'filter_save_redirect' ], 10, 3 );
	}

	/**
	 * Filter the suggested HTML for blockquotes and citations.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Renamed from execute_html() with improved typing.
	 *
	 * @param array $html Default HTML templates.
	 * @param array $data Press This data.
	 * @return array Modified HTML templates.
	 */
	public function filter_suggested_html( array $html, array $data ): array {
		$text_discovery = (bool) get_option( self::OPTION_PREFIX . 'text', $this->defaults['text'] );

		$html = [
			'quote' => get_option( self::OPTION_PREFIX . 'blockquote', $this->defaults['blockquote'] ),
			'link'  => get_option( self::OPTION_PREFIX . 'citation', $this->defaults['citation'] ),
		];

		if ( ! $text_discovery && ! isset( $data['s'] ) ) {
			$html['quote'] = '';
		}

		return $html;
	}

	/**
	 * Filter the redirect URL after saving a Press This post.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Renamed from redirect_publish() with improved typing.
	 *
	 * @param string $redirect    Default redirect URL.
	 * @param int    $post_id     Post ID.
	 * @param string $post_status Post status.
	 * @return string Modified redirect URL.
	 */
	public function filter_save_redirect( string $redirect, int $post_id, string $post_status ): string {
		$save_publish = get_option( self::OPTION_PREFIX . 'save-publish', $this->defaults['save_publish'] );
		$save_draft   = get_option( self::OPTION_PREFIX . 'save-draft', $this->defaults['save_draft'] );

		$should_redirect_to_editor = (
			( 'publish' === $post_status && 'editor' === $save_publish ) ||
			( 'publish' !== $post_status && 'editor' === $save_draft )
		);

		if ( $should_redirect_to_editor ) {
			$redirect = get_edit_post_link( $post_id, 'raw' ) ?? $redirect;
		}

		return $redirect;
	}

	/**
	 * Add Settings link to plugin action links.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Updated to use new settings page URL.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=press-this-extended' ) ),
			esc_html__( 'Settings', self::TEXT_DOMAIN )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add mobile web app meta tags for Press This.
	 *
	 * @since 1.1.0
	 */
	public function mobile_app_meta(): void {
		echo '<meta name="apple-mobile-web-app-capable" content="yes">';
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black">';
		echo '<meta name="mobile-web-app-capable" content="yes">';
	}
}

// Initialize the plugin.
new Press_This_Extended();