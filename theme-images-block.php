<?php
/**
 * Plugin Name:  Theme Images Block
 * Description:  Use images from your theme as blocks in content.
 * Version:      0.0.1
 * Author:       Happy Prime
 * Author URI:   https://happyprime.co
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  happyprime
 * Domain Path:  /languages
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package HappyPrime\ThemeImagesBlock
 */

namespace HappyPrime\ThemeImagesBlock;

const VERSION = '0.0.1';

/**
 * A common prefix used with settings pages, fields, sections, and other
 * components to help with uniquity.
 *
 * @var string
 */
const PREFIX = 'hp-ti-';

/**
 * A common slug combined with the prefix and used to build the names of
 * settings pages, fields, sections, and other components.
 *
 * @var string
 */
const SLUG = 'theme-images-block';

/**
 * The main option key used for the plugin.
 *
 * @var string
 */
const OPTION_NAME = 'hp_theme_images_block';

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', [ Init::class, 'init' ] );
