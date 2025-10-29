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
	 */
	public static function render( $attributes, $content ): string {
		if ( empty( $attributes['themeImage'] ) ) {
			return '';
		}

		$theme_image = sanitize_file_name( $attributes['themeImage'] );
		$alt         = isset( $attributes['alt'] ) ? esc_attr( $attributes['alt'] ) : '';
		$inline_svg  = isset( $attributes['inlineSVG'] ) && $attributes['inlineSVG'];
		$link_url    = isset( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
		$link_target = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '';
		$link_rel    = isset( $attributes['linkRel'] ) ? esc_attr( $attributes['linkRel'] ) : '';
		$width       = isset( $attributes['width'] ) && ! empty( $attributes['width'] ) ? esc_attr( $attributes['width'] ) : '';
		$height      = isset( $attributes['height'] ) && ! empty( $attributes['height'] ) ? esc_attr( $attributes['height'] ) : '';

		// Construct the image path.
		$image_path = get_template_directory() . '/images/' . $theme_image;
		$image_url  = get_template_directory_uri() . '/images/' . $theme_image;

		// Check if file exists.
		if ( ! file_exists( $image_path ) ) {
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
				'<div %s><p>%s</p></div>',
				get_block_wrapper_attributes( $wrapper_attrs ),
				esc_html__( 'Theme image not found.', 'happyprime' )
			);
		}

		$is_svg = 'image/svg+xml' === mime_content_type( $image_path );

		// Handle inline SVG.
		if ( $inline_svg && $is_svg ) {
			$svg = file_get_contents( $image_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( $svg ) {
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
			esc_url( $image_url ),
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
