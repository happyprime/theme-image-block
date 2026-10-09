<?php
/**
 * Plugin Name:       Theme Image Block
 * Description:       Use images from your theme as blocks in content.
 * Version:           1.2.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Happy Prime
 * Author URI:        https://happyprime.co
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       theme-image-block
 * Domain Path:       /languages
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', [ Init::class, 'init' ] );

/**
 * Registers a theme image for the Theme Image block.
 *
 * @param string               $slug Unique identifier for the image.
 * @param array<string, mixed> $args {
 *     Image configuration arguments.
 *
 *     @type string $title       Display title for the image (required).
 *     @type string $description Description of the image (optional).
 *     @type string $alt         Default alt text for the image (optional).
 *     @type string $caption     Default caption for the image (optional).
 *     @type string $path        Path to the image file relative to the parent theme directory (required).
 *     @type string $width       Pixel width of the file (optional).
 *     @type string $height      Pixel height of the file (optional).
 *     @type string $max_width   CSS max-width applied to the image (optional).
 *     @type string $max_height  CSS max-height applied to the image (optional).
 *     @type array  $variations  Image variations keyed by size, each with name, path, width and height (optional).
 *     @type string $sizes       Value for the sizes attribute (optional).
 * }
 *
 * @return bool True if registered successfully, false otherwise.
 */
function register_theme_image( string $slug, array $args ): bool {
	return \HappyPrime\ThemeImageBlock\Registry::register( $slug, $args );
}

/**
 * Registers a style that sets the dimensions of a Theme Image block.
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
