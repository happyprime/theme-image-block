/**
 * Image size variations and their effect on src and srcset.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { FIGURE, IMAGES, themeUrl, withThemeImagePost } from '../utils';

const { path, width, variations } = IMAGES.tetons;

test.describe( 'Variations', () => {
	test( 'the medium variation is the src and caps the srcset at its width', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'tetons', imageSize: 'medium' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute(
					'src',
					themeUrl( variations.medium.path )
				);

				const srcset = await img.getAttribute( 'srcset' );
				expect( srcset ).toContain(
					`${ themeUrl( variations.small.path ) } ${ variations.small.width }w`
				);
				expect( srcset ).toContain(
					`${ themeUrl( variations.medium.path ) } ${ variations.medium.width }w`
				);
				expect( srcset ).not.toContain( variations.large.path );
				expect( srcset ).not.toContain( `${ width }w` );
			}
		);
	} );

	test( 'the large variation keeps every candidate narrower than itself', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'tetons', imageSize: 'large' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute(
					'src',
					themeUrl( variations.large.path )
				);

				const srcset = await img.getAttribute( 'srcset' );
				const candidates = srcset?.split( ',' ).map( ( c ) => c.trim() );
				expect( candidates ).toEqual(
					expect.arrayContaining( [
						`${ themeUrl( variations.small.path ) } 400w`,
						`${ themeUrl( variations.medium.path ) } 800w`,
						`${ themeUrl( variations.large.path ) } 1600w`,
					] )
				);
				expect( candidates ).toHaveLength( 3 );
				expect( srcset ).not.toContain( path );
			}
		);
	} );

	test( 'an unknown variation key falls back to the original', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'tetons', imageSize: 'nope' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute( 'src', themeUrl( path ) );

				const srcset = await img.getAttribute( 'srcset' );
				expect( srcset?.split( ',' ) ).toHaveLength( 4 );
			}
		);
	} );

	test( 'choosing a variation in the inspector updates the canvas preview', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Variation preview' } );
		await editor.insertBlock( {
			name: 'happyprime/theme-image',
			attributes: { themeImage: 'tetons' },
		} );
		await editor.openDocumentSettingsSidebar();

		const img = editor.canvas.locator( `${ FIGURE } img` );
		await expect( img ).toHaveAttribute( 'src', themeUrl( path ) );

		const variation = page.getByRole( 'combobox', { name: 'Variation' } );
		await expect( variation.locator( 'option' ) ).toHaveText( [
			'Original',
			'Small',
			'Medium',
			'Large',
		] );

		await variation.selectOption( 'medium' );
		await expect( img ).toHaveAttribute(
			'src',
			themeUrl( variations.medium.path )
		);

		expect( await editor.getEditedPostContent() ).toContain(
			'"imageSize":"medium"'
		);
	} );
} );
