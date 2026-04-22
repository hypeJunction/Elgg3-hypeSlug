import { test, expect } from '@playwright/test';
import { loginAs, getMetadata, getUserByUsername } from '../helpers/elgg';

/**
 * E2E coverage for hypeslug route-rewrite feature.
 *
 * The install script seeds a slug `/test-slug-redirect` on the admin user,
 * with slug_target pointing to /register (a core Elgg route that is always
 * available without any extra plugins active).
 *
 * Tests verify:
 *  - Normal Elgg navigation is unaffected by the plugin
 *  - A request for a slug path is transparently rewritten to the target route
 *  - An unknown slug path does not crash Elgg (returns a valid page, not 500)
 *  - The slug metadata is persisted correctly in the database
 */
test.describe('hypeslug: route rewrite', () => {

  test('homepage loads without error (plugin does not break normal routing)', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBeLessThan(400);
    // Elgg homepage contains a recognisable element
    await expect(page.locator('body')).toBeAttached();
    const content = await page.content();
    expect(content.length).toBeGreaterThan(500);
  });

  test('unknown slug path returns a valid Elgg page (not a 500 error)', async ({ page }) => {
    const response = await page.goto('/no-such-slug-' + Date.now());
    // Elgg should handle unknown slugs gracefully — any 2xx or 4xx is acceptable;
    // a 5xx indicates a crash introduced by the plugin.
    expect(response?.status()).toBeLessThan(500);
  });

  test('seeded slug /test-slug-redirect rewrites to /login route', async ({ page }) => {
    // The install script seeded slug_target = <site_url>login on admin.
    // Navigating to /test-slug-redirect should transparently render /login
    // (route:rewrite rewrites the identifier — browser URL stays the same).
    const response = await page.goto('/test-slug-redirect');
    expect(response?.status()).toBeLessThan(400);

    // The rendered page is the login page.
    // Assert the page has meaningful content — not an empty or error page.
    const content = await page.content();
    expect(content.length).toBeGreaterThan(500);

    // The login page renders a login form — no PHP fatal or Elgg error block.
    await expect(page.locator('.elgg-system-messages .elgg-message-error')).toHaveCount(0);
    // Login form is present, confirming we reached the real login page.
    // Elgg renders two login forms — target the sidebar (module-aside) one.
    await expect(page.locator('.elgg-module-aside input[name="username"]')).toBeVisible();
  });

  test('admin user has slug metadata persisted in DB', async ({ page }) => {
    const adminRow = await getUserByUsername('admin');
    expect(adminRow).toBeTruthy();

    const slugRows = await getMetadata(Number(adminRow.guid), 'slug');
    expect(slugRows.length).toBeGreaterThan(0);
    expect(slugRows[0].value).toContain('test-slug-redirect');
  });

  test('admin can log in and view their profile (smoke test for Elgg instance)', async ({ page }) => {
    await loginAs(page, 'admin');
    // After login, Elgg redirects to a logged-in page (dashboard or profile).
    // Just assert we ended up somewhere valid — not on the login page.
    expect(page.url()).not.toMatch(/\/login/);
    const content = await page.content();
    expect(content.length).toBeGreaterThan(500);
  });
});
