/**
 * Playwright global teardown.
 *
 * Stops wp-env only when global setup started it (see the marker file).
 */
import { execSync } from 'node:child_process';
import { existsSync, rmSync } from 'node:fs';

import { STARTED_MARKER } from './global-setup';

export default async function globalTeardown() {
	if ( ! existsSync( STARTED_MARKER ) ) {
		return;
	}

	rmSync( STARTED_MARKER );
	execSync( 'npm run env:stop', { stdio: 'inherit' } );
}
