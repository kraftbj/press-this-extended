<?php
/**
 * Press This Extended Legacy Compatibility
 *
 * Handles migration from v1.x option names to v2.x.
 *
 * @package BJGK\Press_This_Extended
 * @since 2.0.0
 */

namespace PressThisExtended;

class Legacy_Compat {

	/**
	 * Mapping of old option names to new option names.
	 *
	 * @var array
	 */
	private static $option_mapping = array(
		'press-this-extended-media'        => 'press_this_extended_media_discovery',
		'press-this-extended-text'         => 'press_this_extended_text_discovery',
		'press-this-extended-blockquote'   => 'press_this_extended_blockquote_format',
		'press-this-extended-citation'     => 'press_this_extended_citation_format',
		'press-this-extended-parent'       => 'press_this_extended_redirect_in_parent',
		'press-this-extended-save-publish' => 'press_this_extended_redirect_on_publish',
		'press-this-extended-save-draft'   => 'press_this_extended_redirect_on_draft',
	);

	/**
	 * Value transformations for certain options.
	 *
	 * @var array
	 */
	private static $value_transforms = array(
		'press-this-extended-save-draft' => array(
			'pt' => 'press-this',
		),
	);

	/**
	 * Initialize legacy compatibility.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_migrate' ), 5 );
	}

	/**
	 * Check if migration is needed and perform it.
	 */
	public static function maybe_migrate() {
		// Check if we've already migrated
		if ( get_option( 'press_this_extended_migrated_v2' ) ) {
			return;
		}

		// Check if any old options exist
		$sentinel        = 'press_this_extended_not_set';
		$has_old_options = false;
		foreach ( array_keys( self::$option_mapping ) as $old_key ) {
			if ( get_option( $old_key, $sentinel ) !== $sentinel ) {
				$has_old_options = true;
				break;
			}
		}

		if ( ! $has_old_options ) {
			// No old options, mark as migrated
			update_option( 'press_this_extended_migrated_v2', true );
			return;
		}

		self::migrate_options();
	}

	/**
	 * Migrate options from old format to new format.
	 */
	private static function migrate_options() {
		$sentinel = 'press_this_extended_not_set';

		foreach ( self::$option_mapping as $old_key => $new_key ) {
			$old_value = get_option( $old_key, $sentinel );

			if ( $old_value === $sentinel ) {
				continue;
			}

			// Transform value if needed
			if ( isset( self::$value_transforms[ $old_key ] ) ) {
				$transforms = self::$value_transforms[ $old_key ];
				if ( isset( $transforms[ $old_value ] ) ) {
					$old_value = $transforms[ $old_value ];
				}
			}

			// Only migrate if new option doesn't exist
			if ( get_option( $new_key, $sentinel ) === $sentinel ) {
				update_option( $new_key, $old_value );
			}
		}

		// Handle legacy 'press-this-extended-legacy' option
		$legacy = get_option( 'press-this-extended-legacy', 'nothing' );
		if ( $legacy === 1 || $legacy === '1' ) {
			// Legacy mode was enabled - set specific values
			update_option( 'press_this_extended_media_discovery', false );
			update_option( 'press_this_extended_text_discovery', false );
			update_option( 'press_this_extended_citation_format', '<p>via <a href="%1$s">%2$s</a></p>' );
			delete_option( 'press-this-extended-legacy' );
		} elseif ( $legacy === false || $legacy === '0' ) {
			delete_option( 'press-this-extended-legacy' );
		}

		// Clean up old options
		self::cleanup_old_options();

		// Mark as migrated
		update_option( 'press_this_extended_migrated_v2', true );
	}

	/**
	 * Remove old option keys after migration.
	 */
	private static function cleanup_old_options() {
		foreach ( array_keys( self::$option_mapping ) as $old_key ) {
			delete_option( $old_key );
		}

		// Also clean up other deprecated options
		delete_option( 'press-this-extended-editor' );
		delete_option( 'press-this-extended-legacy' );
	}

	/**
	 * Get migrated option value (for use during transition).
	 *
	 * @param string $new_key New option key.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	public static function get_option( $new_key, $default = false ) {
		$sentinel = 'press_this_extended_not_set';
		$value    = get_option( $new_key, $sentinel );

		if ( $value !== $sentinel ) {
			return $value;
		}

		// Try to find old key
		$old_key = array_search( $new_key, self::$option_mapping, true );

		if ( $old_key !== false ) {
			$old_value = get_option( $old_key, $sentinel );

			if ( $old_value !== $sentinel ) {
				// Transform if needed
				if ( isset( self::$value_transforms[ $old_key ] ) ) {
					$transforms = self::$value_transforms[ $old_key ];
					if ( isset( $transforms[ $old_value ] ) ) {
						$old_value = $transforms[ $old_value ];
					}
				}
				return $old_value;
			}
		}

		return $default;
	}
}
