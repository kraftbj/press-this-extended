<?php

/**
 * Press This Extended
 *
 * @package     BJGK\Press_this_extended
 * @version     0.1-20150414-2225
 * @author      Brandon Kraft <public@brandonkraft.com>
 * @copyright   Copyright (c) 2015, Brandon Kraft
 * @link        https://www.brandonkraft.com/press-this-extended/
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Press This Extended
 * Plugin URI:  https://www.brandonkraft.com/press-this-extended/
 * Description: Provides options for extending and modifying the Press This feature (WP 4.2+)
 * Version:     0.1-20150414-2225
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
	 * @since 1.0.0
	 * @return void
	 * @access public
	 */
	public function __construct() {
		global $pagenow;

		add_action( 'admin_init',               array( $this, 'load_translations' ) , 1 );
		add_action( 'admin_init',               array( $this, 'add_settings' ) );
		add_action( 'admin_init',               array( $this, 'legacy_conversion') );
		add_action( 'load-options-writing.php', array( $this, 'help_tab' ) );

		if ( 'press-this.php' == $pagenow ) {
			add_action( 'admin_init',             array( $this, 'execute' ) );
			add_filter( 'http_headers_useragent', array( $this, 'ua_hack' ) ); // When WP is 5.3+, use anonymous function
		}
	}


	/**
 	 * Load the textdomain / translations for the plugin.
 	 *
 	 * @since 1.0.0
 	 * @return void
 	 */
	public function load_translations() {
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
	 * @access public
	 **/
	public function add_settings() {
		$slug = 'press-this-extended';

		add_settings_section( $slug, 'Press This', null, 'writing');

		/*add_settings_field( $slug . '-legacy', __( 'Legacy Mode', $slug ), array( $this, 'press_this_extended_legacy' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-legacy', 'intval' );
		add_filter( 'default_option_' . $slug . '-legacy', '__return_false' ); */

		add_settings_field( $slug . '-media', __( 'Content Discovery', $slug ), array( $this, 'press_this_extended_media' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-media', 'intval' );
		add_filter( 'default_option_'. $slug . '-media', '__return_true' );

		add_settings_field( $slug . '-text', null, array( $this, 'press_this_extended_text' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-text', 'intval' );
		add_filter( 'default_option_' . $slug . '-text', '__return_true' );

		add_settings_field( $slug . '-blockquote', __('Blockquote Wrapping', $slug), array( $this, 'press_this_extended_blockquote' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-blockquote', 'wp_kses_post' );
		add_filter( 'default_option_' . $slug . '-blockquote', array( $this, 'default_blockquote' ) ); // When WP is 5.3+, use anonymous function

		add_settings_field( $slug . '-citation', __('Citation Wrapping', $slug), array( $this, 'press_this_extended_citation' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-citation', 'wp_kses_post' );
		add_filter( 'default_option_' . $slug . '-citation', array( $this, 'default_citation' ) ); // When WP is 5.3+, use anonymous function
	}

	/**
	 * Converts pre-release Legacy option to standard options and delete option.
	 *
	 * This will be left in for version 1 to clean up the few beta testers.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function legacy_conversion() {
		$legacy = get_option( 'press-this-extended-legacy', 'nothing' ); // sets a default value if setting is not present in the DB. Can't use false since that is a valid setting state in the db.
		if ( $legacy == true ) {
			$citation = '<p>via <a href="%1$s">%2$s</a></p>';
			update_option( 'press-this-extended-media', false );
			update_option( 'press-this-extended-text', false );
			update_option( 'press_this_extended_citation', $citation );
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
	 * @access public
	 **/
	public function default_blockquote() {
		return '<blockquote>%1$s</blockquote>';
	}

	/**
	 * Returns the default citation or "source" wrapper original to Press This.
	 *
	 * The default's use of "Source" is translated natively in WordPress Core, so no plugin language domain is indicated
	 * @return string Default source wrapping with %1$s representing the URL and %2$s the pressed page's title.
	 * @since 1.0.0
	 * @access public
	 **/
	public function default_citation() {
		$html = '<p>' . _x( 'Source:', 'Used in Press This to indicate where the content comes from.' ) .
				' <em><a href="%1$s">%2$s</a></em></p>';
		return $html;
	}

	/**
	 * Echos HTML for the Legacy option setting form field.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	/*public function press_this_extended_legacy() {
		$html = '<input type="checkbox" id="press-this-extended-legacy" name="press-this-extended-legacy" value="1" ' . checked(1, get_option('press-this-extended-legacy'), false) . '/>';
		$html .= '<label for="press-this-extended-legacy"> '  . __( 'Have Press This mimic behavior prior to WordPress 4.2', 'press-this-extended' ) . '</label>';

		echo $html;
	}*/

	/**
	 * Echos the Media Discovery setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_extended_media(){
		$html = '<input type="checkbox" id="press-this-extended-media" name="press-this-extended-media" value="1" ' . checked(1, get_option('press-this-extended-media'), false) . '/>';
		$html .= '<label for="press-this-extended-media"> '  . __( 'Should Press This suggest media to add to a new post?', 'press-this-extended' ) . '</label>';
		echo $html;
	}

	/**
	 * Echos the Text Discovery setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_extended_text(){
		$html = '<input type="checkbox" id="press-this-extended-text" name="press-this-extended-text" value="1" ' . checked(1, get_option('press-this-extended-text'), false) . '/>';
		$html .= '<label for="press-this-extended-text"> '  . __( "Should Press This try to suggest a quote if you haven't preselected text?", 'press-this-extended' ) . '</label>';
		echo $html;
	}

	/**
	 * Echos the Blockquote wrapper setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_extended_blockquote(){
		$html = '<input type="text" id="press-this-extended-blockquote" name="press-this-extended-blockquote" value="' . esc_attr( get_option('press-this-extended-blockquote')) . '" class="regular-text ltr" />';
		$html .= '<p class="description">' . __( 'Use %1$s as a placeholder for the blockquote.', 'press-this-extended' ) .'</p>';
		echo $html;
	}

	/**
	 * Echos the Citation wrapper setting form field
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_extended_citation(){
		$html = '<input type="text" id="press-this-extended-citation" name="press-this-extended-citation" value="' . esc_attr( get_option('press-this-extended-citation')) . '" class="regular-text ltr" />';
		$html .= '<p class="description">' . __( 'Use %1$s and %2$s as a placeholders for the page URL and title, respectively.', 'press-this-extended' ) .'</p>';
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
	 * @access public
	 **/
	public function execute_html( $html, $data ){
		//$legacy = get_option( 'press-this-extended-legacy' );
		$text_discovery  = get_option( 'press-this-extended-text' );

		$html = array(
				'quote' => get_option( 'press_this_extended_blockquote'),
				'link'  => get_option( 'press-this-extended-citation'),
			);

		if ( $text_discovery == false && ! isset( $data['s'] ) ) {
			$html['quote'] = '';
		}

		/*if ( $legacy ) {
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
	 * @access public
	 **/
	public function execute() {
		//$legacy          = get_option( 'press-this-extended-legacy' );
		$text_discovery  = get_option( 'press-this-extended-text' );
		$media_discovery = get_option( 'press-this-extended-media' );

		if ( /*$legacy || ( */$media_discovery == false ) /*)*/ {
			add_filter( 'enable_press_this_media_discovery', '__return_false' );
		}

		add_filter( 'press_this_suggested_html', array( $this, 'execute_html' ), 10, 2 );

		if ( apply_filters('press_this_extended_code', false ) ){
			add_filter('wp_editor_settings', array( $this, 'enable_text_editor' ) );
			add_action('admin_print_styles', array( $this, 'press_this_text_editor_style' ) );
		}

	}

	/**
	 * Adds contextual help for Press This Extended options.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function help_tab() {
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
	 * @access public
	 **/
	public function ua_hack() {
		return 'WP Press This';
	}

	/**
	 * Adds inline styling to the Press This page via admin_print_styles hook for the text editor.
	 *
	 *
	 * @return void
	 * @see 'admin_print_styles'
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_text_editor_style(){
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
	 * @access public
	 **/
	public function enable_text_editor( $settings ){
		$settings['quicktags'] = true;
		return $settings;
	}
}

// Giddy up.
$Press_This_Extended = new Press_This_Extended;