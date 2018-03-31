<?php

namespace hypeJunction\Slug;

use Elgg\Cache\CompositeCache;
use ElggEntity;
use Elgg\Database\QueryBuilder;

class SlugService {

	/**
	 * @var CompositeCache
	 */
	protected $cache;

	/**
	 * Constructor
	 *
	 * @param CompositeCache $cache Cache
	 */
	public function __construct(CompositeCache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Set slug
	 *
	 * @param ElggEntity $entity Entity
	 * @param null       $slug   Slug
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setSlug(ElggEntity $entity, $slug = null) {
		if (empty($slug)) {
			unset($entity->slug);

			return;
		}

		$slug = elgg_get_friendly_title($slug);
		$slug = '/' . trim($slug, '/');

		if (!$this->isAvailableSlug($entity, $slug)) {
			$slug = $this->generateSlug($entity);
		}

		$entity->slug = $slug;

		$entity->setVolatileData('use_slug', false);

		$cache_key = sha1($slug);
		$this->cache->save($cache_key, $entity->getURL(), '+1 year');
	}

	/**
	 * Get slug
	 *
	 * @param ElggEntity $entity Entity
	 *
	 * @return string
	 */
	public function getSlug(ElggEntity $entity) {
		return $entity->slug;
	}

	/**
	 * Generate a slug from entity title and guid
	 *
	 * @param ElggEntity $entity Entity
	 *
	 * @return string
	 */
	public function generateSlug(ElggEntity $entity) {
		$title = elgg_get_friendly_title($entity->title);

		return "/{$title}-{$entity->guid}";
	}

	/**
	 * Check slug availability
	 *
	 * @param ElggEntity $entity Entity
	 * @param string     $slug   Slug
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isAvailableSlug(ElggEntity $entity, $slug) {
		$flags = ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES;
		$handler = function () use ($slug, $entity) {
			$count = elgg_get_entities([
				'metadata_name_value_pairs' => [
					'slug' => $slug,
				],
				'wheres' => function (QueryBuilder $qb) use ($entity) {
					return $qb->compare('e.guid', '!=', $entity->guid, ELGG_VALUE_INTEGER);
				},
				'count' => true,
			]);

			return !$count;
		};

		return elgg_call($flags, $handler);
	}

	/**
	 * Flush cache
	 * @return void
	 */
	public function flushCache() {
		$this->cache->clear();
	}

	/**
	 * Rebuild cache
	 * @todo Add a different storage mechanism (e.g. a database table)
	 * @return void
	 */
	public function rebuildCache() {

		$entities = elgg_get_entities([
			'metadata_names' => 'slug',
			'limit' => 0,
			'batch' => true,
		]);

		foreach ($entities as $entity) {
			$cache_key = sha1($entity->slug);
			$this->cache->save($cache_key, $entity->getURL(), '+1 year');
		}
	}
}
