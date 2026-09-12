/**
 * Every URL the block emits for every registered image must resolve.
 */
import type { Locator } from '@playwright/test';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { FIGURE, IMAGES, themeUrl, withThemeImagePost } from '../utils';

async function collectUrls( img: Locator ): Promise< string[] > {
	const src = ( await img.getAttribute( 'src' ) ) as string;
	const srcset = ( await img.getAttribute( 'srcset' ) ) ?? '';

	return [
		...new Set( [
			src,
			...srcset
				.split( ',' )
				.map( ( candidate ) => candidate.trim().split( /\s+/ )[ 0 ] )
				.filter( Boolean ),
		] ),
	];
}

test.describe( 'Front end image URLs', () => {
	for ( const [ slug, image ] of Object.entries( IMAGES ) ) {
		// Covered below: its variations are broken on purpose.
		if ( 'brokenvars' === slug ) {
			continue;
		}

		test( `${ slug }: src and srcset URLs return 200 with ${ image.type }`, async ( {
			page,
			requestUtils,
		} ) => {
			await withThemeImagePost(
				requestUtils,
				{ themeImage: slug },
				async ( post ) => {
					await page.goto( post.link );

					const img = page.locator( `${ FIGURE } img` );
					await expect( img ).toBeVisible();

					const urls = await collectUrls( img );
					const variations = 'variations' in image ? image.variations : {};
					expect( urls ).toHaveLength( 1 + Object.keys( variations ).length );

					for ( const url of urls ) {
						const response = await page.request.get( url );
						expect( response.status(), url ).toBe( 200 );
						expect( response.headers()[ 'content-type' ], url ).toBe( image.type );
					}

					// The browser decoded it: a broken file has no natural size.
					expect(
						await img.evaluate( ( el: HTMLImageElement ) => el.naturalWidth )
					).toBeGreaterThan( 0 );
				}
			);
		} );
	}

	test( 'a variation whose file is missing is not a srcset candidate', async ( {
		page,
		requestUtils,
	} ) => {
		test.fixme(
			true,
			'K5/S3: Registry::sanitize_variations() never checks that a variation file exists, so images/tetons-ghost.jpg is emitted as a 1200w candidate and returns 404.'
		);
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'brokenvars' },
			async ( post ) => {
				await page.goto( post.link );

				const urls = await collectUrls( page.locator( `${ FIGURE } img` ) );
				expect( urls ).toContain( themeUrl( IMAGES.brokenvars.path ) );

				for ( const url of urls ) {
					const response = await page.request.get( url );
					expect( response.status(), url ).toBe( 200 );
				}
			}
		);
	} );

	test( 'a variation width that is not a pixel count is not a srcset descriptor', async ( {
		page,
		requestUtils,
	} ) => {
		test.fixme(
			true,
			"K4: Block::render casts variation widths with (int), so a registered width of '10rem' becomes a 10w srcset descriptor."
		);
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'brokenvars' },
			async ( post ) => {
				await page.goto( post.link );

				const srcset =
					( await page.locator( `${ FIGURE } img` ).getAttribute( 'srcset' ) ) ?? '';
				const descriptors = srcset
					.split( ',' )
					.map( ( candidate ) => candidate.trim().split( /\s+/ )[ 1 ] );

				expect( descriptors ).toContain( '800w' );
				// '10rem' cast to (int) is 10, which is not what was registered.
				expect( descriptors ).not.toContain( '10w' );
			}
		);
	} );
} );
