<?php
/**
 * HTMX integrator class.
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

class Htmx_Integrator {

	use Singleton;

	private bool $should_enqueue = false;

	protected function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_htmx' ] );
		add_action( 'wp_head', [ $this, 'output_htmx_config' ], 1 );
		add_shortcode( 'htmx', [ $this, 'render_shortcode' ] );
		add_filter( 'render_block', [ $this, 'check_block_for_htmx' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'detect_htmx_usage' ] );
	}

	public function detect_htmx_usage(): void {
		if ( $this->should_enqueue ) {
			return;
		}

		global $post;

		if ( $post && has_shortcode( $post->post_content, 'htmx' ) ) {
			$this->should_enqueue = true;
			return;
		}

		if ( $post && has_blocks( $post->post_content ) ) {
			$blocks = parse_blocks( $post->post_content );
			if ( $this->has_htmx_block( $blocks ) ) {
				$this->should_enqueue = true;
			}
		}
	}

	protected function has_htmx_block( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			if ( 'kkorsakov/htmx-fragment' === $block['blockName'] ) {
				return true;
			}

			if ( ! empty( $block['innerBlocks'] ) && $this->has_htmx_block( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
	}

	public function check_block_for_htmx( string $block_content, array $block ): string {
		if ( 'kkorsakov/htmx-fragment' === $block['blockName'] ) {
			$this->should_enqueue = true;
		}

		return $block_content;
	}

	public function maybe_enqueue_htmx(): void {
		$force_enqueue = apply_filters( 'kkorsakov_htmx_force_enqueue', false );

		if ( $this->should_enqueue || $force_enqueue ) {
			Assets::get_instance()->enqueue_htmx_assets();
		}
	}

	public function output_htmx_config(): void {
		$force_enqueue = apply_filters( 'kkorsakov_htmx_force_enqueue', false );

		if ( ! $this->should_enqueue && ! $force_enqueue ) {
			return;
		}

		Assets::get_instance()->add_htmx_config_meta();
	}

	public function render_shortcode( array $atts, ?string $content = null ): string {
		$this->should_enqueue = true;

		$atts = shortcode_atts(
			[
				'target'    => '',
				'trigger'   => 'click',
				'swap'      => 'innerHTML',
				'url'       => '',
				'indicator' => '.htmx-indicator',
				'method'    => 'get',
			],
			$atts,
			'htmx'
		);

		$url = $this->get_fragment_url( $atts['url'], $atts['target'] );

		$htmx_attrs = [
			'hx-' . sanitize_key( $atts['method'] ) => esc_url( $url ),
			'hx-trigger'                            => esc_attr( $atts['trigger'] ),
			'hx-target'                             => esc_attr( $atts['target'] ),
			'hx-swap'                               => esc_attr( $atts['swap'] ),
		];

		if ( ! empty( $atts['indicator'] ) ) {
			$htmx_attrs['hx-indicator'] = esc_attr( $atts['indicator'] );
		}

		$attributes = Security::get_instance()->escape_html_attributes( $htmx_attrs );

		$output = sprintf(
			'<span %s>%s</span>',
			$attributes,
			do_shortcode( $content ?? '' )
		);

		return $output;
	}

	protected function get_fragment_url( string $custom_url, string $target ): string {
		if ( ! empty( $custom_url ) ) {
			return esc_url_raw( $custom_url );
		}

		$url = add_query_arg(
			[
				'target' => sanitize_text_field( $target ),
			],
			rest_url( 'kkorsakov-htmx/v1/fragment' )
		);

		return apply_filters( 'kkorsakov_htmx_fragment_url', $url, $target );
	}

	public function force_enqueue(): void {
		$this->should_enqueue = true;
	}
}
