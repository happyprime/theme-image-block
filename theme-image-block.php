<?php
/**
 * Plugin Name:  Theme Image Block
 * Description:  Use images from your theme as blocks in content.
 * Version:      1.1.0
 * Author:       Happy Prime
 * Author URI:   https://happyprime.co
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  theme-image-block
 * Domain Path:  /languages
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package HappyPrime\ThemeImageBlock
 */

namespace HappyPrime\ThemeImageBlock;

const BLOCKS_DIR = __DIR__ . '/blocks';

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', [ Init::class, 'init' ] );

/**
 * Register a theme image.
 *
 * This function can be used by themes and plugins to register images that
 * should be available for selection in the Theme Image block.
 *
 * @since 1.0.0
 *
 * @param string               $slug Unique identifier for the image.
 * @param array<string, mixed> $args {
 *     Image configuration arguments.
 *
 *     @type string $title       Display title for the image (required).
 *     @type string $description Description of the image (optional).
 *     @type string $alt         Default alt text for the image (optional).
 *     @type string $path        Path to the image file relative to the theme directory (required).
 *     @type string $width       Default width value (optional).
 *     @type string $height      Default height value (optional).
 *     @type array  $variations  Array of image variations for srcset (optional).
 *     @type string $sizes       Value for the sizes attribute (optional).
 * }
 *
 * @return bool True if registered successfully, false otherwise.
 */
function register_theme_image( string $slug, array $args ): bool {
	return \HappyPrime\ThemeImageBlock\Registry::register( $slug, $args );
}

/**
 * Register a theme image style.
 *
 * This function can be used by themes and plugins to register styles that
 * control the dimensions of images in the Theme Image block.
 *
 * @since 1.0.0
 *
 * @param string               $slug Unique identifier for the style.
 * @param array<string, mixed> $args {
 *     Style configuration arguments.
 *
 *     @type string $name   Display name for the style (required).
 *     @type string $width  CSS width value (optional).
 *     @type string $height CSS height value (optional).
 * }
 *
 * @return bool True if registered successfully, false otherwise.
 */
function register_theme_image_style( string $slug, array $args ): bool {
	return \HappyPrime\ThemeImageBlock\StyleRegistry::register( $slug, $args );
}
