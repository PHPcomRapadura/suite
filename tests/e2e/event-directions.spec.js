import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const address = 'Café & Rapadura — Rua São João, 42 #2, Fortaleza - CE';

// Usa os bundles reais sem depender de um evento previamente cadastrado no banco.
async function openEvent(page, { layout = 1, location = address, is_online = false } = {}) {
  const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
  const stylesheets = [manifest['resources/css/app.css'].file, ...(manifest['resources/js/event-site.js'].css ?? [])]
    .map(file => `<link rel="stylesheet" href="/build/${file}">`).join('');
  const data = {
    event: { name: 'Encontro PHP', slug: 'encontro-php', location, is_online },
    site: { layout, primary_color: '#025c98', secondary_color: '#f59e0b', font: 'lexend' },
    sponsors: {}, schedule: {}, speakers: [],
  };
  await page.route('**/__event-directions-test', route => route.fulfill({
    contentType: 'text/html',
    body: `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${stylesheets}</head><body><script id="event-site-data" type="application/json">${JSON.stringify(data)}</script><div id="event-site-app"></div><script type="module" src="/build/${manifest['resources/js/event-site.js'].file}"></script></body></html>`,
  }));
  await page.goto('http://localhost:8000/__event-directions-test');
  await expect(page.getByRole('heading', { name: 'Encontro PHP', exact: true })).toBeVisible();
}

for (const layout of [1, 2, 3]) {
  test(`layout ${layout}: aplicativos recebem o endereço completo do evento`, async ({ page }) => {
    await openEvent(page, { layout });
    const button = page.getByRole('button', { name: 'Como chegar' });
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await button.click();
    const navigation = page.getByRole('navigation', { name: 'Aplicativos para chegar ao evento' });
    await expect(navigation).toBeVisible();
    const expected = [
      ['Uber', 'm.uber.com', 'dropoff[formatted_address]'],
      ['Google Maps', 'www.google.com', 'query'],
      ['Waze', 'waze.com', 'q'],
    ];
    for (const [name, host, parameter] of expected) {
      const link = navigation.getByRole('link', { name: `Abrir ${name} com o endereço do evento` });
      const url = new URL(await link.getAttribute('href'));
      expect(url.protocol).toBe('https:');
      expect(url.hostname).toBe(host);
      expect(url.searchParams.get(parameter)).toBe(address);
      await expect(link.locator('svg').first()).toBeVisible();
    }
    const maps = new URL(await navigation.getByRole('link', { name: /Abrir Google Maps/ }).getAttribute('href'));
    expect(maps.searchParams.get('api')).toBe('1');
  });
}

test('fecha por Escape, clique fora e pelo próprio botão', async ({ page }) => {
  await openEvent(page);
  const button = page.locator('.event-directions__trigger');
  const navigation = page.getByRole('navigation', { name: 'Aplicativos para chegar ao evento' });
  await button.focus();
  await page.keyboard.press('Enter');
  await expect(navigation.getByRole('link').first()).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(navigation).toBeHidden();
  await expect(button).toBeFocused();
  await button.click();
  await button.click();
  await expect(navigation).toBeHidden();
  await button.click();
  await page.getByRole('heading', { name: 'Encontro PHP', exact: true }).click();
  await expect(navigation).toBeHidden();
});

test('não exibe deslocamento para evento online', async ({ page }) => {
  await openEvent(page, { is_online: true });
  await expect(page.getByRole('button', { name: 'Como chegar' })).toHaveCount(0);
});

test('não exibe deslocamento quando o local está em branco', async ({ page }) => {
  await openEvent(page, { location: '   ' });
  await expect(page.getByRole('button', { name: 'Como chegar' })).toHaveCount(0);
});

test('painel permanece dentro de uma tela pequena', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 568 });
  await openEvent(page);
  await page.getByRole('button', { name: 'Como chegar' }).click();
  const panel = await page.locator('.event-directions__panel').boundingBox();
  expect(panel.x).toBeGreaterThanOrEqual(0);
  expect(panel.y).toBeGreaterThanOrEqual(0);
  expect(panel.x + panel.width).toBeLessThanOrEqual(320);
  expect(panel.y + panel.height).toBeLessThanOrEqual(568);
});
