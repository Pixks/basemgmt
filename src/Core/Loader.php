<?php

declare(strict_types=1);

namespace BaseMgmt\Core;

defined('ABSPATH') || exit;

/**
 * Collects WordPress actions and filters then runs them all at once.
 * Standard WP Plugin Boilerplate pattern.
 */
final class Loader {

	/** @var array<int, array{hook:string, component:object|string, callback:string, priority:int, args:int}> */
	private array $actions = [];

	/** @var array<int, array{hook:string, component:object|string, callback:string, priority:int, args:int}> */
	private array $filters = [];

	public function add_action(
		string $hook,
		object|string $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions[] = compact('hook', 'component', 'callback', 'priority') + ['args' => $accepted_args];
	}

	public function add_filter(
		string $hook,
		object|string $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters[] = compact('hook', 'component', 'callback', 'priority') + ['args' => $accepted_args];
	}

	public function run(): void {
		foreach ( $this->filters as $f ) {
			add_filter($f['hook'], [$f['component'], $f['callback']], $f['priority'], $f['args']);
		}
		foreach ( $this->actions as $a ) {
			add_action($a['hook'], [$a['component'], $a['callback']], $a['priority'], $a['args']);
		}
	}
}
