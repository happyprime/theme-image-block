/**
 * Helpers shared by the specs.
 *
 * Image slugs, paths and widths mirror what the fixture theme registers in
 * tests/e2e/fixtures/theme/theme-image-block-e2e/functions.php.
 */
import { randomUUID } from 'node:crypto';

import type { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

export const THEME_PATH = '/wp-content/themes/theme-image-block-e2e';

export const FIGURE = 'figure.wp-block-happyprime-theme-image';

export const IMAGES = {
	tetons: {
		title: 'Tetons and Snake River',
		path: 'images/tetons.jpg',
		alt: 'The Tetons and the Snake River',
		type: 'image/jpeg',
		width: 3000,
		sizes: '(max-width: 800px) 100vw, 800px',
		variations: {
			small: { path: 'images/tetons-400.jpg', width: 400 },
			medium: { path: 'images/tetons-800.jpg', width: 800 },
			large: { path: 'images/tetons-1600.jpg', width: 1600 },
		},
	},
	flag: {
		title: 'Flag of Japan',
		path: 'images/flag-of-japan.svg',
		alt: 'Flag of Japan',
		type: 'image/svg+xml',
	},
	alpha: {
		title: 'Alpha PNG',
		path: 'images/alpha-600x400.png',
		alt: 'Plasma with transparency',
		type: 'image/png',
	},
	webp: {
		title: 'WebP',
		path: 'images/webp-1800x1200.webp',
		alt: 'Plasma in WebP',
		type: 'image/webp',
	},
	animated: {
		title: 'Animated GIF',
		path: 'images/animated-640x480.gif',
		alt: 'Animated plasma',
		type: 'image/gif',
		width: 640,
		maxWidth: '320px',
		caption: 'Default caption from the registry',
	},
	exported: {
		title: 'Exported SVG',
		path: 'images/svg-comment-first.svg',
		alt: 'Tom\'s "logo" & co',
		type: 'image/svg+xml',
	},
	prolog: {
		title: 'Prolog SVG',
		path: 'images/svg-with-prolog-and-doctype.svg',
		alt: 'Prolog',
		type: 'image/svg+xml',
	},
	brokenvars: {
		title: 'Broken Variations',
		path: 'images/tetons-800.jpg',
		alt: 'Tetons with broken variations',
		type: 'image/jpeg',
		width: 800,
		variations: {
			ghost: { path: 'images/tetons-ghost.jpg', width: 1200 },
			rem: { path: 'images/tetons-400.jpg', width: '10rem' },
		},
	},
} as const;

export type ThemeImageAttributes = Partial< {
	themeImage: string;
	imageSize: string;
	imageStyle: string;
	inlineSVG: boolean;
	linkUrl: string;
	linkTarget: string;
	linkRel: string;
	caption: string;
	showCaption: boolean;
	altText: string;
	omitAltText: boolean;
	align: string;
	style: Record< string, unknown >;
} >;

export interface CreatedPost {
	id: number;
	link: string;
}

export function themeUrl( path: string ): string {
	return `${ process.env.WP_BASE_URL }${ THEME_PATH }/${ path }`;
}

// Same escaping as @wordpress/blocks serializeAttributes(), so attribute
// values containing markup survive the block comment delimiters.
export function serializeBlock( attributes: ThemeImageAttributes ): string {
	const json = JSON.stringify( attributes )
		.replace( /--/g, '\\u002d\\u002d' )
		.replace( /</g, '\\u003c' )
		.replace( />/g, '\\u003e' )
		.replace( /&/g, '\\u0026' )
		.replace( /\\"/g, '\\u0022' );

	return `<!-- wp:happyprime/theme-image ${ json } /-->`;
}

export async function createThemeImagePost(
	requestUtils: RequestUtils,
	attributes: ThemeImageAttributes,
	title = 'Theme image e2e'
): Promise< CreatedPost > {
	// Parallel workers publishing the same title race wp_unique_post_slug(),
	// and the canonical redirect then lands on whichever post won the slug.
	const post = await requestUtils.createPost( {
		title,
		slug: `tib-e2e-${ randomUUID() }`,
		content: serializeBlock( attributes ),
		status: 'publish',
	} );

	return { id: post.id, link: post.link };
}

export async function deletePost(
	requestUtils: RequestUtils,
	id: number
): Promise< void > {
	await requestUtils.rest( {
		method: 'DELETE',
		path: `/wp/v2/posts/${ id }`,
		params: { force: true },
	} );
}

/**
 * Publishes a post holding one theme image block, runs the callback against
 * it, and deletes the post afterwards even when an assertion fails.
 */
export async function withThemeImagePost(
	requestUtils: RequestUtils,
	attributes: ThemeImageAttributes,
	callback: ( post: CreatedPost ) => Promise< void >
): Promise< void > {
	const post = await createThemeImagePost( requestUtils, attributes );

	try {
		await callback( post );
	} finally {
		await deletePost( requestUtils, post.id );
	}
}
