<?php
/**
 * Singleton trait for plugin classes.
 *
 * @package Kkorsakov\Htmx
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Kkorsakov\Htmx\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Singleton {

	protected static $instance = null;

	final public static function get_instance(): self {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	final protected function __construct() {
		$this->init();
	}

	protected function init(): void {}

	final public function __clone() {}

	final public function __wakeup() {}
}
