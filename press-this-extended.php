<?php

/**
 * Press This Extended
 *
 * @package     BJGK\Press_this_extended
 * @version     1.0.0-beta1
 * @author      Brandon Kraft <public@brandonkraft.com>
 * @copyright   Copyright (c) 2015, Brandon Kraft
 * @link        https://www.brandonkraft.com/press-this-extended/
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Press This Extended
 * Plugin URI:  https://www.brandonkraft.com/press-this-extended/
 * Description: Provides options for extending and modifying the Press This feature (WP 4.2+)
 * Version:     1.0.0-beta1
 * Author:      Brandon Kraft
 * Author URI:  https://www.brandonkraft.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: press-this-extended
 * Domain Path: /languages
 */

 /*
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

class Press_This_Extended {

	/**
	 * Sets the cruise control at 88 MPH. In other words, let's fire the engines
	 *
	 * Selectively adding hooks based on need so we don't cross streams resulting in every molecule in our bodies exploding.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function __construct() {
		global $pagenow;

		add_action( 'admin_init',                                       array( $this, 'load_translations' ) , 1 ); // These filters are generally used within wp-admin.
		add_action( 'admin_init',                                       array( $this, 'add_settings' ) );
		add_action( 'admin_init',                                       array( $this, 'legacy_conversion') );
		add_action( 'load-options-writing.php',                         array( $this, 'help_tab' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'action_links' ) );

		if ( 'press-this.php' == $pagenow ) { // Only needed when Press This is loaded.
			add_action( 'admin_init',             array( $this, 'execute' ) );
			add_filter( 'http_headers_useragent', array( $this, 'ua_hack' ) ); // When WP is 5.3+, use anonymous function.
		}

		if ( 'admin-ajax.php' == $pagenow ) { // These hooks are the only one used exclusively within the ajax context.
			add_action( 'admin_init', array( $this, 'execute_ajax' ) );
		}
	}

	/**
 	 * Load the textdomain / translations for the plugin.
 	 *
 	 * @since 1.0.0
 	 * @return void
 	 */
	function load_translations() {
		$domain = 'press-this-extended';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add settings field to the the Settings->Writing page.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function add_settings() {
		$slug = 'press-this-extended';

		add_settings_section( $slug, 'Press This', null, 'writing');

		add_settings_field( $slug . '-media', __( 'Content Grabbing', $slug ), array( $this, 'setting_media' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-media', 'intval' );
		add_filter( 'default_option_'. $slug . '-media', '__return_true' );

		add_settings_field( $slug . '-text', null, array( $this, 'setting_text' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-text', 'intval' );
		add_filter( 'default_option_' . $slug . '-text', '__return_true' );

		add_settings_field( $slug . '-blockquote', __('Blockquote Formatting', $slug), array( $this, 'setting_blockquote' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-blockquote', 'wp_kses_post' );
		add_filter( 'default_option_' . $slug . '-blockquote', array( $this, 'default_blockquote' ) ); // When WP is 5.3+, use anonymous function.

		add_settings_field( $slug . '-citation', __('Citation Formatting', $slug), array( $this, 'setting_citation' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-citation', 'wp_kses_post' );
		add_filter( 'default_option_' . $slug . '-citation', array( $this, 'default_citation' ) ); // When WP is 5.3+, use anonymous function.

		add_settings_field( $slug . '-parent', __('Redirection', $slug), array( $this, 'setting_parent' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-parent', 'intval' );
		add_filter( 'default_option_' . $slug . '-parent', '__return_false' );

		add_settings_field( $slug . '-save-publish', __( 'Upon Publishing...', 'press-this-extended' ), array( $this, 'setting_save_publish' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-save-publish', array( $this, 'save_sanitize' ) );
		add_filter( 'default_option_' . $slug . '-save-publish', array( $this, 'default_publish' ) );

		add_settings_field( $slug . '-save-draft', __( 'Upon Saving a Draft...', 'press-this-extended' ), array( $this, 'setting_save_draft' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-save-draft', array( $this, 'save_sanitize' ) );
		add_filter( 'default_option_' . $slug . '-save-draft', array( $this, 'default_save' ) );

		add_settings_field( $slug . '-editor', __('Text Editor', $slug), array( $this, 'setting_editor' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-editor', 'intval' );
		add_filter( 'default_option_' . $slug . '-editor', '__return_false' );
	}


	/**
	 * Santizes setting options for save_publish and save_draft settings.
	 *
	 * @return string Either 'pt', 'permalink', 'editor'. Defaults to 'editor' if validation fails.
	 * @since 1.0.0
	 **/
	function save_sanitize( $value ) {
		if ( $value != 'permalink' && $value != 'pt' ) {
			$value = 'editor';
		}

		return $value;
	}

	/**
	 * Converts pre-release Legacy option to standard options and delete option.
	 *
	 * This will be left in for version 1 to clean up the few beta testers.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function legacy_conversion() {
		$legacy = get_option( 'press-this-extended-legacy', 'nothing' ); // sets a default value if setting is not present in the DB. Can't use false since that is a valid setting state in the db.
		if ( $legacy == 1 ) {
			$citation = '<p>via <a href="%1$s">%2$s</a></p>';
			update_option( 'press-this-extended-media', false );
			update_option( 'press-this-extended-text', false );
			update_option( 'press-this-extended-citation', $citation );
			delete_option( 'press-this-extended-legacy' );
		}
		elseif ( $legacy == false ) {
			delete_option( 'press-this-extended-legacy' );
		}
	}

	/**
	 * Returns the default blockquote wrapper original to Press This
	 *
	 * @return string Default blockquote wrapping with %1$s being the variable for the quote.
	 * @since 1.0.0
	 **/
	function default_blockquote() {
		return '<blockquote>%1$s</blockquote>';
	}

	/**
	 * Returns the default citation or "source" wrapper original to Press This.
	 *
	 * The default's use of "Source" is translated natively in WordPress Core, so no plugin language domain is indicated
	 *
	 * @return string Default source wrapping with %1$s representing the URL and %2$s the pressed page's title.
	 * @since 1.0.0
	 **/
	function default_citation() {
		$html = '<p>' . _x( 'Source:', 'Used in Press This to indicate where the content comes from.' ) .
				' <em><a href="%1$s">%2$s</a></em></p>';
		return $html;
	}

	/**
	 * Returns the default value for saving a draft.
	 *
	 * @return string Default draft redirect location.
	 * @since 1.0.0
	 **/
	function default_save() {
		return 'pt';
	}

	/**
	 * Returns the default value for publishing a post.
	 *
	 * @return string Default publish redirect location.
	 * @since 1.0.0
	 **/
	function default_publish() {
		return 'permalink';
	}

	/**
	 * Echos the Media Discovery setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_media(){
		$html = '<input type="checkbox" id="press-this-extended-media" name="press-this-extended-media" value="1" ' . checked(1, get_option('press-this-extended-media'), false) . '/>';
		$html .= '<label for="press-this-extended-media"> '  . __( 'Should Press This suggest media to add to a new post?', 'press-this-extended' ) . '</label>';
		echo $html;
	}

	/**
	 * Echos the Text Discovery setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_text(){
		$html = '<input type="checkbox" id="press-this-extended-text" name="press-this-extended-text" value="1" ' . checked(1, get_option('press-this-extended-text'), false) . '/>';
		$html .= '<label for="press-this-extended-text"> '  . __( "Should Press This try to suggest a quote if you haven't preselected text?", 'press-this-extended' ) . '</label>';
		echo $html;
	}

	/**
	 * Echos the Blockquote wrapper setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_blockquote(){
		$html = '<input type="text" id="press-this-extended-blockquote" name="press-this-extended-blockquote" value="' . esc_attr( get_option('press-this-extended-blockquote')) . '" class="regular-text ltr" />';
		$html .= '<p class="description">' . __( 'Use %1$s as a placeholder for the blockquote.', 'press-this-extended' ) .'</p>';
		echo $html;
	}

	/**
	 * Echos the Citation wrapper setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_citation(){
		$html = '<input type="text" id="press-this-extended-citation" name="press-this-extended-citation" value="' . esc_attr( get_option('press-this-extended-citation')) . '" class="regular-text ltr" />';
		$html .= '<p class="description">' . __( 'Use %1$s and %2$s as a placeholders for the page URL and title, respectively.', 'press-this-extended' ) .'</p>';
		echo $html;
	}

	/**
	 * Echos HTML for the Parent Redirection option setting form field.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_parent() {
		$html = '<input type="checkbox" id="press-this-extended-parent" name="press-this-extended-parent" value="1" ' . checked(1, get_option('press-this-extended-parent'), false) . '/>';
		$html .= '<label for="press-this-extended-parent"> '  . __( 'Upon publishing or saving a draft, close the Press This popup and redirect the original tab.', 'press-this-extended' ) . '</label>';

		echo $html;
	}

	/**
	 * Echos HTML for the selecting redirect when publishing a post.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_save_publish() {
		$options = array(
			'permalink' => __( 'Published Post', 'press-this-extended' ),
			'editor'    => __( 'Standard Editor' ),
		);

		$extra_text = __( 'After publishing a post, you will be redirected to this location.', 'press-this-extended' );

		$this->settings_select( 'press-this-extended-save-publish', $options, $extra_text );
	}

	/**
	 * Echos HTML for the selecting redirect when saving a draft.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_save_draft() {
		$options = array(
			'pt'     => __( 'Remain in the Press This editor', 'press-this-extended' ),
			'editor' => __( 'Standard Editor' ),
		);

		$extra_text = __( 'After saving a draft, you will be redirected to this location.', 'press-this-extended' );

		$this->settings_select( 'press-this-extended-save-draft', $options, $extra_text );
	}

	/**
	 * Echos HTML dropdown selection forms.
	 *
	 * @return void
	 * @since 1.0.0
	 **/

	function settings_select( $name, $values, $extra_text = '' ) {
		if ( empty( $name ) || empty( $values ) || ! is_array( $values ) ) {
			return;
		}
		$option = get_option( $name );
		?>
		<fieldset>
			<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $values as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $option ); ?>>
						<?php echo esc_html( $value ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! empty( $extra_text ) ) : ?>
				<p class="description"><?php echo esc_html( $extra_text ); ?></p>
			<?php endif; ?>
		</fieldset>
		<?php
	}

	/**
	 * Echos HTML for the Code Editor option setting form field.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function setting_editor() {
		$html = '<input type="checkbox" id="press-this-extended-editor" name="press-this-extended-editor" value="1" ' . checked(1, get_option('press-this-extended-editor'), false) . '/>';
		$html .= '<label for="press-this-extended-editor"> '  . __( 'Enable the Text Editor in Press This', 'press-this-extended' ) . '</label>';
		$html .= '<p class="description">' . __( 'Experimental! This is not fully operational yet. Inserting detected media will not work in the Text Editor. The "Save Draft" button will not reappear until you focus within the Visual Editor.<br />There be dragons! Your milage may vary. No warranty implied or stated!', 'press-this-extended' ) .'</p>';
		echo $html;
	}

	/**
	 * Used when filtering the default html from Press This based on the various options set in Press This Extended.
	 *
	 * @return array $html {
	 *		@type string $quote Blockquote wrapping with placeholder %1$s for the quoted text.
	 *		@type string $link  Citation wrapping with placeholders %1$s and %2$s for Pressed URL and page title, respectively.
	 * }
	 * @since 1.0.0
	 **/
	function execute_html( $html, $data ){
		$text_discovery  = get_option( 'press-this-extended-text' );

		$html = array(
				'quote' => get_option( 'press-this-extended-blockquote'),
				'link'  => get_option( 'press-this-extended-citation'),
			);

		if ( $text_discovery == false && ! isset( $data['s'] ) ) {
			$html['quote'] = '';
		}

		/* Leaving this in for now for future reference.
		if ( $legacy ) {
			if ( isset( $data['s'] ) ){
				$html = array(
					'quote' => '<p>%1$s</p>',
					'link'  => '<p>via <a href="%1$s">%2$s</a></p>',
					);
			}
			else {
				$html = array(
					'quote' => '',
					'link'  => '<a href="%1$s">%2$s</a>',
					);
			}
		}*/

		return $html;
	}

	/**
	 * Execute the settings by adding the filters specified by the various settings.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function execute() {
		$text_discovery  = get_option( 'press-this-extended-text' );
		$media_discovery = get_option( 'press-this-extended-media' );
		$redirect_parent = get_option( 'press-this-extended-parent' );
		$text_editor     = get_option( 'press-this-extended-editor' );

		if ( $media_discovery == false ) {
			add_filter( 'enable_press_this_media_discovery', '__return_false' );
		}

		add_filter( 'press_this_suggested_html', array( $this, 'execute_html' ), 10, 2 );

		if ( $text_editor ) {
			add_filter('wp_editor_settings', array( $this, 'enable_text_editor' ) );
			add_action('admin_print_styles', array( $this, 'text_editor_style' ) );
		}

		if ( $redirect_parent ) {
			add_filter( 'press_this_redirect_in_parent', '__return_true' );
		}
	}

	/**
	 * Execute filters only used in the AJAX context.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function execute_ajax() {
		add_filter( 'press_this_save_redirect', array( $this, 'redirect_publish'), 10, 3 );
	}

	/**
	 * Determines the redirected URL upon publishing a Press This post.
	 *
	 * This could be expanded in the future to do other fun things.
	 *
	 * @return URL of redirectoin
	 * @since 1.0.0
	 */
	function redirect_publish( $redirect, $post_id, $post_status ) {
		$save_publish = get_option( 'press-this-extended-save-publish' );
		$save_draft   = get_option( 'press-this-extended-save-draft' );

		if ( ( 'publish' == $post_status && $save_publish == 'editor' ) || 'publish' != $post_status && $save_draft == 'editor' ) {
				$redirect = get_edit_post_link( $post_id, 'raw' );
		}

		return $redirect; // otherwise, it is using the default action within WordPress. We want their option in case we need to flesh this out later.
	}

	/**
	 * Adds contextual help for Press This Extended options.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	function help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'      => 'options-press-this-extended',
			'title'   => __('Press This'),
			'content' => '<p>' . __( 'Filler text. These options allow you to customize the Press This bookmarklet to do some cool stuff.' ) . '</p>',
			)
		);
	}

	/**
	 * Changes the UA on Press This scrapes to "WP Press This".
	 *
	 * Added since Medium and pehaps others block all requests with the UA of "WordPress" to stop pingback spam.
	 *
	 * @return string New UA string.
	 * @since 1.0.0
	 **/
	function ua_hack() {
		return 'WP Press This';
	}

	/**
	 * Adds inline styling to the Press This page via admin_print_styles hook for the text editor.
	 *
	 *
	 * @return void
	 * @see 'admin_print_styles'
	 * @since 1.0.0
	 **/
	function text_editor_style(){
		echo '<style type="text/css">textarea#pressthis {color: #404040;}.quicktags-toolbar {background: 0;}</style>';
	}

	/**
	 * Enables the "quicktags" option in TinyMCE to flip between Visual and Text editors.
	 *
	 * Filter will retain all current TinyMCE settings except forcing the Visual/Text editor.
	 * @return array $settings {
	 *		@type bool $quicktags Enables Visual/Text Editor buttons in TinyMCE
	 * }
	 * @since 1.0.0
	 **/
	function enable_text_editor( $settings ){
		$settings['quicktags'] = true;
		return $settings;
	}

	/**
	 * Adds Settings link to plugin table.
	 *
	 * @return array $links
	 *
	 * @since 1.0.0
	 **/
	function action_links( $links ) {
		$links[] = '<a href="'. get_admin_url(null, 'options-writing.php') .'">Settings</a>';
		return $links;
	}
}

// Giddy up.
$Press_This_Extended = new Press_This_Extended;