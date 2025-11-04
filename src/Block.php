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
	 * @param array<string, string> $attributes Block attributes. {
	 *     @type string $themeImage The slug of the theme image to display. Required.
	 *     @type string $imageSize  The size variation to display. Default 'original'.
	 *     @type bool   $inlineSVG  Whether to inline SVG content instead of using an img tag. Default false.
	 *     @type string $linkUrl    URL for wrapping the image in a link. Default empty string.
	 *     @type string $linkTarget Target attribute for the link (e.g., '_blank'). Default empty string.
	 *     @type string $linkRel    Rel attribute for the link (e.g., 'nofollow'). Default empty string.
	 *     @type string $width      CSS width value for the image. Default empty string.
	 *     @type string $height     CSS height value for the image. Default empty string.
	 * }
	 * @param string                $content    Block content.
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
		$image_size  = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'original';
		$inline_svg  = isset( $attributes['inlineSVG'] ) && $attributes['inlineSVG'];
		$link_url    = isset( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
		$link_target = isset( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '';
		$link_rel    = isset( $attributes['linkRel'] ) ? esc_attr( $attributes['linkRel'] ) : '';
		$width       = isset( $attributes['width'] ) && ! empty( $attributes['width'] ) ? esc_attr( $attributes['width'] ) : '';
		$height      = isset( $attributes['height'] ) && ! empty( $attributes['height'] ) ? esc_attr( $attributes['height'] ) : '';

		// Determine the image path based on selected size.
		$display_path = $image_data['path'];
		if (
			'original' !== $image_size &&
			! empty( $image_data['variations'][ $image_size ]['path'] )
		) {
			$display_path = $image_data['variations'][ $image_size ]['path'];
		}

		// Build srcset from registered variations.
		$srcset_parts = array();

		// Add the main image if it has a width.
		if ( ! empty( $image_data['width'] ) ) {
			$srcset_parts[] = sprintf(
				'%s %sw',
				esc_url( get_template_directory_uri() . '/' . $image_data['path'] ),
				(int) $image_data['width']
			);
		}

		// Add variations if they have width and path.
		if ( ! empty( $image_data['variations'] ) && is_array( $image_data['variations'] ) ) {
			foreach ( $image_data['variations'] as $variation ) {
				if ( ! empty( $variation['width'] ) && ! empty( $variation['path'] ) ) {
					$srcset_parts[] = sprintf(
						'%s %sw',
						esc_url( get_template_directory_uri() . '/' . $variation['path'] ),
						(int) $variation['width']
					);
				}
			}
		}

		$srcset = ! empty( $srcset_parts ) ? implode( ', ', $srcset_parts ) : '';
		$sizes  = ! empty( $image_data['sizes'] ) ? esc_attr( $image_data['sizes'] ) : '';

		// Construct the image path from the display path.
		$image_path = realpath( get_template_directory() . '/' . $display_path );
		$theme_dir  = realpath( get_template_directory() );

		// Protect against path traversal, even though these images are all
		// registered via PHP anyway.
		if ( ! $image_path || ! $theme_dir || strpos( $image_path, $theme_dir ) !== 0 ) {
			return '';
		}

		$inline_styles = array();
		if ( $width ) {
			$inline_styles[] = 'width: ' . $width;
		}
		if ( $height ) {
			$inline_styles[] = 'height: ' . $height;
		}

		$wrapper_classes = array();

		$is_svg = 'image/svg+xml' === mime_content_type( $image_path );

		if ( $inline_svg && $is_svg ) {
			$wrapper_classes[] = 'has-inline-svg';
			$content           = SVG::get(
				$image_path,
				[
					'alt'    => $alt,
					'width'  => $width,
					'height' => $height,
				]
			);
		} else {
			$content = sprintf(
				'<img src="%s" alt="%s" />',
				esc_url( get_template_directory_uri() . '/' . $display_path ),
				$alt,
			);
		}

		if ( $link_url ) {
			$content = '<a>' . $content . '</a>';
		}

		$html = new \WP_HTML_Tag_Processor( $content );
		if ( $html->next_tag( array( 'tag_name' => 'a' ) ) ) {
			$html->set_attribute( 'href', $link_url );
			if ( $link_target ) {
				$html->set_attribute( 'target', $link_target );
			}
			if ( $link_rel ) {
				$html->set_attribute( 'rel', $link_rel );
			}
		}

		// This seems to be the best way to rewind and seek again? Seems strange.
		$html = new \WP_HTML_Tag_Processor( $html->get_updated_html() );

		if ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			if ( ! empty( $inline_styles ) ) {
				$html->set_attribute( 'style', implode( '; ', $inline_styles ) );
			}
			if ( ! empty( $srcset ) ) {
				$html->set_attribute( 'srcset', $srcset );
			}
			if ( ! empty( $sizes ) ) {
				$html->set_attribute( 'sizes', $sizes );
			}
		}

		$content = $html->get_updated_html();

		$wrapper_attrs = array( 'class' => implode( ' ', $wrapper_classes ) );

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
