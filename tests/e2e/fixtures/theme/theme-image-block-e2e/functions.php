<?php
/**
 * Registers fixture images and styles for the Theme Image Block e2e suite.
 *
 * Every image path is relative to this directory. The specs under
 * tests/e2e/specs assert against the exact slugs, paths, widths and styles
 * registered here, so change them together.
 *
 * @package ThemeImageBlockE2E
 */

use function HappyPrime\ThemeImageBlock\register_theme_image;
use function HappyPrime\ThemeImageBlock\register_theme_image_style;

add_action(
	'after_setup_theme',
	function () {
		if ( ! function_exists( 'HappyPrime\ThemeImageBlock\register_theme_image' ) ) {
			return;
		}

		register_theme_image(
			'tetons',
			[
				'title'       => 'Tetons and Snake River',
				'description' => 'Ansel Adams, 1942. Public domain.',
				'alt'         => 'The Tetons and the Snake River',
				'path'        => 'images/tetons.jpg',
				'width'       => '3000',
				'height'      => '2402',
				'variations'  => [
					'small'  => [
						'name'   => 'Small',
						'path'   => 'images/tetons-400.jpg',
						'width'  => '400',
						'height' => '320',
					],
					'medium' => [
						'name'   => 'Medium',
						'path'   => 'images/tetons-800.jpg',
						'width'  => '800',
						'height' => '641',
					],
					'large'  => [
						'name'   => 'Large',
						'path'   => 'images/tetons-1600.jpg',
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
				'title' => 'Flag of Japan',
				'alt'   => 'Flag of Japan',
				'path'  => 'images/flag-of-japan.svg',
			]
		);

		register_theme_image(
			'alpha',
			[
				'title' => 'Alpha PNG',
				'alt'   => 'Plasma with transparency',
				'path'  => 'images/alpha-600x400.png',
			]
		);

		register_theme_image(
			'webp',
			[
				'title' => 'WebP',
				'alt'   => 'Plasma in WebP',
				'path'  => 'images/webp-1800x1200.webp',
			]
		);

		register_theme_image(
			'animated',
			[
				'title'       => 'Animated GIF',
				'description' => 'Three frame plasma animation.',
				'alt'         => 'Animated plasma',
				'caption'     => 'Default caption from the registry',
				'path'        => 'images/animated-640x480.gif',
				'width'       => '640',
				'height'      => '480',
				'max_width'   => '320px',
			]
		);

		// Starts with a comment, so libmagic does not see image/svg+xml. The
		// alt text holds every character that must survive attribute escaping.
		register_theme_image(
			'exported',
			[
				'title' => 'Exported SVG',
				'alt'   => 'Tom\'s "logo" & co',
				'path'  => 'images/svg-comment-first.svg',
			]
		);

		// XML prolog first, so libmagic sees an SVG; the root has a style attribute.
		register_theme_image(
			'prolog',
			[
				'title' => 'Prolog SVG',
				'alt'   => 'Prolog',
				'path'  => 'images/svg-with-prolog-and-doctype.svg',
			]
		);

		// The registry rejects these on purpose; under WP_DEBUG the notice
		// would print ahead of the response headers and break logins.
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		// The file does not exist.
		register_theme_image(
			'missing',
			[
				'title' => 'Missing File',
				'path'  => 'images/does-not-exist.jpg',
			]
		);

		// One variation file is missing and one width is not a pixel count.
		register_theme_image(
			'brokenvars',
			[
				'title'      => 'Broken Variations',
				'alt'        => 'Tetons with broken variations',
				'path'       => 'images/tetons-800.jpg',
				'width'      => '800',
				'variations' => [
					'ghost' => [
						'name'  => 'Ghost',
						'path'  => 'images/tetons-ghost.jpg',
						'width' => '1200',
					],
					'rem'   => [
						'name'  => 'Rem',
						'path'  => 'images/tetons-400.jpg',
						'width' => '10rem',
					],
				],
			]
		);

		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		register_theme_image_style(
			'hero',
			[
				'name'  => 'Hero',
				'width' => 'clamp(10rem, 100vw, 60rem)',
			]
		);

		register_theme_image_style(
			'thumb',
			[
				'name'   => 'Thumb',
				'width'  => '150px',
				'height' => '150px',
			]
		);
	}
);
