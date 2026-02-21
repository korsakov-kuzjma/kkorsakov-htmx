<?php
/**
 * REST API endpoints class.
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Kkorsakov\Htmx\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest_Api {

	use Singleton;

	protected function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'maybe_send_html_response' ], 10, 4 );
	}

	public function register_routes(): void {
		register_rest_route(
			'kkorsakov-htmx/v1',
			'/fragment',
			[
				'methods'             => [ 'GET', 'POST' ],
				'callback'            => [ $this, 'handle_fragment_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_endpoint_args(),
			]
		);
	}

	public function maybe_send_html_response( $served, $result, $request, $server ) {
		if ( ! Security::get_instance()->is_htmx_request() ) {
			return $served;
		}

		$route = $request->get_route();
		if ( strpos( $route, 'kkorsakov-htmx/v1/fragment' ) === false ) {
			return $served;
		}

		$data = $result->get_data();

		if ( is_string( $data ) && ! is_wp_error( $data ) ) {
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			header( 'HX-Trigger: afterFragmentLoad' );
			echo $data;
			return true;
		}

		return $served;
	}

	protected function get_endpoint_args(): array {
		return [
			'target' => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Target fragment identifier (ID, CSS selector, or slug)', 'kkorsakov-htmx' ),
			],
			'context' => [
				'type'              => 'string',
				'default'           => 'view',
				'enum'              => [ 'view', 'edit' ],
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Context under which the fragment is rendered', 'kkorsakov-htmx' ),
			],
			'args' => [
				'type'              => 'object',
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_args' ],
				'description'       => __( 'Additional arguments for fragment rendering', 'kkorsakov-htmx' ),
			],
		];
	}

	public function sanitize_args( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return Security::get_instance()->sanitize_array( $value );
	}

	public function check_permissions( WP_REST_Request $request ): bool {
		$security = Security::get_instance();

		$nonce = $security->get_rest_nonce_from_request();

		if ( ! empty( $nonce ) && ! $security->verify_rest_nonce( $nonce ) ) {
			return false;
		}

		$allowed = $security->can_access_rest_endpoint( 'read' );

		return apply_filters( 'kkorsakov_htmx_rest_permissions', $allowed, $request );
	}

	public function handle_fragment_request( WP_REST_Request $request ) {
		$target  = $request->get_param( 'target' );
		$context = $request->get_param( 'context' );
		$args    = $request->get_param( 'args' );

		$html = $this->render_fragment( $target, $context, $args );

		if ( is_wp_error( $html ) ) {
			return $html;
		}

		$html = apply_filters( 'kkorsakov_htmx_fragment_output', $html, $target, $context );

		$security = Security::get_instance();

		if ( $security->is_htmx_request() ) {
			return $this->send_htmx_response( $html );
		}

		if ( $security->wants_json_response() ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'html'    => $html,
				],
				200
			);
		}

		return new WP_REST_Response( $html, 200 );
	}

	protected function render_fragment( string $target, string $context, array $args ) {
		$html = '';

		if ( $this->is_post_id( $target ) ) {
			$html = $this->render_post_fragment( (int) $target, $context, $args );
		} elseif ( $this->is_term( $target ) ) {
			$html = $this->render_term_fragment( $target, $context, $args );
		} elseif ( $this->is_css_selector( $target ) ) {
			$html = $this->render_custom_fragment( $target, $context, $args );
		} else {
			$html = apply_filters( 'kkorsakov_htmx_render_fragment', '', $target, $context, $args );
		}

		if ( empty( $html ) ) {
			return new WP_Error(
				'fragment_not_found',
				__( 'Fragment not found or cannot be rendered.', 'kkorsakov-htmx' ),
				[ 'status' => 404 ]
			);
		}

		return $html;
	}

	protected function is_post_id( string $target ): bool {
		return is_numeric( $target ) && get_post( (int) $target ) !== null;
	}

	protected function is_term( string $target ): bool {
		$parts = explode( ':', $target, 2 );

		if ( count( $parts ) === 2 && is_numeric( $parts[1] ) ) {
			$term = get_term( (int) $parts[1], $parts[0] );
			return $term && ! is_wp_error( $term );
		}

		if ( is_numeric( $target ) ) {
			$term = get_term( (int) $target );
			return $term && ! is_wp_error( $term );
		}

		return false;
	}

	protected function is_css_selector( string $target ): bool {
		return str_starts_with( $target, '#' ) || str_starts_with( $target, '.' );
	}

	protected function render_post_fragment( int $post_id, string $context, array $args ): string {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		if ( 'edit' === $context && ! current_user_can( 'edit_post', $post_id ) ) {
			return '';
		}

		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		$template = $this->get_post_template( $post, $context );

		ob_start();

		if ( $template && file_exists( $template ) ) {
			include $template;
		} else {
			echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
		}

		$html = ob_get_clean();

		wp_reset_postdata();

		return $html;
	}

	protected function get_post_template( \WP_Post $post, string $context ): string|false {
		$template = false;

		$template = apply_filters( 'kkorsakov_htmx_post_template', $template, $post, $context );

		if ( $template && file_exists( $template ) ) {
			return $template;
		}

		$post_type = get_post_type( $post );

		$theme_template = locate_template(
			[
				"htmx-fragment-{$post_type}-{$context}.php",
				"htmx-fragment-{$post_type}.php",
				"htmx-fragment-{$context}.php",
				'htmx-fragment.php',
			]
		);

		if ( $theme_template ) {
			return $theme_template;
		}

		return false;
	}

	protected function render_term_fragment( string $target, string $context, array $args ): string {
		$parts  = explode( ':', $target, 2 );
		$term   = null;
		$term_id = 0;

		if ( count( $parts ) === 2 && is_numeric( $parts[1] ) ) {
			$term    = get_term( (int) $parts[1], $parts[0] );
			$term_id = (int) $parts[1];
		} elseif ( is_numeric( $target ) ) {
			$term    = get_term( (int) $target );
			$term_id = (int) $target;
		}

		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		$GLOBALS['wp_query']->is_archive       = true;
		$GLOBALS['wp_query']->is_tax           = true;
		$GLOBALS['wp_query']->queried_object   = $term;
		$GLOBALS['wp_query']->queried_object_id = $term_id;

		$template = $this->get_term_template( $term, $context );

		ob_start();

		if ( $template && file_exists( $template ) ) {
			include $template;
		} else {
			echo wp_kses_post( term_description( $term ) );
		}

		$html = ob_get_clean();

		return $html;
	}

	protected function get_term_template( \WP_Term $term, string $context ): string|false {
		$template = false;

		$template = apply_filters( 'kkorsakov_htmx_term_template', $template, $term, $context );

		if ( $template && file_exists( $template ) ) {
			return $template;
		}

		$taxonomy = $term->taxonomy;

		$theme_template = locate_template(
			[
				"htmx-fragment-{$taxonomy}-{$context}.php",
				"htmx-fragment-{$taxonomy}.php",
				"htmx-fragment-taxonomy-{$context}.php",
				"htmx-fragment-taxonomy.php",
				"htmx-fragment-{$context}.php",
				'htmx-fragment.php',
			]
		);

		if ( $theme_template ) {
			return $theme_template;
		}

		return false;
	}

	protected function render_custom_fragment( string $target, string $context, array $args ): string {
		$html = '';

		$html = apply_filters( 'kkorsakov_htmx_custom_fragment', $html, $target, $context, $args );

		if ( empty( $html ) ) {
			$html = apply_filters( "kkorsakov_htmx_fragment_{$target}", $html, $context, $args );
		}

		return $html;
	}

	protected function send_htmx_response( string $html ): WP_REST_Response {
		return new WP_REST_Response( $html, 200 );
	}
}
