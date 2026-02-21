<?php
/**
 * Plugin Name: kkorsakov-htmx
 * Plugin URI: https://github.com/korsakov-kuzjma/kkorsakov-htmx
 * Description: Seamless HTMX library integration for WordPress with extended REST API for fragment requests.
 * Version: 1.0.0
 * Author: kkorsakov
 * Author URI: https://github.com/korsakov-kuzjma
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kkorsakov-htmx
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KKORSAKOV_HTMX_VERSION', '1.0.0' );
define( 'KKORSAKOV_HTMX_MIN_PHP', '7.4' );
define( 'KKORSAKOV_HTMX_MIN_WP', '6.0' );
define( 'KKORSAKOV_HTMX_FILE', __FILE__ );
define( 'KKORSAKOV_HTMX_PATH', plugin_dir_path( __FILE__ ) );
define( 'KKORSAKOV_HTMX_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'Kkorsakov\\Htmx\\';
		$base_dir = KKORSAKOV_HTMX_PATH . 'includes/';

		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
			return;
		}

		$lowercase_relative_class = strtolower( str_replace( '\\', '-', $relative_class ) );
		$file = $base_dir . 'class-' . $lowercase_relative_class . '.php';

		if ( file_exists( $file ) ) {
			require $file;
			return;
		}

		if ( strpos( $relative_class, 'Traits\\' ) === 0 ) {
			$trait_name = str_replace( 'Traits/', '', $relative_class );
			$trait_file = $base_dir . 'traits/trait-' . strtolower( $trait_name ) . '.php';

			if ( file_exists( $trait_file ) ) {
				require $trait_file;
			}
		}
	}
);

function init(): void {
	Plugin::get_instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
