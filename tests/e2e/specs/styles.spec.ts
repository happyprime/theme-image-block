/**
 * Registered styles, max_width, and block support wrapper output.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { FIGURE, IMAGES, themeUrl, withThemeImagePost } from '../utils';

const HERO_WIDTH = 'clamp(10rem, 100vw, 60rem)';

test.describe( 'Styles', () => {
	test( 'the hero style sets a clamp() width on the img', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'tetons', imageStyle: 'hero' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute( 'style', `width: ${ HERO_WIDTH }` );
			}
		);
	} );

	test( 'the thumb style sets a fixed width and height', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', imageStyle: 'thumb' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute(
					'style',
					'width: 150px; height: 150px'
				);
				await expect( img ).toHaveCSS( 'width', '150px' );
				await expect( img ).toHaveCSS( 'height', '150px' );
			}
		);
	} );

	test( 'a registered max_width becomes an inline max-width', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'animated' },
			async ( post ) => {
				await page.goto( post.link );

				const img = page.locator( `${ FIGURE } img` );
				await expect( img ).toHaveAttribute(
					'style',
					`max-width: ${ IMAGES.animated.maxWidth }`
				);
			}
		);
	} );

	test( 'style width and registered max_width combine', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'animated', imageStyle: 'hero' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } img` ) ).toHaveAttribute(
					'style',
					`width: ${ HERO_WIDTH }; max-width: ${ IMAGES.animated.maxWidth }`
				);
			}
		);
	} );

	test( 'an unknown style slug adds no inline style', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', imageStyle: 'nope' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } img` ) ).not.toHaveAttribute(
					'style'
				);
			}
		);
	} );

	test( 'the style applies to an inline SVG', async ( { page, requestUtils } ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'flag', inlineSVG: true, imageStyle: 'thumb' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } svg` ) ).toHaveAttribute(
					'style',
					'width: 150px; height: 150px'
				);
			}
		);
	} );

	test( 'align, color and spacing supports decorate the figure', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{
				themeImage: 'alpha',
				align: 'wide',
				style: {
					color: { background: '#ff0000', text: '#00ff00' },
					spacing: { padding: { top: '20px' }, margin: { bottom: '30px' } },
				},
			},
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure ).toHaveClass( /alignwide/ );
				await expect( figure ).toHaveClass( /has-background/ );
				await expect( figure ).toHaveClass( /has-text-color/ );

				const style = await figure.getAttribute( 'style' );
				expect( style ).toContain( 'background-color:#ff0000' );
				expect( style ).toContain( 'color:#00ff00' );
				expect( style ).toContain( 'padding-top:20px' );
				expect( style ).toContain( 'margin-bottom:30px' );
				await expect( figure ).toHaveCSS( 'background-color', 'rgb(255, 0, 0)' );
			}
		);
	} );

	test( 'choosing a style in the inspector updates the canvas preview', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Style preview' } );
		await editor.insertBlock( {
			name: 'happyprime/theme-image',
			attributes: { themeImage: 'tetons' },
		} );
		await editor.openDocumentSettingsSidebar();

		const style = page.getByRole( 'combobox', { name: 'Style' } );
		await expect( style.locator( 'option' ) ).toHaveText( [
			'Default',
			'Hero',
			'Thumb',
		] );

		await style.selectOption( 'thumb' );

		const img = editor.canvas.locator( `${ FIGURE } img` );
		await expect( img ).toHaveAttribute( 'src', themeUrl( IMAGES.tetons.path ) );
		await expect( img ).toHaveCSS( 'width', '150px' );
		await expect( img ).toHaveCSS( 'height', '150px' );

		expect( await editor.getEditedPostContent() ).toContain(
			'"imageStyle":"thumb"'
		);
	} );
} );
