<?php
/**
 * Press This Extended Settings
 *
 * Handles settings registration, REST API, and admin page.
 *
 * @package BJGK\Press_This_Extended
 * @since 2.0.0
 */

namespace PressThisExtended;

class Settings {

	/**
	 * Option name prefix.
	 */
	const OPTION_PREFIX = 'press_this_extended_';

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'press-this-extended/v1';

	/**
	 * Settings definitions.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_settings();
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Define all available settings.
	 */
	private function define_settings() {
		// Version 1.x settings
		$this->settings = array(
			// Content Discovery
			'media_discovery' => array(
				'type'         => 'boolean',
				'default'      => true,
				'label'        => __( 'Media Discovery', 'press-this-extended' ),
				'description'  => __( 'Suggest media from the source page to add to new posts.', 'press-this-extended' ),
				'capability'   => 'media_discovery',
				'section'      => 'content',
				'filter'       => 'enable_press_this_media_discovery',
			),
			'text_discovery' => array(
				'type'         => 'boolean',
				'default'      => true,
				'label'        => __( 'Text Discovery', 'press-this-extended' ),
				'description'  => __( 'Suggest a quote from the source page if no text is pre-selected.', 'press-this-extended' ),
				'capability'   => 'text_discovery',
				'section'      => 'content',
			),

			// Formatting
			'blockquote_format' => array(
				'type'         => 'string',
				'default'      => '<blockquote>%1$s</blockquote>',
				'label'        => __( 'Blockquote Format', 'press-this-extended' ),
				'description'  => __( 'HTML template for blockquotes. Use %1$s as placeholder for the quoted text.', 'press-this-extended' ),
				'capability'   => 'suggested_html',
				'section'      => 'formatting',
				'sanitize'     => 'wp_kses_post',
			),
			'citation_format' => array(
				'type'         => 'string',
				'default'      => '<p>Source: <em><a href="%1$s">%2$s</a></em></p>',
				'label'        => __( 'Citation Format', 'press-this-extended' ),
				'description'  => __( 'HTML template for citations. Use %1$s for URL and %2$s for page title.', 'press-this-extended' ),
				'capability'   => 'suggested_html',
				'section'      => 'formatting',
				'sanitize'     => 'wp_kses_post',
			),

			// Redirection
			'redirect_in_parent' => array(
				'type'         => 'boolean',
				'default'      => false,
				'label'        => __( 'Redirect Parent Window', 'press-this-extended' ),
				'description'  => __( 'Close Press This popup and redirect the original browser tab after saving.', 'press-this-extended' ),
				'capability'   => 'redirect_in_parent',
				'section'      => 'redirection',
				'filter'       => 'press_this_redirect_in_parent',
			),
			'redirect_on_publish' => array(
				'type'         => 'string',
				'default'      => 'permalink',
				'label'        => __( 'After Publishing', 'press-this-extended' ),
				'description'  => __( 'Where to redirect after publishing a post.', 'press-this-extended' ),
				'capability'   => 'save_redirect',
				'section'      => 'redirection',
				'options'      => array(
					'permalink' => __( 'View Published Post', 'press-this-extended' ),
					'editor'    => __( 'Open in Block Editor', 'press-this-extended' ),
				),
			),
			'redirect_on_draft' => array(
				'type'         => 'string',
				'default'      => 'press-this',
				'label'        => __( 'After Saving Draft', 'press-this-extended' ),
				'description'  => __( 'Where to redirect after saving a draft.', 'press-this-extended' ),
				'capability'   => 'save_redirect',
				'section'      => 'redirection',
				'options'      => array(
					'press-this' => __( 'Stay in Press This', 'press-this-extended' ),
					'editor'     => __( 'Open in Block Editor', 'press-this-extended' ),
				),
			),

			// Version 2.x settings - Block Editor
			'allowed_blocks' => array(
				'type'         => 'array',
				'default'      => array(
					'core/paragraph',
					'core/heading',
					'core/image',
					'core/quote',
					'core/list',
					'core/list-item',
					'core/embed',
					'core/post-featured-image',
				),
				'label'        => __( 'Allowed Blocks', 'press-this-extended' ),
				'description'  => __( 'Select which blocks are available in the Press This editor.', 'press-this-extended' ),
				'capability'   => 'allowed_blocks',
				'section'      => 'blocks',
				'filter'       => 'press_this_allowed_blocks',
			),

			// Version 2.x settings - Post Type
			'post_type' => array(
				'type'         => 'string',
				'default'      => 'post',
				'label'        => __( 'Post Type', 'press-this-extended' ),
				'description'  => __( 'The post type to use when creating new Press This posts.', 'press-this-extended' ),
				'capability'   => 'post_type',
				'section'      => 'post',
				'filter'       => 'press_this_post_type',
			),

			// Version 2.x settings - Post Format
			'post_format_override' => array(
				'type'         => 'string',
				'default'      => '',
				'label'        => __( 'Force Post Format', 'press-this-extended' ),
				'description'  => __( 'Force a specific post format, bypassing all detection logic. Leave empty to use auto-detection.', 'press-this-extended' ),
				'capability'   => 'post_format_override',
				'section'      => 'post',
				'filter'       => 'press_this_post_format_override',
				'options'      => 'post_formats', // Dynamic options
			),
			'default_post_format' => array(
				'type'         => 'string',
				'default'      => '',
				'label'        => __( 'Default Post Format', 'press-this-extended' ),
				'description'  => __( 'Default post format when auto-detection finds nothing. Leave empty for standard format.', 'press-this-extended' ),
				'capability'   => 'default_post_format',
				'section'      => 'post',
				'filter'       => 'press_this_default_post_format',
				'options'      => 'post_formats', // Dynamic options
			),

			// Version 2.x settings - Media Sideloading
			'sideload_max_size' => array(
				'type'         => 'integer',
				'default'      => 10,
				'label'        => __( 'Max Sideload Size (MB)', 'press-this-extended' ),
				'description'  => __( 'Maximum file size in megabytes for sideloading images.', 'press-this-extended' ),
				'capability'   => 'sideload_max_size',
				'section'      => 'media',
				'filter'       => 'press_this_sideload_max_size',
				'min'          => 1,
				'max'          => 50,
			),
			'sideload_allowed_types' => array(
				'type'         => 'array',
				'default'      => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
				'label'        => __( 'Allowed Image Types', 'press-this-extended' ),
				'description'  => __( 'MIME types allowed for image sideloading.', 'press-this-extended' ),
				'capability'   => 'sideload_allowed_types',
				'section'      => 'media',
				'filter'       => 'press_this_sideload_allowed_types',
				'options'      => array(
					'image/jpeg' => 'JPEG',
					'image/png'  => 'PNG',
					'image/gif'  => 'GIF',
					'image/webp' => 'WebP',
					'image/avif' => 'AVIF',
					'image/svg+xml' => 'SVG',
				),
			),

			// Version 2.x settings - URL Proxy
			'enable_url_proxy' => array(
				'type'         => 'boolean',
				'default'      => false,
				'label'        => __( 'Enable URL Proxy', 'press-this-extended' ),
				'description'  => __( 'Allow Press This to fetch URLs server-side for Direct Access Mode. Use with caution.', 'press-this-extended' ),
				'capability'   => 'enable_url_proxy',
				'section'      => 'advanced',
				'filter'       => 'press_this_enable_url_proxy',
			),
		);
	}

	/**
	 * Get all settings definitions.
	 *
	 * @return array Settings definitions.
	 */
	public function get_settings_definitions() {
		return $this->settings;
	}

	/**
	 * Get available settings based on Press This version.
	 *
	 * @return array Filtered settings definitions.
	 */
	public function get_available_settings() {
		$available = array();

		foreach ( $this->settings as $key => $setting ) {
			if ( Version_Detector::has_capability( $setting['capability'] ) ) {
				$available[ $key ] = $setting;
			}
		}

		return $available;
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Press This Extended', 'press-this-extended' ),
			__( 'Press This', 'press-this-extended' ),
			'manage_options',
			'press-this-extended',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Register settings with WordPress.
	 */
	public function register_settings() {
		foreach ( $this->settings as $key => $setting ) {
			$option_name = self::OPTION_PREFIX . $key;

			$args = array(
				'type'              => $setting['type'] === 'array' ? 'array' : $setting['type'],
				'default'           => $setting['default'],
				'sanitize_callback' => $this->get_sanitize_callback( $setting ),
				'show_in_rest'      => $this->get_rest_schema( $setting ),
			);

			register_setting( 'press_this_extended', $option_name, $args );
		}
	}

	/**
	 * Get sanitize callback for a setting.
	 *
	 * @param array $setting Setting definition.
	 * @return callable Sanitize callback.
	 */
	private function get_sanitize_callback( $setting ) {
		if ( isset( $setting['sanitize'] ) ) {
			return $setting['sanitize'];
		}

		switch ( $setting['type'] ) {
			case 'boolean':
				return 'rest_sanitize_boolean';
			case 'integer':
				return 'absint';
			case 'array':
				return array( $this, 'sanitize_array' );
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Sanitize array values.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized array.
	 */
	public function sanitize_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Get REST schema for a setting.
	 *
	 * @param array $setting Setting definition.
	 * @return array|bool REST schema or true.
	 */
	private function get_rest_schema( $setting ) {
		if ( $setting['type'] === 'array' ) {
			return array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			);
		}

		return true;
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/version',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_version_info' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/blocks',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_available_blocks' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permission for REST requests.
	 *
	 * @return bool Whether the user has permission.
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get current settings via REST.
	 *
	 * @return \WP_REST_Response Settings response.
	 */
	public function get_settings() {
		$values   = array();
		$available = $this->get_available_settings();

		foreach ( $this->settings as $key => $setting ) {
			$option_name    = self::OPTION_PREFIX . $key;
			$values[ $key ] = get_option( $option_name, $setting['default'] );
		}

		return rest_ensure_response( array(
			'values'      => $values,
			'definitions' => $this->settings,
			'available'   => array_keys( $available ),
		) );
	}

	/**
	 * Update settings via REST.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error Updated settings response or error.
	 */
	public function update_settings( $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return new \WP_Error(
				'press_this_extended_invalid_request',
				__( 'Invalid request body', 'press-this-extended' ),
				array( 'status' => 400 )
			);
		}

		foreach ( $params as $key => $value ) {
			if ( ! isset( $this->settings[ $key ] ) ) {
				continue;
			}

			$option_name = self::OPTION_PREFIX . $key;
			$sanitize    = $this->get_sanitize_callback( $this->settings[ $key ] );
			$value       = call_user_func( $sanitize, $value );

			update_option( $option_name, $value );
		}

		return $this->get_settings();
	}

	/**
	 * Get Press This version info via REST.
	 *
	 * @return \WP_REST_Response Version info response.
	 */
	public function get_version_info() {
		return rest_ensure_response( Version_Detector::get_version_info() );
	}

	/**
	 * Get available blocks for the block picker.
	 *
	 * @return \WP_REST_Response Available blocks response.
	 */
	public function get_available_blocks() {
		$block_registry = \WP_Block_Type_Registry::get_instance();
		$blocks         = $block_registry->get_all_registered();
		$block_list     = array();

		foreach ( $blocks as $name => $block ) {
			// Skip certain block types
			if ( strpos( $name, 'core/legacy' ) === 0 ) {
				continue;
			}

			$block_list[] = array(
				'name'        => $name,
				'title'       => isset( $block->title ) ? $block->title : $name,
				'description' => isset( $block->description ) ? $block->description : '',
				'category'    => isset( $block->category ) ? $block->category : 'common',
				'icon'        => isset( $block->icon ) ? $block->icon : 'block-default',
				'keywords'    => isset( $block->keywords ) ? $block->keywords : array(),
			);
		}

		// Sort by title
		usort( $block_list, function( $a, $b ) {
			return strcasecmp( $a['title'], $b['title'] );
		} );

		return rest_ensure_response( $block_list );
	}

	/**
	 * Get available post types.
	 *
	 * @return \WP_REST_Response Post types response.
	 */
	public function get_post_types() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$list = array();

		foreach ( $post_types as $post_type ) {
			// Skip attachments
			if ( $post_type->name === 'attachment' ) {
				continue;
			}

			$list[] = array(
				'name'  => $post_type->name,
				'label' => $post_type->label,
			);
		}

		return rest_ensure_response( $list );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( $hook !== 'settings_page_press-this-extended' ) {
			return;
		}

		$asset_file = plugin_dir_path( dirname( __FILE__ ) ) . 'build/settings.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'press-this-extended-settings',
			plugins_url( 'build/settings.js', PRESS_THIS_EXTENDED_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'press-this-extended-settings',
			plugins_url( 'build/settings.css', PRESS_THIS_EXTENDED_FILE ),
			array( 'wp-components' ),
			$asset['version']
		);

		wp_localize_script(
			'press-this-extended-settings',
			'pressThisExtendedSettings',
			array(
				'restUrl'   => rest_url( self::REST_NAMESPACE ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'version'   => Version_Detector::get_version_info(),
				'postFormats' => $this->get_post_formats(),
			)
		);
	}

	/**
	 * Get available post formats.
	 *
	 * @return array Post formats.
	 */
	private function get_post_formats() {
		$formats = array(
			'' => __( 'Standard', 'press-this-extended' ),
		);

		if ( current_theme_supports( 'post-formats' ) ) {
			$supported = get_theme_support( 'post-formats' );
			if ( is_array( $supported ) && isset( $supported[0] ) ) {
				foreach ( $supported[0] as $format ) {
					$formats[ $format ] = get_post_format_string( $format );
				}
			}
		}

		return $formats;
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		$asset_file = plugin_dir_path( dirname( __FILE__ ) ) . 'build/settings.asset.php';
		$has_build  = file_exists( $asset_file );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Press This Extended', 'press-this-extended' ); ?></h1>
			<?php if ( ! $has_build ) : ?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'Build Required', 'press-this-extended' ); ?></strong>
					</p>
					<p>
						<?php esc_html_e( 'The JavaScript assets have not been built yet. Please run the following commands in the plugin directory:', 'press-this-extended' ); ?>
					</p>
					<pre style="background: #f0f0f0; padding: 10px; margin: 10px 0;">npm install
npm run build</pre>
				</div>
			<?php endif; ?>
			<div id="press-this-extended-settings"></div>
		</div>
		<?php
	}

	/**
	 * Get option value.
	 *
	 * @param string $key Setting key.
	 * @return mixed Option value.
	 */
	public function get_option( $key ) {
		if ( ! isset( $this->settings[ $key ] ) ) {
			return null;
		}

		$option_name = self::OPTION_PREFIX . $key;
		return get_option( $option_name, $this->settings[ $key ]['default'] );
	}
}
