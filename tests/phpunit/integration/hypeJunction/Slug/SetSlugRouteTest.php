<?php

namespace hypeJunction\Slug;

use Elgg\Event;
use Elgg\IntegrationTestCase;

/**
 * Tests for SetSlugRoute: entity:url object event handler.
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

	protected function makeHook(\ElggEntity $entity): Event {
		$event = $this->getMockBuilder(Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getEntityParam')->willReturn($entity);
		$event->method('getName')->willReturn('entity:url');
		$event->method('getType')->willReturn('object');
		$event->method('getValue')->willReturn('');
		$event->method('getParam')->willReturnCallback(function ($key, $default = null) { return $default; });
		$event->method('getParams')->willReturn([]);
		return $event;
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

		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity((int) $entity->guid);

		$hook = $this->makeHook($freshEntity);
		$result = ($this->handler)($hook);

		$this->assertNotNull($result);
		$this->assertIsString($result);
		$normalizedSlug = elgg_get_friendly_title(trim($uniqueSlug, '/'));
		$this->assertStringContainsString($normalizedSlug, $result);
	}

	public function testReturnsNullWhenUseSlugVolatileDataIsFalse(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$this->service->setSlug($entity, '/slug-disabled-' . $entity->guid);

		$entity->setVolatileData('use_slug', false);

		$hook = $this->makeHook($entity);
		$result = ($this->handler)($hook);
		$this->assertNull($result);
	}

	public function testSlugUrlIsAbsolute(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$this->service->setSlug($entity, '/absolute-check-' . $entity->guid);

		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity((int) $entity->guid);

		$hook = $this->makeHook($freshEntity);
		$result = ($this->handler)($hook);

		$this->assertNotNull($result);
		$this->assertStringStartsWith('http', $result);
	}

	public function testHandlerIsRegisteredForEntityUrlEvent(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = '/hook-wired-' . $entity->guid;
		$this->service->setSlug($entity, $uniqueSlug);

		_elgg_services()->entityCache->delete($entity->guid);
		$freshEntity = get_entity((int) $entity->guid);

		$url = $freshEntity->getURL();
		$normalizedSlug = elgg_get_friendly_title(trim($uniqueSlug, '/'));
		$this->assertStringContainsString($normalizedSlug, $url);
	}
}
