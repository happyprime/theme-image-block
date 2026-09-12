<?php
/**
 * SVG helper tests.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\SVG;
use WP_UnitTestCase;

/**
 * Test SVG class.
 */
class Test_SVG extends WP_UnitTestCase {
	/**
	 * Path to test SVG file.
	 *
	 * @var string
	 */
	private string $test_svg_path;

	/**
	 * Set up test.
	 */
	public function set_up(): void {
		parent::set_up();

		// Create test SVG file in theme directory.
		$theme_dir = get_template_directory();
		if ( ! file_exists( $theme_dir . '/images' ) ) {
			mkdir( $theme_dir . '/images', 0755, true );
		}

		$this->test_svg_path = $theme_dir . '/images/test.svg';
		file_put_contents(
			$this->test_svg_path,
			'<svg width="100" height="100"><rect width="100" height="100" fill="red"/></svg>'
		);
	}

	/**
	 * Tear down test.
	 */
	public function tear_down(): void {
		// Clean up test file.
		if ( file_exists( $this->test_svg_path ) ) {
			unlink( $this->test_svg_path );
		}

		$theme_dir = get_template_directory();
		if ( file_exists( $theme_dir . '/images' ) ) {
			rmdir( $theme_dir . '/images' );
		}

		parent::tear_down();
	}

	/**
	 * Test get returns string.
	 */
	public function test_get_returns_string(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertIsString( $result );
	}

	/**
	 * Test get returns empty string for nonexistent file.
	 */
	public function test_get_returns_empty_for_nonexistent_file(): void {
		$result = SVG::get( '/nonexistent/file.svg' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test get strips the XML prolog, comments and DOCTYPE ahead of the root.
	 */
	public function test_get_strips_prolog_comment_and_doctype(): void {
		$result = SVG::get( __DIR__ . '/fixtures/svg/svg-with-prolog-and-doctype.svg' );

		$this->assertStringStartsWith( '<svg', $result );
		$this->assertStringNotContainsString( '<?xml', $result );
		$this->assertStringNotContainsString( '<!DOCTYPE', $result );
		$this->assertStringNotContainsString( '<!--', $result );
		$this->assertStringContainsString( '<title>Prolog</title>', $result );
	}

	/**
	 * Test get strips a BOM and leading whitespace ahead of the root.
	 */
	public function test_get_strips_bom_and_leading_whitespace(): void {
		file_put_contents( $this->test_svg_path, "\xEF\xBB\xBF\n\n  <svg><rect/></svg>" );

		$this->assertStringStartsWith( '<svg', SVG::get( $this->test_svg_path ) );
	}

	/**
	 * Test get returns empty for a DOCTYPE that declares entities.
	 */
	public function test_get_returns_empty_for_doctype_with_entities(): void {
		$this->assertSame( '', SVG::get( __DIR__ . '/fixtures/svg/svg-entities.svg' ) );
	}

	/**
	 * Test get returns empty when the root element is not svg.
	 */
	public function test_get_returns_empty_when_root_is_not_svg(): void {
		file_put_contents( $this->test_svg_path, '<div><svg><rect/></svg></div>' );

		$this->assertSame( '', SVG::get( $this->test_svg_path ) );
	}

	/**
	 * Test get includes SVG tag.
	 */
	public function test_get_includes_svg_tag(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringContainsString( '<svg', $result );
	}

	/**
	 * Test get with alt adds aria-label.
	 */
	public function test_get_with_alt_adds_aria_label(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'alt' => 'Test image' )
		);

		$this->assertStringContainsString( 'aria-label="Test image"', $result );
	}

	/**
	 * Test get with alt adds role attribute.
	 */
	public function test_get_with_alt_adds_role(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'alt' => 'Test image' )
		);

		$this->assertStringContainsString( 'role="img"', $result );
	}

	/**
	 * Test get without alt adds aria-hidden.
	 */
	public function test_get_without_alt_adds_aria_hidden(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringContainsString( 'aria-hidden="true"', $result );
	}

	/**
	 * Test get without alt does not add role.
	 */
	public function test_get_without_alt_excludes_role(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringNotContainsString( 'role="img"', $result );
	}

	/**
	 * Test get adds focusable attribute.
	 */
	public function test_get_adds_focusable_attribute(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringContainsString( 'focusable="false"', $result );
	}

	/**
	 * Test get with width adds width style.
	 */
	public function test_get_with_width_adds_width_style(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '200px' )
		);

		$this->assertStringContainsString( 'width: 200px', $result );
	}

	/**
	 * Test get with height adds height style.
	 */
	public function test_get_with_height_adds_height_style(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'height' => '200px' )
		);

		$this->assertStringContainsString( 'height: 200px', $result );
	}

	/**
	 * Test get with width and height includes both in style.
	 */
	public function test_get_with_width_and_height_includes_both(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array(
				'width'  => '200px',
				'height' => '300px',
			)
		);

		$this->assertStringContainsString( 'style=', $result );
	}

	/**
	 * Test get with width includes style attribute.
	 */
	public function test_get_with_width_includes_style_attribute(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '200px' )
		);

		$this->assertStringContainsString( 'style=', $result );
	}

	/**
	 * Test get without dimensions does not add style.
	 */
	public function test_get_without_dimensions_excludes_style(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringNotContainsString( 'style=', $result );
	}

	/**
	 * Test get with empty width does not add width style.
	 */
	public function test_get_with_empty_width_excludes_width_style(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '' )
		);

		$this->assertStringNotContainsString( 'width:', $result );
	}

	/**
	 * Test get with empty height does not add height style.
	 */
	public function test_get_with_empty_height_excludes_height_style(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'height' => '' )
		);

		$this->assertStringNotContainsString( 'height:', $result );
	}

	/**
	 * Test get preserves original SVG content.
	 */
	public function test_get_preserves_original_content(): void {
		$result = SVG::get( $this->test_svg_path );

		$this->assertStringContainsString( '<rect', $result );
	}

	/**
	 * Test get with complex width value applies correctly.
	 */
	public function test_get_with_complex_width_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => 'clamp(10rem, 100vw, 60rem)' )
		);

		$this->assertStringContainsString( 'clamp(10rem, 100vw, 60rem)', $result );
	}

	/**
	 * Test get with calc height value applies correctly.
	 */
	public function test_get_with_calc_height_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'height' => 'calc(100vh - 200px)' )
		);

		$this->assertStringContainsString( 'calc(100vh - 200px)', $result );
	}

	/**
	 * Test get with auto value applies correctly.
	 */
	public function test_get_with_auto_value_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => 'auto' )
		);

		$this->assertStringContainsString( 'width: auto', $result );
	}

	/**
	 * Test get combines all attributes correctly.
	 */
	public function test_get_combines_all_attributes(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array(
				'alt'    => 'Test image',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$this->assertStringContainsString( 'aria-label=', $result );
	}

	/**
	 * Test get with alt and dimensions includes role.
	 */
	public function test_get_with_alt_and_dimensions_includes_role(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array(
				'alt'   => 'Test image',
				'width' => '100px',
			)
		);

		$this->assertStringContainsString( 'role="img"', $result );
	}

	/**
	 * Test get with percentage width applies correctly.
	 */
	public function test_get_with_percentage_width_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '100%' )
		);

		$this->assertStringContainsString( 'width: 100%', $result );
	}

	/**
	 * Test get with viewport units applies correctly.
	 */
	public function test_get_with_viewport_units_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '50vw' )
		);

		$this->assertStringContainsString( 'width: 50vw', $result );
	}

	/**
	 * Test get with rem units applies correctly.
	 */
	public function test_get_with_rem_units_applies_correctly(): void {
		$result = SVG::get(
			$this->test_svg_path,
			array( 'width' => '20rem' )
		);

		$this->assertStringContainsString( 'width: 20rem', $result );
	}
}
