import { test, expect } from '@playwright/test';

test('GET / retorna página institucional', async ({ page }) => {
  const response = await page.goto('http://localhost:8000/');

  await expect(page).toHaveTitle(/PHP com Rapadura/i);
  expect(response.status()).toBe(200);
});

test('GET / exibe seção hero', async ({ page }) => {
  await page.goto('http://localhost:8000/');

  await expect(page.locator('main')).toBeVisible();
});

test('GET / navegação principal está presente', async ({ page }) => {
  await page.goto('http://localhost:8000/');

  await expect(page.getByRole('navigation', { name: 'Navegação principal' })).toBeVisible();
});
