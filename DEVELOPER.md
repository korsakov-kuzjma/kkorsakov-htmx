# Developer Guide (DEVELOPER.md)

Guide to extending and customizing the kkorsakov-htmx plugin.

## Table of Contents

1. [Introduction](#introduction)
2. [Plugin Architecture](#plugin-architecture)
3. [Shortcode [htmx]]](#shortcode-htmx)
4. [Filters (Hooks)](#filters-hooks)
5. [Extending REST API](#extending-rest-api)
6. [Adding Custom Fragment Types](#adding-custom-fragment-types)
7. [HTMX Customization](#htmx-customization)
8. [Creating Custom Components](#creating-custom-components)
9. [Fragment Templates](#fragment-templates)
10. [Security](#security)
11. [Extension Examples](#extension-examples)

---

## Introduction

The kkorsakov-htmx plugin is built on object-oriented architecture using:
- Singleton pattern for class instance management
- Namespace `Kkorsakov\Htmx`
- Traits for code reuse

All classes are automatically loaded via the SPL autoloader.

---

## Plugin Architecture

### Class Structure

```
Kkorsakov\Htmx\
├── Plugin          - Main class, manages initialization
├── Security        - Security utilities (nonce, permissions)
├── Assets          - JS/CSS asset management
├── Htmx_Integrator - HTMX integration (shortcodes, blocks)
├── Rest_Api        - REST API endpoints
└── Traits\
    └── Singleton   - Trait for Singleton pattern
```

### Main Constants

| Constant | Description |
|---------|-------------|
| `KKORSAKOV_HTMX_VERSION` | Plugin version |
| `KKORSAKOV_HTMX_PATH` | Absolute path to plugin directory |
| `KKORSAKOV_HTMX_URL` | URL of plugin directory |
| `KKORSAKOV_HTMX_FILE` | Path to main plugin file |
| `KKORSAKOV_HTMX_MIN_PHP` | Minimum PHP version |
| `KKORSAKOV_HTMX_MIN_WP` | Minimum WordPress version |

---

## Shortcode [htmx]

The plugin provides the `[htmx]` shortcode for quickly creating HTMX elements.

### Shortcode Parameters

| Parameter | Required | Description | Default |
|-----------|----------|-------------|---------|
| `tag` | No | HTML tag element | `span` |
| `class` | No | CSS classes | (empty) |
| `target` | Yes | CSS selector of target element | (empty) |
| `fragment` | No | Fragment ID | `target` value |
| `trigger` | No | Trigger event | `click` |
| `swap` | No | Swap method | `innerHTML` |
| `url` | No | Custom URL | (auto) |
| `indicator` | No | Indicator selector | `.htmx-indicator` |
| `method` | No | HTTP method | `get` |

### Available HTML Tags

`span`, `div`, `a`, `button`, `img`, `input`, `form`, `ul`, `li`, `section`, `article`, `header`, `footer`, `main`, `aside`, `nav`

### Usage Examples

```php
// Simple element
[htmx target="#content"]Load[/htmx]

// Div with class
[htmx tag="div" target="#sidebar" fragment="widget" class="my-widget"]
  <h3>Title</h3>
[/htmx]

// Link
[htmx tag="a" target="#main" fragment="about" href="/about"]About Us[/htmx]

// Image
[htmx tag="img" target="#modal" fragment="123" src="/img.jpg" alt="Click here"]

// Button
[htmx tag="button" target="#results" method="post" class="btn"]
  Submit
[/htmx]
```

---

## Filters (Hooks)

### HTMX Configuration Filters

#### `kkorsakov_htmx_config`
Modify HTMX configuration.

```php
add_filter('kkorsakov_htmx_config', function($config) {
    $config['historyEnabled'] = false;
    $config['defaultSwapStyle'] = 'outerHTML';
    return $config;
});
```

#### `kkorsakov_htmx_force_enqueue`
Force HTMX loading on all pages.

```php
add_filter('kkorsakov_htmx_force_enqueue', '__return_true');
```

#### `kkorsakov_htmx_use_cdn`
Use CDN instead of local file.

```php
add_filter('kkorsakov_htmx_use_cdn', '__return_true');
```

### REST API Filters

#### `kkorsakov_htmx_rest_permissions`
Control access to REST API endpoints.

```php
add_filter('kkorsakov_htmx_rest_permissions', function($allowed, $request) {
    return $allowed && current_user_can('edit_posts');
}, 10, 2);
```

#### `kkorsakov_htmx_fragment_url`
Modify fragment URL.

```php
add_filter('kkorsakov_htmx_fragment_url', function($url, $target) {
    return add_query_arg('custom_param', 'value', $url);
}, 10, 2);
```

#### `kkorsakov_htmx_fragment_output`
Filter HTML output of fragment.

```php
add_filter('kkorsakov_htmx_fragment_output', function($html, $target, $context) {
    return '<div class="wrapped">' . $html . '</div>';
}, 10, 3);
```

---

## Extending REST API

### Adding Custom Endpoint

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

### Intercepting Fragment Rendering

```php
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if (strpos($target, 'my-custom-type:') === 0) {
        return '<div>Custom content for: ' . esc_html($target) . '</div>';
    }
    return $html;
}, 10, 4);
```

---

## Adding Custom Fragment Types

### Example: Custom Fragment by CSS Selector

```php
add_filter('kkorsakov_htmx_custom_fragment', function($html, $target, $context, $args) {
    if ($target === '#featured') {
        ob_start();
        ?>
        <div id="featured">
            <h2>Featured Posts</h2>
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

---

## HTMX Customization

### Programmatic HTMX Loading

```php
add_action('wp', function() {
    if (is_single()) {
        Htmx_Integrator::get_instance()->force_enqueue();
    }
});
```

### Adding Custom Headers

```php
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script('htmx-lib', '
        document.addEventListener("htmx:configRequest", function(event) {
            event.detail.headers["X-WP-Nonce"] = kkorsakovHtmxSettings.nonce;
        });
    ');
});
```

---

## Creating Custom Components

### Creating New Component Class

```php
<?php
/**
 * My Custom Component
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
        add_filter('kkorsakov_htmx_render_fragment', [$this, 'handle_fragment'], 20, 4);
    }

    public function handle_fragment($html, $target, $context, $args) {
        // Processing logic
        return $html;
    }
}
```

### Registering Component

```php
add_action('plugins_loaded', function() {
    if (class_exists('Kkorsakov\Htmx\Plugin')) {
        My_Custom_Component::get_instance();
    }
}, 20);
```

---

## Fragment Templates

### Template Hierarchy for Posts

```
wp-content/themes/{theme}/
├── htmx-fragment-{post_type}-{context}.php  # htmx-fragment-post-edit.php
├── htmx-fragment-{post_type}.php             # htmx-fragment-post.php
├── htmx-fragment-{context}.php               # htmx-fragment-edit.php
└── htmx-fragment.php                         # Generic template
```

### Template Hierarchy for Terms

```
wp-content/themes/{theme}/
├── htmx-fragment-{taxonomy}-{context}.php    # htmx-fragment-category-edit.php
├── htmx-fragment-{taxonomy}.php              # htmx-fragment-category.php
├── htmx-fragment-taxonomy-{context}.php      # htmx-fragment-taxonomy-edit.php
├── htmx-fragment-taxonomy.php                # htmx-fragment-taxonomy.php
├── htmx-fragment-{context}.php               # htmx-fragment-edit.php
└── htmx-fragment.php
```

### Example Fragment Template

```php
<?php
/**
 * Fragment template htmx-fragment.php
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
</article>
```

---

## Security

### Using Security Class Methods

```php
$security = Security::get_instance();

// Verify nonce
if (!$security->verify_rest_nonce($nonce)) {
    wp_send_json_error(['message' => 'Invalid nonce']);
}

// Sanitize array
$sanitized = $security->sanitize_array($_POST);

// Escape attributes
$attributes = $security->escape_html_attributes([
    'class' => 'active',
    'data-id' => $id
]);
```

### Custom Permissions

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

## Extension Examples

### Example 1: Load More Button

```php
// 1. Add shortcode for HTMX button
add_shortcode('load-more', function($atts) {
    $atts = shortcode_atts([
        'target'   => '#content',
        'posts'    => '5',
        'button'   => 'Load More'
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
            <span class="htmx-indicator">Loading...</span>
        </button>',
        esc_url($url),
        esc_attr($atts['target']),
        esc_html($atts['button'])
    );
});

// 2. Handle fragment request
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

### Example 2: AJAX Search Form

```php
// 1. HTML form
/*
<form id="search-form" hx-post="<?php echo rest_url('kkorsakov-htmx/v1/fragment'); ?>" 
      hx-target="#search-results" 
      hx-swap="innerHTML">
    <input type="hidden" name="target" value="search-results">
    <input type="search" name="q" placeholder="Search..." required>
    <button type="submit">Search</button>
</form>
<div id="search-results"></div>
*/

// 2. Handler
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
        
        return '<p>No results found</p>';
    }
    return $html;
}, 10, 4);
```

---

## Developer Constants

| Constant | Description | Example |
|-----------|-------------|---------|
| `KKORSAKOV_HTMX_VERSION` | Plugin version | '1.0.0' |
| `KKORSAKOV_HTMX_PATH` | Plugin directory path | '/wp-content/plugins/kkorsakov-htmx/' |
| `KKORSAKOV_HTMX_URL` | Plugin directory URL | 'https://example.com/wp-content/plugins/kkorsakov-htmx/' |
| `KKORSAKOV_HTMX_FILE` | Main file path | '/wp-content/plugins/kkorsakov-htmx/kkorsakov-htmx.php' |

---

## FAQ

### How to Add New Fragment Type?

Use the `kkorsakov_htmx_render_fragment` filter:

```php
add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if (strpos($target, 'my-type:') === 0) {
        // Your logic here
    }
    return $html;
}, 10, 4);
```

### How to Override Template?

Create template file in theme folder:
- For posts: `htmx-fragment-{post_type}.php`
- For terms: `htmx-fragment-{taxonomy}.php`

### How to Add Custom JS/CSS?

```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('my-custom-style', get_stylesheet_directory_uri() . '/custom.css');
    wp_enqueue_script('my-custom-script', get_stylesheet_directory_uri() . '/custom.js', ['htmx-lib']);
});
```

---

## Debugging

### Enable HTMX Debug Mode

```php
add_filter('kkorsakov_htmx_config', function($config) {
    $config['debug'] = true;
    return $config;
});
```

### Logging Requests

```php
add_action('rest_api_init', function() {
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        error_log('HTMX Request: ' . print_r($request->get_params(), true));
        return $result;
    }, 10, 3);
});
```

---

## Technical Support

- GitHub Issues: https://github.com/korsakov-kuzjma/kkorsakov-htmx/issues
- HTMX Documentation: https://htmx.org/docs/
