/**
 * Playwright configuration for the e2e suite.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
import os from 'node:os';

import { defineConfig, devices } from '@playwright/test';

// @wordpress/e2e-test-utils-playwright reads process.env.WP_BASE_URL (default
// :8889) for requestUtils REST calls, so it must match the browser's baseURL
// or fixtures land on a different WordPress than the one under test. Port 8892
// is this plugin's dedicated wp-env port (see .wp-env.json).
process.env.WP_BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8892';
const baseURL = process.env.WP_BASE_URL;

export default defineConfig( {
	testDir: './tests/e2e',
	outputDir: './tests/e2e/test-results',
	globalSetup: './tests/e2e/global-setup.ts',
	globalTeardown: './tests/e2e/global-teardown.ts',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	// Contention on the single WordPress container, not CPU, is the limit.
	workers: Math.min( 4, os.cpus().length ),
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: './tests/e2e/playwright-report', open: 'never' } ],
	],
	use: {
		baseURL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'setup',
			testDir: './tests/e2e/setup',
			testMatch: /.*\.setup\.ts/,
		},
		{
			name: 'chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: 'tests/e2e/.auth/admin.json',
			},
			dependencies: [ 'setup' ],
			testDir: './tests/e2e/specs',
		},
	],
} );
