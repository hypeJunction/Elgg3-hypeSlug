<?php

namespace hypeJunction\Slug;

use Elgg\Event;

/**
 * SetSlugRoute class.
 */
class SetSlugRoute {

	/**
	 * @elgg_plugin_hook entity:url object
	 *
	 * @param Event $event Hook
	 *
	 * @return string
	 */
	public function __invoke(Event $event) {
		$entity = $event->getEntityParam();

		if ($entity->getVolatileData('use_slug') === false) {
			return null;
		}

		if ($entity->slug) {
			return elgg_normalize_url($entity->slug);
		}
	}
}
