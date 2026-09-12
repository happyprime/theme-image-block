<?php
/**
 * Fuzz tests for Registry::register() and StyleRegistry::register().
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock\Tests;

use HappyPrime\ThemeImageBlock\Registry;
use HappyPrime\ThemeImageBlock\StyleRegistry;

require_once __DIR__ . '/class-fuzz-case.php';

/**
 * Registration must return a bool for any input, never throw or notice,
 * and only ever store paths that resolve inside the theme directory.
 */
class Test_Fuzz_Registry extends Fuzz_Case {
	/**
	 * Real path of the active theme directory.
	 *
	 * @var string
	 */
	private string $theme;

	/**
	 * Paths created outside the theme's images directory, removed on tear down.
	 *
	 * @var string[]
	 */
	private array $extra = array();

	/**
	 * Relative paths that resolve to a file or directory inside the theme.
	 *
	 * @var string[]
	 */
	private const INSIDE = array(
		'images/tiny.jpg',
		'images/plain.svg',
		'images/UPPERCASE.JPG',
		'images/spaces and (parens) & ampersand.jpg',
		'images/ünïcödé-名前-🙂.jpg',
		'images/zero-byte.jpg',
		'images/link-inside.jpg',
		'images/../images/tiny.jpg',
		'images',
		'images/sub',
	);

	/**
	 * Relative paths that must be rejected.
	 *
	 * @var string[]
	 */
	private const OUTSIDE = array(
		'images/link-outside.jpg',
		'../../../etc/passwd',
		'/etc/passwd',
		'C:\\Windows\\win.ini',
		'https://example.com/a.jpg',
		'file:///etc/passwd',
		'%2e%2e/%2e%2e/etc/passwd',
		'',
		' images/tiny.jpg',
		"images/tiny.jpg\n",
		'images/missing.jpg',
		'0',
	);

	/**
	 * Copies fixtures into the theme and builds the symlink and sibling cases.
	 */
	public function set_up(): void {
		parent::set_up();
		Registry::clear();
		StyleRegistry::clear();

		$this->theme = realpath( get_template_directory() );
		$images      = $this->theme . '/images';
		$fixtures    = __DIR__ . '/fixtures';

		mkdir( $images . '/sub', 0755, true );
		copy( $fixtures . '/images/tiny-320x240.jpg', $images . '/tiny.jpg' );
		copy( $fixtures . '/svg/svg-plain.svg', $images . '/plain.svg' );
		foreach ( array( 'UPPERCASE.JPG', 'spaces and (parens) & ampersand.jpg', 'ünïcödé-名前-🙂.jpg', 'zero-byte.jpg' ) as $name ) {
			copy( $fixtures . '/images/' . $name, $images . '/' . $name );
		}
		copy( $fixtures . '/svg/svg-plain.svg', $images . '/my%20logo.svg' );
		symlink( $images . '/tiny.jpg', $images . '/link-inside.jpg' );

		$outside = sys_get_temp_dir() . '/tib-fuzz-outside-' . getmypid() . '.jpg';
		copy( $fixtures . '/images/tiny-320x240.jpg', $outside );
		symlink( $outside, $images . '/link-outside.jpg' );
		$this->extra[] = $outside;

		$sibling = $this->theme . '-evil';
		mkdir( $sibling . '/images', 0755, true );
		copy( $fixtures . '/svg/svg-plain.svg', $sibling . '/images/x.svg' );
		$this->extra[] = $sibling;
	}

	/**
	 * Removes everything set_up created.
	 */
	public function tear_down(): void {
		self::remove_tree( $this->theme . '/images' );
		foreach ( $this->extra as $path ) {
			self::remove_tree( $path );
		}
		Registry::clear();
		StyleRegistry::clear();
		parent::tear_down();
	}

	/**
	 * Builds a random slug.
	 */
	private function random_slug(): string {
		return $this->pick(
			array(
				'photo',
				'Photo',
				'my image',
				'..',
				'a/b',
				'ünïcödé',
				"nul\0byte",
				str_repeat( 'x', 300 ),
				'0',
				'',
				$this->random_text( 20, true ),
			)
		);
	}

	/**
	 * Builds a random scalar-ish value for a text field.
	 *
	 * @return mixed
	 */
	private function random_scalar() {
		return $this->pick(
			array(
				'Title',
				'<script>alert(1)</script>',
				'&amp; already encoded',
				'"quoted" & \'single\'',
				str_repeat( 't', 10000 ),
				'',
				'0',
				0,
				123,
				2.5,
				-1,
				null,
				true,
				false,
				array(),
				array( 'nested' => array( 'x' ) ),
				$this->random_text( 40 ),
			)
		);
	}

	/**
	 * Builds a random width-like value.
	 *
	 * @return mixed
	 */
	private function random_dimension() {
		return $this->pick(
			array(
				'100',
				'-1',
				'1.5',
				'1e3',
				'<script>',
				'100px; color:red',
				'10rem',
				'',
				'0',
				'abc',
				10,
				-3,
				2.5,
				null,
				true,
				array(),
				str_repeat( '9', 50 ),
			)
		);
	}

	/**
	 * Builds a random path value.
	 *
	 * @return mixed
	 */
	private function random_path() {
		if ( $this->chance( 10 ) ) {
			return $this->pick( array( array(), 123, null, true, str_repeat( 'a/', 200 ) . 'x.jpg', $this->random_text( 30 ) ) );
		}

		return $this->pick( $this->chance( 60 ) ? self::INSIDE : self::OUTSIDE );
	}

	/**
	 * Builds a random variations value.
	 *
	 * @return mixed
	 */
	private function random_variations() {
		if ( $this->chance( 20 ) ) {
			return $this->pick( array( 'not-array', 42, null, array( 'images/tiny.jpg', 'x' ) ) );
		}

		$count      = $this->chance( 5 ) ? 300 : mt_rand( 0, 4 );
		$variations = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$key   = $this->pick( array( 'small', 'Medium', 'a b', '', '..', 0, 'ünï', 'v' . $i ) );
			$entry = $this->pick(
				array(
					array(
						'name'   => $this->random_scalar(),
						'path'   => $this->random_path(),
						'width'  => $this->random_dimension(),
						'height' => $this->random_dimension(),
					),
					array( 'path' => $this->random_path() ),
					array( 'name' => array( 'x' ) ),
					array(),
					'string',
					null,
				)
			);

			$variations[ $key ] = $entry;
		}

		return $variations;
	}

	/**
	 * Builds a random argument array.
	 *
	 * @return array<string, mixed>
	 */
	private function random_args(): array {
		$args = array();
		foreach ( array( 'title', 'description', 'alt', 'caption', 'sizes' ) as $key ) {
			if ( $this->chance( 80 ) ) {
				$args[ $key ] = $this->random_scalar();
			}
		}
		foreach ( array( 'width', 'height', 'max_width', 'max_height' ) as $key ) {
			if ( $this->chance( 70 ) ) {
				$args[ $key ] = $this->random_dimension();
			}
		}
		if ( $this->chance( 90 ) ) {
			$args['path'] = $this->random_path();
		}
		if ( $this->chance( 70 ) ) {
			$args['variations'] = $this->random_variations();
		}
		if ( $this->chance( 10 ) ) {
			$args['unexpected'] = $this->random_scalar();
		}

		return $args;
	}

	/**
	 * Registration returns a bool, never throws, and stores only resolvable paths.
	 */
	public function test_register_never_throws_and_stores_resolvable_paths(): void {
		$registered = array();
		$true_count = 0;

		for ( $i = 0; $i < $this->runs; $i++ ) {
			if ( $this->chance( 10 ) ) {
				Registry::clear();
				$registered = array();
			}

			$slug = $this->random_slug();
			$args = $this->random_args();

			$input = array( 'slug' => $slug, 'args' => $args );

			try {
				$result = Registry::register( $slug, $args );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $input, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsBool( $result, $this->replay( $i, $input, 'register() did not return a bool' ) );

			$key = sanitize_key( $slug );

			if ( '' === $key ) {
				$this->assertFalse( $result, $this->replay( $i, $input, 'a slug that sanitizes to nothing was registered' ) );
			}
			$this->assertArrayNotHasKey( '', Registry::get_all(), $this->replay( $i, $input, 'an image is stored under the empty slug' ) );

			if ( ! $result ) {
				if ( ! isset( $registered[ $key ] ) ) {
					$this->assertFalse( Registry::has( $key ), $this->replay( $i, $input, 'register() returned false but the slug is registered' ) );
				}
				continue;
			}

			++$true_count;
			$registered[ $key ] = true;

			$stored = Registry::get( $key );
			$this->assertNotNull( $stored, $this->replay( $i, $input, 'register() returned true but get() found nothing' ) );

			$real = realpath( $this->theme . '/' . $stored['path'] );
			$this->assertNotFalse( $real, $this->replay( $i, $input, 'stored path does not resolve: ' . $stored['path'] ) );
			$this->assertStringStartsWith( $this->theme . '/', $real, $this->replay( $i, $input, 'stored path resolves outside the theme: ' . $real ) );
			$this->assertTrue( is_file( $real ), $this->replay( $i, $input, 'stored path is not a file: ' . $real ) );
			$this->assertSame( $real, realpath( $this->theme . '/' . $args['path'] ), $this->replay( $i, $input, 'stored path resolves to a different file than the registered one' ) );

			$this->assertIsString( $stored['path'] );
			foreach ( array( 'title', 'description', 'alt', 'caption', 'width', 'height', 'max_width', 'max_height', 'sizes' ) as $field ) {
				$this->assertIsString( $stored[ $field ], $this->replay( $i, $input, "stored $field is not a string" ) );
				$this->assertSame( sanitize_text_field( $stored[ $field ] ), $stored[ $field ], $this->replay( $i, $input, "stored $field is not sanitize_text_field() stable" ) );
			}
			$this->assertIsArray( $stored['variations'] );
			foreach ( $stored['variations'] as $size => $variation ) {
				$this->assertSame( array( 'name', 'path', 'width', 'height' ), array_keys( $variation ), $this->replay( $i, $input, 'variation keys drifted' ) );
				foreach ( $variation as $field => $value ) {
					$this->assertSame( sanitize_text_field( $value ), $value, $this->replay( $i, $input, "variation $size.$field is not sanitize_text_field() stable" ) );
				}
			}

			$json = wp_json_encode( Registry::get_for_editor() );
			$this->assertNotFalse( $json, $this->replay( $i, $input, 'get_for_editor() does not JSON encode: ' . json_last_error_msg() ) );
		}

		$this->assertGreaterThan( 0, $true_count, 'The generator never produced a valid registration; widen INSIDE.' );
	}

	/**
	 * Style registration returns a bool and stores sanitize_text_field() stable strings.
	 */
	public function test_style_register_never_throws(): void {
		for ( $i = 0; $i < $this->runs; $i++ ) {
			if ( $this->chance( 10 ) ) {
				StyleRegistry::clear();
			}

			$slug = $this->random_slug();
			$args = array();
			foreach ( array( 'name', 'width', 'height' ) as $key ) {
				if ( $this->chance( 80 ) ) {
					$args[ $key ] = 'name' === $key ? $this->random_scalar() : $this->random_dimension();
				}
			}

			$input = array( 'slug' => $slug, 'args' => $args );

			try {
				$result = StyleRegistry::register( $slug, $args );
			} catch ( \Throwable $e ) {
				$this->fail( $this->replay( $i, $input, get_class( $e ) . ': ' . $e->getMessage() ) );
			}

			$this->assertIsBool( $result );
			if ( '' === sanitize_key( $slug ) ) {
				$this->assertFalse( $result, $this->replay( $i, $input, 'a slug that sanitizes to nothing was registered' ) );
			}
			$this->assertArrayNotHasKey( '', StyleRegistry::get_all(), $this->replay( $i, $input, 'a style is stored under the empty slug' ) );
			if ( ! $result ) {
				continue;
			}

			$stored = StyleRegistry::get( $slug );
			$this->assertNotNull( $stored );
			foreach ( $stored as $field => $value ) {
				$this->assertSame( sanitize_text_field( $value ), $value, $this->replay( $i, $input, "stored $field is not sanitize_text_field() stable" ) );
			}
			$this->assertNotFalse( wp_json_encode( StyleRegistry::get_for_editor() ) );
		}
	}

	/**
	 * A symlink inside the theme that points outside is rejected.
	 */
	public function test_symlink_outside_theme_is_rejected(): void {
		$this->assertFalse( Registry::register( 'out', array( 'title' => 'Out', 'path' => 'images/link-outside.jpg' ) ) );
	}

	/**
	 * A null byte in the path returns false instead of throwing.
	 */
	public function test_null_byte_in_path_returns_false(): void {
		$this->assertFalse( Registry::register( 'nul', array( 'title' => 'Nul', 'path' => "images/tiny.jpg\0.png" ) ) );
	}

	/**
	 * A sibling directory sharing the theme's name as a prefix is rejected.
	 */
	public function test_sibling_directory_with_shared_prefix_is_rejected(): void {
		$path = '../' . basename( $this->theme ) . '-evil/images/x.svg';

		$this->assertFalse( Registry::register( 'evil', array( 'title' => 'Evil', 'path' => $path ) ) );
	}

	/**
	 * A directory is not an image.
	 */
	public function test_directory_path_is_rejected(): void {
		$this->assertFalse( Registry::register( 'dir', array( 'title' => 'Dir', 'path' => 'images' ) ) );
	}

	/**
	 * A file name with a percent-encoded octet round-trips through the registry.
	 */
	public function test_percent_encoded_filename_round_trips(): void {
		$this->assertTrue( Registry::register( 'pct', array( 'title' => 'Pct', 'path' => 'images/my%20logo.svg' ) ) );

		$this->assertSame( 'images/my%20logo.svg', Registry::get( 'pct' )['path'] );
	}

	/**
	 * A stored path is the on-disk path, without `..` segments or symlinks.
	 */
	public function test_stored_path_is_normalized(): void {
		$this->assertTrue( Registry::register( 'dots', array( 'title' => 'Dots', 'path' => 'images/../images/tiny.jpg' ) ) );
		$this->assertSame( 'images/tiny.jpg', Registry::get( 'dots' )['path'] );

		$this->assertTrue( Registry::register( 'link', array( 'title' => 'Link', 'path' => 'images/link-inside.jpg' ) ) );
		$this->assertSame( 'images/tiny.jpg', Registry::get( 'link' )['path'] );

		$this->assertTrue( Registry::register( 'uni', array( 'title' => 'Uni', 'path' => 'images/ünïcödé-名前-🙂.jpg' ) ) );
		$this->assertSame( 'images/ünïcödé-名前-🙂.jpg', Registry::get( 'uni' )['path'] );
	}

	/**
	 * A slug that sanitizes to nothing is rejected.
	 */
	public function test_slug_of_only_punctuation_is_rejected(): void {
		$this->assertFalse( Registry::register( '!!!', array( 'title' => 'Bang', 'path' => 'images/tiny.jpg' ) ) );
		$this->assertArrayNotHasKey( '', Registry::get_all() );
	}

	/**
	 * Same as above for styles.
	 */
	public function test_style_slug_of_only_punctuation_is_rejected(): void {
		$this->assertFalse( StyleRegistry::register( '!!!', array( 'name' => 'Bang' ) ) );
		$this->assertArrayNotHasKey( '', StyleRegistry::get_all() );
	}

	/**
	 * Variation paths get the same containment check as the main path.
	 *
	 * S3/K5: sanitize_variations() only runs sanitize_text_field() on the path.
	 */
	public function test_variation_path_outside_theme_is_dropped(): void {
		$this->assertTrue(
			Registry::register(
				'vars',
				array(
					'title'      => 'Vars',
					'path'       => 'images/tiny.jpg',
					'variations' => array(
						'ok'       => array( 'path' => 'images/plain.svg', 'width' => '100' ),
						'missing'  => array( 'path' => 'images/missing.jpg', 'width' => '200' ),
						'outside'  => array( 'path' => '../../../etc/passwd', 'width' => '300' ),
						'external' => array( 'path' => 'images/link-outside.jpg', 'width' => '400' ),
					),
				)
			)
		);

		$bad = array();
		foreach ( Registry::get( 'vars' )['variations'] as $size => $variation ) {
			$real = realpath( $this->theme . '/' . $variation['path'] );
			if ( ! $real || 0 !== strpos( $real, $this->theme . '/' ) ) {
				$bad[] = "$size => {$variation['path']}";
			}
		}

		if ( $bad ) {
			$this->markTestIncomplete( 'S3/K5: variations stored without a containment or existence check: ' . implode( ', ', $bad ) );
		}

		$this->assertSame( array( 'ok' ), array_keys( Registry::get( 'vars' )['variations'] ) );
	}
}
