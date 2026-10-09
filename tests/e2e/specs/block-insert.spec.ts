/**
 * Inserting the block through the editor UI and checking the front end.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { FIGURE, IMAGES, deletePost, themeUrl } from '../utils';

test.describe( 'Block insertion', () => {
	test( 'inserting the block and picking the JPEG renders a responsive image', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		let postId: number | undefined;

		try {
			await admin.createNewPost( { title: 'Insert theme image' } );

			await page
				.getByRole( 'button', { name: /Block Inserter|Toggle block inserter/i } )
				.first()
				.click();
			await page
				.getByRole( 'searchbox', { name: /Search/i } )
				.first()
				.fill( 'Theme Image' );
			await page.getByRole( 'option', { name: 'Theme Image' } ).click();

			const block = editor.canvas.locator( FIGURE );
			await expect( block ).toBeVisible();
			await expect( block ).toContainText( 'Select an image from the block settings' );

			await editor.openDocumentSettingsSidebar();
			await page
				.getByRole( 'combobox', { name: 'Theme Image' } )
				.selectOption( 'tetons' );

			await expect( block.locator( 'img' ) ).toHaveAttribute(
				'src',
				themeUrl( IMAGES.tetons.path )
			);

			postId = await editor.publishPost();
			expect( postId ).toBeGreaterThan( 0 );

			await page.goto( `/?p=${ postId }` );

			const figure = page.locator( FIGURE );
			await expect( figure ).toBeVisible();
			await expect( figure ).toHaveClass( /wp-block-happyprime-theme-image/ );

			const img = figure.locator( 'img' );
			await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.tetons.path ) );
			await expect( img ).toHaveAttribute( 'alt', IMAGES.tetons.alt );
			await expect( img ).toHaveAttribute( 'sizes', IMAGES.tetons.sizes );

			const srcset = await img.getAttribute( 'srcset' );
			const candidates = srcset?.split( ',' ).map( ( c ) => c.trim() );
			const { variations, width, path } = IMAGES.tetons;
			expect( candidates ).toEqual(
				expect.arrayContaining( [
					`${ themeUrl( path ) } ${ width }w`,
					`${ themeUrl( variations.small.path ) } ${ variations.small.width }w`,
					`${ themeUrl( variations.medium.path ) } ${ variations.medium.width }w`,
					`${ themeUrl( variations.large.path ) } ${ variations.large.width }w`,
				] )
			);
			expect( candidates ).toHaveLength( 4 );
		} finally {
			if ( postId ) {
				await deletePost( requestUtils, postId );
			}
		}
	} );
} );
