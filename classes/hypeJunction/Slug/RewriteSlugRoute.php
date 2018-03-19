<?php

namespace hypeJunction\Slug;

use Elgg\Hook;

class RewriteSlugRoute {

	/**
	 * Rewrite slug routes
	 *
	 * @elgg_plugin_hook route:rewrite all
	 *
	 * @param Hook $hook Hook
	 *
	 * @return mixed
	 */
	public function __invoke(Hook $hook) {

		$cache = elgg()->{'posts.slug.cache'};
		/* @var $cache \Elgg\Cache\CompositeCache */

		$value = $hook->getValue();

		$identifier = elgg_extract('identifier', $value);
		if (!$identifier) {
			return null;
		}

		$segments = elgg_extract('segments', $value);

		$slug = "/$identifier";
		if ($segments) {
			$slug .= '/' . implode('/', $segments);
		}

		$cache_key = sha1($slug);
		$url = $cache->load($cache_key);

		if (!$url) {
			$entities = elgg_get_entities([
				'metadata_name_value_pairs' => [
					'slug' => $slug,
				],
				'limit' => 1,
			]);

			if ($entities) {
				$entity = array_shift($entities);
				/* @var $entity \ElggEntity */

				$entity->setVolatileData('use_slug', false);
				$url = $entity->getURL();

				$cache->save($cache_key, $url, '+1 year');
			}
		}

		if ($url) {
			$url = substr($url, strlen(elgg_get_site_url()) - 1);
			$url = trim($url, '/');

			$segments = explode('/', $url);
			$identifier = array_shift($segments);

			return [
				'identifier' => $identifier,
				'segments' => $segments,
			];
		}
	}
}
