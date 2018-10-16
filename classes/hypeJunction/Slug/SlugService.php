<?php

namespace hypeJunction\Slug;

use Elgg\Cache\CompositeCache;
use Elgg\Di\ServiceFacade;
use Elgg\Loggable;
use ElggEntity;
use Elgg\Database\QueryBuilder;

class SlugService {

	use Loggable;
	use ServiceFacade;

	/**
	 * @var CompositeCache
	 */
	protected $cache;

	public static function name() {
		return 'posts.slug';
	}

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
	 */
	public function setSlug(ElggEntity $entity, $slug = null) {
		if (empty($slug)) {
			unset($entity->slug);
			unset($entity->slug_target);

			return;
		}

		$slug = elgg_get_friendly_title($slug);
		$slug = '/' . trim($slug, '/');

		if (!$this->isAvailableSlug($entity, $slug)) {
			$slug = $this->generateSlug($entity);
		}

		$entity->setVolatileData('use_slug', false);

		$entity->slug = $slug;
		$entity->slug_target = $entity->getURL();

		$cache_key = sha1($slug);
		$this->cache->save($cache_key, $entity->slug_target, '+1 year');
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
	 */
	public function isAvailableSlug(ElggEntity $entity, $slug) {
		$flags = ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES;
		$handler = function () use ($slug, $entity) {
			$count = elgg_get_entities([
				'metadata_name_value_pairs' => [
					[
						'name' => 'slug',
						'value' => $slug,
						'case_sensitive' => false,
					],
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

		elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () {
			$entities = elgg_get_entities([
				'metadata_names' => 'slug',
				'limit' => 0,
				'batch' => true,
			]);

			foreach ($entities as $entity) {
				$entity->setVolatileData('use_slug', false);

				if (!$entity->slug_target) {
					$entity->slug_target = $entity->getURL();
				}

				$cache_key = sha1('/' . trim($entity->slug, '/'));
				$this->cache->save($cache_key, $entity->slug_target, '+1 year');
			}
		});
	}
}
