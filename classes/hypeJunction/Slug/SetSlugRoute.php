<?php

namespace hypeJunction\Slug;

use Elgg\Hook;

class SetSlugRoute {

	/**
	 * @elgg_plugin_hook entity:url object
	 *
	 * @param Hook $hook Hook
	 *
	 * @return string
	 */
	public function __invoke(Hook $hook) {
		$entity = $hook->getEntityParam();

		if ($entity->getVolatileData('use_slug') === false) {
			return null;
		}

		if ($entity->slug) {
			return elgg_normalize_url($entity->slug);
		}
	}
}
