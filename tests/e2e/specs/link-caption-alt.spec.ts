/**
 * Link wrapper, caption and alt text handling.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	FIGURE,
	IMAGES,
	deletePost,
	themeUrl,
	withThemeImagePost,
} from '../utils';

test.describe( 'Link, caption and alt text', () => {
	test( 'link attributes wrap the image in an anchor', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{
				themeImage: 'alpha',
				linkUrl: 'https://example.com/path/?a=1&b=2',
				linkTarget: '_blank',
				linkRel: 'noopener noreferrer nofollow',
			},
			async ( post ) => {
				await page.goto( post.link );

				const link = page.locator( `${ FIGURE } > a` );
				await expect( link ).toHaveAttribute(
					'href',
					'https://example.com/path/?a=1&b=2'
				);
				await expect( link ).toHaveAttribute( 'target', '_blank' );
				await expect( link ).toHaveAttribute( 'rel', 'noopener noreferrer nofollow' );
				await expect( link.locator( 'img' ) ).toHaveAttribute(
					'src',
					themeUrl( IMAGES.alpha.path )
				);
			}
		);
	} );

	test( 'a javascript: link URL is dropped', async ( { page, requestUtils } ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', linkUrl: 'javascript:alert(1)' },
			async ( post ) => {
				await page.goto( post.link );

				const figure = page.locator( FIGURE );
				await expect( figure.locator( 'img' ) ).toBeVisible();
				await expect( figure.locator( 'a' ) ).toHaveCount( 0 );
			}
		);
	} );

	test( 'the caption keeps allowed HTML and strips scripts', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{
				themeImage: 'alpha',
				showCaption: true,
				caption:
					'Hello <em>world</em> <script>alert(1)</script><a href="https://example.com" onclick="alert(2)">link</a>',
			},
			async ( post ) => {
				await page.goto( post.link );

				const caption = page.locator( `${ FIGURE } figcaption` );
				await expect( caption ).toHaveCount( 1 );
				await expect( caption.locator( 'em' ) ).toHaveText( 'world' );
				await expect( caption.locator( 'a' ) ).toHaveAttribute(
					'href',
					'https://example.com'
				);
				await expect( caption.locator( 'a' ) ).not.toHaveAttribute( 'onclick' );
				await expect( caption.locator( 'script' ) ).toHaveCount( 0 );

				const html = await caption.innerHTML();
				expect( html ).not.toContain( '<script' );
				expect( html ).not.toContain( 'onclick' );
			}
		);
	} );

	test( 'the caption is omitted unless showCaption is set', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', caption: 'Hidden caption' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } figcaption` ) ).toHaveCount( 0 );
			}
		);
	} );

	test( 'custom alt text overrides the registered default', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', altText: 'Custom "quoted" & alt' },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } img` ) ).toHaveAttribute(
					'alt',
					'Custom "quoted" & alt'
				);
			}
		);
	} );

	test( 'omitted alt text leaves an empty alt attribute', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', altText: 'Ignored', omitAltText: true },
			async ( post ) => {
				await page.goto( post.link );

				await expect( page.locator( `${ FIGURE } img` ) ).toHaveAttribute(
					'alt',
					''
				);
			}
		);
	} );

	test( 'target _blank without a rel is rendered as given', async ( {
		page,
		requestUtils,
	} ) => {
		await withThemeImagePost(
			requestUtils,
			{ themeImage: 'alpha', linkUrl: 'https://example.com/', linkTarget: '_blank' },
			async ( post ) => {
				await page.goto( post.link );

				const link = page.locator( `${ FIGURE } > a` );
				await expect( link ).toHaveAttribute( 'target', '_blank' );
				// The block does not force noopener; hand-edited markup can omit it.
				await expect( link ).not.toHaveAttribute( 'rel' );
			}
		);
	} );

	test( 'the toolbar link control writes url, target and rel', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		let postId: number | undefined;

		try {
			await admin.createNewPost( { title: 'Link control' } );
			await editor.insertBlock( {
				name: 'happyprime/theme-image',
				attributes: { themeImage: 'alpha' },
			} );
			const block = editor.canvas.locator( FIGURE );
			await block.click();

			await editor.clickBlockToolbarButton( 'Link' );
			const popover = page.locator( '.components-popover' ).last();
			await page
				.getByPlaceholder( 'Search or type URL' )
				.fill( 'https://example.com/linked' );
			await popover.getByRole( 'button', { name: 'Submit' } ).click();

			await popover.getByRole( 'button', { name: 'Edit link' } ).click();
			await popover.getByRole( 'button', { name: 'Advanced' } ).click();
			// The popover flips upward once the drawer opens, so a pointer
			// click can land on "Advanced" instead and collapse it again.
			const newTab = page.getByRole( 'checkbox', { name: 'Open in new tab' } );
			await expect( newTab ).toBeVisible();
			await newTab.dispatchEvent( 'click' );
			await expect( newTab ).toBeChecked();
			await popover.getByRole( 'button', { name: 'Apply' } ).click();
			await page.keyboard.press( 'Escape' );

			const link = block.locator( 'a' );
			await expect( link ).toHaveAttribute( 'href', 'https://example.com/linked' );
			await expect( link ).toHaveAttribute( 'target', '_blank' );
			await expect( link ).toHaveAttribute( 'rel', 'noopener noreferrer' );

			// Clicking the image in the canvas must not follow the link.
			const editorUrl = page.url();
			await block.click( { position: { x: 100, y: 100 } } );
			await page.waitForTimeout( 500 );
			expect( page.url() ).toBe( editorUrl );
			await expect( block ).toBeVisible();
			await expect( block.locator( 'img' ) ).toHaveAttribute(
				'src',
				themeUrl( IMAGES.alpha.path )
			);

			postId = await editor.publishPost();
			await page.goto( `/?p=${ postId }` );

			const frontLink = page.locator( `${ FIGURE } > a` );
			await expect( frontLink ).toHaveAttribute( 'href', 'https://example.com/linked' );
			await expect( frontLink ).toHaveAttribute( 'target', '_blank' );
			await expect( frontLink ).toHaveAttribute( 'rel', 'noopener noreferrer' );
			await expect( frontLink.locator( 'img' ) ).toHaveAttribute(
				'src',
				themeUrl( IMAGES.alpha.path )
			);
		} finally {
			if ( postId ) {
				await deletePost( requestUtils, postId );
			}
		}
	} );

	test( 'unlink clears every link attribute', async ( { admin, editor, page } ) => {
		await admin.createNewPost( { title: 'Unlink' } );
		await editor.insertBlock( {
			name: 'happyprime/theme-image',
			attributes: {
				themeImage: 'alpha',
				linkUrl: 'https://example.com/',
				linkTarget: '_blank',
				linkRel: 'noopener noreferrer',
			},
		} );
		const block = editor.canvas.locator( FIGURE );
		await block.click();
		await expect( block.locator( 'a' ) ).toHaveCount( 1 );

		await editor.clickBlockToolbarButton( 'Unlink' );
		await expect( block.locator( 'a' ) ).toHaveCount( 0 );

		const content = await editor.getEditedPostContent();
		expect( content ).not.toContain( 'linkUrl' );
		expect( content ).not.toContain( 'linkTarget' );
		expect( content ).not.toContain( 'linkRel' );
	} );

	test( 'the caption toolbar button toggles a rich text caption', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		let postId: number | undefined;

		try {
			await admin.createNewPost( { title: 'Caption control' } );
			await editor.insertBlock( {
				name: 'happyprime/theme-image',
				attributes: { themeImage: 'animated' },
			} );
			const block = editor.canvas.locator( FIGURE );
			await block.click();

			await editor.clickBlockToolbarButton( 'Add caption' );
			const figcaption = block.locator( 'figcaption' );
			await expect( figcaption ).toBeVisible();
			// The registered caption is only a placeholder, never content.
			await expect( figcaption ).toHaveAttribute(
				'aria-label',
				IMAGES.animated.caption
			);

			await figcaption.click();
			await page.keyboard.type( 'Photo by ' );
			await editor.clickBlockToolbarButton( 'Bold' );
			await page.keyboard.type( 'Someone' );
			await editor.clickBlockToolbarButton( 'Bold' );

			let content = await editor.getEditedPostContent();
			expect( content ).toContain( '"showCaption":true' );
			expect( content ).toContain(
				'Photo by \\u003cstrong\\u003eSomeone\\u003c/strong\\u003e'
			);

			postId = await editor.publishPost();
			await page.goto( `/?p=${ postId }` );
			const caption = page.locator( `${ FIGURE } figcaption` );
			await expect( caption ).toHaveText( 'Photo by Someone' );
			await expect( caption.locator( 'strong' ) ).toHaveText( 'Someone' );

			await page.goBack();
			await block.click();
			await editor.clickBlockToolbarButton( 'Add caption' );
			await expect( block.locator( 'figcaption' ) ).toHaveCount( 0 );
			content = await editor.getEditedPostContent();
			expect( content ).not.toContain( '"showCaption":true' );
			expect( content ).toContain( '"caption":"Photo by' );
		} finally {
			if ( postId ) {
				await deletePost( requestUtils, postId );
			}
		}
	} );
} );
