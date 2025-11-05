<?php
/**
 * Registry for theme image styles.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Manages the registration and retrieval of theme image styles.
 */
class StyleRegistry {
	/**
	 * Registered theme image styles.
	 *
	 * @var array<string, array{
	 *     name: string,
	 *     width: string,
	 *     height: string
	 * }>
	 */
	private static array $styles = array();

	/**
	 * Register a theme image style.
	 *
	 * @param string               $slug Unique identifier for the style.
	 * @param array<string, mixed> $args {
	 *     Style configuration arguments.
	 *
	 *     @type string $name   Display name for the style (required).
	 *     @type string $width  CSS width value (optional).
	 *     @type string $height CSS height value (optional).
	 * }
	 *
	 * @return bool True if registered successfully, false otherwise.
	 */
	public static function register( string $slug, array $args ): bool {
		if ( empty( $slug ) || empty( $args['name'] ) ) {
			return false;
		}

		// Sanitize the slug.
		$slug = sanitize_key( $slug );

		// This style is already registered.
		if ( self::has( $slug ) ) {
			return false;
		}

		// Set defaults for optional fields.
		$defaults = array(
			'name'   => '',
			'width'  => '',
			'height' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Store the registered style.
		self::$styles[ $slug ] = array(
			'name'   => sanitize_text_field( $args['name'] ),
			'width'  => sanitize_text_field( $args['width'] ),
			'height' => sanitize_text_field( $args['height'] ),
		);

		return true;
	}

	/**
	 * Get all registered styles.
	 *
	 * @return array<string, array{
	 *     name: string,
	 *     width: string,
	 *     height: string
	 * }> Registered styles, keyed by style slug.
	 */
	public static function get_all(): array {
		return self::$styles;
	}

	/**
	 * Get a specific registered style by slug.
	 *
	 * @param string $slug Style slug.
	 *
	 * @return array{
	 *     name: string,
	 *     width: string,
	 *     height: string
	 * }|null Style data or null if not found.
	 */
	public static function get( string $slug ): ?array {
		$slug = sanitize_key( $slug );
		return self::$styles[ $slug ] ?? null;
	}

	/**
	 * Check if a style is registered.
	 *
	 * @param string $slug Style slug.
	 *
	 * @return bool True if registered, false otherwise.
	 */
	public static function has( string $slug ): bool {
		$slug = sanitize_key( $slug );
		return isset( self::$styles[ $slug ] );
	}

	/**
	 * Unregister a theme image style.
	 *
	 * @param string $slug Style slug.
	 *
	 * @return bool True if unregistered, false if not found.
	 */
	public static function unregister( string $slug ): bool {
		$slug = sanitize_key( $slug );

		if ( ! isset( self::$styles[ $slug ] ) ) {
			return false;
		}

		unset( self::$styles[ $slug ] );
		return true;
	}

	/**
	 * Prepare styles for JavaScript consumption.
	 *
	 * Transforms the registered styles into a format suitable for the block editor.
	 *
	 * @return array<int, array<string, mixed>> Styles formatted for JavaScript.
	 */
	public static function get_for_editor(): array {
		$styles = array();

		foreach ( self::$styles as $slug => $data ) {
			$styles[] = array(
				'slug'   => $slug,
				'name'   => $data['name'],
				'width'  => $data['width'],
				'height' => $data['height'],
			);
		}

		return $styles;
	}

	/**
	 * Clear all registered styles.
	 *
	 * Primarily useful for testing.
	 */
	public static function clear(): void {
		self::$styles = array();
	}
}
