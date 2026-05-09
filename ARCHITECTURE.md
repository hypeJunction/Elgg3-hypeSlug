# hypeSlug — Architecture (Elgg 4.x)

## Summary

Adds URL slug support to Elgg entities. Entities with a `slug` metadata key
get an alternate vanity URL (`/<slug>`) that rewrites at route-dispatch time
back to the canonical entity URL cached in `slug_target`. Provides a
declarative "slug" form field plugins can add to entity edit forms via the
`fields` hook, and a service (`posts.slug`) that encapsulates slug
generation, uniqueness checks, and the write-through cache.

## Directory structure

```
hypeslug/
├── composer.json                 # elgg-plugin metadata, php >=7.4, elgg/elgg ^4.0
├── elgg-plugin.php               # declarative hooks/events
├── elgg-services.php             # DI bindings: posts.slug, posts.slug.cache
├── CHANGELOG.md
├── ARCHITECTURE.md
├── languages/en.php
└── classes/hypeJunction/Slug/
    ├── SlugService.php           # service facade (posts.slug)
    ├── SlugField.php             # hypejunction/fields Field subclass
    ├── AddFormField.php          # 'fields' hook handler
    ├── SetSlugRoute.php          # 'entity:url' hook handler
    ├── RewriteSlugRoute.php      # 'route:rewrite' hook handler
    └── FlushCache.php            # 'cache:flush:after' event handler
```

## Registered hooks

| Hook | Type | Class | Priority |
|------|------|-------|----------|
| `route:rewrite` | `all` | `RewriteSlugRoute` | default |
| `entity:url` | `object` | `SetSlugRoute` | 900 |
| `fields` | `object` | `AddFormField` | default |

## Registered events

| Event | Type | Class |
|-------|------|-------|
| `cache:flush:after` | `system` | `FlushCache` |

## Services (elgg-services.php)

| DI key | Class | Deps |
|--------|-------|------|
| `posts.slug.cache` | `Elgg\Cache\CompositeCache` | `config` |
| `posts.slug` | `hypeJunction\Slug\SlugService` | `posts.slug.cache` |

Access pattern: `elgg()->{'posts.slug'}` or `SlugService::instance()` via
the `ServiceFacade` trait.

## Entities

None — plugin operates on arbitrary `ElggEntity` instances by reading/
writing the `slug` and `slug_target` metadata keys.

## Dependencies

**Runtime (suggested)** — `hypePost` (provides `hypejunction/fields`
collection used by `AddFormField`/`SlugField`). Plugins that call
`SlugField` directly are effectively dependent on `hypePost` being active.

**Composer** — `elgg/elgg ^4.0`, `composer/installers ^2.0`. No third-party
PHP deps.

## Migration notes (3.x → 4.x)

- Removed legacy `PluginBootstrap` subclass and `autoloader.php`. All boot
  and init hook registrations moved to declarative `hooks`/`events` arrays
  in `elgg-plugin.php`.
- Replaced the `cache:flush:after` closure with `FlushCache::__invoke()`
  (Iron Law 5: closures are not serializable in Elgg 4+).
- `\DI\object()` → `\DI\create()` in `elgg-services.php` (PHP-DI 6).
- `Elgg\Di\ServiceFacade`, `Elgg\Loggable` → `Elgg\Traits\*` namespaces.
- `\InvalidParameterException` → `\Elgg\Exceptions\InvalidParameterException`
  in docblocks.
- `manifest.xml` deleted; composer.json lowercased `installer-name` and
  added `extra.elgg-plugin.id` pointing at `hypeslug`.
- `composer.json` bumped: `php >=7.4`, `elgg/elgg ^4.0`,
  `composer/installers ^2.0`, `config.allow-plugins.composer/installers`.

## Known issues

- `RewriteSlugRoute` and `SlugService` use `sha1()` for cache key
  derivation. Flagged by the security sweep as deprecated-crypto; accepted
  as non-sensitive (hashes identify cache entries, not credentials).
- No PHPUnit test suite. Validation is limited to activation + DI-binding
  smoke tests on the elgg4 Docker harness.

## Seeding

No seeder required. This plugin owns no entity types, subtypes, or persistent relationship schemas — it is a pure UI/utility/admin plugin with no persisted entity surface of its own.
