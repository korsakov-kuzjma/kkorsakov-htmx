<?php
/**
 * Security utilities class.
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

class Security {

	use Singleton;

	public function verify_rest_nonce( ?string $nonce ): bool {
		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	public function get_rest_nonce_from_request(): ?string {
		$nonce = '';

		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
		} elseif ( isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		}

		return ! empty( $nonce ) ? $nonce : null;
	}

	public function can_access_rest_endpoint( string $capability = 'read' ): bool {
		return current_user_can( $capability );
	}

	public function sanitize_array( array $data ): array {
		$sanitized = [];

		foreach ( $data as $key => $value ) {
			$safe_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $safe_key ] = $this->sanitize_array( $value );
			} elseif ( is_string( $value ) ) {
				$sanitized[ $safe_key ] = sanitize_text_field( wp_unslash( $value ) );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $safe_key ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $safe_key ] = $value;
			}
		}

		return $sanitized;
	}

	public function escape_html_attributes( array $attributes ): string {
		$escaped = [];

		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) && $value ) {
				$escaped[] = esc_attr( $key );
			} elseif ( ! is_bool( $value ) ) {
				$escaped[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $escaped );
	}

	public function is_htmx_request(): bool {
		return isset( $_SERVER['HTTP_HX_REQUEST'] ) && 'true' === $_SERVER['HTTP_HX_REQUEST'];
	}

	public function wants_json_response(): bool {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		return false !== strpos( $accept, 'application/json' ) && ! $this->is_htmx_request();
	}
}
