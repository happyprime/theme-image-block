# Theme Image Block

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

```php
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
```

| Argument | Notes |
|---|---|
| `title` | Required. Shown in the block's image selector. |
| `path` | Required. Relative to the parent theme directory and must resolve to a file inside it: `..` that leaves the theme, a directory, or a symlink pointing outside the theme is rejected. Child theme paths are not resolved. |
| `alt` | Default alt text. The block can override or omit it. |
| `caption` | Default caption, rendered when the block shows a caption and has none of its own. |
| `description` | Stored and passed to the editor; not displayed. |
| `width`, `height` | Pixel dimensions of the file. Integers only: they become the `width` and `height` attributes and the `w` descriptor in `srcset`. Other values are ignored. |
| `max_width`, `max_height` | CSS values applied inline to the image. |
| `variations` | Keyed by size slug. Each has a `path` (validated like the main path; a variation that fails is dropped), a `name` for the Variation dropdown, and pixel `width` and `height`. Choosing a variation makes it the `src` and drops wider `srcset` candidates. SVG files never get a `srcset`. |
| `sizes` | The `sizes` attribute, emitted only with a `srcset`. |

Registration returns `false` when it is rejected; with `WP_DEBUG` on, a `_doing_it_wrong()` notice says why.

### Registering Theme Image Styles

Register styles with `HappyPrime\ThemeImageBlock\register_theme_image_style()` on `init` or `after_setup_theme`. `width` and `height` are inserted into the image's `style` attribute as given.

```php
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
```

## Security

Inline SVG output is the theme file as shipped, with accessibility and sizing attributes added to the root element. Nothing is sanitized: a script, event handler or `<foreignObject>` in a theme SVG runs on the page. Style values are inserted into the `style` attribute as given. Only files inside the parent theme directory can be registered, and only PHP can register images and styles, so the theme is the trust boundary.

## Development

`npm run env:start` brings up WordPress 7.1 on http://localhost:8892 with the
plugin active (wp-env, Docker). The dev site also mounts the e2e fixture theme
from `tests/e2e/fixtures/theme/`; `npm run env:cli -- wp ...` runs wp-cli
inside it.

### End-to-end tests

```
npm run test:e2e        # headless; starts wp-env if it is down, stops it after
npm run test:e2e:ui     # Playwright UI mode against a running env
```

Global setup activates the `theme-image-block-e2e` theme, which registers the
images and styles the specs assert against. `test.fixme()` marks cases that
fail on a known plugin bug; the reason names the finding.

### PHPUnit

```
npm run test:php        # unit and fuzz tests in the tests env (port 8894)
npm run test:php:fuzz   # fuzz tests only
```

The fuzz tests seed `mt_rand()` from `TIB_FUZZ_SEED` (default `12345`) and
run `TIB_FUZZ_RUNS` iterations (default `300`). A failure prints both plus
the generated input; replay it with

```
TIB_FUZZ_SEED=777 TIB_FUZZ_RUNS=1000 npm run test:php:fuzz
```

Tests that hit a known bug end in `markTestIncomplete()` with the finding, so
the suite stays green until the fix flips them.

## Changelog

### 1.2.0

* Fix the inline SVG `aria-label` being double-escaped on WordPress 7.0 and later.
* Decide SVG inlining by file extension, so exports that open with a comment inline and the block no longer needs the fileinfo extension.
* Inline only the `svg` element: the XML prolog, DOCTYPE and leading comments are dropped, and a file declaring entities falls back to an `img`.
* Merge style dimensions into an SVG's existing root `style` attribute and remove its `aria-hidden` when a label is set.
* Render the registered `caption` when the block caption is empty.
* Build `srcset` from integer pixel widths only, once per file, never for SVG, and emit `sizes` only alongside it.
* Drop image variations whose file is missing or outside the theme.
* Add `width` and `height` to the `img` so browsers reserve space and core can lazy-load it.
* Limit the link `target` to browsing context keywords and add `noopener` for `_blank`.
* Reject registrations with a null byte, a directory, or a path in a sibling directory; keep file names with `%`, spaces or unicode intact and encode them in URLs.
* Emit a `_doing_it_wrong()` notice under `WP_DEBUG` when a registration is rejected.
* Replace the deprecated `__experimentalLinkControl` and adopt the current control sizing in the editor.
* Register the block from `blocks/build`; the block style is now cache-busted by the plugin version.
* Declare `Requires at least: 6.8` and `Requires PHP: 7.4` in the plugin header.

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
