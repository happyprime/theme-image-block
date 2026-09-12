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

Theme images should be registered using `HappyPrime\ThemeImageBlock\register_theme_image()`. This is likely best done on the `init` or `after_setup_theme` action.

```php
HappyPrime\ThemeImageBlock\register_theme_image(
	'happy-prime-logo',
	[
		'title' => 'Happy Prime Logo',
		'description' => 'The Happy Prime logo.',
		'alt' => 'Happy Prime',
		'path' => 'images/happy-prime-logo.svg',
		'width' => '300',
		'height' => '',
		'variations' => [
			'small' => [
				'path'   => 'images/happy-prime-logo-small.svg',
				'width'  => '100',
				'height' => '100',
			],
			'medium' => [
				'path'   => 'images/happy-prime-logo-medium.svg',
				'width'  => '200',
				'height' => '200',
			],
			'large' => [
				'path'   => 'images/happy-prime-logo-large.svg',
				'width'  => '300',
				'height' => '300',
			],
		],
		'sizes' => '(max-width: 600px) 100vw, 300px',
	]
);
```

### Registering Theme Image Styles

Theme image styles should be registered using `HappyPrime\ThemeImageBlock\register_theme_image_style()`. This is likely best done on the `init` or `after_setup_theme` action.

```php
HappyPrime\ThemeImageBlock\register_theme_image_style(
	'hero',
	[
		'name'   => 'Hero',
		'width'  => 'clamp(10rem, 100vw, 60rem)',
		'height' => 'auto',
	]
);
```

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
