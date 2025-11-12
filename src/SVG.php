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
	 * Get an SVG.
	 *
	 * @param string                $path The slug of the SVG to get.
	 * @param array<string, string> $args The arguments for the SVG.
	 * @return string The SVG.
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

		// Process SVG to add accessibility attributes.
		$processor = new \WP_HTML_Tag_Processor( $svg );

		if ( $processor->next_tag( array( 'tag_name' => 'svg' ) ) ) {
			if ( $args['alt'] ) {
				$processor->set_attribute( 'aria-label', $args['alt'] );
				$processor->set_attribute( 'role', 'img' );
			} else {
				$processor->set_attribute( 'aria-hidden', 'true' );
			}

			$processor->set_attribute( 'focusable', 'false' );

			// Apply width/height/max-width/max-height to SVG if set.
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
		}

		return $processor->get_updated_html();
	}
}
