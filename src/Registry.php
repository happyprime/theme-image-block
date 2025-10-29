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
	 * @var array<string, array<string, mixed>>
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
	 *     @type string $path        Path to the image file relative to the theme directory (required).
	 *     @type string $width       Default width value (optional).
	 *     @type string $height      Default height value (optional).
	 *     @type array  $sizes       Array of size variations (optional).
	 * }
	 *
	 * @return bool True if registered successfully, false otherwise.
	 */
	public static function register( string $slug, array $args ): bool {
		if ( empty( $slug ) || empty( $args['title'] ) || empty( $args['path'] ) ) {
			return false;
		}

		$full_path = realpath( get_template_directory() . '/' . $args['path'] );
		$theme_dir = realpath( get_template_directory() );

		// Protect against path traversal, even though these images are all
		// registered via PHP anyway.
		if ( ! $full_path || ! $theme_dir || strpos( $full_path, $theme_dir ) !== 0 ) {
			return false;
		}

		// Only allow images that exist to be registered.
		if ( ! file_exists( $full_path ) ) {
			return false;
		}

		// Sanitize the slug.
		$slug = sanitize_key( $slug );

		// This image is already registered.
		if ( self::has( $slug ) ) {
			return false;
		}

		// Set defaults for optional fields.
		$defaults = array(
			'title'       => '',
			'description' => '',
			'alt'         => '',
			'path'        => '',
			'width'       => '',
			'height'      => '',
			'sizes'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		// Store the registered image.
		self::$images[ $slug ] = array(
			'title'       => sanitize_text_field( $args['title'] ),
			'description' => sanitize_text_field( $args['description'] ),
			'alt'         => sanitize_text_field( $args['alt'] ),
			'path'        => sanitize_text_field( $args['path'] ),
			'width'       => sanitize_text_field( $args['width'] ),
			'height'      => sanitize_text_field( $args['height'] ),
			'sizes'       => self::sanitize_sizes( $args['sizes'] ),
		);

		return true;
	}

	/**
	 * Get all registered images.
	 *
	 * @return array<string, array<string, mixed>> Registered images.
	 */
	public static function get_all(): array {
		return self::$images;
	}

	/**
	 * Get a specific registered image by slug.
	 *
	 * @param string $slug Image slug.
	 *
	 * @return array<string, mixed>|null Image data or null if not found.
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
				'width'       => $data['width'],
				'height'      => $data['height'],
				'sizes'       => $data['sizes'],
			);
		}

		return $images;
	}

	/**
	 * Sanitize size variations.
	 *
	 * @param mixed $sizes Size variations.
	 *
	 * @return array<string, array<string, mixed>> Sanitized sizes.
	 */
	private static function sanitize_sizes( $sizes ): array {
		if ( ! is_array( $sizes ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $sizes as $size => $data ) {
			if ( ! is_array( $data ) ) {
				continue;
			}

			$sanitized[ sanitize_key( $size ) ] = array(
				'path'   => isset( $data['path'] ) ? sanitize_text_field( $data['path'] ) : '',
				'width'  => isset( $data['width'] ) ? sanitize_text_field( $data['width'] ) : '',
				'height' => isset( $data['height'] ) ? sanitize_text_field( $data['height'] ) : '',
			);
		}

		return $sanitized;
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
