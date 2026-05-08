<?php

namespace hypeJunction\Slug;

use Elgg\Event;
use Elgg\IntegrationTestCase;

/**
 * Tests for RewriteSlugRoute: route:rewrite event handler.
 */
class RewriteSlugRouteTest extends IntegrationTestCase {

	/** @var SlugService */
	private $service;

	/** @var RewriteSlugRoute */
	private $handler;

	public function getPluginID(): string {
		return 'hypeslug';
	}

	public function up(): void {
		$this->service = elgg()->{'posts.slug'};
		$this->handler = new RewriteSlugRoute();
	}

	public function down(): void {
		$this->service = null;
		$this->handler = null;
	}

	protected function makeHook(string $identifier, array $segments = []): Event {
		$event = $this->getMockBuilder(Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getName')->willReturn('route:rewrite');
		$event->method('getType')->willReturn('all');
		$event->method('getValue')->willReturn([
			'identifier' => $identifier,
			'segments'   => $segments,
		]);
		$event->method('getParam')->willReturnCallback(function ($key, $default = null) { return $default; });
		$event->method('getParams')->willReturn([]);
		return $event;
	}

	public function testReturnsNullForEmptyIdentifier(): void {
		$hook = $this->makeHook('');
		$result = ($this->handler)($hook);
		$this->assertNull($result);
	}

	public function testReturnsNullForUnknownSlug(): void {
		$hook = $this->makeHook('no-such-slug-' . uniqid());
		$result = ($this->handler)($hook);
		$this->assertNull($result);
	}

	public function testRewritesKnownSlugToInternalRoute(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);

		$uniqueSlug = 'rewrite-test-' . $entity->guid;
		$entity->slug = '/' . $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();

		$cache = elgg()->{'posts.slug.cache'};
		$cache->save(sha1('/' . $uniqueSlug), elgg_get_site_url() . 'register', '+1 year');

		$hook = $this->makeHook($uniqueSlug);
		$result = ($this->handler)($hook);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('identifier', $result);
		$this->assertEquals('register', $result['identifier']);
	}

	public function testRewriteWorksViaDatabaseLookupWhenCacheMissed(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);

		$uniqueSlug = 'db-lookup-' . $entity->guid;
		$entity->slug = '/' . $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();

		$cache = elgg()->{'posts.slug.cache'};
		$cache->delete(sha1('/' . $uniqueSlug));

		$hook = $this->makeHook($uniqueSlug);
		$result = ($this->handler)($hook);

		$this->assertIsArray($result);
		$this->assertEquals('register', $result['identifier']);
	}

	public function testRewriteWithSegmentsInSlug(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);

		$uniqueBase = 'seg-test-' . $entity->guid;
		$entity->slug = '/' . $uniqueBase . '/detail';
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();

		$cache = elgg()->{'posts.slug.cache'};
		$cache->save(sha1('/' . $uniqueBase . '/detail'), elgg_get_site_url() . 'register', '+1 year');

		$hook = $this->makeHook($uniqueBase, ['detail']);
		$result = ($this->handler)($hook);

		$this->assertIsArray($result);
		$this->assertEquals('register', $result['identifier']);
	}

	public function testHandlerIsRegisteredForRouteRewriteEvent(): void {
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = 'hook-fire-test-' . $entity->guid;
		$entity->slug = '/' . $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();
		$cache = elgg()->{'posts.slug.cache'};
		$cache->save(sha1('/' . $uniqueSlug), elgg_get_site_url() . 'register', '+1 year');

		$rewritten = elgg_trigger_event_results('route:rewrite', 'all', [], [
			'identifier' => $uniqueSlug,
			'segments'   => [],
		]);

		$this->assertIsArray($rewritten);
		$this->assertEquals('register', $rewritten['identifier']);
	}
}
