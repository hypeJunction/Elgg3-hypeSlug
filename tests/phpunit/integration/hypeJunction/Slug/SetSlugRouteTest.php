<?php

namespace hypeJunction\Slug;

use Elgg\IntegrationTestCase;

/**
 * Tests for SetSlugRoute: entity:url object hook handler.
 *
 * Verifies that entities with a slug get their URL overridden to the slug
 * path, and that the use_slug volatile-data escape hatch is respected.
 */
class SetSlugRouteTest extends IntegrationTestCase {

	/** @var SlugService */
	private $service;

	/** @var SetSlugRoute */
	private $handler;

	public function getPluginID(): string {
		return 'hypeslug';
	}

	public function up(): void {
		$this->service = elgg()->{'posts.slug'};
		$this->handler = new SetSlugRoute();
	}

	public function down(): void {
		$this->service = null;
		$this->handler = null;
	}

	/**
	 * Build a mock \Elgg\Hook for the entity:url hook.
	 */
	protected function makeHook(\ElggEntity $entity): \Elgg\Hook {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getEntityParam')->willReturn($entity);
		$hook->method('getName')->willReturn('entity:url');
		$hook->method('getType')->willReturn('object');
		$hook->method('getValue')->willReturn('');
		$hook->method('getParam')->willReturnCallback(function ($key, $default = null) { return $default; });
		$hook->method('getParams')->willReturn([]);
		return $hook;
	}

	public function testReturnsNullWhenEntityHasNoSlug(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$hook = $this->makeHook($entity);
		$result = ($this->handler)($hook);
		$this->assertNull($result);
	}

	public function testReturnsSlugUrlWhenEntityHasSlug(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = '/my-blog-post-' . $entity->guid;
		$this->service->setSlug($entity, $uniqueSlug);

		// setSlug sets use_slug=false as volatile data on the same object to
		// prevent infinite URL loops.  Reload from DB to get a fresh instance
		// with no volatile data, simulating what happens in a real request.
		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity($entity->guid);

		$hook = $this->makeHook($freshEntity);
		$result = ($this->handler)($hook);

		$this->assertNotNull($result);
		$this->assertIsString($result);
		// elgg_normalize_url returns absolute URL containing the slug path
		$normalizedSlug = elgg_get_friendly_title(trim($uniqueSlug, '/'));
		$this->assertStringContainsString($normalizedSlug, $result);
	}

	public function testReturnsNullWhenUseSlugVolatileDataIsFalse(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$this->service->setSlug($entity, '/slug-disabled-' . $entity->guid);

		// Simulate the escape hatch used internally by setSlug to avoid loops
		$entity->setVolatileData('use_slug', false);

		$hook = $this->makeHook($entity);
		$result = ($this->handler)($hook);
		$this->assertNull($result);
	}

	public function testSlugUrlIsAbsolute(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$this->service->setSlug($entity, '/absolute-check-' . $entity->guid);

		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity($entity->guid);

		$hook = $this->makeHook($freshEntity);
		$result = ($this->handler)($hook);

		$this->assertNotNull($result);
		$this->assertStringStartsWith('http', $result);
	}

	public function testHandlerIsRegisteredForEntityUrlHook(): void {
		// Verify the hook is wired in elgg-plugin.php — if it were removed
		// by a migration, triggering entity:url for an object with a slug
		// would not return the slug URL.
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = '/hook-wired-' . $entity->guid;
		$this->service->setSlug($entity, $uniqueSlug);

		// Reload to clear the use_slug=false volatile data set by setSlug.
		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity($entity->guid);

		$url = $freshEntity->getURL();
		// If the handler is registered and fires, the URL contains the slug.
		// If it's not registered, the URL is whatever the default is (empty or /view/guid).
		$normalizedSlug = elgg_get_friendly_title(trim($uniqueSlug, '/'));
		$this->assertStringContainsString($normalizedSlug, $url);
	}
}
