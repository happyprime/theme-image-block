<?php
/**
 * Initialize the plugin.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

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
	 * Register the theme image and theme cover blocks.
	 */
	public static function register_block(): void {
		register_block_type_from_metadata(
			BLOCKS_DIR . '/src/theme-image/block.json',
			array(
				'render_callback' => [ Block::class, 'render' ],
			)
		);

		register_block_type_from_metadata(
			BLOCKS_DIR . '/src/theme-cover/block.json',
			array(
				'render_callback' => [ CoverBlock::class, 'render' ],
			)
		);
	}

	/**
	 * Localize the editor scripts for each block.
	 */
	public static function enqueue_editor_assets(): void {
		$data = array(
			'themeUrl' => get_template_directory_uri(),
			'images'   => Registry::get_for_editor(),
			'styles'   => StyleRegistry::get_for_editor(),
		);

		foreach ( array( 'happyprime/theme-image', 'happyprime/theme-cover' ) as $block_name ) {
			$script_handle = generate_block_asset_handle( $block_name, 'editorScript' );

			if ( wp_script_is( $script_handle, 'registered' ) ) {
				wp_localize_script(
					$script_handle,
					'happyprime_themeimageblock_data',
					$data
				);
			}
		}
	}
}
