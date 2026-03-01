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

/**
 * Основной файл плагина kkorsakov-htmx.
 *
 * Этот файл является точкой входа плагина. Он подключает все
 * необходимые компоненты и инициализирует автозагрузку классов.
 *
 * Структура плагина:
 * - kkorsakov-htmx.php         - Главный файл (этот)
 * - includes/class-plugin.php   - Основной класс плагина
 * - includes/class-security.php - Утилиты безопасности
 * - includes/class-assets.php  - Управление активами
 * - includes/class-htmx-integrator.php - Интеграция HTMX
 * - includes/class-rest-api.php - REST API эндпоинты
 * - includes/traits/trait-singleton.php - Трейт Singleton
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

/**
 * Защита от прямого доступа.
 *
 * Предотвращает прямой доступ к файлу, если он вызван
 * не через WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Определение констант плагина.
 *
 * Константы используются во всем плагине для:
 * - Версии плагина
 * - Минимальных требований (PHP, WordPress)
 * - Путей и URL файлов плагина
 */

// Версия плагина.
define( 'KKORSAKOV_HTMX_VERSION', '1.0.0' );

// Минимальная требуемая версия PHP.
define( 'KKORSAKOV_HTMX_MIN_PHP', '7.4' );

// Минимальная требуемая версия WordPress.
define( 'KKORSAKOV_HTMX_MIN_WP', '6.0' );

// Путь к основному файлу плагина.
define( 'KKORSAKOV_HTMX_FILE', __FILE__ );

// Абсолютный путь к директории плагина.
define( 'KKORSAKOV_HTMX_PATH', plugin_dir_path( __FILE__ ) );

// URL директории плагина.
define( 'KKORSAKOV_HTMX_URL', plugin_dir_url( __FILE__ ) );

/**
 * Автозагрузчик классов плагина.
 *
 * Регистрирует функцию автозагрузки для классов
 * пространства имен Kkorsakov\Htmx.
 *
 * Ищет файлы по двум алгоритмам:
 * 1. class-{ClassName}.php (class-htmx-integrator.php)
 * 2. traits/trait-{TraitName}.php
 *
 * @since 1.0.0
 *
 * @param string $class Полное имя класса с пространством имен.
 */
spl_autoload_register(
	function ( string $class ): void {
		// Префикс пространства имен для этого плагина.
		$prefix   = 'Kkorsakov\\Htmx\\';

		// Базовая директория для классов плагина.
		$base_dir = KKORSAKOV_HTMX_PATH . 'includes/';

		// Длина префикса пространства имен.
		$len = strlen( $prefix );

		// Если класс не использует наш префикс - пропускаем.
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Относительное имя класса (без префикса).
		$relative_class = substr( $class, $len );

		// Формируем путь к файлу (PSR-4 стиль).
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// Если файл существует - подключаем.
		if ( file_exists( $file ) ) {
			require $file;
			return;
		}

		// Альтернативный алгоритм: class-{filename}.php
		$lowercase_relative_class = strtolower( str_replace( [ '\\', '_' ], '-', $relative_class ) );
		$file = $base_dir . 'class-' . $lowercase_relative_class . '.php';

		if ( file_exists( $file ) ) {
			require $file;
			return;
		}

		// Обработка трейтов.
		if ( strpos( $relative_class, 'Traits\\' ) === 0 ) {
			$trait_name = str_replace( 'Traits\\', '', $relative_class );
			$trait_file = $base_dir . 'traits/trait-' . strtolower( $trait_name ) . '.php';

			if ( file_exists( $trait_file ) ) {
				require $trait_file;
			}
		}
	}
);

/**
 * Инициализация плагина.
 *
 * Создает экземпляр основного класса Plugin,
 * который инициализирует все компоненты плагина.
 *
 * Вызывается на хуке plugins_loaded (приоритет по умолчанию = 0).
 *
 * @since 1.0.0
 */
function init(): void {
	Plugin::get_instance();
}

// Запуск инициализации при загрузке плагинов WordPress.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
