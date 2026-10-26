import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

async function openAdmin(page) {
  const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
  const css = [manifest['resources/css/app.css'].file, ...(manifest['resources/js/admin.js'].css ?? [])]
    .map(file => `<link rel="stylesheet" href="/build/${file}">`).join('');
  const event = {
    id: 9, name: 'Encontro PHP', slug: 'encontro-php', status: 'rascunho',
    starts_at: '2026-11-14T14:00:00Z', location: 'Fortaleza — CE', is_online: false,
    latitude: -3.7654321, longitude: -38.5432109,
  };
  await page.route('**/admin/events', route => route.fulfill({
    contentType: 'text/html',
    body: `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${css}</head><body><div id="admin-app"></div><script type="module" src="/build/${manifest['resources/js/admin.js'].file}"></script></body></html>`,
  }));
  await page.route('**/admin/api/**', route => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    const json = path.endsWith('/me')
      ? { id: 1, name: 'Administrador de teste', role: 'admin', is_active: true }
      : request.method() === 'GET'
        ? { data: [event], meta: { current_page: 1, last_page: 1, total: 1, per_page: 9 } }
        : event;
    return route.fulfill({ json });
  });
  await page.goto('http://localhost:8000/admin/events');
  await expect(page.getByRole('button', { name: 'Novo evento', exact: true })).toBeVisible();
}

test('edição carrega as coordenadas e envia campos vazios para removê-las', async ({ page }) => {
  await openAdmin(page);
  await page.getByRole('button', { name: 'Editar', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog.getByLabel('Latitude', { exact: true })).toHaveValue('-3.7654321');
  await expect(dialog.getByLabel('Longitude', { exact: true })).toHaveValue('-38.5432109');
  await dialog.getByLabel('Latitude', { exact: true }).fill('');
  await dialog.getByLabel('Longitude', { exact: true }).fill('');
  const [request] = await Promise.all([
    page.waitForRequest(request => request.url().endsWith('/admin/api/events/9') && request.method() === 'POST'),
    dialog.getByRole('button', { name: 'Salvar', exact: true }).click(),
  ]);
  const data = await new Response(request.postDataBuffer(), { headers: { 'Content-Type': request.headers()['content-type'] } }).formData();
  expect(data.get('_method')).toBe('PUT');
  expect(data.get('latitude')).toBe('');
  expect(data.get('longitude')).toBe('');
});

test('criação envia coordenadas zero sem descartá-las e oculta campos para evento online', async ({ page }) => {
  await openAdmin(page);
  await page.getByRole('button', { name: 'Novo evento', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByRole('button', { name: 'Ativar evento online' }).click();
  await expect(dialog.getByLabel('Latitude', { exact: true })).toHaveCount(0);
  await dialog.getByRole('button', { name: 'Desativar evento online' }).click();
  await dialog.locator('input[type="text"]').first().fill('Evento de teste');
  await dialog.locator('input[type="datetime-local"]').first().fill('2026-11-14T14:00');
  await dialog.getByLabel('Latitude', { exact: true }).fill('0');
  await dialog.getByLabel('Longitude', { exact: true }).fill('0');
  const [request] = await Promise.all([
    page.waitForRequest(request => request.url().endsWith('/admin/api/events') && request.method() === 'POST'),
    dialog.getByRole('button', { name: 'Salvar', exact: true }).click(),
  ]);
  expect(request.postDataJSON().latitude).toBe(0);
  expect(request.postDataJSON().longitude).toBe(0);
});
