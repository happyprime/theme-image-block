<?php
/**
 * Registry tests.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\Registry;
use WP_UnitTestCase;

/**
 * Test Registry class.
 */
class Test_Registry extends WP_UnitTestCase {
	/**
	 * Set up test.
	 */
	public function set_up(): void {
		parent::set_up();
		Registry::clear();

		// Create test image files in theme directory.
		$theme_dir = get_template_directory();
		if ( ! file_exists( $theme_dir . '/images' ) ) {
			mkdir( $theme_dir . '/images', 0755, true );
		}

		// Create a test image file.
		file_put_contents( $theme_dir . '/images/test.jpg', 'fake image content' );
		file_put_contents( $theme_dir . '/images/test.svg', '<svg></svg>' );
	}

	/**
	 * Tear down test.
	 */
	public function tear_down(): void {
		Registry::clear();

		// Clean up test files.
		$theme_dir = get_template_directory();
		if ( file_exists( $theme_dir . '/images/test.jpg' ) ) {
			unlink( $theme_dir . '/images/test.jpg' );
		}
		if ( file_exists( $theme_dir . '/images/test.svg' ) ) {
			unlink( $theme_dir . '/images/test.svg' );
		}
		if ( file_exists( $theme_dir . '/images' ) ) {
			rmdir( $theme_dir . '/images' );
		}

		parent::tear_down();
	}

	/**
	 * Test registering a valid image returns true.
	 */
	public function test_register_valid_image_returns_true(): void {
		$result = Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test registering image without title returns false.
	 */
	public function test_register_without_title_returns_false(): void {
		$result = Registry::register(
			'test-image',
			array(
				'path' => 'images/test.jpg',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering image without path returns false.
	 */
	public function test_register_without_path_returns_false(): void {
		$result = Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering image with empty slug returns false.
	 */
	public function test_register_with_empty_slug_returns_false(): void {
		$result = Registry::register(
			'',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering non-existent file returns false.
	 */
	public function test_register_nonexistent_file_returns_false(): void {
		$result = Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/nonexistent.jpg',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering image with path traversal returns false.
	 */
	public function test_register_with_path_traversal_returns_false(): void {
		$result = Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => '../../../etc/passwd',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test registering duplicate slug returns false.
	 */
	public function test_register_duplicate_slug_returns_false(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$result = Registry::register(
			'test-image',
			array(
				'title' => 'Another Image',
				'path'  => 'images/test.svg',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns registered image.
	 */
	public function test_get_returns_registered_image(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$image = Registry::get( 'test-image' );

		$this->assertIsArray( $image );
	}

	/**
	 * Test get returns null for unregistered image.
	 */
	public function test_get_returns_null_for_unregistered_image(): void {
		$image = Registry::get( 'nonexistent' );

		$this->assertNull( $image );
	}

	/**
	 * Test get returns correct image title.
	 */
	public function test_get_returns_correct_title(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$image = Registry::get( 'test-image' );

		$this->assertSame( 'Test Image', $image['title'] );
	}

	/**
	 * Test get returns correct image path.
	 */
	public function test_get_returns_correct_path(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$image = Registry::get( 'test-image' );

		$this->assertSame( 'images/test.jpg', $image['path'] );
	}

	/**
	 * Test get_all returns empty array initially.
	 */
	public function test_get_all_returns_empty_array_initially(): void {
		$images = Registry::get_all();

		$this->assertSame( array(), $images );
	}

	/**
	 * Test get_all returns all registered images.
	 */
	public function test_get_all_returns_all_registered_images(): void {
		Registry::register(
			'image-one',
			array(
				'title' => 'Image One',
				'path'  => 'images/test.jpg',
			)
		);
		Registry::register(
			'image-two',
			array(
				'title' => 'Image Two',
				'path'  => 'images/test.svg',
			)
		);

		$images = Registry::get_all();

		$this->assertCount( 2, $images );
	}

	/**
	 * Test has returns true for registered image.
	 */
	public function test_has_returns_true_for_registered_image(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$result = Registry::has( 'test-image' );

		$this->assertTrue( $result );
	}

	/**
	 * Test has returns false for unregistered image.
	 */
	public function test_has_returns_false_for_unregistered_image(): void {
		$result = Registry::has( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister returns true for registered image.
	 */
	public function test_unregister_returns_true_for_registered_image(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$result = Registry::unregister( 'test-image' );

		$this->assertTrue( $result );
	}

	/**
	 * Test unregister returns false for unregistered image.
	 */
	public function test_unregister_returns_false_for_unregistered_image(): void {
		$result = Registry::unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister removes image from registry.
	 */
	public function test_unregister_removes_image_from_registry(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);
		Registry::unregister( 'test-image' );

		$result = Registry::has( 'test-image' );

		$this->assertFalse( $result );
	}

	/**
	 * Test registering image with variations includes name field.
	 */
	public function test_register_with_variations_includes_name(): void {
		Registry::register(
			'test-image',
			array(
				'title'      => 'Test Image',
				'path'       => 'images/test.jpg',
				'variations' => array(
					'large' => array(
						'name'   => 'Large Size',
						'path'   => 'images/test-large.jpg',
						'width'  => '1024',
						'height' => '768',
					),
				),
			)
		);

		$image = Registry::get( 'test-image' );

		$this->assertSame( 'Large Size', $image['variations']['large']['name'] );
	}

	/**
	 * Test get_for_editor returns array.
	 */
	public function test_get_for_editor_returns_array(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$images = Registry::get_for_editor();

		$this->assertIsArray( $images );
	}

	/**
	 * Test get_for_editor includes slug.
	 */
	public function test_get_for_editor_includes_slug(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$images = Registry::get_for_editor();

		$this->assertSame( 'test-image', $images[0]['slug'] );
	}

	/**
	 * Test get_for_editor includes label.
	 */
	public function test_get_for_editor_includes_label(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$images = Registry::get_for_editor();

		$this->assertSame( 'Test Image', $images[0]['label'] );
	}

	/**
	 * Test sanitize_key is applied to slug.
	 */
	public function test_slug_is_sanitized(): void {
		$result = Registry::register(
			'Test Image!',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		// sanitize_key converts uppercase to lowercase and removes special chars.
		$this->assertTrue( Registry::has( 'testimage' ) );
	}

	/**
	 * Test registered image includes all default fields.
	 */
	public function test_registered_image_includes_all_fields(): void {
		Registry::register(
			'test-image',
			array(
				'title' => 'Test Image',
				'path'  => 'images/test.jpg',
			)
		);

		$image = Registry::get( 'test-image' );

		$this->assertArrayHasKey( 'description', $image );
	}
}
