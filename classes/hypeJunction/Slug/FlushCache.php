<?php

namespace hypeJunction\Slug;

use Elgg\Event;

class FlushCache {

	/**
	 * Flush and rebuild the slug cache when the system cache is flushed.
	 *
	 * @param Event $event Event
	 *
	 * @return void
	 */
	public function __invoke(Event $event) {
		$service = SlugService::instance();
		$service->flushCache();
		$service->rebuildCache();
	}
}
