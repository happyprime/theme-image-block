<?php
/**
 * Plugin Name: Theme Image Block demo images
 * Description: Registers demo images and styles for the dev site when Twenty Twenty-Five is active.
 *
 * The demo files are the e2e fixture images, which .wp-env.json mounts at
 * twentytwentyfive/theme-image-block-demo so they resolve as theme files.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Dev;

use function HappyPrime\ThemeImageBlock\register_theme_image;
use function HappyPrime\ThemeImageBlock\register_theme_image_style;

add_action( 'init', __NAMESPACE__ . '\register_demo_images' );

/**
 * Registers the demo images and styles.
 */
function register_demo_images(): void {
	// The e2e fixture theme registers its own set.
	if ( 'twentytwentyfive' !== get_template() || ! function_exists( 'HappyPrime\ThemeImageBlock\register_theme_image' ) ) {
		return;
	}

	register_theme_image(
		'tetons',
		[
			'title'       => 'Tetons and Snake River',
			'description' => 'Ansel Adams, 1942. Public domain.',
			'alt'         => 'The Tetons and the Snake River',
			'path'        => 'theme-image-block-demo/tetons.jpg',
			'width'       => '3000',
			'height'      => '2402',
			'variations'  => [
				'small'  => [
					'name'   => 'Small',
					'path'   => 'theme-image-block-demo/tetons-400.jpg',
					'width'  => '400',
					'height' => '320',
				],
				'medium' => [
					'name'   => 'Medium',
					'path'   => 'theme-image-block-demo/tetons-800.jpg',
					'width'  => '800',
					'height' => '641',
				],
				'large'  => [
					'name'   => 'Large',
					'path'   => 'theme-image-block-demo/tetons-1600.jpg',
					'width'  => '1600',
					'height' => '1281',
				],
			],
			'sizes'       => '(max-width: 800px) 100vw, 800px',
		]
	);

	register_theme_image(
		'flag',
		[
			'title' => 'Flag of Japan (SVG)',
			'alt'   => 'Flag of Japan',
			'path'  => 'theme-image-block-demo/flag-of-japan.svg',
		]
	);

	register_theme_image(
		'exported',
		[
			'title' => 'Exported SVG with a leading comment',
			'alt'   => 'Tom\'s "logo" & co',
			'path'  => 'theme-image-block-demo/svg-comment-first.svg',
		]
	);

	register_theme_image(
		'animated',
		[
			'title'     => 'Animated GIF',
			'alt'       => 'Animated plasma',
			'caption'   => 'Registered caption, shown when the block has none of its own.',
			'path'      => 'theme-image-block-demo/animated-640x480.gif',
			'width'     => '640',
			'height'    => '480',
			'max_width' => '320px',
		]
	);

	// Files Twenty Twenty-Five ships itself.
	register_theme_image(
		'botany',
		[
			'title'  => 'Botany flowers (Twenty Twenty-Five)',
			'alt'    => 'Pressed botanical flowers',
			'path'   => 'assets/images/botany-flowers.webp',
			'width'  => '1520',
			'height' => '1448',
		]
	);

	register_theme_image(
		'buttercups',
		[
			'title'  => 'Northern buttercups (Twenty Twenty-Five)',
			'alt'    => 'Northern buttercup flowers',
			'path'   => 'assets/images/northern-buttercups-flowers.webp',
			'width'  => '2880',
			'height' => '1664',
		]
	);

	register_theme_image_style(
		'hero',
		[
			'name'   => 'Hero',
			'width'  => 'clamp(10rem, 100vw, 40rem)',
			'height' => 'auto',
		]
	);

	register_theme_image_style(
		'icon',
		[
			'name'  => 'Icon',
			'width' => '8rem',
		]
	);
}
