/**
 * SVG images rendered inline or through an img tag.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { FIGURE, IMAGES, themeUrl, withThemeImagePost } from '../utils';

test.describe( 'SVG', () => {
	test( 'inline SVG replaces the img with an accessible svg element', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag', inlineSVG: true },
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure ).toHaveClass( /has-inline-svg/ );
				await expect( figure.locator( 'img' ) ).toHaveCount( 0 );

				const svg = figure.locator( 'svg' );
				await expect( svg ).toHaveCount( 1 );
				await expect( svg ).toHaveAttribute( 'role', 'img' );
				await expect( svg ).toHaveAttribute( 'aria-label', IMAGES.flag.alt );
				await expect( svg ).toHaveAttribute( 'focusable', 'false' );
				await expect( svg ).not.toHaveAttribute( 'aria-hidden' );
				await expect( svg.locator( 'circle' ) ).toHaveAttribute( 'fill', '#bc002d' );
			}
		);
	} );

	test( 'inline SVG with omitted alt text is hidden from assistive tech', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag', inlineSVG: true, omitAltText: true },
			async ( post ) => {
				await page.goto( post.link );

				const svg = page.locator( `${ FIGURE } svg` );
				await expect( svg ).toHaveAttribute( 'aria-hidden', 'true' );
				await expect( svg ).not.toHaveAttribute( 'role' );
				await expect( svg ).not.toHaveAttribute( 'aria-label' );
				await expect( svg ).toHaveAttribute( 'focusable', 'false' );
			}
		);
	} );

	test( 'custom alt text becomes the inline SVG label', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag', inlineSVG: true, altText: 'Hinomaru' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } svg` ) ).toHaveAttribute(
					'aria-label',
					'Hinomaru'
				);
			}
		);
	} );

	test( 'an SVG without inlining renders as an img', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag' },
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure ).not.toHaveClass( /has-inline-svg/ );
				await expect( figure.locator( 'svg' ) ).toHaveCount( 0 );

				const img = figure.locator( 'img' );
				await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.flag.path ) );
				await expect( img ).toHaveAttribute( 'alt', IMAGES.flag.alt );
				await expect( img ).not.toHaveAttribute( 'srcset' );
			}
		);
	} );

	test( 'inlineSVG on a raster image still renders an img', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', inlineSVG: true },
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure ).not.toHaveClass( /has-inline-svg/ );
				await expect( figure.locator( 'img' ) ).toHaveAttribute(
					'src',
					themeUrl( IMAGES.alpha.path )
				);
			}
		);
	} );

	test( 'the img alt keeps quotes and ampersands (single escape)', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'exported' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } img` ) ).toHaveAttribute(
					'alt',
					IMAGES.exported.alt
				);
			}
		);
	} );

	test( 'an SVG that starts with a comment is still inlined', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'exported', inlineSVG: true },
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure ).toHaveClass( /has-inline-svg/ );
				await expect( figure.locator( 'svg' ) ).toHaveCount( 1 );
				await expect( figure.locator( 'img' ) ).toHaveCount( 0 );
			}
		);
	} );

	test( 'the inline SVG aria-label keeps quotes and ampersands (single escape)', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag', inlineSVG: true, altText: IMAGES.exported.alt },
			async ( post ) => {
				await page.goto( post.link );

				const svg = page.locator( `${ FIGURE } svg[role="img"]` );
				await expect( svg ).toHaveCount( 1 );
				expect( await svg.getAttribute( 'aria-label' ) ).toBe(
					IMAGES.exported.alt
				);
			}
		);
	} );

	test( 'a style width is merged with the root style the SVG already has', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'prolog', inlineSVG: true, imageStyle: 'thumb' },
			async ( post ) => {
				await page.goto( post.link );

				const svg = page.locator( `${ FIGURE } svg` );
				await expect( svg ).toHaveCount( 1 );
				const style = ( await svg.getAttribute( 'style' ) ) ?? '';
				expect( style ).toContain( 'width: 150px' );
				expect( style ).toContain( 'border: 1px solid red' );
			}
		);
	} );

	test( 'the Inline SVG toggle only shows for SVGs and inlines the preview', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Inline SVG preview' } );
		await editor.insertBlock( {
			name: 'happyprime/theme-image',
			attributes: { themeImage: 'tetons' },
		} );
		await editor.openDocumentSettingsSidebar();

		const toggle = page.getByRole( 'checkbox', { name: 'Inline SVG' } );
		await expect( toggle ).toHaveCount( 0 );

		await page
			.getByRole( 'combobox', { name: 'Theme Image' } )
			.selectOption( 'flag' );
		await expect( toggle ).toBeVisible();

		const block = editor.canvas.locator( FIGURE );
		await expect( block.locator( 'img' ) ).toHaveAttribute(
			'src',
			themeUrl( IMAGES.flag.path )
		);

		await toggle.check();
		await expect( block.locator( 'svg' ) ).toHaveCount( 1 );
		await expect( block.locator( 'img' ) ).toHaveCount( 0 );
		await expect( block ).toHaveClass( /has-inline-svg/ );

		expect( await editor.getEditedPostContent() ).toContain(
			'"inlineSVG":true'
		);
	} );
} );
