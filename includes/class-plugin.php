<?php
/**
 * Main plugin class.
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

use Kkorsakov\Htmx\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	use Singleton;

	protected function init(): void {
		$this->load_textdomain();
		$this->init_components();
		$this->register_hooks();
	}

	protected function load_textdomain(): void {
		load_plugin_textdomain(
			'kkorsakov-htmx',
			false,
			dirname( plugin_basename( KKORSAKOV_HTMX_FILE ) ) . '/languages'
		);
	}

	protected function init_components(): void {
		Security::get_instance();
		Assets::get_instance();
		Htmx_Integrator::get_instance();
		Rest_Api::get_instance();
	}

	protected function register_hooks(): void {
		register_activation_hook( KKORSAKOV_HTMX_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( KKORSAKOV_HTMX_FILE, [ $this, 'deactivate' ] );
	}

	public function activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( version_compare( PHP_VERSION, KKORSAKOV_HTMX_MIN_PHP, '<' ) ) {
			deactivate_plugins( plugin_basename( KKORSAKOV_HTMX_FILE ) );
			wp_die(
				sprintf(
					esc_html__( 'This plugin requires PHP version %s or higher.', 'kkorsakov-htmx' ),
					esc_html( KKORSAKOV_HTMX_MIN_PHP )
				)
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), KKORSAKOV_HTMX_MIN_WP, '<' ) ) {
			deactivate_plugins( plugin_basename( KKORSAKOV_HTMX_FILE ) );
			wp_die(
				sprintf(
					esc_html__( 'This plugin requires WordPress version %s or higher.', 'kkorsakov-htmx' ),
					esc_html( KKORSAKOV_HTMX_MIN_WP )
				)
			);
		}

		if ( ! get_option( 'kkorsakov_htmx_version' ) ) {
			add_option( 'kkorsakov_htmx_version', KKORSAKOV_HTMX_VERSION );
		}
	}

	public function deactivate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_option( 'kkorsakov_htmx_version' );
	}
}
