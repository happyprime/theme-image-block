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
				esc_url( get_template_directory_uri() . '/' . $image_data['path'] ),
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

		if ( $html->next_tag( array( 'tag_name' => 'img' ) ) && ! empty( $inline_styles ) ) {
			$html->set_attribute( 'style', implode( '; ', $inline_styles ) );
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
