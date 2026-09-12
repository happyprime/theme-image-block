<?php
/**
 * Block render tests.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\Registry;
use HappyPrime\ThemeImageBlock\StyleRegistry;
use WP_Block_Supports;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * Test Block::render() through render_block().
 */
class Test_Block extends WP_UnitTestCase {
	/**
	 * Real path of the active theme directory.
	 *
	 * @var string
	 */
	private string $theme;

	/**
	 * Parent theme URI.
	 *
	 * @var string
	 */
	private string $uri;

	/**
	 * Registers images and styles against copied fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		Registry::clear();
		StyleRegistry::clear();

		$this->theme = realpath( get_template_directory() );
		$this->uri   = get_template_directory_uri();
		$images      = $this->theme . '/images';
		$fixtures    = __DIR__ . '/fixtures';

		mkdir( $images, 0755, true );
		copy( $fixtures . '/images/boundary-800x533.jpg', $images . '/photo.jpg' );
		copy( $fixtures . '/images/tiny-320x240.jpg', $images . '/photo-400.jpg' );
		copy( $fixtures . '/images/boundary-800x533.jpg', $images . '/photo-800.jpg' );
		copy( $fixtures . '/images/tiny-320x240.jpg', $images . '/spaces and (parens) & ampersand.jpg' );
		copy( $fixtures . '/svg/svg-plain.svg', $images . '/logo.svg' );

		Registry::register(
			'photo',
			array(
				'title'      => 'Photo',
				'alt'        => 'A photo',
				'path'       => 'images/photo.jpg',
				'width'      => '1600',
				'height'     => '1066',
				'sizes'      => '(max-width: 800px) 100vw, 800px',
				'variations' => array(
					'small'   => array( 'path' => 'images/photo-400.jpg', 'width' => '400', 'height' => '300' ),
					'medium'  => array( 'path' => 'images/photo-800.jpg', 'width' => '800', 'height' => '533' ),
					'again'   => array( 'path' => 'images/photo-800.jpg', 'width' => '800' ),
					'rem'     => array( 'path' => 'images/photo-400.jpg', 'width' => '10rem' ),
					'nowidth' => array( 'path' => 'images/photo-400.jpg' ),
				),
			)
		);
		Registry::register(
			'logo',
			array(
				'title'      => 'Logo',
				'alt'        => 'Logo',
				'path'       => 'images/logo.svg',
				'width'      => '200',
				'sizes'      => '100vw',
				'variations' => array(
					'small' => array( 'path' => 'images/logo.svg', 'width' => '100' ),
				),
			)
		);
		Registry::register(
			'unsized',
			array(
				'title' => 'Unsized',
				'path'  => 'images/photo.jpg',
				'sizes' => '100vw',
			)
		);
		Registry::register(
			'odd',
			array(
				'title' => 'Odd name',
				'path'  => 'images/spaces and (parens) & ampersand.jpg',
			)
		);
	}

	/**
	 * Removes the copied fixtures.
	 */
	public function tear_down(): void {
		foreach ( glob( $this->theme . '/images/*' ) as $file ) {
			unlink( $file );
		}
		rmdir( $this->theme . '/images' );
		Registry::clear();
		StyleRegistry::clear();
		WP_Block_Supports::$block_to_render = null;
		parent::tear_down();
	}

	/**
	 * Renders one theme image block through render_block().
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 */
	private function render( array $attrs ): string {
		return render_block(
			array(
				'blockName'    => 'happyprime/theme-image',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Returns the attributes of the first tag of the given name, or null.
	 *
	 * @param string $html Rendered block.
	 * @param string $tag  Tag name.
	 * @return array<string, string|true>|null
	 */
	private function tag_attributes( string $html, string $tag ): ?array {
		$processor = new WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag( array( 'tag_name' => $tag ) ) ) {
			return null;
		}

		$attributes = array();
		foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$attributes[ $name ] = $processor->get_attribute( $name );
		}

		return $attributes;
	}

	/**
	 * Test srcset lists each integer width once, original first.
	 */
	public function test_srcset_lists_integer_widths_once(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo' ) ), 'img' );

		$this->assertSame( $this->uri . '/images/photo.jpg', $img['src'] );
		$this->assertSame(
			$this->uri . '/images/photo.jpg 1600w, ' . $this->uri . '/images/photo-400.jpg 400w, ' . $this->uri . '/images/photo-800.jpg 800w',
			$img['srcset']
		);
		$this->assertSame( '(max-width: 800px) 100vw, 800px', $img['sizes'] );
	}

	/**
	 * Test the selected variation is the src and caps the srcset.
	 */
	public function test_srcset_is_capped_at_the_selected_variation(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo', 'imageSize' => 'medium' ) ), 'img' );

		$this->assertSame( $this->uri . '/images/photo-800.jpg', $img['src'] );
		$this->assertSame(
			$this->uri . '/images/photo-400.jpg 400w, ' . $this->uri . '/images/photo-800.jpg 800w',
			$img['srcset']
		);
	}

	/**
	 * Test a selected variation without a pixel width yields no srcset.
	 */
	public function test_no_srcset_when_selected_variation_has_no_pixel_width(): void {
		foreach ( array( 'rem', 'nowidth' ) as $size ) {
			$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo', 'imageSize' => $size ) ), 'img' );

			$this->assertSame( $this->uri . '/images/photo-400.jpg', $img['src'], $size );
			$this->assertArrayNotHasKey( 'srcset', $img, $size );
			$this->assertArrayNotHasKey( 'sizes', $img, $size );
		}
	}

	/**
	 * Test an SVG never gets srcset or sizes.
	 */
	public function test_no_srcset_for_svg(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'logo' ) ), 'img' );

		$this->assertSame( $this->uri . '/images/logo.svg', $img['src'] );
		$this->assertArrayNotHasKey( 'srcset', $img );
		$this->assertArrayNotHasKey( 'sizes', $img );
	}

	/**
	 * Test sizes is only emitted alongside srcset.
	 */
	public function test_no_sizes_without_srcset(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'unsized' ) ), 'img' );

		$this->assertArrayNotHasKey( 'srcset', $img );
		$this->assertArrayNotHasKey( 'sizes', $img );
	}

	/**
	 * Test each path segment is URL encoded.
	 */
	public function test_file_url_encodes_path_segments(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'odd' ) ), 'img' );

		$this->assertSame( $this->uri . '/images/spaces%20and%20%28parens%29%20%26%20ampersand.jpg', $img['src'] );
	}
}
