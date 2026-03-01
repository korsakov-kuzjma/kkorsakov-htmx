# Руководство разработчика (DEVELOPER.ru.md)

Руководство по расширению и кастомизации плагина kkorsakov-htmx.

## Оглавление

1. [Введение](#введение)
2. [Архитектура плагина](#архитектура-плагина)
3. [Шорткод [htmx]](#шорткод-htmx)
4. [Фильтры (Hooks)](#фильтры-hooks)
5. [Расширение REST API](#расширение-rest-api)
6. [Добавление новых типов фрагментов](#добавление-новых-типов-фрагментов)
7. [Кастомизация HTMX](#кастомизация-htmx)
8. [Создание собственного компонента](#создание-собственного-компонента)
9. [Шаблоны фрагментов](#шаблоны-фрагментов)
10. [Безопасность](#безопасность)
11. [Примеры расширений](#примеры-расширений)

---

## Введение

Плагин kkorsakov-htmx построен на объектно-ориентированной архитектуре с использованием:
- Паттерна Singleton для управления экземплярами классов
- Пространства имен `Kkorsakov\Htmx`
- Трейтов для повторного использования кода

Все классы автоматически загружаются через SPL-автозагрузчик.

---

## Архитектура плагина

### Структура классов

```
Kkorsakov\Htmx\
├── Plugin          - Главный класс, управляет инициализацией
├── Security        - Утилиты безопасности (nonce, права доступа)
├── Assets          - Управление JS/CSS активами
├── Htmx_Integrator - Интеграция HTMX (шорткоды, блоки)
├── Rest_Api        - REST API эндпоинты
├── Admin_Settings  - Страницы настроек и документации в админке
└── Traits\
    └── Singleton   - Трейт для паттерна Singleton
```

### Страницы админки

Плагин предоставляет страницы в админке WordPress:

- **Страница настроек**: HTMX → Настройки
- **Страница документации**: HTMX → Документация

### Хранение настроек

Настройки сохраняются в таблице опций WordPress:

```php
// Получить настройки
$options = get_option('kkorsakov_htmx_options', []);

// Значения по умолчанию
$defaults = [
    'use_cdn'        => false,
    'force_enqueue'  => false,
    'history_enabled' => true,
    'swap_style'      => 'innerHTML',
];
```

### Основные константы

| Константа | Описание |
|-----------|----------|
| `KKORSAKOV_HTMX_VERSION` | Версия плагина |
| `KKORSAKOV_HTMX_PATH` | Абсолютный путь к папке плагина |
| `KKORSAKOV_HTMX_URL` | URL папки плагина |
| `KKORSAKOV_HTMX_FILE` | Путь к главному файлу плагина |
| `KKORSAKOV_HTMX_MIN_PHP` | Минимальная версия PHP |
| `KKORSAKOV_HTMX_MIN_WP` | Минимальная версия WordPress |

---

## Шорткод [htmx]

Плагин предоставляет шорткод `[htmx]` для быстрого создания HTMX-элементов.

### Параметры шорткода

| Параметр | Обязательный | Описание | По умолчанию |
|----------|--------------|----------|--------------|
| `tag` | Нет | HTML тег элемента | `span` |
| `class` | Нет | CSS классы | (пусто) |
| `target` | Да | CSS селектор целевого элемента | (пусто) |
| `fragment` | Нет | ID фрагмента | значение `target` |
| `trigger` | Нет | Событие триггера | `click` |
| `swap` | Нет | Способ замены | `innerHTML` |
| `url` | Нет | Кастомный URL | (авто) |
| `indicator` | Нет | Селектор индикатора | `.htmx-indicator` |
| `method` | Нет | HTTP метод | `get` |

### Доступные HTML теги

`span`, `div`, `a`, `button`, `img`, `input`, `form`, `ul`, `li`, `section`, `article`, `header`, `footer`, `main`, `aside`, `nav`

### Примеры использования

```php
// Простой элемент
[htmx target="#content"]Загрузить[/htmx]

// Div с классом
[htmx tag="div" target="#sidebar" fragment="widget" class="my-widget"]
  <h3>Заголовок</h3>
[/htmx]

// Ссылка
[htmx tag="a" target="#main" fragment="about" href="/about"]О нас[/htmx]

// Изображение
[htmx tag="img" target="#modal" fragment="123" src="/img.jpg" alt="Кликните"]

// Кнопка
[htmx tag="button" target="#results" method="post" class="btn"]
  Отправить
[/htmx]
```

---

## Фильтры (Hooks)

### Фильтры конфигурации HTMX

#### `kkorsakov_htmx_config`
Позволяет изменить конфигурацию HTMX.

```php
add_filter('kkorsakov_htmx_config', function($config) {
    $config['historyEnabled'] = false;
    $config['defaultSwapStyle'] = 'outerHTML';
    return $config;
});
```

#### `kkorsakov_htmx_force_enqueue`
Принудительная загрузка HTMX на всех страницах.

```php
add_filter('kkorsakov_htmx_force_enqueue', '__return_true');
```

#### `kkorsakov_htmx_use_cdn`
Использовать CDN для загрузки HTMX вместо локальной копии.

```php
add_filter('kkorsakov_htmx_use_cdn', '__return_true');
```

### Фильтры REST API

#### `kkorsakov_htmx_rest_permissions`
Управление доступом к REST API эндпоинтам.

```php
add_filter('kkorsakov_htmx_rest_permissions', function($allowed, $request) {
    // Дополнительная проверка
    return $allowed && current_user_can('edit_posts');
}, 10, 2);
```

#### `kkorsakov_htmx_fragment_url`
Изменить URL фрагмента.

```php
add_filter('kkorsakov_htmx_fragment_url', function($url, $target) {
    return add_query_arg('custom_param', 'value', $url);
}, 10, 2);
```

#### `kkorsakov_htmx_fragment_output`
Фильтрация HTML-вывода фрагмента.

```php
add_filter('kkorsakov_htmx_fragment_output', function($html, $target, $context) {
    return '<div class="wrapped">' . $html . '</div>';
}, 10, 3);
```

---

## Расширение REST API

### Добавление нового эндпоинта

Для добавления собственного REST API эндпоинта используйте стандартный WordPress REST API:

```php
add_action('rest_api_init', function() {
    register_rest_route('kkorsakov-htmx/v1', '/custom-endpoint', [
        'methods'  => 'POST',
        'callback' => 'my_custom_endpoint_callback',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);
});

function my_custom_endpoint_callback($request) {
    return rest_ensure_response([
        'success' => true,
        'data'    => $request->get_json_params()
    ]);
}
```

### Перехват рендеринга фрагментов

#### `kkorsakov_htmx_render_fragment`
Перехват для нестандартных типов фрагментов.

```php
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if (strpos($target, 'my-custom-type:') === 0) {
        return '<div>Кастомный контент для: ' . esc_html($target) . '</div>';
    }
    return $html;
}, 10, 4);
```

---

## Добавление новых типов фрагментов

### Пример: кастомный фрагмент по CSS-селектору

```php
// Добавляем обработку CSS-селектора #featured
add_filter('kkorsakov_htmx_custom_fragment', function($html, $target, $context, $args) {
    if ($target === '#featured') {
        ob_start();
        ?>
        <div id="featured">
            <h2>Избранные посты</h2>
            <?php
            $query = new WP_Query([
                'posts_per_page' => $args['limit'] ?? 5,
                'meta_key'      => 'featured',
                'meta_value'    => '1'
            ]);
            
            while ($query->have_posts()) : $query->the_post();
                echo '<article>' . get_the_title() . '</article>';
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    return $html;
}, 10, 4);
```

### Пример: динамический фрагмент по имени

```php
// Обработка фрагмента по имени: recent-posts
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    switch ($target) {
        case 'recent-posts':
            return '<ul>' . wp_get_recent_posts([
                'numberposts' => 5,
                'post_status' => 'publish'
            ], ARRAY_A) . '</ul>';
            
        case 'popular-tags':
            return '<div class="tags">' . 
                get_tag_cloud(['format' => 'list']) . 
                '</div>';
    }
    return $html;
}, 10, 4);
```

---

## Кастомизация HTMX

### Программная загрузка HTMX

```php
// Принудительная загрузка без шорткода
add_action('wp', function() {
    if (is_single()) {
        Htmx_Integrator::get_instance()->force_enqueue();
    }
});
```

### Модификация URL фрагмента

```php
// Добавление nonce к каждому запросу
add_filter('kkorsakov_htmx_fragment_url', function($url, $target) {
    return add_query_arg('_wpnonce', wp_create_nonce('htmx_request'), $url);
}, 10, 2);
```

### Кастомные атрибуты HTMX

```php
// Добавление заголовков к HTMX запросам
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('htmx-lib', '
        document.addEventListener("htmx:configRequest", function(event) {
            event.detail.headers["X-WP-Nonce"] = kkorsakovHtmxSettings.nonce;
        });
    ');
});
```

---

## Создание собственного компонента

### Создание нового класса-компонента

```php
<?php
/**
 * Мой кастомный компонент
 *
 * @package Kkorsakov\Htmx
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

use Kkorsakov\Htmx\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Custom_Component {

    use Singleton;

    protected function init(): void {
        // Регистрация хуков
        add_filter('kkorsakov_htmx_render_fragment', [$this, 'handle_fragment'], 20, 4);
    }

    public function handle_fragment($html, $target, $context, $args) {
        // Логика обработки
        return $html;
    }
}
```

### Регистрация компонента

В файле `class-plugin.php` или через отдельный плагин:

```php
// Инициализация после основного плагина
add_action('plugins_loaded', function() {
    if (class_exists('Kkorsakov\Htmx\Plugin')) {
        My_Custom_Component::get_instance();
    }
}, 20);
```

---

## Шаблоны фрагментов

### Иерархия шаблонов для постов

```
wp-content/themes/{theme}/
├── htmx-fragment-{post_type}-{context}.php  # htmx-fragment-post-edit.php
├── htmx-fragment-{post_type}.php             # htmx-fragment-post.php
├── htmx-fragment-{context}.php               # htmx-fragment-edit.php
└── htmx-fragment.php                         #通用模板
```

### Иерархия шаблонов для терминов

```
wp-content/themes/{theme}/
├── htmx-fragment-{taxonomy}-{context}.php    # htmx-fragment-category-edit.php
├── htmx-fragment-{taxonomy}.php              # htmx-fragment-category.php
├── htmx-fragment-taxonomy-{context}.php      # htmx-fragment-taxonomy-edit.php
├── htmx-fragment-taxonomy.php                # htmx-fragment-taxonomy.php
├── htmx-fragment-{context}.php               # htmx-fragment-edit.php
└── htmx-fragment.php
```

### Пример шаблона фрагмента

```php
<?php
/**
 * Шаблон фрагмента htmx-fragment.php
 *
 * @var WP_Post $post
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <h2 class="entry-title">
            <a href="<?php the_permalink(); ?>" hx-boost="true">
                <?php the_title(); ?>
            </a>
        </h2>
    </header>
    
    <div class="entry-content">
        <?php the_excerpt(); ?>
    </div>
    
    <footer class="entry-footer">
        <span class="posted-on">
            <?php echo get_the_date(); ?>
        </span>
    </footer>
</article>
```

### Переопределение шаблона через фильтр

```php
add_filter('kkorsakov_htmx_post_template', function($template, $post, $context) {
    // Использовать кастомный шаблон для определенных постов
    if (get_post_meta($post->ID, '_use_custom_template', true)) {
        return get_stylesheet_directory() . '/custom-fragment.php';
    }
    return $template;
}, 10, 3);
```

---

## Безопасность

### Использование методов Security класса

```php
$security = Security::get_instance();

// Проверка nonce
if (!$security->verify_rest_nonce($nonce)) {
    wp_send_json_error(['message' => 'Invalid nonce']);
}

// Санитизация массива данных
$sanitized = $security->sanitize_array($_POST);

// Экранирование атрибутов
$attributes = $security->escape_html_attributes([
    'class' => 'active',
    'data-id' => $id
]);
```

### Проверка HTMX-запроса

```php
$security = Security::get_instance();

if ($security->is_htmx_request()) {
    // Логика для HTMX запросов
}

if ($security->wants_json_response()) {
    // Логика для JSON запросов
}
```

### Кастомные права доступа

```php
add_filter('kkorsakov_htmx_rest_permissions', function($allowed, $request) {
    $context = $request->get_param('context');
    
    if ($context === 'edit') {
        return current_user_can('edit_posts');
    }
    
    return $allowed;
}, 10, 2);
```

---

## Примеры расширений

### Пример 1: Динамическая загрузка контента

```php
// functions.php темы

// 1. Добавляем шорткод для HTMX кнопки
add_shortcode('load-more', function($atts) {
    $atts = shortcode_atts([
        'target'   => '#content',
        'posts'    => '5',
        'button'   => 'Загрузить ещё'
    ], $atts);
    
    $url = rest_url('kkorsakov-htmx/v1/fragment');
    $url = add_query_arg([
        'target' => 'load-more-posts',
        'context' => 'view',
        'args[posts]' => $atts['posts']
    ], $url);
    
    return sprintf(
        '<button class="load-more-btn" 
            hx-get="%s" 
            hx-target="%s"
            hx-swap="afterend"
            hx-indicator=".htmx-indicator">
            %s
            <span class="htmx-indicator">Загрузка...</span>
        </button>',
        esc_url($url),
        esc_attr($atts['target']),
        esc_html($atts['button'])
    );
});

// 2. Обрабатываем запрос фрагмента
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if ($target === 'load-more-posts') {
        $posts = get_posts([
            'posts_per_page' => $args['posts'] ?? 5,
            'offset'         => $args['offset'] ?? 0
        ]);
        
        ob_start();
        foreach ($posts as $post) {
            setup_postdata($post);
            ?>
            <article class="post-item">
                <h3><?php the_title(); ?></h3>
                <div class="excerpt"><?php the_excerpt(); ?></div>
            </article>
            <?php
        }
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    return $html;
}, 10, 4);
```

### Пример 2: AJAX форма поиска

```php
// 1. HTML форма
/*
<form id="search-form" hx-post="<?php echo rest_url('kkorsakov-htmx/v1/fragment'); ?>" 
      hx-target="#search-results" 
      hx-swap="innerHTML">
    <input type="hidden" name="target" value="search-results">
    <input type="search" name="q" placeholder="Поиск..." required>
    <button type="submit">Найти</button>
</form>
<div id="search-results"></div>
*/

// 2. Обработчик
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if ($target === 'search-results' && !empty($args['q'])) {
        $query = new WP_Query([
            's'         => sanitize_text_field($args['q']),
            'posts_per_page' => 10
        ]);
        
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="result-item"><a href="' . get_permalink() . '">' . 
                     get_the_title() . '</a></div>';
            }
            wp_reset_postdata();
            return ob_get_clean();
        }
        
        return '<p>Ничего не найдено</p>';
    }
    return $html;
}, 10, 4);
```

### Пример 3: Интеграция с WooCommerce

```php
// Отображение мини-корзины
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if ($target === 'mini-cart') {
        ob_start();
        if (class_exists('WooCommerce')) {
            ?>
            <div class="mini-cart">
                <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                <span class="cart-total"><?php echo WC()->cart->get_cart_total(); ?></span>
            </div>
            <?php
        }
        return ob_get_clean();
    }
    return $html;
}, 10, 4);
```

### Пример 4:Infinite Scroll (бесконечный скролл)

```php
// Добавляем CSS класс для индикатора
add_filter('kkorsakov_htmx_config', function($config) {
    $config['scrollBehavior'] = 'smooth';
    return $config;
});

// JavaScript для бесконечного скролла
add_action('wp_footer', function() {
    ?>
    <script>
    document.body.addEventListener('htmx:afterSwap', function() {
        // Инициализация после загрузки контента
        initInfiniteScroll();
    });
    </script>
    <?php
});
```

---

## Константы для разработчиков

| Константа | Описание | Пример |
|-----------|----------|--------|
| `KKORSAKOV_HTMX_VERSION` | Текущая версия плагина | '1.0.0' |
| `KKORSAKOV_HTMX_PATH` | Путь к папке плагина | '/wp-content/plugins/kkorsakov-htmx/' |
| `KKORSAKOV_HTMX_URL` | URL папки плагина | 'https://example.com/wp-content/plugins/kkorsakov-htmx/' |
| `KKORSAKOV_HTMX_FILE` | Путь к главному файлу | '/wp-content/plugins/kkorsakov-htmx/kkorsakov-htmx.php' |

---

## Часто задаваемые вопросы (FAQ)

### Как добавить новый тип фрагмента?

Используйте фильтр `kkorsakov_htmx_render_fragment`:

```php
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if (strpos($target, 'my-type:') === 0) {
        // Ваша логика
    }
    return $html;
}, 10, 4);
```

### Как переопределить шаблон?

Создайте файл шаблона в папке темы:
- Для постов: `htmx-fragment-{post_type}.php`
- Для терминов: `htmx-fragment-{taxonomy}.php`

### Как добавить кастомные JS/CSS?

```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('my-custom-style', get_stylesheet_directory_uri() . '/custom.css');
    wp_enqueue_script('my-custom-script', get_stylesheet_directory_uri() . '/custom.js', ['htmx-lib']);
});
```

### Как изменить URL эндпоинта?

URL определяется автоматически, но вы можете изменить его через фильтр `kkorsakov_htmx_fragment_url` или использовать кастомный URL в шорткоде `[htmx url="..."]`.

---

## Отладка

### Включение режима отладки HTMX

```php
add_filter('kkorsakov_htmx_config', function($config) {
    $config['debug'] = true;
    return $config;
});
```

### Логирование запросов

```php
add_action('rest_api_init', function() {
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        error_log('HTMX Request: ' . print_r($request->get_params(), true));
        return $result;
    }, 10, 3);
});
```

---

## Техническая поддержка

- GitHub Issues: https://github.com/korsakov-kuzjma/kkorsakov-htmx/issues
- Документация HTMX: https://htmx.org/docs/
