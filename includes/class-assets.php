<?php
/**
 * Assets management class.
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

class Assets {

	use Singleton;

	protected function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		$this->register_htmx_library();
		$this->register_frontend_script();
		$this->register_frontend_style();
	}

	protected function register_htmx_library(): void {
		$use_cdn = apply_filters( 'kkorsakov_htmx_use_cdn', false );
		$version = '2.0.2';

		if ( $use_cdn ) {
			$src = "https://unpkg.com/htmx.org@{$version}/dist/htmx.min.js";
		} else {
			$src = KKORSAKOV_HTMX_URL . 'assets/js/htmx.min.js';
		}

		wp_register_script(
			'htmx-lib',
			$src,
			[],
			$use_cdn ? $version : KKORSAKOV_HTMX_VERSION,
			true
		);
	}

	protected function register_frontend_script(): void {
		wp_register_script(
			'kkorsakov-htmx-js',
			KKORSAKOV_HTMX_URL . 'assets/js/frontend.js',
			[ 'htmx-lib' ],
			KKORSAKOV_HTMX_VERSION,
			true
		);

		$localized_data = [
			'root'  => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'endpoint' => rest_url( 'kkorsakov-htmx/v1/fragment' ),
		];

		wp_localize_script(
			'kkorsakov-htmx-js',
			'kkorsakovHtmxSettings',
			$localized_data
		);
	}

	protected function register_frontend_style(): void {
		wp_register_style(
			'kkorsakov-htmx-css',
			KKORSAKOV_HTMX_URL . 'assets/css/frontend.css',
			[],
			KKORSAKOV_HTMX_VERSION
		);
	}

	public function enqueue_htmx_assets(): void {
		wp_enqueue_script( 'kkorsakov-htmx-js' );
		wp_enqueue_style( 'kkorsakov-htmx-css' );
	}

	public function add_htmx_config_meta(): void {
		$config = [
			'historyEnabled'    => true,
			'defaultSwapStyle'  => 'innerHTML',
			'defaultSwapDelay'  => 0,
			'defaultSettleDelay'=> 20,
		];

		$config = apply_filters( 'kkorsakov_htmx_config', $config );

		printf(
			'<meta name="htmx-config" content="%s">',
			esc_attr( wp_json_encode( $config ) )
		);
	}
}
