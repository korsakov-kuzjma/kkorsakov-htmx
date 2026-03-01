<?php
/**
 * Класс Admin_Settings (Настройки админки).
 *
 * Управляет страницами настроек и документации в админке WordPress:
 * - Страница настроек плагина
 * - Страница документации
 * - Пункты меню
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
 * Класс Admin_Settings.
 *
 * Создаёт и управляет страницами настроек и документации
 * в административной панели WordPress.
 *
 * @since 1.0.0
 */
class Admin_Settings {

	/**
	 * Подключение трейта Singleton.
	 *
	 * @since 1.0.0
	 */
	use Singleton;

	/**
	 * Инициализация админ-страниц.
	 *
	 * Регистрирует хуки WordPress для создания меню.
	 *
	 * @since 1.0.0
	 */
	protected function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Добавить страницы меню.
	 *
	 * Создаёт пункты меню в админке:
	 * - Настройки HTMX
	 * - Документация HTMX
	 *
	 * @since 1.0.0
	 */
	public function add_menu_pages(): void {
		add_menu_page(
			__( 'Настройки HTMX', 'kkorsakov-htmx' ),
			__( 'HTMX', 'kkorsakov-htmx' ),
			'manage_options',
			'kkorsakov-htmx',
			[ $this, 'render_settings_page' ],
			'dashicons-admin-generic',
			100
		);

		add_submenu_page(
			'kkorsakov-htmx',
			__( 'Настройки HTMX', 'kkorsakov-htmx' ),
			__( 'Настройки', 'kkorsakov-htmx' ),
			'manage_options',
			'kkorsakov-htmx',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'kkorsakov-htmx',
			__( 'Документация HTMX', 'kkorsakov-htmx' ),
			__( 'Документация', 'kkorsakov-htmx' ),
			'manage_options',
			'kkorsakov-htmx-docs',
			[ $this, 'render_docs_page' ]
		);
	}

	/**
	 * Зарегистрировать настройки.
	 *
	 * Регистрирует опции настроек для сохранения в базе данных.
	 *
	 * @since 1.0.0
	 */
	public function register_settings(): void {
		register_setting(
			'kkorsakov_htmx_settings',
			'kkorsakov_htmx_options',
			[
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'           => $this->get_default_options(),
			]
		);

		add_settings_section(
			'kkorsakov_htmx_general',
			__( 'Основные настройки', 'kkorsakov-htmx' ),
			[ $this, 'render_general_section' ],
			'kkorsakov_htmx'
		);

		add_settings_field(
			'use_cdn',
			__( 'Использовать CDN', 'kkorsakov-htmx' ),
			[ $this, 'render_use_cdn_field' ],
			'kkorsakov_htmx',
			'kkorsakov_htmx_general'
		);

		add_settings_field(
			'force_enqueue',
			__( 'Принудительная загрузка HTMX', 'kkorsakov-htmx' ),
			[ $this, 'render_force_enqueue_field' ],
			'kkorsakov_htmx',
			'kkorsakov_htmx_general'
		);

		add_settings_section(
			'kkorsakov_htmx_advanced',
			__( 'Расширенные настройки', 'kkorsakov-htmx' ),
			[ $this, 'render_advanced_section' ],
			'kkorsakov_htmx'
		);

		add_settings_field(
			'history_enabled',
			__( 'Включить историю', 'kkorsakov-htmx' ),
			[ $this, 'render_history_enabled_field' ],
			'kkorsakov_htmx',
			'kkorsakov_htmx_advanced'
		);

		add_settings_field(
			'swap_style',
			__( 'Стиль замены по умолчанию', 'kkorsakov-htmx' ),
			[ $this, 'render_swap_style_field' ],
			'kkorsakov-htmx',
			'kkorsakov_htmx_advanced'
		);
	}

	/**
	 * Получить значения по умолчанию.
	 *
	 * @since 1.0.0
	 *
	 * @return array Значения по умолчанию.
	 */
	protected function get_default_options(): array {
		return [
			'use_cdn'        => false,
			'force_enqueue'  => false,
			'history_enabled' => true,
			'swap_style'      => 'innerHTML',
		];
	}

	/**
	 * Санитизация опций при сохранении.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Входящие данные.
	 * @return array Санитизированные данные.
	 */
	public function sanitize_options( array $input ): array {
		return [
			'use_cdn'        => ! empty( $input['use_cdn'] ),
			'force_enqueue'  => ! empty( $input['force_enqueue'] ),
			'history_enabled' => ! empty( $input['history_enabled'] ),
			'swap_style'     => in_array( $input['swap_style'], [ 'innerHTML', 'outerHTML', 'afterbegin', 'beforeend', 'delete' ], true )
				? $input['swap_style']
				: 'innerHTML',
		];
	}

	/**
	 * Отобразить секцию общих настроек.
	 *
	 * @since 1.0.0
	 */
	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Базовые настройки плагина.', 'kkorsakov-htmx' ) . '</p>';
	}

	/**
	 * Отобразить секцию расширенных настроек.
	 *
	 * @since 1.0.0
	 */
	public function render_advanced_section(): void {
		echo '<p>' . esc_html__( 'Расширенные настройки конфигурации HTMX.', 'kkorsakov-htmx' ) . '</p>';
	}

	/**
	 * Отобразить поле "Использовать CDN".
	 *
	 * @since 1.0.0
	 */
	public function render_use_cdn_field(): void {
		$options = get_option( 'kkorsakov_htmx_options', $this->get_default_options() );
		?>
		<input type="checkbox"
		       id="use_cdn"
		       name="kkorsakov_htmx_options[use_cdn]"
		       value="1"
		       <?php checked( $options['use_cdn'], true ); ?>
		/>
		<label for="use_cdn">
			<?php esc_html_e( 'Загружать HTMX с внешнего сервера (CDN)', 'kkorsakov-htmx' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'По умолчанию HTMX загружается из локального файла в директории плагина.', 'kkorsakov-htmx' ); ?>
		</p>
		<?php
	}

	/**
	 * Отобразить поле "Принудительная загрузка".
	 *
	 * @since 1.0.0
	 */
	public function render_force_enqueue_field(): void {
		$options = get_option( 'kkorsakov_htmx_options', $this->get_default_options() );
		?>
		<input type="checkbox"
		       id="force_enqueue"
		       name="kkorsakov_htmx_options[force_enqueue]"
		       value="1"
		       <?php checked( $options['force_enqueue'], true ); ?>
		/>
		<label for="force_enqueue">
			<?php esc_html_e( 'Загружать HTMX на всех страницах', 'kkorsakov-htmx' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'По умолчанию HTMX загружается только на страницах с шорткодом [htmx] или блоком.', 'kkorsakov-htmx' ); ?>
		</p>
		<?php
	}

	/**
	 * Отобразить поле "Включить историю".
	 *
	 * @since 1.0.0
	 */
	public function render_history_enabled_field(): void {
		$options = get_option( 'kkorsakov_htmx_options', $this->get_default_options() );
		?>
		<input type="checkbox"
		       id="history_enabled"
		       name="kkorsakov_htmx_options[history_enabled]"
		       value="1"
		       <?php checked( $options['history_enabled'], true ); ?>
		/>
		<label for="history_enabled">
			<?php esc_html_e( 'Включить поддержку истории HTMX', 'kkorsakov-htmx' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Позволяет кнопкам назад/вперёд в браузере работать с HTMX-запросами.', 'kkorsakov-htmx' ); ?>
		</p>
		<?php
	}

	/**
	 * Отобразить поле "Стиль замены".
	 *
	 * @since 1.0.0
	 */
	public function render_swap_style_field(): void {
		$options = get_option( 'kkorsakov_htmx_options', $this->get_default_options() );
		?>
		<select id="swap_style" name="kkorsakov_htmx_options[swap_style]">
			<option value="innerHTML" <?php selected( $options['swap_style'], 'innerHTML' ); ?>>
				innerHTML (<?php esc_html_e( 'по умолчанию', 'kkorsakov-htmx' ); ?>)
			</option>
			<option value="outerHTML" <?php selected( $options['swap_style'], 'outerHTML' ); ?>>
				outerHTML
			</option>
			<option value="afterbegin" <?php selected( $options['swap_style'], 'afterbegin' ); ?>>
				afterbegin (<?php esc_html_e( 'в начало', 'kkorsakov-htmx' ); ?>)
			</option>
			<option value="beforeend" <?php selected( $options['swap_style'], 'beforeend' ); ?>>
				beforeend (<?php esc_html_e( 'в конец', 'kkorsakov-htmx' ); ?>)
			</option>
			<option value="delete" <?php selected( $options['swap_style'], 'delete' ); ?>>
				delete (<?php esc_html_e( 'удалить элемент', 'kkorsakov-htmx' ); ?>)
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Метод замены контента по умолчанию после HTMX-запроса.', 'kkorsakov-htmx' ); ?>
		</p>
		<?php
	}

	/**
	 * Отобразить страницу настроек.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'kkorsakov_htmx_settings' );
				do_settings_sections( 'kkorsakov_htmx' );
				submit_button( __( 'Сохранить настройки', 'kkorsakov-htmx' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Быстрый старт', 'kkorsakov-htmx' ); ?></h2>

			<h3><?php esc_html_e( 'Использование шорткода', 'kkorsakov-htmx' ); ?></h3>
			<pre><code>[htmx target="#content" fragment="sidebar"]Нажмите для загрузки[/htmx]

&lt;div id="content"&gt;&lt;/div&gt;</code></pre>

			<h3><?php esc_html_e( 'Использование HTML напрямую', 'kkorsakov-htmx' ); ?></h3>
			<pre><code>&lt;button hx-get="/wp-json/kkorsakov-htmx/v1/fragment?target=123" 
      hx-target="#content"&gt;
  Загрузить пост
&lt;/button&gt;</code></pre>

			<p>
				<a href="<?php echo admin_url( 'admin.php?page=kkorsakov-htmx-docs' ); ?>" class="button button-primary">
					<?php esc_html_e( 'Читать полную документацию', 'kkorsakov-htmx' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Отобразить страницу документации.
	 *
	 * @since 1.0.0
	 */
	public function render_docs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap kkorsakov-htmx-docs">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="kkorsakov-htmx-docs-content">
				<h2><?php esc_html_e( 'Что такое HTMX?', 'kkorsakov-htmx' ); ?></h2>
				<p>
					<?php esc_html_e( 'HTMX — это JavaScript-библиотека, которая позволяет создавать современные пользовательские интерфейсы с помощью простых HTML-атрибутов. Вместо написания сложного JavaScript вы можете использовать атрибуты like hx-get, hx-post, hx-target для выполнения AJAX-запросов непосредственно из HTML-элементов.', 'kkorsakov-htmx' ); ?>
				</p>

				<hr>

				<h2><?php esc_html_e( 'Использование шорткода', 'kkorsakov-htmx' ); ?></h2>

				<h3><?php esc_html_e( 'Базовый пример', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>[htmx target="#content"]Нажмите меня[/htmx]

&lt;div id="content"&gt;&lt;/div&gt;</code></pre>

				<h3><?php esc_html_e( 'С кастомным тегом', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>[htmx tag="button" target="#result" fragment="123" class="btn btn-primary"]
  Загрузить пост #123
[/htmx]

[htmx tag="div" target="#sidebar" fragment="popular-posts"]
  &lt;h3&gt;Популярные посты&lt;/h3&gt;
[/htmx]

[htmx tag="img" target="#modal" fragment="123" 
  src="/path/to/image.jpg" alt="Нажмите для просмотра"]
</code></pre>

				<h3><?php esc_html_e( 'Доступные параметры', 'kkorsakov-htmx' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Параметр', 'kkorsakov-htmx' ); ?></th>
							<th><?php esc_html_e( 'Описание', 'kkorsakov-htmx' ); ?></th>
							<th><?php esc_html_e( 'По умолчанию', 'kkorsakov-htmx' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>tag</code></td>
							<td><?php esc_html_e( 'HTML-тег для создания (span, div, a, button, img и т.д.)', 'kkorsakov-htmx' ); ?></td>
							<td>span</td>
						</tr>
						<tr>
							<td><code>class</code></td>
							<td><?php esc_html_e( 'CSS-классы', 'kkorsakov-htmx' ); ?></td>
							<td>-</td>
						</tr>
						<tr>
							<td><code>target</code></td>
							<td><?php esc_html_e( 'CSS-селектор элемента для обновления', 'kkorsakov-htmx' ); ?></td>
							<td>-</td>
						</tr>
						<tr>
							<td><code>fragment</code></td>
							<td><?php esc_html_e( 'ID фрагмента для загрузки (ID поста, термин или кастомный)', 'kkorsakov-htmx' ); ?></td>
							<td>-</td>
						</tr>
						<tr>
							<td><code>trigger</code></td>
							<td><?php esc_html_e( 'Событие для запуска запроса (click, submit, mouseenter)', 'kkorsakov-htmx' ); ?></td>
							<td>click</td>
						</tr>
						<tr>
							<td><code>method</code></td>
							<td><?php esc_html_e( 'HTTP-метод (get, post)', 'kkorsakov-htmx' ); ?></td>
							<td>get</td>
						</tr>
						<tr>
							<td><code>swap</code></td>
							<td><?php esc_html_e( 'Как заменять контент (innerHTML, outerHTML, afterbegin, beforeend)', 'kkorsakov-htmx' ); ?></td>
							<td>innerHTML</td>
						</tr>
						<tr>
							<td><code>indicator</code></td>
							<td><?php esc_html_e( 'CSS-селектор элемента индикатора загрузки', 'kkorsakov-htmx' ); ?></td>
							<td>.htmx-indicator</td>
						</tr>
					</tbody>
				</table>

				<hr>

				<h2><?php esc_html_e( 'Загрузка контента', 'kkorsakov-htmx' ); ?></h2>

				<h3><?php esc_html_e( 'Загрузка поста по ID', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>[htmx target="#content" fragment="123"]
  Загрузить пост #123
[/htmx]</code></pre>

				<h3><?php esc_html_e( 'Загрузка термина таксономии', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>[htmx target="#content" fragment="category:5"]
  Загрузить категорию #5
[/htmx]

[htmx target="#content" fragment="post_tag:10"]
  Загрузить тег #10
[/htmx]</code></pre>

				<h3><?php esc_html_e( 'Загрузка кастомного фрагмента', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>[htmx target="#content" fragment="my-custom-fragment"]
  Загрузить кастомный фрагмент
[/htmx]</code></pre>

				<p>
					<?php esc_html_e( 'Для обработки кастомных фрагментов добавьте фильтр в файл functions.php вашей темы:', 'kkorsakov-htmx' ); ?>
				</p>
				<pre><code>add_filter('kkorsakov_htmx_render_fragment', function($html, $target, $context, $args) {
    if ($target === 'my-custom-fragment') {
        return '&lt;div&gt;Кастомный контент здесь!&lt;/div&gt;';
    }
    return $html;
}, 10, 4);</code></pre>

				<hr>

				<h2><?php esc_html_e( 'Индикатор загрузки', 'kkorsakov-htmx' ); ?></h2>
				<pre><code>[htmx target="#content" indicator=".loader"]
  Загрузить контент
[/htmx]

&lt;span class="htmx-indicator" style="display:none;"&gt;Загрузка...&lt;/span&gt;
&lt;div id="content"&gt;&lt;/div&gt;

&lt;style&gt;
.htmx-request .htmx-indicator { display: inline; }
.htmx-request.htmx-request .htmx-indicator { display: inline; }
&lt;/style&gt;</code></pre>

				<hr>

				<h2><?php esc_html_e( 'REST API', 'kkorsakov-htmx' ); ?></h2>
				<p>
					<?php esc_html_e( 'Плагин предоставляет REST API эндпоинт:', 'kkorsakov-htmx' ); ?>
				</p>
				<pre><code>/wp-json/kkorsakov-htmx/v1/fragment</code></pre>

				<h3><?php esc_html_e( 'Параметры', 'kkorsakov-htmx' ); ?></h3>
				<ul>
					<li><code>target</code> - <?php esc_html_e( 'Идентификатор фрагмента (ID поста, термин или кастомный)', 'kkorsakov-htmx' ); ?></li>
					<li><code>context</code> - <?php esc_html_e( 'view или edit', 'kkorsakov-htmx' ); ?></li>
					<li><code>args</code> - <?php esc_html_e( 'Дополнительные аргументы (объект)', 'kkorsakov-htmx' ); ?></li>
				</ul>

				<h3><?php esc_html_e( 'Пример запроса', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>fetch('/wp-json/kkorsakov-htmx/v1/fragment?target=123')
  .then(response => response.text())
  .then(html => {
      document.getElementById('content').innerHTML = html;
  });</code></pre>

				<hr>

				<h2><?php esc_html_e( 'Шаблоны', 'kkorsakov-htmx' ); ?></h2>
				<p>
					<?php esc_html_e( 'Вы можете создавать кастомные шаблоны в вашей теме:', 'kkorsakov-htmx' ); ?>
				</p>

				<h3><?php esc_html_e( 'Для постов', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>wp-content/themes/{theme}/
├── htmx-fragment-{post_type}-{context}.php
├── htmx-fragment-{post_type}.php
├── htmx-fragment-{context}.php
└── htmx-fragment.php</code></pre>

				<h3><?php esc_html_e( 'Для терминов', 'kkorsakov-htmx' ); ?></h3>
				<pre><code>wp-content/themes/{theme}/
├── htmx-fragment-{taxonomy}-{context}.php
├── htmx-fragment-{taxonomy}.php
└── htmx-fragment.php</code></pre>

				<hr>

				<h2><?php esc_html_e( 'Фильтры для разработчиков', 'kkorsakov-htmx' ); ?></h2>
				<pre><code>// Изменить конфигурацию HTMX
add_filter('kkorsakov_htmx_config', function($config) {
    $config['historyEnabled'] = false;
    return $config;
});

// Принудительная загрузка HTMX
add_filter('kkorsakov_htmx_force_enqueue', '__return_true');

// Использовать CDN
add_filter('kkorsakov_htmx_use_cdn', '__return_true');

// Изменить URL фрагмента
add_filter('kkorsakov_htmx_fragment_url', function($url, $target) {
    return $url;
}, 10, 2);

// Изменить вывод
add_filter('kkorsakov_htmx_fragment_output', function($html, $target, $context) {
    return $html;
}, 10, 3);</code></pre>

				<hr>

				<h2><?php esc_html_e( 'Дополнительная информация', 'kkorsakov-htmx' ); ?></h2>
				<p>
					<?php
					printf(
						esc_html__( 'Для получения дополнительной информации посетите %s.', 'kkorsakov-htmx' ),
						'<a href="https://htmx.org/docs/" target="_blank">' . esc_html__( 'документацию HTMX', 'kkorsakov-htmx' ) . '</a>'
					);
					?>
				</p>
				<p>
					<?php
					printf(
						esc_html__( 'GitHub: %s', 'kkorsakov-htmx' ),
						'<a href="https://github.com/korsakov-kuzjma/kkorsakov-htmx" target="_blank">kkorsakov-htmx</a>'
					);
					?>
				</p>
			</div>
		</div>

		<style>
		.kkorsakov-htmx-docs-content {
			max-width: 800px;
			background: #fff;
			padding: 20px;
			border: 1px solid #ccd0d4;
		}
		.kkorsakov-htmx-docs-content pre {
			background: #f6f7f9;
			padding: 15px;
			border-radius: 4px;
			overflow-x: auto;
		}
		.kkorsakov-htmx-docs-content code {
			background: #f6f7f9;
			padding: 2px 5px;
			border-radius: 3px;
		}
		.kkorsakov-htmx-docs-content pre code {
			background: none;
			padding: 0;
		}
		.kkorsakov-htmx-docs-content table {
			margin-bottom: 20px;
		}
		</style>
		<?php
	}
}
