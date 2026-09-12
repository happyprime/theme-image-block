<?php
/**
 * Initialize the plugin.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the plugin.
 */
class Init {
	/**
	 * Add hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_block' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_editor_assets' ] );
	}

	/**
	 * Registers the theme image block from the built block.json.
	 */
	public static function register_block(): void {
		register_block_type_from_metadata(
			BLOCKS_DIR . '/build/theme-image/block.json',
			array(
				'render_callback' => [ Block::class, 'render' ],
			)
		);
	}

	/**
	 * Localize the editor script.
	 */
	public static function enqueue_editor_assets(): void {
		$script_handle = generate_block_asset_handle( 'happyprime/theme-image', 'editorScript' );

		if ( wp_script_is( $script_handle, 'registered' ) ) {
			// Pass theme URL, registered images, and styles to JavaScript.
			wp_localize_script(
				$script_handle,
				'happyprime_themeimageblock_data',
				array(
					'themeUrl' => get_template_directory_uri(),
					'images'   => Registry::get_for_editor(),
					'styles'   => StyleRegistry::get_for_editor(),
				)
			);
		}
	}
}
