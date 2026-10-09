/**
 * Editor canvas preview, image selector contents, and console hygiene.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { ConsoleMessage } from '@playwright/test';

import { FIGURE, IMAGES, themeUrl } from '../utils';

test.describe( 'Editor preview', () => {
	test( 'the canvas shows the selected image and follows the selector', async ( {
		admin,
		editor,
		page,
	} ) => {
		const errors: string[] = [];
		const deprecations: string[] = [];

		page.on( 'console', ( message: ConsoleMessage ) => {
			const text = message.text();
			if ( message.type() === 'error' ) {
				errors.push( text );
			} else if ( message.type() === 'warning' && /deprecated/i.test( text ) ) {
				deprecations.push( text );
			}
		} );

		await admin.createNewPost( { title: 'Editor preview' } );
		await editor.insertBlock( { name: 'happyprime/theme-image' } );
		await editor.openDocumentSettingsSidebar();

		const block = editor.canvas.locator( FIGURE );
		await expect( block ).toContainText( 'Select an image from the block settings' );
		await expect( block.locator( 'img' ) ).toHaveCount( 0 );

		const selector = page.getByRole( 'combobox', { name: 'Theme Image' } );
		const options = await selector.locator( 'option' ).allTextContents();
		expect( options ).toEqual( [
			'Select an image',
			...Object.values( IMAGES ).map( ( image ) => image.title ),
		] );
		// Registered with a path that does not exist, so the registry rejected it.
		expect( options ).not.toContain( 'Missing File' );

		await selector.selectOption( 'tetons' );
		const img = block.locator( 'img' );
		await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.tetons.path ) );
		await expect( img ).toHaveAttribute( 'alt', IMAGES.tetons.alt );

		await selector.selectOption( 'webp' );
		await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.webp.path ) );
		await expect( img ).toHaveAttribute( 'alt', IMAGES.webp.alt );

		// Switching images resets the variation to the original.
		await page
			.getByRole( 'combobox', { name: 'Theme Image' } )
			.selectOption( 'tetons' );
		await page.getByRole( 'combobox', { name: 'Variation' } ).selectOption( 'small' );
		await expect( img ).toHaveAttribute(
			'src',
			themeUrl( IMAGES.tetons.variations.small.path )
		);
		await selector.selectOption( 'flag' );
		await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.flag.path ) );
		await expect( page.getByRole( 'combobox', { name: 'Variation' } ) ).toHaveCount( 0 );
		expect( await editor.getEditedPostContent() ).not.toContain( '"imageSize":"small"' );

		await page.getByRole( 'textbox', { name: 'Alt Text' } ).fill( 'Typed alt' );
		await expect( img ).toHaveAttribute( 'alt', 'Typed alt' );

		await page.getByRole( 'checkbox', { name: 'Omit alt text' } ).check();
		await expect( img ).toHaveAttribute( 'alt', '' );
		await expect( page.getByRole( 'textbox', { name: 'Alt Text' } ) ).toHaveCount( 0 );

		// The link popover mounts LinkControl, where deprecations surface.
		await block.click();
		await editor.clickBlockToolbarButton( 'Link' );
		await expect( page.getByPlaceholder( 'Search or type URL' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await editor.clickBlockToolbarButton( 'Add caption' );
		await expect( block.locator( 'figcaption' ) ).toBeVisible();

		// Reported, not failed: WordPress 7.1 component deprecations are
		// findings for the plugin's next release.
		for ( const warning of deprecations ) {
			test.info().annotations.push( { type: 'deprecation', description: warning } );
			console.log( `DEPRECATION: ${ warning }` );
		}

		expect( errors, errors.join( '\n' ) ).toEqual( [] );
	} );
} );
