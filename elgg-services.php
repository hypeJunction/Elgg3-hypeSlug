<?php

return [
	'posts.slug.cache' => \DI\object(\Elgg\Cache\CompositeCache::class)
		->constructor(
			'slugs',
			\DI\get('config'),
			ELGG_CACHE_PERSISTENT | ELGG_CACHE_FILESYSTEM
		),
	'posts.slug' => \DI\object(\hypeJunction\Slug\SlugService::class)
		->constructor(\DI\get('posts.slug.cache')),
];
