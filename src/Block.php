<?php
/**
 * Render the theme image block.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Render the theme image block.
 */
class Block {
	/**
	 * Render the theme image block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 *
	 * @return string Rendered block HTML.
	 */
	public static function render( $attributes, $content ): string {
		if ( empty( $attributes['themeImage'] ) ) {
			return '';
		}

		// Get the image slug and look up the registered image data.
		$image_slug = sanitize_key( $attributes['themeImage'] );
		$image_data = Registry::get( $image_slug );

		// If the image isn't registered, return an empty string.
		if ( ! $image_data ) {
			return '';
		}

		$alt         = esc_attr( $image_data['alt'] );
		$inline_svg  = isset( $attributes['inlineSVG'] ) && $attributes['inlineSVG'];
		$link_url    = isset( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
		$link_target = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '';
		$link_rel    = isset( $attributes['linkRel'] ) ? esc_attr( $attributes['linkRel'] ) : '';
		$width       = isset( $attributes['width'] ) && ! empty( $attributes['width'] ) ? esc_attr( $attributes['width'] ) : '';
		$height      = isset( $attributes['height'] ) && ! empty( $attributes['height'] ) ? esc_attr( $attributes['height'] ) : '';

		// Construct the image path from the registered image data.
		$image_path = realpath( get_template_directory() . '/' . $image_data['path'] );
		$theme_dir  = realpath( get_template_directory() );

		// Protect against path traversal, even though these images are all
		// registered via PHP anyway.
		if ( ! $image_path || ! $theme_dir || strpos( $image_path, $theme_dir ) !== 0 ) {
			return '';
		}

		$is_svg = 'image/svg+xml' === mime_content_type( $image_path );

		// Handle inline SVG.
		if ( $inline_svg && $is_svg ) {
			$svg = file_get_contents( $image_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( ! $svg ) {
				return '';
			}

			// Process SVG to add accessibility attributes.
			$processor = new \WP_HTML_Tag_Processor( $svg );

			if ( $processor->next_tag( 'svg' ) ) {
				if ( $alt ) {
					$processor->set_attribute( 'aria-label', $alt );
					$processor->set_attribute( 'role', 'img' );
				} else {
					$processor->set_attribute( 'aria-hidden', 'true' );
				}

				$processor->set_attribute( 'focusable', 'false' );

				// Apply width/height to SVG if set.
				$inline_styles = array();
				if ( $width ) {
					$inline_styles[] = 'width: ' . $width;
				}
				if ( $height ) {
					$inline_styles[] = 'height: ' . $height;
				}
				if ( ! empty( $inline_styles ) ) {
					$processor->set_attribute( 'style', implode( '; ', $inline_styles ) );
				}
			}

			$svg = $processor->get_updated_html();
			// Remove XML declaration if present.
			$svg = preg_replace( '/<\?xml.*?\?>/', '', $svg );

			// Wrap in link if URL is provided.
			if ( $link_url ) {
				$link_attrs  = sprintf( ' href="%s"', $link_url );
				$link_attrs .= $link_target ? sprintf( ' target="%s"', $link_target ) : '';
				$link_attrs .= $link_rel ? sprintf( ' rel="%s"', $link_rel ) : '';

				$content = sprintf( '<a%s>%s</a>', $link_attrs, $svg );
			} else {
				$content = $svg;
			}

			$wrapper_class = 'has-inline-svg';
			$wrapper_attrs = array( 'class' => $wrapper_class );

			$inline_styles = array();
			if ( $width ) {
				$inline_styles[] = 'width: ' . $width;
			}
			if ( $height ) {
				$inline_styles[] = 'height: ' . $height;
			}
			if ( ! empty( $inline_styles ) ) {
				$wrapper_attrs['style'] = implode( '; ', $inline_styles );
			}

			return sprintf(
				'<div %s>%s</div>',
				get_block_wrapper_attributes( $wrapper_attrs ),
				$content
			);
		}

		// Standard image tag rendering.
		$inline_styles = array();
		if ( $width ) {
			$inline_styles[] = 'width: ' . $width;
		}
		if ( $height ) {
			$inline_styles[] = 'height: ' . $height;
		}
		$img_style = ! empty( $inline_styles ) ? sprintf( ' style="%s"', implode( '; ', $inline_styles ) ) : '';
		$img       = sprintf(
			'<img src="%s" alt="%s"%s />',
			esc_url( get_template_directory_uri() . '/' . $image_data['path'] ),
			$alt,
			$img_style
		);

		// Wrap in link if URL is provided.
		if ( $link_url ) {
			$link_attrs  = sprintf( ' href="%s"', $link_url );
			$link_attrs .= $link_target ? sprintf( ' target="%s"', $link_target ) : '';
			$link_attrs .= $link_rel ? sprintf( ' rel="%s"', $link_rel ) : '';

			$content = sprintf( '<a%s>%s</a>', $link_attrs, $img );
		} else {
			$content = $img;
		}

		$wrapper_attrs = array();

		$inline_styles = array();
		if ( $width ) {
			$inline_styles[] = 'width: ' . $width;
		}
		if ( $height ) {
			$inline_styles[] = 'height: ' . $height;
		}
		if ( ! empty( $inline_styles ) ) {
			$wrapper_attrs['style'] = implode( '; ', $inline_styles );
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes( $wrapper_attrs ),
			$content
		);
	}
}
