import { test, expect } from '@playwright/test';

test('GET /blog retorna a página do blog', async ({ page }) => {
  const response = await page.goto('http://localhost:8000/blog');

  expect(response.status()).toBe(200);
  await expect(page).toHaveTitle(/Blog — PHP com Rapadura/);
  await expect(page.getByRole('heading', { level: 1, name: 'Blog' })).toBeVisible();
});

test('GET /blog marca o item Blog como ativo no menu', async ({ page }) => {
  await page.goto('http://localhost:8000/blog');

  const nav = page.getByRole('navigation', { name: 'Navegação principal' });
  await expect(nav.getByRole('link', { name: 'Blog' })).toHaveAttribute('aria-current', 'page');
});

test('GET / tem o link Blog no menu principal', async ({ page }) => {
  await page.goto('http://localhost:8000/');

  const nav = page.getByRole('navigation', { name: 'Navegação principal' });
  await expect(nav.getByRole('link', { name: 'Blog' })).toHaveAttribute('href', /\/blog$/);
});
