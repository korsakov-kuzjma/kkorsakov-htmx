# AGENTS.md

## Project Description

The WordPress plugin `kkorsakov-htmx` is designed for seamless integration of the HTMX library into the WordPress ecosystem and provides a specialized endpoint in the REST API for handling HTMX requests.

## Project Metadata

| Parameter | Value |
|----------|----------|
| **Plugin Name** | kkorsakov-htmx |
| **Author** | kkorsakov |
| **Author URL** | https://github.com/korsakov-kuzjma |
| **Plugin URL** | https://github.com/korsakov-kuzjma/kkorsakov-htmx |
| **License** | GPL-2.0-or-later |
| **Min WordPress Version** | 6.0 |
| **Min PHP Version** | 7.4 |
| **Text Domain** | `kkorsakov-htmx` |

## Plugin Architecture

### File Structure
```text
kkorsakov-htmx/
├── kkorsakov-htmx.php              # Main plugin file (bootstrap)
├── README.md                       # Public documentation (for users)
├── DEVELOPER.md                   # Developer guide (English)
├── DEVELOPER.ru.md                # Developer guide (Russian)
├── AGENTS.md                       # Instructions for AI agents and developers (English)
├── AGENTS.ru.md                    # Instructions for AI agents and developers (Russian)
├── CHANGELOG.md                    # Changelog (Keep a Changelog format)
├── includes/
│   ├── class-plugin.php           # Main plugin class (initialization, hooks)
│   ├── class-htmx-integrator.php   # HTMX integration: shortcode, attributes, detection
│   ├── class-rest-api.php          # REST API endpoints registration and handling
│   ├── class-assets.php            # JS/CSS assets management
│   ├── class-security.php          # Utilities: nonce, sanitization, capabilities
│   └── traits/
│       └── trait-singleton.php     # Singleton pattern for plugin classes
├── assets/
│   ├── js/
│   │   ├── htmx.min.js             # Local HTMX copy (not CDN)
│   │   └── frontend.js             # Initialization and custom events
│   └── css/
│       └── frontend.css            # Basic styles for HTMX indicators
├── languages/                      # .pot and .mo files for localization
└── tests/
    ├── phpunit/                    # Unit tests
    └── e2e/                        # Integration tests (Playwright)
```

## Coding Standards

### PHP / WordPress
```php
<?php
/**
 * File header example
 *
 * @package Kkorsakov_Htmx
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx;

// Follow WordPress Coding Standards
// Use prefix kkorsakov_htmx_ for global functions/hooks
// Apply strict typing and PHPDoc for all public elements
```

**Requirements:**
- All classes in namespace `Kkorsakov\Htmx`
- Class files: `class-{classname}.php`, autoloading via `spl_autoload_register` or Composer PSR-4
- Functions and hooks: prefix `kkorsakov_htmx_`
- Constants: prefix `KKORSAKOV_HTMX_`
- Avoid global state, use dependency injection

### Security (Mandatory)

| Data Type | Sanitization (Input) | Escaping (Output) |
|-----------|---------------------|-------------------|
| Text | `sanitize_text_field()` | `esc_html()` |
| HTML block | `wp_kses_post()` | `wp_kses_post()` |
| URL | `esc_url_raw()` | `esc_url()` |
| JS variable | — | `wp_json_encode()` + `esc_js()` |
| HTML attribute | — | `esc_attr()` |

**Critical Rules:**
1. All REST API requests: verify `X-WP-Nonce` or `wp_rest_nonce`
2. All state-changing operations: `check_ajax_referer()` or `wp_verify_nonce()`
3. Permission check: `current_user_can( $capability )` before executing actions
4. SQL queries: only `$wpdb->prepare()` or WP_Query with validation
5. No `eval()`, `create_function()`, or serialized user data

### HTMX-Specific Requirements
- Load `htmx.min.js` only on pages using `[htmx]` shortcode or block
- Add `HX-Trigger` and `HX-Headers` with WordPress nonce for authenticated requests
- Provide filters for attribute customization:
  ```php
  apply_filters( 'kkorsakov_htmx_config', array $config ): array
  ```

## REST API Specification

### Main Endpoint
- **Route**: `/kkorsakov-htmx/v1/fragment`
- **Methods**: `GET`, `POST`
- **Namespace**: `kkorsakov-htmx/v1`
- **Permission callback**: configurable via `kkorsakov_htmx_rest_permissions` filter

### Request Parameters
```php
[
    'target' => [
        'type' => 'string',
        'required' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'description' => 'Target fragment identifier (ID, CSS selector, or slug)'
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
        'description' => 'Additional arguments for fragment rendering'
    ]
]
```

### Response Format (HTMX-compatible)
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
HX-Trigger: afterFragmentLoad
X-WP-Nonce: {new_nonce_if_rotated}

<div id="fragment-target">
    <!-- rendered HTML fragment -->
</div>
```

**Important:** For HTMX requests (`HX-Request: true`) return pure HTML, not JSON. For AJAX requests with `Accept: application/json` return standard WP REST response.

## Development

### Local Environment
```bash
# Clone
git clone https://github.com/korsakov-kuzjma/kkorsakov-htmx.git
cd kkorsakov-htmx

# Install PHP dependencies (for development)
composer install

# Run linters
composer lint          # PHPCS
composer lint:fix      # PHPCBF
composer test          # PHPUnit
composer test:e2e      # Playwright (requires Node.js)
```

### Docker (Optional)
```yaml
# docker-compose.yml (example)
version: '3.8'
services:
  wordpress:
    image: wordpress:php8.2-apache
    volumes:
      - .:/var/www/html/wp-content/plugins/kkorsakov-htmx
    environment:
      WORDPRESS_DEBUG: 1
```

## Security Checklist Before PR

- [ ] All input data sanitized via `sanitize_*()` or custom validators
- [ ] All output data escaped via `esc_*()` functions
- [ ] Nonces verified for all state-changing requests
- [ ] `current_user_can()` check performed before privileged operations
- [ ] REST API endpoint has `permission_callback`
- [ ] No direct output of `$_GET`/`$_POST` without escaping
- [ ] SQL queries use prepared statements
- [ ] Assets loaded via `wp_enqueue_script/style` with versioning
- [ ] Localization: all strings wrapped in `__()`/`_e()` with text domain

## Documentation and Hooks

### Public Hooks for Developers
```php
// Modify HTMX configuration
apply_filters( 'kkorsakov_htmx_config', array $config ): array

// Force enqueue HTMX on all pages
apply_filters( 'kkorsakov_htmx_force_enqueue', bool $force ): bool

// Use CDN instead of local file
apply_filters( 'kkorsakov_htmx_use_cdn', bool $use_cdn ): bool

// Modify fragment URL before request
apply_filters( 'kkorsakov_htmx_fragment_url', string $url, string $target ): string

// Modify HTML fragment before sending
apply_filters( 'kkorsakov_htmx_fragment_output', string $html, string $target, string $context ): string

// Handle custom fragment types (CSS selectors like #my-div)
apply_filters( 'kkorsakov_htmx_custom_fragment', string $html, string $target, string $context, array $args ): string

// Handle custom fragment by name (non-CSS-selector)
apply_filters( 'kkorsakov_htmx_render_fragment', string $html, string $target, string $context, array $args ): string

// Check REST API access permissions
apply_filters( 'kkorsakov_htmx_rest_permissions', bool $allowed, WP_REST_Request $request ): bool
```

### PHPDoc Requirements
```php
/**
 * Short method description.
 *
 * @since 1.0.0
 *
 * @param string $target Fragment identifier.
 * @param array  $args   Additional arguments.
 * @return string|WP_Error HTML fragment or error.
 */
public function get_fragment( string $target, array $args = [] ) {
    // ...
}
```

## Versioning and Releases

- Follow [Semantic Versioning 2.0.0](https://semver.org/)
- Tags: `v{MAJOR}.{MINOR}.{PATCH}` (e.g., `v1.2.3`)
- Maintain `CHANGELOG.md` in [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format
- Before release:
  1. Update version in main plugin file header
  2. Run all tests and linters
  3. Check compatibility with latest WordPress version

## License

The plugin is distributed under the **GNU General Public License v2.0 or later**. All code, including third-party libraries, must be GPL-compatible. HTMX (MIT License) is GPL-compatible and may be used without restrictions.
