<?php

namespace hypeJunction\Slug;

use Elgg\HooksRegistrationService\Hook;
use Elgg\Includer;
use Elgg\PluginBootstrap;

class Bootstrap extends PluginBootstrap {

	/**
	 * Get plugin root
	 * @return string
	 */
	protected function getRoot() {
		return $this->plugin->getPath();
	}

	/**
	 * {@inheritdoc}
	 */
	public function load() {
		Includer::requireFileOnce($this->getRoot() . '/autoloader.php');
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot() {
		elgg_register_plugin_hook_handler('route:rewrite', 'all', RewriteSlugRoute::class);

		elgg_register_event_handler('cache:flush:after', 'system', function () {
			SlugService::instance()->flushCache();
			SlugService::instance()->rebuildCache();
		});
	}

	/**
	 * {@inheritdoc}
	 */
	public function init() {
		elgg_register_plugin_hook_handler('entity:url', 'object', SetSlugRoute::class, 900);

		elgg_register_plugin_hook_handler('fields', 'object', AddFormField::class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function shutdown() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function activate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function deactivate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function upgrade() {

	}
}