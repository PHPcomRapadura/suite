import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

async function openEvent(page, layout, calendar = true) {
  const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
  const css = [manifest['resources/css/app.css'].file, ...(manifest['resources/js/event-site.js'].css ?? [])]
    .map(file => `<link rel="stylesheet" href="/build/${file}">`).join('');
  const data = {
    event: {
      name: 'Encontro PHP', starts_at: '2026-11-14T14:00:00Z', ends_at: '2026-11-14T18:00:00Z',
      location: 'Fortaleza — CE',
      calendar: calendar ? {
        google: 'https://calendar.google.com/calendar/render?action=TEMPLATE',
        apple: 'http://localhost:8000/encontro-php/calendario.ics',
        microsoft: 'https://outlook.live.com/calendar/0/deeplink/compose?rru=addevent',
      } : null,
    },
    site: { layout, primary_color: '#025c98', secondary_color: '#f59e0b', font: 'lexend', ticket_url: 'https://example.org/ingressos' },
    sponsors: {}, schedule: {}, speakers: [],
  };
  await page.route('**/__event-calendar-test', route => route.fulfill({
    contentType: 'text/html',
    body: `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${css}</head><body><script id="event-site-data" type="application/json">${JSON.stringify(data)}</script><div id="event-site-app"></div><script type="module" src="/build/${manifest['resources/js/event-site.js'].file}"></script></body></html>`,
  }));
  await page.goto('http://localhost:8000/__event-calendar-test');
  await expect(page.getByRole('heading', { name: 'Encontro PHP', exact: true })).toBeVisible();
}

for (const layout of [1, 2, 3]) {
  test(`layout ${layout}: abre opções, permite teclado e mantém a inscrição disponível`, async ({ page }) => {
    await openEvent(page, layout);
    const button = page.getByRole('button', { name: 'Adicionar ao calendário' });
    await button.click();
    const options = page.getByRole('navigation', { name: 'Opções para adicionar ao calendário' });
    await expect(options.getByRole('link')).toHaveCount(3);
    await expect(options.getByRole('link', { name: 'Google Agenda' })).toBeFocused();
    await expect(options.getByRole('link', { name: 'Microsoft Outlook' })).toHaveAttribute('target', '_blank');
    await expect(options.getByRole('link', { name: 'Apple Calendário' })).toHaveAttribute('href', /\/calendario\.ics$/);
    await expect(options.getByRole('link', { name: 'Apple Calendário' })).toHaveAttribute('target', '_self');
    for (const option of await options.getByRole('link').all()) {
      await option.click({ trial: true });
    }
    await page.keyboard.press('Escape');
    await expect(options).toBeHidden();
    await expect(button).toBeFocused();
    await button.click();
    await page.getByRole('heading', { name: 'Encontro PHP', exact: true }).click();
    await expect(options).toBeHidden();
    await expect(page.locator('a[href="https://example.org/ingressos"]').last()).toBeVisible();
  });
}

test('não exibe o botão quando o backend não oferece calendário', async ({ page }) => {
  await openEvent(page, 1, false);
  await expect(page.getByRole('button', { name: 'Adicionar ao calendário' })).toHaveCount(0);
});

test('opções cabem na largura de um celular', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 568 });
  await openEvent(page, 3);
  await page.getByRole('button', { name: 'Adicionar ao calendário' }).click();
  const panel = await page.getByRole('navigation', { name: 'Opções para adicionar ao calendário' }).boundingBox();
  expect(panel.x).toBeGreaterThanOrEqual(0);
  expect(panel.x + panel.width).toBeLessThanOrEqual(320);
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBe(320);
});
