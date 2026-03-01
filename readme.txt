=== kkorsakov-htmx ===
Contributors: kkorsakov
Tags: ajax, javascript, api, dynamic-content, lazy-loading
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://github.com/korsakov-kuzjma/kkorsakov-htmx

Бесшовная интеграция библиотеки HTMX в WordPress с расширенным REST API для фрагментных запросов.

== Description ==

HTMX — это JavaScript- библиотека, которая позволяет создавать современные пользовательские интерфейсы с помощью простых HTML- атрибутов. Этот плагин обеспечивает полную интеграцию HTMX с WordPress.

= Возможности =

* Автоматическая загрузка HTMX только на нужных страницах
* Шорткод `[htmx]` для быстрого создания HTMX-элементов
* REST API эндпоинт для фрагментных запросов
* Поддержка загрузки постов, терминов таксономий и кастомных фрагментов
* Страница настроек и документации в админке
* Фильтры для разработчиков

= Использование =

Простой пример:
`[htmx target="#content" fragment="123"]Загрузить пост[/htmx]`

Подробная документация доступна в админке: **HTMX → Документация**

== Installation ==

1. Загрузите папку плагина в директорию `/wp-content/plugins/`
2. Активируйте плагин через меню "Плагины" в WordPress
3. Перейдите в **HTMX → Настройки** для конфигурации

== Frequently Asked Questions ==

= Как загрузить пост по ID? =
`[htmx target="#content" fragment="123"]Загрузить пост #123[/htmx]`

= Как загрузить категорию? =
`[htmx target="#content" fragment="category:5"]Загрузить категорию[/htmx]`

= Где найти документацию? =
В админке WordPress: **HTMX → Документация**

== Screenshots ==

1. Страница настроек в админке
2. Страница документации

== Changelog ==

= 1.0.0 =
* Первая версия
* Интеграция HTMX библиотеки
* REST API для фрагментных запросов
* Шорткод [htmx]
* Страница настроек и документации

== Upgrade Notice ==

= 1.0.0 =
Первая версия плагина.
