/**
 * Press This Extended Settings
 *
 * React-based settings page using WordPress components.
 */

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SettingsPage from './settings';

/**
 * Initialize the settings page.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'press-this-extended-settings' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsPage /> );
	}
} );