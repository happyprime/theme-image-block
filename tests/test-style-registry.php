<?php
/**
 * StyleRegistry tests.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\StyleRegistry;
use WP_UnitTestCase;

/**
 * Test StyleRegistry class.
 */
class Test_StyleRegistry extends WP_UnitTestCase {
	/**
	 * Set up test.
	 */
	public function set_up(): void {
		parent::set_up();
		StyleRegistry::clear();
	}

	/**
	 * Tear down test.
	 */
	public function tear_down(): void {
		StyleRegistry::clear();
		parent::tear_down();
	}

	/**
	 * Test registering a valid style returns true.
	 */
	public function test_register_valid_style_returns_true(): void {
		$result = StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => 'clamp(10rem, 100vw, 60rem)',
				'height' => 'auto',
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test registering style without name returns false.
	 */
	public function test_register_without_name_returns_false(): void {
		$this->setExpectedIncorrectUsage( 'HappyPrime\ThemeImageBlock\StyleRegistry::register' );

		$result = StyleRegistry::register(
			'hero',
			array(
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering style with empty slug returns false.
	 */
	public function test_register_with_empty_slug_returns_false(): void {
		$this->setExpectedIncorrectUsage( 'HappyPrime\ThemeImageBlock\StyleRegistry::register' );

		$result = StyleRegistry::register(
			'',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering duplicate slug returns false.
	 */
	public function test_register_duplicate_slug_returns_false(): void {
		$this->setExpectedIncorrectUsage( 'HappyPrime\ThemeImageBlock\StyleRegistry::register' );

		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$result = StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero Alternate',
				'width'  => '80%',
				'height' => '400px',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns registered style.
	 */
	public function test_get_returns_registered_style(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$style = StyleRegistry::get( 'hero' );

		$this->assertIsArray( $style );
	}

	/**
	 * Test get returns null for unregistered style.
	 */
	public function test_get_returns_null_for_unregistered_style(): void {
		$style = StyleRegistry::get( 'nonexistent' );

		$this->assertNull( $style );
	}

	/**
	 * Test get returns correct style name.
	 */
	public function test_get_returns_correct_name(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$style = StyleRegistry::get( 'hero' );

		$this->assertSame( 'Hero', $style['name'] );
	}

	/**
	 * Test get returns correct width.
	 */
	public function test_get_returns_correct_width(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => 'clamp(10rem, 100vw, 60rem)',
				'height' => 'auto',
			)
		);

		$style = StyleRegistry::get( 'hero' );

		$this->assertSame( 'clamp(10rem, 100vw, 60rem)', $style['width'] );
	}

	/**
	 * Test get returns correct height.
	 */
	public function test_get_returns_correct_height(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$style = StyleRegistry::get( 'hero' );

		$this->assertSame( 'auto', $style['height'] );
	}

	/**
	 * Test get_all returns empty array initially.
	 */
	public function test_get_all_returns_empty_array_initially(): void {
		$styles = StyleRegistry::get_all();

		$this->assertSame( array(), $styles );
	}

	/**
	 * Test get_all returns all registered styles.
	 */
	public function test_get_all_returns_all_registered_styles(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);
		StyleRegistry::register(
			'thumbnail',
			array(
				'name'   => 'Thumbnail',
				'width'  => '150px',
				'height' => '150px',
			)
		);

		$styles = StyleRegistry::get_all();

		$this->assertCount( 2, $styles );
	}

	/**
	 * Test has returns true for registered style.
	 */
	public function test_has_returns_true_for_registered_style(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$result = StyleRegistry::has( 'hero' );

		$this->assertTrue( $result );
	}

	/**
	 * Test has returns false for unregistered style.
	 */
	public function test_has_returns_false_for_unregistered_style(): void {
		$result = StyleRegistry::has( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister returns true for registered style.
	 */
	public function test_unregister_returns_true_for_registered_style(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$result = StyleRegistry::unregister( 'hero' );

		$this->assertTrue( $result );
	}

	/**
	 * Test unregister returns false for unregistered style.
	 */
	public function test_unregister_returns_false_for_unregistered_style(): void {
		$result = StyleRegistry::unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister removes style from registry.
	 */
	public function test_unregister_removes_style_from_registry(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);
		StyleRegistry::unregister( 'hero' );

		$result = StyleRegistry::has( 'hero' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_for_editor returns array.
	 */
	public function test_get_for_editor_returns_array(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$styles = StyleRegistry::get_for_editor();

		$this->assertIsArray( $styles );
	}

	/**
	 * Test get_for_editor includes slug.
	 */
	public function test_get_for_editor_includes_slug(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$styles = StyleRegistry::get_for_editor();

		$this->assertSame( 'hero', $styles[0]['slug'] );
	}

	/**
	 * Test get_for_editor includes name.
	 */
	public function test_get_for_editor_includes_name(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$styles = StyleRegistry::get_for_editor();

		$this->assertSame( 'Hero', $styles[0]['name'] );
	}

	/**
	 * Test get_for_editor includes width.
	 */
	public function test_get_for_editor_includes_width(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$styles = StyleRegistry::get_for_editor();

		$this->assertSame( '100%', $styles[0]['width'] );
	}

	/**
	 * Test get_for_editor includes height.
	 */
	public function test_get_for_editor_includes_height(): void {
		StyleRegistry::register(
			'hero',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		$styles = StyleRegistry::get_for_editor();

		$this->assertSame( 'auto', $styles[0]['height'] );
	}

	/**
	 * Test sanitize_key is applied to slug.
	 */
	public function test_slug_is_sanitized(): void {
		StyleRegistry::register(
			'Hero Style!',
			array(
				'name'   => 'Hero',
				'width'  => '100%',
				'height' => 'auto',
			)
		);

		// sanitize_key converts uppercase to lowercase and removes special chars.
		$result = StyleRegistry::has( 'herostyle' );

		$this->assertTrue( $result );
	}

	/**
	 * Test style with only name registers successfully.
	 */
	public function test_register_with_only_name_succeeds(): void {
		$result = StyleRegistry::register(
			'minimal',
			array(
				'name' => 'Minimal',
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test registered style has empty width by default.
	 */
	public function test_registered_style_has_empty_width_by_default(): void {
		StyleRegistry::register(
			'minimal',
			array(
				'name' => 'Minimal',
			)
		);

		$style = StyleRegistry::get( 'minimal' );

		$this->assertSame( '', $style['width'] );
	}

	/**
	 * Test registered style has empty height by default.
	 */
	public function test_registered_style_has_empty_height_by_default(): void {
		StyleRegistry::register(
			'minimal',
			array(
				'name' => 'Minimal',
			)
		);

		$style = StyleRegistry::get( 'minimal' );

		$this->assertSame( '', $style['height'] );
	}
}
