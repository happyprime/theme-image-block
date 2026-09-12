<?php
/**
 * Registry for theme images.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Manages the registration and retrieval of theme images.
 */
class Registry {
	/**
	 * Registered theme images.
	 *
	 * @var array<string, array{
	 *     title: string,
	 *     description: string,
	 *     alt: string,
	 *     caption: string,
	 *     path: string,
	 *     width: string,
	 *     height: string,
	 *     max_width: string,
	 *     max_height: string,
	 *     variations: array<string, array{name: string, path: string, width: string, height: string}>,
	 *     sizes: string
	 * }>
	 */
	private static array $images = array();

	/**
	 * Register a theme image.
	 *
	 * @param string               $slug  Unique identifier for the image.
	 * @param array<string, mixed> $args  {
	 *     Image configuration arguments.
	 *
	 *     @type string $title       Display title for the image (required).
	 *     @type string $description Description of the image (optional).
	 *     @type string $alt         Default alt text for the image (optional).
	 *     @type string $caption     Default caption for the image (optional).
	 *     @type string $path        Path to the image file relative to the theme directory (required).
	 *     @type string $width       Default width value (optional).
	 *     @type string $height      Default height value (optional).
	 *     @type string $max_width   Max width to apply to the image element (optional).
	 *     @type string $max_height  Max height to apply to the image element (optional).
	 *     @type array  $variations  Array of image variations for srcset (optional).
	 *     @type string $sizes       Value for the sizes attribute (optional).
	 * }
	 *
	 * @return bool True if registered successfully, false otherwise.
	 */
	public static function register( string $slug, array $args ): bool {
		$slug = sanitize_key( $slug );

		if ( '' === $slug ) {
			self::warn( __( 'The image slug sanitizes to an empty string.', 'theme-image-block' ) );
			return false;
		}

		if ( empty( $args['title'] ) || empty( $args['path'] ) ) {
			/* translators: %s: image slug */
			self::warn( sprintf( __( 'Image "%s" needs a title and a path.', 'theme-image-block' ), $slug ) );
			return false;
		}

		$path = self::resolve_path( $args['path'] );

		if ( null === $path ) {
			/* translators: 1: image slug, 2: registered path */
			self::warn( sprintf( __( 'Image "%1$s": the path "%2$s" is not a file inside the theme directory.', 'theme-image-block' ), $slug, is_string( $args['path'] ) ? $args['path'] : gettype( $args['path'] ) ) );
			return false;
		}

		if ( self::has( $slug ) ) {
			/* translators: %s: image slug */
			self::warn( sprintf( __( 'Image "%s" is already registered.', 'theme-image-block' ), $slug ) );
			return false;
		}

		// Set defaults for optional fields.
		$defaults = array(
			'title'       => '',
			'description' => '',
			'alt'         => '',
			'caption'     => '',
			'path'        => '',
			'width'       => '',
			'height'      => '',
			'max_width'   => '',
			'max_height'  => '',
			'variations'  => array(),
			'sizes'       => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Store the registered image.
		self::$images[ $slug ] = array(
			'title'       => sanitize_text_field( $args['title'] ),
			'description' => sanitize_text_field( $args['description'] ),
			'alt'         => sanitize_text_field( $args['alt'] ),
			'caption'     => sanitize_text_field( $args['caption'] ),
			'path'        => $path,
			'width'       => sanitize_text_field( $args['width'] ),
			'height'      => sanitize_text_field( $args['height'] ),
			'max_width'   => sanitize_text_field( $args['max_width'] ),
			'max_height'  => sanitize_text_field( $args['max_height'] ),
			'variations'  => self::sanitize_variations( $args['variations'] ),
			'sizes'       => sanitize_text_field( $args['sizes'] ),
		);

		return true;
	}

	/**
	 * Get all registered images.
	 *
	 * @return array<string, array{
	 *     title: string,
	 *     description: string,
	 *     alt: string,
	 *     caption: string,
	 *     path: string,
	 *     width: string,
	 *     height: string,
	 *     max_width: string,
	 *     max_height: string,
	 *     variations: array<string, array{name: string, path: string, width: string, height: string}>,
	 *     sizes: string
	 * }> Registered images, keyed by image slug.
	 */
	public static function get_all(): array {
		return self::$images;
	}

	/**
	 * Get a specific registered image by slug.
	 *
	 * @param string $slug Image slug.
	 *
	 * @return array{
	 *     title: string,
	 *     description: string,
	 *     alt: string,
	 *     caption: string,
	 *     path: string,
	 *     width: string,
	 *     height: string,
	 *     max_width: string,
	 *     max_height: string,
	 *     variations: array<string, array{name: string, path: string, width: string, height: string}>,
	 *     sizes: string
	 * }|null Image data or null if not found.
	 */
	public static function get( string $slug ): ?array {
		$slug = sanitize_key( $slug );
		return self::$images[ $slug ] ?? null;
	}

	/**
	 * Check if an image is registered.
	 *
	 * @param string $slug Image slug.
	 *
	 * @return bool True if registered, false otherwise.
	 */
	public static function has( string $slug ): bool {
		$slug = sanitize_key( $slug );
		return isset( self::$images[ $slug ] );
	}

	/**
	 * Unregister a theme image.
	 *
	 * @param string $slug Image slug.
	 *
	 * @return bool True if unregistered, false if not found.
	 */
	public static function unregister( string $slug ): bool {
		$slug = sanitize_key( $slug );

		if ( ! isset( self::$images[ $slug ] ) ) {
			return false;
		}

		unset( self::$images[ $slug ] );
		return true;
	}

	/**
	 * Prepare images for JavaScript consumption.
	 *
	 * Transforms the registered images into a format suitable for the block editor.
	 *
	 * @return array<int, array<string, mixed>> Images formatted for JavaScript.
	 */
	public static function get_for_editor(): array {
		$images = array();

		foreach ( self::$images as $slug => $data ) {
			$images[] = array(
				'slug'        => $slug,
				'value'       => $data['path'],
				'label'       => $data['title'],
				'description' => $data['description'],
				'alt'         => $data['alt'],
				'caption'     => $data['caption'],
				'width'       => $data['width'],
				'height'      => $data['height'],
				'max_width'   => $data['max_width'],
				'max_height'  => $data['max_height'],
				'variations'  => $data['variations'],
				'sizes'       => $data['sizes'],
			);
		}

		return $images;
	}

	/**
	 * Sanitizes image variations, dropping any whose path is not a theme file.
	 *
	 * @param mixed $variations Image variations.
	 *
	 * @return array<string, array{name: string, path: string, width: string, height: string}> Sanitized variations.
	 */
	private static function sanitize_variations( $variations ): array {
		if ( ! is_array( $variations ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $variations as $size => $data ) {
			$size = sanitize_key( (string) $size );

			if ( '' === $size || ! is_array( $data ) ) {
				continue;
			}

			$path = self::resolve_path( $data['path'] ?? null );

			if ( null === $path ) {
				/* translators: %s: variation key */
				self::warn( sprintf( __( 'Variation "%s" was dropped: its path is not a file inside the theme directory.', 'theme-image-block' ), $size ) );
				continue;
			}

			$sanitized[ $size ] = array(
				'name'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
				'path'   => $path,
				'width'  => isset( $data['width'] ) ? sanitize_text_field( $data['width'] ) : '',
				'height' => isset( $data['height'] ) ? sanitize_text_field( $data['height'] ) : '',
			);
		}

		return $sanitized;
	}

	/**
	 * Resolves a theme-relative path to an existing file inside the parent theme directory.
	 *
	 * The returned path is rebuilt from realpath(), so symlinks and `..`
	 * segments are gone and the file name is stored as it is on disk.
	 *
	 * @param mixed $path Path relative to the theme directory.
	 * @return string|null The normalized relative path, or null when it does not resolve to a theme file.
	 */
	private static function resolve_path( $path ): ?string {
		// realpath() throws on a null byte in PHP 8.
		if ( ! is_string( $path ) || '' === $path || false !== strpos( $path, "\0" ) ) {
			return null;
		}

		$theme_dir = realpath( get_template_directory() );
		$full_path = realpath( get_template_directory() . '/' . $path );

		if ( false === $theme_dir || false === $full_path || ! is_file( $full_path ) ) {
			return null;
		}

		// The separator keeps a sibling directory sharing the theme's name as a prefix out.
		$prefix = $theme_dir . DIRECTORY_SEPARATOR;

		if ( 0 !== strpos( $full_path, $prefix ) ) {
			return null;
		}

		return str_replace( DIRECTORY_SEPARATOR, '/', substr( $full_path, strlen( $prefix ) ) );
	}

	/**
	 * Reports a rejected registration under WP_DEBUG.
	 *
	 * @param string $message Why the registration was rejected.
	 */
	private static function warn( string $message ): void {
		_doing_it_wrong( __CLASS__ . '::register', esc_html( $message ), '1.2.0' );
	}

	/**
	 * Clear all registered images.
	 *
	 * Primarily useful for testing.
	 */
	public static function clear(): void {
		self::$images = array();
	}
}
