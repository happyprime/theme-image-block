/**
 * Logs in as admin and saves the session for the chromium project.
 *
 * A fresh wp-env can land on the database upgrade screen instead of the login
 * form, so that is handled first.
 *
 * @see https://playwright.dev/docs/auth
 */
import { test as setup, expect } from '@wordpress/e2e-test-utils-playwright';

const authFile = 'tests/e2e/.auth/admin.json';

setup( 'authenticate as admin', async ( { page, baseURL } ) => {
	await page.goto( `${ baseURL }/wp-admin/` );

	const upgradeButton = page.locator(
		'input[name="upgrade"], a.button:has-text("Update WordPress Database")'
	);
	if ( ( await upgradeButton.count() ) > 0 ) {
		await upgradeButton.click();

		const continueLink = page.locator( 'a:has-text("Continue")' );
		await continueLink.waitFor( { timeout: 30000 } );
		await continueLink.click();
	}

	const noUpdateContinue = page.locator( 'a:has-text("Continue")' );
	if (
		( await noUpdateContinue.count() ) > 0 &&
		( await page.locator( 'text=No Update Required' ).count() ) > 0
	) {
		await noUpdateContinue.click();
	}

	const loginForm = page.locator( '#loginform' );
	if ( ( await loginForm.count() ) > 0 ) {
		// Set both values together because WordPress can replace the password
		// input while its visibility control initializes.
		await loginForm.evaluate( ( form: HTMLFormElement ) => {
			const username = form.elements.namedItem( 'log' ) as HTMLInputElement;
			const password = form.elements.namedItem( 'pwd' ) as HTMLInputElement;

			username.value = 'admin';
			password.value = 'password';
		} );
		await Promise.all( [
			page.waitForURL( /\/wp-admin\/?/, { waitUntil: 'domcontentloaded' } ),
			page.locator( '#wp-submit' ).click(),
		] );
	}

	await expect( page.locator( '#wpadminbar' ) ).toBeVisible();

	await page.context().storageState( { path: authFile } );
} );
