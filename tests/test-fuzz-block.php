<?php
/**
 * Fuzz tests for Block::render().
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\Block;
use HappyPrime\ThemeImageBlock\Registry;
use HappyPrime\ThemeImageBlock\StyleRegistry;
use WP_Block_Supports;
use WP_Block_Type_Registry;
use WP_HTML_Processor;

require_once __DIR__ . '/class-fuzz-case.php';

/**
 * Rendering must return a string for any attributes, never throw or notice,
 * and only ever emit theme URLs, safe hrefs and no scripts or handlers.
 */
class Test_Fuzz_Block extends Fuzz_Case {
	/**
	 * Real path of the active theme directory.
	 *
	 * @var string
	 */
	private string $theme;

	/**
	 * Registers two images and two styles against copied fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		Registry::clear();
		StyleRegistry::clear();

		$this->theme = realpath( get_template_directory() );
		$images      = $this->theme . '/images';
		$fixtures    = __DIR__ . '/fixtures';

		mkdir( $images, 0755, true );
		copy( $fixtures . '/images/boundary-800x533.jpg', $images . '/photo.jpg' );
		copy( $fixtures . '/images/tiny-320x240.jpg', $images . '/photo-400.jpg' );
		copy( $fixtures . '/images/boundary-800x533.jpg', $images . '/photo-800.jpg' );
		copy( $fixtures . '/svg/svg-plain.svg', $images . '/logo.svg' );

		Registry::register(
			'photo',
			array(
				'title'      => 'Photo',
				'alt'        => 'Tom\'s "cat" & dog',
				'caption'    => 'Registered caption',
				'path'       => 'images/photo.jpg',
				'width'      => '800',
				'height'     => '533',
				'max_width'  => '40rem',
				'sizes'      => '(max-width: 800px) 100vw, 800px',
				'variations' => array(
					'small'  => array( 'name' => 'Small', 'path' => 'images/photo-400.jpg', 'width' => '400', 'height' => '300' ),
					'medium' => array( 'name' => 'Medium', 'path' => 'images/photo-800.jpg', 'width' => '800', 'height' => '533' ),
				),
			)
		);
		Registry::register( 'logo', array( 'title' => 'Logo', 'alt' => 'Logo <b>alt</b>', 'path' => 'images/logo.svg' ) );
		StyleRegistry::register( 'hero', array( 'name' => 'Hero', 'width' => 'clamp(10rem, 100vw, 60rem)' ) );
		StyleRegistry::register( 'thumb', array( 'name' => 'Thumb', 'width' => '150px', 'height' => '150px' ) );

		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'happyprime/theme-image' ),
			'The block type must be registered on init for render_block() to reach Block::render().'
		);
	}

	/**
	 * Removes the copied fixtures.
	 */
	public function tear_down(): void {
		self::remove_tree( $this->theme . '/images' );
		Registry::clear();
		StyleRegistry::clear();
		WP_Block_Supports::$block_to_render = null;
		parent::tear_down();
	}

	/**
	 * Builds a random string attribute value.
	 */
	private function random_string_attr( string $name ): string {
		switch ( $name ) {
			case 'themeImage':
				return $this->pick( array( 'photo', 'logo', 'PHOTO', 'nope', '..', 'ünï', '', 'photo ', $this->random_text( 12 ) ) );
			case 'imageSize':
				return $this->pick( array( 'original', 'small', 'medium', 'ORIGINAL', 'nope', '', '..', $this->random_text( 8 ) ) );
			case 'imageStyle':
				return $this->pick( array( '', 'hero', 'thumb', 'Hero', 'nope', $this->random_text( 8 ) ) );
			case 'linkUrl':
				return $this->pick(
					array(
						'',
						'https://example.com/?a=1&b=2',
						'http://example.com/path with spaces',
						'//example.com/protocol-relative',
						'#fragment',
						'/relative/path',
						'mailto:someone@example.com',
						'tel:+15555551212',
						'javascript:alert(1)',
						' javascript:alert(1)',
						"java\tscript:alert(1)",
						'JaVaScRiPt:alert(1)',
						'data:text/html,<script>alert(1)</script>',
						'vbscript:msgbox(1)',
						'https://例え.jp/パス',
						"https://example.com/\x00\x01\x1f",
						'"><script>alert(1)</script>',
						$this->random_text( 40 ),
					)
				);
			case 'linkTarget':
				return $this->pick( array( '', '_blank', '_top', '_self', 'x"y', '<', 'a b', $this->random_text( 10 ) ) );
			case 'linkRel':
				return $this->pick( array( '', 'nofollow', 'noopener noreferrer', "no\nfollow", '"', $this->random_text( 10 ) ) );
			case 'caption':
				return $this->pick(
					array(
						'',
						'Plain caption',
						'<em>kept</em> <script>alert(1)</script>',
						'<img src=x onerror=alert(1)>',
						'<a href="https://example.com" onclick="alert(1)">x</a>',
						'<iframe src="https://example.com"></iframe>',
						'<b>unbalanced <i>tags',
						'<svg onload=alert(1)>',
						str_repeat( 'c', 100000 ),
						$this->random_text( 60 ),
					)
				);
			case 'altText':
				return $this->pick( array( '', 'Alt', 'Tom\'s "cat" & <dog>', '"><script>alert(1)</script>', str_repeat( 'a', 5000 ), $this->random_text( 30 ) ) );
			default:
				return $this->random_text( 20 );
		}
	}

	/**
	 * Builds a random attribute array.
	 *
	 * @param bool $typed Whether every value matches the block.json type.
	 * @return array<string, mixed>
	 */
	private function random_attributes( bool $typed ): array {
		$strings = array( 'themeImage', 'imageSize', 'imageStyle', 'linkUrl', 'linkTarget', 'linkRel', 'caption', 'altText' );
		$bools   = array( 'inlineSVG', 'showCaption', 'omitAltText' );
		$wild    = array( null, array(), array( 'x' ), 0, 1, -1, 2.5, true, false, 'true', '0', new \stdClass(), str_repeat( 'w', 100000 ) );

		$attrs = array();
		foreach ( $strings as $name ) {
			if ( $this->chance( 15 ) ) {
				continue;
			}
			$attrs[ $name ] = ( $typed || $this->chance( 70 ) ) ? $this->random_string_attr( $name ) : $this->pick( $wild );
		}
		foreach ( $bools as $name ) {
			if ( $this->chance( 25 ) ) {
				continue;
			}
			$attrs[ $name ] = ( $typed || $this->chance( 70 ) ) ? $this->chance( 50 ) : $this->pick( $wild );
		}
		if ( $this->chance( 30 ) ) {
			$attrs['align'] = $this->pick( array( 'left', 'center', 'right', 'wide', 'full', 'nope', '' ) );
		}
		if ( $this->chance( 20 ) ) {
			$attrs['className'] = $this->pick( array( 'is-style-x', '"><script>', $this->random_text( 12 ) ) );
		}
		if ( $this->chance( 10 ) ) {
			$attrs['extra'] = $this->pick( $wild );
		}

		return $attrs;
	}

	/**
	 * Checks the rendered markup against every oracle.
	 *
	 * @param string               $html  Rendered output.
	 * @param int                  $i     Iteration.
	 * @param array<string, mixed> $attrs The attributes rendered.
	 */
	private function assert_safe_markup( string $html, int $i, array $attrs ): void {
		if ( '' === $html ) {
			return;
		}

		$this->assertStringStartsWith( '<figure', $html, $this->replay( $i, $attrs, 'output is neither empty nor a figure' ) );
		$this->assertStringNotContainsStringIgnoringCase( '<script', $html, $this->replay( $i, $attrs, 'script tag in output' ) );

		$uri       = get_template_directory_uri() . '/';
		$processor = WP_HTML_Processor::create_fragment( $html );
		$tags      = 0;

		$this->assertNotNull( $processor, $this->replay( $i, $attrs, 'create_fragment() refused the output' ) );

		while ( $processor->next_tag() ) {
			++$tags;
			$tag = $processor->get_tag();

			$this->assertSame( array(), $processor->get_attribute_names_with_prefix( 'on' ), $this->replay( $i, $attrs, "event handler attribute on <$tag>" ) );

			// wp_kses_post() keeps <img> in captions; only the block's own image is ours.
			if ( 'IMG' === $tag && ! in_array( 'FIGCAPTION', $processor->get_breadcrumbs(), true ) ) {
				$src = (string) $processor->get_attribute( 'src' );
				$this->assertStringStartsWith( $uri, $src, $this->replay( $i, $attrs, "img src outside the theme: $src" ) );

				$srcset = $processor->get_attribute( 'srcset' );
				if ( is_string( $srcset ) ) {
					foreach ( explode( ',', $srcset ) as $candidate ) {
						$parts = preg_split( '/\s+/', trim( $candidate ) );
						$this->assertCount( 2, $parts, $this->replay( $i, $attrs, "malformed srcset candidate: $candidate" ) );
						$this->assertStringStartsWith( $uri, $parts[0], $this->replay( $i, $attrs, "srcset url outside the theme: {$parts[0]}" ) );
						$this->assertMatchesRegularExpression( '/^\d+w$/', $parts[1], $this->replay( $i, $attrs, "srcset descriptor is not <int>w: {$parts[1]}" ) );
					}
				}
			}

			if ( 'A' === $tag ) {
				$href   = (string) $processor->get_attribute( 'href' );
				$scheme = strtolower( preg_replace( '/[\s\x00-\x1f]+/', '', $href ) );
				foreach ( array( 'javascript:', 'data:', 'vbscript:' ) as $bad ) {
					$this->assertStringStartsNotWith( $bad, $scheme, $this->replay( $i, $attrs, "unsafe href: $href" ) );
				}
			}
		}

		$this->assertNull( $processor->get_last_error(), $this->replay( $i, $attrs, 'tag processor error: ' . (string) $processor->get_last_error() ) );
		$this->assertGreaterThan( 0, $tags );
	}

	/**
	 * Through render_block(), where core validates attribute types first.
	 */
	public function test_render_block_never_throws_and_emits_safe_markup(): void {
		$rendered = 0;

		for ( $i = 0; $i < $this->runs; $i++ ) {
			$attrs = $this->random_attributes( false );
			$block = array(
				'blockName'    => 'happyprime/theme-image',
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			);

			try {
				$html = render_block( $block );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $attrs, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsString( $html );
			$this->assert_safe_markup( $html, $i, $attrs );
			if ( '' !== $html ) {
				++$rendered;
			}
		}

		$this->assertGreaterThan( 0, $rendered, 'The generator never produced a rendered block.' );
	}

	/**
	 * Direct calls with values of the declared types but hostile content.
	 */
	public function test_render_direct_with_typed_attributes_emits_safe_markup(): void {
		for ( $i = 0; $i < $this->runs; $i++ ) {
			$attrs = $this->random_attributes( true );

			WP_Block_Supports::$block_to_render = array( 'blockName' => 'happyprime/theme-image', 'attrs' => $attrs );

			try {
				$html = Block::render( $attrs, '' );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $attrs, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsString( $html );
			$this->assert_safe_markup( $html, $i, $attrs );
		}
	}

	/**
	 * Direct calls with values of the wrong type return a string without notices.
	 *
	 * Only direct PHP callers can reach this: WP_Block drops attributes that
	 * fail block.json validation before the render callback runs.
	 */
	public function test_render_direct_with_wrong_types_does_not_throw(): void {
		$wild = array( null, array(), array( 'x' ), 0, 1, true, new \stdClass() );

		foreach ( array( 'themeImage', 'imageSize', 'imageStyle', 'inlineSVG', 'linkUrl', 'linkTarget', 'linkRel', 'caption', 'showCaption', 'altText', 'omitAltText' ) as $name ) {
			foreach ( $wild as $value ) {
				$attrs = array(
					'themeImage'  => 'photo',
					'linkUrl'     => 'https://example.com/',
					'showCaption' => true,
					'caption'     => 'Caption',
					$name         => $value,
				);

				WP_Block_Supports::$block_to_render = array( 'blockName' => 'happyprime/theme-image', 'attrs' => $attrs );

				try {
					$html = Block::render( $attrs, '' );
				} catch ( \Throwable $e ) {
					$this->fail( sprintf( '%s => %s: %s', $name, var_export( $value, true ), $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				}

				$this->assertIsString( $html );
				$this->assert_safe_markup( $html, 0, $attrs );
			}
		}
	}

	/**
	 * Direct calls with random values of any type emit safe markup.
	 */
	public function test_render_direct_with_wild_types_emits_safe_markup(): void {
		for ( $i = 0; $i < $this->runs; $i++ ) {
			$attrs = $this->random_attributes( false );

			WP_Block_Supports::$block_to_render = array( 'blockName' => 'happyprime/theme-image', 'attrs' => $attrs );

			try {
				$html = Block::render( $attrs, '' );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $attrs, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsString( $html );
			$this->assert_safe_markup( $html, $i, $attrs );
		}
	}
}
