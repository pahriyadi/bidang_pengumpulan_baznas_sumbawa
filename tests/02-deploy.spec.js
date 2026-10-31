// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('E2E SIM Pengumpulan - Endpoint Auto-Deploy Webhook', () => {
  test('2.1 Proteksi akses ilegal tanpa token secret', async ({ page }) => {
    const res = await page.goto('deploy.php');
    expect(res?.status()).toBe(403);
    const text = await res?.text();
    expect(text).toContain('Akses ditolak');
  });

  test('2.2 Verifikasi respons pemicu manual deploy.php dengan secret key yang valid', async ({ page }) => {
    const res = await page.goto('deploy.php?key=BaznasPengumpulan2026_AutoDeploy_88!');
    expect([200, 500]).toContain(res?.status());
    const json = await res?.json();
    expect(json).toHaveProperty('status');
    expect(json).toHaveProperty('timestamp');
    expect(json).toHaveProperty('method');
  });
});

