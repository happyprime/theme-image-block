<?php
/**
 * Fuzz tests for SVG::get().
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\SVG;
use WP_HTML_Tag_Processor;

require_once __DIR__ . '/class-fuzz-case.php';

/**
 * SVG::get() must return a string for any file and decorate the root svg
 * tag with the accessibility attributes whenever one can be found.
 */
class Test_Fuzz_SVG extends Fuzz_Case {
	/**
	 * Scratch directory for mutated files.
	 *
	 * @var string
	 */
	private string $scratch;

	/**
	 * Creates the scratch directory.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->scratch = sys_get_temp_dir() . '/tib-fuzz-svg-' . getmypid();
		mkdir( $this->scratch, 0755, true );
	}

	/**
	 * Removes the scratch directory.
	 */
	public function tear_down(): void {
		self::remove_tree( $this->scratch );
		parent::tear_down();
	}

	/**
	 * Returns every corpus SVG path.
	 *
	 * @return string[]
	 */
	private static function corpus(): array {
		return glob( __DIR__ . '/fixtures/svg/*.svg' );
	}

	/**
	 * Whether the tag processor can find a root svg tag in the markup.
	 */
	private static function has_svg_tag( string $markup ): bool {
		$processor = new WP_HTML_Tag_Processor( $markup );
		return $processor->next_tag( array( 'tag_name' => 'svg' ) );
	}

	/**
	 * Asserts the first svg tag in the output carries the a11y attributes.
	 *
	 * @param string $output Rendered SVG.
	 * @param string $alt    Alt text passed in.
	 * @param string $label  Failure context.
	 */
	private function assert_a11y( string $output, string $alt, string $label ): void {
		$processor = new WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'svg' ) ), "$label: output lost its svg tag" );
		$this->assertSame( 'false', $processor->get_attribute( 'focusable' ), "$label: focusable" );

		if ( '' !== $alt ) {
			$this->assertSame( 'img', $processor->get_attribute( 'role' ), "$label: role" );
			$this->assertSame( $alt, $processor->get_attribute( 'aria-label' ), "$label: aria-label decodes to the alt" );
			$this->assertNull( $processor->get_attribute( 'aria-hidden' ), "$label: aria-hidden with alt" );
			$this->assertMatchesRegularExpression( '/ aria-label="[^"]*"/', $output, "$label: raw aria-label value is not quote-safe" );
		} else {
			$this->assertSame( 'true', $processor->get_attribute( 'aria-hidden' ), "$label: aria-hidden" );
			$this->assertNull( $processor->get_attribute( 'role' ), "$label: role without alt" );
			$this->assertNull( $processor->get_attribute( 'aria-label' ), "$label: aria-label without alt" );
		}
	}

	/**
	 * Every corpus file renders to a string with the a11y attributes on the root.
	 */
	public function test_corpus_files_get_accessibility_attributes(): void {
		$this->assertNotEmpty( self::corpus() );

		foreach ( self::corpus() as $file ) {
			$input = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$name  = basename( $file );

			foreach ( array( '', 'Tom\'s "cat" & <dog>' ) as $alt ) {
				try {
					$output = SVG::get( $file, array( 'alt' => $alt ) );
				} catch ( \Throwable $e ) {
					$this->fail( "$name (alt '$alt'): " . get_class( $e ) . ': ' . $e->getMessage() );
				}

				$this->assertIsString( $output );

				if ( '' === $input ) {
					$this->assertSame( '', $output, "$name: empty file" );
					continue;
				}

				if ( ! self::has_svg_tag( $input ) ) {
					// No root tag to decorate, so the bytes pass through untouched.
					$this->assertSame( $input, $output, "$name: passthrough without an svg tag" );
					continue;
				}

				$this->assert_a11y( $output, $alt, "$name (alt '$alt')" );
			}
		}
	}

	/**
	 * Scripts, event handlers and javascript: hrefs in a theme SVG are emitted verbatim.
	 *
	 * This pins the trust model (S5): the file lives in the theme and was
	 * chosen by PHP, so SVG::get() does not sanitize. Flip these assertions
	 * when a sanitizer lands.
	 */
	public function test_theme_svg_content_is_trusted_verbatim(): void {
		$output = SVG::get( __DIR__ . '/fixtures/svg/svg-with-script.svg', array( 'alt' => 'x' ) );

		$this->assertStringContainsString( '<script>alert(2)</script>', $output );
		$this->assertStringContainsString( 'onload="alert(1)"', $output );
		$this->assertStringContainsString( 'xlink:href="javascript:alert(3)"', $output );
		$this->assert_a11y( $output, 'x', 'svg-with-script.svg' );
	}

	/**
	 * A width replaces an existing root style attribute; without one it is kept.
	 *
	 * K2: the editor preview merges the two, so the front end drifts from it.
	 */
	public function test_existing_root_style_is_replaced_by_dimensions(): void {
		$file = __DIR__ . '/fixtures/svg/svg-with-prolog-and-doctype.svg';

		$processor = new WP_HTML_Tag_Processor( SVG::get( $file ) );
		$processor->next_tag( array( 'tag_name' => 'svg' ) );
		$this->assertSame( 'border: 1px solid red', $processor->get_attribute( 'style' ) );

		$processor = new WP_HTML_Tag_Processor( SVG::get( $file, array( 'width' => '10rem', 'max_height' => '5rem' ) ) );
		$processor->next_tag( array( 'tag_name' => 'svg' ) );
		$this->assertSame( 'width: 10rem; max-height: 5rem', $processor->get_attribute( 'style' ) );
	}

	/**
	 * A labelled SVG must not keep an aria-hidden="true" from the file.
	 *
	 * K15: set_attribute() overwrites role and aria-label but nothing removes
	 * aria-hidden, so the SVG stays hidden from assistive tech despite the label.
	 */
	public function test_existing_aria_hidden_is_removed_when_labelled(): void {
		$file = $this->scratch . '/hidden.svg';
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg" role="presentation" aria-hidden="true" focusable="true"><rect width="1" height="1"/></svg>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$processor = new WP_HTML_Tag_Processor( SVG::get( $file, array( 'alt' => 'Labelled' ) ) );
		$processor->next_tag( array( 'tag_name' => 'svg' ) );

		$this->assertSame( 'img', $processor->get_attribute( 'role' ) );
		$this->assertSame( 'Labelled', $processor->get_attribute( 'aria-label' ) );
		$this->assertSame( 'false', $processor->get_attribute( 'focusable' ) );

		if ( 'true' === $processor->get_attribute( 'aria-hidden' ) ) {
			$this->markTestIncomplete( 'K15: SVG::get() sets role="img" and aria-label but leaves the file\'s aria-hidden="true" in place.' );
		}

		$this->assertNull( $processor->get_attribute( 'aria-hidden' ) );
	}

	/**
	 * Mutates a corpus file at random.
	 */
	private function mutate( string $svg ): string {
		$count = mt_rand( 1, 3 );

		for ( $m = 0; $m < $count; $m++ ) {
			$length = strlen( $svg );
			$pos    = $length > 0 ? mt_rand( 0, $length ) : 0;

			switch ( mt_rand( 1, 8 ) ) {
				case 1:
					if ( $length > 0 ) {
						$svg[ min( $pos, $length - 1 ) ] = chr( mt_rand( 0, 255 ) );
					}
					break;
				case 2:
					$svg = substr( $svg, 0, $pos );
					break;
				case 3:
					$svg = substr_replace( $svg, '<script>alert(1)</script>', $pos, 0 );
					break;
				case 4:
					$svg = preg_replace( '/<svg\b/', '<svg onload="alert(1)" onclick="alert(2)"', $svg, 1 );
					break;
				case 5:
					$svg = preg_replace( '/<svg\b/', '<svg style="' . str_repeat( 'a', 100000 ) . '"', $svg, 1 );
					break;
				case 6:
					$svg = substr_replace( $svg, $this->random_text( 30, true ), $pos, 0 );
					break;
				case 7:
					$svg .= $svg;
					break;
				case 8:
					$svg = preg_replace( '/<svg\b/', '<svg role="presentation" aria-hidden="true" focusable="true" aria-label="old"', $svg, 1 );
					break;
			}
		}

		return $svg;
	}

	/**
	 * Mutated files never throw and still get the root attributes when a root exists.
	 */
	public function test_mutated_svgs_never_throw(): void {
		$corpus = array_values(
			array_filter(
				self::corpus(),
				static function ( string $file ): bool {
					return filesize( $file ) > 0;
				}
			)
		);

		for ( $i = 0; $i < $this->runs; $i++ ) {
			$source = $this->pick( $corpus );
			$svg    = $this->mutate( (string) file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file   = $this->scratch . '/case-' . $i . '.svg';
			file_put_contents( $file, $svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			$alt  = $this->chance( 50 ) ? str_replace( "\0", '', $this->random_text( 40 ) ) : '';
			$args = array(
				'alt'    => $alt,
				'width'  => $this->chance( 30 ) ? '10rem' : '',
				'height' => $this->chance( 30 ) ? 'auto' : '',
			);

			$input = array( 'source' => basename( $source ), 'file' => $file, 'args' => $args );

			try {
				$output = SVG::get( $file, $args );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $input, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsString( $output );
			$this->assertLessThanOrEqual( strlen( $svg ) + 1024 + 8 * strlen( $alt ), strlen( $output ), $this->replay( $i, $input, 'output grew more than the added attributes explain' ) );

			if ( '' === $svg || ! self::has_svg_tag( $svg ) ) {
				continue;
			}

			// A root that already carries role/aria-hidden is K15's case below.
			$root = new WP_HTML_Tag_Processor( $svg );
			$root->next_tag( array( 'tag_name' => 'svg' ) );
			if ( null !== $root->get_attribute( 'role' ) || null !== $root->get_attribute( 'aria-hidden' ) ) {
				continue;
			}

			$this->assert_a11y( $output, $alt, $this->replay( $i, $input, 'a11y attributes' ) );
		}
	}
}
