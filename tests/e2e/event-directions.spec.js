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

for (const [browser, userAgent] of [
  ['Safari no iPhone', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'],
  ['Chrome no iPhone', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/130.0.0.0 Mobile/15E148 Safari/604.1'],
]) {
  test.describe(browser, () => {
    test.use({ userAgent, viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });

    test('oferece links nativos na mesma aba e alternativa web sem fechar o painel', async ({ page }) => {
      await openEvent(page);
      await page.getByRole('button', { name: 'Como chegar' }).tap();
      const navigation = page.getByRole('navigation', { name: 'Aplicativos para chegar ao evento' });
      for (const [name, protocol, parameter] of [
        ['Uber', 'uber:', 'dropoff[formatted_address]'],
        ['Google Maps', 'comgooglemaps:', 'q'],
        ['Waze', 'waze:', 'q'],
      ]) {
        const link = navigation.getByRole('link', { name: `Abrir ${name} com o endereço do evento` });
        const url = new URL(await link.getAttribute('href'));
        expect(url.protocol).toBe(protocol);
        expect(url.searchParams.get(parameter)).toBe(address);
        await expect(link).toHaveAttribute('target', '_self');

        // O navegador de teste não tem os apps: bloqueia somente a navegação externa.
        await link.evaluate(el => el.addEventListener('click', event => event.preventDefault(), { once: true }));
        await link.tap();
        const fallback = page.getByRole('link', { name: `Não abriu? Usar ${name} no navegador` });
        await expect(fallback).toBeVisible();
        expect(new URL(await fallback.getAttribute('href')).protocol).toBe('https:');
        await link.evaluate(el => el.dispatchEvent(new FocusEvent('focusout', { bubbles: true, relatedTarget: null })));
        await expect(navigation).toBeVisible();
      }
    });
  });
}

test.describe('Chrome no Android', () => {
  test.use({
    userAgent: 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true,
  });

  test('aponta para os aplicativos com endereço e fallback web', async ({ page }) => {
    await openEvent(page);
    await page.getByRole('button', { name: 'Como chegar' }).tap();
    for (const [name, packageName, parameter] of [
      ['Uber', 'com.ubercab', 'dropoff[formatted_address]'],
      ['Google Maps', 'com.google.android.apps.maps', 'query'],
      ['Waze', 'com.waze', 'q'],
    ]) {
      const link = page.getByRole('link', { name: `Abrir ${name} com o endereço do evento` });
      const href = await link.getAttribute('href');
      expect(href).toMatch(/^intent:/);
      expect(href).toContain(`package=${packageName};`);
      const fallback = new URL(decodeURIComponent(href.match(/S\.browser_fallback_url=([^;]+)/)[1]));
      expect(fallback.protocol).toBe('https:');
      expect(fallback.searchParams.get(parameter)).toBe(address);
      await expect(link).toHaveAttribute('target', '_self');
    }
  });
});
