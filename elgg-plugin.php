<?php

return [
	'plugin' => [
		'name' => 'hypeSlug',
		'version' => '1.1.7',
	],
	'bootstrap' => \hypeJunction\Slug\Bootstrap::class,
	'events' => [
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
		'cache:flush:after' => [
			'system' => [
				\hypeJunction\Slug\FlushCache::class => [],
			],
		],
	],
];
