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
	 * @return string The SVG markup, or '' when the file has no svg root element.
	 */
	public static function get( string $path, array $args = array() ): string {
		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! $svg ) {
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

		if ( $args['alt'] ) {
			$processor->set_attribute( 'aria-label', $args['alt'] );
			$processor->set_attribute( 'role', 'img' );
		} else {
			$processor->set_attribute( 'aria-hidden', 'true' );
		}

		$processor->set_attribute( 'focusable', 'false' );

		$inline_styles = array();
		if ( $args['width'] ) {
			$inline_styles[] = 'width: ' . $args['width'];
		}
		if ( $args['height'] ) {
			$inline_styles[] = 'height: ' . $args['height'];
		}
		if ( $args['max_width'] ) {
			$inline_styles[] = 'max-width: ' . $args['max_width'];
		}
		if ( $args['max_height'] ) {
			$inline_styles[] = 'max-height: ' . $args['max_height'];
		}
		if ( ! empty( $inline_styles ) ) {
			$processor->set_attribute( 'style', implode( '; ', $inline_styles ) );
		}

		return $processor->get_updated_html();
	}
}
