<?php
/**
 * Класс Assets (Управление активами).
 *
 * Отвечает за регистрацию и подключение CSS и JS файлов плагина:
 * - Библиотека HTMX (локальная копия или CDN)
 * - JavaScript инициализации фронтенда
 * - CSS стили для индикаторов загрузки
 * - Конфигурация HTMX через мета-тег
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
 * Класс Assets.
 *
 * Управляет регистрацией и подключением всех активов плагина.
 * Использует WordPress API для регистрации скриптов и стилей.
 *
 * @since 1.0.0
 */
class Assets {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Инициализация класса.
	 *
	 * Регистрирует хуки WordPress для регистрации активов.
	 * Вызывается автоматически при создании экземпляра.
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ] );
		add_filter( 'kkorsakov_htmx_use_cdn', [ $this, 'get_option_use_cdn' ] );
		add_filter( 'kkorsakov_htmx_config', [ $this, 'get_option_config' ] );
	}

	/**
	 * Получить опцию use_cdn.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Значение опции.
	 */
	public function get_option_use_cdn(): bool {
		$options = get_option( 'kkorsakov_htmx_options', [] );
		return ! empty( $options['use_cdn'] );
	}

	/**
	 * Получить опции конфигурации.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Текущая конфигурация.
	 * @return array Обновленная конфигурация.
	 */
	public function get_option_config( array $config ): array {
		$options = get_option( 'kkorsakov_htmx_options', [] );

		if ( isset( $options['history_enabled'] ) ) {
			$config['historyEnabled'] = ! empty( $options['history_enabled'] );
		}

		if ( ! empty( $options['swap_style'] ) ) {
			$config['defaultSwapStyle'] = $options['swap_style'];
		}

		return $config;
	}

	/**
	 * Зарегистрировать все активы.
	 *
	 * Регистрирует библиотеку HTMX, JS и CSS файлы.
	 * Вызывается WordPress автоматически при загрузке страницы.
	 *
	 * @since 1.0.0
	 */
	public function register_assets(): void {
		$this->register_htmx_library();
		$this->register_frontend_script();
		$this->register_frontend_style();
	}

	/**
	 * Зарегистрировать библиотеку HTMX.
	 *
	 * Регистрирует скрипт библиотеки HTMX.
	 * Поддерживает два режима загрузки:
	 * - Локальный файл (по умолчанию)
	 * - CDN (unpkg.com) - включается через фильтр 'kkorsakov_htmx_use_cdn'
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_use_cdn (bool) - использовать CDN
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * Зарегистрировать фронтенд скрипт.
	 *
	 * Регистрирует основной JS файл плагина с зависимостью от HTMX.
	 * Также добавляет локализованные данные для использования в JS:
	 * - root: URL корня REST API
	 * - nonce: WordPress REST nonce
	 * - endpoint: URL эндпоинта фрагментов
	 *
	 * Доступ в JS:
	 *   kkorsakovHtmxSettings.root
	 *   kkorsakovHtmxSettings.nonce
	 *   kkorsakovHtmxSettings.endpoint
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * Зарегистрировать фронтенд стили.
	 *
	 * Регистрирует CSS файл плагина для стилизации
	 * индикаторов загрузки HTMX.
	 *
	 * @since 1.0.0
	 */
	protected function register_frontend_style(): void {
		wp_register_style(
			'kkorsakov-htmx-css',
			KKORSAKOV_HTMX_URL . 'assets/css/frontend.css',
			[],
			KKORSAKOV_HTMX_VERSION
		);
	}

	/**
	 * Подключить активы HTMX.
	 *
	 * Подключает JS и CSS файлы на страницу.
	 * Вызывается автоматически при обнаружении использования HTMX.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_htmx_assets(): void {
		wp_enqueue_script( 'kkorsakov-htmx-js' );
		wp_enqueue_style( 'kkorsakov-htmx-css' );
	}

	/**
	 * Вывести конфигурацию HTMX в HEAD.
	 *
	 * Добавляет мета-тег с конфигурацией HTMX в секцию <head>.
	 * По умолчанию включает:
	 * - historyEnabled: включено历史 API
	 * - defaultSwapStyle: innerHTML
	 * - defaultSwapDelay: 0мс
	 * - defaultSettleDelay: 20мс
	 *
	 * Фильтры:
	 * - kkorsakov_htmx_config (array) - изменить конфигурацию
	 *
	 * @since 1.0.0
	 */
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
