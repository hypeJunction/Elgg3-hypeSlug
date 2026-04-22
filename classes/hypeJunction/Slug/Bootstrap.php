<?php

namespace hypeJunction\Slug;

use Elgg\DefaultPluginBootstrap;

/**
 * Plugin bootstrap — registers handlers that must be active before init:system.
 *
 * route:rewrite fires inside Application::allowPathRewrite(), which runs in
 * bootApplication() BEFORE the init:system event sequence.  Handlers registered
 * via elgg-plugin.php are only wired during init:system, so they are too late.
 * Registering here (boot() runs during plugins_boot:before:system) ensures the
 * handler is present when the hook fires.
 */
class Bootstrap extends DefaultPluginBootstrap {

	public function boot(): void {
		elgg_register_plugin_hook_handler('route:rewrite', 'all', RewriteSlugRoute::class);
	}
}
