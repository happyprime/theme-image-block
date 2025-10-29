# Theme Image Block

Use images from your theme as blocks in content.

## Description

The Theme Image Block plugin allows you to register images from your theme and make them available as blocks in the WordPress editor. This is useful for logos, icons, and other images that are part of your theme's design system.

## Installation

1. Install and activate the plugin
2. Register theme images using the `register_theme_image()` function
3. Use the Theme Image block in the editor to insert registered images

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
		'width' => '',
		'height' => '',
		'sizes' => [
			'small' => [
				'path'   => 'images/happy-prime-logo-small.svg',
				'width'  => 100,
				'height' => 100,
			],
			'medium' => [
				'path'   => 'images/happy-prime-logo-medium.svg',
				'width'  => 200,
				'height' => 200,
			],
			'large' => [
				'path'   => 'images/happy-prime-logo-large.svg',
				'width'  => 300,
				'height' => 300,
			],
		],
	]
);
```

### Function Reference

#### `register_theme_image( string $slug, array $args )`

Registers a theme image for use in the Theme Image block.

**Parameters:**

- `$slug` (string, required): Unique identifier for the image.
- `$args` (array, required): Image configuration arguments.
  - `title` (string, required): Display title for the image shown in the block selector.
  - `description` (string, optional): Description of the image.
  - `alt` (string, optional): Default alt text for accessibility.
  - `path` (string, required): Path to the image file relative to the theme directory.
  - `width` (string, optional): Default width value.
  - `height` (string, optional): Default height value.
  - `sizes` (array, optional): Array of size variations.

**Returns:** Boolean indicating success.

### Using the Block

1. In the WordPress editor, add a new block
2. Search for "Theme Image"
3. Select an image from the dropdown
4. Configure the image settings in the sidebar:
   - Alt text for accessibility
   - Width and height (supports all CSS units and functions like `clamp()`)
   - Inline SVG option for SVG files (for better styling control)
   - Link settings via the block toolbar

## Features

- **PHP-based registration**: Register images from your theme or plugin code
- **Rich metadata**: Include titles, descriptions, alt text, and size variations
- **Flexible dimensions**: Support for all CSS units and functions (px, %, rem, clamp, calc, etc.)
- **Inline SVG support**: Render SVG files inline for better styling control
- **Link support**: Add links to images with target and rel options
- **Block supports**: Includes alignment, colors, spacing, and more
