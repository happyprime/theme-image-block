<?php
/**
 * Seeds the dev site with a front page that shows every Theme Image block feature.
 *
 * Run with `npm run env:seed`. Safe to repeat: the page is updated in place.
 *
 * @package HappyPrime\ThemeImageBlock
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
	exit;
}

/**
 * Returns the markup for one Theme Image block.
 *
 * @param array<string, mixed> $attrs Block attributes.
 */
function happyprime_themeimageblock_seed_block( array $attrs ): string {
	return serialize_block(
		[
			'blockName'    => 'happyprime/theme-image',
			'attrs'        => $attrs,
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		]
	);
}

/**
 * Returns a heading and paragraph that introduce one demo block.
 *
 * @param string $heading Heading text.
 * @param string $text    Paragraph text.
 */
function happyprime_themeimageblock_seed_intro( string $heading, string $text ): string {
	return sprintf(
		"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">%s</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->",
		esc_html( $heading ),
		wp_kses_post( $text )
	);
}

/**
 * Activates Twenty Twenty-Five, sets pretty permalinks, and writes the demo page.
 */
function happyprime_themeimageblock_seed(): void {
	$sections = [
		[
			'Registered image with variations',
			'The original file is the <code>src</code>; every variation with a pixel width joins the <code>srcset</code>, with the registered <code>sizes</code>.',
			[ 'themeImage' => 'tetons' ],
		],
		[
			'Selected variation',
			'Small is the <code>src</code>, and wider candidates are dropped from the <code>srcset</code>.',
			[
				'themeImage' => 'tetons',
				'imageSize'  => 'small',
			],
		],
		[
			'Registered style',
			'The Hero style sets the width with <code>clamp()</code>.',
			[
				'themeImage' => 'tetons',
				'imageSize'  => 'medium',
				'imageStyle' => 'hero',
			],
		],
		[
			'Inline SVG',
			'The SVG markup is inlined with <code>role="img"</code> and an <code>aria-label</code> from the registered alt text.',
			[
				'themeImage' => 'flag',
				'inlineSVG'  => true,
				'imageStyle' => 'icon',
			],
		],
		[
			'Inline SVG from a design tool',
			'This file opens with a comment and has a root <code>style</code>; the alt text holds quotes and an ampersand.',
			[
				'themeImage' => 'exported',
				'inlineSVG'  => true,
				'imageStyle' => 'icon',
			],
		],
		[
			'Decorative image',
			'Alt text is omitted, so the <code>img</code> gets <code>alt=""</code>.',
			[
				'themeImage'  => 'flag',
				'omitAltText' => true,
				'imageStyle'  => 'icon',
			],
		],
		[
			'Linked image',
			'A file Twenty Twenty-Five ships, linked to WordPress.org in a new tab.',
			[
				'themeImage' => 'botany',
				'linkUrl'    => 'https://wordpress.org/',
				'linkTarget' => '_blank',
				'linkRel'    => 'noopener noreferrer',
				'imageStyle' => 'hero',
			],
		],
		[
			'Registered caption',
			'The block shows a caption but has none of its own, so the registered one renders. The image has a registered <code>max_width</code>.',
			[
				'themeImage'  => 'animated',
				'showCaption' => true,
			],
		],
		[
			'Custom caption and alt text',
			'The block caption and alt text override the registered values.',
			[
				'themeImage'  => 'tetons',
				'imageSize'   => 'medium',
				'showCaption' => true,
				'caption'     => 'The Tetons, <strong>1942</strong>',
				'altText'     => 'Mountains above a winding river',
			],
		],
		[
			'Block supports',
			'Wide alignment, a background color, and padding.',
			[
				'themeImage' => 'buttercups',
				'align'      => 'wide',
				'style'      => [
					'color'   => [ 'background' => '#f6f1e7' ],
					'spacing' => [
						'padding' => [
							'top'    => '1rem',
							'right'  => '1rem',
							'bottom' => '1rem',
							'left'   => '1rem',
						],
					],
				],
			],
		],
	];

	$content = [];

	foreach ( $sections as $section ) {
		$content[] = happyprime_themeimageblock_seed_intro( $section[0], $section[1] );
		$content[] = happyprime_themeimageblock_seed_block( $section[2] );
	}

	$page = get_page_by_path( 'theme-image-block-demo' );

	// wp_insert_post() unslashes, which would strip the escaping in block attribute JSON.
	$page_id = wp_insert_post(
		wp_slash(
			[
				'ID'           => $page ? $page->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Theme Image Block demo',
				'post_name'    => 'theme-image-block-demo',
				'post_content' => implode( "\n\n", $content ),
			]
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( $page_id );
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_id );

	if ( 'twentytwentyfive' !== get_stylesheet() ) {
		WP_CLI::runcommand( 'theme activate twentytwentyfive' );
	}

	// Last, in a fresh process: writes above flush rules with this process's old structure.
	WP_CLI::runcommand( 'rewrite structure "/%postname%/" --hard' );

	WP_CLI::success( sprintf( 'Demo page %d is the front page: %s', $page_id, home_url( '/' ) ) );
}

happyprime_themeimageblock_seed();
