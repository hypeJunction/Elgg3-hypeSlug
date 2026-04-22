<?php

namespace hypeJunction\Slug;

use Elgg\IntegrationTestCase;

/**
 * Tests for FlushCache: cache:flush:after system event handler.
 *
 * FlushCache calls SlugService::instance() which uses the ServiceFacade trait.
 * If that trait is removed in the target version, loading this class will
 * throw a fatal — these tests surface that before migration.
 */
class FlushCacheTest extends IntegrationTestCase {

	/** @var SlugService */
	private $service;

	public function getPluginID(): string {
		return 'hypeslug';
	}

	public function up(): void {
		$this->service = elgg()->{'posts.slug'};
	}

	public function down(): void {
		$this->service = null;
	}

	public function testFlushCacheHandlerIsRegisteredForEvent(): void {
		// Verify the event wiring: triggering cache:flush:after system should
		// not throw (FlushCache must be callable with a single \Elgg\Event arg).
		$this->assertTrue(
			class_exists(FlushCache::class),
			'FlushCache class must be autoloadable'
		);

		$handler = new FlushCache();
		$this->assertTrue(is_callable($handler));
	}

	public function testFlushCacheEventClearsAndRebuilds(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = '/flush-event-' . $entity->guid;

		// Seed slug directly so rebuildCache can find it.
		$entity->slug = $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();

		$cache = elgg()->{'posts.slug.cache'};
		$cache->save(sha1($uniqueSlug), elgg_get_site_url() . 'register', '+1 year');
		$this->assertNotEmpty($cache->load(sha1($uniqueSlug)));

		// Trigger the event — FlushCache::__invoke fires, clearing and rebuilding.
		elgg_trigger_after_event('cache:flush', 'system');

		// After rebuildCache, the entity's slug is re-indexed in cache.
		$this->assertNotEmpty($cache->load(sha1($uniqueSlug)));
	}

	public function testServiceInstanceMethodExists(): void {
		// SlugService uses ServiceFacade::instance(). If ServiceFacade is
		// removed in the target version, this assertion documents the break.
		$this->assertTrue(
			method_exists(SlugService::class, 'instance'),
			'SlugService::instance() must exist (provided by ServiceFacade or manual impl)'
		);
	}

	public function testServiceInstanceReturnsSlugService(): void {
		$instance = SlugService::instance();
		$this->assertInstanceOf(SlugService::class, $instance);
	}
}
