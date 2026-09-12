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
				'title'   => 'Unsized',
				'path'    => 'images/photo.jpg',
				'sizes'   => '100vw',
				'caption' => 'Registered & <em>plain</em> caption',
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
	 * Test the registered caption renders when the block has none.
	 */
	public function test_registered_caption_is_the_fallback(): void {
		$html = $this->render( array( 'themeImage' => 'unsized', 'showCaption' => true ) );
		$this->assertStringContainsString( '<figcaption>Registered &amp; plain caption</figcaption>', $html );

		$html = $this->render( array( 'themeImage' => 'unsized', 'showCaption' => true, 'caption' => 'Typed <strong>caption</strong>' ) );
		$this->assertStringContainsString( '<figcaption>Typed <strong>caption</strong></figcaption>', $html );

		$html = $this->render( array( 'themeImage' => 'unsized', 'caption' => 'Typed' ) );
		$this->assertStringNotContainsString( '<figcaption', $html );
	}

	/**
	 * Test width and height attributes come from the displayed file's pixel dimensions.
	 */
	public function test_img_dimensions_from_the_displayed_file(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo' ) ), 'img' );
		$this->assertSame( '1600', $img['width'] );
		$this->assertSame( '1066', $img['height'] );

		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo', 'imageSize' => 'medium' ) ), 'img' );
		$this->assertSame( '800', $img['width'] );
		$this->assertSame( '533', $img['height'] );

		// One dimension alone, or a non-pixel value, emits neither.
		foreach ( array( array( 'themeImage' => 'photo', 'imageSize' => 'again' ), array( 'themeImage' => 'photo', 'imageSize' => 'rem' ), array( 'themeImage' => 'logo' ) ) as $attrs ) {
			$img = $this->tag_attributes( $this->render( $attrs ), 'img' );
			$this->assertArrayNotHasKey( 'width', $img, wp_json_encode( $attrs ) );
			$this->assertArrayNotHasKey( 'height', $img, wp_json_encode( $attrs ) );
		}
	}

	/**
	 * Test only browsing context keywords are accepted as the link target.
	 */
	public function test_link_target_is_constrained(): void {
		$base = array( 'themeImage' => 'photo', 'linkUrl' => 'https://example.com/' );

		$a = $this->tag_attributes( $this->render( $base + array( 'linkTarget' => '_top' ) ), 'a' );
		$this->assertSame( '_top', $a['target'] );
		$this->assertArrayNotHasKey( 'rel', $a );

		$a = $this->tag_attributes( $this->render( $base + array( 'linkTarget' => 'popup', 'linkRel' => 'nofollow' ) ), 'a' );
		$this->assertArrayNotHasKey( 'target', $a );
		$this->assertSame( 'nofollow', $a['rel'] );
	}

	/**
	 * Test a _blank target always carries noopener.
	 */
	public function test_blank_target_forces_noopener(): void {
		$base = array( 'themeImage' => 'photo', 'linkUrl' => 'https://example.com/', 'linkTarget' => '_blank' );

		$a = $this->tag_attributes( $this->render( $base ), 'a' );
		$this->assertSame( 'noopener', $a['rel'] );

		$a = $this->tag_attributes( $this->render( $base + array( 'linkRel' => 'nofollow' ) ), 'a' );
		$this->assertSame( 'nofollow noopener', $a['rel'] );

		$a = $this->tag_attributes( $this->render( $base + array( 'linkRel' => 'noopener noreferrer' ) ), 'a' );
		$this->assertSame( 'noopener noreferrer', $a['rel'] );
	}

	/**
	 * Test a style value of 0 is emitted.
	 */
	public function test_zero_style_value_is_emitted(): void {
		StyleRegistry::register( 'flat', array( 'name' => 'Flat', 'height' => '0' ) );

		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'photo', 'imageStyle' => 'flat' ) ), 'img' );

		$this->assertSame( 'height: 0', $img['style'] );
	}

	/**
	 * Test the img pass leaves an img inside an inlined SVG alone.
	 */
	public function test_inline_svg_foreign_object_img_is_untouched(): void {
		copy( __DIR__ . '/fixtures/svg/svg-nested.svg', $this->theme . '/images/nested.svg' );
		file_put_contents( $this->theme . '/images/nested.svg', str_replace( '<div xmlns="http://www.w3.org/1999/xhtml">hi</div>', '<img src="x.png" alt="">', file_get_contents( $this->theme . '/images/nested.svg' ) ) );
		Registry::register( 'nested', array( 'title' => 'Nested', 'path' => 'images/nested.svg', 'width' => '200', 'sizes' => '100vw' ) );
		StyleRegistry::register( 'thumb', array( 'name' => 'Thumb', 'width' => '150px' ) );

		$html = $this->render( array( 'themeImage' => 'nested', 'inlineSVG' => true, 'imageStyle' => 'thumb' ) );

		$this->assertStringContainsString( '<img src="x.png" alt="">', $html );
		$this->assertStringContainsString( 'style="width: 150px"', $html );
	}

	/**
	 * Test each path segment is URL encoded.
	 */
	public function test_file_url_encodes_path_segments(): void {
		$img = $this->tag_attributes( $this->render( array( 'themeImage' => 'odd' ) ), 'img' );

		$this->assertSame( $this->uri . '/images/spaces%20and%20%28parens%29%20%26%20ampersand.jpg', $img['src'] );
	}
}
