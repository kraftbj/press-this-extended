/**
 * Press This Extended Settings Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	SelectControl,
	TextareaControl,
	Spinner,
	Notice,
	Flex,
} from '@wordpress/components';

const DEFAULT_SETTINGS = {
	media: true,
	text: true,
	blockquote: '<blockquote>%1$s</blockquote>',
	citation: '<p>Source: <em><a href="%1$s">%2$s</a></em></p>',
	parent: false,
	save_publish: 'permalink',
	save_draft: 'pt',
};

export default function SettingsPage() {
	const [ settings, setSettings ] = useState( DEFAULT_SETTINGS );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isSaved, setIsSaved ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		loadSettings();
	}, [] );

	const loadSettings = async () => {
		try {
			const response = await apiFetch( {
				path: '/press-this-extended/v1/settings',
			} );
			setSettings( { ...DEFAULT_SETTINGS, ...response } );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: __( 'Failed to load settings.', 'press-this-extended' ),
			} );
		} finally {
			setIsLoading( false );
		}
	};

	const saveSettings = async () => {
		setIsSaving( true );
		setIsSaved( false );
		setNotice( null );

		try {
			await apiFetch( {
				path: '/press-this-extended/v1/settings',
				method: 'POST',
				data: settings,
			} );
			setIsSaved( true );
			// Reset "Saved!" state after 2 seconds
			setTimeout( () => setIsSaved( false ), 2000 );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: __( 'Failed to save settings.', 'press-this-extended' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	const updateSetting = ( key, value ) => {
		setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	if ( isLoading ) {
		return (
			<Flex justify="center" style={ { padding: '40px' } }>
				<Spinner />
			</Flex>
		);
	}

	return (
		<div className="press-this-extended-settings wrap">
			<h1>{ __( 'Press This Extended Settings', 'press-this-extended' ) }</h1>

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<div className="pte-settings-cards">
				<Card>
					<CardHeader>
						<h2>{ __( 'Content Discovery', 'press-this-extended' ) }</h2>
					</CardHeader>
					<CardBody>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Media Discovery', 'press-this-extended' ) }
							help={ __( 'Should Press This suggest media to add to a new post?', 'press-this-extended' ) }
							checked={ settings.media }
							onChange={ ( value ) => updateSetting( 'media', value ) }
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Text Discovery', 'press-this-extended' ) }
							help={ __( "Should Press This try to suggest a quote if you haven't preselected text?", 'press-this-extended' ) }
							checked={ settings.text }
							onChange={ ( value ) => updateSetting( 'text', value ) }
						/>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Formatting', 'press-this-extended' ) }</h2>
					</CardHeader>
					<CardBody>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Blockquote Formatting', 'press-this-extended' ) }
							help={ __( 'Use %1$s as a placeholder for the blockquote.', 'press-this-extended' ) }
							value={ settings.blockquote }
							onChange={ ( value ) => updateSetting( 'blockquote', value ) }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Citation Formatting', 'press-this-extended' ) }
							help={ __( 'Use %1$s and %2$s as placeholders for the page URL and title, respectively.', 'press-this-extended' ) }
							value={ settings.citation }
							onChange={ ( value ) => updateSetting( 'citation', value ) }
						/>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{ __( 'Redirection', 'press-this-extended' ) }</h2>
					</CardHeader>
					<CardBody>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Redirect Parent Window', 'press-this-extended' ) }
							help={ __( 'Upon publishing or saving a draft, close the Press This popup and redirect the original tab.', 'press-this-extended' ) }
							checked={ settings.parent }
							onChange={ ( value ) => updateSetting( 'parent', value ) }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Upon Publishing...', 'press-this-extended' ) }
							help={ __( 'After publishing a post, you will be redirected to this location.', 'press-this-extended' ) }
							value={ settings.save_publish }
							options={ [
								{ label: __( 'Published Post', 'press-this-extended' ), value: 'permalink' },
								{ label: __( 'Standard Editor', 'press-this-extended' ), value: 'editor' },
							] }
							onChange={ ( value ) => updateSetting( 'save_publish', value ) }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Upon Saving a Draft...', 'press-this-extended' ) }
							help={ __( 'After saving a draft, you will be redirected to this location.', 'press-this-extended' ) }
							value={ settings.save_draft }
							options={ [
								{ label: __( 'Remain in Press This', 'press-this-extended' ), value: 'pt' },
								{ label: __( 'Standard Editor', 'press-this-extended' ), value: 'editor' },
							] }
							onChange={ ( value ) => updateSetting( 'save_draft', value ) }
						/>
					</CardBody>
				</Card>

				<p className="submit">
					<Button
						variant="primary"
						onClick={ saveSettings }
						isBusy={ isSaving }
						disabled={ isSaving || isSaved }
					>
						{ isSaving && __( 'Saving…', 'press-this-extended' ) }
						{ isSaved && __( 'Saved!', 'press-this-extended' ) }
						{ ! isSaving && ! isSaved && __( 'Save Settings', 'press-this-extended' ) }
					</Button>
				</p>
			</div>
		</div>
	);
}