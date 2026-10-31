// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('E2E SIM Pengumpulan - Autentikasi & Antarmuka Login', () => {
  test('1.1 Membuka halaman login dan verifikasi komponen utama', async ({ page }) => {
    const res = await page.goto('login.php');
    expect(res?.status()).toBe(200);

    await expect(page).toHaveTitle(/Masuk \| SIM Pengumpulan ZIS/i);
    await expect(page.locator('.login-box')).toBeVisible();
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('1.2 Pengalihan otomatis saat mengakses index.php tanpa autentikasi', async ({ page }) => {
    await page.goto('index.php');
    await expect(page).toHaveURL(/login/);
  });

  test('1.3 Toggle tema Dark Mode / Light Mode dan persistensi localStorage', async ({ page }) => {
    await page.goto('login.php');
    const toggleBtn = page.locator('#btn-theme-toggle');
    await expect(toggleBtn).toBeVisible();

    await toggleBtn.click();
    const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark-mode') || document.body.classList.contains('dark-mode'));
    const stored = await page.evaluate(() => localStorage.getItem('theme'));
    expect(['dark', 'light']).toContain(stored);
  });

  test('1.4 Proteksi brute-force & notifikasi kesalahan input password', async ({ page }) => {
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'pahriyadi');
    await page.fill('input[name="password"]', 'PasswordSalah123_Test!');
    await page.click('button[type="submit"]');

    const alertBox = page.locator('.alert-danger');
    await expect(alertBox).toBeVisible();
    await expect(alertBox).toContainText(/Username atau password salah|Terlalu banyak percobaan/i);
  });

  test('1.5 Komponen Google Sign-In & penanganan form token Google', async ({ page }) => {
    await page.goto('login.php');
    const googleForm = page.locator('#googleLoginForm');
    await expect(googleForm).toBeAttached();
    await expect(page.locator('#google_credential')).toBeAttached();

    // Verifikasi container Google Button terpasang di DOM
    await expect(page.locator('#googleButtonContainer')).toBeVisible();
  });
});
