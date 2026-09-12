/**
 * Playwright global setup.
 *
 * Starts wp-env when nothing answers on the configured port and records that
 * in a marker file so teardown stops only what this run started. A manually
 * started env is reused and left running.
 */
import { execSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';

export const STARTED_MARKER = 'tests/e2e/.env-started-by-tests';

const isUp = async ( url: string ): Promise< boolean > => {
	try {
		const response = await fetch( url, { redirect: 'manual' } );
		return response.status > 0;
	} catch {
		return false;
	}
};

export default async function globalSetup() {
	const url = process.env.WP_BASE_URL || 'http://localhost:8892';

	if ( ! ( await isUp( url ) ) ) {
		execSync( 'npm run env:start', { stdio: 'inherit' } );
		writeFileSync( STARTED_MARKER, 'started by playwright global setup\n' );
	}

	// The specs assert against the images and styles this theme registers.
	// wp-cli rather than requestUtils.activateTheme(), which scrapes
	// themes.php markup and breaks across WordPress versions.
	execSync(
		'npm run --silent env:cli -- wp theme activate theme-image-block-e2e',
		{ stdio: 'inherit' }
	);
}
