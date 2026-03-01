<?php
/**
 * Класс Rest_Api (REST API).
 *
 * Регистрирует и обрабатывает REST API эндпоинты плагина:
 * - Эндпоинт /kkorsakov-htmx/v1/fragment для получения HTML-фрагментов
 * - Поддержка GET и POST запросов
 * - Рендеринг фрагментов постов, терминов таксономий, кастомных фрагментов
 * - HTMX-совместимые ответы (HTML) и JSON-ответы
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

/**
 * Класс Rest_Api.
 *
 * Управляет REST API эндпоинтами для AJAX-запросов HTMX.
 * Обрабатывает рендеринг различных типов фрагментов:
 * - Посты (по ID)
 * - Термины таксономий (категории, теги, etc)
 * - Кастомные фрагменты (CSS-селекторы)
 *
 * @since 1.0.0
 */
class Rest_Api {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Инициализация REST API.
	 *
	 * Регистрирует маршруты и фильтры WordPress REST API.
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'maybe_send_html_response' ], 10, 4 );
	}

	/**
	 * Зарегистрировать REST API маршруты.
	 *
	 * Регистрирует основной эндпоинт /kkorsakov-htmx/v1/fragment
	 * для получения HTML-фрагментов.
	 *
	 * Маршрут: /kkorsakov-htmx/v1/fragment
	 * Методы: GET, POST
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * Отправить HTML-ответ для HTMX.
	 *
	 * Перехватывает стандартный REST ответ и преобразует его
	 * в HTML для HTMX, если запрос содержит заголовок HX-Request.
	 *
	 * Добавляет заголовки:
	 * - Content-Type: text/html
	 * - HX-Trigger: afterFragmentLoad
	 *
	 * @since 1.0.0
	 *
	 * @param bool           $served  Флаг, был ли уже обработан ответ.
	 * @param WP_REST_Response $result Объект ответа.
	 * @param WP_REST_Request  $request Объект запроса.
	 * @param WP_REST_Server   $server Сервер REST API.
	 * @return bool|mixed Обработанный ответ или исходное значение.
	 */
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

	/**
	 * Получить аргументы эндпоинта.
	 *
	 * Определяет параметры REST API эндпоинта:
	 * - target: идентификатор фрагмента
	 * - context: контекст рендеринга (view/edit)
	 * - args: дополнительные аргументы
	 *
	 * @since 1.0.0
	 *
	 * @return array Массив аргументов эндпоинта.
	 */
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

	/**
	 * Санитизировать дополнительные аргументы.
	 *
	 * Применяет глубокую санитизацию к массиву аргументов.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Значение для санитизации.
	 * @return array Санитизированный массив.
	 */
	public function sanitize_args( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return Security::get_instance()->sanitize_array( $value );
	}

	/**
	 * Проверить права доступа к эндпоинту.
	 *
	 * Верифицирует nonce и проверяет права пользователя.
	 * Использует фильтр kkorsakov_htmx_rest_permissions
	 * для сторонней модификации логики доступа.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_rest_permissions (bool, WP_REST_Request)
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Объект запроса.
	 * @return bool True если доступ разрешен.
	 */
	public function check_permissions( WP_REST_Request $request ): bool {
		$security = Security::get_instance();

		$nonce = $security->get_rest_nonce_from_request();

		if ( ! empty( $nonce ) && ! $security->verify_rest_nonce( $nonce ) ) {
			return false;
		}

		$allowed = $security->can_access_rest_endpoint( 'read' );

		return apply_filters( 'kkorsakov_htmx_rest_permissions', $allowed, $request );
	}

	/**
	 * Обработать запрос фрагмента.
	 *
	 * Основной обработчик REST API запроса.
	 * Определяет тип фрагмента и вызывает соответствующий рендер.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_fragment_output (string) - фильтр вывода фрагмента
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Объект запроса.
	 * @return WP_REST_Response|WP_Error HTML-фрагмент или ошибка.
	 */
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

	/**
	 * Определить тип и отрисовать фрагмент.
	 *
	 * Определяет тип фрагмента по формату параметра target:
	 * - Числовое значение -> пост (render_post_fragment)
	 * - Формат taxonomy:term_id -> термин (render_term_fragment)
	 * - Начинается с # или . -> кастомный (render_custom_fragment)
	 * - Иначе -> фильтр kkorsakov_htmx_render_fragment
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_render_fragment (string) - для нестандартных типов
	 *
	 * @since 1.0.0
	 *
	 * @param string $target Идентификатор фрагмента.
	 * @param string $context Контекст рендеринга (view/edit).
	 * @param array  $args Дополнительные аргументы.
	 * @return string|WP_Error HTML-фрагмент или ошибка.
	 */
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

	/**
	 * Проверить, является ли цель ID поста.
	 *
	 * @since 1.0.0
	 *
	 * @param string $target Проверяемое значение.
	 * @return bool True если это ID поста.
	 */
	protected function is_post_id( string $target ): bool {
		return is_numeric( $target ) && get_post( (int) $target ) !== null;
	}

	/**
	 * Проверить, является ли цель термином таксономии.
	 *
	 * Поддерживаемые форматы:
	 * - category:5 - конкретный термин
	 * - post_tag:10 - тег с ID
	 * - 123 - любой термин по ID
	 *
	 * @since 1.0.0
	 *
	 * @param string $target Проверяемое значение.
	 * @return bool True если это термин.
	 */
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

	/**
	 * Проверить, является ли цель CSS-селектором.
	 *
	 * CSS-селекторы начинаются с # (id) или . (class).
	 *
	 * @since 1.0.0
	 *
	 * @param string $target Проверяемое значение.
	 * @return bool True если это CSS-селектор.
	 */
	protected function is_css_selector( string $target ): bool {
		return str_starts_with( $target, '#' ) || str_starts_with( $target, '.' );
	}

	/**
	 * Отрисовать фрагмент поста.
	 *
	 * Получает пост по ID и рендерит его контент.
	 * Контекст 'edit' требует прав на редактирование поста.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_post_template (string) - кастомный шаблон
	 *
	 * Поиск шаблонов в теме:
	 * 1. htmx-fragment-{post_type}-{context}.php
	 * 2. htmx-fragment-{post_type}.php
	 * 3. htmx-fragment-{context}.php
	 * 4. htmx-fragment.php
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id ID поста.
	 * @param string $context Контекст рендеринга.
	 * @param array  $args Дополнительные аргументы.
	 * @return string HTML контента поста.
	 */
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

	/**
	 * Получить путь к шаблону поста.
	 *
	 * Ищет шаблон фрагмента в теме сначала через фильтр,
	 * затем через locate_template.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_post_template (string) - кастомный путь к шаблону
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post    Объект поста.
	 * @param string   $context Контекст рендеринга.
	 * @return string|false Путь к шаблону или false.
	 */
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

	/**
	 * Отрисовать фрагмент термина таксономии.
	 *
	 * Получает термин и рендерит его описание.
	 * Поддерживает форматы: taxonomy:ID или просто ID.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_term_template (string) - кастомный шаблон
	 *
	 * Поиск шаблонов в теме:
	 * 1. htmx-fragment-{taxonomy}-{context}.php
	 * 2. htmx-fragment-{taxonomy}.php
	 * 3. htmx-fragment-taxonomy-{context}.php
	 * 4. htmx-fragment-taxonomy.php
	 * 5. htmx-fragment-{context}.php
	 * 6. htmx-fragment.php
	 *
	 * @since 1.0.0
	 *
	 * @param string $target Идентификатор термина.
	 * @param string $context Контекст рендеринга.
	 * @param array  $args Дополнительные аргументы.
	 * @return string HTML описания термина.
	 */
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

	/**
	 * Получить путь к шаблону термина.
	 *
	 * Ищет шаблон фрагмента термина в теме.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_term_template (string) - кастомный путь
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Term $term    Объект термина.
	 * @param string   $context Контекст рендеринга.
	 * @return string|false Путь к шаблону или false.
	 */
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

	/**
	 * Отрисовать кастомный фрагмент.
	 *
	 * Обрабатывает фрагменты по CSS-селектору (#id или .class).
	 * Использует фильтры для генерации контента:
	 * - kkorsakov_htmx_custom_fragment - общий фильтр
	 * - kkorsakov_htmx_fragment_{target} - специфичный фильтр
	 *
	 * Примеры target:
	 * - #sidebar - боковая панель
	 * - .comments - комментарии
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_custom_fragment (string)
	 * - kkorsakov_htmx_fragment_{$target} (string)
	 *
	 * @since 1.0.0
	 *
	 * @param string $target CSS-селектор (с префиксом # или .).
	 * @param string $context Контекст рендеринга.
	 * @param array  $args Дополнительные аргументы.
	 * @return string HTML кастомного фрагмента.
	 */
	protected function render_custom_fragment( string $target, string $context, array $args ): string {
		$html = '';

		$html = apply_filters( 'kkorsakov_htmx_custom_fragment', $html, $target, $context, $args );

		if ( empty( $html ) ) {
			$html = apply_filters( "kkorsakov_htmx_fragment_{$target}", $html, $context, $args );
		}

		return $html;
	}

	/**
	 * Отправить HTMX-ответ.
	 *
	 * Создает REST ответ для HTMX-запроса.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html HTML контент.
	 * @return WP_REST_Response Ответ с HTML.
	 */
	protected function send_htmx_response( string $html ): WP_REST_Response {
		return new WP_REST_Response( $html, 200 );
	}
}
