<?php
/**
 * Трейт Singleton (Одиночка).
 *
 * Реализует паттерн "Одиночка" для классов плагина.
 * Обеспечивает создание только одного экземпляра класса
 * и предоставляет глобальную точку доступа к нему.
 *
 * Использование:
 *   use Kkorsakov\Htmx\Traits\Singleton;
 *
 *   class My_Class {
 *       use Singleton;
 *
 *       protected function init(): void {
 *           // Код инициализации
 *       }
 *   }
 *
 *   $instance = My_Class::get_instance();
 *
 * @package Kkorsakov\Htmx
 * @subpackage Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Трейт Singleton.
 *
 * Предотвращает создание множественных экземпляров класса,
 * вызывая метод init() при первом создании объекта.
 * Защищает методы __clone и __wakeup от дублирования.
 *
 * @since 1.0.0
 */
trait Singleton {

	/**
	 * Экземпляр класса.
	 *
	 * Хранит единственный экземпляр класса для предотвращения
	 * создания дубликатов.
	 *
	 * @since 1.0.0
	 * @var object|null
	 */
	protected static $instance = null;

	/**
	 * Получить экземпляр класса.
	 *
	 * Возвращает существующий экземпляр класса или создает новый,
	 * если он ещё не был создан. Реализует ленивую инициализацию.
	 *
	 * @since 1.0.0
	 *
	 * @return static Единственный экземпляр класса.
	 */
	final public static function get_instance(): self {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Конструктор.
	 *
	 * Приватный конструктор предотвращает прямое создание объекта
	 * через оператор new. Автоматически вызывает метод init()
	 * после создания экземпляра.
	 *
	 * @since 1.0.0
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Инициализация экземпляра.
	 *
	 * Переопределите этот метод в классе, использующем трейт,
	 * для выполнения начальной настройки. Вызывается автоматически
	 * после создания объекта.
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {}

	/**
	 * Запрет клонирования.
	 *
	 * Предотвращает создание копии объекта через clone.
	 * Генерирует фатальную ошибку при попытке клонирования.
	 *
	 * @since 1.0.0
	 */
	final public function __clone() {}

	/**
	 * Запрет десериализации.
	 *
	 * Предотвращает десериализацию объекта через unserialize().
	 * Генерирует фатальную ошибку при попытке десериализации.
	 *
	 * @since 1.0.0
	 */
	final public function __wakeup() {}
}
