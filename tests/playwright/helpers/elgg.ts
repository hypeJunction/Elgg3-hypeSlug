import { Page } from '@playwright/test';
import mysql from 'mysql2/promise';

const DB_CONFIG = {
  host: process.env.ELGG_DB_HOST || 'db',
  port: Number(process.env.ELGG_DB_PORT || 3306),
  user: process.env.ELGG_DB_USER || 'elgg',
  password: process.env.ELGG_DB_PASS || 'elgg',
  database: process.env.ELGG_DB_NAME || 'elgg',
};

export async function loginAs(page: Page, username: string, password = 'admin12345') {
  await page.goto('/login');
  // Elgg 4.x renders two login forms: a hidden dropdown and a visible sidebar.
  // Always target the sidebar form to avoid filling the hidden one.
  await page.fill('.elgg-module-aside input[name="username"]', username);
  await page.fill('.elgg-module-aside input[name="password"]', password);
  await Promise.all([
    page.waitForURL(url => !url.toString().includes('/login'), { timeout: 10000 }),
    page.click('.elgg-module-aside button[type="submit"]'),
  ]);
}

export async function queryDb(sql: string, params: any[] = []) {
  const conn = await mysql.createConnection(DB_CONFIG);
  const [rows] = await conn.execute(sql, params);
  await conn.end();
  return rows as Record<string, any>[];
}

export async function getMetadata(entityGuid: number, name: string) {
  return queryDb(
    'SELECT * FROM elgg_metadata WHERE entity_guid = ? AND name = ?',
    [entityGuid, name]
  );
}

export async function getUserByUsername(username: string) {
  const rows = await queryDb(
    `SELECT e.guid FROM elgg_entities e
     JOIN elgg_metadata m ON m.entity_guid = e.guid
     WHERE e.type = 'user' AND m.name = 'username' AND m.value = ?
     LIMIT 1`,
    [username]
  );
  return rows[0] ?? null;
}
