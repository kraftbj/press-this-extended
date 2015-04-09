<?php

/**
 * Press This Extended
 *
 * @package     BJGK\Press_this_extended
 * @version     0.1
 * @author      Brandon Kraft <public@brandonkraft.com>
 * @copyright   Copyright (c) 2015, Brandon Kraft
 * @link        http://www.brandonkraft.com/press-this-extended/
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Press This Extended
 * Plugin URI:  http://www.brandonkraft.com/press-this-extended/
 * Description: Provides options for extending and modifying the Press This feature (WP 4.2+)
 * Version:     0.1
 * Author:      Brandon Kraft
 * Author URI:  http://www.brandonkraft.com
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
	 * @access public
	 */
	public function __construct() {
		global $pagenow;

		add_action( 'admin_init',               array( $this, 'load_translations' ) , 1 );
		add_action( 'admin_init',               array( $this, 'add_settings' ) );
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

		add_settings_field( $slug . '-legacy', __( 'Legacy Mode', $slug ), array( $this, 'press_this_extended_legacy' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-legacy', 'intval' );
		add_filter( 'default_option_' . $slug . '-legacy', '__return_false' );

		add_settings_field( $slug . '-media', __( 'Media Discovery', $slug ), array( $this, 'press_this_extended_media' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-media', 'intval' );
		add_filter( 'default_option_'. $slug . '-media', '__return_true' );

		add_settings_field( $slug . '-text', __( 'Text Discovery', $slug ), array( $this, 'press_this_extended_text' ), 'writing', $slug );
		register_setting( 'writing', $slug . '-text', 'intval' );
		add_filter( 'default_option_' . $slug . '-text', '__return_true' );
	}

	/**
	 * Echos HTML for the Legacy option form field.
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 **/
	public function press_this_extended_legacy() {
		$html = '<input type="checkbox" id="press-this-extended-legacy" name="press-this-extended-legacy" value="1" ' . checked(1, get_option('press-this-extended-legacy'), false) . '/>';
		$html .= '<label for="press-this-extended-legacy"> '  . __( 'Have Press This mimic behavior prior to WordPress 4.2', 'press-this-extended' ) . '</label>';

		echo $html;
	}

	public function press_this_extended_media(){
		$html = '<input type="checkbox" id="press-this-extended-media" name="press-this-extended-media" value="1" ' . checked(1, get_option('press-this-extended-media'), false) . '/>';
		$html .= '<label for="press-this-extended-media"> '  . __( 'Should Press This suggest media to add to a new post?', 'press-this-extended' ) . '</label>';
		echo $html;
	}

	public function press_this_extended_text(){
		$html = '<input type="checkbox" id="press-this-extended-text" name="press-this-extended-text" value="1" ' . checked(1, get_option('press-this-extended-text'), false) . '/>';
		$html .= '<label for="press-this-extended-text"> '  . __( "Should Press This add a quote when you haven't selected text?", 'press-this-extended' ) . '</label>';
		echo $html;
	}

	public function execute_html_legacy( $html, $data ){
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

		return $html;
	}

	public function execute_html( $html, $data ){
		// make magic happen

		return $html;
	}

	/**
	 * Execute the settings by adding the filters specified by the various settings.
	 *
	 * @return void
	 * @since 1.0.0
	 **/
	public function execute() {
		$legacy = get_option( 'press-this-extended-legacy' );

		if ( $legacy ) {
			add_filter( 'press_this_suggested_html', array( $this, 'execute_html'), 10, 2 );
			add_filter( 'enable_press_this_media_discovery', '__return_false' ); // It did exist previously but virtually no one used it.
		}
	}

	/**
	 * Adds contextual help for Press This Extended options.
	 *
	 * @return void
	 * @since 1.0.0
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
	 **/
	public function ua_hack() {
		return 'WP Press This';
	}

}

$Press_This_Extended = new Press_This_Extended;