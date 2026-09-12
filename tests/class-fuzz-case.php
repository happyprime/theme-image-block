<?php
/**
 * Base class for the seeded fuzz tests.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use WP_UnitTestCase;

/**
 * Seeds mt_rand() per test and turns every PHP notice into an exception.
 *
 * Replay a failure with `TIB_FUZZ_SEED=<seed> npm run test:php:fuzz`; the
 * failure message carries the seed, the iteration and the input.
 */
abstract class Fuzz_Case extends WP_UnitTestCase {
	/**
	 * Seed for this run, from TIB_FUZZ_SEED.
	 *
	 * @var int
	 */
	protected int $seed;

	/**
	 * Iterations per loop test, from TIB_FUZZ_RUNS.
	 *
	 * @var int
	 */
	protected int $runs;

	/**
	 * Characters the string generator draws from.
	 *
	 * @var string[]
	 */
	private const CHARS = array(
		'a', 'b', 'Z', '0', '9', ' ', '-', '_', '.', '/', '\\', 'é', '名', '🙂',
		'"', "'", '<', '>', '&', ';', ':', '(', ')', '%', "\n", "\t", '=', '#', '?',
	);

	/**
	 * Seeds the generator and installs the error handler.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->seed = (int) getenv( 'TIB_FUZZ_SEED' ) ?: 12345;
		$this->runs = (int) getenv( 'TIB_FUZZ_RUNS' ) ?: 300;
		mt_srand( $this->seed );

		set_error_handler(
			static function ( int $errno, string $errstr, string $errfile, int $errline ): bool {
				// Respect the @ operator the way PHP's own handler would.
				if ( ! ( error_reporting() & $errno ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
					return false;
				}
				throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );
			}
		);
	}

	/**
	 * Restores the previous error handler.
	 */
	public function tear_down(): void {
		restore_error_handler();
		parent::tear_down();
	}

	/**
	 * Returns one random element of a list.
	 *
	 * @param array<int, mixed> $pool Candidates.
	 * @return mixed
	 */
	protected function pick( array $pool ) {
		return $pool[ mt_rand( 0, count( $pool ) - 1 ) ];
	}

	/**
	 * Returns true with the given percentage probability.
	 *
	 * @param int $percent Probability, 0-100.
	 */
	protected function chance( int $percent ): bool {
		return mt_rand( 1, 100 ) <= $percent;
	}

	/**
	 * Builds a random string of up to $max characters from the fuzz charset.
	 *
	 * @param int  $max       Maximum length in characters.
	 * @param bool $null_byte Whether a null byte may appear.
	 */
	protected function random_text( int $max, bool $null_byte = false ): string {
		$chars = self::CHARS;
		if ( $null_byte ) {
			$chars[] = "\0";
		}

		$length = mt_rand( 0, $max );
		$out    = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$out .= $chars[ mt_rand( 0, count( $chars ) - 1 ) ];
		}

		return $out;
	}

	/**
	 * Formats a failure message that says how to replay the case.
	 *
	 * @param int    $iteration Loop index.
	 * @param mixed  $input     The generated input.
	 * @param string $what      What went wrong.
	 */
	protected function replay( int $iteration, $input, string $what ): string {
		return sprintf(
			"%s\nReplay: TIB_FUZZ_SEED=%d TIB_FUZZ_RUNS=%d npm run test:php:fuzz (iteration %d)\nInput: %s",
			$what,
			$this->seed,
			$this->runs,
			$iteration,
			var_export( $input, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		);
	}

	/**
	 * Deletes a directory tree without following symlinks.
	 *
	 * @param string $dir Directory to remove.
	 */
	protected static function remove_tree( string $dir ): void {
		if ( is_link( $dir ) || is_file( $dir ) ) {
			unlink( $dir );
			return;
		}
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( scandir( $dir ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			self::remove_tree( $dir . '/' . $entry );
		}
		rmdir( $dir );
	}
}
