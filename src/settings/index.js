/**
 * Press This Extended - Settings Page Entry Point
 */

import { createRoot } from '@wordpress/element';
import SettingsPage from './components/SettingsPage';
import './styles.scss';

// Wait for DOM to be ready
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'press-this-extended-settings' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsPage /> );
	}
} );
