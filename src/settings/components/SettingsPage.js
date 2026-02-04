/**
 * Press This Extended - Main Settings Page Component
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	Panel,
	PanelBody,
	PanelRow,
	Spinner,
	Notice,
	ToggleControl,
	TextareaControl,
	SelectControl,
	RangeControl,
	CheckboxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import BlockPicker from './BlockPicker';

const { restUrl, nonce, version: initialVersion, postFormats } = window.pressThisExtendedSettings || {};

// Set up API fetch with nonce
apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );

const SettingsPage = () => {
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ saved, setSaved ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ settings, setSettings ] = useState( {} );
	const [ definitions, setDefinitions ] = useState( {} );
	const [ available, setAvailable ] = useState( [] );
	const [ version, setVersion ] = useState( initialVersion || {} );
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ blocks, setBlocks ] = useState( [] );

	// Load initial data
	useEffect( () => {
		const loadData = async () => {
			try {
				setLoading( true );
				setError( null );

				const [ settingsData, postTypesData, blocksData ] = await Promise.all( [
					apiFetch( { url: `${ restUrl }/settings` } ),
					apiFetch( { url: `${ restUrl }/post-types` } ),
					apiFetch( { url: `${ restUrl }/blocks` } ),
				] );

				setSettings( settingsData.values || {} );
				setDefinitions( settingsData.definitions || {} );
				setAvailable( settingsData.available || [] );
				setPostTypes( postTypesData || [] );
				setBlocks( blocksData || [] );
			} catch ( err ) {
				setError( err.message || __( 'Failed to load settings', 'press-this-extended' ) );
			} finally {
				setLoading( false );
			}
		};

		loadData();
	}, [] );

	// Update a setting
	const updateSetting = useCallback( ( key, value ) => {
		setSettings( prev => ( { ...prev, [ key ]: value } ) );
		setSaved( false );
	}, [] );

	// Save settings
	const saveSettings = useCallback( async () => {
		try {
			setSaving( true );
			setError( null );

			const response = await apiFetch( {
				url: `${ restUrl }/settings`,
				method: 'POST',
				data: settings,
			} );

			setSettings( response.values || {} );
			setSaved( true );

			// Clear saved message after 3 seconds
			setTimeout( () => setSaved( false ), 3000 );
		} catch ( err ) {
			setError( err.message || __( 'Failed to save settings', 'press-this-extended' ) );
		} finally {
			setSaving( false );
		}
	}, [ settings ] );

	// Check if a setting is available
	const isAvailable = useCallback( ( key ) => {
		return available.includes( key );
	}, [ available ] );

	// Render version notice
	const renderVersionNotice = () => {
		if ( ! version.installed ) {
			return (
				<div className="not-installed-notice">
					<div className="not-installed-notice__title">
						{ __( 'Press This Not Detected', 'press-this-extended' ) }
					</div>
					<p className="not-installed-notice__text">
						{ __( 'The Press This plugin does not appear to be installed. Please install and activate Press This to use these settings.', 'press-this-extended' ) }
					</p>
				</div>
			);
		}

		if ( version.major_version === 1 ) {
			return (
				<div className="version-notice">
					<div className="version-notice__title">
						{ __( 'Press This 1.x Detected', 'press-this-extended' ) }
					</div>
					<p className="version-notice__text">
						{ __( 'You are using Press This version 1.x. Some advanced features like block selection are only available in Press This 2.x.', 'press-this-extended' ) }
					</p>
				</div>
			);
		}

		return null;
	};

	// Render a setting field based on its definition
	const renderSettingField = ( key, def ) => {
		if ( ! isAvailable( key ) ) {
			return null;
		}

		const value = settings[ key ] ?? def.default;

		switch ( def.type ) {
			case 'boolean':
				return (
					<div className="setting-row" key={ key }>
						<ToggleControl
							label={ def.label }
							help={ def.description }
							checked={ !! value }
							onChange={ ( newValue ) => updateSetting( key, newValue ) }
						/>
					</div>
				);

			case 'string':
				// Check if it has options (select) or is a textarea
				if ( def.options ) {
					let options = [];

					if ( def.options === 'post_formats' ) {
						options = Object.entries( postFormats || {} ).map( ( [ val, label ] ) => ( {
							value: val,
							label: label,
						} ) );
					} else {
						options = Object.entries( def.options ).map( ( [ val, label ] ) => ( {
							value: val,
							label: label,
						} ) );
					}

					return (
						<div className="setting-row" key={ key }>
							<SelectControl
								label={ def.label }
								help={ def.description }
								value={ value || '' }
								options={ options }
								onChange={ ( newValue ) => updateSetting( key, newValue ) }
							/>
						</div>
					);
				}

				// Textarea for formatting strings
				return (
					<div className="setting-row formatting-textarea" key={ key }>
						<TextareaControl
							label={ def.label }
							help={ def.description }
							value={ value || '' }
							onChange={ ( newValue ) => updateSetting( key, newValue ) }
							rows={ 3 }
						/>
					</div>
				);

			case 'integer':
				return (
					<div className="setting-row" key={ key }>
						<RangeControl
							label={ def.label }
							help={ def.description }
							value={ value || def.default }
							onChange={ ( newValue ) => updateSetting( key, newValue ) }
							min={ def.min || 1 }
							max={ def.max || 100 }
						/>
					</div>
				);

			case 'array':
				// Special handling for allowed blocks
				if ( key === 'allowed_blocks' ) {
					return (
						<div className="setting-row" key={ key }>
							<BlockPicker
								blocks={ blocks }
								selectedBlocks={ value || [] }
								onChange={ ( newValue ) => updateSetting( key, newValue ) }
							/>
						</div>
					);
				}

				// Multi-select checkboxes for other arrays
				if ( def.options ) {
					return (
						<div className="setting-row" key={ key }>
							<fieldset>
								<legend className="components-base-control__label">
									{ def.label }
								</legend>
								{ def.description && (
									<p className="components-base-control__help">
										{ def.description }
									</p>
								) }
								{ Object.entries( def.options ).map( ( [ optValue, optLabel ] ) => (
									<CheckboxControl
										key={ optValue }
										label={ optLabel }
										checked={ ( value || [] ).includes( optValue ) }
										onChange={ ( checked ) => {
											const current = value || [];
											const newValue = checked
												? [ ...current, optValue ]
												: current.filter( v => v !== optValue );
											updateSetting( key, newValue );
										} }
									/>
								) ) }
							</fieldset>
						</div>
					);
				}

				return null;

			default:
				return null;
		}
	};

	// Group settings by section
	const groupedSettings = {};
	Object.entries( definitions ).forEach( ( [ key, def ] ) => {
		const section = def.section || 'general';
		if ( ! groupedSettings[ section ] ) {
			groupedSettings[ section ] = [];
		}
		groupedSettings[ section ].push( { key, def } );
	} );

	// Section titles and descriptions
	const sectionMeta = {
		content: {
			title: __( 'Content Discovery', 'press-this-extended' ),
			description: __( 'Control how Press This discovers and suggests content from source pages.', 'press-this-extended' ),
		},
		formatting: {
			title: __( 'Content Formatting', 'press-this-extended' ),
			description: __( 'Customize the HTML templates used for blockquotes and citations.', 'press-this-extended' ),
		},
		redirection: {
			title: __( 'Redirection', 'press-this-extended' ),
			description: __( 'Control where users are redirected after saving or publishing.', 'press-this-extended' ),
		},
		blocks: {
			title: __( 'Block Editor', 'press-this-extended' ),
			description: __( 'Configure which blocks are available in the Press This block editor.', 'press-this-extended' ),
		},
		post: {
			title: __( 'Post Settings', 'press-this-extended' ),
			description: __( 'Configure post type and format settings for new Press This posts.', 'press-this-extended' ),
		},
		media: {
			title: __( 'Media Settings', 'press-this-extended' ),
			description: __( 'Configure media sideloading limits and allowed file types.', 'press-this-extended' ),
		},
		advanced: {
			title: __( 'Advanced Settings', 'press-this-extended' ),
			description: __( 'Advanced configuration options. Change these only if you know what you are doing.', 'press-this-extended' ),
		},
	};

	if ( loading ) {
		return (
			<div className="press-this-extended-settings">
				<Spinner />
				<p>{ __( 'Loading settings...', 'press-this-extended' ) }</p>
			</div>
		);
	}

	return (
		<div className="press-this-extended-settings">
			{ error && (
				<Notice status="error" isDismissible onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			{ renderVersionNotice() }

			<Panel>
				{ Object.entries( sectionMeta ).map( ( [ sectionKey, meta ] ) => {
					const sectionSettings = groupedSettings[ sectionKey ] || [];
					const availableSettings = sectionSettings.filter( ( { key } ) => isAvailable( key ) );

					if ( availableSettings.length === 0 ) {
						return null;
					}

					return (
						<PanelBody
							key={ sectionKey }
							title={ meta.title }
							initialOpen={ sectionKey === 'content' }
						>
							<div className="settings-section">
								<p className="settings-section__description">
									{ meta.description }
								</p>
								{ sectionKey === 'advanced' && (
									<div className="advanced-warning">
										{ __( 'These settings can affect security and performance. Only modify them if you understand the implications.', 'press-this-extended' ) }
									</div>
								) }
								{ availableSettings.map( ( { key, def } ) =>
									renderSettingField( key, def )
								) }
							</div>
						</PanelBody>
					);
				} ) }
			</Panel>

			<div className="save-button-wrapper">
				<Button
					variant="primary"
					onClick={ saveSettings }
					disabled={ saving }
					isBusy={ saving }
				>
					{ saving
						? __( 'Saving...', 'press-this-extended' )
						: __( 'Save Settings', 'press-this-extended' )
					}
				</Button>
				{ saved && (
					<span className="save-notice">
						{ __( 'Settings saved!', 'press-this-extended' ) }
					</span>
				) }
			</div>
		</div>
	);
};

export default SettingsPage;
