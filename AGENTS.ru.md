# AGENTS.ru.md

## Описание проекта

Плагин WordPress `kkorsakov-htmx` предназначен для бесшовной интеграции библиотеки HTMX в экосистему WordPress и предоставления специализированного эндпоинта в REST API для обработки HTMX-запросов.

## Метаданные проекта

| Параметр | Значение |
|----------|----------|
| **Название плагина** | kkorsakov-htmx |
| **Автор** | kkorsakov |
| **Страница автора** | https://github.com/korsakov-kuzjma |
| **Страница плагина** | https://github.com/korsakov-kuzjma/kkorsakov-htmx |
| **Лицензия** | GPL-2.0-or-later |
| **Мин. версия WordPress** | 6.0 |
| **Мин. версия PHP** | 7.4 |
| **Текстдомен** | `kkorsakov-htmx` |

## Архитектура плагина

### Структура файлов
```text
kkorsakov-htmx/
├── kkorsakov-htmx.php              # Главный файл плагина (bootstrap)
├── README.md                       # Публичная документация (для пользователей)
├── DEVELOPER.md                    # Руководство разработчика (на английском)
├── DEVELOPER.ru.md                # Руководство разработчика (на русском)
├── AGENTS.md                       # Инструкции для AI-агентов и разработчиков (на английском)
├── AGENTS.ru.md                    # Инструкции для AI-агентов и разработчиков (на русском)
├── CHANGELOG.md                    # История изменений (Keep a Changelog)
├── includes/
│   ├── class-plugin.php           # Главный класс плагина (инициализация, хуки)
│   ├── class-htmx-integrator.php   # Интеграция HTMX: шорткод, атрибуты, обнаружение
│   ├── class-rest-api.php          # Регистрация и обработка REST API endpoints
│   ├── class-assets.php            # Управление JS/CSS активами
│   ├── class-security.php          # Утилиты: nonce, санитизация, capabilities
│   ├── class-admin-settings.php    # Страницы настроек и документации в админке
│   └── traits/
│       └── trait-singleton.php     # Паттерн Singleton для классов плагина
├── assets/
│   ├── js/
│   │   ├── htmx.min.js             # Локальная копия HTMX (не CDN)
│   │   └── frontend.js             # Инициализация и кастомные события
│   └── css/
│       └── frontend.css            # Базовые стили для HTMX-индикаторов
├── languages/                      # .pot и .mo файлы для локализации
└── tests/
    ├── phpunit/                    # Юнит-тесты
    └── e2e/                        # Интеграционные тесты (Playwright)
```

## Стандарты кодирования

### PHP / WordPress
```php
<?php
/**
 * Пример заголовка файла
 *
 * @package Kkorsakov_Htmx
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

// Следовать WordPress Coding Standards
// Использовать префикс kkorsakov_htmx_ для глобальных функций/хуков
// Применять строгую типизацию и PHPDoc для всех публичных элементов
```

**Требования:**
- Все классы в пространстве имён `Kkorsakov\Htmx`
- Файлы классов: `class-{classname}.php`, автозагрузка через `spl_autoload_register` или Composer PSR-4
- Функции и хуки: префикс `kkorsakov_htmx_`
- Константы: префикс `KKORSAKOV_HTMX_`
- Избегать глобального состояния, использовать dependency injection

### Безопасность (обязательно)

| Тип данных | Санитизация (вход) | Экранирование (выход) |
|------------|-------------------|----------------------|
| Текст | `sanitize_text_field()` | `esc_html()` |
| HTML-блок | `wp_kses_post()` | `wp_kses_post()` |
| URL | `esc_url_raw()` | `esc_url()` |
| JS-переменная | — | `wp_json_encode()` + `esc_js()` |
| Атрибут HTML | — | `esc_attr()` |

**Критические правила:**
1. Все REST API запросы: проверять `X-WP-Nonce` или `wp_rest_nonce`
2. Все state-changing операции: `check_ajax_referer()` или `wp_verify_nonce()`
3. Проверка прав: `current_user_can( $capability )` перед выполнением действий
4. SQL-запросы: только `$wpdb->prepare()` или WP_Query с валидацией
5. Никаких `eval()`, `create_function()`, сериализованных пользовательских данных

### HTMX-специфичные требования
- Подключать `htmx.min.js` только на страницах, где используется `[htmx]` шорткод или блок
- Добавлять `HX-Trigger` и `HX-Headers` с WordPress nonce для аутентифицированных запросов
- Предоставлять фильтры для кастомизации атрибутов:
  ```php
  apply_filters( 'kkorsakov_htmx_config', array $config ): array
  ```

## REST API спецификация

### Основной эндпоинт
- **Route**: `/kkorsakov-htmx/v1/fragment`
- **Methods**: `GET`, `POST`
- **Namespace**: `kkorsakov-htmx/v1`
- **Permission callback**: настраивается через фильтр `kkorsakov_htmx_rest_permissions`

### Параметры запроса
```php
[
    'target' => [
        'type' => 'string',
        'required' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'description' => 'Идентификатор целевого фрагмента (ID, CSS селектор или slug)'
    ],
    'context' => [
        'type' => 'string',
        'default' => 'view',
        'enum' => ['view', 'edit'],
    ],
    'args' => [
        'type' => 'object',
        'default' => [],
        'sanitize_callback' => 'sanitize_args',
        'description' => 'Дополнительные аргументы для рендеринга фрагмента'
    ]
]
```

### Формат ответа (HTMX-compatible)
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
HX-Trigger: afterFragmentLoad
X-WP-Nonce: {new_nonce_if_rotated}

<div id="fragment-target">
    <!-- отрендеренный HTML фрагмент -->
</div>
```

**Важно:** Для HTMX-запросов (`HX-Request: true`) возвращать чистый HTML, а не JSON. Для AJAX-запросов с `Accept: application/json` возвращать стандартный WP REST ответ.

## Разработка

### Локальное окружение
```bash
# Клонирование
git clone https://github.com/korsakov-kuzjma/kkorsakov-htmx.git
cd kkorsakov-htmx

# Установка PHP-зависимостей (для разработки)
composer install

# Запуск линтеров
composer lint          # PHPCS
composer lint:fix      # PHPCBF
composer test          # PHPUnit
composer test:e2e      # Playwright (требует Node.js)
```

### Docker (опционально)
```yaml
# docker-compose.yml (пример)
version: '3.8'
services:
  wordpress:
    image: wordpress:php8.2-apache
    volumes:
      - .:/var/www/html/wp-content/plugins/kkorsakov-htmx
    environment:
      WORDPRESS_DEBUG: 1
```

## Чеклист безопасности перед PR

- [ ] Все входные данные проходят санитизацию через `sanitize_*()` или кастомные валидаторы
- [ ] Все выходные данные экранированы через `esc_*()` функции
- [ ] Nonces проверены для всех запросов, изменяющих состояние
- [ ] Проверка `current_user_can()` выполнена перед привилегированными операциями
- [ ] REST API endpoint имеет `permission_callback`
- [ ] Нет прямого вывода `$_GET`/`$_POST` без экранирования
- [ ] SQL-запросы используют подготовленные выражения
- [ ] Файлы ассетов загружаются через `wp_enqueue_script/style` с versioning
- [ ] Локализация: все строки обернуты в `__()`/`_e()` с текстовым доменом

## Документация и хуки

### Публичные хуки для разработчиков
```php
// Изменение конфигурации HTMX
apply_filters( 'kkorsakov_htmx_config', array $config ): array

// Принудительная загрузка HTMX на всех страницах
apply_filters( 'kkorsakov_htmx_force_enqueue', bool $force ): bool

// Использовать CDN вместо локального файла
apply_filters( 'kkorsakov_htmx_use_cdn', bool $use_cdn ): bool

// Изменить URL фрагмента перед запросом
apply_filters( 'kkorsakov_htmx_fragment_url', string $url, string $target ): string

// Модификация HTML-фрагмента перед отправкой
apply_filters( 'kkorsakov_htmx_fragment_output', string $html, string $target, string $context ): string

// Обработка кастомных типов фрагментов (CSS селекторы типа #my-div)
apply_filters( 'kkorsakov_htmx_custom_fragment', string $html, string $target, string $context, array $args ): string

// Обработка кастомного фрагмента по имени (не CSS-селектор)
apply_filters( 'kkorsakov_htmx_render_fragment', string $html, string $target, string $context, array $args ): string

// Проверка прав доступа к REST API
apply_filters( 'kkorsakov_htmx_rest_permissions', bool $allowed, WP_REST_Request $request ): bool
```

### Требования к PHPDoc
```php
/**
 * Краткое описание метода.
 *
 * @since 1.0.0
 *
 * @param string $target Идентификатор фрагмента.
 * @param array  $args   Дополнительные аргументы.
 * @return string|WP_Error HTML-фрагмент или ошибка.
 */
public function get_fragment( string $target, array $args = [] ) {
    // ...
}
```

## Версионирование и релизы

- Следовать [Semantic Versioning 2.0.0](https://semver.org/lang/ru/)
- Теги: `v{MAJOR}.{MINOR}.{PATCH}` (например, `v1.2.3`)
- Вести `CHANGELOG.md` в формате [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/)
- Перед релизом:
  1. Обновить версию в заголовке главного файла плагина
  2. Прогнать все тесты и линтеры
  3. Проверить совместимость с последней версией WordPress

## Лицензия

Плагин распространяется под лицензией **GNU General Public License v2.0 or later**. Весь код, включая сторонние библиотеки, должен быть совместим с GPL. HTMX (MIT License) совместим с GPL и может использоваться без ограничений.
