<?php
/**
 * Render the theme image block.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Render the theme image block.
 *
 * @phpstan-type Image_Data array{path: string, width: string, variations: array<string, array{path: string, width: string}>}
 */
class Block {
	/**
	 * Render the theme image block.
	 *
	 * @param array<string, string> $attributes Block attributes. {
	 *     @type string $themeImage  The slug of the theme image to display. Required.
	 *     @type string $imageSize   The size variation to display. Default 'original'.
	 *     @type string $imageStyle  The slug of the registered style to apply. Default empty string.
	 *     @type bool   $inlineSVG   Whether to inline SVG content instead of using an img tag. Default false.
	 *     @type string $linkUrl     URL for wrapping the image in a link. Default empty string.
	 *     @type string $linkTarget  Target attribute for the link (e.g., '_blank'). Default empty string.
	 *     @type string $linkRel     Rel attribute for the link (e.g., 'nofollow'). Default empty string.
	 *     @type string $caption     Caption text to display below the image. Default empty string.
	 *     @type bool   $showCaption Whether to display the caption. Default false.
	 *     @type string $altText     Custom alt text to override the registered default. Default empty string.
	 *     @type bool   $omitAltText Whether to omit alt text entirely. Default false.
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

		// Kept raw: the img sprintf and set_attribute() each escape once.
		$omit_alt_text = isset( $attributes['omitAltText'] ) && $attributes['omitAltText'];
		if ( $omit_alt_text ) {
			$alt = '';
		} elseif ( isset( $attributes['altText'] ) && ! empty( $attributes['altText'] ) ) {
			$alt = $attributes['altText'];
		} else {
			$alt = $image_data['alt'];
		}

		$image_size  = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'original';
		$inline_svg  = isset( $attributes['inlineSVG'] ) && $attributes['inlineSVG'];
		$link_url    = isset( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
		$link_target = isset( $attributes['linkTarget'] ) ? $attributes['linkTarget'] : '';
		$link_rel    = isset( $attributes['linkRel'] ) ? $attributes['linkRel'] : '';

		// Get width/height from registered style if imageStyle is set.
		$width  = '';
		$height = '';
		if ( isset( $attributes['imageStyle'] ) && ! empty( $attributes['imageStyle'] ) ) {
			$style_slug = sanitize_key( $attributes['imageStyle'] );
			$style_data = StyleRegistry::get( $style_slug );

			if ( $style_data ) {
				if ( ! empty( $style_data['width'] ) ) {
					$width = $style_data['width'];
				}
				if ( ! empty( $style_data['height'] ) ) {
					$height = $style_data['height'];
				}
			}
		}

		// Determine the image path based on selected size.
		$display_path = $image_data['path'];
		if (
			'original' !== $image_size &&
			! empty( $image_data['variations'][ $image_size ]['path'] )
		) {
			$display_path = $image_data['variations'][ $image_size ]['path'];
		}

		// By extension: fileinfo is optional in PHP and libmagic misreads
		// exports that open with a comment.
		$filetype = wp_check_filetype( $display_path, array( 'svg' => 'image/svg+xml' ) );
		$is_svg   = 'image/svg+xml' === $filetype['type'];

		$theme_uri = get_template_directory_uri();
		$srcset    = $is_svg ? '' : self::srcset( $image_data, $image_size, $theme_uri );
		$sizes     = '' !== $srcset ? $image_data['sizes'] : '';

		// Construct the image path from the display path.
		$image_path = realpath( get_template_directory() . '/' . $display_path );
		$theme_dir  = realpath( get_template_directory() );

		// Registration validated the path; the theme may have changed on disk since.
		if ( ! $image_path || ! $theme_dir || 0 !== strpos( $image_path, $theme_dir . DIRECTORY_SEPARATOR ) ) {
			return '';
		}

		$inline_styles = array();
		if ( $width ) {
			$inline_styles[] = 'width: ' . $width;
		}
		if ( $height ) {
			$inline_styles[] = 'height: ' . $height;
		}
		if ( ! empty( $image_data['max_width'] ) ) {
			$inline_styles[] = 'max-width: ' . $image_data['max_width'];
		}
		if ( ! empty( $image_data['max_height'] ) ) {
			$inline_styles[] = 'max-height: ' . $image_data['max_height'];
		}

		$wrapper_classes = array();
		$content         = '';

		if ( $inline_svg && $is_svg ) {
			$content = SVG::get(
				$image_path,
				[
					'alt'        => $alt,
					'width'      => $width,
					'height'     => $height,
					'max_width'  => $image_data['max_width'],
					'max_height' => $image_data['max_height'],
				]
			);
		}

		$inline_svg = '' !== $content;

		if ( $inline_svg ) {
			$wrapper_classes[] = 'has-inline-svg';
		} else {
			$content = sprintf(
				'<img src="%s" alt="%s" />',
				esc_url( self::file_url( $theme_uri, $display_path ) ),
				esc_attr( $alt )
			);
		}

		if ( $link_url ) {
			$content = '<a>' . $content . '</a>';

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

			$content = $html->get_updated_html();
		}

		$html = new \WP_HTML_Tag_Processor( $content );

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

		// Add caption if showCaption is enabled and caption is provided.
		$show_caption = isset( $attributes['showCaption'] ) && $attributes['showCaption'];
		$caption      = isset( $attributes['caption'] ) ? wp_kses_post( $attributes['caption'] ) : '';
		if ( $show_caption && ! empty( $caption ) ) {
			$content .= sprintf( '<figcaption>%s</figcaption>', $caption );
		}

		$wrapper_attrs = ! empty( $wrapper_classes )
			? array( 'class' => implode( ' ', $wrapper_classes ) )
			: array();

		return sprintf(
			'<figure %s>%s</figure>',
			get_block_wrapper_attributes( $wrapper_attrs ),
			$content
		);
	}

	/**
	 * Builds the srcset for a registered image, capped at the selected variation's width.
	 *
	 * Only a positive integer width makes a candidate. A selected variation
	 * without one yields no srcset at all, so the browser keeps the chosen src.
	 *
	 * @param Image_Data $image_data Registered image.
	 * @param string     $image_size Selected variation key, or 'original'.
	 * @param string     $theme_uri  Parent theme URI.
	 * @return string Comma-separated candidates, or '' when there are none.
	 */
	private static function srcset( array $image_data, string $image_size, string $theme_uri ): string {
		$max_width = null;

		if ( 'original' !== $image_size && isset( $image_data['variations'][ $image_size ] ) ) {
			$max_width = self::pixel_width( $image_data['variations'][ $image_size ]['width'] );

			if ( 0 === $max_width ) {
				return '';
			}
		}

		$candidates = array_merge( array( $image_data ), array_values( $image_data['variations'] ) );
		$parts      = array();
		$seen       = array();

		foreach ( $candidates as $candidate ) {
			$width = self::pixel_width( $candidate['width'] );
			$url   = self::file_url( $theme_uri, $candidate['path'] );

			if ( 0 === $width || ( null !== $max_width && $width > $max_width ) ) {
				continue;
			}

			// Browsers keep the first of two equal descriptors and flag the second as an error.
			if ( isset( $seen[ $url ] ) || isset( $seen[ $width ] ) ) {
				continue;
			}

			$seen[ $url ]   = true;
			$seen[ $width ] = true;
			$parts[]        = sprintf( '%s %dw', $url, $width );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Returns a registered width as a pixel count, or 0 when it is not a positive integer.
	 *
	 * @param string $width Registered width.
	 */
	private static function pixel_width( string $width ): int {
		$width = trim( $width );

		return ctype_digit( $width ) ? (int) $width : 0;
	}

	/**
	 * Builds the URL of a theme file, encoding each path segment.
	 *
	 * @param string $theme_uri Parent theme URI.
	 * @param string $path      Path relative to the theme directory.
	 */
	private static function file_url( string $theme_uri, string $path ): string {
		return $theme_uri . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );
	}
}
