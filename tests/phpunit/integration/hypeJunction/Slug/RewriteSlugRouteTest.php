<?php

namespace hypeJunction\Slug;

use Elgg\IntegrationTestCase;

/**
 * Tests for RewriteSlugRoute: route:rewrite all hook handler.
 *
 * Verifies that incoming paths matching a stored slug are rewritten to the
 * entity's real route (identifier + segments), and unknown paths are passed
 * through unchanged (null return).
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

	/**
	 * Build a mock \Elgg\Hook for route:rewrite with the given identifier and segments.
	 */
	protected function makeHook(string $identifier, array $segments = []): \Elgg\Hook {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getName')->willReturn('route:rewrite');
		$hook->method('getType')->willReturn('all');
		$hook->method('getValue')->willReturn([
			'identifier' => $identifier,
			'segments'   => $segments,
		]);
		$hook->method('getParam')->willReturnCallback(function ($key, $default = null) { return $default; });
		$hook->method('getParams')->willReturn([]);
		return $hook;
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

		// Manually seed the slug and slug_target (avoids depending on
		// getURL() returning a non-empty URL for a bare test entity).
		$uniqueSlug = 'rewrite-test-' . $entity->guid;
		$entity->slug = '/' . $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();

		// Warm cache as setSlug would do.
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

		// Ensure cache does NOT have this entry — force DB path.
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

	public function testHandlerIsRegisteredForRouteRewriteHook(): void {
		// Verify the hook wiring is in place by triggering it and asserting
		// that our handler fires (i.e., a known slug gets rewritten).
		$entity = $this->createObject(['subtype' => 'test_slug_obj']);
		$uniqueSlug = 'hook-fire-test-' . $entity->guid;
		$entity->slug = '/' . $uniqueSlug;
		$entity->slug_target = elgg_get_site_url() . 'register';
		$entity->save();
		$cache = elgg()->{'posts.slug.cache'};
		$cache->save(sha1('/' . $uniqueSlug), elgg_get_site_url() . 'register', '+1 year');

		$rewritten = elgg_trigger_plugin_hook('route:rewrite', 'all', [], [
			'identifier' => $uniqueSlug,
			'segments'   => [],
		]);

		$this->assertIsArray($rewritten);
		$this->assertEquals('register', $rewritten['identifier']);
	}
}
