<?php

use hypeJunction\Slug\AddFormField;
use hypeJunction\Slug\RewriteSlugRoute;
use hypeJunction\Slug\SetSlugRoute;

require_once __DIR__ . '/autoloader.php';

return function () {

	elgg_register_plugin_hook_handler('route:rewrite', 'all', RewriteSlugRoute::class);

	elgg_register_plugin_hook_handler('cache:flush', 'system', function () {
		elgg()->get('posts.slug')->flushCache();
	});

	elgg_register_event_handler('init', 'system', function () {

		elgg_register_plugin_hook_handler('entity:url', 'object', SetSlugRoute::class, 900);

		elgg_register_plugin_hook_handler('fields', 'object', AddFormField::class);

	});

};
