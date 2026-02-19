<?php
/**
 * Press This Extended Filters
 *
 * Applies WordPress filters based on plugin settings.
 *
 * @package BJGK\Press_This_Extended
 * @since 2.0.0
 */

namespace PressThisExtended;

class Filters {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize filter hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'apply_filters' ) );

		// AJAX-specific filters
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'admin_init', array( $this, 'apply_ajax_filters' ) );
		}

		// REST API filters (for Press This 2.x)
		add_action( 'rest_api_init', array( $this, 'apply_rest_filters' ) );
	}

	/**
	 * Apply non-AJAX filters.
	 */
	public function apply_filters() {
		// Media discovery
		if ( Version_Detector::has_capability( 'media_discovery' ) ) {
			$media_discovery = $this->settings->get_option( 'media_discovery' );
			if ( ! $media_discovery ) {
				add_filter( 'enable_press_this_media_discovery', '__return_false' );
			}
		}

		// Suggested HTML (blockquote/citation formatting)
		if ( Version_Detector::has_capability( 'suggested_html' ) ) {
			add_filter( 'press_this_suggested_html', array( $this, 'filter_suggested_html' ), 10, 2 );
		}

		// Redirect in parent
		if ( Version_Detector::has_capability( 'redirect_in_parent' ) ) {
			$redirect_parent = $this->settings->get_option( 'redirect_in_parent' );
			if ( $redirect_parent ) {
				add_filter( 'press_this_redirect_in_parent', '__return_true' );
			}
		}

		// Version 2.x filters
		$this->apply_v2_filters();
	}

	/**
	 * Apply Version 2.x specific filters.
	 */
	private function apply_v2_filters() {
		// Allowed blocks
		if ( Version_Detector::has_capability( 'allowed_blocks' ) ) {
			add_filter( 'press_this_allowed_blocks', array( $this, 'filter_allowed_blocks' ) );
		}

		// Post type
		if ( Version_Detector::has_capability( 'post_type' ) ) {
			add_filter( 'press_this_post_type', array( $this, 'filter_post_type' ), 10, 2 );
		}

		// Post format override
		if ( Version_Detector::has_capability( 'post_format_override' ) ) {
			add_filter( 'press_this_post_format_override', array( $this, 'filter_post_format_override' ) );
		}

		// Default post format
		if ( Version_Detector::has_capability( 'default_post_format' ) ) {
			add_filter( 'press_this_default_post_format', array( $this, 'filter_default_post_format' ) );
		}

		// Sideload max size
		if ( Version_Detector::has_capability( 'sideload_max_size' ) ) {
			add_filter( 'press_this_sideload_max_size', array( $this, 'filter_sideload_max_size' ) );
		}

		// Sideload allowed types
		if ( Version_Detector::has_capability( 'sideload_allowed_types' ) ) {
			add_filter( 'press_this_sideload_allowed_types', array( $this, 'filter_sideload_allowed_types' ) );
		}

		// URL proxy
		if ( Version_Detector::has_capability( 'enable_url_proxy' ) ) {
			$enable_proxy = $this->settings->get_option( 'enable_url_proxy' );
			if ( $enable_proxy ) {
				add_filter( 'press_this_enable_url_proxy', '__return_true' );
			}
		}
	}

	/**
	 * Apply AJAX-specific filters.
	 */
	public function apply_ajax_filters() {
		if ( Version_Detector::has_capability( 'save_redirect' ) ) {
			add_filter( 'press_this_save_redirect', array( $this, 'filter_save_redirect' ), 10, 3 );
		}
	}

	/**
	 * Apply REST API filters (for Press This 2.x).
	 */
	public function apply_rest_filters() {
		// Same redirect filter for REST API
		if ( Version_Detector::has_capability( 'save_redirect' ) ) {
			add_filter( 'press_this_save_redirect', array( $this, 'filter_save_redirect' ), 10, 3 );
		}
	}

	/**
	 * Filter suggested HTML for blockquotes and citations.
	 *
	 * @param array $html Default HTML templates.
	 * @param array $data Press This data.
	 * @return array Modified HTML templates.
	 */
	public function filter_suggested_html( $html, $data ) {
		$text_discovery = $this->settings->get_option( 'text_discovery' );
		$blockquote     = $this->settings->get_option( 'blockquote_format' );
		$citation       = $this->settings->get_option( 'citation_format' );

		$html = array(
			'quote' => $blockquote,
			'link'  => $citation,
		);

		// If text discovery is disabled and no text was pre-selected, clear the quote
		if ( ! $text_discovery && ! isset( $data['s'] ) ) {
			$html['quote'] = '';
		}

		return $html;
	}

	/**
	 * Filter allowed blocks.
	 *
	 * @param array $blocks Default allowed blocks.
	 * @return array Modified allowed blocks.
	 */
	public function filter_allowed_blocks( $blocks ) {
		$allowed = $this->settings->get_option( 'allowed_blocks' );

		if ( ! empty( $allowed ) && is_array( $allowed ) ) {
			return $allowed;
		}

		return $blocks;
	}

	/**
	 * Filter post type.
	 *
	 * @param string $post_type Default post type.
	 * @param array  $data Press This data.
	 * @return string Modified post type.
	 */
	public function filter_post_type( $post_type, $data ) {
		$custom_type = $this->settings->get_option( 'post_type' );

		if ( ! empty( $custom_type ) && post_type_exists( $custom_type ) ) {
			return $custom_type;
		}

		return $post_type;
	}

	/**
	 * Filter post format override.
	 *
	 * @return string Post format to force.
	 */
	public function filter_post_format_override() {
		return $this->settings->get_option( 'post_format_override' );
	}

	/**
	 * Filter default post format.
	 *
	 * @return string Default post format.
	 */
	public function filter_default_post_format() {
		return $this->settings->get_option( 'default_post_format' );
	}

	/**
	 * Filter sideload max size.
	 *
	 * @param int $max_size Default max size in bytes.
	 * @return int Modified max size in bytes.
	 */
	public function filter_sideload_max_size( $max_size ) {
		$mb = $this->settings->get_option( 'sideload_max_size' );

		if ( $mb > 0 ) {
			return $mb * 1024 * 1024; // Convert MB to bytes
		}

		return $max_size;
	}

	/**
	 * Filter sideload allowed types.
	 *
	 * @param array $types Default allowed MIME types.
	 * @return array Modified allowed MIME types.
	 */
	public function filter_sideload_allowed_types( $types ) {
		$allowed = $this->settings->get_option( 'sideload_allowed_types' );

		if ( ! empty( $allowed ) && is_array( $allowed ) ) {
			return $allowed;
		}

		return $types;
	}

	/**
	 * Filter save redirect URL.
	 *
	 * @param string $redirect Default redirect URL.
	 * @param int    $post_id The post ID.
	 * @param string $post_status The post status.
	 * @return string Modified redirect URL.
	 */
	public function filter_save_redirect( $redirect, $post_id, $post_status ) {
		if ( 'publish' === $post_status ) {
			$setting = $this->settings->get_option( 'redirect_on_publish' );
			if ( 'editor' === $setting ) {
				return get_edit_post_link( $post_id, 'raw' );
			}
		} else {
			$setting = $this->settings->get_option( 'redirect_on_draft' );
			if ( 'editor' === $setting ) {
				return get_edit_post_link( $post_id, 'raw' );
			}
		}

		return $redirect;
	}
}
