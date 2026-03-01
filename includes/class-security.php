<?php
/**
 * Класс Security (Безопасность).
 *
 * Предоставляет утилиты для обеспечения безопасности:
 * - Проверка и верификация nonce
 * - Проверка прав доступа
 * - Санитизация данных
 * - Экранирование HTML-атрибутов
 * - Определение HTMX-запросов
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
 * Класс Security.
 *
 * Централизованный класс для работы с функциями безопасности WordPress.
 * Использует паттерн Singleton для единой точки доступа.
 *
 * @since 1.0.0
 */
class Security {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Верификация REST API nonce.
	 *
	 * Проверяет валидность nonce для REST API запросов.
	 * Использует WordPress функцию wp_verify_nonce.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $nonce Строка nonce для проверки.
	 * @return bool True если nonce валиден, false в противном случае.
	 */
	public function verify_rest_nonce( ?string $nonce ): bool {
		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Получить nonce из запроса.
	 *
	 * Извлекает nonce из заголовка X-WP-Nonce или параметра _wpnonce.
	 * Автоматически применяет санитизацию.
	 *
	 * Приоритет поиска:
	 *   1. Заголовок X-WP-Nonce
	 *   2. Параметр _wpnonce в запросе
	 *
	 * @since 1.0.0
	 *
	 * @return string|null nonce или null если не найден.
	 */
	public function get_rest_nonce_from_request(): ?string {
		$nonce = '';

		if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
		} elseif ( isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		}

		return ! empty( $nonce ) ? $nonce : null;
	}

	/**
	 * Проверить доступ к REST эндпоинту.
	 *
	 * Проверяет, имеет ли текущий пользователь достаточные права
	 * для доступа к REST API. По умолчанию проверяет capability 'read'.
	 *
	 * @since 1.0.0
	 *
	 * @param string $capability Проверяемая возможность (capability).
	 *                           По умолчанию: 'read'.
	 * @return bool True если доступ разрешен, false в противном случае.
	 */
	public function can_access_rest_endpoint( string $capability = 'read' ): bool {
		return current_user_can( $capability );
	}

	/**
	 * Санитизировать массив данных.
	 *
	 * Рекурсивно санитизирует все элементы массива.
	 * Применяет sanitize_key() для ключей и sanitize_text_field() для строк.
	 * Сохраняет числа и булевы значения без изменений.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Массив данных для санитизации.
	 * @return array Санитизированный массив.
	 */
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

	/**
	 * Экранировать HTML-атрибуты.
	 *
	 * Безопасно экранирует атрибуты HTML-тегов.
	 * Обрабатывает булевы атрибуты (например, checked, disabled).
	 *
	 * Пример использования:
	 *   $attrs = ['class' => 'active', 'data-id' => 5];
	 *   echo '<div ' . $security->escape_html_attributes($attrs) . '>';
	 *   // Результат: <div class="active" data-id="5">
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Массив атрибутов [ключ => значение].
	 * @return string Строка экранированных атрибутов.
	 */
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

	/**
	 * Определить HTMX-запрос.
	 *
	 * Проверяет, является ли текущий запрос HTMX-запросом,
	 * по наличию заголовка HX-Request со значением 'true'.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True если это HTMX-запрос.
	 */
	public function is_htmx_request(): bool {
		return isset( $_SERVER['HTTP_HX_REQUEST'] ) && 'true' === $_SERVER['HTTP_HX_REQUEST'];
	}

	/**
	 * Определить запрос JSON-ответа.
	 *
	 * Проверяет, запрашивает ли клиент JSON-ответ
	 * по заголовку Accept: application/json.
	 * Не применяется для HTMX-запросов (они всегда получают HTML).
	 *
	 * @since 1.0.0
	 *
	 * @return bool True если запрашивается JSON.
	 */
	public function wants_json_response(): bool {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		return false !== strpos( $accept, 'application/json' ) && ! $this->is_htmx_request();
	}
}
