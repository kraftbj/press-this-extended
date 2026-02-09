/**
 * Press This Extended - Block Picker Component
 *
 * Provides a UI for selecting which blocks are allowed in Press This.
 */

import { useState, useMemo, useCallback } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SearchControl,
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Default Press This blocks (from 2.x)
const DEFAULT_BLOCKS = [
	'core/paragraph',
	'core/heading',
	'core/image',
	'core/quote',
	'core/list',
	'core/list-item',
	'core/embed',
	'core/post-featured-image',
];

// Recommended blocks for Press This
const RECOMMENDED_BLOCKS = [
	...DEFAULT_BLOCKS,
	'core/video',
	'core/audio',
	'core/gallery',
	'core/separator',
	'core/spacer',
];

// Category display names
const CATEGORY_NAMES = {
	text: __( 'Text', 'press-this-extended' ),
	media: __( 'Media', 'press-this-extended' ),
	design: __( 'Design', 'press-this-extended' ),
	widgets: __( 'Widgets', 'press-this-extended' ),
	theme: __( 'Theme', 'press-this-extended' ),
	embed: __( 'Embeds', 'press-this-extended' ),
	common: __( 'Common', 'press-this-extended' ),
	formatting: __( 'Formatting', 'press-this-extended' ),
	layout: __( 'Layout', 'press-this-extended' ),
	reusable: __( 'Reusable', 'press-this-extended' ),
};

const BlockPicker = ( { blocks, selectedBlocks, onChange } ) => {
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ expandedCategories, setExpandedCategories ] = useState( new Set( [ 'text', 'media' ] ) );

	// Filter and group blocks
	const { filteredBlocks, groupedBlocks, categories } = useMemo( () => {
		const query = searchQuery.toLowerCase().trim();

		// Filter blocks based on search
		const filtered = query
			? blocks.filter( block =>
				block.name.toLowerCase().includes( query ) ||
				block.title.toLowerCase().includes( query ) ||
				( block.keywords || [] ).some( k => k.toLowerCase().includes( query ) )
			)
			: blocks;

		// Group by category
		const grouped = {};
		const cats = new Set();

		filtered.forEach( block => {
			const category = block.category || 'common';
			cats.add( category );

			if ( ! grouped[ category ] ) {
				grouped[ category ] = [];
			}
			grouped[ category ].push( block );
		} );

		// Sort blocks within each category
		Object.values( grouped ).forEach( categoryBlocks => {
			categoryBlocks.sort( ( a, b ) => a.title.localeCompare( b.title ) );
		} );

		return {
			filteredBlocks: filtered,
			groupedBlocks: grouped,
			categories: Array.from( cats ).sort(),
		};
	}, [ blocks, searchQuery ] );

	// Check if a block is selected
	const isSelected = useCallback( ( blockName ) => {
		return selectedBlocks.includes( blockName );
	}, [ selectedBlocks ] );

	// Toggle a single block
	const toggleBlock = useCallback( ( blockName ) => {
		const newSelection = isSelected( blockName )
			? selectedBlocks.filter( b => b !== blockName )
			: [ ...selectedBlocks, blockName ];
		onChange( newSelection );
	}, [ selectedBlocks, isSelected, onChange ] );

	// Toggle all blocks in a category
	const toggleCategory = useCallback( ( category ) => {
		const categoryBlocks = groupedBlocks[ category ] || [];
		const categoryBlockNames = categoryBlocks.map( b => b.name );
		const allSelected = categoryBlockNames.every( name => selectedBlocks.includes( name ) );

		let newSelection;
		if ( allSelected ) {
			// Deselect all in category
			newSelection = selectedBlocks.filter( b => ! categoryBlockNames.includes( b ) );
		} else {
			// Select all in category
			const toAdd = categoryBlockNames.filter( b => ! selectedBlocks.includes( b ) );
			newSelection = [ ...selectedBlocks, ...toAdd ];
		}

		onChange( newSelection );
	}, [ groupedBlocks, selectedBlocks, onChange ] );

	// Select all visible blocks
	const selectAll = useCallback( () => {
		const allNames = filteredBlocks.map( b => b.name );
		const merged = [ ...new Set( [ ...selectedBlocks, ...allNames ] ) ];
		onChange( merged );
	}, [ filteredBlocks, selectedBlocks, onChange ] );

	// Deselect all visible blocks
	const deselectAll = useCallback( () => {
		const visibleNames = new Set( filteredBlocks.map( b => b.name ) );
		const remaining = selectedBlocks.filter( b => ! visibleNames.has( b ) );
		onChange( remaining );
	}, [ filteredBlocks, selectedBlocks, onChange ] );

	// Reset to defaults
	const resetToDefaults = useCallback( () => {
		onChange( [ ...DEFAULT_BLOCKS ] );
	}, [ onChange ] );

	// Reset to recommended
	const resetToRecommended = useCallback( () => {
		onChange( [ ...RECOMMENDED_BLOCKS ] );
	}, [ onChange ] );

	// Toggle category expansion
	const toggleCategoryExpansion = useCallback( ( category ) => {
		setExpandedCategories( prev => {
			const next = new Set( prev );
			if ( next.has( category ) ) {
				next.delete( category );
			} else {
				next.add( category );
			}
			return next;
		} );
	}, [] );

	// Count selected in category
	const getSelectedCountInCategory = useCallback( ( category ) => {
		const categoryBlocks = groupedBlocks[ category ] || [];
		return categoryBlocks.filter( b => isSelected( b.name ) ).length;
	}, [ groupedBlocks, isSelected ] );

	return (
		<div className="block-picker">
			<label className="components-base-control__label">
				{ __( 'Allowed Blocks', 'press-this-extended' ) }
			</label>
			<p className="components-base-control__help">
				{ __( 'Select which blocks are available in the Press This editor.', 'press-this-extended' ) }
			</p>

			<div className="block-picker__search">
				<SearchControl
					value={ searchQuery }
					onChange={ setSearchQuery }
					placeholder={ __( 'Search blocks...', 'press-this-extended' ) }
				/>
			</div>

			<div className="block-picker__actions">
				<Button variant="secondary" size="small" onClick={ selectAll }>
					{ __( 'Select All', 'press-this-extended' ) }
				</Button>
				<Button variant="secondary" size="small" onClick={ deselectAll }>
					{ __( 'Deselect All', 'press-this-extended' ) }
				</Button>
				<Button variant="tertiary" size="small" onClick={ resetToDefaults }>
					{ __( 'Reset to Defaults', 'press-this-extended' ) }
				</Button>
				<Button variant="tertiary" size="small" onClick={ resetToRecommended }>
					{ __( 'Recommended', 'press-this-extended' ) }
				</Button>
			</div>

			<div className="block-picker__list">
				{ categories.length === 0 ? (
					<p>{ __( 'No blocks found.', 'press-this-extended' ) }</p>
				) : (
					categories.map( category => {
						const categoryBlocks = groupedBlocks[ category ] || [];
						const selectedCount = getSelectedCountInCategory( category );
						const totalCount = categoryBlocks.length;
						const isExpanded = expandedCategories.has( category ) || searchQuery;

						return (
							<div className="block-picker__category" key={ category }>
								<button
									type="button"
									className="block-picker__category-header"
									onClick={ () => toggleCategoryExpansion( category ) }
									aria-expanded={ !! isExpanded }
									style={ {
										display: 'flex',
										justifyContent: 'space-between',
										alignItems: 'center',
										cursor: 'pointer',
										padding: '8px 0',
										background: 'none',
										border: 'none',
										borderBottom: '1px solid #e0e0e0',
										width: '100%',
										font: 'inherit',
										textAlign: 'left',
									} }
								>
									<span className="block-picker__category-title">
										{ CATEGORY_NAMES[ category ] || category }
										<Text
											variant="muted"
											size="small"
											style={ { marginLeft: '8px' } }
										>
											({ selectedCount }/{ totalCount })
										</Text>
									</span>
									<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
										<Button
											variant="tertiary"
											size="small"
											onClick={ ( e ) => {
												e.stopPropagation();
												toggleCategory( category );
											} }
										>
											{ selectedCount === totalCount
												? __( 'Deselect all', 'press-this-extended' )
												: __( 'Select all', 'press-this-extended' )
											}
										</Button>
										<span style={ { fontSize: '12px' } }>
											{ isExpanded ? '▼' : '▶' }
										</span>
									</div>
								</button>

								{ isExpanded && (
									<div className="block-picker__blocks">
										{ categoryBlocks.map( block => (
											<div
												key={ block.name }
												className={ `block-picker__block ${ isSelected( block.name ) ? 'block-picker__block--selected' : '' }` }
												onClick={ () => toggleBlock( block.name ) }
											>
												<CheckboxControl
													checked={ isSelected( block.name ) }
													onChange={ () => toggleBlock( block.name ) }
													onClick={ ( e ) => e.stopPropagation() }
												/>
												<span className="block-picker__block-name">
													{ block.title }
												</span>
											</div>
										) ) }
									</div>
								) }
							</div>
						);
					} )
				) }
			</div>

			<div className="block-picker__selected-count">
				{ __( 'Selected:', 'press-this-extended' ) } { selectedBlocks.length } { __( 'blocks', 'press-this-extended' ) }
			</div>
		</div>
	);
};

export default BlockPicker;
