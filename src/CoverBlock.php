<?php
/**
 * Render the theme cover block.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

/**
 * Render the theme cover block.
 */
class CoverBlock {
	/**
	 * Allowed HTML tags for the cover wrapper element.
	 *
	 * @var array<int, string>
	 */
	private static array $allowed_tags = array(
		'div',
		'header',
		'main',
		'section',
		'article',
		'aside',
		'footer',
	);

	/**
	 * Render the theme cover block.
	 *
	 * @param array<string, string> $attributes Block attributes. {
	 *     @type string $themeImage         The slug of the theme image to display. Required.
	 *     @type string $imageSize          The size variation to display. Default 'original'.
	 *     @type string $altText            Custom alt text. Default empty string.
	 *     @type bool   $omitAltText        Whether to omit alt text entirely. Default true.
	 *     @type string $overlayColor       Slug of a preset overlay color. Default empty string.
	 *     @type string $customOverlayColor Custom overlay color (hex). Default empty string.
	 *     @type int    $dimRatio           Overlay opacity 0-100. Default 50.
	 *     @type array  $focalPoint         Focal point {x, y}. Default null.
	 *     @type bool   $hasParallax        Whether the background is fixed. Default false.
	 *     @type bool   $isRepeated         Whether the background repeats. Default false.
	 *     @type int    $minHeight          Minimum height value. Default 430.
	 *     @type string $minHeightUnit      Minimum height unit. Default 'px'.
	 *     @type string $contentPosition    Content position. Default 'center center'.
	 *     @type string $tagName            HTML wrapper element. Default 'div'.
	 * }
	 * @param string                $content    Inner block content (already rendered).
	 *
	 * @return string Rendered block HTML.
	 */
	public static function render( $attributes, $content ): string {
		if ( empty( $attributes['themeImage'] ) ) {
			return '';
		}

		$image_slug = sanitize_key( $attributes['themeImage'] );
		$image_data = Registry::get( $image_slug );

		if ( ! $image_data ) {
			return '';
		}

		$image_size   = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'original';
		$omit_alt     = ! isset( $attributes['omitAltText'] ) || $attributes['omitAltText'];
		$alt_input    = isset( $attributes['altText'] ) ? $attributes['altText'] : '';
		$has_parallax = ! empty( $attributes['hasParallax'] );
		$is_repeated  = ! empty( $attributes['isRepeated'] );
		$dim_ratio    = isset( $attributes['dimRatio'] ) ? max( 0, min( 100, (int) $attributes['dimRatio'] ) ) : 50;
		$min_height   = isset( $attributes['minHeight'] ) ? (float) $attributes['minHeight'] : 430.0;
		$min_unit_raw = isset( $attributes['minHeightUnit'] ) ? $attributes['minHeightUnit'] : 'px';
		$min_unit     = preg_replace( '/[^a-z%]/i', '', $min_unit_raw );
		$content_pos  = isset( $attributes['contentPosition'] ) ? $attributes['contentPosition'] : 'center center';
		$tag_name     = isset( $attributes['tagName'] ) ? strtolower( $attributes['tagName'] ) : 'div';

		if ( ! in_array( $tag_name, self::$allowed_tags, true ) ) {
			$tag_name = 'div';
		}

		if ( $omit_alt ) {
			$alt = '';
		} elseif ( '' !== $alt_input ) {
			$alt = esc_attr( $alt_input );
		} else {
			$alt = esc_attr( $image_data['alt'] );
		}

		$custom_overlay = isset( $attributes['customOverlayColor'] ) ? $attributes['customOverlayColor'] : '';
		$preset_overlay = isset( $attributes['overlayColor'] ) ? $attributes['overlayColor'] : '';
		$overlay_color  = '';

		if ( '' !== $custom_overlay && preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $custom_overlay ) ) {
			$overlay_color = $custom_overlay;
		}

		// Determine which image path to render.
		$display_path = $image_data['path'];
		if (
			'original' !== $image_size &&
			! empty( $image_data['variations'][ $image_size ]['path'] )
		) {
			$display_path = $image_data['variations'][ $image_size ]['path'];
		}

		// Protect against path traversal.
		$image_abs = realpath( get_template_directory() . '/' . $display_path );
		$theme_dir = realpath( get_template_directory() );
		if ( ! $image_abs || ! $theme_dir || strpos( $image_abs, $theme_dir ) !== 0 ) {
			return '';
		}

		$image_url = esc_url( get_template_directory_uri() . '/' . $display_path );

		// Focal point => object/background position.
		$focal_x = 50;
		$focal_y = 50;
		if ( isset( $attributes['focalPoint'] ) && is_array( $attributes['focalPoint'] ) ) {
			if ( isset( $attributes['focalPoint']['x'] ) ) {
				$focal_x = max( 0, min( 100, (int) round( ( (float) $attributes['focalPoint']['x'] ) * 100 ) ) );
			}
			if ( isset( $attributes['focalPoint']['y'] ) ) {
				$focal_y = max( 0, min( 100, (int) round( ( (float) $attributes['focalPoint']['y'] ) * 100 ) ) );
			}
		}
		$position_value = sprintf( '%d%% %d%%', $focal_x, $focal_y );

		// Position classes.
		$position_classes = array();
		if ( $content_pos && 'center center' !== $content_pos ) {
			$parts = explode( ' ', $content_pos );
			if ( ! empty( $parts[0] ) && 'center' !== $parts[0] ) {
				$position_classes[] = 'is-position-y-' . sanitize_html_class( $parts[0] );
			}
			if ( ! empty( $parts[1] ) && 'center' !== $parts[1] ) {
				$position_classes[] = 'is-position-x-' . sanitize_html_class( $parts[1] );
			}
		}

		$wrapper_classes = array( 'has-background-image' );
		if ( $has_parallax ) {
			$wrapper_classes[] = 'has-parallax';
		}
		if ( $is_repeated ) {
			$wrapper_classes[] = 'is-repeated';
		}
		if ( '' !== $preset_overlay ) {
			$wrapper_classes[] = 'has-' . sanitize_html_class( $preset_overlay ) . '-background-color';
		}
		$wrapper_classes = array_merge( $wrapper_classes, $position_classes );

		// Build inline style for wrapper.
		$wrapper_style_parts = array();
		if ( $min_height > 0 ) {
			$wrapper_style_parts[] = sprintf(
				'min-height: %s%s',
				rtrim( rtrim( number_format( $min_height, 4, '.', '' ), '0' ), '.' ),
				$min_unit ? $min_unit : 'px'
			);
		}
		$wrapper_style = implode( '; ', $wrapper_style_parts );

		$wrapper_attrs = array(
			'class' => implode( ' ', array_filter( $wrapper_classes ) ),
		);
		if ( $wrapper_style ) {
			$wrapper_attrs['style'] = $wrapper_style;
		}

		// Overlay markup.
		$overlay_style_parts = array();
		if ( $overlay_color ) {
			$overlay_style_parts[] = 'background-color: ' . esc_attr( $overlay_color );
		}
		$overlay_style_parts[] = 'opacity: ' . ( $dim_ratio / 100 );
		$overlay_markup        = sprintf(
			'<span aria-hidden="true" class="wp-block-happyprime-theme-cover__background" style="%s"></span>',
			esc_attr( implode( '; ', $overlay_style_parts ) )
		);

		// Background image markup.
		if ( $has_parallax ) {
			$bg_style = sprintf(
				'background-image: url(%s); background-position: %s; background-repeat: %s; background-size: %s; background-attachment: fixed;',
				$image_url,
				$position_value,
				$is_repeated ? 'repeat' : 'no-repeat',
				$is_repeated ? 'auto' : 'cover'
			);
			$bg_markup = sprintf(
				'<div role="img" aria-label="%s" class="wp-block-happyprime-theme-cover__image-background has-parallax" style="%s"></div>',
				$alt,
				esc_attr( $bg_style )
			);
		} else {
			$img_style = sprintf(
				'object-position: %s; object-fit: %s;',
				$position_value,
				$is_repeated ? 'none' : 'cover'
			);
			$bg_markup = sprintf(
				'<img class="wp-block-happyprime-theme-cover__image-background" src="%s" alt="%s" style="%s" />',
				$image_url,
				$alt,
				esc_attr( $img_style )
			);
		}

		// Inner content — $content is the saved inner-container div + rendered inner blocks.
		$inner_content = '' !== trim( $content )
			? $content
			: '<div class="wp-block-happyprime-theme-cover__inner-container"></div>';

		$wrapper_attributes = get_block_wrapper_attributes( $wrapper_attrs );

		return sprintf(
			'<%1$s %2$s>%3$s%4$s%5$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$overlay_markup,
			$bg_markup,
			$inner_content
		);
	}
}
