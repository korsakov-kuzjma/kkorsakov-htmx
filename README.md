# kkorsakov-htmx

Плагин для бесшовной интеграции HTMX библиотеки в WordPress с расширенным REST API для обработки фрагментных запросов.

## Содержание

- [Возможности](#возможности)
- [Требования](#требования)
- [Установка](#установка)
- [Быстрый старт](#быстрый-старт)
- [Использование шорткода [htmx]](#использование-шорткода-htmx)
- [Использование REST API](#использование-rest-api)
- [Кастомизация шаблонов](#кастомизация-шаблонов)
- [PHP Фильтры](#php-фильтры)
- [JavaScript API](#javascript-api)
- [Best Practices](#best-practices)
- [FAQ](#faq)

## Возможности

- Автоматическая загрузка HTMX библиотеки только на нужных страницах
- REST API endpoint для фрагментных запросов (`/kkorsakov-htmx/v1/fragment`)
- Поддержка шорткода `[htmx]` для быстрого создания HTMX-элементов
- Интеграция с WordPress Theme templates
- WP REST API Nonce автоматическая передача
- Фильтры для кастомизации поведения
- Поддержка контекстов `view` и `edit`

## Требования

- WordPress 6.0 или выше
- PHP 7.4 или выше
- Composer (для разработки)

## Установка

### Через WordPress Dashboard

1. Загрузите файл плагина в `/wp-content/plugins/kkorsakov-htmx`
2. Активируйте плагин через меню 'Плагины' в WordPress
3. Готово!

### Через Composer

```bash
composer require kkorsakov/htmx
```

### Ручная установка

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/korsakov-kuzjma/kkorsakov-htmx.git kkorsakov-htmx
```

Затем активируйте плагин через WordPress Dashboard.

## Быстрый старт

### Пример 1: Простая кнопка с загрузкой фрагмента

```php
[htmx target="#result" trigger="click" url="/wp-json/kkorsakov-htmx/v1/fragment?target=123"]
  Загрузить контент
[/htmx]
<div id="result"></div>
```

Этот пример создаст кнопку, которая при нажатии загрузит контент поста ID 123 в div с id="result".

### Пример 2: Форма с AJAX сабмитом

```php
<form hx-post="/wp-json/kkorsakov-htmx/v1/fragment?target=contact-form" hx-target="#form-response">
  <input type="email" name="email" placeholder="Ваш email" required>
  <button type="submit">Подписаться</button>
</form>
<div id="form-response"></div>
```

### Пример 3: Использование атрибутов напрямую ( Gutenberg block )

В редакторе Gutenberg добавьте HTML блок:

```html
<button 
  hx-get="/wp-json/kkorsakov-htmx/v1/fragment?target=products"
  hx-trigger="click"
  hx-target="#products-container">
  Загрузить товары
</button>
<div id="products-container"></div>
```

## Использование шорткода [htmx]

### Параметры шорткода

| Параметр | Обязательный | Описание | По умолчанию |
|----------|--------------|----------|--------------|
| `target` | Нет | CSS selector целевого элемента | (пусто) |
| `trigger` | Нет | Событие, которое инициирует запрос | `click` |
| `swap` | Нет | Способ замены контента | `innerHTML` |
| `url` | Нет | Полный URL для запроса | Автогенерация |
| `indicator` | Нет | CSS selector элемента-индикатора загрузки | `.htmx-indicator` |
| `method` | Нет | HTTP метод запроса | `get` |

### Примеры шорткода

#### Простой линк

```php
[htmx target="#content" trigger="click"]Нажмите для загрузки[/htmx]
<div id="content"></div>
```

#### Ручное указание URL

```php
[htmx target="#widget" url="https://example.com/custom-endpoint/"]
  Обновить виджет
[/htmx]
<div id="widget"></div>
```

#### С индикатором загрузки

```php
[htmx target="#posts" trigger="click" indicator=".loader"]
  Загрузить посты
[/htmx]
<div class="loader" style="display:none;">⏳ Загрузка...</div>
<div id="posts"></div>
```

#### С POST запросом и обновлением атрибутов

```php
[htmx method="post" target="#status" trigger="click"]
  Отметить как прочитанное
[/htmx]
<span id="status">Новые</span>
```

## Использование REST API

### Endpoint: `/kkorsakov-htmx/v1/fragment`

**Методы:** `GET`, `POST`  
**Namespace:** `kkorsakov-htmx/v1`

### Параметры запроса

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `target` | string | Да | Идентификатор фрагмента (ID поста, слаг, CSS selector) |
| `context` | string | Нет | Контекст рендеринга: `view` или `edit` | `view` |
| `args` | object | Нет | Дополнительные аргументы для шаблона |

### Примеры запросов

#### Запрос через HTMX атрибуты

```html
<div hx-get="/wp-json/kkorsakov-htmx/v1/fragment?target=123" hx-target="#content">
  Загрузить пост
</div>
<div id="content"></div>
```

#### Запрос через JavaScript

```javascript
fetch('/wp-json/kkorsakov-htmx/v1/fragment?target=123', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
  }
})
  .then(response => response.text())
  .then(html => {
    document.getElementById('content').innerHTML = html;
  });
```

#### Формат ответа для HTMX запросов

```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
HX-Trigger: afterFragmentLoad

<div class="post-content">
  <h1>Название поста</h1>
  <p>Контент поста...</p>
</div>
```

#### Формат ответа для обычных AJAX запросов

```json
{
  "success": true,
  "html": "<div class=\"post-content\">...</div>"
}
```

### Поддерживаемые типы target

#### 1. ID поста
```
?target=123
```
Загружает контент поста с ID 123.

#### 2. Слаг термина таксономии
```
?target=category:5
```
Загружает архивную страницу категории с ID 5.

#### 3. CSS selector
```
?target=#custom-section
```
Загружает кастомный фрагмент через хук `kkorsakov_htmx_custom_fragment`.

#### 4. ID термина таксономии
```
?target=5
```
Если термин с ID 5 существует, загружает его архив.

## Кастомизация шаблонов

Плагин ищет шаблоны в следующих местах (в порядке приоритета):

### Для постов

```
htmx-fragment-{post_type}-{context}.php
htmx-fragment-{post_type}.php
htmx-fragment-{context}.php
htmx-fragment.php
```

**Пример:** Для поста типа `product` в контексте `view` будет искаться:
1. `htmx-fragment-product-view.php`
2. `htmx-fragment-product.php`

### Для терминов таксономий

```
htmx-fragment-{taxonomy}-{context}.php
htmx-fragment-{taxonomy}.php
htmx-fragment-taxonomy-{context}.php
htmx-fragment-taxonomy.php
htmx-fragment-{context}.php
htmx-fragment.php
```

**Пример:** Для категории в контексте `edit`:
1. `htmx-fragment-category-edit.php`
2. `htmx-fragment-category.php`

### Пример шаблона поста

**Файл:** `htmx-fragment-post-view.php`

```php
<?php
/**
 * Template for rendering post fragment in view context
 *
 * @package kkorsakov-htmx
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post = get_post();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <h2 class="entry-title"><?php the_title(); ?></h2>
    </header>
    
    <div class="entry-content">
        <?php the_content(); ?>
    </div>
    
    <footer class="entry-footer">
        <span class="posted-on"><?php echo get_the_date(); ?></span>
        <span class="byline">by <?php the_author(); ?></span>
    </footer>
</article>
```

### Пример шаблона термина

**Файл:** `htmx-fragment-category-view.php`

```php
<?php
/**
 * Template for rendering category term fragment
 *
 * @package kkorsakov-htmx
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$term = get_queried_object();
?>

<section class="term-archive">
    <header class="term-header">
        <h2 class="term-title"><?php echo esc_html( $term->name ); ?></h2>
        <p class="term-description"><?php echo wp_kses_post( term_description() ); ?></p>
    </header>
    
    <div class="term-posts">
        <?php
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 5,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ),
            ),
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) : $query->the_post();
                get_template_part( 'template-parts/content', 'excerpt' );
            endwhile;
            wp_reset_postdata();
        endif;
        ?>
    </div>
</section>
```

## PHP Фильтры

### `kkorsakov_htmx_config`

Изменяет конфигурацию HTMX.

```php
add_filter( 'kkorsakov_htmx_config', function( $config ) {
    $config['historyEnabled'] = false;
    $config['defaultSwapStyle'] = 'outerHTML';
    return $config;
} );
```

### `kkorsakov_htmx_force_enqueue`

Принудительно загружает HTMX на всех страницах.

```php
add_filter( 'kkorsakov_htmx_force_enqueue', '__return_true' );
```

### `kkorsakov_htmx_fragment_url`

Изменяет URL для фрагментного запроса.

```php
add_filter( 'kkorsakov_htmx_fragment_url', function( $url, $target ) {
    if ( $target === 'special' ) {
        $url = home_url( '/custom-endpoint/?target=' . $target );
    }
    return $url;
}, 10, 2 );
```

### `kkorsakov_htmx_fragment_output`

Изменяет HTML фрагмента перед отправкой.

```php
add_filter( 'kkorsakov_htmx_fragment_output', function( $html, $target, $context ) {
    if ( 'special' === $target ) {
        $html = '<div class="special">' . $html . '</div>';
    }
    return $html;
}, 10, 3 );
```

### `kkorsakov_htmx_rest_permissions`

Управляет правами доступа к REST endpoint.

```php
add_filter( 'kkorsakov_htmx_rest_permissions', function( $allowed, $request ) {
    if ( $request->get_param( 'context' ) === 'edit' ) {
        return current_user_can( 'edit_posts' );
    }
    return $allowed;
}, 10, 2 );
```

### `kkorsakov_htmx_post_template`

Кастомизирует шаблон для поста.

```php
add_filter( 'kkorsakov_htmx_post_template', function( $template, $post, $context ) {
    if ( $context === 'edit' ) {
        $template = get_template_directory() . '/htmx-edit-post.php';
    }
    return $template;
}, 10, 3 );
```

### `kkorsakov_htmx_term_template`

Кастомизирует шаблон для термина.

```php
add_filter( 'kkorsakov_htmx_term_template', function( $template, $term, $context ) {
    if ( $term->taxonomy === 'product_cat' ) {
        $template = get_template_directory() . '/htmx-product-category.php';
    }
    return $template;
}, 10, 3 );
```

### `kkorsakov_htmx_custom_fragment`

Кастомизирует кастомный фрагмент.

```php
add_filter( 'kkorsakov_htmx_custom_fragment', function( $html, $target, $context, $args ) {
    if ( $target === '#special-widget' ) {
        ob_start();
        do_action( 'kkorsakov_htmx_special_widget' );
        $html = ob_get_clean();
    }
    return $html;
}, 10, 4 );
```

## JavaScript API

Плагин регистрирует глобальный объект `kkorsakovHtmx`.

### `kkorsakovHtmx.refresh( element )`

Обновляет элемент через HTMX.

```javascript
var element = document.getElementById('content');
kkorsakovHtmx.refresh(element);
```

### `kkorsakovHtmx.ajax( method, url, config )`

Прямой вызов HTMX ajax.

```javascript
kkorsakovHtmx.ajax('GET', '/wp-json/kkorsakov-htmx/v1/fragment?target=123', {
    target: '#content',
    swap: 'innerHTML'
});
```

### `kkorsakovHtmx.loadFragment( target, context, args )`

Создает URL для загрузки фрагмента.

```javascript
var url = kkorsakovHtmx.loadFragment('123', 'view', {
    format: 'short'
});

// Результат: /wp-json/kkorsakov-htmx/v1/fragment?target=123&context=view&args[format]=short

fetch(url)
    .then(response => response.text())
    .then(html => {
        document.getElementById('content').innerHTML = html;
    });
```

### События HTMX

Плагин генерирует собственные события на основе `HX-Trigger` header.

```javascript
document.addEventListener('kkorsakovHtmx:afterFragmentLoad', function(event) {
    console.log('Fragment loaded!');
    console.log('Target:', event.detail.target);
    console.log('XHR:', event.detail.xhr);
});

// Или через htmx
htmx.on('#my-element', 'kkorsakovHtmx:afterFragmentLoad', function(evt) {
    console.log('Fragment loaded in my-element');
});
```

### События по умолчанию

- `kkorsakovHtmx:afterFragmentLoad` — когда фрагмент загружен

## Best Practices

### 1. Используйте классы вместо ID

```html
<!-- Плохо -->
<button hx-target="#result" ...>Load</button>
<div id="result"></div>

<!-- Хорошо -->
<button hx-target=".result-zone" ...>Load</button>
<div class="result-zone"></div>
```

### 2. Обрабатывайте ошибки

```html
<div hx-get="/wp-json/kkorsakov-htmx/v1/fragment?target=123"
     hx-target="#content"
     hx-swap="innerHTML"
     hx-indicator=".loader">
  Загрузить
</div>
<div class="loader" style="display:none;">⏳</div>

<style>
.htmx-request .loader { display: block; }
.htmx-request { opacity: 0.5; }
</style>
```

### 3. Кэшируйте запросы

```php
[htmx target="#cached-content" trigger="click" 
     params="'HX-Cache--Control: max-age=300'"]
  Обновить кэш
[/htmx]
```

### 4. Используйте контекст edit для администраторов

```php
<?php if ( current_user_can( 'edit_posts' ) ) : ?>
<div hx-get="/wp-json/kkorsakov-htmx/v1/fragment?target=123&context=edit"
     hx-target="#edit-preview">
  Просмотр в режиме редактирования
</div>
<?php endif; ?>
```

### 5. Блокируйте повторные клики во время загрузки

```javascript
document.body.addEventListener('htmx:beforeRequest', function(evt) {
    evt.detail.xhr.addEventListener('htmx:abort', function() {
        evt.detail.element.disabled = false;
    });
});

document.body.addEventListener('htmx:afterRequest', function(evt) {
    if (evt.detail.success) {
        evt.detail.element.disabled = false;
    }
});
```

## FAQ

### Почему HTMX не загружается?

Проверьте, что на странице есть шорткод `[htmx]` или блок `kkorsakov/htmx-fragment`.

### Как отключить CDN?

По умолчанию используется локальный файл. Для использования CDN:

```php
add_filter( 'kkorsakov_htmx_use_cdn', '__return_true' );
```

### Почему 403 Forbidden при запросе?

Убедитесь, что запрос содержит правильный nonce. Плагин автоматически добавляет `X-WP-Nonce` header.

### Как использовать с кастомными эндпоинтами?

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'my-plugin/v1', '/custom', [
        'methods' => 'GET',
        'callback' => function( $request ) {
            return new WP_REST_Response( 'Hello World', 200 );
        },
    ]);
} );

// Использовать в HTMX:
// hx-get="/wp-json/my-plugin/v1/custom"
```

### Можно ли использовать с кэширующими плагинами?

Да, но для кэширования фрагментов используйте header:

```php
add_action( 'rest_api_init', function() {
    add_filter( 'kkorsakov_htmx_fragment_output', function( $html ) {
        header( 'HX-Push-Url: true' );
        header( 'Cache-Control: max-age=300' );
        return $html;
    });
} );
```

## Лицензия

GPL-2.0-or-later

## Участие в разработке

1. Форкните репозиторий
2. Создайте ветку (`git checkout -b feature/amazing-feature`)
3. Сделайте коммит (`git commit -m 'Add some amazing feature'`)
4. Отправьте в ветку (`git push origin feature/amazing-feature`)
5. Создайте Pull Request

## Благодарности

- [HTMX](https://htmx.org/) — замечательная библиотека для создания интерактивных интерфейсов

## Ссылки

- [GitHub Repository](https://github.com/korsakov-kuzjma/kkorsakov-htmx)
- [Issue Tracker](https://github.com/korsakov-kuzjma/kkorsakov-htmx/issues)
