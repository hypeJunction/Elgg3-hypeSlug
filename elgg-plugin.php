<?php

return [
	'plugin' => [
		'name' => 'hypeSlug',
		'version' => '1.1.7',
	],
	'hooks' => [
		'route:rewrite' => [
			'all' => [
				\hypeJunction\Slug\RewriteSlugRoute::class => [],
			],
		],
		'entity:url' => [
			'object' => [
				\hypeJunction\Slug\SetSlugRoute::class => [
					'priority' => 900,
				],
			],
		],
		'fields' => [
			'object' => [
				\hypeJunction\Slug\AddFormField::class => [],
			],
		],
	],
	'events' => [
		'cache:flush:after' => [
			'system' => [
				\hypeJunction\Slug\FlushCache::class => [],
			],
		],
	],
];
