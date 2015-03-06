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
		// This only needs to fire in /wp-admin/ since PT is wp-admin exclusive.
		add_action( 'admin_init', array( $this, 'load_translations' ) , 1 );
		add_action( 'admin_init',  array( $this, 'add_settings' ) );
		add_action( 'admin_init', array( $this, 'execute' ) );
	}


	/**
 	 * Load the textdomain / translations for the plugin.
 	 *
 	 * @since 1.0.0
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
	 * @access public
	 **/
	public function add_settings() {
		add_settings_section('press-this-extended', 'Press This', null, 'writing');
		add_settings_field( 'press-this-extended-legacy', 'Legacy Mode', array( $this, 'press_this_extended_legacy' ), 'writing', 'press-this-extended');
		register_setting( 'writing', 'press-this-extended-legacy', 'intval' );
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
		$html .= '<label for="press-this-extended-legacy"> '  . __( 'Have Press This mimic behavior prior to WordPress 4.2') . '</label>';

		echo $html;
	}

	public function execute_html(){
		$legacy = get_option( 'press-this-extended-legacy' );

		if ( $legacy ) {
			$html = array(
				'quote' => '',
				'link'  => '<a href="%1$s">%2$s</a>',
			);
		}

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
			add_filter( 'press_this_suggested_html', array( $this, 'execute_html') );
			add_filter( 'enable_press_this_media_discovery', '__return_false' ); // It did exist previously but virtually no one used it.
		}
	}

}

$Press_This_Extended = new Press_This_Extended;