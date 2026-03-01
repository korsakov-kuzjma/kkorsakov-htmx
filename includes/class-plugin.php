<?php
/**
 * Класс Plugin (Основной класс плагина).
 *
 * Главный класс плагина, управляющий:
 * - Инициализацией всех компонентов
 * - Загрузкой текстового домена (локализация)
 * - Регистрацией хуков активации/деактивации
 * - Проверкой совместимости (PHP, WordPress)
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
 * Класс Plugin.
 *
 * Является точкой входа плагина. Инициализирует все компоненты
 * и управляет хуками активации и деактивации.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Инициализация плагина.
	 *
	 * Вызывается автоматически при создании экземпляра.
	 * Загружает текстовый домен, инициализирует компоненты
	 * и регистрирует хуки.
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {
		$this->load_textdomain();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Загрузить текстовый домен.
	 *
	 * Инициализирует локализацию плагина для поддержки
	 * переводов на разные языки.
	 *
	 * @since 1.0.0
	 */
	protected function load_textdomain(): void {
		load_plugin_textdomain(
			'kkorsakov-htmx',
			false,
			dirname( plugin_basename( KKORSAKOV_HTMX_FILE ) ) . '/languages'
		);
	}

	/**
	 * Инициализировать компоненты.
	 *
	 * Создает экземпляры всех основных классов плагина:
	 * - Security: безопасность
	 * - Assets: управление активами
	 * - Htmx_Integrator: интеграция HTMX
	 * - Rest_Api: REST API эндпоинты
	 *
	 * @since 1.0.0
	 */
	protected function init_components(): void {
		Security::get_instance();
		Assets::get_instance();
		Htmx_Integrator::get_instance();
		Rest_Api::get_instance();
	}

	/**
	 * Зарегистрировать хуки.
	 *
	 * Регистрирует хуки активации и деактивации плагина.
	 *
	 * @since 1.0.0
	 */
	protected function register_hooks(): void {
		register_activation_hook( KKORSAKOV_HTMX_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( KKORSAKOV_HTMX_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Обработчик активации плагина.
	 *
	 * Выполняется при активации плагина:
	 * - Проверка прав пользователя (activate_plugins)
	 * - Проверка минимальной версии PHP
	 * - Проверка минимальной версии WordPress
	 * - Создание опции с версией плагина
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * Обработчик деактивации плагина.
	 *
	 * Выполняется при деактивации плагина:
	 * - Удаление опции версии плагина
	 *
	 * Примечание: данные пользователей и контент не удаляются.
	 *
	 * @since 1.0.0
	 */
	public function deactivate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_option( 'kkorsakov_htmx_version' );
	}
}
