<?php
/**
 * Класс Htmx_Integrator (Интегратор HTMX).
 *
 * Основной класс интеграции библиотеки HTMX в WordPress:
 * - Автоматическое обнаружение использования HTMX
 * - Подключение JS/CSS только при необходимости
 * - Шорткод [htmx] для создания HTMX-элементов
 * - Поддержка Gutenberg-блока kkorsakov/htmx-fragment
 * - Вывод конфигурации HTMX в meta-теге
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

/**
 * Класс Htmx_Integrator.
 *
 * Обеспечивает интеграцию HTMX с WordPress.
 * Автоматически определяет, когда нужно загружать HTMX,
 * на основе использования шорткода или блока Gutenberg.
 *
 * @since 1.0.0
 */
class Htmx_Integrator {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Флаг необходимости загрузки HTMX.
	 *
	 * Устанавливается в true, если на странице обнаружено
	 * использование HTMX (шорткод или блок).
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $should_enqueue = false;

	/**
	 * Инициализация интегратора.
	 *
	 * Регистрирует все необходимые хуки WordPress:
	 * - wp_enqueue_scripts: подключение скриптов
	 * - wp_head: вывод конфигурации
	 * - Шорткод [htmx]
	 * - render_block: определение блока
	 * - template_redirect: анализ контента
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_htmx' ] );
		add_action( 'wp_head', [ $this, 'output_htmx_config' ], 1 );
		add_shortcode( 'htmx', [ $this, 'render_shortcode' ] );
		add_filter( 'render_block', [ $this, 'check_block_for_htmx' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'detect_htmx_usage' ] );

		add_filter( 'kkorsakov_htmx_force_enqueue', [ $this, 'get_option_force_enqueue' ] );
	}

	/**
	 * Получить опцию force_enqueue.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Значение опции.
	 */
	public function get_option_force_enqueue(): bool {
		$options = get_option( 'kkorsakov_htmx_options', [] );
		return ! empty( $options['force_enqueue'] );
	}

	/**
	 * Определить использование HTMX на странице.
	 *
	 * Анализирует контент текущего поста на наличие:
	 * - Шорткода [htmx]
	 * - Блока Gutenberg kkorsakov/htmx-fragment
	 *
	 * Вызывается на хуке template_redirect для оптимизации
	 * - активы загружаются только при необходимости.
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * Проверить наличие HTMX блока в массиве блоков.
	 *
	 * Рекурсивно проверяет все блоки и вложенные блоки
	 * на наличие блока kkorsakov/htmx-fragment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $blocks Массив блоков Gutenberg.
	 * @return bool True если найден HTMX блок.
	 */
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

	/**
	 * Обработчик рендеринга блока Gutenberg.
	 *
	 * Срабатывает при рендеринге блока kkorsakov/htmx-fragment.
	 * Устанавливает флаг should_enqueue для загрузки активов.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_content HTML-содержимое блока.
	 * @param array  $block          Массив данных блока.
	 * @return string Неизмененное содержимое блока.
	 */
	public function check_block_for_htmx( string $block_content, array $block ): string {
		if ( 'kkorsakov/htmx-fragment' === $block['blockName'] ) {
			$this->should_enqueue = true;
		}

		return $block_content;
	}

	/**
	 * Подключить активы HTMX при необходимости.
	 *
	 * Проверяет флаг should_enqueue и фильтр kkorsakov_htmx_force_enqueue,
	 * затем подключает JS и CSS файлы.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_force_enqueue (bool) - принудительная загрузка
	 *
	 * @since 1.0.0
	 */
	public function maybe_enqueue_htmx(): void {
		$force_enqueue = apply_filters( 'kkorsakov_htmx_force_enqueue', false );

		if ( $this->should_enqueue || $force_enqueue ) {
			Assets::get_instance()->enqueue_htmx_assets();
		}
	}

	/**
	 * Вывести конфигурацию HTMX.
	 *
	 * Добавляет мета-тег htmx-config в секцию <head>,
	 * если HTMX используется на странице.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_force_enqueue (bool) - принудительная загрузка
	 *
	 * @since 1.0.0
	 */
	public function output_htmx_config(): void {
		$force_enqueue = apply_filters( 'kkorsakov_htmx_force_enqueue', false );

		if ( ! $this->should_enqueue && ! $force_enqueue ) {
			return;
		}

		Assets::get_instance()->add_htmx_config_meta();
	}

	/**
	 * Обработчик шорткода [htmx].
	 *
	 * Создает HTML-элемент с атрибутами HTMX для AJAX-запросов.
	 *
	 * Пример использования:
	 *   [htmx target="#content" fragment="sidebar" trigger="click"]
	 *   [htmx tag="div" target="#content" fragment="my-content"]
	 *   [htmx tag="img" target="#preview" fragment="123"]
	 *
	 * Параметры шорткода:
	 * - tag       (string) - HTML тег (span, div, a, img, button и т.д.) - по умолчанию: span
	 * - class     (string) - CSS классы для элемента
	 * - target    (string) - CSS-селектор целевого элемента (обязательно)
	 * - fragment  (string) - ID фрагмента для загрузки
	 * - trigger   (string) - событие триггера (click, submit, etc)
	 * - swap      (string) - способ замены (innerHTML, outerHTML, etc)
	 * - url       (string) - кастомный URL для запроса
	 * - indicator (string) - CSS-селектор индикатора загрузки
	 * - method    (string) - HTTP метод (get, post)
	 *
	 * @since 1.0.0
	 *
	 * @param array      $atts    Атрибуты шорткода.
	 * @param string|null $content Содержимое внутри шорткода.
	 * @return string HTML-элемент с HTMX атрибутами.
	 */
	public function render_shortcode( array $atts, ?string $content = null ): string {
		$this->should_enqueue = true;

		$atts = shortcode_atts(
			[
				'tag'       => 'span',
				'class'     => '',
				'target'    => '',
				'fragment'  => '',
				'trigger'   => 'click',
				'swap'      => 'innerHTML',
				'url'       => '',
				'indicator' => '.htmx-indicator',
				'method'    => 'get',
			],
			$atts,
			'htmx'
		);

		$allowed_tags = [ 'span', 'div', 'a', 'button', 'img', 'input', 'form', 'ul', 'li', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav' ];
		$tag = in_array( $atts['tag'], $allowed_tags, true ) ? $atts['tag'] : 'span';

		$fragment_id = ! empty( $atts['fragment'] ) ? $atts['fragment'] : $atts['target'];
		$url = $this->get_fragment_url( $atts['url'], $fragment_id );

		$htmx_attrs = [
			'hx-' . sanitize_key( $atts['method'] ) => esc_url( $url ),
			'hx-trigger'                            => esc_attr( $atts['trigger'] ),
			'hx-target'                             => esc_attr( $atts['target'] ),
			'hx-swap'                               => esc_attr( $atts['swap'] ),
		];

		if ( ! empty( $atts['indicator'] ) ) {
			$htmx_attrs['hx-indicator'] = esc_attr( $atts['indicator'] );
		}

		if ( ! empty( $atts['class'] ) ) {
			$htmx_attrs['class'] = esc_attr( $atts['class'] );
		}

		$attributes = Security::get_instance()->escape_html_attributes( $htmx_attrs );

		$inner_content = do_shortcode( $content ?? '' );

		if ( 'img' === $tag ) {
			$output = sprintf(
				'<img %s alt="%s">',
				$attributes,
				esc_attr( strip_tags( $inner_content ) )
			);
		} elseif ( 'a' === $tag ) {
			$output = sprintf(
				'<a %s>%s</a>',
				$attributes,
				$inner_content
			);
		} else {
			$output = sprintf(
				'<%s %s>%s</%s>',
				$tag,
				$attributes,
				$inner_content,
				$tag
			);
		}

		return $output;
	}

	/**
	 * Получить URL для фрагмента.
	 *
	 * Формирует URL для REST API запроса фрагмента.
	 * Если передан кастомный URL - использует его,
	 * иначе формирует стандартный URL эндпоинта.
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_fragment_url (string) - изменить URL
	 *
	 * @since 1.0.0
	 *
	 * @param string $custom_url Кастомный URL (если есть).
	 * @param string $target     Идентификатор фрагмента.
	 * @return string Обработанный URL.
	 */
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

	/**
	 * Принудительно включить загрузку HTMX.
	 *
	 * Устанавливает флаг should_enqueue в true.
	 * Полезно для тем или плагинов, которые программно
	 * используют HTMX без шорткода или блока.
	 *
	 * Пример использования:
	 *   add_action('wp', function() {
	 *       Htmx_Integrator::get_instance()->force_enqueue();
	 *   });
	 *
	 * @since 1.0.0
	 */
	public function force_enqueue(): void {
		$this->should_enqueue = true;
	}
}
