<?php
/**
 * Provide SVGs.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Provide SVGs.
 */
class SVG {
	/**
	 * Returns an SVG file's markup with accessibility and sizing attributes on its root element.
	 *
	 * @param string                $path Absolute path to the SVG file.
	 * @param array<string, string> $args Alt text and CSS dimensions to apply.
	 * @return string The SVG markup, or '' when the file cannot be inlined.
	 */
	public static function get( string $path, array $args = array() ): string {
		$svg = self::read( $path );

		if ( '' === $svg ) {
			return '';
		}

		$defaults = array(
			'alt'        => '',
			'width'      => '',
			'height'     => '',
			'max_width'  => '',
			'max_height' => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$processor = new \WP_HTML_Tag_Processor( $svg );

		if ( ! $processor->next_tag( array( 'tag_name' => 'svg' ) ) ) {
			return '';
		}

		if ( '' !== $args['alt'] ) {
			$processor->set_attribute( 'aria-label', $args['alt'] );
			$processor->set_attribute( 'role', 'img' );
			$processor->remove_attribute( 'aria-hidden' );
		} else {
			$processor->set_attribute( 'aria-hidden', 'true' );
			$processor->remove_attribute( 'aria-label' );
		}

		$processor->set_attribute( 'focusable', 'false' );

		$inline_styles = Block::inline_styles(
			array(
				'width'      => $args['width'],
				'height'     => $args['height'],
				'max-width'  => $args['max_width'],
				'max-height' => $args['max_height'],
			)
		);
		if ( array() !== $inline_styles ) {
			$existing = $processor->get_attribute( 'style' );
			if ( is_string( $existing ) && '' !== trim( $existing, "; \t\n\r" ) ) {
				array_unshift( $inline_styles, trim( $existing, "; \t\n\r" ) );
			}
			$processor->set_attribute( 'style', implode( '; ', $inline_styles ) );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Reads an SVG file and drops everything ahead of its root element.
	 *
	 * Returns '' when the file is unreadable or the root element is not an
	 * svg tag. A DOCTYPE with an internal subset is left in place, so files
	 * declaring entities are rejected: HTML never expands them.
	 *
	 * @param string $path Absolute path to the SVG file.
	 * @return string SVG markup starting at the root element, or ''.
	 */
	private static function read( string $path ): string {
		// A logo in a template part renders on every page; read it once per request.
		static $cache = array();

		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return '';
		}

		$key = $path . '|' . filemtime( $path ) . '|' . filesize( $path );

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! is_string( $svg ) || '' === $svg ) {
			return '';
		}

		$svg = preg_replace( '/^(?:\xEF\xBB\xBF|\s+|<\?xml[^>]*\?>|<!DOCTYPE[^\[>]*>|<!--.*?-->)+/is', '', $svg );

		if ( ! is_string( $svg ) || 1 !== preg_match( '/^<svg[\s\/>]/i', $svg ) ) {
			return '';
		}

		$cache[ $key ] = $svg;

		return $svg;
	}
}
