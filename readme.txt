# Theme Image Block
Contributors: happyprime, jeremyfelt, slocker, philcable
Tags: images, media
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.1.1
License: GPLv2 or later
Requires PHP: 7.4

Use images from your theme as blocks in content.

## Description

The Theme Image Block plugin allows you to register images from your theme and make them available as blocks in the WordPress editor. This is useful for logos, icons, and other images that are part of your theme's design system.

## Installation

1. Install and activate the plugin.
2. Register theme images using the `register_theme_image()` function.
3. Register theme image styles using the `register_theme_image_style()` function.
3. Use the Theme Image block in the editor to insert registered images.

## Usage

### Registering Theme Images

Register images with `HappyPrime\ThemeImageBlock\register_theme_image()` on `init` or `after_setup_theme`. Guard the call so the theme keeps working when the plugin is inactive.

<pre><code>
add_action( 'init', function () {
	if ( ! function_exists( 'HappyPrime\ThemeImageBlock\register_theme_image' ) ) {
		return;
	}

	HappyPrime\ThemeImageBlock\register_theme_image(
		'happy-prime-logo',
		[
			'title'       => 'Happy Prime Logo',
			'description' => 'The Happy Prime logo.',
			'alt'         => 'Happy Prime',
			'caption'     => 'Happy Prime, est. 2015',
			'path'        => 'images/happy-prime-logo.png',
			'width'       => '1200',
			'height'      => '400',
			'max_width'   => '40rem',
			'variations'  => [
				'small'  => [
					'name'   => 'Small',
					'path'   => 'images/happy-prime-logo-small.png',
					'width'  => '400',
					'height' => '133',
				],
				'medium' => [
					'name'   => 'Medium',
					'path'   => 'images/happy-prime-logo-medium.png',
					'width'  => '800',
					'height' => '267',
				],
			],
			'sizes'       => '(max-width: 600px) 100vw, 40rem',
		]
	);
} );
</code></pre>

* `title`: Required. Shown in the block's image selector.
* `path`: Required. Relative to the parent theme directory and must resolve to a file inside it: `..` that leaves the theme, a directory, or a symlink pointing outside the theme is rejected. Child theme paths are not resolved.
* `alt`: Default alt text. The block can override or omit it.
* `caption`: Default caption, rendered when the block shows a caption and has none of its own.
* `description`: Stored and passed to the editor; not displayed.
* `width`, `height`: Pixel dimensions of the file. Integers only: they become the `width` and `height` attributes and the `w` descriptor in `srcset`. Other values are ignored.
* `max_width`, `max_height`: CSS values applied inline to the image.
* `variations`: Keyed by size slug. Each has a `path` (validated like the main path; a variation that fails is dropped), a `name` for the Variation dropdown, and pixel `width` and `height`. Choosing a variation makes it the `src` and drops wider `srcset` candidates. SVG files never get a `srcset`.
* `sizes`: The `sizes` attribute, emitted only with a `srcset`.

Registration returns `false` when it is rejected; with `WP_DEBUG` on, a `_doing_it_wrong()` notice says why.

### Registering Theme Image Styles

Register styles with `HappyPrime\ThemeImageBlock\register_theme_image_style()` on `init` or `after_setup_theme`. `width` and `height` are inserted into the image's `style` attribute as given.

<pre><code>
add_action( 'init', function () {
	if ( ! function_exists( 'HappyPrime\ThemeImageBlock\register_theme_image_style' ) ) {
		return;
	}

	HappyPrime\ThemeImageBlock\register_theme_image_style(
		'hero',
		[
			'name'   => 'Hero',
			'width'  => 'clamp(10rem, 100vw, 60rem)',
			'height' => 'auto',
		]
	);
} );
</code></pre>

## Security

Inline SVG output is the theme file as shipped, with accessibility and sizing attributes added to the root element. Nothing is sanitized: a script, event handler or `<foreignObject>` in a theme SVG runs on the page. Style values are inserted into the `style` attribute as given. Only files inside the parent theme directory can be registered, and only PHP can register images and styles, so the theme is the trust boundary.

## Changelog

### 1.1.1

* Prevent fatal error on activation.
* Improve HTML processing when rendering output.
* Fix reference to renamed global JavaScript variable.
* Fix textdomain mismatch.
* Improve support for multiple theme image blocks in one editor view.

### 1.1.0

* Initial release on wp.org.

### 1.0.0

* Initial release.
